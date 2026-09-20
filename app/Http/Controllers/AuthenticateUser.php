<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser extends Controller
{
    /**
     * The admin dashboard leads with the work queue, so the queue is assembled
     * here rather than inferred from four collections in the template. Counters
     * that read zero are not a dashboard; "3 things need you today" is.
     */
    public function adminView()
    {
        $equipments = Equipment::query()
            ->withSum([
                'borrowTransactions as units_out' => fn ($query) => $query->whereNull('voided_at')
                    ->whereIn('status', ['Borrowed', 'Overdue']),
            ], 'quantity')
            ->orderBy('equipment_name')
            ->get();

        $users = User::all();

        $transactions = BorrowTransaction::with(['user', 'equipment', 'returnLog'])
            ->whereNull('voided_at')
            ->get();

        $requests = ItemRequest::with(['user', 'equipment'])->get();
        $returnLogs = ReturnLog::with(['borrower', 'receiver', 'equipment'])->latest()->take(6)->get();

        // Counted here rather than from $returnLogs, which is capped at six for
        // the activity feed and so could never report more than six.
        $returnedThisWeek = ReturnLog::where('return_date', '>=', now()->subWeek())->count();

        $openLoans = $transactions->filter(fn ($transaction) => $transaction->isOut())
            ->sortBy(fn ($transaction) => $transaction->return_date?->timestamp ?? PHP_INT_MAX)
            ->values();

        $attention = $this->adminAttention($requests, $openLoans, $equipments);

        return view('admin.dashboard', compact(
            'equipments', 'users', 'transactions', 'requests', 'returnLogs', 'openLoans', 'attention', 'returnedThisWeek'
        ));
    }

    /**
     * The ordered list of things an admin has to do something about, worst
     * first. Every entry names a number and where to act on it; an empty list
     * is itself the answer, and the view says so in words.
     *
     * @return list<array{tone: string, title: string, detail: string, action: string, url: string}>
     */
    private function adminAttention($requests, $openLoans, $equipments): array
    {
        $attention = [];

        $overdue = $openLoans->filter(fn ($loan) => $loan->isOverdue())->values();
        if ($overdue->isNotEmpty()) {
            $worst = $overdue->sortByDesc(fn ($loan) => $loan->daysLate())->first();
            $attention[] = [
                'tone' => 'danger',
                'title' => $overdue->count().' '.str('loan')->plural($overdue->count()).' overdue',
                'detail' => ($worst->equipment->equipment_name ?? 'Equipment').' ×'.$worst->quantity
                    .' with '.($worst->user->name ?? 'a deleted user').' · '.$worst->dateLine(),
                'action' => 'Follow up',
                'url' => route('admin.transaction'),
            ];
        }

        $pending = $requests->where('status', 'Pending');
        if ($pending->isNotEmpty()) {
            $oldest = $pending->sortBy(fn ($request) => $request->requested_date?->timestamp ?? PHP_INT_MAX)->first();
            $waiting = $oldest->requested_date
                ? (int) $oldest->requested_date->startOfDay()->diffInDays(now()->startOfDay())
                : 0;
            $blocked = $pending->reject(fn ($request) => $request->canBeFilled())->count();

            $attention[] = [
                'tone' => $blocked > 0 ? 'warning' : 'primary',
                'title' => $pending->count().' '.str('request')->plural($pending->count()).' awaiting review',
                'detail' => ($waiting > 0 ? 'Oldest has waited '.$waiting.' '.str('day')->plural($waiting) : 'Newest arrived today')
                    .($blocked > 0 ? ' · '.$blocked.' cannot be filled from current stock' : ' · all fillable from stock'),
                'action' => 'Review',
                'url' => route('admin.request'),
            ];
        }

        $dueToday = $openLoans->filter(fn ($loan) => $loan->daysUntilDue() === 0)->values();
        if ($dueToday->isNotEmpty()) {
            $attention[] = [
                'tone' => 'warning',
                'title' => $dueToday->count().' '.str('loan')->plural($dueToday->count()).' due back today',
                'detail' => $dueToday->take(2)->map(fn ($loan) => ($loan->equipment->equipment_name ?? 'Equipment')
                    .' · '.($loan->user->name ?? 'deleted user'))->implode(' · '),
                'action' => 'Send reminders',
                'url' => route('admin.transaction'),
            ];
        }

        $fullyOut = $equipments->filter(fn ($item) => ! $item->isRetired() && $item->available_quantity <= 0)->values();
        if ($fullyOut->isNotEmpty()) {
            $attention[] = [
                'tone' => 'warning',
                'title' => $fullyOut->count().' '.str('item')->plural($fullyOut->count()).' fully out',
                'detail' => $fullyOut->take(3)->pluck('equipment_name')->implode(', ')
                    .' — nothing left to lend.',
                'action' => 'View stock',
                'url' => route('admin.equipment'),
            ];
        }

        return $attention;
    }

    /**
     * The borrower dashboard is a to-do list, not a set of counters: one row
     * per thing this person has to do, soonest first.
     */
    public function borrowerView()
    {
        $userId = Auth::id();
        $requests = ItemRequest::where('user_id', $userId)->with(['equipment', 'decider'])->get();
        $transactions = BorrowTransaction::where('user_id', $userId)
            ->whereNull('voided_at')
            ->with(['equipment', 'returnLog'])
            ->get();
        $equipments = Equipment::lendable()->orderBy('equipment_name')->get();
        // Read-only feed for the dashboard bell. Rows are written elsewhere by
        // BorrowTransactionController::sendReturnAlertNotification.
        $notifications = Notification::where('user_id', $userId)->latest()->take(10)->get();

        $agenda = $this->borrowerAgenda($requests, $transactions);
        $history = $this->borrowerHistory($requests, $transactions);

        return view('borrower.dashboard', compact(
            'requests', 'transactions', 'equipments', 'notifications', 'agenda', 'history'
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function borrowerAgenda($requests, $transactions): array
    {
        $agenda = [];

        foreach ($transactions->filter(fn ($transaction) => $transaction->isOut()) as $loan) {
            $days = $loan->daysUntilDue();
            $overdue = $loan->isOverdue();
            $soon = $days !== null && $days >= 0 && $days <= 1;

            $agenda[] = [
                'kind' => 'loan',
                'rank' => $overdue ? 0 : ($soon ? 1 : 4),
                'date' => $loan->return_date,
                'tag' => $overdue ? 'Overdue' : ($soon ? 'Due soon' : 'On loan'),
                'tone' => $overdue ? 'danger' : ($soon ? 'warning' : 'primary'),
                'title' => 'Return '.($loan->equipment->equipment_name ?? 'equipment')
                    .($loan->quantity > 1 ? ' ×'.$loan->quantity : ''),
                'when' => $loan->timingLabel(),
                'detail' => $overdue
                    ? 'Taken out '.$loan->borrow_date->format('M j').'. Bring it to the equipment room today — '
                        .'anything overdue holds up your next request.'
                    : rtrim((string) $loan->purpose, '. ').'. Booked out '.$loan->dateRangeLabel().'.',
                'action' => 'Print slip',
                'url' => route('borrower.transaction.receipt', $loan->id),
                'id' => $loan->id,
            ];
        }

        foreach ($requests->where('status', 'Pending') as $request) {
            $agenda[] = [
                'kind' => 'pending',
                'rank' => 3,
                'date' => $request->requested_date,
                'tag' => 'Waiting',
                'tone' => 'warning',
                'title' => ($request->equipment->equipment_name ?? 'Equipment')
                    .($request->quantity > 1 ? ' ×'.$request->quantity : '').' requested',
                'when' => 'sent '.($request->requested_date?->diffForHumans() ?? 'recently'),
                'detail' => ($request->remarks ? $request->remarks.'. ' : '')
                    .'Nothing is held for you until an admin approves it.',
                'action' => 'Withdraw',
                'url' => null,
                'id' => $request->id,
            ];
        }

        // A decline the borrower has not seen yet is work too: it is the only
        // place the reason for it ever reaches them.
        foreach ($requests->where('status', 'Declined') as $request) {
            if (! $this->isRecentDecision($request)) {
                continue;
            }

            $agenda[] = [
                'kind' => 'declined',
                'rank' => 2,
                'date' => $this->decidedAt($request),
                'tag' => 'Declined',
                'tone' => 'danger',
                'title' => ($request->equipment->equipment_name ?? 'Equipment')
                    .($request->quantity > 1 ? ' ×'.$request->quantity : '').' was declined',
                'when' => $this->decidedAt($request)?->diffForHumans() ?? 'recently',
                'detail' => $request->decision_reason ?: 'No reason was recorded.',
                'action' => null,
                'url' => null,
                'id' => $request->id,
            ];
        }

        usort($agenda, function ($a, $b) {
            $left = $a['date']?->timestamp ?? PHP_INT_MAX;
            $right = $b['date']?->timestamp ?? PHP_INT_MAX;

            return [$a['rank'], $left] <=> [$b['rank'], $right];
        });

        return $agenda;
    }

    /**
     * Everything already settled, folded away under the agenda.
     *
     * @return list<array<string, mixed>>
     */
    private function borrowerHistory($requests, $transactions): array
    {
        $history = [];

        foreach ($transactions->where('status', 'Returned') as $loan) {
            $history[] = [
                'tone' => 'success',
                'title' => ($loan->equipment->equipment_name ?? 'Equipment')
                    .($loan->quantity > 1 ? ' ×'.$loan->quantity : ''),
                'note' => ucfirst($loan->timingLabel()),
                'when' => $loan->returnLog?->return_date ?? $loan->return_date,
                'id' => $loan->id,
                'receipt' => route('borrower.transaction.receipt', $loan->id),
            ];
        }

        foreach ($requests->whereIn('status', ['Approved', 'Declined']) as $request) {
            $isDeclined = $request->status === 'Declined';

            // A decline inside the two-week window is already on the agenda.
            if ($isDeclined && $this->isRecentDecision($request)) {
                continue;
            }

            $history[] = [
                'tone' => $isDeclined ? 'danger' : 'success',
                'title' => ($request->equipment->equipment_name ?? 'Equipment')
                    .($request->quantity > 1 ? ' ×'.$request->quantity : ''),
                'note' => $isDeclined
                    ? 'Declined — '.($request->decision_reason ?: 'no reason recorded')
                    : 'Approved and handed over',
                'when' => $this->decidedAt($request),
                'id' => 'r'.$request->id,
                'receipt' => null,
            ];
        }

        usort($history, fn ($a, $b) => ($b['when']?->timestamp ?? 0) <=> ($a['when']?->timestamp ?? 0));

        return $history;
    }

    /**
     * When a request was decided. Rows written before the decision trail
     * existed have no decided_at, so `updated_at` stands in — and because both
     * the agenda and the history ask the same question here, a decline lands in
     * exactly one of the two lists.
     */
    private function decidedAt(ItemRequest $request): ?\Carbon\Carbon
    {
        return $request->decided_at ?? $request->updated_at;
    }

    private function isRecentDecision(ItemRequest $request): bool
    {
        $decided = $this->decidedAt($request);

        return $decided !== null && $decided->gte(now()->subDays(14));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // The "keep me signed in" checkbox is passed through rather than
            // ignored. It was on the form before this and did nothing, which
            // made it a control that reported a state the session did not have.
            // The validation rules above are untouched; `remember` is a flag,
            // not a credential.
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $user = Auth::user();

                // Deactivating an account has to mean something, or the Remove
                // dialog is offering a button that does nothing.
                if ($user->isDeactivated()) {
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'This account has been deactivated. Ask the equipment office to restore it.',
                    ])->onlyInput('email');
                }

                $request->session()->regenerate();

                $msg = 'Welcome back, '.$user->name.'!';
                // FIX: flash both 'welcome' (legacy) and 'success' so shared alerts + existing checks show it
                $request->session()->flash('welcome', $msg);
                $request->session()->flash('success', $msg);

                if ($user->user_type === 'Admin') {
                    return redirect()->intended(route('admin.dashboard'));
                } else {
                    return redirect()->intended(route('borrower.dashboard'));
                }
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');

        } catch (\Exception $e) {
            \Log::error('Login error: '.$e->getMessage());

            return back()->withErrors([
                'email' => 'Something went wrong. Please try again later.',
            ]);
        }
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function registerUser()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'user_type' => 'required|in:Instructor,Student',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4|confirmed',
            'contact_number' => 'nullable|string|max:15',
        ]);

        $user = User::create([
            'user_type' => $validatedData['user_type'],
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'contact_number' => $validatedData['contact_number'] ?? null,
        ]);

        return redirect()->back()->with('success', $user->name.' added as a '.strtolower($user->user_type).'.');
    }
}
