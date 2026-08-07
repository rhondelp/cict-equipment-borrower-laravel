# 🧰 CICT Equipment Borrower

A Laravel-based system for managing the borrowing and return of equipment within a college/department (CICT — College of Information and Communications Technology), with tracking for status, overdue alerts, and class-schedule linkage.

---

## ✨ Features

- **Equipment inventory** — track total and available quantity per item, with a live status per unit:
  - 🟢 Available
  - 🔵 In Use
  - 🟡 Maintenance
  - 🔴 Out of Service
- **Borrowing records** — log who borrowed what, when, how many, and for what purpose.
- **Return tracking** — records borrow date and (nullable) return date; status moves through `Borrowed` → `Returned` / `Overdue`.
- **Class schedule linkage** — a borrow record can optionally be tied to a specific class schedule.
- **Remarks** — free-text notes on any borrow transaction.
- **Automated overdue alerts** — scheduled task/scripts (`schedule.bat`, `send_alert.bat`) for triggering notifications on Windows-based deployments.
- **Dashboard view** — at-a-glance totals for equipment count and availability.

---

## 🗄️ Data Model (Borrow Records)

Key fields from the borrowing migration:

| Field | Type | Notes |
|-------|------|-------|
| `user_id` | FK → `users` | Borrower, cascades on delete |
| `equipment_id` | FK → `equipment` | Item borrowed, cascades on delete |
| `borrow_date` | date | When the item was checked out |
| `return_date` | date, nullable | When the item was returned |
| `quantity` | integer | Number of units borrowed |
| `purpose` | string | Reason for borrowing |
| `status` | enum | `Borrowed`, `Returned`, `Overdue` (default: `Borrowed`) |
| `remarks` | text, nullable | Additional notes |
| `class_schedule_id` | FK → `class_schedules`, nullable | Optional link to a class session, sets null on delete |

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | [Laravel 12](https://laravel.com) (PHP ^8.2) |
| Frontend build | [Vite](https://vitejs.dev) + Laravel Vite Plugin |
| Styling | Tailwind CSS (`tailwind.config.js`, PostCSS) |
| Testing | PHPUnit |
| Dev tooling | Laravel Pint, Laravel Sail, Laravel Pail, Faker, Mockery |
| Scheduled tasks | Windows batch scripts (`schedule.bat`, `send_alert.bat`) for cron-like alerting |

---

## 🚀 Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js & npm
- A database (MySQL/SQLite/PostgreSQL — configure via `.env`)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/rhondelp/cict-equipment-borrower-laravel.git
cd cict-equipment-borrower-laravel

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Copy the environment file and generate an app key
cp .env.example .env
php artisan key:generate

# 5. Configure your database credentials in .env, then run migrations
php artisan migrate

# 6. (Optional) Seed the database with sample data
php artisan db:seed
```

### Running the app

```bash
# Start the Laravel dev server
php artisan serve

# In a separate terminal, run the Vite dev server for hot-reloading assets
npm run dev
```

Visit `http://localhost:8000` in your browser.

### Building for production

```bash
npm run build
```

---

## ⏰ Scheduled Overdue Alerts

This project ships with Windows batch helpers for triggering Laravel's scheduler and dispatching overdue-equipment alerts outside of a Unix cron setup:

- **`schedule.bat`** — runs the Laravel scheduler (equivalent to `php artisan schedule:run`) on a recurring basis, intended to be wired up to Windows Task Scheduler.
- **`send_alert.bat`** — triggers the alert/notification logic for overdue borrows.

On Linux/macOS deployments, prefer a standard cron entry instead:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧹 Useful Artisan Commands

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
php artisan optimize
```

---

## 🧪 Testing

```bash
php artisan test
```

---

## 📁 Project Structure

Standard Laravel 12 directory layout:

```
app/          Application logic (models, controllers, policies, etc.)
bootstrap/    Framework bootstrap files
config/       Configuration files
database/     Migrations, factories, and seeders
public/       Publicly accessible entry point & compiled assets
resources/    Views (Blade), CSS, and JS source
routes/       Route definitions
storage/      Logs, cache, and file uploads
tests/        PHPUnit tests

schedule.bat      Windows scheduler trigger
send_alert.bat    Windows overdue-alert trigger
```

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome. Feel free to open a pull request or start a discussion.

## 📄 License

This project is built on the Laravel framework, which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). Check the repository for any project-specific license terms.
