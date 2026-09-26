1. ~~Can't edit loan on admin~~ — **Fixed 2026-09-26.** The Edit (and Check-in) buttons threw a JS error before opening their modal; see CHANGELOG.
2. ~~Every sign-up became an Instructor~~ — **Fixed 2026-09-26.** Everyone shares @nmsc.edu.ph, so the role can no longer come from the email domain. Sign-up now asks Student or Instructor; the account is always created as a Student, and an Instructor answer waits for an admin to confirm on the Users screen. Run `php artisan migrate` on the dev database (new column `users.instructor_requested_at`).
3. ----
