# AGENT_CONTEXT.md

Dense reference for AI agents working in this repo. Read this before touching code.

## Purpose

CICT Equipment Borrower is an internal web app for a college IT department that manages the
lending of departmental equipment. Borrowers (Instructors and Students) submit item requests
from their dashboard; an Admin reviews each request, and approving one deducts stock and
auto-creates a borrow transaction. Admins can also record a loan directly, check one back in
(which writes the return log and restores stock), manage the equipment inventory, users and
instructor class schedules, and send return-reminder emails (manually per loan, or via a daily
scheduled job). Everything hangs off keeping `equipment.available_quantity` truthful as loans
move between states.

**Status is never typed.** Since the Sept 2026 redesign there is no control anywhere that sets
a status directly: availability is computed from the loans, and overdue is computed from the
due date at render time. An admin records events — a handover, a check-in, a void — and the
status follows. See "Derived state" below before adding any form field called `status`.

## Tech stack

- Laravel 12 (PHP ^8.2), no starter kit — auth is hand-rolled on `Auth::attempt` + the `web` guard
- Blade views (no Livewire/Inertia/Vue); interactivity is vanilla JS + SweetAlert in the views
- **No jQuery and no DataTables.** Both were dropped in the Sept 2026 redesign; list search, filter chips, the "showing N of M" counter, modals and the shared remove dialog all live in `resources/js/ui.js`, driven by `data-list*` / `data-modal*` / `data-remove-*` attributes
- Tailwind CSS (custom `primary` / `neutral` palettes in `tailwind.config.js`), compiled by Vite
- Vite via `laravel-vite-plugin`; entries `resources/css/app.css`, `resources/js/app.js`
- MySQL (`cict_equipment_borrower` per `.env`; `.env.example` still ships `sqlite` — ignore that)
- Mail via `App\Mail\ReturnNotification` + `resources/views/emails/return-notification.blade.php`
- Dev env: XAMPP on Windows (`C:\xampp\htdocs\cict-equipment-borrower`)
- Tooling: Pint (`laravel/pint`), PHPUnit 11; `schedule.bat` / `send_alert.bat` drive the scheduler on Windows

## User roles

Enum `users.user_type` = `Admin` | `Instructor` | `Student`. Enforced by
`App\Http\Middleware\UserTypeMiddleware` (alias `userType`, registered in `bootstrap/app.php`),
used as `userType:Admin` or `userType:Instructor,Student`. The middleware aborts 403 when
`$user->user_type` is not in the allowed list — it assumes an authenticated user, so it must
always sit inside the `auth` middleware group.

- **Admin** — full CRUD on equipment, users, class schedules and borrow transactions; approves/declines item requests; views notifications and return logs. Lands on `/admin/dashboard`.
- **Instructor** / **Student** — collectively "borrowers". Same routes and same dashboard view; they create, update and delete only their *own* item requests (ownership enforced by `ItemRequestController::assertOwner`). Land on `/borrower/dashboard`. Instructors additionally own `ClassSchedule` rows that Admins attach to transactions.

No web route can create an `Admin`, and the two registration routes refuse it differently.

**Public sign-up (`POST /register` → `AuthenticateUser::registerPublic`)** does not read a role
from the request at all. The role is derived from the school domain of the submitted address by
`User::roleForEmail()` — `@student.nmsc.edu.ph` → `Student`, `@nmsc.edu.ph` → `Instructor`,
anything else rejected on the `email` field. The domain is matched **whole**, never as a suffix:
`str_ends_with($email, 'nmsc.edu.ph')` would hand an Instructor account to whoever registers
`not-nmsc.edu.ph`. A `user_type` in the payload is ignored rather than validated, so there is no
field to tamper with. If you add a role, add it to `roleForEmail()`, not to the form.

**The admin users form (`POST /admin/users` → `AuthenticateUser::register`)** still takes an
explicit `user_type`, capped at `in:Instructor,Student` — an admin uses that form to add
borrowers, not staff, and they are behind `userType:Admin` to do it. Posting `Admin` there is
**rejected** (a `user_type` validation error, no row written) rather than downgraded.

Creating an Admin is a deliberate DB/seed/tinker action with no web path. All of this is pinned
by `tests/Feature/SecurityRegressionTest` and `tests/Feature/RegisterPageTest`.

## Password reset

The `Password` broker in `config/auth.php` owns token generation, expiry (`expire`, 60 minutes)
and resend throttling (`throttle`, 60 seconds) — none of it is hand-rolled. The forgot-password
screen **reads both numbers out of that config** and states them in its copy rather than quoting
figures of its own, and its resend cooldown is counted from the `created_at` on the broker's own
`password_reset_tokens` row. If you change either config value the copy follows it; if you quote
a number in the template instead, the page starts promising something the server will refuse.

A **deactivated account is refused before the broker is called**. It could otherwise be sent a
link, reset its password, and still be turned away by `AuthenticateUser::login`, which checks
`deactivated_at` separately — a loop with no exit and no explanation. Office contact details
live in `config/office.php` (`OFFICE_EMAIL`, `OFFICE_HOURS`). Pinned by
`tests/Feature/ForgotPasswordPageTest`.

## Legal documents

`resources/views/legal/terms.blade.php` and `privacy.blade.php` hold **no markup** — each is a
single `$doc` array (title, lede, updated, three summary bullets, and an ordered list of
sections). `layouts/legal.blade.php` renders both, and builds the table-of-contents rail from the
same `sections` array the article renders, so the rail cannot drift out of step with the
headings. Section shape is `['id', 'heading', 'paras' => [], 'items' => [], 'note' => '']`;
`note` renders as a bordered aside and is reserved for a clause that tells someone what to do
when the normal path is closed — there is exactly one per document, and the treatment stops
meaning anything if that grows.

Both documents keep their own URL. The tab switch is two links, not a client-side toggle,
because the registration consent checkbox links straight to each of them.

Body copy is 17px `font-serif` (Source Serif 4) at `max-w-[66ch]`. The font is pushed onto the
`styles` stack by the legal layout alone — do not move it into `components/default`, where every
page would pay for it.

**Careful with Blade comments here:** Blade lifts `@php … @endphp` blocks out of a template
*before* it strips comments, so writing `@php(` inside a `{{-- --}}` comment swallows the file
down to the next `@endphp` and fails with an unrelated "Cannot end a push stack" error. The
layout's own doc-comment spells directive names without their `@` for this reason.

Pinned by `tests/Feature/LegalPagesTest`.

## Data model

All models are plain `Illuminate\Database\Eloquent\Model` with `$fillable`, no observers and no
soft deletes. FKs are `onDelete('cascade')` unless noted. Date columns are cast on `Equipment`,
`BorrowTransaction`, `ItemRequest`, `ReturnLog` and `User`, so `$tx->borrow_date` is a Carbon —
do not `Carbon::parse` it again, and do not echo it bare (you get `Y-m-d H:i:s`).

Three nullable timestamps carry the "no longer in use, still real" state the remove dialogs
offer instead of a hard delete: `equipment.retired_at`, `users.deactivated_at`,
`borrow_transactions.voided_at`. Nothing in the app hard-deletes a row that history points at.

| Model | Table | Key fields | Relationships |
|---|---|---|---|
| `User` | `users` | `user_type` (enum Admin/Instructor/Student), `name`, `email` (unique), `password` (hashed cast), `contact_number`, `deactivated_at` | hasMany `borrowTransactions`, `itemRequests`, `notifications`, `classSchedules` |
| `Equipment` | `equipment` (explicit `$table`) | `equipment_name`, `description`, `quantity` (total owned), `available_quantity` (on shelf), `status` (enum Available/Unavailable, **derived**), `retired_at` (nullable) | hasMany `borrowTransactions`, `itemRequests` |
| `ItemRequest` | `item_requests` | `user_id`, `equipment_id`, `quantity`, `status` (string: Pending/Approved/Declined), `requested_date`, `remarks`, `decision_reason`, `decided_at`, `decided_by` | belongsTo `user`, `equipment`, `decider` |
| `BorrowTransaction` | `borrow_transactions` | `user_id`, `equipment_id`, `borrow_date`, `return_date` (nullable), `quantity`, `purpose`, `status` (enum Borrowed/Returned/Overdue), `remarks`, `class_schedule_id` (nullable, `onDelete('set null')`), `voided_at`, `void_reason` | belongsTo `user`, `equipment`, `classSchedule`; hasOne `returnLog` |
| `ReturnLog` | `return_logs` | `borrow_transaction_id`, `user_id` (the *staff receiver*, nullable, `set null`), `return_date`, `condition`, `remarks` | belongsTo `borrowTransaction`, `receiver` (User via `user_id`); hasOneThrough `borrower` (User via transaction) and `equipment` (via transaction) |
| `ClassSchedule` | `class_schedules` | `user_id` (the instructor), `year_level`, `block_name`, `subject_code`, `subject_name`, `schedule_time`, `room` | belongsTo `instructor` (User via `user_id`); hasMany `borrowTransactions` |
| `Notification` | `notifications` | `user_id`, `message`, `notification_type` (e.g. `Return Notice`), `send_date` (dateTime) | belongsTo `user` |

`App\Models\Notification` is a custom table, unrelated to the framework notifications table;
`User` still uses the `Notifiable` trait but nothing dispatches framework notifications.

## Route map

All in `routes/web.php`. Everything under `/admin` and `/borrower` is inside `auth`.

**Public**
- `GET /` — closure, `welcome` view
- `GET /welcome` — `auth.welcome`, `welcome` view
- `GET /login` — `UserController@index` (`login`)
- `POST /login` — `AuthenticateUser@login` (`login.store`)
- `POST /logout` — `AuthenticateUser@destroy` (`logout`)
- `GET /register` — `AuthenticateUser@registerUser` (`register`)
- `POST /register` — `AuthenticateUser@registerPublic` (`register.store`); role is derived from the email domain, never read from the request
- `GET /privacy` / `GET /terms` — `Route::view` (`legal.privacy`, `legal.terms`); both render through `layouts/legal`, which builds the page from one `$doc` array per document
- `GET /forgot-password` — `PasswordResetController@request` (`password.request`); renders the confirmation state instead of the form while `reset_link_sent_to` is in the session; `?new=1` clears it ("Use a different address")
- `POST /forgot-password` — `PasswordResetController@email` (`password.email`); also serves the Resend button, and refuses a deactivated account before the broker is called
- `GET /reset-password/{token}` — `PasswordResetController@reset` (`password.reset`)
- `POST /reset-password` — `PasswordResetController@update` (`password.update`)

**Admin** (`auth` + `userType:Admin`)
- `GET /admin/dashboard` — `AuthenticateUser@adminView` (`admin.dashboard`); loads equipment, users, transactions, requests, return logs
- `GET /admin/equipment` — `EquipmentController@index` (`admin.equipment`)
- `POST /admin/equipment` — `EquipmentController@store` (`admin.equipment.store`)
- `POST /admin/equipment/update` — `EquipmentController@update` (`admin.equipment.update`); id in body, not URL
- `POST /admin/equipment/{id}/retire` — `EquipmentController@retire` (`admin.equipment.retire`); the non-destructive default
- `POST /admin/equipment/{id}/restore` — `EquipmentController@restore` (`admin.equipment.restore`)
- `DELETE /admin/equipment/{id}` — `EquipmentController@destroy` (`admin.equipment.destroy`); refused while any loan or request references the row
- `GET /admin/users` — `UserController@adminUser` (`admin.users`)
- `POST /admin/users` — `AuthenticateUser@register` (`admin.user.register`); the one route that still takes an explicit `user_type`
- `POST /admin/users/update` — `UserController@update` (`admin.users.update`); id in body
- `POST /admin/users/add-sched` — `ClassScheduleController@store` (`admin.add-sched`)
- `POST /admin/users/{id}/deactivate` — `UserController@deactivate` (`admin.users.deactivate`); the non-destructive default, and it blocks login
- `POST /admin/users/{id}/reactivate` — `UserController@reactivate` (`admin.users.reactivate`)
- `DELETE /admin/users/{id}` — `UserController@destroy` (`admin.users.destroy`); blocks self-delete, and refused while any loan or request references the person
- `GET /admin/transaction` — `BorrowTransactionController@index` (`admin.transaction`)
- `POST /admin/transaction` — `BorrowTransactionController@store` (`admin.transaction.store`); multi-equipment create
- `POST /admin/transaction/update` — `BorrowTransactionController@update` (`admin.transaction.update`); id in body
- `POST /admin/transaction/{id}/void` — `BorrowTransactionController@void` (`admin.transaction.void`); requires `void_reason`, restores stock if the loan was open
- `DELETE /admin/transaction/{id}` — `BorrowTransactionController@destroy` (`admin.transaction.destroy`); refused while the loan is open or has a return log
- `POST /admin/transaction/check-in` — `BorrowTransactionController@checkIn` (`admin.transaction.checkin`); records the return. Replaced `inlineUpdate`, which was a status-only edit from a dropdown in the table
- `POST /send-email/{id}` — `BorrowTransactionController@sendManualEmail` (unnamed); JSON. `type=custom` uses `message`, otherwise a canned return reminder
- `GET /admin/notifications` — `NotificationController@index` (`admin.notifications`), renders view `admin.notification`
- `GET /admin/request` — `ItemRequestController@index` (`admin.request`)
- `POST /admin/request/approve` — `ItemRequestController@requestActions` (`admin.request.approve`)
- `POST /admin/request/decline` — `ItemRequestController@requestActions` (`admin.request.decline`)
- `GET /admin/logs` — `ReturnLogsController@index` (`admin.logs`)
- `GET /admin/send-return-alerts` — `BorrowTransactionController@sendReturnAlertNotification` (`admin.send-return-alerts`)

**Borrower** (`auth` + `userType:Instructor,Student`)
- `GET /borrower/dashboard` — `AuthenticateUser@borrowerView` (`borrower.dashboard`); own requests + own transactions + all equipment
- `POST /borrower/request` — `ItemRequestController@store` (`borrower.request.store`)
- `PUT /borrower/request` — `ItemRequestController@update` (`borrower.request.update`); id in body
- `DELETE /borrower/request/{id}` — `ItemRequestController@destroy` (`borrower.request.destroy`)

The approve and decline routes hit the **same** method; it branches on
`$request->route()->getName()`. Renaming either route breaks the branch.

## Critical business rule — stock sync

`equipment.available_quantity` and `equipment.status` are derived state that must stay in sync
with every `borrow_transactions.status` change. The invariant:

- **"out"** = status `Borrowed` **or** `Overdue`, and `voided_at IS NULL` — units are off the shelf, stock is deducted. Every aggregate that counts units out filters on both; `BorrowTransaction::isOut()` is the one place that decides.
- **"in"** = status `Returned` — units are back, stock is restored
- `Borrowed` to `Overdue` (either direction) is a transition *within* "out" — **no stock change**. This is the rule most easily broken; always test `in_array($status, ['Borrowed','Overdue'])`, never `=== 'Borrowed'`.
- After every mutation: `status = $equipment->lendableStatus()`, which is `Available` only when the item has units on the shelf **and** has not been retired
- `quantity` (total owned) is **never** touched by transactions — only by `EquipmentController`

This logic lives in `BorrowTransactionController` and `ItemRequestController::requestActions`.
Every write is wrapped in `DB::transaction` with `Equipment::...->lockForUpdate()` to guard
concurrent stock races, and insufficient stock is signalled by throwing
`ValidationException::withMessages(['quantity' => ...])`, caught by the caller and turned into
a redirect-with-errors.

Where it happens:

- `BorrowTransactionController::store` — loops equipment ids; per item locks the row, and if the new status is "out" checks stock then deducts. The whole loop is one DB transaction, so a shortfall on item 3 rolls back items 1 and 2. Empty placeholder values are stripped from `equipment[]` / `quantities[]` before validation.
- `BorrowTransactionController::checkIn` — the only out-to-in path: `available_quantity += $transaction->quantity`, creates a `ReturnLog` (`user_id` is the acting admin) and sets the status to `Returned`. One direction only — lending the same item again is a new loan, not an edit of the old one — and it refuses a loan that is already returned or voided.
- `BorrowTransactionController::update` — edits an **open** loan only; it throws if the loan is returned or voided. If `equipment_id` changed it locks both rows in sorted id order (deadlock avoidance), releases the old quantity and reserves the new. If the equipment is unchanged it moves the delta: `$newQty - $oldQty` reserved when positive, released when negative. There is no status branch left, because `status` is not in the payload.
- `BorrowTransactionController::void` — restores stock if the loan was open, then stamps `voided_at` + `void_reason`. Voided rows are excluded from every "out" aggregate.
- `BorrowTransactionController::destroy` — a hard delete, refused while the loan is open or a `ReturnLog` points at it. By the time it can run, the row is voided and holds no stock, so there is nothing to restore.
- `ItemRequestController::requestActions`, approve branch — locks the equipment, rejects if short, deducts, flips the request to `Approved`, **and auto-creates a `BorrowTransaction`** (`borrow_date` today, `return_date` today + 7 days, status `Borrowed`, `purpose` falling back to the request remarks). Decline only flips the request status, no stock movement. Both are idempotent: a request whose status is not `Pending` is rejected up front.
- `BorrowTransactionController::sendReturnAlertNotification` — bulk-updates `Borrowed` rows whose `return_date` is past to `Overdue`. Because both are "out", this deliberately performs no stock change. It then emails borrowers whose `return_date` is today, skipping anyone already given a `Return Notice` notification today (checked twice: before sending, and again inside the DB transaction). Invoked by `php artisan notifications:return` (`App\Console\Commands\SendReturnNotifications`), scheduled daily at 08:00 in `bootstrap/app.php`, and reachable manually at `GET /admin/send-return-alerts`.

- `EquipmentController::store` / `::update` — neither takes `available_quantity` or `status` any more. A new item starts fully available; an edit recomputes `available_quantity = quantity − unitsOut()` under a row lock, which also repairs drift, and refuses a total below the units currently out. `EquipmentController::destroy` is refused while any loan or request references the item, so the cascade can no longer take history with it.

## Derived state

Three things are computed, never stored-and-trusted, and the screens read them on every render:

- `Equipment::lendableStatus()` — `Available` when not retired and `available_quantity > 0`. The two stock helpers call it, so the enum cannot drift from the shelf.
- `Equipment::availabilityState()` — the label a row shows (`All in` / `Partly out` / `Running low` / `Fully out` / `Retired`) plus its tone.
- `BorrowTransaction::derivedStatus()` — `Void` / `Returned` / `Overdue` / `Out`, with `Overdue` decided by comparing `return_date` to today. The stored `Overdue` enum still exists and `sendReturnAlertNotification` still writes it, because the reminder queries key off it — but no screen depends on that job having run.

Date strings come from the model too: `dateRangeLabel()` ("Sep 10 → Sep 17"), `timingLabel()`
("3 days late", "due tomorrow", "returned on time") and `dateLine()`, which joins them. Views
never format a borrow or return date by hand, and never render a raw ISO date.

## Conventions

- **No FormRequest classes** — all validation is inline via `$request->validate([...])` in the controller. Follow that; do not introduce `app/Http/Requests` for a one-off change.
- **No service layer, no repositories, no action classes** — business logic lives directly in controllers. `BorrowTransactionController` has one private helper, `safe(callable, string $context)`, which runs a closure and logs any `\Throwable` instead of letting it bubble; callers keep their own response shaping.
- **No policies or gates** — authorization is the `userType` middleware plus ad-hoc checks (`ItemRequestController::assertOwner`, the self-delete guard in `UserController::destroy`).
- **No API routes, no `routes/api.php`** — `sendManualEmail` is the one JSON endpoint left; it is a web route returning `response()->json`, called by fetch from Blade with the token from `<meta name="csrf-token">`. Everything else, check-in included, is a plain form post that redirects with a flash message.
- **Update routes take the id in the request body**, not the URL, and use `POST` (except the borrower request `PUT`). Route-model binding is essentially unused: `ItemRequestController::update` declares an `ItemRequest` parameter but overwrites it with `findOrFail($validated['id'])`.
- **Views** live under `resources/views/` in three groups: `admin/` (dashboard, equipment, transaction, user, request, notification, logs), `borrower/` (dashboard, receipt), and `components/` for everything reusable — `components/admin/*` (navbar plus per-feature modals: `equipment/form-modal`, `user/form-modal`, `user/schedules-modal`, `transaction/new-loan-modal`, `transaction/edit-modal`, `transaction/checkin-modal`, `transaction/email-modal`), `components/instructor/*` (the two borrower request modals), `components/ui/*` (`badge`, `status`, `stat-strip`, `panel`, `empty-state`, `page-header`, `remove-dialog`), plus `alerts`, `auth-card`, `default`. `emails/` holds the mail template. Note `NotificationController` returns `admin.notification` (singular) while the route is named `admin.notifications`.
- **Lists are CSS grids, not `<table>`s.** There is no `<table>` left in the app. A row is a grid that restacks below `md`/`lg`, which is what let DataTables Responsive go. A list opts into search and filtering by wrapping itself in `[data-list]` and marking rows `[data-list-row]` with `data-search` and `data-chip`; `resources/js/ui.js` does the rest.
- **One destructive confirm.** `x-ui.remove-dialog` is the only delete dialog; a page renders one instance and each row's trigger carries the figures (`data-fact-a/b/c`), the reason a hard delete is refused (`data-blocked`), and the two form targets (`data-delete-url`, `data-safe-url`). If you add a destructive action, add it here rather than writing a fourth modal.
- **Flash messages** — `success` / `error` via session, rendered by `components/alerts.blade.php`. Login flashes both `welcome` and `success` for legacy view checks.
- **Casing matters** — model statuses are TitleCase (`Borrowed`, `Pending`, `Available`). The `item_requests` migration defaults `status` to lowercase `'pending'`, but `requestActions` compares against `'Pending'`, so rows created straight from the DB default are not processable. Always write `'Pending'` explicitly, as `ItemRequestController::store` does.
- **Style** — Laravel Pint defaults (`vendor/bin/pint`). The admin sidebar layout is `w-64` / `md:ml-64`.