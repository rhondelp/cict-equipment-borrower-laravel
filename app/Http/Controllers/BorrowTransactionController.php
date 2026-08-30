<?php
namespace App\Http\Controllers;

use App\Mail\ReturnNotification;
use App\Models\BorrowTransaction;
use App\Models\ClassSchedule;
use App\Models\Equipment;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BorrowTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $transactions   = BorrowTransaction::all();
        // $users          = User::all();
        // $equipment      = Equipment::all();
        // $classSchedules = ClassSchedule::with('instructor')
        //     ->whereHas('instructor', function ($query) {
        //         $query->where('user_type', 'Instructor');
        //     })
        //     ->get();

        // return view('admin.transaction', compact('transactions', 'users', 'equipment', 'classSchedules'));

        // with eager loading improvements
        $transactions   = BorrowTransaction::with(['user', 'equipment', 'classSchedule'])->get();
        $users          = User::with('borrowTransactions')->get();
        $equipment      = Equipment::with('borrowTransactions')->get();
        $classSchedules = ClassSchedule::with('instructor')
            ->whereHas('instructor', function ($query) {
                $query->where('user_type', 'Instructor');
            })
            ->get();

        return view('admin.transaction', compact('transactions', 'users', 'equipment', 'classSchedules'));

    }

    public function inlineUpdate(Request $request)
    {
        $request->validate([
            'id'        => 'required|exists:borrow_transactions,id',
            'status'    => 'required|in:Borrowed,Returned,Overdue',
            'condition' => 'nullable|string|max:50',
            'remarks'   => 'nullable|string|max:255',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $transaction = BorrowTransaction::findOrFail($request->id);
                // Lock equipment row to prevent concurrent race on available_quantity
                $equipment = Equipment::where('id', $transaction->equipment_id)->lockForUpdate()->firstOrFail();

                $oldStatus = $transaction->status;
                $newStatus = $request->status;
                $oldOut = in_array($oldStatus, ['Borrowed', 'Overdue']);
                $newOut = in_array($newStatus, ['Borrowed', 'Overdue']);

                if ($oldStatus !== $newStatus) {
                    if ($oldOut && !$newOut) {
                        // Returning: add back stock (Borrowed/Overdue -> Returned)
                        $equipment->available_quantity += $transaction->quantity;
                        $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                        $equipment->save();

                        ReturnLog::create([
                            'borrow_transaction_id' => $transaction->id,
                            'return_date'           => now(),
                            'condition'             => $request->condition ?? 'Good',
                            'remarks'               => $request->remarks ?? 'Auto logged from inline update',
                            'user_id'               => auth()->id(),
                        ]);
                    } elseif (!$oldOut && $newOut) {
                        // Re-borrowing: deduct stock (Returned -> Borrowed/Overdue)
                        if ($equipment->available_quantity < $transaction->quantity) {
                            throw ValidationException::withMessages(['quantity' => 'Not enough equipment available.']);
                        }
                        $equipment->available_quantity -= $transaction->quantity;
                        $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                        $equipment->save();
                    }
                    // Borrowed <-> Overdue : no stock change (both are "out")
                }

                $transaction->status = $newStatus;
                $transaction->save();

                return $transaction;
            });

            return response()->json(['message' => 'Status updated successfully!']);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->errors()['quantity'][0] ?? 'Not enough equipment available'], 422);
        }
    }

    public function getOnlyTransactionsHasClassSchedule()
    {
        $transactions = BorrowTransaction::with(['user', 'equipment', 'classSchedule.instructor'])
            ->whereNotNull('class_schedule_id')
            ->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'user_id'           => 'required|exists:users,id',
            'equipment'         => 'required|array|min:1',
            'equipment.*'       => 'exists:equipment,id',
            'quantities'        => 'required|array|min:1',
            'quantities.*'      => 'integer|min:1',
            'borrow_date'       => 'required|date',
            'return_date'       => 'required|date|after_or_equal:borrow_date',
            'purpose'           => 'required|string|max:255',
            'status'            => 'required|in:Borrowed,Returned,Overdue',
            'remarks'           => 'nullable|string',
            'class_schedule_id' => 'nullable|exists:class_schedules,id',
        ]);

        $userId          = $validated['user_id'];
        $borrowDate      = $validated['borrow_date'];
        $returnDate      = $validated['return_date'];
        $status          = $validated['status'];
        $remarks         = $validated['remarks'] ?? null;
        $classScheduleId = $validated['class_schedule_id'] ?? null;
        $purpose         = $validated['purpose'];

        // FIX: wrap multi-equipment loop in a transaction with row locking to prevent race
        // and to avoid partial writes when one item lacks stock. Also handle Overdue as "out" like Borrowed.
        $isOut = in_array($status, ['Borrowed', 'Overdue']);

        try {
            DB::transaction(function () use ($validated, $userId, $borrowDate, $returnDate, $status, $remarks, $classScheduleId, $purpose, $isOut) {
                foreach ($validated['equipment'] as $equipmentId) {
                    // quantities may be keyed as string numeric; handle missing key gracefully
                    if (!isset($validated['quantities'][$equipmentId])) {
                        throw ValidationException::withMessages(['quantity' => "Quantity missing for equipment #{$equipmentId}."]);
                    }
                    $quantity = (int) $validated['quantities'][$equipmentId];

                    $equipment = Equipment::where('id', $equipmentId)->lockForUpdate()->firstOrFail();

                    if ($isOut) {
                        if ($equipment->available_quantity < $quantity) {
                            throw ValidationException::withMessages(['quantity' => "Not enough {$equipment->equipment_name} available (have {$equipment->available_quantity}, need {$quantity})."]);
                        }
                        $equipment->available_quantity -= $quantity;
                        $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                        $equipment->save();
                    }

                    BorrowTransaction::create([
                        'user_id'           => $userId,
                        'equipment_id'      => $equipmentId,
                        'borrow_date'       => $borrowDate,
                        'return_date'       => $returnDate,
                        'quantity'          => $quantity,
                        'purpose'           => $purpose,
                        'status'            => $status,
                        'remarks'           => $remarks,
                        'class_schedule_id' => $classScheduleId,
                    ]);
                }
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()->back()->with('success', 'Transaction created successfully.');
    }

    public function sendManualEmail(Request $request, $id)
    {
        $transaction = BorrowTransaction::with(['user', 'equipment'])->findOrFail($id);

        if (! $transaction->user || ! $transaction->user->email) {
            return response()->json(['message' => 'User has no email address.'], 400);
        }

        $type    = $request->input('type');
        $message = $request->input('message');

        if ($type === 'custom' && empty(trim((string) $message))) {
            return response()->json(['message' => 'Custom message cannot be empty.'], 422);
        }

        $details = $type === 'custom'
            ? ['title' => 'Message from Admin', 'body' => $message]
            : ['title' => 'Return Reminder', 'body' => "Hello {$transaction->user->name}, please return the equipment you borrowed ({$transaction->equipment->equipment_name})."];

        try {
            Mail::to($transaction->user->email)->send(new ReturnNotification($details));
        } catch (\Exception $e) {
            \Log::error('Manual email failed for transaction ' . $id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Email sent successfully!']);
    }

    public function sendReturnAlertNotification()
    {
        $today = Carbon::today()->toDateString();

        // FIX: Automatically mark overdue transactions (previously commented out — Overdue was never written).
        // Do this atomically before sending today's reminders.
        BorrowTransaction::where('status', 'Borrowed')
            ->whereDate('return_date', '<', $today)
            ->update(['status' => 'Overdue']);

        // Find all borrow transactions with return_date == today and status still "Borrowed"
        $transactions = BorrowTransaction::with(['user', 'equipment'])
            ->whereDate('return_date', $today)
            ->where('status', 'Borrowed')
            ->get();

        $sent = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->user && $transaction->user->email) {
                $details = [
                    'title' => 'Return Reminder',
                    'body'  => "Hello {$transaction->user->name}, please return the equipment you borrowed ({$transaction->equipment->equipment_name}) today ("
                    . Carbon::parse($transaction->return_date)->format('F j, Y') . ").",
                ];

                // Skip users who already received a return notice today
                // (guards against double sends when multiple triggers run the same day).
                $alreadyNotified = Notification::where('user_id', $transaction->user->id)
                    ->where('notification_type', 'Return Notice')
                    ->whereDate('send_date', $today)
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                try {
                    Mail::to($transaction->user->email)->send(new ReturnNotification($details));
                } catch (\Exception $e) {
                    \Log::error('Return notification mail failed for transaction ' . $transaction->id . ': ' . $e->getMessage());
                    continue;
                }

                // Save the notification into DB inside a transaction to keep mail+DB consistent
                try {
                    DB::transaction(function () use ($transaction, $details) {
                        // Double-check inside transaction to avoid race
                        $exists = Notification::where('user_id', $transaction->user->id)
                            ->where('notification_type', 'Return Notice')
                            ->whereDate('send_date', Carbon::today()->toDateString())
                            ->exists();
                        if (!$exists) {
                            Notification::create([
                                'user_id'           => $transaction->user->id,
                                'message'           => $details['body'],
                                'notification_type' => 'Return Notice',
                                'send_date'         => Carbon::now(),
                            ]);
                        }
                    });
                } catch (\Exception $e) {
                    \Log::error('Notification DB log failed for transaction ' . $transaction->id . ': ' . $e->getMessage());
                }

                $sent++;
            }
        }

        return $sent . " return notifications sent and logged for today.";
    }

    /**
     * Display the specified resource.
     */
    public function show(BorrowTransaction $borrowTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BorrowTransaction $borrowTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'                => 'required|exists:borrow_transactions,id',
            'user_id'           => 'required|exists:users,id',
            'equipment_id'      => 'required|exists:equipment,id',
            'borrow_date'       => 'required|date',
            'return_date'       => 'nullable|date|after_or_equal:borrow_date',
            'quantity'          => 'required|integer|min:1',
            'purpose'           => 'required|string|max:255',
            'status'            => 'required|in:Borrowed,Returned,Overdue',
            'remarks'           => 'nullable|string',
            'class_schedule_id' => 'nullable|exists:class_schedules,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $transaction = BorrowTransaction::where('id', $validated['id'])->lockForUpdate()->firstOrFail();

                $oldEquipmentId = $transaction->equipment_id;
                $newEquipmentId = $validated['equipment_id'];
                $oldStatus = $transaction->status;
                $newStatus = $validated['status'];
                $oldQty = $transaction->quantity;
                $newQty = $validated['quantity'];

                $oldOut = in_array($oldStatus, ['Borrowed', 'Overdue']);
                $newOut = in_array($newStatus, ['Borrowed', 'Overdue']);

                if ($oldEquipmentId != $newEquipmentId) {
                    // Lock both equipment rows in consistent order to avoid deadlock
                    $ids = collect([$oldEquipmentId, $newEquipmentId])->sort()->values();
                    $locked = Equipment::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
                    $oldEquipment = $locked[$oldEquipmentId];
                    $equipment = $locked[$newEquipmentId];

                    if ($oldOut) {
                        $oldEquipment->available_quantity += $oldQty;
                        $oldEquipment->status = $oldEquipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                        $oldEquipment->save();
                    }
                    if ($newOut) {
                        if ($equipment->available_quantity < $newQty) {
                            throw ValidationException::withMessages(['quantity' => 'Not enough equipment available.']);
                        }
                        $equipment->available_quantity -= $newQty;
                        $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                        $equipment->save();
                    }
                } else {
                    $equipment = Equipment::where('id', $oldEquipmentId)->lockForUpdate()->firstOrFail();

                    if ($oldOut && !$newOut) {
                        // Out -> Returned: restore old quantity
                        $equipment->available_quantity += $oldQty;
                    } elseif (!$oldOut && $newOut) {
                        // Returned -> Out: deduct new quantity
                        if ($equipment->available_quantity < $newQty) {
                            throw ValidationException::withMessages(['quantity' => 'Not enough equipment available.']);
                        }
                        $equipment->available_quantity -= $newQty;
                    } elseif ($oldOut && $newOut) {
                        // Out -> Out with possible quantity change
                        $diff = $newQty - $oldQty; // positive means need more stock
                        if ($diff > 0 && $equipment->available_quantity < $diff) {
                            throw ValidationException::withMessages(['quantity' => 'Not enough equipment available.']);
                        }
                        $equipment->available_quantity -= $diff;
                    }
                    // Returned -> Returned : no stock change even if quantity changed

                    $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                    $equipment->save();
                }

                $transaction->update($validated);
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $transaction = BorrowTransaction::where('id', $id)->lockForUpdate()->firstOrFail();
            $equipment = Equipment::where('id', $transaction->equipment_id)->lockForUpdate()->firstOrFail();
            // Both Borrowed and Overdue are "out" and should restore stock on delete
            if (in_array($transaction->status, ['Borrowed', 'Overdue'])) {
                $equipment->available_quantity += $transaction->quantity;
                $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                $equipment->save();
            }
            $transaction->delete();
        });

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }
}
