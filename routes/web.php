<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SeatPlanController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\RoutineController;
use App\Http\Controllers\Admin\FacultySubjectController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\RoutineBuilderController;

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


Route::get('/', [AuthController::class, 'showAdminLogin'])->name('admin.login.form');
Route::post('/login/admin', [AuthController::class, 'adminLogin'])->name('admin.login');

Route::group(['middleware' => 'admin.auth'], function () {
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('admin')->group(function () {
        Route::resource('rooms', RoomController::class)->except(['show']);
        Route::resource('faculties', FacultyController::class)->except(['show']);
        Route::resource('students', StudentController::class)->except(['show']);


        Route::get('routines',            [RoutineController::class, 'index'])->name('routines.index');
        Route::get('routines/create',     [RoutineController::class, 'create'])->name('routines.create');
        Route::post('routines',           [RoutineController::class, 'store'])->name('routines.store');
        Route::get('routines/{slot}/edit', [RoutineController::class, 'edit'])->name('routines.edit');
        Route::put('routines/{slot}',     [RoutineController::class, 'update'])->name('routines.update');
        Route::delete('routines/{slot}',  [RoutineController::class, 'destroy'])->name('routines.destroy');

        Route::get('routine-presets',     [RoutineController::class, 'fetchPresets'])->name('routines.presets'); // ?semester=5
        Route::post('routine-presets',    [RoutineController::class, 'storePreset'])->name('routines.presets.store');

        Route::resource('admin/exams', ExamController::class);
        // Add this route with your other exam routes
        Route::patch('/admin/exams/{exam}/status', [ExamController::class, 'updateStatus'])->name('exams.update-status');
        Route::get('/exams/meta', [ExamController::class, 'meta'])->name('exams.meta');



        Route::get('/routine-builder', [RoutineBuilderController::class, 'create'])->name('routine.builder.create');
        Route::post('/routine-builder/generate', [RoutineBuilderController::class, 'store'])->name('routine.builder.store');
    });

    Route::get('faculty-subjects', [FacultySubjectController::class, 'index'])->name('faculty_subjects.index');
    Route::post('faculty-subjects', [FacultySubjectController::class, 'store'])->name('faculty_subjects.store');
    Route::put('faculty-subjects/{subject}', [FacultySubjectController::class, 'update'])->name('faculty_subjects.update');
    Route::delete('faculty-subjects/{subject}', [FacultySubjectController::class, 'destroy'])->name('faculty_subjects.destroy');



    Route::get('/seat-plan/create', [SeatPlanController::class, 'create'])->name('seatplan.create');
    Route::post('/seat-plan', [SeatPlanController::class, 'store'])->name('seatplan.store');
    Route::get('/seat-plan/{exam}', [SeatPlanController::class, 'show'])->name('seatplan.show');
});
