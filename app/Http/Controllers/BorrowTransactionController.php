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
use Illuminate\Support\Facades\Mail;

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

        $transaction = BorrowTransaction::findOrFail($request->id);
        $equipment   = Equipment::findOrFail($transaction->equipment_id);

        // Handle status change
        if ($transaction->status !== $request->status) {
            if ($request->status === 'Returned') {
                // Add back equipment
                $equipment->available_quantity += $transaction->quantity;
                $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                $equipment->save();

                // Log return
                ReturnLog::create([
                    'borrow_transaction_id' => $transaction->id,
                    'return_date'           => now(),
                    'condition'             => $request->condition ?? 'Good',
                    'remarks'               => $request->remarks ?? 'Auto logged from inline update',
                    'user_id'               => auth()->id(),
                ]);
            } elseif ($transaction->status === 'Returned' && $request->status === 'Borrowed') {
                // Borrowed again
                if ($equipment->available_quantity < $transaction->quantity) {
                    return response()->json(['message' => 'Not enough equipment available'], 422);
                }
                $equipment->available_quantity -= $transaction->quantity;
                $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                $equipment->save();
            }
        }

        $transaction->status = $request->status;
        $transaction->save();

        // The admin UI calls this via AJAX and expects a JSON response.
        return response()->json(['message' => 'Status updated successfully!']);
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

        // Loop through each selected equipment
        foreach ($validated['equipment'] as $equipmentId) {
            $quantity = $validated['quantities'][$equipmentId]; // Get the quantity for each equipment item

            $equipment = Equipment::findOrFail($equipmentId);

            // Check if enough equipment is available
            if ($status === 'Borrowed') {
                if ($equipment->available_quantity < $quantity) {
                    return redirect()->back()
                        ->withErrors(['quantity' => 'Not enough equipment available.'])
                        ->withInput();
                }

                // Update the equipment's available quantity
                $equipment->available_quantity -= $quantity;
                $equipment->save();
            }

            // Create a BorrowTransaction record for each equipment item
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

        return redirect()->back()->with('success', 'Transaction created successfully.');
    }

    public function sendManualEmail(Request $request, $id)
    {
        $transaction = BorrowTransaction::with(['user', 'equipment'])->findOrFail($id);

        if (! $transaction->user || ! $transaction->user->email) {
            return response()->json(['message' => 'User has no email address.'], 400);
        }

        $type    = $request->type;
        $message = $request->message;

        // TEMPLATE EMAIL
        if ($type === 'template') {

            $details = [
                'title' => 'Return Reminder',
                'body'  => "Hello {$transaction->user->name}, please return the equipment you borrowed ({$transaction->equipment->equipment_name}).",
            ];

            Mail::to($transaction->user->email)->send(new ReturnNotification($details));
        }

        // CUSTOM EMAIL
        else if ($type === 'custom') {

            $details = [
                'title' => 'Message from Admin',
                'body'  => $message,
            ];

            Mail::to($transaction->user->email)->send(new ReturnNotification($details));

        }

        return response()->json(['message' => 'Email sent successfully!']);
    }

    public function sendReturnAlertNotification()
    {
        $today = Carbon::today()->toDateString();

        // BorrowTransaction::whereDate('return_date', '<', Carbon::today())
        //     ->where('status', 'Borrowed')
        //     ->update(['status' => 'Overdue']);

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

                // Send the email
                Mail::to($transaction->user->email)->send(new ReturnNotification($details));

                // Save the notification into DB
                Notification::create([
                    'user_id'           => $transaction->user->id,
                    'message'           => $details['body'],
                    'notification_type' => 'Return Notice',
                    'send_date'         => Carbon::now(),
                ]);

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

        $transaction = BorrowTransaction::findOrFail($validated['id']);
        $equipment   = Equipment::findOrFail($validated['equipment_id']);

        // Calculate quantity difference if equipment changed
        if ($transaction->equipment_id != $validated['equipment_id']) {
            // Return old quantity to old equipment
            $oldEquipment = Equipment::findOrFail($transaction->equipment_id);
            if ($transaction->status === 'Borrowed') {
                $oldEquipment->available_quantity += $transaction->quantity;
                $oldEquipment->status = $oldEquipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                $oldEquipment->save();
            }

            // Deduct new quantity from new equipment if status is Borrowed
            if ($validated['status'] === 'Borrowed') {
                if ($equipment->available_quantity < $validated['quantity']) {
                    return redirect()->back()
                        ->withErrors(['quantity' => 'Not enough equipment available.'])
                        ->withInput();
                }
                $equipment->available_quantity -= $validated['quantity'];
                $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                $equipment->save();
            }
        } else {
            // Same equipment, check status changes and quantity difference
            if ($transaction->status === 'Borrowed' && $validated['status'] === 'Returned') {
                // Returned: add back quantity
                $equipment->available_quantity += $transaction->quantity;
            } elseif ($transaction->status === 'Returned' && $validated['status'] === 'Borrowed') {
                // Borrowed again: subtract quantity
                if ($equipment->available_quantity < $validated['quantity']) {
                    return redirect()->back()
                        ->withErrors(['quantity' => 'Not enough equipment available.'])
                        ->withInput();
                }
                $equipment->available_quantity -= $validated['quantity'];
            } elseif ($transaction->status === 'Borrowed' && $validated['status'] === 'Borrowed') {
                // Quantity changed while still borrowed
                if ($validated['quantity'] > $transaction->quantity
                    && ($validated['quantity'] - $transaction->quantity) > $equipment->available_quantity) {
                    return redirect()->back()
                        ->withErrors(['quantity' => 'Not enough equipment available.'])
                        ->withInput();
                }
                $diff = $transaction->quantity - $validated['quantity'];
                $equipment->available_quantity += $diff;
            }

            $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
            $equipment->save();
        }

        $transaction->update($validated);

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        $transaction = BorrowTransaction::findOrFail($id);
        $equipment   = Equipment::findOrFail($transaction->equipment_id);
        if ($transaction->status === 'Borrowed') {
            // If the transaction is still marked as 'Borrowed', return the equipment to available quantity
            $equipment->available_quantity += $transaction->quantity;
            $equipment->save();
        }
        $transaction->delete();
        return redirect()->back()->with('success', 'Transaction deleted successfully.');

    }
}
