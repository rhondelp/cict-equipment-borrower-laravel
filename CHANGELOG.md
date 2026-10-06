# Changelog

## 2026-10-07

- **A Reports screen: the activity log, filtered, on screen.** `GET /admin/reports` (`admin.reports`, `ReportsController@index`) sits in the Admin group like every other admin page: a borrower gets 403 and a guest goes to sign-in. The sidebar has a new "Records" group with a Reports link that uses the shared link markup, active state and text size, and a small bar-chart icon added to `x-icon`. Exports come next, in Prompt 26.

  **A report is a URL.** The filters are a plain GET form:
  - From and To, defaulting to the last 30 days ending today. To includes its whole day.
  - Activity, a select of All activity and then each group, offering "Everything in <group>" followed by that group's types, labelled from `ActivityLog::TYPES`.
  - Equipment, every item including retired ones, since a retired item's history is often the point.
  - Person, every account, matched as the one who acted **or** the one affected.
  - Status after, the distinct statuses actually present.
  - Search, over details and names.

  Quick links set Last 7 days, Last 30 days, This month or All time, keeping the other filters. The results are built only through `ActivityLog::filter()`, and the filters are read by `ReportsController::resolveFilters()`. The exports will reuse both, so the screen, CSV, PDF and printout cannot disagree about what a report contains.

  **Bad input never fails the page.** An unreadable date, an unknown type or an over-long search is dropped, with an inline message beside its field. A start date after the end date shows "The start date is after the end date, so the report shows the last 30 days instead." in a red `role="alert"` line, and the report runs over the default range rather than returning a 500 or an empty page.

  **The results.** They are newest first, 50 per page, with the query string kept on every page link. A plain pager (First, Previous, "Page 2 of 26", Next, Last) replaces Laravel's links view, which lives in `vendor/` outside Tailwind's content globs and would have shipped unstyled. Above the table:
  - a count line, "Showing 1–50 of 1,284 activities";
  - the applied filters, in words;
  - a summary strip: Activities, Loans recorded ("2 lent · 1 issued for good"), Returns, and Requests decided ("10 approved · 10 declined").

  The strip and the paginator's total come from **one** grouped `COUNT` over the same filtered query, and the total is handed to `paginate()` so the page does not count twice. A test reads the query log and finds exactly one `COUNT` against `activity_logs`. Nothing is cached, and the response is `Cache-Control: no-store`, so a back button never shows a stale report. In the browser check the count rose by one between two loads, because the sign-in had just been logged.

  **A real `<table>`, the one exception to "lists are grids".** It is wide, dense data read across a row, and the same markup must print and become a PDF. AGENT_CONTEXT now records the exception and says not to add a second. The columns are Date and time, Activity (label plus a group badge), Done by (name and role, or "System"), Equipment, Affected person, Qty, Status (from → to) and Details. Cells are `text-base` in near-black, and the header is uppercase `text-sm` on grey.

  The header is `sticky`, and the wrapper is `overflow-auto max-h-[75vh]`. A sticky header inside a wrapper that only scrolls sideways does nothing, so the wrapper scrolls both ways and the header sticks inside it.

  The group badges use `x-ui.badge`, which gains a `primary` tone: blue for Loans, amber for Requests, green for Returns, neutral for the rest. Green and red would have read as good or bad news.

  **Fitted after looking at it.** The first build squeezed every column but Details:
  - names broke onto three lines;
  - at 1280px, Status was clipped and Details was off-screen.

  Now each column has a minimum width, the date stacks over the time, the status stacks "Borrowed →" over **Returned**, and cell padding is slightly tighter. Eight columns of 16px text still need about 74rem against the roughly 61rem a 1280px laptop leaves. Squeezing Details to around 20 characters a line was worse, so the table still scrolls about 200px sideways there. A line, "More columns to the right — scroll the table sideways.", shows only while columns are actually off-screen, and hides once the last one is reached. It nearly fits at 1440px.

  **Empty states.** With nothing logged at all: "No activity recorded yet. Activity appears here as soon as equipment is lent, returned or changed." With filters that match nothing: "No activity matches these filters.", every applied filter echoed as a chip, and Clear filters.

  **Verification.** `php artisan test`: **465 passed**, up from 453. The 12 new tests are in `tests/Feature/ReportsPageTest.php`:
  - access: admin, borrower 403, guest redirected, the active sidebar link, `no-store`;
  - the default range is exactly Sep 8 to Oct 7, with 23:59:59 the day before excluded;
  - an inclusive end date, at both edges;
  - an inverted range and unreadable inputs fall back, with their messages;
  - type by key and by each group, "Account & system" included;
  - equipment, with retired items offered;
  - person as actor and as subject;
  - status, with the options drawn from the data;
  - search over details and names, and filters combined;
  - 55 rows paginate 50 + 5, with the query string on the next link;
  - the one grouped count and the strip's figures, filtered and unfiltered;
  - the table's columns in order, the sticky header, `text-base` cells, the stacked date and status, the group badge and "System";
  - both empty states.

  In headless Chrome at 1280px and 390px, against the scratch SQLite database with 60 sample entries:
  - no page-level horizontal overflow; the table box scrolls both ways;
  - 16px cells in `rgb(15, 23, 42)`;
  - the header still at the top of the box after scrolling it 600px;
  - the hint shown while columns are hidden;
  - the Loans filter applied through the real form, giving a bookmarkable URL with 30 results;
  - the bad-range message and the no-match state rendered;
  - no JS errors.

  `npm run build` was run, and the arbitrary utilities were confirmed in the built CSS. `vendor/bin/pint --test`: no new issues over the 9-file baseline.

- **Maintenance and repair records, logged per item from the inventory list.** The reports need to show when an item was serviced or fixed, and nothing in the system recorded that. This adds the smallest thing that does: a record, with no new state.

  **The entry is the record.** `POST /admin/equipment/{id}/maintenance` (`admin.equipment.maintenance`, `EquipmentController@logMaintenance`, Admin only) writes one activity log entry, `maintenance_logged` or `repair_logged`, and nothing else. There is no table, no "in repair" status, and no change to stock, availability or `status`. An item can be serviced while units are lent out, and the shelf count is a fact about the loans, not about upkeep. The entry carries:
  - the item;
  - `quantity` null;
  - the summary as `details`, which is the line the report shows;
  - `performed_on`, `notes` and `cost` in `meta`, with the cost rounded to centavos.

  `occurred_at` is the day the work was done, at the current time of day. A repair entered on Thursday for Monday therefore sorts onto Monday in a date-filtered report.

  **Validation and refusals:**
  - `kind` is `maintenance` or `repair`;
  - `performed_on` defaults to today and may not be in the future;
  - `summary` is required, at most 200 characters;
  - `notes` is optional, at most 1000;
  - `cost` is optional, numeric and not negative.

  A retired item is refused with "Restore it first", the same rule as lending. A borrower gets 403 from the Admin group. A refusal writes nothing, as every refused action does since this morning's audit-trail change.

  **The screen.** Each inventory row gains a third icon button, a wrench, labelled "Log maintenance / repair for <item>". It opens a modal built like the others: `div[data-modal]` with the header outside the form, so the item's name is set in the header and survives the form being reset. Inside:
  - two radio cards, Maintenance ("Routine care: cleaning, updates, a check-up") and Repair ("Something was broken and has been fixed");
  - "What was done";
  - a date that defaults to today, with today as the latest choice;
  - an optional cost in ₱ and optional notes.

  The text is a size larger than the other admin modals, as asked: a 22px title, and nothing smaller than 16px. On a retired row the button is disabled and its tooltip says why. The actions column widened from 6rem to 9rem for the third button. `EquipmentPageTest` counted exactly two buttons per row, and now counts three. Its real rule, that no row action is red at rest, still holds for the new one.

  **Found on the way: a stale route cache.** `bootstrap/cache/routes-v7.php`, written by the `php artisan optimize` of 27 Sept, made Laravel ignore `routes/web.php`, so the new route 404'd in tests and would have in the browser. Comparing it with `routes/web.php` showed this was the only route it was missing, so nothing earlier was affected. It was cleared with `php artisan route:clear`. It is gitignored and can be rebuilt with `route:cache`, though during development it is better left off. AGENT_CONTEXT now says to check `bootstrap/cache/` first when a new route 404s.

  **Verification.** `php artisan test`: **453 passed**, up from 446. The 7 new tests are in `tests/Feature/MaintenanceLogTest.php`:
  - maintenance saves as `maintenance_logged`, dated today at the current time, with the actor, the item, null quantity and the meta;
  - a repair for three days ago saves as `repair_logged` on that day, with notes and a cost of 1250.5;
  - a future date, an empty summary, a 201-character summary, an unknown kind and a negative cost are each rejected, with nothing written;
  - a retired item is refused;
  - a borrower gets 403;
  - two records leave `quantity`, `available_quantity`, `status`, `retired_at` and even `updated_at` unchanged, with no status-flip entry;
  - the list renders the button and route per row and disables it on a retired row, the modal keeps the item name in its header outside the form, and the date picker's maximum is today.

  In headless Chrome at 1280px and 390px, against a scratch SQLite database signed in as the seeded admin:
  - no horizontal overflow, and the three row buttons fit on one line inside the row;
  - the modal opened with the right item and form target, today's date and max, a 22px title and no text under 16px, within the viewport;
  - submitting a repair with a cost through the real form showed "Repair logged for Document Camera on Oct 7. Stock is unchanged." and stored the entry;
  - no JS errors.

  `npm run build` was run. `vendor/bin/pint --test`: no new issues over the 9-file baseline.

- **Every action that changes data or access now writes its audit entry.** The activity log that was built empty this morning is now filled as things happen:
  - loans, requests, equipment, users and class schedules;
  - return outcomes and corrections;
  - sign-ins and password resets.

  No action does or returns anything different: the same redirects, flashes and JSON. Reads and page views are not logged. AGENT_CONTEXT has a new "Audit trail" section, with a table of which action writes which type and the rule for new code.

  **Inside the transaction, after the change.** Where an action already had a `DB::transaction`, `record()` is called inside it, after the change succeeds, so the entry and the change commit or roll back together. That covers new loan, edit, check-in, void, approval, equipment edit and the sweep. Where there was none, the entry is written right after the successful save. Hard deletes of a loan, item or account now wrap the delete and its entry in one transaction.

  **Refusals write nothing.** None of these leaves an entry:
  - a validation error or a short shelf;
  - a void without a reason, a decline without one;
  - a check-in of a returned loan, suspending yourself, lifting a suspension that isn't there.

  The one exception is a failed sign-in, which is the point of logging it. A refused sign-in to a **deactivated** account counts as a failed one too. **A save that changed nothing is not an event either.** That covers a loan or request edit submitted unchanged, a restore of an item that was never retired, and a reactivation of an active account.

  **What each entry carries.** Each entry has the actor, and where they apply: the item, the person affected, the loan, the quantity and the status from/to. It also has a one-line `details` sentence, such as "Checked in 2 × Projector (Epson) from Mia Santos in Good condition, 1 day late.", "Declined: None left this week", or "Role changed Student to Instructor: Teaches IT 101". An edit stores each changed field old → new in `meta.changes`, by name rather than id. A whole number posted as text is stored as a number, so meta reads 2 → 3, not 2 → "3". **No password, token or reset link is ever written.** An admin setting a password records only `meta.password_changed`. A failed sign-in names the address in `details` and nothing else. A test searches every column of every entry for the passwords and token it used and finds none.

  **Who acted.** The signed-in user, by default. A failed sign-in and a reset request have nobody signed in, so their actor reads "Not signed in" rather than the "System" a null actor would show. The overdue sweep is the system **even when an admin runs it from the screen**, as the prompt asked. A public sign-up is its own actor. So is a completed password reset: whoever held the link set the password.

  **The availability flip is logged centrally.** `Equipment::reserveStock()` and `releaseStock()` now take the loan as an optional last argument. They write `equipment_status_changed` only when the stored status really moves between Available and Unavailable, and no controller writes it. To give that entry its loan, the New loan form and request approval now **create the loan row before reserving stock**. Both run inside one transaction, so a short shelf still rolls the row back; a test proves it with a two-item hand-over whose second line is short.

  **Choices that needed a decision:**
  - **Non-returnable lines are `loan_issued`, not `loan_created`.** The type exists, and the backfill already writes it.
  - **Approval writes two entries:** `request_approved`, pointing at the loan it made, and that loan's own `loan_created`/`loan_issued`. Without the second, a "loans recorded" report would miss every loan that started as a request, and live history would disagree with the backfill.
  - **A user edit that changes the role writes `user_role_changed`, and `user_updated` only if other fields changed too.** That avoids an empty "edited" entry beside every role change. Confirming an instructor is `instructor_confirmed` alone.
  - **The sweep's bulk `update()` became read-then-flip-by-id**, inside a transaction with the rows locked. The prompt asks for one `loan_marked_overdue` per loan, and a bulk update cannot say which rows it touched. Each flip repeats the `Borrowed` check, so a loan checked in between the read and the write is neither flipped nor logged.
  - **Deletes are recorded after the row is gone, with its key null and its id in meta.** A key to a deleted row would fail the foreign key, and recording before the delete would claim a deletion that might not happen.
  - **The custom message is a `loan_reminder_sent`.** The prompt puts `sendManualEmail` under that type, and a custom message is sent the same way, with `meta.notification_type` saying which it was. **This reverses one Prompt 22 decision:** the backfill now also counts `Message from Admin`, so old and new history agree, and `ActivityLogTest` expects it.

  **Verification.** `php artisan test`: **446 passed**, up from 435. All existing tests pass unchanged; Prompt 22's backfill test was updated for the reminder change above. The 11 new tests in `tests/Feature/ActivityLoggingTest.php` cover:
  - one test per group, driving every route and asserting the type, actor, item, subject, loan, quantity and statuses of exactly one entry each:
    - Loans: create, issue, edit, unchanged re-edit, reminder, check-in, void, delete;
    - Requests: submit, change, withdraw, approve (both entries), decline;
    - Equipment: add, edit, retire, restore, delete, with all five entries surviving the delete by name;
    - Users: admin-created, edited with a role change, suspended, lifted, deactivated, reactivated, instructor confirmed and declined, schedule added, edited and removed, deleted, public sign-up;
    - Returns: resolve, note;
    - Account: failed, successful and deactivated sign-in, sign-out, reset requested and completed;
  - the sweep as system: two overdue entries and one reminder, then a second run adding nothing;
  - six refused actions writing nothing;
  - a rolled-back two-item loan and a hand-rolled failed transaction leaving nothing;
  - the status flip appearing only on the lend that empties the shelf and the check-in that refills it, with the loan's id.

  `vendor/bin/pint --test`: no new issues over the 9-file baseline. No front-end changes, so no build.

- **An activity log for the reports to read from: table, model and backfill, with nothing visible yet.** The reports need one place that answers "what happened to this item", "what did this person do" and "who approved that". Until now that history was spread across loans, return logs, requests, user columns and notifications, and some of it was never kept. This change is the foundation only. No controller, view or route changed, and nothing writes to the log live yet.

  **The table, `activity_logs`.** Each row records:
  - `occurred_at`: when it happened. This is not `created_at`, because a backfilled row is written today about last month;
  - `type`: a stable key;
  - the actor, the person affected (`subject_*`) and the item, each stored twice: as a foreign key and as a name snapshot;
  - the loan, a quantity, `status_from` / `status_to` labels, a `details` sentence, `meta` json and the IP;
  - `source` (`live` or `backfill`) and a unique, nullable `backfill_key`.

  Every foreign key is `nullOnDelete` and none cascades. Deleting an account or an item must not empty the history it is part of, and the snapshot keeps the entry readable once the key is null. A test deletes the actor, the borrower and the item and still reads all three names.

  **The model, `ActivityLog`.** `TYPES` holds 42 keys, each with a label and a group: the 39 the prompt listed, plus the 3 conditional ones that apply. The groups run Loans, Requests, Equipment, Users, Returns, Account & system. `loan_issued` is included because loan types are in. `schedule_updated` and `schedule_deleted` are included because the schedule update and delete routes exist. `record($type, $attrs)` is the one way to write:
  - an unknown type is refused;
  - the actor defaults to the signed-in user, and `'actor' => null` means the system;
  - names are snapshotted from the models passed in, and a loan supplies its own item and borrower;
  - any column passed explicitly wins, which is how the backfill sets the real time and "Not recorded";
  - a console run records no IP, because its synthetic request always claims 127.0.0.1.

  `filter()` takes `from`, `to`, `type`, `equipment_id`, `user_id`, `status` and `q`:
  - `to` covers the whole end day in the app timezone;
  - `type` takes a key or a group name;
  - `user_id` matches the person as actor or as subject;
  - an unreadable date is ignored rather than throwing.

  **Append-only, like the return logs.** `updating` and `deleting` hooks throw `LogicException`. So does a custom query builder, because `ActivityLog::where(...)->delete()` never loads a model and never fires a hook. A test tries eight paths and the row is unchanged after all of them. The rule for whoever wires in live logging is in AGENT_CONTEXT: call `record()` inside the same `DB::transaction` as the change, so a rollback takes the entry with it.

  **`php artisan activity:backfill` rebuilds what the old tables already knew.** It reads loans (as `loan_created`, or `loan_issued` for an Issued row), return logs, voids, requests and their decisions, retirements, deactivations, suspensions, role records, incident resolutions, correction notes and reminders. It uses `chunkById`, takes `occurred_at` from the real timestamps, and prints a count per type. Each entry's key names its source row, for example `loan_checked_in:return_logs:12`, so a second run adds nothing. Keys for state that can be set again (suspension, deactivation, role, retirement) also carry the timestamp: a new suspension after a lift becomes a new entry and the old one stays.

  **Choices that needed a decision:**
  - **Reminders.** The prompt named `Return Notice`. That is only the nightly sweep's type. A manual reminder is stored as `Return Reminder` or `Overdue notice`, so all three count as `loan_reminder_sent`. `Message from Admin` is a custom message, not a reminder, and is left out.
  - **Role records.** `role_overridden_*` is written by three different actions, so the reason and the timing decide. "Confirmed instructor request from sign-up" becomes `instructor_confirmed`. An Admin whose record is stamped within a minute of the account's creation is a staff account created with a reason, `user_created`. Anything else is `user_role_changed`.
  - **Old decisions.** A decision made before `decided_at` existed takes its time from `updated_at`, the fallback `ItemRequest::decisionLine()` already uses, and `meta.occurred_at_from` says so. Request statuses are matched in any case, so a legacy lowercase `approved` counts.
  - **Who acted.** Where no one stored who acted, the actor is "Not recorded" rather than a guess. That covers loan creation, voids, deactivation, retirement and reminders. A borrower is the actor of their own request.
  - **The live boundary.** For each type the backfill stops at the first `live` entry, so once live logging is wired in the same event cannot be written once from each side.

  **Not recoverable, and not invented.** The users and equipment rows keep only their current state. A lifted suspension, a reactivation, a restore, a declined instructor request and every role change but the latest left no trace. Names, and the actor's role, are snapshotted as they are today. **Not backfilled, though possible:** `equipment_added` and `user_created` for every row from its `created_at`. The prompt did not list them, and a seeded database would report every item as added in the same second.

  **Verification.** `php artisan test`: **435 passed**, up from 420. The 15 new tests are in `tests/Feature/ActivityLogTest`:
  - `record()` snapshots, an explicit system actor, explicit columns winning, an unknown type refused;
  - eight update and delete paths refused;
  - deleting the actor, borrower and item keeping the names;
  - the type list and group order;
  - the date filter at 23:59:59 and 00:00:00 on either edge;
  - type key and group;
  - equipment, user as actor and as subject, status, `q` on each of the four columns, and filters combined;
  - `newestFirst()` ties broken by id;
  - the backfill: 22 entries from a seeded history with known timestamps, a second run adding nothing, a new suspension added beside the old one, and the stop at the live boundary.

  On a **copy** of the dev database, served by a separate MariaDB on port 3307 from the scratchpad:
  - `php artisan migrate` ran the five pending migrations, the 2 Oct and 5 Oct ones and this one;
  - `activity:backfill` added 11 entries: 5 loans, 2 check-ins, 1 request and its approval, 1 instructor confirmation, 1 resolution;
  - the copy's one notification is a custom message and was correctly skipped;
  - a second run reported "No new entries";
  - on MariaDB, `filter()` returned the expected counts and both a query delete and a model update were refused;
  - `ñ` in a name was stored as UTF-8.

  The real data directory was not touched, and its `multi-master.info` problem from 5 Oct is still there. `vendor/bin/pint --test`: no new issues over the 9-file baseline. No front-end changes, so no build.

- **Loan types reach requests, the borrower's side, reminders and the overdue rule.** Until now only the admin's New loan form knew about loan types. Approving a borrower's request still created a seven-day date-only loan whatever the item was, and every overdue count judged loans by day.

  **One overdue rule, in SQL as well as PHP.** `BorrowTransaction` gains two query scopes:
  - `out()`: not voided and `Borrowed`/`Overdue`, so never Issued;
  - `overdue(?now)`: out, and either timed with `return_date` before now, or date-only with `return_date` before the start of today. That is `dueAt()` written as a query.

  `overdueCaseSql()` gives the same rule for a `SUM(CASE …)`. A test builds seven loans (dated, timed, returned, voided and issued, either side of the line) and asserts that the scope and `isOverdue()` pick the same rows. Every aggregate that counted overdue loans by date alone now uses the scopes:
  - the borrowing block in `ItemRequestController::store`;
  - the nightly sweep;
  - the users screen's `overdue_count` and `units_out`;
  - the request queue's per-borrower standing;
  - the admin dashboard's units-out sum.

  The dashboard's open loans are ordered by `dueAt()`, its worst overdue loan is the one whose due moment passed first, and "Longest: …" uses that loan's own label. A loan three hours late used to read "0 days" and rank last.

  **Approval builds the loan from the item's type.** The equipment row is read under the same lock as before, and the idempotency check and `ValidationException` handling are unchanged:
  - **Returnable:** as before, today to today + `loan_days`, `timed` false.
  - **Time-Limited:** out now, due now + `config('office.time_limited_minutes')`, `timed` true. The new config value defaults to 60 and comes from `OFFICE_TIME_LIMITED_MINUTES`. The flash reads "…due 11:05 AM."
  - **Non-Returnable:** `Issued`, `return_date` null, `timed` false. The flash reads "…issued to Mia Santos, not expected back."

  Stock for an issue comes off with the same `reserveStock()` as a loan, because `available_quantity` is already quantity − out − issued. What makes it an issue is the row: `unitsIssued()` counts it, `unitsOut()` never does, and a check-in never releases it. A test asserts that the stored and derived availability are equal after approving one. **The clock for a time-limited request starts at approval**, which is when the item is handed over. If approvals happen before the borrower reaches the counter, the hour is already running when they collect.

  **The overdue block.** A one-hour loan that is ten minutes late now blocks new requests. Before, it only blocked from the next day. A timed loan inside its time does not block, and an Issued item never does. The refusal names the due time: "Clicker, due Oct 5, 1:00 PM".

  **Reminders and the nightly sweep.** The sweep flips `Borrowed` to `Overdue` with the `overdue()` scope, so a timed loan due at 07:00 is marked at the 08:00 run. An Issued row cannot match. "Due today" reminders go only to loans that are out and still `Borrowed`, and a timed one names its time: "due back on Oct 5 at 9:00 AM". `sendManualEmail` now refuses the canned reminder for an issue with a 422, and a custom message still sends. `notifications:return` prints the controller's summary instead of a fixed line.

  **Not done, deliberately: the sweep still runs once a day.** No hourly scheduler was added. A timed loan that falls due after 08:00 is stored as `Overdue` only at the next morning's run, and gets no "due today" reminder if it was created after that run. This affects only the stored enum and the mails. Every screen, the dashboards and the borrowing block read overdue from `dueAt()` when they render or check, so a late one-hour loan shows as overdue and blocks requests within the minute.

  **Borrower side.**
  - **Request form:** under the list, the chosen item's terms appear in a note with an icon: "Return by a date", "Return within 1 hour" (worded from the config, so 90 reads "90 minutes"), or "Given to you — no return needed". The two non-returnable types also say it on their own row, so it is read before choosing. The change-request dialog shows the same note.
  - **Dashboard:** timed loans already read "due in 39 min" and "Booked out Oct 5, 5:59 PM → 6:59 PM" through the model. An issue never appears on the agenda, because there is nothing to do. It is listed in earlier activity as "Issued to you — no return needed", with its slip and a grey dot. "Units held" counts only loans that are out. The due-soon line now says "by the date or time on each card".
  - **Slip:** a timed loan's slip shows "September 28, 2026 at 9:30 AM" and a "Due back" line with the time. An issue gets an **Issue Slip** with "Issued on", "Not expected back — given out for good" in place of a return line, a grey Issued badge, and a footer asking the borrower to keep the slip. The footer's "Issued <date>" stamp, which meant when it was printed, now says "Printed".
  - **Admin dashboard activity:** issues are tagged "Issued … issued to".

  **One claim corrected on the way.** The request form's ready hint said "Reviewed within one working day". Nothing in the system sets a review time, the same false promise the landing page had removed. It now reads "Nothing is held until an admin approves it", and a test asserts the phrase is gone. **Not fixed: the sign-in page (`login.blade.php`, line 60) still says "Requests are reviewed within one working day".** It is outside this prompt.

  **Verification.** `php artisan test`: **420 passed**, up from 404.

  `BorrowingRulesTest` gains 11 tests:
  - approving each type produces the right loan, stock and flash, with the time-limited period read from config;
  - approval still refuses a short shelf for the new types;
  - a late timed loan blocks requests, one inside its time does not, and an issue never does;
  - the sweep, run through `artisan notifications:return` at 08:00, marks overdue by due moment, leaves the issue alone, and sends exactly two reminders, the timed one naming its time;
  - a manual reminder for an issue is refused while a custom message sends;
  - the dashboard, users screen and request queue count a late timed loan and ignore an issue;
  - the scope matches `isOverdue()` row by row.

  `BorrowerDashboardTest` gains 5 tests:
  - the dashboard with all three types;
  - the request form's per-type notes and the absence of a review-time promise;
  - the time-limited wording following config;
  - the timed and issue slips;
  - the change-request dialog's note.

  In headless Chrome at 1280px and 390px against a scratch SQLite database, signed in as a borrower holding a one-hour loan and an issue:
  - the agenda showed only the loan, "due in 39 min" with its times;
  - earlier activity showed the issue with no due line;
  - the request dialog's note was hidden until an item was picked, then read correctly for each type;
  - there was no overflow and there were no JS errors.

  `npm run build` was run. `vendor/bin/pint --test`: no new issues over the 9-file baseline.

- **The admin loan forms now follow each item's loan type: time-limited items get a date-and-time picker, and non-returnable items are recorded as Issued with no due date.**

  **New loan.** Each item in the picker now carries its loan type, and the two non-default types are labelled ("Time-Limited · due back at a set time", "Non-Returnable · given out for good"). The date fields follow what is ticked:
  - **Only returnable items:** two dates, exactly as before. The default due date now comes from `config('office.loan_days')` instead of a literal 7.
  - **Any time-limited item:** both fields become date-and-time pickers. The borrow moment starts at now and stays editable, and the due time starts an hour later. Three plain buttons, **+30 min**, **+1 hour** and **+2 hours**, fill the due field counted from the borrow moment. When returnable items are ticked alongside, a line explains that they are due by the end of that day.
  - **Only non-returnable items:** the due field disappears. It is also disabled, so a hidden required field cannot block the submit. A line says the items are given out for good and the issue is still recorded.

  The preview reads "out for 1 hr 30 min" or "issued, not expected back", and the hint says "Recorded as issued the moment you save".

  **The server decides, per item, from the equipment row.** `store()` now locks every selected row up front, in id order, then a new `handoverMoments()` checks the dates against the locked rows' loan types:
  - a due date is required when anything is coming back, and its error names the items that need it;
  - anything time-limited needs a time on both fields and a due moment strictly after the borrow moment;
  - returnable items are due on or after the borrow day;
  - a due date sent with only non-returnable items is ignored.

  Each item is then written by its own type:
  - **returnable:** `Borrowed` at `00:00:00`, `timed` false; in a mixed booking it takes the date part of the shared datetime;
  - **time-limited:** `Borrowed` at the exact moments, `timed` true;
  - **non-returnable:** `Issued`, with no `return_date` and `timed` false.

  `status` and `timed` are never read from the request, and a test posts both to prove it. Every type comes off the shelf with `reserveStock()`, so for an issue the stored `available_quantity` and `derivedAvailableQuantity()` agree. It is still one transaction with `ValidationException` handling, so a refusal on any item writes nothing. The flash says what happened: "Loan recorded — 1 unit out, due back 11:30 AM.", "…; 3 units issued, not expected back.", or "Issue recorded — 5 units issued, not expected back."

  **Edit loan.** A loan keeps the kind it was made as. A timed loan's fields become date-and-time pickers, with a line reading "Time-limited loan · due back at an exact time". The server's new `editedMoments()` requires a time on both and a due moment after the borrow moment. A date-only loan stores date parts, even if a time is posted. The equipment select disables items of another loan type, and the server refuses such a move with an error naming both types: it would give the loan terms it was never made under. **Issued records are void-only**, the simpler of the two options. A remarks-only edit would have needed a second mode in this dialog. The Edit button is not drawn for them, and `update()` refuses them anyway.

  **Check-in, void, delete.** `checkIn` refuses an Issued row ("issued, not lent, so nothing is due back"). Its "Logged as … late" note now comes from `timingLabel()`, so a timed loan reads "10 min late", and a date-only one reads "1 day late" where it used to say "1 days late". `void` restores stock for open loans **and** Issued rows, which also drops them out of `unitsIssued()`. **`destroy` now refuses an un-voided Issued row.** Your prompt did not list this, but deleting one would silently drop it from `unitsIssued()` and leave the shelf short with nothing to explain why. Once voided, it can be deleted as before.

  **Loans list.**
  - Issued rows show a neutral **Issued** status and read "Issued Oct 5 · Not expected back". They get their own **Issued** chip when any exist, and sit with the settled records.
  - Issued rows have **no** Check-in, Edit or Email button. Email was dropped because the only template is a return reminder for something nothing is due back on.
  - Timed loans show times in the date line ("Oct 5, 8:00 AM → 9:30 AM · 30 min late").
  - The queue order in the controller and the urgency and due-date sort keys use `dueAt()`, so a loan due at 2 PM outranks one due "today".
  - The overdue figure reads "Longest: 30 min late" from the worst loan's own label instead of "Longest: 0 days late".
  - The check-in dialog's due line and the reminder email include the time for a timed loan.

  **Not changed, and worth knowing.** Approving a borrower's request (`ItemRequestController::requestActions`) still creates an ordinary date-only loan whatever the item's type. The nightly sweep and the borrowing block still compare dates, so they see a timed loan as overdue from the next day. The borrower screens render Issued and timed rows without errors, which a test covers, but were not reworded for them.

  **Verification.** `php artisan test`: **404 passed**, up from 381. The new `LoanTypesLoansTest` has 22 tests:
  - each type recorded with its stored values;
  - a non-returnable item with no due date ending up Issued, counted by `unitsIssued()` and not `unitsOut()`, with stored and derived availability both reduced;
  - a sent due date ignored for an issue;
  - a client-sent status deciding nothing;
  - a returnable item without a date refused;
  - a time-limited item without a time refused, on either field;
  - a due time not after the borrow moment refused;
  - mixed returnable + time-limited sharing one datetime;
  - a mixed selection still needing a time;
  - returnable + non-returnable refused as a whole without a date, then recorded with one;
  - check-in of an issue refused;
  - voiding an issue and a timed loan restoring stock;
  - an issue undeletable until voided;
  - an issue void-only;
  - a timed loan staying timed when edited;
  - a date-only loan staying date-only;
  - a move to another loan type refused;
  - the list's Issued row, timed row, order and overdue figure;
  - the form's per-item types and presets;
  - six other screens still rendering with Issued and timed rows.

  `LoansPageTest` gains a test that every literal `getElementById` and every `#modal [data-…]` lookup in the page script exists. The existing form-scoped lookup test now also covers the new `loanForm` and `editForm` lookups.

  In headless Chrome at 1280px and 390px against a scratch SQLite database:
  - ticking a returnable item kept dates;
  - adding a time-limited one switched both fields to date-and-time (now → +1 hour), showed the presets and the mixed note, and +2 hours set the due field to two hours after the borrow moment;
  - unticking it went back to dates with the 7-day default;
  - a non-returnable item alone hid and disabled the due field;
  - an issue and a +30 min timed loan were saved through the UI ("Loan recorded — 1 unit out, due back 6:32 PM.");
  - the list showed the Issued row without check-in or edit, and an "Issued 1" chip;
  - editing the timed loan opened date-and-time pickers with its values, offered only time-limited items, and read "out for 30 min";
  - no JS errors, and no overflow at 390px.

  The first run showed an issue row saying "Issued" three times, so its second line now reads "Not expected back". `npm run build` was run. `vendor/bin/pint --test`: no new issues over the 9-file baseline.

- **Admins can set an item's loan type, and the inventory list shows and filters by it.** The add/edit equipment dialog has a new **Loan type** control, placed directly under the name. It is three radio cards rather than a select, so the plain-language line under each option is read before choosing:
  - **Returnable:** "Comes back by a date".
  - **Time-Limited:** "Comes back by an exact time, such as within 1 hour".
  - **Non-Returnable:** "Given out and not expected back; the issue is still recorded".

  Titles are 16px semibold and hints are 14px `neutral-700`, in line with the other fields. Each card carries an icon (`fa-rotate-left`, `fa-clock`, `fa-box-open`), the selected card is outlined and tinted, and each radio is described by its hint for screen readers. Returnable is checked for a new item; editing prefills the item's own type.

  **The type cannot change under a loan already out.** That loan was made under the old type's terms, and switching would leave it due by a time it was never given, or turn it into a hand-over nobody recorded. `EquipmentController::update` refuses the change while `unitsOut() > 0`, under the same row lock as the rest of the edit, with an error that names the units, the old type and the new one. The whole post is refused, so a rename sent with it is not half-applied. Other edits to such an item still work when the type is unchanged, and a type change is allowed once everything is returned or voided. In the dialog, the other two cards are disabled and a line says "2 units are out on loan, so the loan type is locked until they are checked in."

  **Validation.** `loan_type` is `required|in:returnable,time_limited,non_returnable` on both routes. A store without the field saves Returnable, which is what every item was before. An update without it **keeps the item's current type** rather than defaulting, because defaulting there would quietly turn a Time-Limited item back into Returnable from any older form. Every existing rule is unchanged:
  - no status or available-quantity input;
  - the total cannot drop below units out, or below out + issued;
  - availability is recomputed under the row lock as total − out − issued.

  **The list.** Each row shows its type as a small neutral badge next to the item name, and the label is searchable. It is neutral on purpose, because colour on this screen marks a stock problem and a loan type is not one. Three chips with counts, **Returnable / Time-Limited / Non-Returnable**, join the existing chip row after a divider. They do not get a group with its own "All", because `resources/js/ui.js` keeps one active chip per list, and two "All" buttons would both light up for one state. Each row's `data-chip` gains its type key, so no new script was needed, and `?filter=time_limited` works like the other chips. A type no item uses yet is shown but disabled, since a chip that can only empty the list is a dead control. The loan screens are untouched.

  **Verification.** `php artisan test`: **381 passed**, up from 370. The new `EquipmentLoanTypeTest` has 11 tests:
  - the default type when the field is absent;
  - each type saving on create and on edit;
  - an unknown, blank, wrongly cased or hyphenated type refused;
  - an edit without the field keeping the current type;
  - a change refused while 2 units are out, with the message and nothing else saved;
  - a quantity edit still working while units are out;
  - a change allowed once loans are returned or voided;
  - a borrower getting 403;
  - every row's badge, icon and chip key;
  - the three chips with counts, a single "All", and an unused type disabled;
  - the three radio cards with their hints, Returnable checked by default, and the edit trigger carrying the type.

  In headless Chrome at 1280px and 390px against a scratch SQLite database:
  - the Time-Limited chip filtered to its one row ("Showing 1 of 8 items") and Returnable to seven;
  - editing the Time-Limited item prefilled it, unlocked;
  - editing an item with 2 units out kept Returnable checked, disabled the other two and showed the lock line;
  - Add defaulted to Returnable, and saving a new item as Non-Returnable put its badge in the list and enabled that chip;
  - there was no horizontal overflow and no page errors. On a phone the chips wrap and the cards stack.

  The first screenshot showed the hand icon reading as a smudge at chip size, so it became `fa-box-open`. `npm run build` was run, and every new utility (`has-[:disabled]:*`, `disabled:*`, `!px-2`, `!py-0.5`) is in the compiled CSS. `vendor/bin/pint --test`: no new issues over the 9-file baseline.

- **Foundation for loan types: time-limited loans, non-returnable items, and an `Issued` status.** This is schema and model logic only. No screen, form or route offers any of it yet, and every existing screen reads exactly as before. Three migrations:
  - `2026_10_05_120000_add_loan_type_to_equipment` adds `equipment.loan_type` (string 20, indexed, default `returnable`), after `category`. `category` is untouched: it is free-text grouping and says nothing about whether an item comes back.
  - `2026_10_05_120100_add_times_to_borrow_transactions` turns `borrow_date` and `return_date` into DATETIME (`return_date` stays nullable) and adds `timed`, a boolean defaulting to false. MySQL keeps every existing date at `00:00:00`.
  - `2026_10_05_120200_add_issued_to_borrow_transaction_status` widens the status enum to Borrowed, Returned, Overdue, Issued, using `->change()`, which needs no raw SQL on MariaDB 10.4 or SQLite.

  **A rollback refuses rather than truncates.** Going back to DATE would discard the due time of every timed loan, and shrinking the enum would reject or blank every Issued row. Neither has an honest fallback, so each `down()` counts the affected rows and throws with the number if there are any. With none, it rolls back cleanly.

  **Two kinds of due date, one place that tells them apart.** `BorrowTransaction::dueAt()` returns `return_date` for a timed loan and `return_date->endOfDay()` for a date-only one. `isOverdue()` is now `isOut() && dueAt() < now()`. For a date-only loan that is the same rule as before, "overdue from the day after it is due", only stated as a moment. The casts moved from `date` to `datetime`, and `timed` is cast to boolean and fillable.

  **Issued is not out.** A non-returnable hand-over has nothing coming back, so `isIssued()` is true for a non-voided `Issued` row, `isOut()` is unchanged and excludes it, and it can never be overdue. `derivedStatus()` checks Void, then Issued, then Returned, then Overdue/Out, and `statusTone('Issued')` is `neutral`. The units still leave the shelf. `Equipment::unitsIssued()` sums non-voided Issued quantities, `derivedAvailableQuantity()` is `quantity − unitsOut() − unitsIssued()`, and voiding the row puts them back in that figure. `quantity` keeps counting issued units as owned.

  **The one controller change.** `EquipmentController::update` recomputes `available_quantity` on every edit, and it now subtracts issued units too. Without that, the next edit of an item would quietly put issued units back on the shelf. It also refuses a total below out + issued; the existing "still out on loan" message is unchanged, and a second message names the issued units. The index query gains a `units_issued` `withSum` next to `units_out`, read by `Equipment::issuedNow()`. Both changes are no-ops while no Issued rows exist, which is the case today.

  **Equipment loan types.** Three constants, `LOAN_RETURNABLE`, `LOAN_TIME_LIMITED` and `LOAN_NON_RETURNABLE`, plus `LOAN_TYPES` mapping each to its label: Returnable, Time-Limited, Non-Returnable. There are `isReturnable()`, `isTimeLimited()`, `isNonReturnable()` and `loanTypeLabel()`, and `loan_type` is fillable. The model's `$attributes` default matches the column's, so an unsaved item answers Returnable too.

  **Labels.** Date-only loans render exactly as before. Timed loans carry times: "Oct 5, 2:30 PM → 3:30 PM", or both dates when the loan crosses midnight. Their timing reads "due in 25 min", "due at 3:30 PM", "due tomorrow, 9:00 AM", "10 min late", "2 hr late", then days. A returned timed loan is judged by the return log's `created_at`, because `return_logs.return_date` is still a DATE column and would make every same-day late return look on time. Issued rows read "Issued Oct 5" and "issued". Their `dateLine()` is just "Issued Oct 5", since "Issued Oct 5 · issued" says it twice. `daysUntilDue()` stays in calendar days for both kinds, so the "due today" counters keep their meaning, and it is null for Issued. `bookingKey()` is unchanged for date-only loans; a timed loan is keyed to the minute, so a morning and an afternoon session on the same day are two bookings, while loans saved seconds apart in one submit stay together.

  **Not handled yet, on purpose.** `BorrowTransactionController::void` releases stock only for an open loan, so voiding an Issued row would not restore `available_quantity` until the item is next edited. The overdue queries that compare against a date string (`return_date < today` in the borrowing block, the nightly sweep, the users screen) work for date-only loans but cannot see a timed loan's time. Both belong with the screens that create these rows.

  **Verification.** `php artisan test`: **370 passed**, up from 357. The new `LoanTypesModelTest` has 13 tests:
  - the schema, including the column default on a raw insert;
  - the loan type labels and predicates;
  - a date-only loan due today is not overdue at 23:59:59 and is at 00:00:01 the next day;
  - a timed loan due an hour ago is overdue and reads "1 hr late";
  - "due in 25 min", "10 min late", "due at 3:30 PM" and "2 days late";
  - a cross-midnight range;
  - a timed return logged 15 minutes late reading late;
  - Issued never out or overdue, even with a past timed due date;
  - Void winning over Issued;
  - issued units reducing derived availability and voiding restoring it;
  - the edit route recomputing without issued units and refusing a total below out + issued, with the index aggregate agreeing;
  - existing date-only rows, including one inserted as `00:00:00` behind the model, rendering the same lines as before;
  - booking keys.

  **Migration run on a copy, not the dev database.** XAMPP's MySQL was not running. The data directory was copied to the scratchpad and served by a separate `mysqld` on port 3307, with every InnoDB path redirected to the copy. Two results on that copy:
  - The pending 2 Oct category migration and the three new ones ran. The five existing loans were rendered through the **committed** models before, and through the new models after: `dateLine()`, `derivedStatus()` and `bookingKey()` are byte-identical. All three foreign keys survived the ALTER.
  - Each rollback guard fired on a probe row. A clean rollback restored DATE columns, and re-migrating worked.

  **Your dev database has not been migrated.** Run `php artisan migrate` once MySQL is up. Separately, `C:\xampp\mysql\data\multi-master.info` holds InnoDB error-log lines from 27 Sept, which stopped the copy from starting until it was moved aside there, and is likely why MySQL won't start. The same log shows "log sequence number is in the future" warnings since then. Neither was touched in the real directory.

  `npm run build` was run and changed nothing tracked. `vendor/bin/pint --test`: all 7 touched files pass, and the 9-file baseline is unchanged.

## 2026-10-02

- **Equipment now has a category, and borrowers see the request list grouped by it.** The equipment form has a new optional **Category** field. It suggests the categories already in use and also accepts a new one, so there is no separate screen for managing categories. The value lives in a new nullable `equipment.category` column (60 characters, indexed; migration `2026_10_02_120000_add_category_to_equipment`). A column was chosen over a categories table because the department has a handful of item types, and a dedicated add/rename/delete screen would cost more than it saves.

  **The list cannot quietly split.** Free text normally drifts: "Cables", "cables" and "Cables " become three groups on the borrower's screen. `Equipment::canonicalCategory()` runs on every create and update. It trims, collapses inner whitespace and stores a blank as `null`. If the value matches an existing category ignoring case, it saves the existing spelling, so typing `presenting & AUDIO` files the item under `Presenting & audio`. A genuinely new name is kept exactly as typed rather than auto-cased, because title-casing would mangle "HDMI" or "USB-C adapters". Validation is `nullable|string|max:60`, on the existing admin-only routes.

  **Where borrowers see it.** In the **Request equipment** modal, items now sit under small uppercase headings: named groups A–Z, then anything without a category under "Other". The headings stick to the top of the list while it scrolls, and the list is a little taller (`max-h-72`, was `max-h-56`) to make room for them. Each group is a `role="group"` labelled by its heading. When no item has a category, no heading is drawn and the list reads exactly as before, so an install that never sets categories sees no change. Items with nothing left stay disabled inside their group. On the dashboard's **On the shelf now** panel, the category follows the stock count ("12 of 12 free · Cables & power"). The count comes first, so a long category is what gets truncated, not the number. The admin inventory list does not show categories yet; the field is visible in the add/edit dialog only. The seeder gives its eight demo items categories.

  **Verification.** `php artisan test`: **357 passed**, up from 345. The new `EquipmentCategoryTest` has 12 tests:
  - the category saves on create;
  - a typed variant reuses the existing spelling, and a new name keeps its casing with spaces collapsed;
  - a blank is stored as `null`;
  - editing changes and clears it;
  - 61 characters is refused;
  - a borrower posting to the update route cannot change it;
  - the form's datalist and the edit button's prefill render;
  - the request list orders groups A–Z with "Other" last;
  - no headings render when nothing is categorised;
  - an item with none left stays disabled;
  - the shelf label follows the count.

  In headless Chrome at 1280px and 390px against a scratch SQLite database: the five headings render in order, the first stays pinned while the list scrolls, the shelf labels show, there is no horizontal overflow and no JS errors. The admin dialog prefilled on edit, and saving `presenting & AUDIO` stored `Presenting & audio`. `vendor/bin/pint --test`: no new issues over the 9-file baseline. `npm run build` was run.

## 2026-10-01

- **Fixed: Decline on the requests queue did nothing.** Clicking **Decline** threw `TypeError: Cannot set properties of null (setting 'textContent')` and the reason dialog never opened, so no request could be declined. The click handler in `admin/request.blade.php` filled the dialog's summary line with `form.querySelector('[data-decline-summary]')`, but that line sits in the dialog header, outside `#decline-form`. The lookup returned `null`, the handler threw, and `openModal` was never reached. It is now scoped to `#decline-modal`. The server side, `ItemRequestController::requestActions`, was always correct.

  **Verification.** Reproduced in headless Chrome against a scratch SQLite database with the TypeError and a zero-size, still-hidden dialog. After the fix, the dialog opens, Decline enables at 5 characters, and `POST /admin/request/decline` returns 302. The row reads `Declined` with the reason and `decided_by`, and the page shows no JS errors. `RequestQueuePageTest` gains two tests: one pins that the summary is not looked up inside the form, and one checks that a decline with a reason is recorded. `php artisan test`: **345 passed**.

- **Admins can now create another admin (staff) account from the users screen.** Until now an Admin had no web path: `POST /admin/users` capped `user_type` at `in:Instructor,Student`, and a new admin meant a seed or tinker. The "Add user" dialog's role select now offers **Admin (staff)** alongside Instructor and Student, and `AuthenticateUser::register` accepts `Admin`. No route was added or changed.

  **A new admin is held to more than a borrower account**, because it is the one account that can mint further admins. Choosing Admin on the add form brings up two fields, and the server enforces four rules (none of them apply to Student or Instructor):
  - the email must be on `@nmsc.edu.ph`, matched whole by `User::isSchoolEmail()`, so `not-nmsc.edu.ph` is refused;
  - the password follows the reset page's rule, `Password::min(8)->letters()->numbers()`, not the borrower form's `min:4`;
  - a reason, "Why they need admin access" (`role_override_reason`, 5–500 characters), is required;
  - **the creating admin's own password** (`current_password`, Laravel's `current_password` rule) is required. A signed-in session left open on a shared lab PC can no longer quietly create a second, permanent admin.

  The new account is written with `role_overridden_at` / `_by` / `_reason`, the record every role change already uses, so the row reads "Set to Admin by Quincy Jane Oliver on Oct 1 — new lab custodian…". The dialog's live hint mirrors each rule in order, and the submit button stays disabled until they are met. Student and Instructor creation is unchanged. A staff account is removed the same way as any other: the existing Deactivate/Delete dialog already worked on admin rows, and Suspend stays hidden for admins as before.

  **Verification.** `php artisan test` passed with **343 tests**, up from 333. The new `StaffAccountTest` has 9 tests:
  - creation succeeds with the right password hash;
  - who made the admin and why are recorded;
  - the new admin can sign in and lands on `/admin/dashboard`;
  - a wrong own password is refused;
  - a missing reason is refused;
  - a non-school or suffix-spoofed domain is refused;
  - each of three weak passwords is refused;
  - borrower accounts keep the lighter rules and get no override record;
  - the form offers the option and the step-up field.

  In `SecurityRegressionTest`, `test_admin_users_form_cannot_create_admin_accounts` is replaced by two tests: an admin posted without reason or step-up is refused with no row written, and a Student posting the same is a 403. `vendor/bin/pint --test`: no new issues over the baseline. Note that the baseline now reads 9 files plus `SecurityRegressionTest`, because `AdminSeeder` arrived in 318feef. `npm run build` was run; it added no new utilities. The rendered users page's 6 inline scripts parse cleanly under Node. **Not verified in a browser:** the field show/hide and live hint were not clicked through.

## 2026-09-28

- **Reset-password page rebuilt to `design-reference/Reset Password.dc.html`, and the last page off `auth.css`.** The page the emailed link opens now uses the same frame as sign-in, register and forgot-password. That frame is two columns from `lg` up (`minmax(0,0.85fr)_minmax(0,1fr)`) and stacked below. The `#183060` panel has the brand block and "What happens when you save" with three numbered points. The heading is "Choose a new password" at 24px/600. The pale background, floating seal, "Account Recovery" label and 52px heading are gone. There is no shared auth layout to reuse, since each auth page inlines the frame, so this page copies forgot-password's markup exactly. It does not add a new style. IBM Plex Mono is loaded on this page for the countdown only.

  **The email is the link's, not a field.** It shows as a read-only chip: the account's initials, "Resetting the password for", then the address. It is posted from a hidden input next to the token. A hidden `username` field lets password managers save the new password against the right account.

  **One password field, held to new rules.** "Confirm new password" and `confirmed` are gone. The rule is now `Password::min(8)->letters()->numbers()`, plus a closure refusing any password that contains the part of the email before the @, case-insensitively. The field has a text Show/Hide toggle, like sign-in. It also has a 3-segment strength bar and three requirements that tick off live, and they mirror the server exactly. Length counts characters, as `mb_strlen` does. Letters and numbers are `\p{L}` and `\p{N}`, as the rule uses `\pL` and `\pN`. The name check is the same "contains". Submitting with a requirement unmet stops on the client with "Meet all three requirements to continue." While a valid submit is sending, the button is disabled and reads "Saving…". **This page's minimum is now 8 while registration and the admin user form are still `min:4`.** That mismatch is deliberate for now and flagged, not fixed.

  **Expiry, from the broker.** "This link expires in MM:SS" is counted from the token row's `created_at` plus `config('auth.passwords.users.expire')`, which is 60. It is not the mockup's 30. The page gets the seconds left and counts down against the wall clock, so a backgrounded tab does not drift. `reset()` now asks the broker's `tokenExists()` before rendering. A used, replaced, timed-out or email-less link gets the **expired state** instead of a form: "This link has expired", "Reset links work once and last 60 minutes. Your password has not been changed.", a primary "Send a new link" to `password.request`, and "Back to sign in". The countdown reaching 0:00 swaps to the same state without a reload. A broker `INVALID_TOKEN` or `INVALID_USER` on submit no longer comes back as a red email error. It redirects to the link with `reset_link_expired` flashed, which renders the expired state.

  **Success signs nobody in, and signs everyone else out.** After `PASSWORD_RESET` the user goes to `/login` with their email pre-filled. A `password_reset` flash draws a green panel there: "Password updated. Sign in with your new password. Anywhere else you were signed in has been logged out." It adds "Didn't make this change? Tell the equipment office at …". The remember token was already rotated. The user's other rows in `sessions` are now deleted too, with the database driver only, which is what `.env.example` uses. The `PasswordReset` event is fired. Routes, the broker, CSRF and the forgot-password throttle are unchanged.

  **Office address: config, not the prompt.** The request quoted `cict.equipment@nmsc.edu.ph`, but `config('office.email')` currently defaults to `quincyjane.oliver@nmsc.edu.ph`. The panel reads the config, like every other public page, and falls back to "at the equipment room" when it is empty. Set `OFFICE_EMAIL` to change it.

  **Verification.** `php artisan test` passed with **333 tests**, up from 319. The new `ResetPasswordPageTest` has 14 tests covering:
  - the frame and removed furniture;
  - the chip and hidden inputs;
  - one password field with no confirmation;
  - the rules and bar;
  - the countdown from token and config, checked with `expire` at 45 and a frozen clock;
  - an expired token, a wrong token and a missing email;
  - a broker refusal on submit landing on the expired state with the password unchanged;
  - each server rule;
  - an inline error;
  - redirect without sign-in;
  - the sign-in panel, which appears only after a reset;
  - other sessions deleted while another user's session survives, and the remember token rotated;
  - the new password working and the old one failing.

  `ForgotPasswordPageTest`'s end-to-end reset passes unchanged. `npm run build` was run, and the new utilities are in the built CSS. `vendor/bin/pint --test` shows no new issues over the 8-file baseline. In headless Chrome on a scratch SQLite database, at 1280 and 390px:
  - a valid link counted down (59:10 → 59:09), ticked rules live, toggled Show/Hide, kept the expired block hidden and set the clock in Plex Mono;
  - a token backdated 61 minutes rendered the expired state with no form;
  - a real submit landed on `/login` with the panel and the email filled in;
  - reopening the used link showed the expired state.

  There was no horizontal overflow and there were no page errors.

## 2026-09-27

- **Admin sidebar polish: outline icons, one left edge, and a brand block that continues the page header's line.** Font Awesome in the nav is replaced by a new anonymous component, `<x-icon name="…">` (`components/icon.blade.php`): six 16px outline SVGs drawn with `currentColor`. Icons are `text-neutral-400`, go to `text-neutral-600` on link hover (`group`), and are `text-primary-600` when active. The aside now carries `px-3`, and the nav, group labels and user card sit inside it, so the logo, both labels, all six icons and the user card start at **x=24**. The brand block is `-mx-3 px-6` rather than `px-3`, which keeps the logo at 24 but lets its `border-b` run the full 256px to meet the sidebar's right border. With plain `px-3` the line would have stopped 12px short of the page header's. Its height is the page header's measured height, **114.59375px**, set exactly because the header's height comes from font metrics and a rounded 115px can land the two borders on different pixel rows. Labels are `pt-5` for the first group and `pt-6` after, `pb-2`. Links are `py-2.5`. The user card's container lost its `border-t`. The ellipsis and logout icons in the user card are still Font Awesome, since only the nav was in scope.

  **Verification.** In headless Chrome at 1024, 1280 and 1440px, the brand block's bottom and the page header's bottom both measure 114.59375px, and the border runs 0–255px, then the sidebar edge, then the header from 256px. There were no page errors. `php artisan test`: 306 passing. `npm run build` and `php artisan view:clear` were run. **Known gap:** below 1024px the dashboard and users headers wrap, measuring 140px and 166px at 768px, so there the lines do not meet. Every other admin page is 114.59375px at every desktop width.

## 2026-09-26

- **Admin sidebar restyled, and its links built from one list.** `components/admin/navbar.blade.php` is the only view touched. Links are smaller (`text-sm`, `py-2`, 16px icons). The 4px left border is replaced by a 3px bar that renders only on the active link. The six hand-copied `<a>` tags are now an `@php` array looped with `@foreach`, in two labelled groups: **Overview** (Dashboard, Equipment, Users) and **Circulation** (Borrow Transactions, Requests, Return Logs). The active match is `request()->routeIs($route, $route.'.*')`, so a child page such as `admin.logs.item` keeps Return Logs lit, and the active link carries `aria-current="page"`. The Requests link shows a red count of pending requests, from a view composer in `AppServiceProvider::boot()` scoped to this partial. It uses `status = 'Pending'`, the value the app writes. The migration's default is `'pending'`, but MySQL's collation compares case-insensitively. The user card shows an initials avatar, the name and the role, with the email as a tooltip, and a `fa-ellipsis-vertical` menu button. **Logout is now a plain `type="submit"`**, so it works without JavaScript and no longer asks "Are you sure?". The dropdown script keeps click-to-toggle and click-outside, closes on Escape (returning focus to the button), and keeps `aria-expanded` in sync. The overlay, `.sidebar` / `.sidebar-overlay` hooks, and all positioning and mobile classes are unchanged.

  **Verification.** `php artisan test`: **306 passing**. `vendor/bin/pint --test`: clean on both touched files. `npm run build` was run, and every new arbitrary utility is in the built CSS. The app was served on a scratch database and logged in as the admin, then each GET admin page was fetched. Exactly one link is active on each: dashboard, equipment, users, transaction, request, logs, and `logs/item/1` (Return Logs). `/admin/notifications` uses the partial but has no sidebar link, so nothing is lit there. There are no edit or create pages; every edit is a modal on its section's page. Headless Chrome at 1280px: the sidebar is 256px, the content starts at 256px, and the menu opens, closes on Escape and returns focus, with no page errors. A plain POST logout redirects to `/`.

  **Found, not fixed: the mobile drawer does not open.** The menu button in `default.blade.php` toggles `.active` on `.sidebar`, but no CSS rule for `.sidebar.active` exists. `b6deed5` removed `.sidebar.active{transform:translateX(0)}` and nothing replaced it. At 390px the sidebar stays at `left: -256px` after the tap. This predates this change.

- **Landing page rebuilt to `design-reference/Landing.dc.html`.** The previous two landing passes are superseded. The page now follows the reference top to bottom:
  - a deep-navy hero, `oklch(0.22 0.06 264)`, carrying the reference's 56px hairline grid (new `.grid-hairlines`: two 1px lines at 3.5% white, no colour gradient);
  - a nav with the CE tile, anchor links and a "Sign in" pill;
  - a status pill, the 64px headline at -0.035em, the lede, a white "Sign in →" and an outline "Request an account";
  - a white "On the shelf now" card with a navy reminder card overlapping its corner;
  - a four-figure band, "How it works" with three step cards, "The rules", a closing navy "Ready when you are." panel, and a footer.

  Poppins throughout, with IBM Plex Mono (loaded on this page only, as the new `font-mono`) for the shelf counts and the 01/02/03 step labels. The grain, meter animation and glass card from the earlier passes are gone, since the reference has none of them.

  **Data, not mockup numbers.**
  - **Shelf and counts:** the rows and the unit / item-type counts come from one read of the lendable equipment, classified by `Equipment::availabilityState()` like the inventory page, with retired stock excluded. That gives full = green, partly out = blue, ≤30% = amber, none left = red. There are at most five rows: none left first, then running low, then partly out, then full, scarcest first within each state.
  - **Hours:** they are now **one structured config value**, `config('office.hours')`: days, opening and closing time. They are read through a new `App\Support\OfficeHours`, which feeds the status pill, the figures band, the visit panel, and the reminder card's "by 5:00 PM".
    - The pill reads "open today" on a working day before closing time and "closed today" otherwise; there is no holiday calendar.
    - The same helper now renders the hours on the sign-in panel, the register and forgot-password copy, and the **borrower dashboard**, which had "Mon–Sat" hard-coded against a Monday-to-Friday config.
  - **Loan period:** it moved to `config('office.loan_days')`, which the approve branch now uses instead of a literal 7, so the "7 days" figure and "Loans run seven days by default" cannot drift from what approval actually does.
  - **Contact and location:** read from `office.email` and a new `office.location`. The contact falls back to "Ask at the equipment room" when the address is empty.

  **The reference's claims were checked against the code, and five were wrong.** Each is corrected on the page, and pinned by a test so it stays corrected:
  1. "Accounts approved within one working day" (eligibility line) and "approves new accounts within a working day" (visit panel): there is no account approval step and no defined turnaround. Both sentences keep only their true half.
  2. Step 01, "with the dates you need it": the request form takes item, quantity and remarks only. It now reads "and say what it is for".
  3. Step 02, "usually the same working day. You hear back by email either way": nothing sets a turnaround, and approve and decline send no email. It now reads "the decision shows on your dashboard", which the borrower dashboard does.
  4. Step 03 note, "Email reminder the day before it is due": `sendReturnAlertNotification` emails on the due date. It now reads "on the day it is due".
  5. Rule 3, "Overdue items pause borrowing": this was **not enforced**, and the terms summary already promised it. At your direction it now is. `ItemRequestController::store` refuses a new request while the borrower has any open loan past its due date, judged by the date, not the stored status, which lags until the nightly sweep. The copy says "cannot send new requests" rather than the mockup's "are held", because nothing is queued.

  The reminder card stays static and illustrative, as asked; "due tomorrow" is wording the borrower dashboard really uses (`timingLabel()`).

  **Also kept.** A signed-in visitor gets "Go to your dashboard" / "Dashboard" on the same buttons, per role, instead of Sign in. There is a skip link and focus rings on every link.

  **Verification.** `php artisan test` — **306 passing**.
  - `WelcomePageTest` was rewritten, 23 tests:
    - the six sections and their anchors, one h1 (the headline), auto-fit grids and a clamped display size, Plex Mono on figures and step labels;
    - every link going to the existing routes, and dashboard links for signed-in admins and borrowers;
    - shelf rows from the table, each state's colour, five rows with out and low first and retired excluded, computed counts, and an empty-inventory state;
    - the pill open on a Monday, closed on a Sunday and closed after 17:00, one hours config driving pill, figures, visit panel and reminder, and the loan period from config;
    - each corrected claim staying corrected, and the contact fallback.
  - New `BorrowingRulesTest`, 6 tests: the overdue block, which keys on the date, ignores loans due today, returned or voided loans and other people's loans, and approval using `loan_days`.
  - New `Tests\Unit\OfficeHoursTest`, 3 tests.
  - `DesignRegressionTest` now checks the landing page for its styled white sign-in button instead of `.btn-primary`, and `ForgotPasswordPageTest` reads the hours through the helper.

  **Rendered in headless Chrome** (puppeteer-core in the scratchpad, against a throwaway SQLite database) at 1440, 1024 and 390px:
  - no horizontal overflow and no element past the viewport at any width;
  - the reminder card overlaps the shelf card by 23px and sits 41px clear of the last shelf row at every width;
  - the h1 computes to 64px / -2.24px at 1440 and 1024 and 40px at 390;
  - Plex Mono and Poppins load and apply;
  - the hero is two columns at 1440 and stacks at 1024, exactly as the reference's `minmax(460px)` dictates.

  Two phone fixes came out of the screenshots. The status pill clipped its wrapped text inside a fixed 30px height; it now grows, and the hours can no longer split mid-range. The figures band now uses a 150px column minimum instead of 220px, so a phone gets 2×2 instead of four stacked rows; desktop is unchanged. `npm run build` run. `vendor/bin/pint --test` — clean on every touched file, 8-file baseline unchanged.

- **Landing page, second pass: the page now shows the product instead of describing it.** The first pass got the structure right but left the navy panel as three bullet points on a flat fill. The headline now leads it at 40px with tighter tracking. Under it is an **"On the shelf now" card** with the four lendable items the room holds most of, each with an "in / total" count in tabular figures and an availability meter. The meter colours follow `Equipment::availabilityState()`, the same "All in / Partly out / Running low / Fully out" vocabulary the admin screens use, desaturated for the dark ground and spoken to screen readers through an `aria-label`. The data comes from a new query in `UserController::welcome()`. Retired items are excluded, and the card is dropped rather than faked when there is no stock or the database does not answer; the inventory total moves into its footer. The card is frosted glass: a translucent fill, a 1px inner ring, an inner top highlight and a navy-tinted drop shadow. A single soft light at the top-left and a grain overlay (new `.surface-grain`) give the panel depth. Both are the panel's own hue, so there is no purple "AI gradient" and no second accent.

  **Right side.** A sentence-case eyebrow with a short rule, a larger `h1` (38px, still exactly one), and more whitespace. The primary button is 48px, with a blue-tinted shadow and an arrow that nudges on hover. "How borrowing works" is now a numbered timeline on a connecting line, because it is a sequence, not three features.

  **States.** Every link and button has a visible `focus-visible` ring, and there is a "Skip to sign in" link for keyboard users. Sections rise in on a stagger using the existing `.anim-rise`. The meters grow from the left with a transform-only `.meter-fill`, and the "Live" dot pulses under `motion-safe:` only. Both new animations keep their collapsed start state inside the `prefers-reduced-motion: no-preference` query, so nothing is left hidden for people who turn motion off.

  **Kept deliberately.** Poppins stays: swapping the face on one page would break the one-system rule with login and register, so any font change should be made app-wide. The frame, panel colour, 10px radii and every fact source are unchanged, and no review timeframe is claimed.

  **Verification.** `php artisan test` — **291 passing**. `WelcomePageTest` grew to 17 tests, adding:
  - the shelf card rendering real counts, the matching meter width and spoken label;
  - retired items excluded, the card capped at four, largest first;
  - no card when there is no stock;
  - both animations respecting reduced motion;
  - the skip link and focus rings being present.

  `npm run build` run, and all 16 new utilities, including the arbitrary-value ones, confirmed in the compiled CSS. `vendor/bin/pint --test` — no new issues over the 8-file baseline. **Not visually checked:** the Chrome extension was not connected, so the layout, grain strength and animation timing have not been seen in a browser.

- **Landing page rebuilt in the sign-in / register frame.** `welcome.blade.php` was the last public entry point still on `auth.css`: a dot-grid overlay, two ambient glow orbs and a centred logo ring, with nothing on the page that told a visitor anything they did not already know from the URL. It now uses the same two-column grid as login and register, `minmax(0,0.85fr)_minmax(0,1fr)` from `lg` up and stacked below. The left panel is the solid `#183060` one: logo lockup, one line on what the room lends, and three facts. The right side makes one choice easy.

  **Facts, read rather than typed.** The inventory line ("N units tracked across N item types") comes from the same query as the sign-in page. It is now a shared `UserController::inventorySummary()`, and `/` and `/welcome` route through a new `UserController::welcome()` instead of closures, so the two pages cannot quote different figures. Retired stock is not counted, and the line is dropped rather than the page if the database does not answer. Opening hours come from `config('office.hours')` and the domain from `User::SCHOOL_DOMAIN`. There is **no review-time claim**: nothing in the application defines one.

  **One primary action.** *Sign in* is the only filled button. *Request an account* follows it, outlined and unaccented, the same treatment it has under the login form. A signed-in visitor, who reaches this page through the logo on every public page, gets *Go to your dashboard* (admin or borrower, by role) with their name, instead of a sign-in button that would only bounce them. Below a rule, **How borrowing works** gives three steps written to match the code: request from the dashboard; the office approves, and stock is held only then; a reminder email goes out on the due date. The legal links and the "ask at the equipment room" line match the other public pages.

  `auth.css` is still in use by `reset-password.blade.php`, so the file stays.

  **Verification.** `php artisan test` — **286 passing**. A new `tests/Feature/WelcomePageTest` (12 tests) covers:
  - no `auth.css` and no orb or overlay markup, and the shared grid and panel colour present;
  - exactly one `h1`, naming the system;
  - sign-in placed ahead of account creation, with a single `btn-primary`;
  - the inventory figure excluding retired stock and matching `/login` exactly;
  - hours following config, and the school domain being named;
  - no review timeframe anywhere, and the steps text matching the stock and reminder behaviour;
  - signed-in borrowers and admins each getting their own dashboard link and no register link;
  - `/welcome` serving the same page.

  `SecurityRegressionTest`'s landing-page assertion moved from `auth.css` to the two links. `npm run build` run. `vendor/bin/pint --test` — no new issues over the 8-file baseline. No browser was available for a visual check.

- **Sign-up asks Student or Instructor — and an admin confirms Instructor.** Every account, student and instructor alike, is on `@nmsc.edu.ph`. The code had assumed students were on a separate `@student.nmsc.edu.ph` and derived the role from the domain. With both constants set to the same value, PHP's `match` resolved every sign-up to the second arm, so **every new account was being created as an Instructor** — students included. The domain cannot carry the role, so the form now asks. Asking is a request, not a grant, and the account is **always written as a Student**. Picking Instructor stamps a new `users.instructor_requested_at`: the person can sign in and borrow straight away, and instructor access waits for the office. The form says exactly that under the choice, and the success state shows "Instructor access — Requested, waiting for the office" with no turnaround promised, because the system sets none. `user_type` is still never read from the request, and `requested_role=Admin` is refused outright.

  **Deciding a request.** The users screen gains an **Instructor requests** section above the restricted accounts, with *Confirm instructor* and *Keep as student*, plus an "Asked for Instructor" filter chip and a row marker. The dashboard queue gains an entry that links straight to that filter. Confirming writes the existing `role_overridden_*` record ("Set to Instructor by … — Confirmed instructor request from sign-up"). Deciding twice is refused, and a deactivated account's request is left out of the queue.

  **Admin role edits lost their reference point.** A role change used to need a reason only when it contradicted the domain. With no domain rule, the admin edit dialog now treats *any* change from the stored role as the recorded decision: the reason field appears once the role differs from the account's current one, the server refuses a change without one, and it records who, when and why. Any manual change also settles a pending instructor request. `User::STUDENT_DOMAIN` / `STAFF_DOMAIN` became one `SCHOOL_DOMAIN`; `roleForEmail()` and `roleMatchesDomain()` are gone, along with the "does not match the email" row flag. The forgot-password page's off-domain warning uses the single domain.

  **Copy.** The login page's "the CICT office approves it before the first borrow" claimed an approval gate that does not exist. It now reads "the CICT office confirms instructor access", keeping your "CICT office" wording. The register success state's opening hours now come from `config('office.hours')` instead of a hard-coded "Monday to Saturday", which had drifted from your config change to Monday to Friday.

  **Schema:** `users.instructor_requested_at` (nullable timestamp), migration `2026_09_26_120000_add_instructor_request_to_users`. **Not yet run against the dev MySQL** — it was not running; run `php artisan migrate`.

  **Verification.** `php artisan test` — **274 passing**, 0 failing (the 19 register/role/login failures from the previous entry are resolved). The tests that encoded domain derivation were rewritten to pin the new rule. RegisterPageTest covers the Student/Instructor-only choice, Student → no request, Instructor → a Student with a pending request, the question being required, Admin refused, the whole-domain match, and both success states. SecurityRegressionTest covers a posted `user_type` being ignored, asking for Instructor not granting it, and a borrower being unable to hit the confirm route. UsersPageTest covers any role change needing a reason and being recorded, demotion included, an unchanged role logging nothing, the section and chip rendering above the list, confirm, decline, no double decision, and a manual change clearing a request. AdminDashboardTest covers the queue entry excluding deactivated accounts. In jsdom against a running server (scratch SQLite): the register submit stays disabled with "Say whether you are a student or an instructor." until a role is picked, and the instructor note shows only for Instructor. In the users dialog the reason field appears only when the role differs from the current one, and never on the add form. The requested chip filters to the one pending row. Over real HTTP, a sign-up posting `requested_role=Instructor&user_type=Admin` produced a Student with a pending request, and the admin confirm turned it into an Instructor with the record written. `npm run build` run: the new `has-[:checked]:*` and `border-l-primary-500` utilities are in the CSS (Tailwind 3.4.17). `vendor/bin/pint --test` — no new issues over the 8-file baseline. No browser was available for a visual check.

- **Fixed: the Edit button on an open loan did nothing — and Check-in was broken the same way.** Reported in `BUGS_FOUND.md` as "Can't edit loan on admin". The server was never the problem: `POST /admin/transaction/update` validated, moved the stock delta and redirected with "Loan updated." when driven over real HTTP against a running server. The failure was entirely in the page script. The click handler filled the modal's subtitle with `editForm.querySelector('[data-edit-summary]')`, but that subtitle lives in the modal *header*, above and outside `<form id="edit-loan-form">`. The lookup returned `null`, setting `.textContent` on it threw, and the handler died before `sync()` and `openModal()` ran — so the pencil icon looked live and did nothing. The Check-in handler made the identical mistake with `[data-checkin-summary]` and `[data-checkin-timing]`, which means a loan could not be returned from this screen either. Both lookups are now scoped to the modal element instead of the form. The email dialog, and the form-scoped lookups on the users screen and the borrower dashboard, were checked and already point inside their forms.

  **Why no test caught it.** Every existing loan-edit test posts straight to the controller, and there was no happy-path edit test at all — only the refusal of a closed loan. PHP cannot run the page's JavaScript, so the regression test pins the invariant that actually broke rather than the markup: it renders the loans page, finds every `somethingForm.querySelector('[data-…]')` in the script, and asserts that attribute exists inside that form in the rendered HTML. With the old line restored it fails with "editForm.querySelector('[data-edit-summary]') finds nothing: [data-edit-summary] is outside #edit-loan-form." Two further tests cover the edit trigger carrying the loan's current values and a successful edit moving stock by the quantity delta.

  **Verification.** The bug was reproduced by serving the app against a throwaway SQLite database in the scratchpad (the dev MySQL was untouched, and was not running), logging in as the seeded admin, fetching the loans page and executing its real inline script plus the built `app.js` in jsdom: before the fix, clicking Edit and Check-in each raised `Cannot set properties of null (setting 'textContent')` and left the modal hidden; after it, both open with no errors, the edit form reads "Stock adjusts to match" with a ceiling of 10 (7 free + 3 already on the loan), and Check-in shows "Due Oct 1 · on time". No browser was available for a visual check. `tests/Feature/LoansPageTest` — 16 passing (3 new). Full suite: **261 passing with the committed versions of `User.php`, `config/office.php`, `login.blade.php` and `register.blade.php`**; with the uncommitted working-tree edits to those files, 19 register/role/login tests fail — see the note left in `BUGS_FOUND.md`. `vendor/bin/pint --test` — no new issues over the 8-file baseline. No new Tailwind classes, so no rebuild was needed.

## 2026-09-21

- **Admin dashboard: two more queues, four figures that all change behaviour, and every link arrives pre-filtered.** The work queue already led the page from the 20 September pass, and an empty one already said so in a sentence. What it did not do was cover everything that needs doing, or land you on the rows it had just counted.

  **Queue entries now link into the screen that resolves them, already filtered.** "1 loan overdue" goes to `/admin/transaction?filter=overdue`, not to the loans screen with the reader left to re-find the row. This is a shared addition to `resources/js/ui.js`: a `?filter=` parameter sets the list's chip on load, and it only does so when the page actually has that chip, so a stale link degrades to the unfiltered screen instead of an empty one.

  **Two queues added**, both newly possible: **damaged or lost returns with no outcome recorded**, which is the only queue here that does not age out on its own — a loan gets returned and a request gets decided, but an unresolved incident just sits there — and **restricted accounts**, which is what this system has in place of the approval queue task 10 asked for.

  **The fourth figure is now "Items fully out"** in place of "Returned this week". Returns this week is a number that changes nothing; an item with nothing on the shelf blocks every request waiting on it. Overdue and fully-out carry colour, units-out and pending-requests stay neutral, per the rule that colour marks a problem rather than decorating a count.

  **A "Stock to watch" panel** lists equipment that is fully out or running low, since that is what blocks approvals — sitting with the decisions rather than on the equipment screen only. Retired items are excluded: they are not blocking anything, they are not lendable at all. With nothing to watch it renders a sentence, not an empty card.

  **Every activity entry is a link** to its own record — a loan anchor, an item's return history, the request queue. An activity feed you cannot click through is a list of things you now have to go and find. It stays capped at six and stays in the secondary column.

  No charts were added, and the two new queues are one query each rather than a filter over a table pulled into PHP.

  **Verification.** `php artisan test` — **258 passing** (up from 76 at the start of this batch); a new `tests/Feature/AdminDashboardTest` (15 tests) covers the queue preceding the counters, an empty queue rendering as words, each queue's pre-filtered link, an incident entering and leaving the queue as its outcome is recorded, the restricted-accounts breakdown, exactly four figures with only the two problem ones coloured, the stock watch including low and out but not retired, the healthy-shelf sentence, activity being capped with linked entries, the absence of any chart, and a query-count assertion that fifteen items do not become fifteen queries.

- **Users screen: standing instead of account fields, role as a derived fact, and suspension as a real state.** A user row listing name, email and role is unusable for the person who has to decide whether to approve that person's next request — all three of the things that decision needs were a page away. Each row now carries **units held, overdue count and pending requests**, the last two added as grouped aggregates on the existing query rather than as per-row lookups.

  **Role is a fact read off the email domain, not a control.** There is no inline role widget on any row; the only role select on the page is inside the edit dialog. A stored role that contradicts the address is flagged on the row. Overriding the derivation is still allowed — admins are behind `userType:Admin` and sometimes the register is simply wrong — but it is now a **recorded decision**: the server refuses an override with no reason, and stores the reason, the author and the timestamp. Setting a role that matches the domain clears any previous override rather than leaving a stale one behind. An unexplained role change was the one edit on this screen nobody could reconstruct afterwards.

  **Suspension is a distinct state from deactivation.** Deactivating someone who owes two items also locks them out of the screen that tells them what they owe, which is the wrong sanction for the thing it was being used for. A suspended account signs in, sees its dashboard and its open loans, and cannot borrow — enforced in `ItemRequestController::store`, not by hiding a button. It takes a reason, because the borrower is shown it, and the row says why and since when.

  **Restricted accounts lead the page**, above the member list. Task 9 asked for pending account approvals here; there is no approval gate in this system — registration creates a working account — and you chose not to add one, so the actionable rows are the accounts a human has already acted on: deactivated, or suspended. Each carries the action that reverses it.

  Also: filter chips for the new states, one sort control (name, most overdue, holding most), `data-list-rows` for it to reorder inside, and four summary figures of which the two that name a problem filter the list.

  **Schema:** `users` gained `suspended_at` / `suspension_reason` / `suspended_by` and `role_overridden_at` / `role_override_reason` / `role_overridden_by`. All nullable, all additive. Migration run against the dev database.

  **Verification.** `php artisan test` — 243 passing; a new `tests/Feature/UsersPageTest` (22 tests) covers the three standing figures and pending counting only pending, the absence of any role control in the rows, the domain-mismatch flag, an override being refused without a reason and recorded with one, an override clearing when the role returns to the derived value, a suspended borrower being refused at `POST /borrower/request` while still being able to sign in, a deactivated one still being unable to, the suspension reason and date rendering, the restricted section leading the page and not rendering when empty, the delete guard, the sort control, 25 rows without pagination, and row actions staying neutral at rest.

- **Return logs rebuilt as an audit record.** This screen is read when something is damaged, missing or disputed — not browsed — and it was laid out for browsing: newest-first, condition as the third column, the receiving staff member collapsed into a single line above the table on the theory that a column with one repeated value is not a column. That collapse was wrong here. A return with no receiving staff member is an **incomplete record**, and a collapsed line cannot say which rows are missing one. Both names are now on every row and a missing receiver is flagged, with its own filter and its own figure in the summary strip.

  **Condition leads**, with colour carrying the severity and the condition note rendered in full. A truncated damage note is the one piece of text on this page nobody can afford to lose the end of. The vocabulary is the four the spec names — Good, Minor damage, Damaged, Lost — offered at check-in from one constant shared with the validation rule. `Missing parts` stays *accepted* though it is no longer offered, because rows written before this exist with that value and retroactively tidying an audit log's vocabulary is exactly what an audit log must not do.

  **The page leads with what still needs follow-up.** Damaged or lost with no outcome recorded is work; everything else is archive. That needed somewhere to put the outcome, so `return_logs` gained `resolution`, `resolved_at` and `resolved_by`, and each incident carries a "Record outcome" action. Once recorded it drops out of the follow-up list into the archive. An outcome cannot be silently overwritten — a second attempt is refused and points at the note mechanism instead.

  **Entries are immutable, enforced by the absence of a route rather than the absence of a button.** There is no update and no destroy on `ReturnLogsController`, and a test walks the registered route table asserting that the only non-GET routes under `admin/logs` are the two append-only writes. A **correction is appended** as a `return_log_notes` row carrying its own author and timestamp, and renders beside the original, which keeps whatever it originally said. The author FK is `nullOnDelete` — a note outlives the account that wrote it.

  **An item-history view** at `/admin/logs/item/{equipment}`: every return ever recorded for one piece of equipment, with the damage rate stated as a percentage. A single damaged return is an accident; the fourth is a fact about the equipment, and it is invisible on a date-ordered list. Each entry also links back to its loan — the loan rows gained `id="loan-{id}"` anchors for that — and forward to the item's history.

  **Filtering by condition and by date range**, plus search across borrower, item, note and receiver. The date range is a shared addition to `resources/js/ui.js`: rows carry `data-date` in ISO form precisely so the comparison can be a string comparison, and there is a Clear control because a range with no way out is a trap.

  **Verification.** `php artisan test` — 221 passing; a new `tests/Feature/ReturnLogsPageTest` (20 tests) covers the route-table immutability assertion, guessed edit/delete URLs 404ing, a correction appending without touching the entry, the follow-up section leading the page and closing on resolution, a good return refusing a resolution, an outcome not being overwritable, condition being the first column with an untruncated note, the missing-receiver flag, the condition and date-range filters, the loan and item links, the item history excluding other items and reporting a 50% damage rate, and the legacy condition still being accepted.

- **Loans screen: worked worst-first, and a reminder is now recorded rather than only sent.** The date rendering ("Sep 10 → Sep 17 · 3 days late"), the derived status with no control that types one, the check-in dialog that records condition and receiving staff before closing the loan and returning stock, and the block on deleting an open or logged loan in favour of void-with-reason were all already in place from the 20 September pass. Three things were not.

  **The list is ordered overdue → due soon → on loan → settled**, most urgent due date first within each band. It arrived in `borrow_date desc`, which is the order loans happened to be created in and has nothing to do with which one needs attention. The rank is computed from the **derived** state, not the stored `status` column: the nightly sweep lags, so a loan still stored as `Borrowed` can be three days late, and ranking on the stored value would bury exactly the row that matters. There is a test for that specific case.

  **One sort control** — most urgent, due date, recently borrowed, borrower A–Z — using the shared mechanism added with the equipment screen.

  **Reminders are recorded against the loan.** `sendManualEmail` sent an email and kept no record, so the screen could never answer "have we chased this one yet?" and an admin looking at an overdue item could not tell a first nudge from a fourth. It now writes a `Notification` row tied to the transaction, after the send, so a failed send leaves no record of one. Each row shows "Reminded Sep 21", or "Not chased yet" on an overdue loan that has never been emailed, and the dialog says how many have already gone out.

  **This needed the one schema change in this batch:** a nullable `borrow_transaction_id` on `notifications`, which had no link to a loan. Additive and nullable — existing rows and the nightly sweep, which writes about a person's whole position rather than one loan, are untouched. Migration run against the dev database.

  Also: the email dialog now names the loan it is about, read from the row's own data attributes with a sentence fallback on every field. The spec called this one out, and it is a real failure mode — a dialog filled from "the current row" has no current row when the list is filtered, which is how these end up rendering "undefined".

  **Verification.** `php artisan test` — 201 passing; a new `tests/Feature/LoansPageTest` (13 tests) covers the four-band ordering, ranking on derived rather than stored state, the default open-loans filter, the sort control and its row keys, check-in being an icon button, the absence of a status control, a sent reminder writing a row with its time, the row wording flipping from "Not chased yet" to "Reminded <date>", the dialog naming template and recipient, the trigger carrying its own record, and the delete/void guards.

- **Request queue: it now answers "what happens if I say yes".** The approve/decline workflow was already right from the 20 September pass — two actions and no status control, a reason required in words on every decline, approval wrapped in a transaction that locks the equipment row, deducts stock and creates the borrow transaction in one step, the pending queue split from the decided archive, oldest first. What it did not do was tell the admin the consequence of the decision they were about to make.

  **Each card now reads "Approving leaves 7 of 10 on the shelf"** in place of restating current stock. The figure is computed **cumulatively down the queue**, which is the only reading that matches what will actually happen: three requests for four units of a ten-unit item are individually fillable and collectively not, and an admin working down a list needs the running total, not the same number three times. Where the queue overdraws the shelf the card says so — "earlier requests in this queue already claim 4 of the 6 available" — instead of rendering a negative.

  **Conflicts are flagged inline, before anything is pressed.** Four kinds: the ask exceeds stock, the item has been retired, earlier queue entries claim the same units, and the borrower already has something overdue. An admin should never approve something the system then has to reject.

  **The borrower's standing sits next to their name** — units currently held, and an overdue count in red when there is one. Deciding a request on the request alone was never possible; that context was a page away.

  All of it is computed in `ItemRequestController::reviewQueue()` with one grouped query for the whole queue's standing, so a list of twenty requests does not fire forty queries to draw twenty subtitles. None of it authorises anything: `requestActions()` still re-checks under a row lock before it deducts. This is the screen telling the truth in advance.

  **Verification.** `php artisan test` — 188 passing; a new `tests/Feature/RequestQueuePageTest` (14 tests) covers the consequence figure and its cumulative behaviour across two requests, the overlap flag and the absence of a negative, the stock/retired/overdue conflicts, a clean request showing none, the standing counting only open unvoided loans, oldest-first ordering, the queue preceding the archive, approve/decline being the only actions, and a query-count assertion that the queue does not N+1 across twelve borrowers.

- **Equipment screen: the summary figures now hand over the rows behind them, and there is one sort control.** Most of what this screen needed was already built in the 20 September pass — availability derived from open loans and rendered as "6 of 10 available" with a proportion bar and one of four states (All in / Partly out / Running low / Fully out), the summary strip, the DataTables chrome stripped, two quiet icon row actions, the description folded under the item name, the guarded delete that quotes units out and referencing records and offers retire as the default, and a server-side floor stopping the total being pushed below the units physically with borrowers. What was missing were three things.

  **"Running low" and "Fully out" are filters now.** They named a problem and then left you to find the rows yourself. Each tile is a button that sets the list's filter chip and scrolls the table into view — and a tile reading zero renders as plain text instead, because a filter that resolves to nothing is a dead control. This is `x-ui.stat-strip` gaining an optional `chip`/`list` pair, so the other admin screens get the same behaviour for free.

  **One sort control** — Name A–Z, Least available first, Largest stock first — in place of no sorting at all. It lives in `resources/js/ui.js` beside the existing filter chips: rows carry `data-sort-<key>`, the option names the key and how to read it (`data-type="number"`, `data-dir="desc"`), and sorting reorders the DOM so it composes with the chips and the search box rather than fighting them. Shared, so screens 6–10 can use it without a second implementation.

  **Figures are right-aligned** against the proportion bar, so the counts form a column the eye can run down instead of drifting with the length of each item's name. They were already `tabular-nums`.

  **Verification.** `php artisan test` — 174 passing (up from 160); a new `tests/Feature/EquipmentPageTest` (14 tests) covers the proportion rendering and all four states, the right-aligned tabular figures, the description's position, the four summary figures, the two tiles filtering and a zero tile *not* being clickable, exactly one sort control with the row keys it needs, 24 rows rendering without pagination, the delete confirm quoting units out and references, the hard delete actually being refused, and row actions carrying destructive styling only under `hover:` — asserted per button after stripping hover variants, because the status cell's severity dot is legitimately danger-toned. `vendor/bin/pint --test` at its 8-file baseline. `npm run build` run.

- **Re-typeset the terms of service and the privacy policy as documents.** The writing was already right; the setting was not. Both pages put ~13px sans-serif body copy inside a floating white card with wide empty margins — a size and a face for scanning a table, used on the two pages in the app that someone is actually expected to read start to finish. Body copy is now **17px Source Serif 4 at a 66-character measure**, the card is gone and the prose sits on the page, and there is real hierarchy instead of headings a shade larger than the text under them: **34px page title, 20px section headings**, 34px between sections. The serif is loaded by the legal layout alone, so no other page pays for a font it does not use.

  **A sticky contents rail**, with an anchor link per section and `scroll-mt` on each section so a jump does not land under the sticky header. Below `lg` the rail folds into a collapsed "On this page" disclosure rather than disappearing — a sticky column on a phone would eat the viewport, but losing the contents entirely is worse. The rail is **generated from the same array the article renders from**, so it cannot drift out of step with the headings; that is the usual way a hand-maintained contents list goes wrong, and there is a test asserting every section is linked and every link lands on a section.

  **"The short version" at the top of each document** — three bullets covering what actually costs someone something. Terms: a request is not a reservation, the default loan is seven days, overdue items pause further borrowing. Privacy: what is stored, who can see it, no trackers.

  **The two fallback clauses are bordered asides now.** Both were the last sentence of a paragraph, which is where a reader stops looking, and both are the sentence that says what to physically do when the normal path is closed: *where the system is unavailable, borrowing falls back to the paper process at the department office*, and *borrowing records tied to equipment that is still out cannot be removed until the item is returned*. One aside per document, because the treatment stops meaning anything if everything gets it.

  **Both documents now come out of one layout**, which each page feeds a single `$doc` array — title, lede, summary, sections. They keep their own URLs: the tab switch in the sticky header is two real links, not a client-side toggle, because these pages are linked to from the registration consent checkbox and a tab that exists only in JavaScript cannot be linked to. The active tab carries `aria-current="page"`, and each document ends with a button through to the other one.

  **One correction to the substance, and it was a real one.** The privacy policy's "what we store" list was framed as complete — "everything else the system keeps is a record of borrowing activity" — and it was not: `SESSION_DRIVER` is `database` and the `sessions` table carries `ip_address` and `user_agent`, so every sign-in writes an IP address and a browser string that the policy never mentioned. The list now discloses it, and the framing sentence was adjusted to match. Relatedly, the design reference's summary bullet ended "— nothing else", which would have promoted that same false claim to the most-read line on the page; the bullet is worded without it, and a test asserts the phrase stays off the page. Nothing else in either document was added, removed or reworded — a few long paragraphs were split at sentence boundaries, and that is all.

  **Verification.** `php artisan test` — 160 passing (up from 131); a new `tests/Feature/LegalPagesTest` (29 tests) covers the serif and the measure, the serif *not* loading on the other four public pages, the heading scale, the card's absence, contents-to-sections agreement in both directions, scroll margin on every section, the summary being exactly three points, both fallback clauses being inside the aside, the tab switch and its active state, the session disclosure, and a set of clause-by-clause assertions that the writing survived the pass intact. `vendor/bin/pint --test` reports no new issues over the pre-existing baseline. `npm run build` run, and the new utilities plus the serif stack confirmed in the compiled CSS.

  One thing worth knowing for next time: `@php` written inside a Blade comment breaks the template. Blade lifts `@php … @endphp` blocks out before it strips comments, so a `@php(` in a doc-comment swallows the file down to the next `@endphp` — it cost a 500 with a misleading "Cannot end a push stack" message. The layout's comment spells the directive names without their `@` and says why.

- **Rebuilt the forgot-password page around the thing it was missing: a confirmation.** The flow stopped at the click — press Send, watch the page come back looking identical, press again — so people queued three emails or gave up. There is now a real confirmation state naming the address the link went to, saying that it works once, and giving the clock time it dies ("expires at 3:00 PM — 1 hour after it was sent"), read from the token row the broker actually wrote.

  **Both numbers come out of the configured broker, not out of the copy.** `config/auth.php` sets `expire` to 60 minutes and `throttle` to 60 seconds. The design reference quoted 30 minutes and a 45-second cooldown; quoting either would have been the page describing a system the server contradicts, so the template renders `1 hour` from config and the resend cooldown is counted from `password_reset_tokens.created_at` — the same row the broker throttles against. A 45-second button in front of a 60-second throttle would have handed people a rejection, which is the exact failure this screen exists to remove. Change the config and the copy follows; there is a test for that.

  **The expiry is stated before the link is sent.** It was previously discoverable only by clicking a dead link. The note under the email field now carries it, and the same slot carries an inline warning for an address outside `nmsc.edu.ph` — a warning, not a block, because the admin users form can create an account on any address and refusing here would shut out exactly the people who cannot get in.

  **Resend is a real re-send with a live countdown**, posting the same address back to `password.email` rather than redrawing client-side. A resend refused by the throttle keeps the confirmation state with the reason shown, instead of dumping someone back to an empty form — the link already in their inbox is still the one they need. **"Use a different address"** (`?new=1`) clears the session key and returns the form. The confirmation survives a refresh, because someone told to go and check their inbox is very likely to reload the tab; it clears itself once the token is gone, which is what a used link looks like, and reports an expired one as expired rather than counting down to nothing.

  **"Not seeing it?" covers the two causes that are real.** School mail filters catch these fairly often. And a **deactivated account is now refused outright** rather than sent a link: it could previously be reset and then still be turned away by `AuthenticateUser::login`, which checks `deactivated_at` separately — so the reset appeared to work, and people did it again. `PasswordResetController::email` checks for it before the broker is called and says the one thing that helps, which is to ask the office to restore the account.

  **The left panel handles the actual emergency.** Someone locked out of this system is often holding equipment that is due back today, so it leads with the fact that returns can be made at the counter without signing in, and carries the office hours and address. Those come from a new `config/office.php` (`OFFICE_EMAIL`, `OFFICE_HOURS`) rather than the template, because an address that stops working should not need a deploy. The form also gained the exit for the failure a reset form cannot solve — forgetting which address you registered with — pointed at the office.

  The broker, the token table, the expiry and throttle config, and `reset-password` itself are all untouched; only `request()` and `email()` changed, and `back()` became a named redirect so the confirmation lands somewhere deterministic.

  **Verification.** `php artisan test` — 131 passing (up from 106); a new `tests/Feature/ForgotPasswordPageTest` (25 tests) covers the confirmation, the real expiry time, the cooldown starting at the broker's throttle and reflecting elapsed time, reload survival, "use a different address", token-gone and expired states, a throttled resend sending exactly one email, a successful resend after the window sending two, the deactivated refusal, the off-domain warning being non-blocking, and a full round trip from request to reset to sign-in. `vendor/bin/pint --test` reports no new issues over the pre-existing baseline. `npm run build` run. The page script was exercised against a DOM stub for the off-domain note and the countdown. The sign-in and registration pages are untouched.

- **Rebuilt the registration page, and moved the role decision off the form and onto the server.** The page is now the same split layout as the rebuilt sign-in page — the solid `#183060` panel on the left, the form on the right, the same type scale, the same 10px radii, the same 44/46px control heights — so the two read as one product rather than two eras. What the left panel carries is the thing the form itself cannot answer: *then what?* Three numbered steps — fill this in, the office verifies you, start requesting — sit where the sign-in page keeps its orientation bullets.

  **The "User Type" dropdown is gone, and nothing replaced it as a field.** Letting someone pick between Student and Instructor at sign-up is a privilege boundary offered as a select. The role is now derived from the school domain of the submitted address by `User::roleForEmail()` — `@student.nmsc.edu.ph` → Student, `@nmsc.edu.ph` → Instructor — and shown on the form as a read-only detected fact ("Detected role: Student. Based on your school email. The equipment office confirms your role during approval."), not as an editable control.

  This is enforced **server-side, and the client half is only a mirror.** `POST /register` moved to a new `AuthenticateUser::registerPublic`, which does not read `user_type` from the request at all — a posted role is not validated, not trusted and not written, which is a stronger property than rejecting the field, because there is no field. The domain is matched **whole**, never as a suffix: `str_ends_with($email, 'nmsc.edu.ph')` would have handed an Instructor account to anyone who can register `not-nmsc.edu.ph`, and a lookalike domain is the cheapest way there is to buy a role. `POST /admin/users` keeps the old `AuthenticateUser::register` with its explicit `in:Instructor,Student` — an admin choosing a borrower's role is a decision they are entitled to make, and they are behind `userType:Admin` to make it. Neither route can produce an `Admin`.

  **Confirm Password is gone.** It caught a typo that the Show/Hide control prevents outright, at the cost of a whole field on a five-field form. In its place: the same Show/Hide **word** the sign-in page uses (an eye glyph leaves you guessing which state it is reporting — the current one or the one it switches to), a strength bar that moves while you type, and the three requirements stated *before* submit and ticked off as they are met — at least 8 characters, contains letters, contains numbers. Those are exactly the three rules `registerPublic` validates; a form that states different rules from the ones the server applies is just a slower way to be rejected.

  **The email field knows what system it belongs to.** The placeholder was `name@company.com`, which is wrong for a school that issues every account on two domains; it is now `name@student.nmsc.edu.ph`, and the note under the field names both domains before anything is typed rather than reporting the rule after a rejection.

  **A successful registration now lands on a success state instead of an unexplained redirect.** It names the submitted email, the detected role, and what happens next — and what it says about timing is what the application actually does. There is no approval queue in this system: `registerPublic` writes an active row, `login` refuses only a `deactivated_at` account, and nothing anywhere sets a review deadline. So the page says you can sign in straight away, that the office checks your details against the school register with no clock on it, and that each *equipment request* is approved before stock is held — rather than inventing "within 24 hours" as a promise no code keeps. The state is reached by redirect-and-flash, so a reload returns the form rather than a stale receipt, and it uses its own `registered` session key rather than `success`, which would have thrown the shared SweetAlert modal on top of a page already saying the same thing.

  **One consent checkbox, and it is a validated field.** The old one had no `name` at all, so it was decoration — the server never saw it. It now posts `agree` and is validated `accepted`, because unticking a box in devtools is a two-second job. The whole framed block is the click target, and the wording commits to the thing that actually matters to the equipment office: returning equipment on time and in working condition. Terms and Privacy are links inside that one sentence rather than a second checkbox.

  **Contact number says why it is collected, next to the field**, and says only what is true: it is shown to the equipment office on your account so they can reach you about a request or an overdue item, and nothing is sent to it automatically. It stays optional, because the column is nullable and the existing validation is `nullable`.

  Also: labels sit above every field and no label is split across a row; required fields carry a marker; validation is inline per field; the submit button is disabled until the form would pass, with the first unmet condition named underneath it (a disabled button that does not say why is a dead control) — and it renders enabled in the HTML, with JavaScript disabling it on load, so a browser without JS can still submit into the server's own validation.

  **Verification.** `php artisan test` — 106 passing (up from 76); a new `tests/Feature/RegisterPageTest` (27 tests) covers both valid domains, case and whitespace normalisation, outside addresses, three lookalike domains, the three password rules, missing consent, duplicate addresses, hashing, the success state, the no-invented-timeframe property, value retention after a rejected submit, and a round trip from registration to a working sign-in. `SecurityRegressionTest` gained four tests pinning that a client-supplied `user_type` cannot override the derived role on either domain, and kept its two admin-route tests unchanged. `vendor/bin/pint --test` reports no new issues over the pre-existing baseline. `npm run build` run, and the new utilities confirmed present in the compiled CSS. The page's own script was additionally exercised against a DOM stub — role detection, the strength scale, the rule ticks, the submit gate opening and closing, and the reveal toggle. The sign-in page is untouched and `LoginPageTest` still passes in full.

## 2026-09-20

- **Rebuilt the sign-in page.** Split layout: a solid dark ground on the left (`#183060`, the reference's `oklch(0.32 0.09 262)`) carrying orientation rather than decoration — what the system is for, how many units are tracked across how many item types, and when the equipment room is open — and the form on the right, which is the point of the page. The unit figure is read from the shelf on each render rather than written into the template, and the query is wrapped so the one page that has to work when the database does not simply drops the line.

  The form itself: school email, password with a **Show/Hide word** rather than an eye icon (an eye leaves you guessing whether it reports the current state or the one it switches to), "Keep me signed in on this computer", and a primary Sign in button. "Forgot password?" sits in the password label row, where it is read before a failed attempt rather than discovered after one. Account creation moved below an OR rule into a single outlined button with the line that was missing: the equipment office approves a new account before the first borrow. Error handling is in two places — the server's answer once, above the form, with the field it names marked but the text not repeated inline; and client-side checks mirroring `AuthenticateUser::login` exactly (`required|email`, `required`, and no minimum length, because the server has none).

  Two things worth calling out. The **"Remember me" checkbox was decorative**: `Auth::attempt($credentials)` was called without the flag, so ticking it did nothing. It now passes `$request->boolean('remember')` — a flag, not a credential; the validation rules are untouched. And the page was rebuilt on the app's Tailwind bundle and **no longer pulls `auth.css`**, which the other four public pages still share, so `/login` and `/register` now differ visually until the rest are brought across. Two assertions that pinned the old markup were updated: the legal-links check now asserts the routes rather than the `lp-legal` class, and the portal check asserts the form instead of the stylesheet. `LoginPageTest` is new — 10 cases covering the form's existence, the reveal control, link placement, the demotion of account creation, the live figures, the error region and the remember flag actually setting a recaller cookie. 76 pass.

- **Redesigned the seven admin screens and the borrower dashboard against eight rules.** This one is not UI-only: three of the rules could not be honoured without changing what the controllers accept, so read this before assuming a form field still exists.

  **DataTables and jQuery are gone.** Every admin table loaded a 90KB plugin to give eight rows a search box, and got "Show 10 entries", a pager for a single page of data and a sort arrow on every column along with it. jQuery was on every page for that plugin and nothing else. Both are out of `components/default.blade.php`, the `.dataTables_wrapper` block is out of `resources/css/app.css`, and `resources/js/tables.js` became `resources/js/ui.js`: search, filter chips, a live "showing N of M", modal open/close with Escape and focus restore, and the shared remove dialog — all delegated from `document` and driven by `data-list*` / `data-modal*` / `data-remove-*` attributes. There is no `<table>` element left in the app; each row is a CSS grid that restacks on a phone, which is what let the Responsive extension go too.

  **Columns that said the same thing on every row are gone.** Equipment had `Quantity` and `Available` as two numeric columns that only meant anything read together — now one column reads "3 of 10 available" over a bar. Loans had ten columns, three of which were the same 14rem of truncated text on every row; purpose, remarks and the class schedule moved into a per-row detail panel and the table is down to four. Users lost `User type` as a column (it is one word next to the name) and gained what the page was missing: what each person is holding. Return Logs and Notifications go further and collapse a column *conditionally*: while every return has the same receiver, or every message the same type, the fact is stated once above the list and the column is not drawn at all.

  **Status is derived, and there is no longer a control that types one.** The inline status dropdown on the loans table, the status select on both loan modals, and the status + available-quantity fields on the equipment form are all removed. `equipment.available_quantity` is recomputed as `quantity − unitsOut()` on save (which repairs existing drift), `equipment.status` comes from `Equipment::lendableStatus()`, and a loan's status comes from `BorrowTransaction::derivedStatus()` — which reads Overdue off the due date at render time, so a loan is overdue the morning it becomes overdue rather than the morning after the nightly job next runs. `inlineUpdate` was replaced by `POST /admin/transaction/check-in`: an admin records the equipment physically coming back, in one direction only, and the status follows. `BorrowTransactionController::update` no longer accepts `status` at all and refuses to edit a returned or voided loan.

  **Every destructive action now states its consequences as data, offers a non-destructive default, and refuses the hard delete while history points at the row.** One shared `x-ui.remove-dialog` replaced three near-identical modals; each row's trigger carries the figures it quotes ("Out with borrowers: 2", "Loans and requests referencing it: 9"), and where the old dialog said "This action cannot be undone" it now says "Can't delete — 2 units are still out" with the delete button not rendered. The safe halves needed somewhere to live, so the migration adds `equipment.retired_at`, `users.deactivated_at` and `borrow_transactions.voided_at` + `void_reason`. Deactivating is enforced at the login screen, not just recorded. Voiding requires a reason and puts an open loan's units back on the shelf. Deleting a class schedule is refused while any loan still references it, because the `set null` FK would quietly erase which class those loans were for.

  **Forms cap at real availability, validate inline, and disable submit with the reason stated.** The new-loan picker will not let a quantity past what is on the shelf, the equipment form will not let a total drop below the units currently out ("3 units are out on loan — the total cannot go below that"), and the borrower's request form caps at the chosen item's availability and disables items with none left. Every one of these is re-checked server-side under a row lock; the client half exists so the answer arrives before the round trip, next to the field that is wrong.

  **Both dashboards lead with what needs doing.** The admin dashboard opens with a "Needs your attention" queue built in `AuthenticateUser::adminAttention` — overdue loans worst-first, requests awaiting review with how long the oldest has waited and how many cannot be filled from stock, loans due back today, items fully out — and only then shows the figures. With nothing outstanding it says so in a sentence. The borrower dashboard replaced three counters that read 0 / 0 / 0 for most people most of the time with a standing band ("One thing is overdue") and a dated agenda, one row per thing that person has to do.

  **Dates read "Sep 10 → Sep 17 · 3 days late".** `dateRangeLabel()`, `timingLabel()` and `dateLine()` on `BorrowTransaction`, plus `timingLine()` on `ReturnLog`, which compares the day a return was logged against that loan's due date. No screen renders a raw ISO date any more.

  **A decline carries a reason.** `POST /admin/request/decline` validates `reason` as `required|min:5`, stores it with `decided_at` and `decided_by`, and the borrower's dashboard surfaces the reason for two weeks as an agenda item before it folds into their history. Declines recorded before this change read "No reason recorded — decided before reasons were required" rather than pretending.

  Also: the requests screen splits the queue from the archive, and an unfillable request shows a disabled "Can't fill" instead of an Approve button the stock check would reject; approving confirms with the consequence stated ("Stock is deducted straight away and a loan is created, due back in 7 days"); `x-ui.table-card` is now `x-ui.panel`, since it no longer styles table cells; `<meta name="csrf-token">` was added to the layout, which the one remaining fetch call was scraping out of an arbitrary form; the Add-user role select no longer offers `Admin`, which `AuthenticateUser::register` rejects outright; two unreachable views (`borrower/student`, `student/dashboard`) were deleted. Tests: `RedesignRulesTest` (24 cases, one per rule) and `ScreenSmokeTest` (13 cases rendering every screen against retired/voided/declined/late-and-damaged fixtures) are new; `DesignRegressionTest` now pins the *absence* of the DataTables stack. 66 pass.

## 2026-09-19

- Redesigned the borrower dashboard and the shared page header — UI only; no controller, route, form field, element id or `data-*` hook changed, and the stock invariant is untouched. **Header.** It gained a `menu` prop defaulting to `true`, so the seven admin views are unaffected, and the two borrower pages pass `:menu="false"`. That removes a genuinely dead control: `#menu-toggle` is wired in `components/default.blade.php` against `.sidebar` / `.sidebar-overlay`, both of which live in `components/admin/navbar.blade.php`, so on the borrower pages the button rendered, ate 44px of a phone-width header and did nothing on tap. The header's inner row is now `max-w-content mx-auto`, matching the `<main>` of every page in the app, which previously left the header running full-bleed while the content below it was centred; it also picks up translucency and a blur behind `supports-[backdrop-filter]`, keeping solid `bg-white` as the fallback. Three shared restyles land on the admin pages too, deliberately: the eyebrow moves to `primary-700` (8.6:1 on white) and `text-xs sm:text-sm`, the title to `text-lg sm:text-2xl`, and vertical padding to `py-3 sm:py-4` — all to buy back mobile height. **Mobile.** The two header action buttons keep a 44px target but drop their text label below `sm`, carrying `aria-label` and `title` instead; without that, bell + "Request Item" + "Logout" overflowed a 360px viewport and squeezed the title to nothing. The notification panel was a flat `w-80`, which overflows any screen under ~344px, and is now `w-[min(22rem,calc(100vw-2rem))]`. The page wrapper uses `min-h-[100dvh]` so the column does not jump as mobile Safari collapses its URL bar. **Layout.** The user card no longer shares a four-column grid with the stat tiles — having to fit a name, role, email and phone into a quarter row is what made it cramped — and is now a welcome band spanning the full width, with an initials avatar, an hour-based greeting, the role badge and the contact details in a `<dl>`. The three stat tiles sit three-across at *every* width rather than stacking on mobile, where three full-width cards pushed the tables a screen down; each drops its icon and caption below `sm` and carries its own coloured accent rule, which is what stops them reading as one card repeated. Section headings moved inside their cards as a bordered header row with a count on the right, replacing three identical floating icon tiles above three identical white boxes. Browse Equipment items lost their borders in favour of a tinted fill, since a bordered box nested inside a bordered card reads as noise, and gain a `lg:max-h-[30rem]` scroll cap so a large inventory cannot run the narrow column far past the transactions table. Filter chips became rounded with a filled active state; the Blade initial state and the eight `classList.toggle` calls that drive them were updated in step. Every interactive element gained a transition and an `active:translate-y-px` press. **Two pre-existing dead controls fixed.** `components/instructor/delete-request-modal.blade.php` closes with `id="cancel-delete-x"`, which the dashboard's handler — matching `#cancel-delete` and `.cancel-delete` — never saw, so the X did nothing; it now also carries the `cancel-delete` class. And the `confirmDelete` SweetAlert block never bound, because that modal has no `#confirm-delete` element (its confirm button is a plain `type="submit"` inside `#delete-form`); it is removed rather than wired up, since the modal *is* the confirmation and adding SweetAlert on top would double-confirm. Deleting a request behaved, and still behaves, as a direct form submit. **Also:** a skip link, `aria-labelledby` on each section, a CTA in the empty-requests state — which forced the add-modal trigger from `#open-add-modal` to a `.js-open-add-modal` class hook, since two elements cannot share one id; the id stays on the header button — and an `anim-rise` entry animation whose hidden start state lives *inside* `@media (prefers-reduced-motion: no-preference)`, so a reduced-motion viewer gets the content at full opacity rather than stuck at `opacity: 0`. Verified with 25 tests (130 assertions) covering the removed hamburger, the admin header keeping both its hamburger and its sidebar-only branding, all seven admin pages still rendering, header width alignment, collapsing labels, the skip link and single `<h1>`, greeting/initials/contact fallbacks and escaping, stat totals and the conditional overdue treatment, the notification chip and per-user scoping, both tables' column alignment, section counts, filter hooks, overdue row tinting, row actions, browse filtering, the empty-state CTA, all 22 element ids, and that submitting a request still creates a `Pending` row while merely viewing the dashboard moves no stock; plus `npm run build`. The full suite is back to its committed baseline of 11 passed with the same 2 pre-existing failures (both about admin account creation, unrelated). Known gap: the Chrome extension was not connected this session, so this was verified structurally rather than by looking at a rendered page at 360px. Files touched: `resources/views/borrower/dashboard.blade.php`, `resources/views/components/ui/page-header.blade.php`, `resources/views/components/instructor/delete-request-modal.blade.php`, `resources/views/borrower/student.blade.php`, `resources/css/app.css`, `CHANGELOG.md`.

- Gave the borrower header the CICT logo and wordmark via a new opt-in `logo` prop on `x-ui.page-header` — UI only, backward compatible by default. The prop defaults to `false` and the whole block sits inside `@if($logo)`, so the seven admin views, which pass nothing and get their branding from the sidebar, are unaffected: their rendered `<header>` was captured before and after the change and is identical once whitespace is normalised, the only raw difference being leading indentation on one line, an artifact of the `@if` directive's surrounding newlines that HTML collapses. When enabled, the header renders the same logo image in the same bordered rounded box as `admin/navbar.blade.php`, followed by "CICT Equipment" and "Management System", then a thin vertical divider before the eyebrow and title. Two small deviations from the sidebar's markup: the wordmark is a `<p>` rather than an `<h1>`, since the page title below is already the page's `<h1>` and a second one would compete with it; and the text block plus divider are hidden below `sm` while the logo image always shows, so the mobile header gains an anchor without crowding the action buttons. Applied to `borrower/dashboard.blade.php` and `borrower/student.blade.php`. Note `student/dashboard.blade.php` was left alone: it contains no header markup at all, only a placeholder div, and nothing routes to it. Verified with 10 tests (52 assertions) covering the borrower logo, the shared image reference, the single `<h1>`, that no admin header or admin view carries the logo, that the admin sidebar still does, and that the actions slot still renders. Files touched: `resources/views/components/ui/page-header.blade.php`, `resources/views/borrower/dashboard.blade.php`, `resources/views/borrower/student.blade.php`, `CHANGELOG.md`.

- Rearranged the borrower dashboard into a responsive two-column grid — layout only; no data, Blade logic, route name, id or field name changed, verified byte-identical against the previous revision. The user info card and the three stat cards now share one `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4` row instead of sitting in two stacked sections. Below it, `grid grid-cols-1 gap-6 lg:grid-cols-3` puts "My borrow transactions" in the wide `lg:col-span-2` primary column and "My Equipment Requests" followed by "Browse Equipment" in the narrow `lg:col-span-1` column; everything collapses to a single column below `lg`, with transactions first. Both columns carry `min-w-0`, without which grid children default to `min-width: auto` and the wide transactions table would push the page into horizontal scroll rather than staying inside its own `overflow-x-auto` container. One thing the move forced: the Browse Equipment card grid was `lg:grid-cols-3`, which would have squeezed three cards into the narrow column, so it is now `sm:grid-cols-2 lg:grid-cols-1` — two-up while the panel is full width, one-up once it moves into the sidebar column. Table internals, filters and DataTables init are untouched and the transactions table still has eight columns. Verified with 12 tests (51 assertions) covering the grid classes, which sections land in which column and in what order, the shrink guards, the collapsed Browse grid and an empty-data render. Actual visual confirmation at mobile width still wants a real browser — the tests assert structure, not rendered geometry. Files touched: `resources/views/borrower/dashboard.blade.php`, `CHANGELOG.md`.

- Added a compact user info card to the borrower dashboard and finished the request modal polish — UI only, no controller change; `Auth::user()` is available in every view. The card sits above the stat cards and shows the signed-in user's name, their `user_type` through the shared `x-ui.badge`, email and contact number, with tooltips on the truncated name and email and a "No contact number" fallback for a null or empty value. On the request modal, only the quantity placeholder was actually missing: the earlier readability passes had already brought its inputs, labels, `space-y-4` field rhythm, `min="1"` and flat-theme header/footer in line with `admin/transaction/add-modal.blade.php`, and the input class strings are now verified byte-identical to that file. Also dropped a leftover `min-h-[120px]` from the equipment `<select>`, which dated from when it was a multi-select and made the single-select render oddly tall. Two deliberate deviations from the brief, both to avoid undoing earlier work: the focus ring is `focus:ring-primary-500/30` and labels are `text-base font-medium text-neutral-800`, matching what `add-modal.blade.php` actually uses today rather than the `/20` and `text-sm text-neutral-700` quoted in the request, which describe the pre-readability-pass state. The form's action, method, field names and ids are byte-identical. Verified with 14 tests (47 assertions) covering the card's fields, fallbacks, escaping and position, the modal styling parity, and that submitting a request still creates a Pending row. Files touched: `resources/views/borrower/dashboard.blade.php`, `resources/views/components/instructor/request-item-modal.blade.php`, `CHANGELOG.md`.

- Added a printable borrow slip for a single transaction. New `BorrowTransactionController::receipt($id)` loads the transaction with its user and equipment and renders `borrower/receipt.blade.php`; the route `borrower.transaction.receipt` (GET `/borrower/transaction/{id}/receipt`) sits inside the existing `userType:Instructor,Student` group. Because that group admits every borrower, the method checks `user_id` against the signed-in user and aborts 403 otherwise — without it any borrower could read another's slip by guessing an id, which is covered by tests for a second borrower, an instructor, an admin and a guest. The view is a standalone document with its own inline stylesheet rather than the app layout, so it prints without the navbar, sidebar, modals or DataTables assets, and a `@media print` block drops the on-screen buttons; printing is triggered by an explicit "Print this slip" button rather than firing `window.print()` on load, so opening the tab does not ambush the user with a print dialog. The slip shows the system name, reference number, borrower, equipment, quantity, formatted borrow and return dates, purpose, a colour-coded status badge, optional remarks and signature lines. Each row in "My borrow transactions" gained a Print link opening in a new tab with `rel="noopener"`, sharing the existing Actions cell with "Borrow again" so the table still has eight columns in both head and body. Verified with 16 tests (50 assertions) covering access control, every rendered field, absence of the app shell, the omitted remarks row, a null return date, per-status badges, HTML escaping and column alignment. `route:list` now shows 42 routes. Files touched: `app/Http/Controllers/BorrowTransactionController.php`, `routes/web.php`, `resources/views/borrower/receipt.blade.php`, `resources/views/borrower/dashboard.blade.php`, `CHANGELOG.md`.

- Added an in-app notifications panel to the borrower dashboard, reusing the existing `Notification` model with no new columns or migration. `AuthenticateUser::borrowerView()` now also passes the signed-in user's ten most recent notifications; nothing else about the method changed, and `BorrowTransactionController::sendReturnAlertNotification` — the only thing that writes these rows — is untouched and covered by a regression test. The page header gained a bell button carrying an `x-ui.badge` count, opening a dropdown that lists each notification's message, type and relative date via `diffForHumans()`, with an empty state when there is nothing to show. The panel closes on the bell, its close button, an outside click or Escape, and tracks `aria-expanded`. Two notes: the badge deliberately carries no class override, because `x-ui.badge` merges rather than replaces classes and Tailwind resolves conflicts like `px-2` vs `px-3` by CSS order, so an override would silently lose; and since the table has no read/unread column, the badge counts recent notifications (capped at ten) rather than unread ones — a read state would need the new column this change was scoped to avoid. Verified with 11 server-side tests (40 assertions) covering the count, relative dates, the ten-row cap, per-user scoping, HTML escaping and the untouched creation path, plus a 23-check headless run of the real panel JS confirming toggle, close, outside-click and Escape behaviour and that the request modal and status filters still work. Files touched: `app/Http/Controllers/AuthenticateUser.php`, `resources/views/borrower/dashboard.blade.php`, `CHANGELOG.md`.

- Added a Browse Equipment section and a "Borrow again" action to the borrower dashboard — UI only, no new route, controller method or form change; `$equipments` was already passed to the view. Browse Equipment sits between the stat cards and the requests table and renders a card per item that is `Available` with stock left, showing its name and available quantity beside a Request button. The transactions table gained an Actions column whose rows with status `Returned` carry a "Borrow again" button; every other row renders a placeholder cell so the header and body column counts stay aligned for DataTables. Both buttons share one handler keyed on a `data-request-equipment` attribute that pre-selects the item in the existing request modal, defaults an empty quantity to 1 and opens it — the modal, its field names and its submission target are untouched, so `components/instructor/request-item-modal.blade.php` needed no edit at all (it already exposed `id="add-equipment"`). If the referenced equipment is not among the modal's options the select falls back to its placeholder rather than silently selecting the wrong item, and an already-typed quantity is never overwritten. Note "Borrow again" is offered even when that equipment is currently out of stock, since the modal lists all equipment and approval is the admin's call. Verified with 11 server-side tests (42 assertions) covering which items appear, the Returned-only action, per-row equipment targeting, column alignment and the unchanged request flow, plus a 21-check headless run of the real prefill JS. `route:list` still shows 41 routes. Files touched: `resources/views/borrower/dashboard.blade.php`, `CHANGELOG.md`.

- Added stat cards, overdue row highlighting and status filters to the borrower dashboard — UI only, no controller change; both collections were already passed to the view. Three bordered cards above the requests table show Active borrows (Borrowed + Overdue), Pending requests and Overdue, all derived in-view from the existing `$transactions` / `$requests` collections; the Overdue card switches to danger text and border only when non-zero. Transaction rows now carry a subtle `bg-danger-50` tint when the row is overdue in practice — either already swept to `Overdue` by the nightly job, or still `Borrowed` with `return_date` before today — alongside the existing badge, with a null return date handled safely. Each table gained All / status filter buttons that filter the already-rendered rows by a `data-status` attribute with no request or reload. The filters hook DataTables' own search pipeline (`dataTable.ext.search.push`) rather than hiding `<tr>` elements directly, because these tables paginate at 10 rows and direct hiding would be undone on the next redraw and would corrupt the "showing N entries" count; a plain show/hide fallback runs if DataTables is unavailable. Note the row tint is deliberately broader than the Overdue card and the Overdue filter, both of which follow the stored status as specified, so a not-yet-swept row shows tinted while still filtering under Borrowed. Verified with 14 server-side tests (56 assertions) and a 26-check headless run of the real page against real jQuery 3.7.1 and DataTables 1.13.8 covering card totals, which rows tint, per-filter row counts on both tables, filter independence, paging integrity (`recordsDisplay` vs `recordsTotal`) and the active-button state. Files touched: `resources/views/borrower/dashboard.blade.php`, `CHANGELOG.md`.

- Sidebar consistency and accessibility pass — UI only, no business logic, routes or form fields touched. Fixed the missing Dashboard icon: the markup used `fas fa-layout-dashboard`, which is not a Font Awesome class (it is a Lucide name), so the icon silently rendered as blank space; it is now `fas fa-gauge-high` from the Font Awesome 6 free set already in use. The active nav item no longer signals state by colour alone — it gains a `border-l-4 border-primary-600` accent bar, with inactive rows carrying `border-transparent` so labels never shift. Nav labels moved from `text-base` to `text-lg` and rows from `py-3` to `py-3.5` for easier reading and a larger click target. A plainly labelled "Logout" item with an icon now sits below the profile card, so signing out no longer requires finding the gear icon; it submits the existing `#logoutForm` through the existing POST `/logout` route, sharing one handler with the gear-menu item so the confirm dialog and behaviour are unchanged. The profile card's truncated name and email gained `title` tooltips. Items 1–4 of the same request (table sizing, muted-text contrast, 40px touch targets, badge contrast) were delivered in the 2026-09-18 entry and were re-verified rather than rebuilt. Verified with 9 server-side tests (54 assertions) and a 27-check headless run of the real sidebar covering icon classes, single active item tracking the current route, both logout triggers confirming and submitting the same form, and that declining the confirm submits nothing. Files touched: `resources/views/components/admin/navbar.blade.php`, `CHANGELOG.md`.

- Grouped the class schedule dropdown by instructor — display-only, no query or field-name change. Each instructor now gets one `<optgroup label="...">` holding just their own schedules, with each option reduced to time + room since the name is no longer repeated per line ("MWF 8:30-10:00 AM — Laboratory 1" under a "Maria Santos" group). Groups are sorted alphabetically for scanning. `BorrowTransactionController::index()`'s `whereHas('instructor', ...)` query is untouched, so instructors without schedules still never appear; `class_schedule_id` and the "-- None --" option are unchanged, with None still outside any group and selected by default. Applied to both `add-modal.blade.php` and `edit-modal.blade.php`, which carried the identical flat list. Part A of the same request — the searchable equipment checklist — was already delivered in the previous entry and was re-verified rather than rebuilt. Verified with 11 server-side tests (44 assertions) covering grouping, alphabetical order, group scoping, the excluded-instructor query, the empty state, and that transactions still submit with the correct equipment, quantities and schedule (including None → null and editing a schedule onto an existing transaction), plus a 25-check headless run of the real page confirming the rendered `optgroup` structure and that selecting a grouped option still posts `class_schedule_id`. Files touched: `resources/views/components/admin/transaction/add-modal.blade.php`, `resources/views/components/admin/transaction/edit-modal.blade.php`, `CHANGELOG.md`.

## 2026-09-18

- Moved per-schedule actions out of the users table and into a Manage modal, fixing the "Class schedule" column stretching rows tall once a user had two or more schedules. The cell now stays on one line: "No schedules" when empty, otherwise an `x-ui.badge` count pill ("1 schedule" / "3 schedules") beside a Manage button. Manage opens a modal titled with the user's name listing every one of their schedules — subject, year level, block, room and time — each row carrying its own 40px edit and delete icon buttons. Edit opens the pre-filled schedule form stacked above the Manage modal at `z-[60]`, which stays open behind it; delete goes through the shared `window.showConfirm` SweetAlert2 helper before posting the hidden `@method('DELETE')` form. Each user's rows are rendered once into a hidden `.manage-sched-group` and revealed by id, so no schedule data is duplicated into attributes and Blade keeps doing the escaping. The backend (`ClassScheduleController::update` / `destroy`) and the `admin.sched.update` / `admin.sched.destroy` routes were already in place from the previous entry and are unchanged. Schedule creation and the transaction form's schedule picker are untouched, both covered by regression tests. Verified with 12 server-side tests (49 assertions) plus a 39-check headless run of the real page and real JS covering group scoping, prefill of all seven fields, modal stacking, and the delete confirm wiring. `route:list` still shows 41 routes. Files touched: `resources/views/admin/user.blade.php`, `CHANGELOG.md`.

- Replaced the add-transaction equipment multi-select with a searchable checklist — UI/UX only, no backend or validation change. Each available item is now a checkbox row showing its name and live available stock; ticking one reveals a quantity field beside it, and a search box above the list filters rows client-side with no new endpoint. The submitted payload is byte-for-byte what it was: each row's checkbox is `equipment[]` and its quantity input carries `quantities[<id>]` in the markup but stays `disabled` until ticked, and browsers do not submit disabled controls, so unticked rows contribute nothing and `BorrowTransactionController::store` needed no edit. The quantity input is capped at the item's stock via `max` plus an inline message and a clamp on commit; this is advisory only — the locked server-side check in `store()` remains the source of truth and is unchanged, which matters because the rendered stock figure can go stale. `edit-modal.blade.php` was left alone: it uses a single `equipment_id` select with one quantity, not the multi-select pattern, because `update()` handles one equipment per transaction. Verified with 8 server-side tests (payload shape, multi-item creation, extra quantity keys ignored, over-allocation still rejected) and a 30-check headless run of the real page and real picker script, which caught and fixed a bug where the "no equipment matches" notice stayed hidden because a ticked row is deliberately kept on screen. Files touched: `resources/views/components/admin/transaction/add-modal.blade.php`, `resources/views/admin/transaction.blade.php`, `CHANGELOG.md`.

- Added edit and delete for instructor class schedules. `ClassScheduleController` gains `update()`, validating the same seven fields as `store()` plus the `id` and applying them via `collect($validated)->except('id')` the way `EquipmentController::update` does, and `destroy()`. Routes `admin.sched.update` (POST `/admin/users/sched/update`) and `admin.sched.destroy` (DELETE `/admin/users/sched/{id}`) sit beside `admin.add-sched` inside the `userType:Admin` group. On the users page each listed schedule now carries edit and delete icon buttons; edit opens a pre-filled modal mirroring the Add Schedule markup, and delete goes through the shared `window.showConfirm` SweetAlert2 helper before posting a hidden `@method('DELETE')` form. The new handlers use `.sched-edit-btn` / `.sched-delete-btn` so they cannot collide with the existing user-level `.edit-btn` / `.delete-btn`, and the instructor `<select>` re-adds the schedule's current owner if that user is no longer typed as an Instructor, so saving cannot silently reassign it. Schedule creation and the transaction form's schedule picker are untouched, both covered by regression tests. Verified with 10 targeted tests: update, reassignment, validation, unknown id, delete, non-admin 403s, and that deleting a schedule nulls `borrow_transactions.class_schedule_id` rather than removing the transaction. `route:list` now shows 41 routes. Files touched: `app/Http/Controllers/ClassScheduleController.php`, `routes/web.php`, `resources/views/admin/user.blade.php`, `CHANGELOG.md`.
- Added a forgot/reset password flow built on Laravel's `Password` broker — no hand-rolled tokens, and no new migration since `password_reset_tokens` already existed. New `PasswordResetController` with four actions behind the conventional route names `password.request`, `password.email`, `password.reset` and `password.update`, all registered outside the `auth` group so a locked-out user can reach them. Two new views, `forgot-password.blade.php` and `reset-password.blade.php`, and the dormant "Forgot password?" link on the login page now points at `password.request`. Existing login, register and logout logic is untouched. Verified end to end: 11 targeted tests covering page reachability while logged out, notification dispatch, token consumption, invalid-token rejection, confirmation mismatch and logging in with the new password; plus a real run with `MAIL_MAILER=log` that produced a working reset URL in `storage/logs/laravel.log` (the temporary `.env` change and the reset-token row it created were both reverted). Two notes: the new views use the `lp-*` markup and `public/resources/css/auth.css` that login and register actually use, not `components/auth-card.blade.php`, which no view references; and an unknown email returns the broker's `INVALID_USER` message, so the form does confirm which addresses are registered, matching what registration already reveals. Files touched: `app/Http/Controllers/PasswordResetController.php`, `resources/views/forgot-password.blade.php`, `resources/views/reset-password.blade.php`, `routes/web.php`, `resources/views/login.blade.php`, `CHANGELOG.md`.
- Brought the borrower side in line with the admin readability pass — UI only, no form logic or route names touched. The borrower dashboard, the instructor variant and the three `components/instructor/` modals now use the same tokens as the admin pages: labels at `text-base`/`neutral-800`, inputs at `text-base` with `px-4 py-3` and a `primary-500/30` focus ring, modal titles at `text-lg`, close buttons at 40px, and every action button at 44px with `text-base`/semibold. Muted text moved off `neutral-500` entirely (it no longer appears in these files), empty-state icons went from `neutral-300` to `neutral-400`, section headings to `text-lg` with larger icon tiles, and the delete-request warning became the same bordered `danger-50`/`danger-700` panel used in the admin delete modals. Row-action buttons grew from `px-2.5 py-1.5 text-xs` to `px-4 py-2 text-sm`. Verified: all element ids, form field names, `data-*` attributes, route calls, `getElementById` lookups and the DataTables/SweetAlert2 hooks are byte-identical, and a smoke harness confirmed the dashboard renders for both Student and Instructor and that submitting, updating and deleting an item request still work. Note: `resources/views/student/dashboard.blade.php` was left as-is because it holds no UI (a single div with a comment), and both it and `resources/views/borrower/student.blade.php` are unreachable — no controller or route renders either. Files touched: `resources/views/borrower/dashboard.blade.php`, `resources/views/borrower/student.blade.php`, the three `resources/views/components/instructor/` modals, `CHANGELOG.md`.
- Readability pass over the admin forms and modals — UI only, no business logic touched. All 52 form labels moved to `text-base` in `neutral-800`, and all 52 inputs, selects and textareas to `text-base` with `px-4 py-3` and a stronger `primary-500/30` focus ring; placeholders moved off `neutral-400` to `neutral-500`, which keeps them distinguishable from entered text while clearing 4.5:1. Modal titles are now `text-lg`, close buttons 40px, and every footer and primary action button 44px at `text-base`/semibold. Destructive-warning copy in the delete modals became a bordered `danger-50`/`danger-700` panel at `text-base` instead of 12px red text. The dynamically generated quantity fields in the transaction add modal were restyled in step with the static ones, keeping their `quantities[...]` names. Verified: all element ids, form field names, `for` attributes, route calls, `getElementById` lookups and DataTables/SweetAlert2 hooks are byte-identical to the previous revision, and a smoke harness confirmed the five admin pages render and that add/edit still works for equipment, transactions, users and class schedules. Known gap: inline validation errors do not exist in these files — they surface through SweetAlert2, styled in `resources/js/alert.js` and `resources/views/components/alerts.blade.php`, neither of which was in scope, so the error body text is still `text-sm`. Files touched: the three `components/admin/equipment/` modals, the five `components/admin/transaction/` modals, `resources/views/admin/user.blade.php`, `resources/views/admin/equipment.blade.php`, `resources/views/admin/transaction.blade.php`, `resources/views/admin/request.blade.php`, `CHANGELOG.md`.
- Readability pass over the admin dashboard, sidebar, tables and badges — UI only, no business logic or data touched. Table body cells now render at `text-base` with `px-5 py-4`, headers at `text-sm` in `neutral-600`, and row-action buttons get a 40px minimum target; these are driven from `table-card.blade.php` with descendant variants so all six admin tables change without editing their markup. Muted text moved off `neutral-400`/`neutral-500` to `neutral-600`+ throughout, most notably the sidebar's inactive nav icons. Navbar and header controls grew to 40–44px targets (settings button, logout row, mobile menu toggle), nav links to `text-base` with taller rows, and dashboard stat figures to `text-3xl`. Badges are larger and higher contrast, moving from a 50/700 tint to 100/800 (or 100/700 where the palette stops at 700) with 300-level borders. Also dropped the `tracking-tight` overrides in these files, which had been defeating the looser tracking set in the previous entry. All Blade directives, route names, element IDs and form field names verified byte-identical; `npm run build` succeeds. Files touched: `resources/views/admin/dashboard.blade.php`, `resources/views/components/admin/navbar.blade.php`, `resources/views/components/ui/table-card.blade.php`, `resources/views/components/ui/page-header.blade.php`, `resources/views/components/ui/badge.blade.php`, `CHANGELOG.md`.
- Switched typography from Inter to Poppins and enlarged the base type scale for readability — UI only, no business logic touched. Swapped the Google Fonts link (weights 400–800), pointed `fontFamily.sans` and `fontFamily.display` at Poppins keeping the existing fallback stack, and matched the `body` rule in `resources/css/app.css`. Raised `base` 15→16px, `lg` 17→18px, `xl` 20→22px, `2xl` 24→26px, `3xl` 30→32px, leaving `xs`/`sm` untouched, and reset letter-spacing to `normal` from `base` upward because tight tracking hurts legibility. Affects both the admin and borrower sides, as intended. `npm run build` succeeds and the emitted CSS contains no remaining Inter reference. Known gap: eight elements still carry the `tracking-tight` utility, which the cascade applies after the font-size rules and so keeps negative tracking on those headings and stat numbers. Files touched: `resources/views/components/default.blade.php`, `tailwind.config.js`, `resources/css/app.css`, `CHANGELOG.md`.
- Deduplicated equipment stock arithmetic — refactor only, no behavior change. Added `Equipment::releaseStock(int $quantity)` and `Equipment::reserveStock(int $quantity, ?string $message = null)`, which own the `available_quantity` adjustment, the `Available`/`Unavailable` status recompute and the save. Replaced the eleven inline copies of that block in `BorrowTransactionController` (`store`, `update`, `inlineUpdate`, `destroy`) and `ItemRequestController::requestActions`. All `lockForUpdate` calls, `DB::transaction` boundaries and error wording are unchanged; `reserveStock` takes an optional message because two distinct wordings exist today (a generic one in `inlineUpdate`/`update`, a detailed name-and-shortfall one in `store`/`requestActions`). Verified by running a 14-case stock harness against both the pre- and post-refactor code with identical results. Files touched: `app/Models/Equipment.php`, `app/Http/Controllers/BorrowTransactionController.php`, `app/Http/Controllers/ItemRequestController.php`, `CHANGELOG.md`.
- Removed dead controller methods — no behavior change. Deleted `EquipmentController::availableEquipment()`, `AuthenticateUser::studentView()` and `UserController::show()`, none of which were reachable from `routes/web.php` or referenced by any view. No `use` imports became unused, so none were removed. `route:list` is byte-identical (35 routes) and the suite passes against the committed baseline. Files touched: `app/Http/Controllers/EquipmentController.php`, `app/Http/Controllers/AuthenticateUser.php`, `app/Http/Controllers/UserController.php`, `CHANGELOG.md`.
- Documentation baseline created
