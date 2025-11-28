<?php

namespace App\Http\Controllers;
use App\Models\Admin;


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
}
