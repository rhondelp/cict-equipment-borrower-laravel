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

Only an Admin can create an `Admin`, through the admin users form with extra checks (below). Public sign-up can never create one.

**Every account — student and instructor — is on `@nmsc.edu.ph`** (`User::SCHOOL_DOMAIN`), so the
address says nothing about role. (Until 26 Sept 2026 the code assumed a separate
`@student.nmsc.edu.ph` and derived the role from the domain; that assumption was wrong and
`roleForEmail()` / `roleMatchesDomain()` are gone. Do not bring back domain-derived roles.)

**Public sign-up (`POST /register` → `AuthenticateUser::registerPublic`)** asks the person to
pick Student or Instructor (`requested_role`, `in:Student,Instructor`), but the answer is a
**request, not a grant**: the account is *always* written as `Student`. Choosing Instructor stamps
`users.instructor_requested_at`; the account works as a Student straight away, and an admin
confirms or declines on the users screen (`POST /admin/users/{id}/instructor/confirm|decline`).
Confirming sets `Instructor` and writes the `role_overridden_*` record ("Confirmed instructor
request from sign-up"); declining just clears the request. `user_type` is never read from the
request. The email must be on the school domain, matched **whole** by `User::isSchoolEmail()`,
never as a suffix — `str_ends_with($email, 'nmsc.edu.ph')` would accept `not-nmsc.edu.ph`.
Pending requests appear in the dashboard queue and as a "Instructor requests" section and
`requested` filter chip on `/admin/users`.

**The admin users form (`POST /admin/users` → `AuthenticateUser::register`)** takes an
explicit `user_type`, `in:Admin,Instructor,Student`, behind `userType:Admin`. Since 1 Oct 2026 it
can create another **Admin (staff)**, but only with all four of:
- a whole-domain `@nmsc.edu.ph` email;
- `Password::min(8)->letters()->numbers()`;
- a `role_override_reason`;
- the creating admin's own password (`current_password`).

The new admin is stamped with `role_overridden_*` (who, when, why). Borrower accounts keep the
lighter `min:4` rules. Pinned by `tests/Feature/StaffAccountTest`,
`tests/Feature/SecurityRegressionTest` and `tests/Feature/RegisterPageTest`.

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
live in `config/office.php` (`OFFICE_EMAIL`, `OFFICE_LOCATION`, the structured `hours`, and
`loan_days`). Pinned by
`tests/Feature/ForgotPasswordPageTest`.

The **reset page** (`reset-password.blade.php`) takes its email from the link's query string and
never lets it be edited. It is a chip on the page and a hidden input in the form. `reset()` asks
the broker's `tokenExists()` up front and renders an **expired state** for a used, replaced,
timed-out or email-less link. Its countdown is the token row's `created_at` plus `expire`. The
password rule here is **`Password::min(8)->letters()->numbers()`, plus "must not contain the part
of the email before the @"**. There is no confirmation field. Registration and the admin user form
still use `min:4`, so the app does not agree on length yet. The page's live checklist mirrors
these rules exactly, so change both together. A broker `INVALID_TOKEN`/`INVALID_USER` on submit
redirects back to the link with `reset_link_expired` flashed, which shows the expired state. On
success nobody is signed in. The remember token is rotated, the user's other `sessions` rows are
deleted (database driver only), and the redirect to `/login` flashes `password_reset`. That flash
draws the "Password updated" panel on the sign-in page. Pinned by
`tests/Feature/ResetPasswordPageTest`. No page loads `resources/css/auth.css` any more.

## Admin screen states

Three states were added on 21 September 2026 and are enforced on the server, not
by hiding controls:

- **`equipment.retired_at`** — not lendable, history kept. Availability itself is
  always derived from open loans; `Equipment::availabilityState()` is the single
  source for the four labels (All in / Partly out / Running low / Fully out, low
  being under 30%).
- **`equipment.category`** (added 2 October 2026) — free text, no categories table.
  The admin form offers the categories in use (`Equipment::categoriesInUse()`, a
  `<datalist>`) and accepts a new one; `EquipmentController` passes every value
  through `Equipment::canonicalCategory()`, which trims, collapses spaces, maps
  blank to `null`, and reuses an existing spelling case-insensitively. Borrowers
  see it as group headings in the request modal (A–Z, uncategorised last as
  "Other", no headings at all when nothing has a category) and after the stock
  count on the dashboard shelf. The admin inventory list does not show it yet.
- **`equipment.loan_type`** (form and list since 5 October 2026) — the add/edit
  dialog has three radio cards (Returnable, Time-Limited, Non-Returnable), each
  with a one-line hint. Returnable is the default. When the item has units out,
  the page script disables the other two cards and says why; the server refuses
  the change regardless. Each inventory row shows the type as a neutral
  `x-ui.badge` with an icon (`fa-rotate-left` / `fa-clock` / `fa-box-open`); it is
  neutral because colour on that screen marks a stock problem. The type's key is
  added to the row's `data-chip`. The three type chips sit in the **same** chip
  row as All / Lendable / Running low / Fully out, because `ui.js` holds one
  active chip per list, and they carry counts. A type no item uses is rendered
  `disabled`. `?filter=time_limited` etc. work through the existing mechanism.
  The admin loan screen reads it (see "Loans screen" below), request approval
  shapes the loan it creates from it, and the borrower's request form states it
  per item via `Equipment::borrowerReturnNote()` ("Return by a date", "Return
  within 1 hour" from `config('office.time_limited_minutes')`, "Given to you —
  no return needed"), on non-returnable rows and under the list.
- **`return_logs.resolution` / `resolved_at` / `resolved_by`** — the outcome of a
  damaged or lost return. `needsFollowUp()` is `isIncident() && ! isResolved()`
  and is what the return-logs screen and the dashboard both lead with. Return
  logs are **immutable**: there is no update or destroy route, corrections go to
  `return_log_notes`, and `ReturnLogsPageTest` asserts the route table itself.
- **`users.suspended_at` / `suspension_reason` / `suspended_by`** — suspension is
  *not* deactivation. A suspended account signs in and cannot borrow (blocked in
  `ItemRequestController::store`); a deactivated one cannot sign in at all
  (blocked in `AuthenticateUser::login`). `users.role_overridden_at` /
  `role_override_reason` / `role_overridden_by` record the latest role change.
  Nothing derives a role any more, so `UserController::update` treats *any* change
  from the stored `user_type` as a decision: it refuses one with no reason, and
  records who, when and why. Confirming an instructor request writes the same record.
- **`users.instructor_requested_at`** — set at sign-up when someone picks Instructor;
  the account is a Student until an admin confirms. Cleared by confirm, decline, or
  any manual role change. Migration `2026_09_26_120000_add_instructor_request_to_users`.

**Shared list behaviour** lives in `resources/js/ui.js` and is used by every
admin list: search, filter chips, one sort control (`data-list-sort` + per-row
`data-sort-<key>`, reordering inside `data-list-rows`), a date range
(`data-list-from` / `data-list-to` against a row's ISO `data-date`), and a
`?filter=` URL parameter that sets a chip on load — which is what lets a
dashboard queue entry land on the rows it counted. `x-ui.stat-strip` figures can
carry `chip` + `list` to become filter controls, and a figure reading zero
renders as plain text rather than a control with nothing behind it.

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
soft deletes. The one exception is `ActivityLog`, whose `booted()` hooks and custom query builder
make it append-only (see below). FKs are `onDelete('cascade')` unless noted. Date columns are cast on `Equipment`,
`BorrowTransaction`, `ItemRequest`, `ReturnLog` and `User`, so `$tx->borrow_date` is a Carbon —
do not `Carbon::parse` it again, and do not echo it bare (you get `Y-m-d H:i:s`).

Three nullable timestamps carry the "no longer in use, still real" state the remove dialogs
offer instead of a hard delete: `equipment.retired_at`, `users.deactivated_at`,
`borrow_transactions.voided_at`. Nothing in the app hard-deletes a row that history points at.

| Model | Table | Key fields | Relationships |
|---|---|---|---|
| `User` | `users` | `user_type` (enum Admin/Instructor/Student), `name`, `email` (unique), `password` (hashed cast), `contact_number`, `deactivated_at`, `instructor_requested_at` | hasMany `borrowTransactions`, `itemRequests`, `notifications`, `classSchedules` |
| `Equipment` | `equipment` (explicit `$table`) | `equipment_name`, `description`, `category` (nullable free text, max 60, indexed; set only through `Equipment::canonicalCategory()`), `loan_type` (string 20, indexed, default `returnable`; one of `Equipment::LOAN_TYPES`: `returnable` / `time_limited` / `non_returnable`, labels Returnable / Time-Limited / Non-Returnable), `quantity` (total owned, issued units included), `available_quantity` (on shelf), `status` (enum Available/Unavailable, **derived**), `retired_at` (nullable) | hasMany `borrowTransactions`, `itemRequests` |
| `ItemRequest` | `item_requests` | `user_id`, `equipment_id`, `quantity`, `status` (string: Pending/Approved/Declined), `requested_date`, `remarks`, `decision_reason`, `decided_at`, `decided_by` | belongsTo `user`, `equipment`, `decider` |
| `BorrowTransaction` | `borrow_transactions` | `user_id`, `equipment_id`, `borrow_date` (DATETIME), `return_date` (DATETIME, nullable), `timed` (bool, default false), `quantity`, `purpose`, `status` (enum Borrowed/Returned/Overdue/Issued), `remarks`, `class_schedule_id` (nullable, `onDelete('set null')`), `voided_at`, `void_reason` | belongsTo `user`, `equipment`, `classSchedule`; hasOne `returnLog` |
| `ReturnLog` | `return_logs` | `borrow_transaction_id`, `user_id` (the *staff receiver*, nullable, `set null`), `return_date`, `condition`, `remarks` | belongsTo `borrowTransaction`, `receiver` (User via `user_id`); hasOneThrough `borrower` (User via transaction) and `equipment` (via transaction) |
| `ClassSchedule` | `class_schedules` | `user_id` (the instructor), `year_level`, `block_name`, `subject_code`, `subject_name`, `schedule_time`, `room` | belongsTo `instructor` (User via `user_id`); hasMany `borrowTransactions` |
| `Notification` | `notifications` | `user_id`, `message`, `notification_type` (e.g. `Return Notice`), `send_date` (dateTime) | belongsTo `user` |
| `ActivityLog` | `activity_logs` | `occurred_at` (dateTime, indexed), `type` (string 40, a key of `ActivityLog::TYPES`), `actor_id` + `actor_name` + `actor_role`, `subject_user_id` + `subject_name`, `equipment_id` + `equipment_name`, `borrow_transaction_id`, `quantity`, `status_from` / `status_to` (labels), `details` (a sentence), `meta` (json, cast array), `ip_address`, `source` (`live`/`backfill`), `backfill_key` (unique, nullable). Every FK is `nullOnDelete`; the `*_name` columns are snapshots taken at write time | belongsTo `actor`, `subject` (Users), `equipment`, `borrowTransaction` — all nullable |

**Loan dates are DATETIME since 5 Oct 2026** (migration `2026_10_05_120100_add_times_to_borrow_transactions`),
cast `datetime`. A date-only loan (`timed` false) is stored at `00:00:00` and is due by the end of
that day; a timed loan (`timed` true) is due at the exact `return_date`. Ask `dueAt()`, never compare
`return_date` yourself. In SQL, use the scopes: `BorrowTransaction::out()` (not voided,
`Borrowed`/`Overdue`, so never Issued) and `BorrowTransaction::overdue(?now)` (out, and timed with
`return_date < now` or date-only with `return_date < today 00:00`). For a `SUM(CASE …)` there is
`BorrowTransaction::overdueCaseSql()` → `[sql, bindings]`. Since 5 Oct 2026 every overdue aggregate
goes through these: the borrowing block, the nightly sweep, the users screen's `overdue_count`, the
request queue's standing, and the dashboards. A test asserts that the scope and `isOverdue()` agree
row by row. A raw `whereDate('return_date', '<', today)` would miss a timed loan due earlier today,
so don't write one. Both `down()`s
of the 5 Oct migrations refuse to run while timed or `Issued` rows exist, rather than truncating them.
`return_logs.return_date` is still a DATE column (cast `datetime`), so it holds no time of day.

**`Issued`** is the status of a non-returnable hand-over. Two paths write it, both from the item's
`loan_type` and never from the request: the admin **New loan** form (`BorrowTransactionController::store`)
and **request approval** (`ItemRequestController::requestActions`). An Issued row has `return_date`
null and `timed` false. It is never checked in, never edited (it is **void-only**), never hard-deleted
until voided, never swept to Overdue, never reminded (`sendManualEmail` refuses the canned
reminder with a 422 and allows a custom message), and never blocks a new request. On the borrower
side it shows only in earlier activity ("Issued to you — no return needed") and on an **Issue Slip**
with no return line.

**The activity log** (`activity_logs`, since 7 Oct 2026) is the audit trail the reports will read.
**Nothing writes to it live yet**: this was the foundation only, with no controller, view or route
changes. Write an entry with `ActivityLog::record($type, $attrs)`:
- it refuses a type not in `ActivityLog::TYPES`;
- it stamps `occurred_at = now()` and takes the actor from `auth()->user()`; pass `'actor' => null`
  for the scheduler, which reads as "System";
- it snapshots names from the `actor` / `subject` / `equipment` / `loan` models passed in. A `loan`
  fills `equipment` and `subject` from its own item and borrower;
- any column passed explicitly wins over a snapshot;
- it records the request IP for a web request and none for a console run.

`TYPES` maps each stable key (`loan_created`, `request_approved`, `user_suspended`, …) to a `label`
and a `group`, and `GROUPS` gives the report order: Loans, Requests, Equipment, Users, Returns,
Account & system. Never rename a key, since keys are stored; labels can change. Read with
`filter([...])`, whose keys are `from`, `to` (inclusive of the whole end day), `type` (a key or a
group name), `equipment_id`, `user_id` (actor **or** subject), `status` (matches `status_to`) and
`q`, and with `newestFirst()`.

**`php artisan activity:backfill`** (`App\Console\Commands\BackfillActivityLog`) rebuilds history
from the older tables with `source = 'backfill'` and a deterministic `backfill_key`, so a second
run adds nothing. Where the old schema never stored who acted (loan creation, voids, deactivation,
retirement, reminders), the actor is "Not recorded". For each type it stops at the first `live`
entry, so once live logging is wired in it cannot write the same event twice. Some history is
unrecoverable and is not invented: lifted suspensions, reactivations, restores, declined instructor
requests, and every role change but the latest. Pinned by `tests/Feature/ActivityLogTest`.

`App\Models\Notification` is a custom table, unrelated to the framework notifications table;
`User` still uses the `Notifiable` trait but nothing dispatches framework notifications.

## Route map

All in `routes/web.php`. Everything under `/admin` and `/borrower` is inside `auth`.

**Public**
- `GET /` — `UserController@welcome`, `welcome` view, built to `design-reference/Landing.dc.html`: hero with a live "On the shelf now" card (at most five lendable items, out → low → partly out → full, colours by `availabilityState()`), a key-figures band, How it works, The rules, a Visit panel and footer. Counts are lendable-only (retired excluded), from the same read as the shelf; hours via `OfficeHours`; a signed-in visitor gets "Go to your dashboard" instead of sign-in
- `GET /welcome` — `UserController@welcome` (`auth.welcome`), same page
- `GET /login` — `UserController@index` (`login`)
- `POST /login` — `AuthenticateUser@login` (`login.store`)
- `POST /logout` — `AuthenticateUser@destroy` (`logout`)
- `GET /register` — `AuthenticateUser@registerUser` (`register`)
- `POST /register` — `AuthenticateUser@registerPublic` (`register.store`); always creates a `Student`; `requested_role=Instructor` only files a request
- `GET /privacy` / `GET /terms` — `Route::view` (`legal.privacy`, `legal.terms`); both render through `layouts/legal`, which builds the page from one `$doc` array per document
- `GET /forgot-password` — `PasswordResetController@request` (`password.request`); renders the confirmation state instead of the form while `reset_link_sent_to` is in the session; `?new=1` clears it ("Use a different address")
- `POST /forgot-password` — `PasswordResetController@email` (`password.email`); also serves the Resend button, and refuses a deactivated account before the broker is called
- `GET /reset-password/{token}` — `PasswordResetController@reset` (`password.reset`); needs `?email=`, renders the expired state when the broker's `tokenExists` fails
- `POST /reset-password` — `PasswordResetController@update` (`password.update`); no `password_confirmation`; success → `/login` with `password_reset` flashed, dead token → back to the link with `reset_link_expired`

**Admin** (`auth` + `userType:Admin`)
- `GET /admin/dashboard` — `AuthenticateUser@adminView` (`admin.dashboard`); loads equipment, users, transactions, requests, return logs
- `GET /admin/equipment` — `EquipmentController@index` (`admin.equipment`)
- `POST /admin/equipment` — `EquipmentController@store` (`admin.equipment.store`)
- `POST /admin/equipment/update` — `EquipmentController@update` (`admin.equipment.update`); id in body, not URL
- `POST /admin/equipment/{id}/retire` — `EquipmentController@retire` (`admin.equipment.retire`); the non-destructive default
- `POST /admin/equipment/{id}/restore` — `EquipmentController@restore` (`admin.equipment.restore`)
- `DELETE /admin/equipment/{id}` — `EquipmentController@destroy` (`admin.equipment.destroy`); refused while any loan or request references the row
- `GET /admin/users` — `UserController@adminUser` (`admin.users`)
- `POST /admin/users` — `AuthenticateUser@register` (`admin.user.register`); the one route that still takes an explicit `user_type`; creating an `Admin` needs a reason and the creator's own password
- `POST /admin/users/update` — `UserController@update` (`admin.users.update`); id in body
- `POST /admin/users/add-sched` — `ClassScheduleController@store` (`admin.add-sched`)
- `POST /admin/users/{id}/deactivate` — `UserController@deactivate` (`admin.users.deactivate`); the non-destructive default, and it blocks login
- `POST /admin/users/{id}/reactivate` — `UserController@reactivate` (`admin.users.reactivate`)
- `DELETE /admin/users/{id}` — `UserController@destroy` (`admin.users.destroy`); blocks self-delete, and refused while any loan or request references the person
- `GET /admin/transaction` — `BorrowTransactionController@index` (`admin.transaction`)
- `POST /admin/transaction` — `BorrowTransactionController@store` (`admin.transaction.store`); multi-equipment create, each item recorded by its own loan type; `return_date` is `nullable` in the rules and required in `handoverMoments()` when anything is coming back
- `POST /admin/transaction/update` — `BorrowTransactionController@update` (`admin.transaction.update`); id in body; keeps the loan's own `timed`; refuses Issued rows and a move to an item of another loan type
- `POST /admin/transaction/{id}/void` — `BorrowTransactionController@void` (`admin.transaction.void`); requires `void_reason`, restores stock if the loan was open **or Issued**
- `DELETE /admin/transaction/{id}` — `BorrowTransactionController@destroy` (`admin.transaction.destroy`); refused while the loan is open, is an un-voided Issued row, or has a return log
- `POST /admin/transaction/check-in` — `BorrowTransactionController@checkIn` (`admin.transaction.checkin`); records the return, refuses Issued rows. Replaced `inlineUpdate`, which was a status-only edit from a dropdown in the table
- `POST /send-email/{id}` — `BorrowTransactionController@sendManualEmail` (unnamed); JSON. `type=custom` uses `message`, otherwise a canned return reminder
- `GET /admin/notifications` — `NotificationController@index` (`admin.notifications`), renders view `admin.notification`
- `GET /admin/request` — `ItemRequestController@index` (`admin.request`)
- `POST /admin/request/approve` — `ItemRequestController@requestActions` (`admin.request.approve`)
- `POST /admin/request/decline` — `ItemRequestController@requestActions` (`admin.request.decline`)
- `GET /admin/logs` — `ReturnLogsController@index` (`admin.logs`)
- `GET /admin/logs/item/{equipment}` — `ReturnLogsController@itemHistory` (`admin.logs.item`)
- `POST /admin/logs/{id}/resolve` — `ReturnLogsController@resolve` (`admin.logs.resolve`); the only writes on this
- `POST /admin/logs/{id}/notes` — `ReturnLogsController@addNote` (`admin.logs.note`); append-only correction
- `POST /admin/users/{id}/suspend` — `UserController@suspend` (`admin.users.suspend`)
- `POST /admin/users/{id}/lift-suspension` — `UserController@liftSuspension` (`admin.users.lift`)
- `POST /admin/users/{id}/instructor/confirm` — `UserController@confirmInstructor` (`admin.users.instructor.confirm`); makes the account an Instructor and records who
- `POST /admin/users/{id}/instructor/decline` — `UserController@declineInstructor` (`admin.users.instructor.decline`); leaves a Student, clears the request
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
- **"issued"** = status `Issued`, `voided_at IS NULL` — units left for good. **Not "out"**: `isOut()`, `unitsOut()` and every `Borrowed`/`Overdue` aggregate exclude it, so it is never overdue and never chased. It still comes off the shelf: `Equipment::unitsIssued()` sums it, and the derived figure is `available_quantity = quantity − unitsOut() − unitsIssued()` (`Equipment::derivedAvailableQuantity()`). `quantity` keeps counting issued units. Voiding an Issued row drops it out of `unitsIssued()`, and `void` releases its units on `available_quantity` too, so the stored and derived figures stay equal. An issue takes stock with `reserveStock()` like a loan; it is simply never released by a check-in.
- `Borrowed` to `Overdue` (either direction) is a transition *within* "out" — **no stock change**. This is the rule most easily broken; always test `in_array($status, ['Borrowed','Overdue'])`, never `=== 'Borrowed'`.
- After every mutation: `status = $equipment->lendableStatus()`, which is `Available` only when the item has units on the shelf **and** has not been retired
- `quantity` (total owned) is **never** touched by transactions — only by `EquipmentController`

This logic lives in `BorrowTransactionController` and `ItemRequestController::requestActions`.
Every write is wrapped in `DB::transaction` with `Equipment::...->lockForUpdate()` to guard
concurrent stock races, and insufficient stock is signalled by throwing
`ValidationException::withMessages(['quantity' => ...])`, caught by the caller and turned into
a redirect-with-errors.

Where it happens:

- `BorrowTransactionController::store` — locks **every selected equipment row up front**, in id order, then `handoverMoments()` checks the two date fields against the locked rows' loan types:
  - only non-returnable items: no due date is needed, and one sent is ignored;
  - anything returnable or time-limited: a due date is required;
  - anything time-limited: both fields need a time (`hasTime()`, `HH:MM` present), and the due moment must be strictly after the borrow moment;
  - returnable items are due on or after the borrow day.

  Then it loops the items, deducting stock with `reserveStock()` for every type, and writes one row per item:
  - returnable: `Borrowed`, both dates at `00:00:00`, `timed` false; in a mixed booking it takes the date part of the shared datetime;
  - time-limited: `Borrowed`, the exact moments, `timed` true;
  - non-returnable: `Issued`, `return_date` null, `timed` false.

  `status` and `timed` are never read from the request. The whole loop is one DB transaction, so a shortfall or a date refusal on any item rolls back the rest. Empty placeholder values are stripped from `equipment[]` / `quantities[]` before validation.
- `BorrowTransactionController::checkIn` — the only out-to-in path: `available_quantity += $transaction->quantity`, creates a `ReturnLog` (`user_id` is the acting admin) and sets the status to `Returned`. One direction only — lending the same item again is a new loan, not an edit of the old one — and it refuses a loan that is already returned or voided, **and any Issued row** (nothing is due back). The "Logged as … late" line in its flash comes from `timingLabel()` read before the status flips, so a timed loan says "10 min late".
- `BorrowTransactionController::update` — edits an **open** loan only; it throws if the loan is returned, voided or Issued (issues are void-only). The loan keeps its own `timed`. `editedMoments()` needs a time on both fields and due > borrow for a timed loan, and stores date parts at `00:00:00` for a date-only one. If `equipment_id` changed, the new item must be of the loan's kind (`time_limited` for a timed loan, `returnable` otherwise). It then locks both rows in sorted id order (deadlock avoidance), releases the old quantity and reserves the new. If the equipment is unchanged it moves the delta: `$newQty - $oldQty` reserved when positive, released when negative. There is no status branch left, because `status` is not in the payload.
- `BorrowTransactionController::void` — restores stock if the loan was open **or Issued**, then stamps `voided_at` + `void_reason`. Voided rows are excluded from every "out" aggregate and from `unitsIssued()`.
- `BorrowTransactionController::destroy` — a hard delete, refused while the loan is open, while an Issued row is un-voided (deleting it would drop it from `unitsIssued()` and leave the shelf short), or while a `ReturnLog` points at it. By the time it can run, the row is voided and holds no stock, so there is nothing to restore.
- **Loans screen** (`admin/transaction.blade.php`) — the queue sorts on `dueAt()` (controller and `data-sort-urgency` / `data-sort-due`). Issued rows rank with the settled records. They have an `issued` chip key and their own chip when any exist, and they read "Issued Oct 5 · Not expected back". They have **no** Check-in, Edit or Email button, because the only email template is a return reminder. The overdue figure says "Longest: …" via the worst loan's `timingLabel()`, so a timed loan reads in minutes. The new-loan modal reads `data-loan-type` on each checkbox and switches between `date` and `datetime-local`, or hides **and disables** the due field. In time mode it shows +30 min / +1 hour / +2 hours presets counted from the borrow moment. The edit modal sets its input type from the trigger's `data-timed` **before** setting the value, since a `date` input drops a value with a time. It also disables equipment options of another loan type. `LoansPageTest` checks every `xForm.querySelector('[data-…]')`, every literal `getElementById`, and every `#modal [data-…]` lookup in the script.
- `ItemRequestController::store` — refuses a new request from a **suspended** account, and (since 26 Sept 2026) from a borrower with **any loan that is `overdue()`**. The due moment decides, not the stored status, which lags until the nightly sweep: a one-hour loan ten minutes late blocks, a date-only loan due today does not, and an Issued row never does. This is the "overdue items pause borrowing" rule the terms summary and the landing page state. Editing an existing pending request is not blocked. Pinned by `tests/Feature/BorrowingRulesTest`.
- `ItemRequestController::requestActions`, approve branch — locks the equipment, rejects if short, deducts with `reserveStock()` (the same arithmetic for every type, since `available_quantity` is quantity − out − issued), flips the request to `Approved`, **and auto-creates a `BorrowTransaction`** shaped by the locked item's `loan_type`:
  - returnable: `borrow_date` today, `return_date` today + `config('office.loan_days')` (7), `timed` false, `Borrowed`;
  - time-limited: `borrow_date` now, `return_date` now + `config('office.time_limited_minutes')` (60, env `OFFICE_TIME_LIMITED_MINUTES`), `timed` true, `Borrowed`. **The clock starts at approval**;
  - non-returnable: `Issued`, `borrow_date` today, no `return_date`, `timed` false.

  `purpose` falls back to the request remarks. Decline only flips the request status, with no stock movement. Both are idempotent: a request whose status is not `Pending` is rejected up front.
- `BorrowTransactionController::sendReturnAlertNotification` — bulk-updates `Borrowed` rows that are `overdue()` to `Overdue`: timed loans by their time, others by their day, never an Issued row. Because both are "out", this deliberately performs no stock change. It then emails borrowers with an `out()`, still-`Borrowed` loan whose `return_date` falls today; a timed loan's mail names its time ("due back on Oct 5 at 9:00 AM"). It skips anyone already given a `Return Notice` notification today, checked twice: before sending, and again inside the DB transaction. Invoked by `php artisan notifications:return` (`App\Console\Commands\SendReturnNotifications`, which prints the summary), scheduled **daily** at 08:00 in `bootstrap/app.php`, and reachable manually at `GET /admin/send-return-alerts`. **No hourly run exists**: a timed loan that falls due after 08:00 is stored as `Overdue` only on the next run. Screens and the borrowing block do not wait for that, because they use `dueAt()`.

- `EquipmentController::store` / `::update` — neither takes `available_quantity` or `status` any more. A new item starts fully available; an edit recomputes `available_quantity = quantity − unitsOut() − unitsIssued()` under a row lock, which also repairs drift, and refuses a total below the units currently out, or below out + issued. The index query loads `units_out` and `units_issued` with `withSum`; `outNow()` / `issuedNow()` read them. Both take `loan_type` (`required`, `Rule::in(array_keys(Equipment::LOAN_TYPES))`). A store without it gets `returnable` via `mergeIfMissing`. An update without it **keeps the current type**: it is validated `sometimes` and falls back to the row's own value, so an older form cannot reset an item. `update` refuses a type change while `unitsOut() > 0`, under the same row lock, with the error on `loan_type`, and saves nothing else from that post. Changing type with only returned, voided or issued history is allowed. `EquipmentController::destroy` is refused while any loan or request references the item, so the cascade can no longer take history with it.

## Opening hours and office config

`config('office.hours')` is **structured** — `['days' => [1..5], 'open' => '08:00', 'close' =>
'17:00']`, ISO weekdays, app timezone (`Asia/Manila`) — and is read only through
`App\Support\OfficeHours::fromConfig()`, which renders `timeRange()` ("8:00 AM – 5:00 PM"),
`dayRange()` / `dayRange(short: true)` ("Monday to Friday" / "Mon–Fri"), `label()` (the sentence
form every page used before), `closingTime()`, and `isOpenToday()` (a working day, before closing
time). Never write the hours into a template: the landing page, sign-in panel, register and
forgot-password copy, and the borrower dashboard all go through the helper. There is no holiday
calendar — `isOpenToday()` knows weekdays only. `config('office.loan_days')` (7) is the default
loan period, used by the approve branch and quoted on the landing page.
`config('office.time_limited_minutes')` (60, env `OFFICE_TIME_LIMITED_MINUTES`) is the period of a
time-limited loan created by approval; `Equipment::timeLimitLabel()` words it ("1 hour", "90 minutes")
for the borrower's request form. `config('office.location')`
("Equipment room, CICT building") came from the landing mockup and is unconfirmed.

## Derived state

Three things are computed, never stored-and-trusted, and the screens read them on every render:

- `Equipment::lendableStatus()` — `Available` when not retired and `available_quantity > 0`. The two stock helpers call it, so the enum cannot drift from the shelf.
- `Equipment::availabilityState()` — the label a row shows (`All in` / `Partly out` / `Running low` / `Fully out` / `Retired`) plus its tone.
- `BorrowTransaction::derivedStatus()` — `Void` / `Issued` / `Returned` / `Overdue` / `Out`, checked in that order. `Overdue` is `isOut() && dueAt() < now()`. `dueAt()` is `return_date` for a timed loan and `return_date->endOfDay()` for a date-only one, so a date-only loan due today turns overdue at midnight, exactly as before. `Issued` is never out and never overdue, and its `statusTone()` is `neutral`. The stored `Overdue` enum still exists and `sendReturnAlertNotification` still writes it, because the reminder queries key off it — but no screen depends on that job having run.
- `Equipment::loanTypeLabel()`, `isReturnable()`, `isTimeLimited()`, `isNonReturnable()` read `loan_type`. The model's `$attributes` default matches the column, so an unsaved item is Returnable too.

Date strings come from the model too: `dateRangeLabel()` ("Sep 10 → Sep 17"), `timingLabel()`
("3 days late", "due tomorrow", "returned on time") and `dateLine()`, which joins them. Views
never format a borrow or return date by hand, and never render a raw ISO date. Date-only loans
render exactly as they did before the columns became datetimes. Timed and issued loans read differently:
- **Timed:** "Oct 5, 2:30 PM → 3:30 PM" (both dates when the loan crosses midnight), then
  "due in 25 min", "due at 3:30 PM", "due tomorrow, 9:00 AM", "10 min late", "2 hr late" or
  "3 days late". A timed return is judged by the return log's `created_at`, because the log's
  `return_date` has no time of day.
- **Issued:** `dateRangeLabel()` is "Issued Oct 5", `timingLabel()` is "issued", and `dateLine()`
  is just "Issued Oct 5".
- **Counts:** `daysUntilDue()` stays calendar days for both kinds, so the "due today" counters keep
  their meaning. It is null for an issued item. `daysLate()` counts whole 24-hour periods for a timed loan.

`bookingKey()` keys a date-only loan on its day, unchanged. A timed loan is keyed on the minute, so
two sessions on one day are two bookings.

## Conventions

- **No FormRequest classes** — all validation is inline via `$request->validate([...])` in the controller. Follow that; do not introduce `app/Http/Requests` for a one-off change.
- **No service layer, no repositories, no action classes** — business logic lives directly in controllers. `BorrowTransactionController` has one private helper, `safe(callable, string $context)`, which runs a closure and logs any `\Throwable` instead of letting it bubble; callers keep their own response shaping.
- **No policies or gates** — authorization is the `userType` middleware plus ad-hoc checks (`ItemRequestController::assertOwner`, the self-delete guard in `UserController::destroy`).
- **No API routes, no `routes/api.php`** — `sendManualEmail` is the one JSON endpoint left; it is a web route returning `response()->json`, called by fetch from Blade with the token from `<meta name="csrf-token">`. Everything else, check-in included, is a plain form post that redirects with a flash message.
- **Update routes take the id in the request body**, not the URL, and use `POST` (except the borrower request `PUT`). Route-model binding is essentially unused: `ItemRequestController::update` declares an `ItemRequest` parameter but overwrites it with `findOrFail($validated['id'])`.
- **Views** live under `resources/views/` in three groups: `admin/` (dashboard, equipment, transaction, user, request, notification, logs), `borrower/` (dashboard, receipt), and `components/` for everything reusable — `components/admin/*` (navbar plus per-feature modals: `equipment/form-modal`, `user/form-modal`, `user/schedules-modal`, `transaction/new-loan-modal`, `transaction/edit-modal`, `transaction/checkin-modal`, `transaction/email-modal`), `components/instructor/*` (the two borrower request modals), `components/ui/*` (`badge`, `status`, `stat-strip`, `panel`, `empty-state`, `page-header`, `remove-dialog`), plus `alerts`, `auth-card`, `default`. `emails/` holds the mail template. Note `NotificationController` returns `admin.notification` (singular) while the route is named `admin.notifications`.
- **Lists are CSS grids, not `<table>`s.** There is no `<table>` left in the app. A row is a grid that restacks below `md`/`lg`, which is what let DataTables Responsive go. A list opts into search and filtering by wrapping itself in `[data-list]` and marking rows `[data-list-row]` with `data-search` and `data-chip`; `resources/js/ui.js` does the rest.
- **One destructive confirm.** `x-ui.remove-dialog` is the only delete dialog; a page renders one instance and each row's trigger carries the figures (`data-fact-a/b/c`), the reason a hard delete is refused (`data-blocked`), and the two form targets (`data-delete-url`, `data-safe-url`). If you add a destructive action, add it here rather than writing a fourth modal.
- **Modal headers sit outside the `<form>`.** Each modal is `div[data-modal] > header + form`, so the header's summary/timing lines are *not* descendants of the form. Look them up from the modal (`document.querySelector('#edit-loan-modal [data-edit-summary]')`), never `someForm.querySelector(...)` — a null there throws inside the click handler before `openModal()` runs, and the button silently does nothing (this was BUGS_FOUND #1, "Can't edit loan", which also broke Check-in). `LoansPageTest::test_every_form_scoped_lookup_in_the_script_finds_its_element` enforces it on the loans page.
- **The audit log is append-only and written in the same DB transaction as the change.** Call `ActivityLog::record()` *inside* the `DB::transaction` that makes the change, after the change succeeds, so a rollback takes the entry with it and a refused action leaves none. Never update or delete an entry: model `update()`/`save()`/`touch()`/`delete()`/`destroy()` and query `update()`/`delete()`/`increment()`/`upsert()` all throw `LogicException`, in the same spirit as the immutable return logs. A wrong entry is answered by a later one. Only raw `DB::table('activity_logs')` bypasses this, so don't use it.
- **Flash messages** — `success` / `error` via session, rendered by `components/alerts.blade.php`. Login flashes both `welcome` and `success` for legacy view checks.
- **Casing matters** — model statuses are TitleCase (`Borrowed`, `Pending`, `Available`). The `item_requests` migration defaults `status` to lowercase `'pending'`, but `requestActions` compares against `'Pending'`, so rows created straight from the DB default are not processable. Always write `'Pending'` explicitly, as `ItemRequestController::store` does.
- **Style** — Laravel Pint defaults (`vendor/bin/pint`). The admin sidebar layout is `w-64` / `md:ml-64`. Its links are an `@php` array in `components/admin/navbar.blade.php` (add a section there, matched with `routeIs($route, $route.".*")`); the Requests badge count, `$pendingRequests`, comes from a view composer in `AppServiceProvider::boot()` scoped to that partial.