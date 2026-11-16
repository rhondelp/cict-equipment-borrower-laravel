<?php

use App\Http\Controllers\AuthenticateUser;
use App\Http\Controllers\BorrowTransactionController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ItemRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ReturnLogsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UserController::class, 'welcome']);

Route::get('/login', [UserController::class, 'index'])->name('login');
Route::post('/login', [AuthenticateUser::class, 'login'])->name('login.store');
Route::post('/logout', [AuthenticateUser::class, 'destroy'])->name('logout');
Route::post('/register', [AuthenticateUser::class, 'registerPublic'])->name('register.store');
Route::get('/register', [AuthenticateUser::class, 'registerUser'])->name('register');

Route::get('/welcome', [UserController::class, 'welcome'])->name('auth.welcome');

Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');

Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

Route::middleware('auth')->group(function () {
    Route::middleware(['userType:Admin'])->group(function () {
        Route::get('/admin/dashboard', [AuthenticateUser::class, 'adminView'])->name('admin.dashboard');
        Route::get('/admin/equipment', [EquipmentController::class, 'index'])->name('admin.equipment');
        Route::post('/admin/equipment', [EquipmentController::class, 'store'])->name('admin.equipment.store');
        Route::post('/admin/equipment/update', [EquipmentController::class, 'update'])->name('admin.equipment.update');
        Route::post('/admin/equipment/{id}/retire', [EquipmentController::class, 'retire'])->name('admin.equipment.retire');
        Route::post('/admin/equipment/{id}/restore', [EquipmentController::class, 'restore'])->name('admin.equipment.restore');
        Route::post('/admin/equipment/{id}/maintenance', [EquipmentController::class, 'logMaintenance'])->name('admin.equipment.maintenance');
        Route::delete('/admin/equipment/{id}', [EquipmentController::class, 'destroy'])->name('admin.equipment.destroy');
        Route::get('/admin/users', [UserController::class, 'adminUser'])->name('admin.users');
        Route::post('admin/users', [AuthenticateUser::class, 'register'])->name('admin.user.register');
        Route::post('/admin/users/update', [UserController::class, 'update'])->name('admin.users.update');
        Route::post('/admin/users/add-sched', [ClassScheduleController::class, 'store'])->name('admin.add-sched');
        Route::post('/admin/users/sched/update', [ClassScheduleController::class, 'update'])->name('admin.sched.update');
        Route::delete('/admin/users/sched/{id}', [ClassScheduleController::class, 'destroy'])->name('admin.sched.destroy');
        Route::post('/admin/users/{id}/deactivate', [UserController::class, 'deactivate'])->name('admin.users.deactivate');
        Route::post('/admin/users/{id}/reactivate', [UserController::class, 'reactivate'])->name('admin.users.reactivate');
        Route::post('/admin/users/{id}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
        Route::post('/admin/users/{id}/lift-suspension', [UserController::class, 'liftSuspension'])->name('admin.users.lift');
        Route::post('/admin/users/{id}/instructor/confirm', [UserController::class, 'confirmInstructor'])->name('admin.users.instructor.confirm');
        Route::post('/admin/users/{id}/instructor/decline', [UserController::class, 'declineInstructor'])->name('admin.users.instructor.decline');
        Route::delete('/admin/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::get('/admin/transaction', [BorrowTransactionController::class, 'index'])->name('admin.transaction');
        Route::post('/admin/transaction', [BorrowTransactionController::class, 'store'])->name('admin.transaction.store');
        Route::post('/admin/transaction/update', [BorrowTransactionController::class, 'update'])->name('admin.transaction.update');
        Route::post('/admin/transaction/{id}/void', [BorrowTransactionController::class, 'void'])->name('admin.transaction.void');
        Route::delete('/admin/transaction/{id}', [BorrowTransactionController::class, 'destroy'])->name('admin.transaction.destroy');
        Route::post('/admin/transaction/check-in', [BorrowTransactionController::class, 'checkIn'])->name('admin.transaction.checkin');
        Route::post('/send-email/{id}', [BorrowTransactionController::class, 'sendManualEmail']);
        Route::get('/admin/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
        Route::get('/admin/request', [ItemRequestController::class, 'index'])->name('admin.request');
        Route::post('/admin/request/approve', [ItemRequestController::class, 'requestActions'])->name('admin.request.approve');
        Route::post('/admin/request/decline', [ItemRequestController::class, 'requestActions'])->name('admin.request.decline');

        Route::get('/admin/logs', [ReturnLogsController::class, 'index'])->name('admin.logs');
        Route::get('/admin/logs/item/{equipment}', [ReturnLogsController::class, 'itemHistory'])->name('admin.logs.item');
        Route::post('/admin/logs/{id}/resolve', [ReturnLogsController::class, 'resolve'])->name('admin.logs.resolve');
        Route::post('/admin/logs/{id}/notes', [ReturnLogsController::class, 'addNote'])->name('admin.logs.note');

        Route::get('/admin/reports', [ReportsController::class, 'index'])->name('admin.reports');
        Route::get('/admin/reports/export.csv', [ReportsController::class, 'exportCsv'])->name('admin.reports.csv');
        Route::get('/admin/reports/export.pdf', [ReportsController::class, 'exportPdf'])->name('admin.reports.pdf');
        Route::get('/admin/reports/print', [ReportsController::class, 'print'])->name('admin.reports.print');

        Route::get('/admin/send-return-alerts', [BorrowTransactionController::class, 'sendReturnAlertNotification'])
            ->name('admin.send-return-alerts');

    });

    Route::middleware(['userType:Instructor,Student'])->group(function () {
        Route::get('/borrower/dashboard', [AuthenticateUser::class, 'borrowerView'])->name('borrower.dashboard');
        Route::post('/borrower/request', [ItemRequestController::class, 'store'])->name('borrower.request.store');
        Route::put('/borrower/request', [ItemRequestController::class, 'update'])->name('borrower.request.update');
        Route::delete('/borrower/request/{id}', [ItemRequestController::class, 'destroy'])->name('borrower.request.destroy');
        Route::get('/borrower/transaction/{id}/receipt', [BorrowTransactionController::class, 'receipt'])->name('borrower.transaction.receipt');
    });

});
