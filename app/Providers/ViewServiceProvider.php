<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\Composers\Active_Schoolyear;
use App\Http\Composers\Student_Classes;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Share active semester with admin layout
        View::composer(['layouts.main', 'admin.grade_management.*', 'admin.enroll_management.*', 
        'layouts.main-teacher',
        'layouts.main-student'
        ], Active_Schoolyear::class);

        View::composer('layouts.main-student', Student_Classes::class);

    }

    public function register()
    {
        //
    }
}