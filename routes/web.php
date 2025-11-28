<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FacultySubjectController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\RoutineController;
use App\Http\Controllers\Admin\TeacherController;





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
        Route::resource('sections', SectionController::class)->except(['show']);
        Route::get('routines/meta', [RoutineController::class, 'meta'])
            ->name('admin.routines.meta');
        Route::get('routines', [RoutineController::class, 'index'])->name('admin.routines.index');
        Route::post('routines', [RoutineController::class, 'store'])->name('admin.routines.store');
        Route::get('routines/{routine}/edit', [RoutineController::class, 'edit'])->name('admin.routines.edit');
        Route::put('routines/{routine}', [RoutineController::class, 'update'])->name('admin.routines.update');
        Route::delete('routines/{routine}', [RoutineController::class, 'destroy'])->name('admin.routines.destroy');

Route::get('teachers/search', [TeacherController::class, 'search'])
    ->name('admin.teachers.search');

        Route::get('teachers',          [TeacherController::class, 'index'])->name('admin.teachers.index');
        Route::post('teachers',         [TeacherController::class, 'store'])->name('admin.teachers.store');
        Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])->name('admin.teachers.edit');
        Route::put('teachers/{teacher}',       [TeacherController::class, 'update'])->name('admin.teachers.update');
        Route::delete('teachers/{teacher}',    [TeacherController::class, 'destroy'])->name('admin.teachers.destroy');
    });


    // NEW: Student import (master students)
    Route::get('students-import', [StudentController::class, 'showImportForm'])->name('students.import.form');
    Route::post('students-import', [StudentController::class, 'import'])->name('students.import');

    // NEW: Bulk semester upgrade
    Route::get('students-upgrade', [StudentController::class, 'showUpgradeForm'])->name('students.upgrade.form');
    Route::post('students-upgrade', [StudentController::class, 'upgradeSemester'])->name('students.upgrade');




    Route::get('faculty-subjects', [FacultySubjectController::class, 'index'])->name('faculty_subjects.index');
    Route::post('faculty-subjects', [FacultySubjectController::class, 'store'])->name('faculty_subjects.store');
    Route::put('faculty-subjects/{subject}', [FacultySubjectController::class, 'update'])->name('faculty_subjects.update');
    Route::delete('faculty-subjects/{subject}', [FacultySubjectController::class, 'destroy'])->name('faculty_subjects.destroy');
});
