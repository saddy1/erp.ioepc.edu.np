<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FacultySubjectController;




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/login/admin', [AuthController::class, 'showAdminLogin'])->name('admin.login.form');
Route::post('/login/admin', [AuthController::class, 'adminLogin'])->name('admin.login');

Route::group(['middleware' => 'admin.auth'], function () {
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('admin')->group(function () {
        Route::resource('rooms', RoomController::class)->except(['show']);
        Route::resource('faculties', FacultyController::class)->except(['show']);
       
        Route::get('rooms/exam-seat-plan', [RoomController::class, 'examSeatPlan'])
            ->name('rooms.exam_seat_plan');

        Route::get('rooms/exam-seat-plan/print', [RoomController::class, 'examSeatPlanPrint'])
            ->name('rooms.exam_seat_plan.print');
      
    });

    Route::get('faculty-subjects', [FacultySubjectController::class, 'index'])->name('faculty_subjects.index');
    Route::post('faculty-subjects', [FacultySubjectController::class, 'store'])->name('faculty_subjects.store');
    Route::put('faculty-subjects/{subject}', [FacultySubjectController::class, 'update'])->name('faculty_subjects.update');
    Route::delete('faculty-subjects/{subject}', [FacultySubjectController::class, 'destroy'])->name('faculty_subjects.destroy');
});
