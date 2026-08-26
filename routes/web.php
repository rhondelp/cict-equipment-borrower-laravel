<?php

use App\Http\Controllers\AuthenticateUser;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\BorrowTransactionController;
use App\Http\Controllers\ItemRequestController;
use App\Http\Controllers\ReturnLogsController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [User::class, 'index'])->name('login');
Route::post('/login', [AuthenticateUser::class, 'login'])->name('login.store');
Route::post('/logout', [AuthenticateUser::class, 'destroy'])->name('logout');
Route::post('/register', [AuthenticateUser::class, 'register'])->name('register');
Route::get('/register', [AuthenticateUser::class, 'registerUser'])->name('register');

Route::get('/welcome', function () {
    return view('welcome');
})->name('auth.welcome');



Route::middleware('auth')->group(function () {
    Route::middleware(['userType:Admin'])->group(function () {
        Route::get('/admin/dashboard', [AuthenticateUser::class, 'adminView'])->name('admin.dashboard');
        Route::get('/admin/equipment', [EquipmentController::class, 'index'])->name('admin.equipment');
        Route::post('/admin/equipment', [EquipmentController::class, 'store'])->name('admin.equipment.store');
        Route::post('/admin/equipment/update', [EquipmentController::class, 'update'])->name('admin.equipment.update');
        Route::delete('/admin/equipment/{id}', [EquipmentController::class, 'destroy'])->name('admin.equipment.destroy');
        Route::get('/admin/users', [User::class, 'adminUser'])->name('admin.users');
        Route::post('admin/users', [AuthenticateUser::class, 'register'])->name('admin.user.register');
        Route::post('/admin/users/update', [User::class, 'update'])->name('admin.users.update');
        Route::post('/admin/users/add-sched', [ClassScheduleController::class, 'store'])->name('admin.add-sched');
        Route::delete('/admin/users/{id}', [User::class, 'destroy'])->name('admin.users.destroy');
        Route::get('/admin/transaction', [BorrowTransactionController::class, 'index'])->name('admin.transaction');
        Route::post('/admin/transaction', [BorrowTransactionController::class, 'store'])->name('admin.transaction.store');
        Route::post('/admin/transaction/update', [BorrowTransactionController::class, 'update'])->name('admin.transaction.update');
        Route::delete('/admin/transaction/{id}', [BorrowTransactionController::class, 'destroy'])->name('admin.transaction.destroy');
        Route::post('/admin/transaction/inline-update', [BorrowTransactionController::class, 'inlineUpdate'])->name('transactions.inlineUpdate');
        Route::post('/send-email/{id}', [BorrowTransactionController::class, 'sendManualEmail']);
        Route::get('/admin/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
        Route::get('/admin/request', [ItemRequestController::class, 'index'])->name('admin.request');
        Route::post('/admin/request/approve', [ItemRequestController::class, 'requestActions'])->name('admin.request.approve');
        Route::post('/admin/request/decline', [ItemRequestController::class, 'requestActions'])->name('admin.request.decline');

        Route::get('/admin/logs', [ReturnLogsController::class, 'index'])->name('admin.logs');

        // Admin-only mail utilities (previously public)
        Route::get('/admin/send-return-alerts', [BorrowTransactionController::class, 'sendReturnAlertNotification'])
            ->name('admin.send-return-alerts');

    });

    Route::middleware(['userType:Instructor,Student'])->group(function () {
        Route::get('/borrower/dashboard', [AuthenticateUser::class, 'borrowerView'])->name('borrower.dashboard');
        Route::post('/borrower/request', [ItemRequestController::class, 'store'])->name('borrower.request.store');
        Route::put('/borrower/request', [ItemRequestController::class, 'update'])->name('borrower.request.update');
        Route::delete('/borrower/request/{id}', [ItemRequestController::class, 'destroy'])->name('borrower.request.destroy');
    });


});
