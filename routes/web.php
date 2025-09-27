<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReceptionSettingController;
use App\Http\Controllers\ReceptionSummaryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SectionMembersController;
use App\Http\Controllers\SectionPackageController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'restrict.receptionist', 'role:Receptionist|Admin'])->group(function () {
    Route::get('/sections/{section}/members', [SectionMembersController::class, 'index'])->name('sections.members.index');
    Route::get('/sections/{section}/members/search', [SectionMembersController::class, 'search'])->name('sections.members.search');
    Route::post('/sections/{section}/members', [SectionMembersController::class, 'store'])->name('sections.members.store');
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('/reception/summary', [ReceptionSummaryController::class, 'index'])->name('reception.summary');
    Route::get('/reception/settings', [ReceptionSettingController::class, 'index'])->name('reception.settings');
    Route::put('/reception/settings/{user}', [ReceptionSettingController::class, 'update'])->name('reception.settings.update');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', 'restrict.receptionist'])->group(function () {
    Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::post('/account/password', [AccountController::class, 'changePassword'])->name('account.password');
});

Route::middleware(['auth', 'restrict.receptionist'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Смены ресепшена
    Route::post('/shift/start', [ShiftController::class, 'start'])->name('shift.start')->middleware('role:Receptionist');
    Route::post('/shift/stop', [ShiftController::class, 'stop'])->name('shift.stop')->middleware('role:Receptionist');

    // Посещения
    Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store')->middleware(['shift.active', 'role:Receptionist']);

    // Платежи
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store')->middleware(['shift.active', 'role:Receptionist']);

    // Enrollment: прикрепление ребёнка к секции/пакету
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');

    Route::middleware(['role:Admin'])->group(function () {
        Route::resource('sections', SectionController::class)->except(['index', 'show']);
        Route::prefix('sections/{section}')->name('sections.')->group(function () {
            Route::get('packages', [SectionPackageController::class, 'index'])->name('packages.index');
            Route::get('packages/create', [SectionPackageController::class, 'create'])->name('packages.create');
            Route::post('packages', [SectionPackageController::class, 'store'])->name('packages.store');
            Route::get('packages/{package}/edit', [SectionPackageController::class, 'edit'])->name('packages.edit');
            Route::put('packages/{package}', [SectionPackageController::class, 'update'])->name('packages.update');
            Route::delete('packages/{package}', [SectionPackageController::class, 'destroy'])->name('packages.destroy');
        });
        Route::resource('rooms', RoomController::class);
    });

    Route::middleware(['role:Receptionist|Admin'])->group(function () {
        Route::resource('children', ChildController::class);
        Route::post('children/{child}/deactivate', [ChildController::class, 'deactivate'])->name('children.deactivate');
        Route::post('children/{child}/activate', [ChildController::class, 'activate'])->name('children.activate');
        Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');
        Route::post('/reception/mark', [ReceptionController::class, 'mark'])->name('reception.mark')->middleware(['shift.active', 'role:Receptionist']);
        Route::post('/reception/renew', [ReceptionController::class, 'renew'])->name('reception.renew')->middleware('role:Receptionist');
    });
});

require __DIR__ . '/auth.php';
