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
use App\Http\Controllers\Student\ClassFeedbackController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Admin\CrRoleController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DepartmentRoleController;

use function Symfony\Component\String\u;

use App\Http\Controllers\Admin\AttendanceAnalyticsController;
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


Route::get('/', function () {
    return view('welcome');
});
// STUDENT AUT


Route::get('/login/student', [AuthController::class, 'showStudentLogin'])->name('student.login.form');
Route::post('/login/student', [AuthController::class, 'studentLogin'])->name('student.login');


// TEACHER AUTH
Route::get('/login/teacher', [AuthController::class, 'showTeacherLogin'])->name('teacher.login.form');
Route::post('/login/teacher', [AuthController::class, 'teacherLogin'])->name('teacher.login');


Route::get('/login/admin', [AuthController::class, 'showAdminLogin'])->name('admin.login.form');
Route::post('/login/admin', [AuthController::class, 'adminLogin'])->name('admin.login');





// STUDENT AREA - FIXED ROUTE ORDER
Route::group(['middleware' => 'student.auth'], function () {
    Route::get('/dashboard/student', [StudentDashboardController::class, 'index'])
        ->name('student.dashboard');

    Route::get('/student/routine', [StudentDashboardController::class, 'routine'])
        ->name('student.routine');

    Route::post('/student/class-feedback', [ClassFeedbackController::class, 'store'])
        ->name('student.class_feedback.store');

    // ✅ PUT SPECIFIC ROUTES BEFORE DYNAMIC ROUTES
    // This MUST come before the {routine} route
    Route::post('/student/routine-feedback/bulk', [StudentDashboardController::class, 'bulkUpdate'])
        ->name('student.routine-feedback.bulk');

    // This MUST come after the /bulk route
    Route::post('/student/routine-feedback/{routine}', [StudentDashboardController::class, 'storeFeedback'])
        ->name('student.routine-feedback.store');


    // Student forced password update
    Route::post(
        '/student/force-password-update',
        [AuthController::class, 'forceStudentPasswordUpdate']
    )->name('student.force.password.update');

    Route::post('/logout/student', [AuthController::class, 'logoutStudent'])
        ->name('student.logout');
});














Route::group(['middleware' => 'teacher.auth'], function () {
    Route::get('/dashboard/teacher', [TeacherDashboardController::class, 'index'])
        ->name('teacher.dashboard');

    // You can keep this GET if you still need a separate "show" page:
    Route::get('/teacher/attendance/{routine}', [AttendanceController::class, 'show'])
        ->name('teacher.attendance.show');

    // ✅ NEW: no {routine} here
    Route::post('/teacher/attendance', [AttendanceController::class, 'store'])
        ->name('teacher.attendance.store');


    Route::post('/teacher/update-password', [AuthController::class, 'forceTeacherPasswordUpdate'])
        ->name('teacher.force.password.update');

    Route::post('/logout/teacher', [AuthController::class, 'logoutTeacher'])
        ->name('teacher.logout');
});









Route::group(['middleware' => 'admin.auth'], function () {
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



        // CR / VCR assignment routes
        Route::get('cr-roles', [CrRoleController::class, 'index'])
            ->name('admin.cr_roles.index');

        Route::post('cr-roles', [CrRoleController::class, 'save'])
            ->name('admin.cr_roles.save');
    });



Route::prefix('admin/departments')
    ->name('admin.departments.')
    ->group(function () {
        Route::get('/', [DepartmentRoleController::class, 'index'])->name('index');

        // NEW: create department
        Route::post('/', [DepartmentRoleController::class, 'store'])->name('store');

        Route::get('/{department}/edit-roles', [DepartmentRoleController::class, 'editRoles'])->name('editRoles');
        Route::post('/{department}/roles', [DepartmentRoleController::class, 'storeRoles'])->name('storeRoles');
    });






    
Route::get('/analytics/dashboard', [AttendanceAnalyticsController::class, 'index'])->name('analytics.index');
Route::get('/analytics/data', [AttendanceAnalyticsController::class, 'data'])->name('analytics.data');

    Route::prefix('admin/analytics/attendance')
        ->name('admin.analytics.attendance.')
        ->group(function () {
            Route::get('/',        [AttendanceAnalyticsController::class, 'index'])->name('index');
            Route::get('/data',    [AttendanceAnalyticsController::class, 'data'])->name('data');
            Route::get('/export',  [AttendanceAnalyticsController::class, 'export'])->name('export');

            // dependent dropdown endpoints
            Route::get('/sections', [AttendanceAnalyticsController::class, 'sections'])->name('sections');
            Route::get('/subjects', [AttendanceAnalyticsController::class, 'subjects'])->name('subjects');
            Route::get('/students', [AttendanceAnalyticsController::class, 'students'])->name('students');
            Route::get('analytics/attendance/teachers', [AttendanceAnalyticsController::class, 'teachers'])
                ->name('teachers');
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
