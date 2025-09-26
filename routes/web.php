<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AttendanceController, PaymentController, ShiftController, EnrollmentController, SectionController, ChildController, ReceptionController, RoomController, SectionPackageController, ReceptionSettingController, ReportController};
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;

use App\Http\Controllers\SectionMembersController;


Route::middleware(['auth','role:Receptionist|Admin'])->group(function(){
    Route::get('/sections/{section}/members',[SectionMembersController::class,'index'])->name('sections.members.index');
    Route::get('/sections/{section}/members/search',[SectionMembersController::class,'search'])->name('sections.members.search');
    Route::post('/sections/{section}/members',[SectionMembersController::class,'store'])->name('sections.members.store');
});

Route::get('/', function(){ return view('welcome'); });

Route::middleware(['auth','role:Admin'])->group(function(){
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('/reception/settings', [ReceptionSettingController::class,'index'])->name('reception.settings');
    Route::put('/reception/settings/{user}', [ReceptionSettingController::class,'update'])->name('reception.settings.update');
    Route::get('/reports', [ReportController::class,'index'])->name('reports.index');
});

Route::middleware(['auth'])->group(function(){
    Route::get('/account','App\\Http\\Controllers\\AccountController@index')->name('account.index');
    Route::post('/account/password','App\\Http\\Controllers\\AccountController@changePassword')->name('account.password');
});

Route::middleware(['auth'])->group(function(){
    Route::view('/dashboard','dashboard')->name('dashboard');


// Смены ресепшена
    Route::post('/shift/start',[ShiftController::class,'start'])->name('shift.start');
    Route::post('/shift/stop',[ShiftController::class,'stop'])->name('shift.stop');


// Посещения
    Route::post('/attendances',[AttendanceController::class,'store'])->name('attendances.store')->middleware('shift.active');


// Платежи
    Route::post('/payments',[PaymentController::class,'store'])->name('payments.store')->middleware('shift.active');


// Enrollment: прикрепление ребёнка к секции/пакету
    Route::post('/enrollments',[EnrollmentController::class,'store'])->name('enrollments.store');


    Route::middleware(['role:Admin'])->group(function(){
        Route::resource('sections', SectionController::class)->except(['show']);
        Route::prefix('sections/{section}')->name('sections.')->group(function(){
            Route::get('packages', [SectionPackageController::class,'index'])->name('packages.index');
            Route::get('packages/create', [SectionPackageController::class,'create'])->name('packages.create');
            Route::post('packages', [SectionPackageController::class,'store'])->name('packages.store');
            Route::get('packages/{package}/edit', [SectionPackageController::class,'edit'])->name('packages.edit');
            Route::put('packages/{package}', [SectionPackageController::class,'update'])->name('packages.update');
            Route::delete('packages/{package}', [SectionPackageController::class,'destroy'])->name('packages.destroy');
        });
        Route::resource('rooms', RoomController::class);
    });


    Route::middleware(['role:Receptionist|Admin'])->group(function(){
        Route::resource('children', ChildController::class)->except(['destroy']);
        Route::post('children/{child}/deactivate',[ChildController::class,'deactivate'])->name('children.deactivate');
        Route::post('children/{child}/activate',[ChildController::class,'activate'])->name('children.activate');
        Route::get('/reception',[ReceptionController::class,'index'])->name('reception.index');
        Route::post('/reception/mark',[ReceptionController::class,'mark'])->name('reception.mark')->middleware('shift.active');
        Route::post('/reception/renew',[ReceptionController::class,'renew'])->name('reception.renew');
    });
});


require __DIR__.'/auth.php';
