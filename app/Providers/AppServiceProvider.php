<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Admin;
use App\Models\Student;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
      
        View::composer('Backend.layouts.app', function ($view) {
            $view->with('admin', Admin::find(session('admin_id')));
        });

       
    }
}
