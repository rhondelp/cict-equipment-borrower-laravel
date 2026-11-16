<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthenticateUser extends Controller
{
    public function adminView()
    {
        $equipments = Equipment::query()
            ->withSum([
                'borrowTransactions as units_out' => fn ($query) => $query->out(),
            ], 'quantity')
            ->orderBy('equipment_name')
            ->get();

        $users = User::all();

        $transactions = BorrowTransaction::with(['user', 'equipment', 'returnLog'])
            ->whereNull('voided_at')
            ->get();

        $requests = ItemRequest::with(['user', 'equipment'])->get();
        $returnLogs = ReturnLog::with(['borrower', 'receiver', 'equipment'])->latest()->take(6)->get();

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

        $returnedThisWeek = ReturnLog::where('return_date', '>=', now()->subWeek())->count();

        $openLoans = $transactions->filter(fn ($transaction) => $transaction->isOut())
            ->sortBy(fn ($transaction) => $transaction->dueAt()?->timestamp ?? PHP_INT_MAX)
            ->values();

        $attention = $this->adminAttention(
            $requests, $openLoans, $equipments, $unresolvedIncidents, $restrictedAccounts, $instructorRequests
        );

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
            $worst = $overdue->sortBy(fn ($loan) => $loan->dueAt()?->timestamp)->first();
            $attention[] = [
                'tone' => 'danger',
                'title' => $overdue->count().' '.str('borrow')->plural($overdue->count()).' overdue',
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
                'title' => $dueToday->count().' '.str('borrow')->plural($dueToday->count()).' due back today',
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

    public function borrowerView()
    {
        $userId = Auth::id();
        $requests = ItemRequest::where('user_id', $userId)->with(['equipment', 'decider'])->get();
        $transactions = BorrowTransaction::where('user_id', $userId)
            ->whereNull('voided_at')
            ->with(['equipment', 'returnLog'])
            ->get();
        $equipments = Equipment::lendable()->orderBy('equipment_name')->get();
        $notifications = Notification::where('user_id', $userId)->latest()->take(10)->get();

        $agenda = $this->borrowerAgenda($requests, $transactions);
        $history = $this->borrowerHistory($requests, $transactions);

        return view('borrower.dashboard', compact(
            'requests', 'transactions', 'equipments', 'notifications', 'agenda', 'history'
        ));
    }

    private function borrowerAgenda($requests, $transactions): array
    {
        $agenda = [];

        foreach ($this->openBookings($transactions) as $booking) {
            $loan = $booking->first();
            $days = $loan->daysUntilDue();
            $overdue = $booking->contains(fn ($tx) => $tx->isOverdue());
            $soon = $days !== null && $days >= 0 && $days <= 1;
            $many = $booking->count() > 1;

            $agenda[] = [
                'kind' => 'loan',
                'rank' => $overdue ? 0 : ($soon ? 1 : 4),
                'date' => $loan->return_date,
                'tag' => $overdue ? 'Overdue' : ($soon ? 'Due soon' : 'Borrowed'),
                'tone' => $overdue ? 'danger' : ($soon ? 'warning' : 'primary'),
                'title' => $many
                    ? 'Return '.$booking->count().' items'
                    : 'Return '.($loan->equipment->equipment_name ?? 'equipment')
                        .($loan->quantity > 1 ? ' ×'.$loan->quantity : ''),
                'items' => $many
                    ? $booking->map(fn ($tx) => [
                        'name' => $tx->equipment->equipment_name ?? 'Equipment',
                        'quantity' => $tx->quantity,
                    ])->values()->all()
                    : [],
                'when' => $loan->timingLabel(),
                'detail' => $overdue
                    ? 'Taken out '.$loan->borrow_date->format('M j').'. Bring '.($many ? 'them' : 'it')
                        .' to the equipment room today — anything overdue holds up your next request.'
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

        foreach ($transactions->filter(fn ($transaction) => $transaction->isIssued()) as $issue) {
            $history[] = [
                'tone' => 'neutral',
                'title' => ($issue->equipment->equipment_name ?? 'Equipment')
                    .($issue->quantity > 1 ? ' ×'.$issue->quantity : ''),
                'note' => 'Issued to you — no return needed',
                'when' => $issue->borrow_date,
                'id' => $issue->id,
                'receipt' => route('borrower.transaction.receipt', $issue->id),
            ];
        }

        foreach ($requests->where('status', 'Declined') as $request) {
            if ($this->isRecentDecision($request)) {
                continue;
            }

            $history[] = [
                'tone' => 'danger',
                'title' => ($request->equipment->equipment_name ?? 'Equipment')
                    .($request->quantity > 1 ? ' ×'.$request->quantity : ''),
                'note' => 'Declined — '.($request->decision_reason ?: 'no reason recorded'),
                'when' => $this->decidedAt($request),
                'id' => 'r'.$request->id,
                'receipt' => null,
            ];
        }

        usort($history, fn ($a, $b) => ($b['when']?->timestamp ?? 0) <=> ($a['when']?->timestamp ?? 0));

        return $history;
    }

    private function openBookings($transactions)
    {
        return $transactions
            ->filter(fn ($transaction) => $transaction->isOut())
            ->sortBy('id')
            ->groupBy(fn ($transaction) => $transaction->bookingKey())
            ->values();
    }

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
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $user = Auth::user();

                if ($user->isDeactivated()) {
                    ActivityLog::record('login_failed', [
                        'actor' => null,
                        'actor_name' => ActivityLog::ACTOR_ANONYMOUS,
                        'subject' => $user,
                        'details' => 'Sign-in refused for '.$credentials['email'].': the account is deactivated.',
                    ]);

                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'This account has been deactivated. Ask the equipment office to restore it.',
                    ])->onlyInput('email');
                }

                $request->session()->regenerate();

                ActivityLog::record('login', [
                    'actor' => $user,
                    'subject' => $user,
                    'details' => 'Signed in.',
                    'meta' => array_filter(['remember' => $request->boolean('remember') ?: null]),
                ]);

                $msg = 'Welcome back, '.$user->name.'!';
                $request->session()->flash('welcome', $msg);
                $request->session()->flash('success', $msg);

                if ($user->user_type === 'Admin') {
                    return redirect()->intended(route('admin.dashboard'));
                } else {
                    return redirect()->intended(route('borrower.dashboard'));
                }
            }

            ActivityLog::record('login_failed', [
                'actor' => null,
                'actor_name' => ActivityLog::ACTOR_ANONYMOUS,
                'subject' => User::where('email', $credentials['email'])->first(),
                'details' => 'Failed sign-in for '.$credentials['email'].'.',
            ]);

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
        $user = Auth::user();

        Auth::guard('web')->logout();

        if ($user) {
            ActivityLog::record('logout', ['actor' => $user, 'subject' => $user, 'details' => 'Signed out.']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function registerUser()
    {
        return view('register');
    }

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
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'contact_number' => 'nullable|string|max:15',
            'course' => 'nullable|string|max:50',
            'year_level' => ['nullable', Rule::in(User::YEAR_LEVELS)],
            'agree' => 'accepted',
        ], [
            'requested_role.required' => 'Say whether you are a student or an instructor.',
            'requested_role.in' => 'Choose Student or Instructor.',
            'password.min' => 'Your password needs at least 8 characters.',
            'password.regex' => 'Your password needs both letters and numbers.',
            'agree.accepted' => 'Tick the agreement to continue.',
            'year_level.in' => 'Choose your year level from the list.',
        ]);

        $wantsInstructor = $validated['requested_role'] === 'Instructor';

        $user = User::create([
            'user_type' => 'Student',
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'contact_number' => $validated['contact_number'] ?? null,
            'course' => $wantsInstructor ? null : ($validated['course'] ?? null),
            'year_level' => $wantsInstructor ? null : ($validated['year_level'] ?? null),
            'instructor_requested_at' => $wantsInstructor ? now() : null,
        ]);

        ActivityLog::record('user_created', [
            'actor' => $user,
            'subject' => $user,
            'status_to' => $user->user_type,
            'details' => 'Signed up as a Student'.($wantsInstructor ? ', asking to be confirmed as an instructor' : '').'.',
            'meta' => ['source' => 'sign-up', 'instructor_requested' => $wantsInstructor],
        ]);

        return redirect()->route('register')->with('registered', [
            'email' => $user->email,
            'role' => $user->user_type,
            'instructor_requested' => $wantsInstructor,
        ]);
    }

    public function register(Request $request)
    {
        $makingAdmin = $request->input('user_type') === 'Admin';

        $rules = [
            'user_type' => 'required|in:Admin,Instructor,Student',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4|confirmed',
            'contact_number' => 'nullable|string|max:15',
            'course' => 'nullable|string|max:50',
            'year_level' => ['nullable', Rule::in(User::YEAR_LEVELS)],
        ];

        if ($makingAdmin) {
            $rules['email'] = [
                'required', 'string', 'email', 'max:255', 'unique:users',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! User::isSchoolEmail($value)) {
                        $fail('Staff accounts must use a school address — @'.User::SCHOOL_DOMAIN.'.');
                    }
                },
            ];
            $rules['password'] = ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()];
            $rules['role_override_reason'] = 'required|string|min:5|max:500';
            $rules['current_password'] = 'required|current_password';
        }

        $validatedData = $request->validate($rules, [
            'role_override_reason.required' => 'Say why this person needs admin access — the reason is recorded.',
            'role_override_reason.min' => 'Give a usable reason (at least 5 characters).',
            'current_password.required' => 'Enter your own password to create an admin account.',
            'current_password.current_password' => 'That is not your password.',
            'year_level.in' => 'Choose a year level from the list.',
        ]);

        $isStudent = $validatedData['user_type'] === 'Student';

        $user = User::create([
            'user_type' => $validatedData['user_type'],
            'name' => $validatedData['name'],
            'email' => strtolower(trim($validatedData['email'])),
            'password' => Hash::make($validatedData['password']),
            'contact_number' => $validatedData['contact_number'] ?? null,
            'course' => $isStudent ? ($validatedData['course'] ?? null) : null,
            'year_level' => $isStudent ? ($validatedData['year_level'] ?? null) : null,
        ] + ($makingAdmin ? [
            'role_overridden_at' => now(),
            'role_overridden_by' => auth()->id(),
            'role_override_reason' => $validatedData['role_override_reason'],
        ] : []));

        ActivityLog::record('user_created', [
            'subject' => $user,
            'status_to' => $user->user_type,
            'details' => 'Created a '.$user->user_type.' account for '.$user->name
                .($makingAdmin ? ': '.$validatedData['role_override_reason'] : '').'.',
            'meta' => array_filter(['source' => 'admin', 'reason' => $makingAdmin ? $validatedData['role_override_reason'] : null]),
        ]);

        return redirect()->back()->with('success', $makingAdmin
            ? $user->name.' added as an admin — they can sign in to the admin dashboard now.'
            : $user->name.' added as a '.strtolower($user->user_type).'.');
    }
}
