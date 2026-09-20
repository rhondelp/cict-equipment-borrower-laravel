@extends('components.default')
@section('title', 'Users - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $holding = $users->filter(fn ($user) => $user->outNow() > 0)->count();
    $deactivated = $users->filter(fn ($user) => $user->isDeactivated())->count();
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
        <x-ui.panel data-list data-active-chip="all">
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
                            if ($deactivated > 0) {
                                $chips['deactivated'] = 'Deactivated '.$deactivated;
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

                <div class="divide-y divide-neutral-200">
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
                            if ($user->isDeactivated()) { $chipKeys->push('deactivated'); }
                            $isSelf = $user->id === Auth::id();
                            $blocked = $out > 0
                                ? "Can't delete — ".$out.' '.str('unit')->plural($out).' '.($out === 1 ? 'is' : 'are').' still out with them'
                                : ($references > 0
                                    ? "Can't delete — ".$references.' '.str('record')->plural($references).' reference this person'
                                    : ($isSelf ? "Can't delete — this is your own account" : ''));
                        @endphp
                        <div data-list-row data-chip="{{ $chipKeys->implode(' ') }}"
                             data-search="{{ strtolower($user->name.' '.$user->email) }}"
                             class="grid gap-3 px-4 py-4 sm:px-5 lg:grid-cols-[minmax(0,1.8fr)_minmax(0,1.6fr)_minmax(0,1.2fr)_minmax(0,1fr)_6rem] lg:items-center lg:gap-4 hover:bg-neutral-50">

                            <div class="flex items-center min-w-0 gap-3">
                                <span class="grid text-sm font-semibold border rounded-full w-9 h-9 shrink-0 place-items-center
                                             {{ $user->user_type === 'Admin' ? 'border-primary-200 bg-primary-50 text-primary-700' : 'border-neutral-200 bg-neutral-100 text-neutral-700' }}"
                                      aria-hidden="true">{{ $initials }}</span>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold truncate text-neutral-900">{{ $user->name }}</p>
                                    <p class="text-sm text-neutral-600">
                                        {{ $user->user_type }}
                                        @if($user->isDeactivated())
                                            <span class="font-semibold text-danger-700">· deactivated</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm truncate text-neutral-700">{{ $user->email }}</p>
                                <p class="text-sm text-neutral-600">{{ $user->contact_number ?: 'No contact number' }}</p>
                            </div>

                            <div class="min-w-0">
                                @if($overdue > 0)
                                    <p class="text-sm font-semibold text-danger-700">
                                        {{ $out }} {{ str('unit')->plural($out) }} out · {{ $overdue }} overdue
                                    </p>
                                @elseif($out > 0)
                                    <p class="text-sm text-neutral-800">{{ $out }} {{ str('unit')->plural($out) }} out</p>
                                @else
                                    <p class="text-sm text-neutral-500">Nothing borrowed</p>
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
                                        class="grid w-10 h-10 border rounded-md place-items-center border-neutral-300 bg-white text-neutral-700 hover:border-primary-300 hover:text-primary-700"
                                        title="Edit {{ $user->name }}" aria-label="Edit {{ $user->name }}"
                                        data-user-edit
                                        data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                        data-user-type="{{ $user->user_type }}" data-contact="{{ $user->contact_number }}">
                                    <i class="text-base fas fa-pen" aria-hidden="true"></i>
                                </button>

                                @unless($isSelf)
                                    <button type="button"
                                            class="grid w-10 h-10 border rounded-md place-items-center border-neutral-300 bg-white text-neutral-600 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700"
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
