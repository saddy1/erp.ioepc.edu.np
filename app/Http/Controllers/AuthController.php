<?php

namespace App\Http\Controllers;


use App\Models\Admin;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use App\Models\StudentRole;
use App\Models\Attendance; // if needed later



class AuthController extends Controller
{
    // STUDENT
    public function showStudentLogin()

    {
        if (session()->has('student_id')) {
            return redirect()->route('student.dashboard');
        }
        return view('Frontend.auth.student-login');
    }




    // ADMIN
    public function showAdminLogin()
    {
        if (session()->has('admin_id')) {
            return redirect()->route('admin.analytics.attendance.index');
        }
        return view('Backend.index');
    }


    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);


        $admin = Admin::where('email', $request->email)->first();
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return back()->withInput()->with('error', 'Invalid credentials.');
        }


        session(['admin_id' => $admin->id]);
        return redirect()->route('admin.analytics.attendance.index');
    }



    // STUDENT

    public function studentLogin(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $student = Student::where('email', $data['email'])->first();

        if (!$student || !$student->password || !Hash::check($data['password'], $student->password)) {
            return back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        // later we can enforce CR/VCR only + must_change_password
        session(['student_id' => $student->id]);

        return redirect()->route('student.dashboard');
    }

    // ========== TEACHER LOGIN ==========
    public function showTeacherLogin()
    {
        if (session()->has('teacher_id')) {
            return redirect()->route('teacher.dashboard');
        }
        return view('Frontend.auth.teacher-login');
    }

    public function teacherLogin(Request $request)
    {
        $request->validate([
            'email'     => ['required', 'string'],   // or email, your choice
            'password' => ['required', 'string', 'min:6'],
        ]);

        $teacher = Teacher::where('email', $request->email)->first();

        if (!$teacher || !$teacher->is_active) {
            return back()->withInput()->with('error', 'Account disabled or not found.');
        }

        if (!$teacher->password || !Hash::check($request->password, $teacher->password)) {
            return back()->withInput()->with('error', 'Invalid credentials.');
        }

        session(['teacher_id' => $teacher->id]);
        return redirect()->route('teacher.dashboard');
    }

    public function forcePasswordUpdate(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $teacherId = session('teacher_id');

        if (!$teacherId) {
            return redirect()->route('teacher.login')->with('error', 'Unauthorized.');
        }

        $teacher = Teacher::find($teacherId);

        if (!$teacher) {
            return redirect()->route('teacher.login')->with('error', 'Teacher not found.');
        }

        $teacher->password = bcrypt($request->password);
        $teacher->must_change_password = 0;
        $teacher->save();

        session()->forget(['teacher_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

return redirect()->route('teacher.login')
                     ->with('success', 'Password updated. Please login again.');    }

    public function logoutStudent(Request $request)
    {
        session()->forget(['student_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login.form')->with('ok', 'Logged out successfully.');
    }
    public function logoutTeacher(Request $request)
    {
        session()->forget(keys: ['teacher_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login.form')->with('ok', 'Logged out successfully.');
    }
    public function logout()
    {
        session()->forget(keys: ['admin_id']);
        return redirect()->route('admin.login.form')->with('success', 'Logged out successfully.');
    }
}
