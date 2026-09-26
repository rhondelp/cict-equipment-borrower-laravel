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

        // Two queues the dashboard leads with that are not loans or requests.
        // Each is one query, counted in the database rather than by pulling the
        // whole table in and filtering it in PHP.
        $unresolvedIncidents = ReturnLog::with(['borrower', 'equipment'])
            ->where('condition', '!=', 'Good')
            ->whereNull('resolved_at')
            ->orderBy('return_date')
            ->get();

        $instructorRequests = User::whereNotNull('instructor_requested_at')
            ->whereNull('deactivated_at')
            ->orderBy('instructor_requested_at')
            ->get();

        $restrictedAccounts = User::query()
            ->where(fn ($query) => $query->whereNotNull('deactivated_at')->orWhereNotNull('suspended_at'))
            ->orderBy('name')
            ->get();

        // Counted here rather than from $returnLogs, which is capped at six for
        // the activity feed and so could never report more than six.
        $returnedThisWeek = ReturnLog::where('return_date', '>=', now()->subWeek())->count();

        $openLoans = $transactions->filter(fn ($transaction) => $transaction->isOut())
            ->sortBy(fn ($transaction) => $transaction->return_date?->timestamp ?? PHP_INT_MAX)
            ->values();

        $attention = $this->adminAttention(
            $requests, $openLoans, $equipments, $unresolvedIncidents, $restrictedAccounts, $instructorRequests
        );

        // What blocks approvals: an item with nothing on the shelf cannot be
        // lent however many requests are waiting for it.
        $stockWatch = $equipments
            ->reject(fn ($item) => $item->isRetired())
            ->filter(fn ($item) => in_array($item->availabilityState()['key'], ['out', 'low'], true))
            ->sortBy('available_quantity')
            ->values();

        return view('admin.dashboard', compact(
            'equipments', 'users', 'transactions', 'requests', 'returnLogs', 'openLoans',
            'attention', 'returnedThisWeek', 'stockWatch', 'unresolvedIncidents', 'restrictedAccounts'
        ));
    }

    /**
     * The ordered list of things an admin has to do something about, worst
     * first. Every entry names a number and where to act on it; an empty list
     * is itself the answer, and the view says so in words.
     *
     * @return list<array{tone: string, title: string, detail: string, action: string, url: string}>
     */
    private function adminAttention(
        $requests, $openLoans, $equipments, $unresolvedIncidents = null, $restrictedAccounts = null,
        $instructorRequests = null
    ): array {
        $attention = [];
        $unresolvedIncidents ??= collect();
        $restrictedAccounts ??= collect();
        $instructorRequests ??= collect();

        $overdue = $openLoans->filter(fn ($loan) => $loan->isOverdue())->values();
        if ($overdue->isNotEmpty()) {
            $worst = $overdue->sortByDesc(fn ($loan) => $loan->daysLate())->first();
            $attention[] = [
                'tone' => 'danger',
                'title' => $overdue->count().' '.str('loan')->plural($overdue->count()).' overdue',
                'detail' => ($worst->equipment->equipment_name ?? 'Equipment').' ×'.$worst->quantity
                    .' with '.($worst->user->name ?? 'a deleted user').' · '.$worst->dateLine(),
                'action' => 'Follow up',
                'url' => route('admin.transaction', ['filter' => 'overdue']),
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
                'url' => route('admin.transaction', ['filter' => 'active']),
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
                'url' => route('admin.equipment', ['filter' => 'out']),
            ];
        }

        // Something came back damaged or lost and nobody has recorded what was
        // done about it. This is the only queue on the dashboard that does not
        // age out on its own — a loan gets returned, a request gets decided,
        // an unresolved incident simply sits there.
        if ($unresolvedIncidents->isNotEmpty()) {
            $oldest = $unresolvedIncidents->first();
            $attention[] = [
                'tone' => 'warning',
                'title' => $unresolvedIncidents->count().' damaged or lost '
                    .str('return')->plural($unresolvedIncidents->count()).' with no outcome recorded',
                'detail' => ($oldest->equipment->equipment_name ?? 'Equipment').' — '.strtolower($oldest->condition)
                    .', returned '.($oldest->return_date?->format('M j') ?? 'recently')
                    .' by '.($oldest->borrower->name ?? 'a deleted user'),
                'action' => 'Record outcomes',
                'url' => route('admin.logs', ['filter' => 'followup']),
            ];
        }

        // People who signed up as instructors. Their accounts already work as
        // Student accounts, so this is not urgent, but nothing else moves it.
        if ($instructorRequests->isNotEmpty()) {
            $oldest = $instructorRequests->first();
            $attention[] = [
                'tone' => 'primary',
                'title' => $instructorRequests->count().' instructor '
                    .str('request')->plural($instructorRequests->count()).' to confirm',
                'detail' => $instructorRequests->take(2)->pluck('name')->implode(', ')
                    .($instructorRequests->count() > 2 ? ' and '.($instructorRequests->count() - 2).' more' : '')
                    .' · oldest asked '.($oldest->instructor_requested_at?->format('M j') ?? 'recently'),
                'action' => 'Confirm or decline',
                'url' => route('admin.users', ['filter' => 'requested']),
            ];
        }

        // Accounts a human has already restricted. There is no approval queue
        // for new accounts — registration creates a working one.
        if ($restrictedAccounts->isNotEmpty()) {
            $suspended = $restrictedAccounts->filter(fn ($user) => $user->isSuspended())->count();
            $deactivated = $restrictedAccounts->count() - $suspended;

            $attention[] = [
                'tone' => 'primary',
                'title' => $restrictedAccounts->count().' restricted '
                    .str('account')->plural($restrictedAccounts->count()),
                'detail' => collect([
                    $suspended > 0 ? $suspended.' suspended from borrowing' : null,
                    $deactivated > 0 ? $deactivated.' unable to sign in' : null,
                ])->filter()->implode(' · '),
                'action' => 'Review',
                'url' => route('admin.users', ['filter' => 'suspended']),
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

    /**
     * Public sign-up.
     *
     * Deliberately separate from register() below, which serves the admin
     * users form: that one is behind `userType:Admin` and an admin picking a
     * borrower's role is a decision they are entitled to make.
     *
     * Students and instructors share one email domain, so the form asks which
     * one you are — but out here nobody is authenticated, so the answer is a
     * request, not a grant. The account is always written as a Student.
     * Answering Instructor stamps `instructor_requested_at`, and an admin
     * confirms or declines it on the users screen. `user_type` is never read
     * from the request, so there is no field to tamper with.
     */
    public function registerPublic(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255', 'unique:users',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! User::isSchoolEmail($value)) {
                        $fail('Use your school address — @'.User::SCHOOL_DOMAIN.'.');
                    }
                },
            ],
            'requested_role' => 'required|in:Student,Instructor',
            // The three rules the form states before submit, in the same order
            // it states them. Confirm-password is gone: it catches a typo the
            // reveal toggle already prevents, at the cost of a whole field.
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'contact_number' => 'nullable|string|max:15',
            'agree' => 'accepted',
        ], [
            'requested_role.required' => 'Say whether you are a student or an instructor.',
            'requested_role.in' => 'Choose Student or Instructor.',
            'password.min' => 'Your password needs at least 8 characters.',
            'password.regex' => 'Your password needs both letters and numbers.',
            'agree.accepted' => 'Tick the agreement to continue.',
        ]);

        $wantsInstructor = $validated['requested_role'] === 'Instructor';

        $user = User::create([
            // Never taken from the request: Instructor is granted by an admin.
            'user_type' => 'Student',
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'contact_number' => $validated['contact_number'] ?? null,
            'instructor_requested_at' => $wantsInstructor ? now() : null,
        ]);

        // Not `success`: that key throws the shared SweetAlert modal, which
        // would land on top of the page's own success state saying the same
        // thing twice. The page reads `registered` and renders it inline.
        return redirect()->route('register')->with('registered', [
            'email' => $user->email,
            'role' => $user->user_type,
            'instructor_requested' => $wantsInstructor,
        ]);
    }

    /**
     * The admin users form (`POST /admin/users`). Still takes an explicit
     * user_type, capped at the two borrower roles — an Admin account has no
     * web path by design.
     */
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
