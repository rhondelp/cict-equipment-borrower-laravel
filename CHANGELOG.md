# Changelog

## 2026-09-18

- Removed dead controller methods — no behavior change. Deleted `EquipmentController::availableEquipment()`, `AuthenticateUser::studentView()` and `UserController::show()`, none of which were reachable from `routes/web.php` or referenced by any view. No `use` imports became unused, so none were removed. `route:list` is byte-identical (35 routes) and the suite passes against the committed baseline. Files touched: `app/Http/Controllers/EquipmentController.php`, `app/Http/Controllers/AuthenticateUser.php`, `app/Http/Controllers/UserController.php`, `CHANGELOG.md`.
- Documentation baseline created
