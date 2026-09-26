@extends('components.default')
@section('title', 'Users - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $holding = $users->filter(fn ($user) => $user->outNow() > 0)->count();
    $deactivated = $users->filter(fn ($user) => $user->isDeactivated())->count();
    $suspended = $users->filter(fn ($user) => $user->isSuspended())->count();
    $overdueHolders = $users->filter(fn ($user) => (int) ($user->overdue_count ?? 0) > 0)->count();
    $counts = [
        'Instructor' => $users->where('user_type', 'Instructor')->count(),
        'Student' => $users->where('user_type', 'Student')->count(),
        'Admin' => $users->where('user_type', 'Admin')->count(),
    ];
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="People" title="Accounts">
        {{ $users->count() }} {{ str('account')->plural($users->count()) }} · {{ $holding }} holding equipment
        <x-slot:actions>
            <button type="button" data-modal-open="schedule-modal" data-schedule-add
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50">
                <i class="text-base fas fa-calendar-plus" aria-hidden="true"></i>
                <span class="hidden sm:inline">Add schedule</span>
            </button>
            <button type="button" data-user-add
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-base fas fa-user-plus" aria-hidden="true"></i> Add user
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        @if($users->isNotEmpty())
            <x-ui.stat-strip :stats="[
                ['label' => 'Holding equipment', 'value' => $holding, 'unit' => str('account')->plural($holding), 'sub' => $holding === 0 ? 'Nothing is out with anyone' : 'Units still with borrowers'],
                ['label' => 'With something overdue', 'value' => $overdueHolders, 'unit' => '', 'sub' => $overdueHolders === 0 ? 'Everyone is inside their due dates' : 'Past the return date', 'tone' => $overdueHolders === 0 ? 'neutral' : 'danger', 'chip' => 'overdue', 'list' => '#user-list'],
                ['label' => 'Suspended', 'value' => $suspended, 'unit' => '', 'sub' => $suspended === 0 ? 'Everyone can borrow' : 'Can sign in, cannot borrow', 'tone' => $suspended === 0 ? 'neutral' : 'warning', 'chip' => 'suspended', 'list' => '#user-list'],
                ['label' => 'Deactivated', 'value' => $deactivated, 'unit' => '', 'sub' => $deactivated === 0 ? 'Every account can sign in' : 'Cannot sign in at all', 'chip' => 'deactivated', 'list' => '#user-list'],
            ]" />
        @endif

        {{-- People who signed up saying they teach. Everyone shares one email
             domain, so the address cannot settle it: the account was created
             as a Student and works as one, and Instructor waits for this
             decision. Either button clears the request. --}}
        @if($instructorRequests->isNotEmpty())
            <section aria-labelledby="instructor-requests-heading" class="space-y-3" data-instructor-requests>
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h2 id="instructor-requests-heading" class="text-lg font-semibold text-neutral-900">Instructor requests</h2>
                    <p class="text-sm text-neutral-600">
                        {{ $instructorRequests->count() }} {{ str('person')->plural($instructorRequests->count()) }}
                        signed up as an instructor · using a student account until confirmed
                    </p>
                </div>

                @foreach($instructorRequests as $user)
                    <article class="bg-white border rounded-xl border-neutral-200 border-l-[3px] border-l-primary-500" data-instructor-request>
                        <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-neutral-900">{{ $user->name }}</h3>
                                <p class="mt-1 text-sm text-neutral-600 [overflow-wrap:anywhere]">
                                    {{ $user->email }}@if($user->contact_number) · {{ $user->contact_number }}@endif
                                </p>
                                <p class="mt-1 text-sm text-neutral-600">
                                    Asked {{ $user->instructor_requested_at->format('M j') }} · currently a {{ strtolower($user->user_type) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('admin.users.instructor.decline', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex min-h-[40px] items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                                        Keep as student
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.instructor.confirm', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex min-h-[40px] items-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                                        <i class="text-sm fas fa-chalkboard-user" aria-hidden="true"></i> Confirm instructor
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        {{-- The accounts a human has already acted on, above the member list.
             There is no approval queue for new accounts: registration creates
             a working one, so the actionable rows here are the ones that have
             been closed or restricted. --}}
        @if($needsAttention->isNotEmpty())
            <section aria-labelledby="attention-heading" class="space-y-3">
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h2 id="attention-heading" class="text-lg font-semibold text-neutral-900">Restricted accounts</h2>
                    <p class="text-sm text-neutral-600">
                        {{ $needsAttention->count() }} {{ str('account')->plural($needsAttention->count()) }} cannot sign in or cannot borrow
                    </p>
                </div>

                @foreach($needsAttention as $user)
                    @php
                        $out = $user->outNow();
                        $overdue = (int) ($user->overdue_count ?? 0);
                    @endphp
                    <article class="bg-white border rounded-xl border-neutral-200 border-l-[3px] {{ $user->isDeactivated() ? 'border-l-danger-500' : 'border-l-warning-500' }}"
                             data-restricted-account>
                        <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-neutral-900">{{ $user->name }}</h3>
                                    @if($user->isDeactivated())
                                        <x-ui.status label="Deactivated" tone="danger" />
                                    @else
                                        <x-ui.status label="Suspended" tone="warning" />
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-neutral-600">{{ $user->email }} · {{ $user->user_type }}</p>

                                {{-- Why, and since when. A restriction with no
                                     reason on it cannot be reviewed by anyone
                                     who was not in the room. --}}
                                <p class="mt-1 text-sm text-pretty {{ $user->isDeactivated() ? 'text-danger-700' : 'text-warning-800' }}"
                                   data-restriction-reason>
                                    @if($user->isSuspended())
                                        {{ $user->suspensionLine() }}@if($user->suspender) · by {{ $user->suspender->name }}@endif
                                    @else
                                        Cannot sign in. History and loans are untouched.
                                    @endif
                                </p>

                                @if($out > 0)
                                    <p class="mt-1 text-sm {{ $overdue > 0 ? 'font-semibold text-danger-700' : 'text-neutral-700' }}">
                                        Still holding {{ $out }} {{ str('unit')->plural($out) }}@if($overdue > 0) · {{ $overdue }} overdue @endif
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                @if($user->isSuspended())
                                    <form method="POST" action="{{ route('admin.users.lift', $user->id) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex min-h-[40px] items-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                                            <i class="text-sm fas fa-unlock" aria-hidden="true"></i> Let them borrow again
                                        </button>
                                    </form>
                                @endif
                                @if($user->isDeactivated())
                                    <form method="POST" action="{{ route('admin.users.reactivate', $user->id) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex min-h-[40px] items-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                                            <i class="text-sm fas fa-rotate-left" aria-hidden="true"></i> Reactivate
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        <x-ui.panel id="user-list" data-list data-active-chip="all">
            @if($users->isEmpty())
                <x-ui.empty-state icon="fa-users" title="No accounts yet"
                                  message="Add the borrowers who will be requesting equipment.">
                    <x-slot:action>
                        <button type="button" class="btn-primary" data-user-add>
                            <i class="text-base fas fa-user-plus" aria-hidden="true"></i> Add user
                        </button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b sm:px-5 border-neutral-200">
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter accounts">
                        @php
                            $chips = [
                                'all' => 'All '.$users->count(),
                                'instructor' => 'Instructors '.$counts['Instructor'],
                                'student' => 'Students '.$counts['Student'],
                                'holding' => 'Holding equipment '.$holding,
                            ];
                            if ($overdueHolders > 0) {
                                $chips['overdue'] = 'With overdue '.$overdueHolders;
                            }
                            if ($suspended > 0) {
                                $chips['suspended'] = 'Suspended '.$suspended;
                            }
                            if ($deactivated > 0) {
                                $chips['deactivated'] = 'Deactivated '.$deactivated;
                            }
                            if ($instructorRequests->isNotEmpty()) {
                                $chips['requested'] = 'Asked for Instructor '.$instructorRequests->count();
                            }
                        @endphp
                        @foreach($chips as $value => $label)
                            <button type="button" data-list-chip="{{ $value }}"
                                    aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                           {{ $value === 'all' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2 text-sm text-neutral-600">
                        <label for="user-sort" class="shrink-0">Sort</label>
                        <select id="user-sort" data-list-sort
                                class="min-h-[40px] rounded-md border border-neutral-300 bg-white px-2.5 py-2 text-sm text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                            <option value="name">Name A&ndash;Z</option>
                            <option value="overdue" data-type="number" data-dir="desc">Most overdue first</option>
                            <option value="out" data-type="number" data-dir="desc">Holding most first</option>
                        </select>
                    </div>

                    <label class="relative flex-1 min-w-[12rem] max-w-xs">
                        <span class="sr-only">Search accounts</span>
                        <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                        <input type="search" data-list-search autocomplete="off" placeholder="Search name or email"
                               class="w-full min-h-[40px] rounded-md border border-neutral-300 py-2 pl-10 pr-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </label>
                </div>

                {{-- "User type" is no longer a column of its own: it is one word
                     that belongs next to the name, which frees the width for
                     what this page was missing — what each person is holding. --}}
                <div class="hidden gap-4 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 bg-neutral-50 border-b border-neutral-200 lg:grid lg:grid-cols-[minmax(0,1.8fr)_minmax(0,1.6fr)_minmax(0,1.2fr)_minmax(0,1fr)_6rem]">
                    <div>Person</div>
                    <div>Contact</div>
                    <div>Holding now</div>
                    <div>Class schedules</div>
                    <div class="text-right">Actions</div>
                </div>

                <div data-list-rows class="divide-y divide-neutral-200">
                    @foreach ($users as $user)
                        @php
                            $out = $user->outNow();
                            $overdue = (int) ($user->overdue_count ?? 0);
                            $references = $user->referencesCount();
                            $schedCount = (int) ($user->class_schedules_count ?? $user->classSchedules->count());
                            $initials = collect(preg_split('/\s+/', trim((string) $user->name)))
                                ->filter()->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '—';
                            $chipKeys = collect(['all', strtolower($user->user_type)]);
                            if ($out > 0) { $chipKeys->push('holding'); }
                            if ($overdue > 0) { $chipKeys->push('overdue'); }
                            if ($user->isDeactivated()) { $chipKeys->push('deactivated'); }
                            if ($user->isSuspended()) { $chipKeys->push('suspended'); }
                            if ($user->hasPendingInstructorRequest()) { $chipKeys->push('requested'); }
                            $pending = $user->pendingRequests();
                            $isSelf = $user->id === Auth::id();
                            $blocked = $out > 0
                                ? "Can't delete — ".$out.' '.str('unit')->plural($out).' '.($out === 1 ? 'is' : 'are').' still out with them'
                                : ($references > 0
                                    ? "Can't delete — ".$references.' '.str('record')->plural($references).' reference this person'
                                    : ($isSelf ? "Can't delete — this is your own account" : ''));
                        @endphp
                        <div data-list-row data-chip="{{ $chipKeys->implode(' ') }}"
                             data-search="{{ strtolower($user->name.' '.$user->email) }}"
                             data-sort-name="{{ $user->name }}"
                             data-sort-out="{{ $out }}"
                             data-sort-overdue="{{ $overdue }}"
                             class="grid gap-3 px-4 py-4 sm:px-5 lg:grid-cols-[minmax(0,1.8fr)_minmax(0,1.6fr)_minmax(0,1.2fr)_minmax(0,1fr)_6rem] lg:items-center lg:gap-4 hover:bg-neutral-50">

                            <div class="flex items-center min-w-0 gap-3">
                                <span class="grid text-sm font-semibold border rounded-full w-9 h-9 shrink-0 place-items-center
                                             {{ $user->user_type === 'Admin' ? 'border-primary-200 bg-primary-50 text-primary-700' : 'border-neutral-200 bg-neutral-100 text-neutral-700' }}"
                                      aria-hidden="true">{{ $initials }}</span>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold truncate text-neutral-900">{{ $user->name }}</p>
                                    {{-- Role is shown, not edited inline — it
                                         changes only through the edit dialog or
                                         an instructor confirmation, and where it
                                         has been changed the row says by whom. --}}
                                    <p class="text-sm text-neutral-600" data-user-role>
                                        {{ $user->user_type }}
                                        @if($user->roleIsOverridden())
                                            <span class="font-semibold text-warning-700" title="{{ $user->roleOverrideLine() }}">· set by hand</span>
                                        @endif
                                        @if($user->hasPendingInstructorRequest())
                                            <span class="font-semibold text-primary-700" data-instructor-requested>· asked for Instructor</span>
                                        @endif
                                        @if($user->isSuspended())
                                            <span class="font-semibold text-warning-700">· suspended</span>
                                        @endif
                                        @if($user->isDeactivated())
                                            <span class="font-semibold text-danger-700">· deactivated</span>
                                        @endif
                                    </p>
                                    @if($user->roleIsOverridden())
                                        <p class="text-sm text-neutral-500 text-pretty" data-role-override>{{ $user->roleOverrideLine() }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm truncate text-neutral-700">{{ $user->email }}</p>
                                <p class="text-sm text-neutral-600">{{ $user->contact_number ?: 'No contact number' }}</p>
                            </div>

                            {{-- Standing, not account fields. Whoever has to
                                 decide on this person's next request needs all
                                 three of these, and none of them are on the
                                 account itself. --}}
                            <div class="min-w-0" data-user-standing>
                                @if($overdue > 0)
                                    <p class="text-sm font-semibold text-danger-700 tabular-nums">
                                        {{ $out }} {{ str('unit')->plural($out) }} out · {{ $overdue }} overdue
                                    </p>
                                @elseif($out > 0)
                                    <p class="text-sm text-neutral-800 tabular-nums">{{ $out }} {{ str('unit')->plural($out) }} out</p>
                                @else
                                    <p class="text-sm text-neutral-500">Nothing borrowed</p>
                                @endif
                                @if($pending > 0)
                                    <p class="text-sm text-neutral-600 tabular-nums">
                                        <a href="{{ route('admin.request') }}" class="underline underline-offset-2 hover:text-primary-700">
                                            {{ $pending }} pending {{ str('request')->plural($pending) }}
                                        </a>
                                    </p>
                                @endif
                            </div>

                            <div class="min-w-0">
                                @if($schedCount === 0)
                                    <p class="text-sm text-neutral-500">No schedule set</p>
                                @else
                                    <button type="button" data-schedule-manage data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            class="text-sm font-semibold underline text-primary-700 underline-offset-4 hover:text-primary-800">
                                        {{ $schedCount }} class {{ str('schedule')->plural($schedCount) }}
                                    </button>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 lg:justify-end">
                                <button type="button"
                                        class="grid w-10 h-10 bg-white border rounded-md place-items-center border-neutral-300 text-neutral-700 hover:border-primary-300 hover:text-primary-700"
                                        title="Edit {{ $user->name }}" aria-label="Edit {{ $user->name }}"
                                        data-user-edit
                                        data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                        data-user-type="{{ $user->user_type }}" data-contact="{{ $user->contact_number }}">
                                    <i class="text-base fas fa-pen" aria-hidden="true"></i>
                                </button>

                                @if(! $isSelf && $user->user_type !== 'Admin')
                                    {{-- Suspension stops them borrowing and
                                         leaves the account usable, which is the
                                         sanction the terms actually describe.
                                         Neutral at rest like its neighbours. --}}
                                    @if($user->isSuspended())
                                        <form method="POST" action="{{ route('admin.users.lift', $user->id) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="grid w-10 h-10 bg-white border rounded-md place-items-center border-neutral-300 text-neutral-600 hover:border-primary-300 hover:text-primary-700"
                                                    title="Let {{ $user->name }} borrow again" aria-label="Lift the suspension on {{ $user->name }}">
                                                <i class="text-base fas fa-unlock" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button"
                                                class="grid w-10 h-10 bg-white border rounded-md place-items-center border-neutral-300 text-neutral-600 hover:border-warning-300 hover:bg-warning-50 hover:text-warning-700"
                                                title="Suspend borrowing for {{ $user->name }}" aria-label="Suspend borrowing for {{ $user->name }}"
                                                data-suspend-trigger
                                                data-id="{{ $user->id }}"
                                                data-summary="{{ $user->name.' · '.$user->user_type.($out > 0 ? ' · holding '.$out.' '.str('unit')->plural($out) : '') }}">
                                            <i class="text-base fas fa-ban" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                @endif

                                @unless($isSelf)
                                    <button type="button"
                                            class="grid w-10 h-10 bg-white border rounded-md place-items-center border-neutral-300 text-neutral-600 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700"
                                            title="Remove {{ $user->name }}" aria-label="Remove {{ $user->name }}"
                                            data-remove-trigger data-dialog="remove-dialog"
                                            data-title="{{ $user->isDeactivated() ? 'Restore '.$user->name.'?' : 'Remove '.$user->name.'?' }}"
                                            data-body="{{ $user->isDeactivated()
                                                ? 'This account is deactivated and cannot sign in. Reactivating lets them back in; their history was never touched.'
                                                : ($out > 0
                                                    ? 'This person still has equipment checked out. Deactivating keeps their loans tracked; the account just stops being usable.'
                                                    : 'Deactivating keeps their borrowing history intact. Deleting erases the person from those records too.') }}"
                                            data-fact-a="{{ $out }}" data-fact-a-alert="{{ $out > 0 ? '1' : '0' }}"
                                            data-fact-b="{{ $references }}"
                                            data-fact-c="{{ $schedCount }}"
                                            data-blocked="{{ $blocked }}"
                                            data-delete-url="{{ route('admin.users.destroy', $user->id) }}"
                                            data-safe-url="{{ $user->isDeactivated() ? route('admin.users.reactivate', $user->id) : route('admin.users.deactivate', $user->id) }}"
                                            data-safe-label="{{ $user->isDeactivated() ? 'Reactivate account' : 'Deactivate account' }}"
                                            data-safe-icon="{{ $user->isDeactivated() ? 'fa-rotate-left' : 'fa-user-slash' }}">
                                        <i class="text-base fas {{ $user->isDeactivated() ? 'fa-rotate-left' : 'fa-trash' }}" aria-hidden="true"></i>
                                    </button>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>

                <p data-list-empty hidden class="px-5 py-12 text-base text-center text-neutral-600">
                    No accounts match that filter.
                </p>

                <div class="px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                    <span data-list-count data-total="{{ $users->count() }}" data-noun="accounts"></span>
                </div>
            @endif
        </x-ui.panel>
    </main>
</div>

@include('components.admin.user.form-modal')
@include('components.admin.user.schedules-modal')

@include('components.admin.user.suspend-modal')

<x-ui.remove-dialog
    id="remove-dialog"
    safe-label="Deactivate account"
    safe-icon="fa-user-slash"
    cancel-label="Keep account"
    :facts="['Equipment held now', 'Loans and requests on record', 'Class schedules']" />

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ---- add / edit account ------------------------------------------ */
    const userForm = document.getElementById('user-form');
    if (userForm) {
        const modal = document.getElementById('user-modal');
        const idField = document.getElementById('user-id');
        const name = document.getElementById('user-name');
        const email = document.getElementById('user-email');
        const type = document.getElementById('user-type');
        const contact = document.getElementById('user-contact');
        const password = document.getElementById('user-password');
        const confirmation = document.getElementById('user-password-confirmation');
        const submit = userForm.querySelector('[data-user-submit]');
        const hint = userForm.querySelector('[data-user-hint]');
        const passwordNote = userForm.querySelector('[data-password-note]');
        const adminOption = type.querySelector('option[value="Admin"]');

        const ADD_URL = @json(route('admin.user.register'));
        const UPDATE_URL = @json(route('admin.users.update'));
        let editing = false;

        function sync() {
            const nameOk = name.value.trim().length > 1;
            const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
            const typeOk = !!type.value;
            const wantsPassword = password.value.length > 0;
            const passwordOk = editing
                ? (!wantsPassword || (password.value.length >= 4 && password.value === confirmation.value))
                : (password.value.length >= 4 && password.value === confirmation.value);
            const ok = nameOk && emailOk && typeOk && passwordOk;

            submit.disabled = !ok;
            hint.textContent = !nameOk ? 'Name required'
                : !emailOk ? 'A valid email address is required'
                : !typeOk ? 'Pick a role'
                : !passwordOk
                    ? (password.value.length < 4 ? 'Password needs at least 4 characters' : 'The two passwords do not match')
                    : (editing ? 'Changes apply immediately' : 'The account can sign in straight away');
            hint.classList.toggle('text-danger-700', !ok);
            hint.classList.toggle('text-neutral-600', ok);
        }

        function open(data) {
            editing = !!(data && data.id);
            userForm.action = editing ? UPDATE_URL : ADD_URL;
            idField.value = editing ? data.id : '';
            name.value = editing ? data.name : '';
            email.value = editing ? data.email : '';
            contact.value = editing ? (data.contact || '') : '';
            password.value = '';
            confirmation.value = '';

            // Admin is a database-only role: AuthenticateUser::register rejects
            // it outright, so offering it on the add form would only ever
            // produce a validation error.
            adminOption.hidden = !editing;
            adminOption.disabled = !editing;
            type.value = editing ? data.userType : '';
            // What a role change is measured against — see form-modal.
            type.dataset.current = editing ? data.userType : '';
            type.dispatchEvent(new Event('role:reset'));

            modal.querySelector('[data-user-title]').textContent = editing ? 'Edit account' : 'Add account';
            modal.querySelector('[data-user-submit-label]').textContent = editing ? 'Save changes' : 'Create account';
            passwordNote.textContent = editing ? 'leave blank to keep the current one' : 'at least 4 characters';

            sync();
            window.appUI.openModal('user-modal');
        }

        [name, email, type, password, confirmation].forEach(function (field) {
            field.addEventListener('input', sync);
            field.addEventListener('change', sync);
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-user-add]')) { open(null); return; }
            const edit = event.target.closest('[data-user-edit]');
            if (edit) open(edit.dataset);
        });

        sync();
    }

    /* ---- class schedules --------------------------------------------- */
    document.addEventListener('click', function (event) {
        const manage = event.target.closest('[data-schedule-manage]');
        if (manage) {
            document.getElementById('schedules-for').textContent = manage.dataset.userName || '';
            document.querySelectorAll('[data-schedule-group]').forEach(function (group) {
                group.hidden = group.dataset.scheduleGroup !== manage.dataset.userId;
            });
            window.appUI.openModal('schedules-modal');
            return;
        }

        const add = event.target.closest('[data-schedule-add]');
        if (add) {
            const form = document.getElementById('schedule-form');
            form.reset();
            form.action = form.dataset.addUrl;
            document.getElementById('schedule-id').value = '';
            document.querySelector('[data-schedule-title]').textContent = 'Add class schedule';
            if (add.dataset.userId) document.getElementById('schedule-user').value = add.dataset.userId;
            return;
        }

        const edit = event.target.closest('[data-schedule-edit]');
        if (edit) {
            const d = edit.dataset;
            const scheduleForm = document.getElementById('schedule-form');
            scheduleForm.action = scheduleForm.dataset.updateUrl;
            document.getElementById('schedule-id').value = d.id;
            document.getElementById('schedule-year').value = d.yearLevel || '';
            document.getElementById('schedule-block').value = d.blockName || '';
            document.getElementById('schedule-code').value = d.subjectCode || '';
            document.getElementById('schedule-name').value = d.subjectName || '';
            document.getElementById('schedule-time').value = d.scheduleTime || '';
            document.getElementById('schedule-room').value = d.room || '';
            document.querySelector('[data-schedule-title]').textContent = 'Edit class schedule';

            // The select lists current Instructors only. If this schedule
            // belongs to someone no longer typed as one, put them back so that
            // saving cannot silently reassign it to the first instructor.
            const owner = document.getElementById('schedule-user');
            owner.value = d.userId;
            if (owner.value !== String(d.userId)) {
                const option = document.createElement('option');
                option.value = d.userId;
                option.textContent = d.userName || ('User #' + d.userId);
                owner.prepend(option);
                owner.value = d.userId;
            }

            window.appUI.openModal('schedule-modal');
            return;
        }

        // Deleting a schedule states what points at it, and the controller
        // refuses outright when any loan does.
        const remove = event.target.closest('[data-schedule-delete]');
        if (remove) {
            const d = remove.dataset;
            const loans = parseInt(d.loans || '0', 10);
            const form = document.getElementById('schedule-delete-form');
            form.action = d.url;

            if (loans > 0) {
                window.showAlert('error',
                    d.label + ' cannot be deleted: ' + loans + (loans === 1 ? ' loan is' : ' loans are')
                    + ' recorded against it, and deleting it would erase which class they were for.',
                    { title: 'Still referenced' });
                return;
            }

            window.showConfirm({
                title: 'Delete ' + d.label + '?',
                text: 'No loans reference this schedule, so nothing else changes.',
                icon: 'warning',
                confirmText: 'Yes, delete',
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        }
    });
});
</script>
@endsection
