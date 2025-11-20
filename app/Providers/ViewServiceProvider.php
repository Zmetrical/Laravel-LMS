<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\Composers\Active_Schoolyear;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Share active semester with admin layout
        View::composer(['layouts.main', 'admin.grade_management.*', 'admin.enroll_management.*'], Active_Schoolyear::class);
    }

    public function register()
    {
        //
    }
}