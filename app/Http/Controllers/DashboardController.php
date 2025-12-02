<?php

namespace App\Http\Controllers;
use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;


class DashboardController extends Controller
{
    public function home()
    {
        return view('Frontend.index');
    }

    public function admin()
    {
        $admin = Admin::find(session('admin_id'));
 
      return view('Backend.dashboard.index', compact('admin'));
     }
     public function student()
    {
        $student = Student::find(session('student_id'));

        // You can change this view path as you like
        return view('Frontend.dashboard.student', compact('student'));
    }

    public function teacher()
    {
        $teacher = Teacher::find(session('teacher_id'));

        // You can change this view path as you like
        return view('Frontend.dashboard.teacher', compact('teacher'));
    }
}
