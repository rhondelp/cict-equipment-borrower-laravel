# AGENT_CONTEXT.md

Dense reference for AI agents working in this repo. Read this before touching code.

## Purpose

CICT Equipment Borrower is an internal web app for a college IT department that manages the
lending of departmental equipment. Borrowers (Instructors and Students) submit item requests
from their dashboard; an Admin reviews each request, and approving one deducts stock and
auto-creates a borrow transaction. Admins can also create borrow transactions directly, edit
their status (Borrowed / Overdue / Returned), record return logs with the item condition,
manage the equipment inventory, users and instructor class schedules, and send return-reminder
emails (manually per transaction, or via a daily scheduled job). Everything hangs off keeping
`equipment.available_quantity` truthful as transactions move between states.

## Tech stack

- Laravel 12 (PHP ^8.2), no starter kit — auth is hand-rolled on `Auth::attempt` + the `web` guard
- Blade views (no Livewire/Inertia/Vue); interactivity is vanilla JS + SweetAlert in the views
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

Registration (`/register`) only allows `Instructor` or `Student`; Admins are created via
`/admin/users`, which reuses `AuthenticateUser::register`, so that form is likewise capped at
Instructor/Student — creating an Admin requires a DB/seed/tinker action.

## Data model

All models are plain `Illuminate\Database\Eloquent\Model` with `$fillable`, no casts beyond
`User`, no observers, no soft deletes. FKs are `onDelete('cascade')` unless noted.

| Model | Table | Key fields | Relationships |
|---|---|---|---|
| `User` | `users` | `user_type` (enum Admin/Instructor/Student), `name`, `email` (unique), `password` (hashed cast), `contact_number` | hasMany `borrowTransactions`, `itemRequests`, `notifications`, `classSchedules` |
| `Equipment` | `equipment` (explicit `$table`) | `equipment_name`, `description`, `quantity` (total owned), `available_quantity` (on shelf), `status` (enum Available/Unavailable) | hasMany `borrowTransactions`, `itemRequests` |
| `ItemRequest` | `item_requests` | `user_id`, `equipment_id`, `quantity`, `status` (string: Pending/Approved/Declined), `requested_date`, `remarks` | belongsTo `user`, `equipment` |
| `BorrowTransaction` | `borrow_transactions` | `user_id`, `equipment_id`, `borrow_date`, `return_date` (nullable), `quantity`, `purpose`, `status` (enum Borrowed/Returned/Overdue), `remarks`, `class_schedule_id` (nullable, `onDelete('set null')`) | belongsTo `user`, `equipment`, `classSchedule`; hasOne `returnLog` |
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
- `POST /register` — `AuthenticateUser@register` (`register.store`)

**Admin** (`auth` + `userType:Admin`)
- `GET /admin/dashboard` — `AuthenticateUser@adminView` (`admin.dashboard`); loads equipment, users, transactions, requests, return logs
- `GET /admin/equipment` — `EquipmentController@index` (`admin.equipment`)
- `POST /admin/equipment` — `EquipmentController@store` (`admin.equipment.store`)
- `POST /admin/equipment/update` — `EquipmentController@update` (`admin.equipment.update`); id in body, not URL
- `DELETE /admin/equipment/{id}` — `EquipmentController@destroy` (`admin.equipment.destroy`)
- `GET /admin/users` — `UserController@adminUser` (`admin.users`)
- `POST /admin/users` — `AuthenticateUser@register` (`admin.user.register`)
- `POST /admin/users/update` — `UserController@update` (`admin.users.update`); id in body
- `POST /admin/users/add-sched` — `ClassScheduleController@store` (`admin.add-sched`)
- `DELETE /admin/users/{id}` — `UserController@destroy` (`admin.users.destroy`); blocks self-delete
- `GET /admin/transaction` — `BorrowTransactionController@index` (`admin.transaction`)
- `POST /admin/transaction` — `BorrowTransactionController@store` (`admin.transaction.store`); multi-equipment create
- `POST /admin/transaction/update` — `BorrowTransactionController@update` (`admin.transaction.update`); id in body
- `DELETE /admin/transaction/{id}` — `BorrowTransactionController@destroy` (`admin.transaction.destroy`)
- `POST /admin/transaction/inline-update` — `BorrowTransactionController@inlineUpdate` (`transactions.inlineUpdate`); JSON, status-only edit from the table
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

- **"out"** = status `Borrowed` **or** `Overdue` — units are off the shelf, stock is deducted
- **"in"** = status `Returned` — units are back, stock is restored
- `Borrowed` to `Overdue` (either direction) is a transition *within* "out" — **no stock change**. This is the rule most easily broken; always test `in_array($status, ['Borrowed','Overdue'])`, never `=== 'Borrowed'`.
- After every mutation: `status = available_quantity > 0 ? 'Available' : 'Unavailable'`
- `quantity` (total owned) is **never** touched by transactions — only by `EquipmentController`

This logic lives in `BorrowTransactionController` and `ItemRequestController::requestActions`.
Every write is wrapped in `DB::transaction` with `Equipment::...->lockForUpdate()` to guard
concurrent stock races, and insufficient stock is signalled by throwing
`ValidationException::withMessages(['quantity' => ...])`, caught by the caller and turned into
a redirect-with-errors (HTML) or a 422 JSON body (`inlineUpdate`).

Where it happens:

- `BorrowTransactionController::store` — loops equipment ids; per item locks the row, and if the new status is "out" checks stock then deducts. The whole loop is one DB transaction, so a shortfall on item 3 rolls back items 1 and 2. Empty placeholder values are stripped from `equipment[]` / `quantities[]` before validation.
- `BorrowTransactionController::inlineUpdate` — out to in: `available_quantity += $transaction->quantity` **and** creates a `ReturnLog` (condition defaults to `Good`, `user_id` is the acting admin). in to out: re-check stock, then deduct. out to out: nothing.
- `BorrowTransactionController::update` — the full-edit case, and the trickiest. If `equipment_id` changed, it locks both rows in sorted id order (deadlock avoidance), restores the old quantity to the old item if it was out, and deducts the new quantity from the new item if the new status is out. If the equipment is unchanged it applies the delta: out to in restores `$oldQty`, in to out deducts `$newQty`, out to out adjusts by `$newQty - $oldQty`, in to in does nothing.
- `BorrowTransactionController::destroy` — restores stock only if the deleted transaction was out.
- `ItemRequestController::requestActions`, approve branch — locks the equipment, rejects if short, deducts, flips the request to `Approved`, **and auto-creates a `BorrowTransaction`** (`borrow_date` today, `return_date` today + 7 days, status `Borrowed`, `purpose` falling back to the request remarks). Decline only flips the request status, no stock movement. Both are idempotent: a request whose status is not `Pending` is rejected up front.
- `BorrowTransactionController::sendReturnAlertNotification` — bulk-updates `Borrowed` rows whose `return_date` is past to `Overdue`. Because both are "out", this deliberately performs no stock change. It then emails borrowers whose `return_date` is today, skipping anyone already given a `Return Notice` notification today (checked twice: before sending, and again inside the DB transaction). Invoked by `php artisan notifications:return` (`App\Console\Commands\SendReturnNotifications`), scheduled daily at 08:00 in `bootstrap/app.php`, and reachable manually at `GET /admin/send-return-alerts`.

**Not covered by the invariant:** `EquipmentController::update` lets an admin set
`available_quantity` by hand (validated only as `min:0|lte:quantity`), and
`EquipmentController::destroy` hard-deletes equipment, cascading away its transactions without
any stock reconciliation. Both can leave derived state inconsistent with outstanding loans.

## Conventions

- **No FormRequest classes** — all validation is inline via `$request->validate([...])` in the controller. Follow that; do not introduce `app/Http/Requests` for a one-off change.
- **No service layer, no repositories, no action classes** — business logic lives directly in controllers. `BorrowTransactionController` has one private helper, `safe(callable, string $context)`, which runs a closure and logs any `\Throwable` instead of letting it bubble; callers keep their own response shaping.
- **No policies or gates** — authorization is the `userType` middleware plus ad-hoc checks (`ItemRequestController::assertOwner`, the self-delete guard in `UserController::destroy`).
- **No API routes, no `routes/api.php`** — the two JSON endpoints (`inlineUpdate`, `sendManualEmail`) are web routes returning `response()->json`, called by fetch from Blade with the CSRF token.
- **Update routes take the id in the request body**, not the URL, and use `POST` (except the borrower request `PUT`). Route-model binding is essentially unused: `ItemRequestController::update` declares an `ItemRequest` parameter but overwrites it with `findOrFail($validated['id'])`.
- **Views** live under `resources/views/` in three groups: `admin/` (full pages: dashboard, equipment, transaction, user, request, notification, logs), `borrower/` (dashboard, student), and `components/` for everything reusable — `components/admin/*` (navbar plus per-feature modals), `components/instructor/*` (request modals), `components/ui/*` (`badge`, `page-header`, `table-card`), plus `alerts`, `auth-card`, `default`. `emails/` holds the mail template. Note `NotificationController` returns `admin.notification` (singular) while the route is named `admin.notifications`.
- **Flash messages** — `success` / `error` via session, rendered by `components/alerts.blade.php`. Login flashes both `welcome` and `success` for legacy view checks.
- **Casing matters** — model statuses are TitleCase (`Borrowed`, `Pending`, `Available`). The `item_requests` migration defaults `status` to lowercase `'pending'`, but `requestActions` compares against `'Pending'`, so rows created straight from the DB default are not processable. Always write `'Pending'` explicitly, as `ItemRequestController::store` does.
- **Style** — Laravel Pint defaults (`vendor/bin/pint`). The admin sidebar layout is `w-64` / `md:ml-64`.
