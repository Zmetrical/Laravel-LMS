@extends('layouts.root')

@section('title', 'Login - Trinity Polytechnic College')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

<style>
    :root {
        --primary-color: #141d5c;
        --primary-hover: #0d133d;
        --text-dark: #2c3e50;
        --text-muted: #6c757d;
        --border-color: #d1d5db;
        --white: #ffffff;
    }
    
    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
    }
    
    .login-container {
        display: flex;
        width: 100%;
        min-height: 100vh;
    }
    
    .login-left {
        flex: 1;
        background: var(--white);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 20px 160px;
        max-width: 800px;
    }
    
    .login-right {
        flex: 1;
        background: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }
    
    .school-logo {
        width: 440px;
        height: 440px;
        background: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .school-logo img {
        max-width: 100%;
        max-height: 100%;
    }
    
    .brand {
        margin-bottom: 50px;
    }
    
    .brand h4 {
        color: var(--text-dark);
        font-size: 32px;
        font-weight: 700;
        margin: 0;
    }
    
    .login-header h1 {
        color: var(--text-dark);
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .form-group label {
        color: var(--text-dark);
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-control {
        height: 45px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(20, 29, 92, 0.15);
    }
    
    .remember-section {
        margin-bottom: 25px;
    }
    
    .custom-control-label {
        color: var(--text-dark);
        font-size: 14px;
    }
    
    .btn-signin {
        height: 45px;
        background: var(--primary-color);
        border: none;
        color: var(--white);
        font-size: 15px;
        font-weight: 600;
        width: 100%;
        border-radius: 4px;
    }
    
    .btn-signin:hover {
        background: var(--primary-hover);
        color: var(--white);
    }
    
    /* Mobile Styles - Completely Different Design */
    @media (max-width: 768px) {
        .login-container {
            flex-direction: column;
            background: var(--primary-color);
        }
        
        .login-right {
            display: none;
        }
        
        .login-left {
            max-width: 100%;
            padding: 0;
            background: transparent;
            justify-content: flex-start;
            padding-top: 40px;
        }
        
        .brand {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        
        .brand h4 {
            color: var(--white);
            font-size: 20px;
        }
        
        /* Mobile Logo Section */
        .mobile-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .mobile-logo-circle {
            width: 120px;
            height: 120px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .mobile-logo-circle img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--white);
            font-size: 22px;
        }
        
        /* Mobile Form Card */
        #loginForm {
            background: var(--white);
            border-radius: 20px 20px 0 0;
            padding: 30px 20px;
            min-height: calc(100vh - 350px);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            height: 50px;
            font-size: 16px;
            border-radius: 8px;
        }
        
        .remember-section {
            margin-bottom: 20px;
        }
        
        .btn-signin {
            height: 50px;
            border-radius: 8px;
            font-size: 16px;
            margin-top: 10px;
        }
    }
    
    /* Hide mobile elements on desktop */
    @media (min-width: 769px) {
        .mobile-logo {
            display: none;
        }
    }
</style>
@endsection

@section('body')
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="brand">
                <h4>Trinity Polytechnic College</h4>
            </div>
            
            <!-- Mobile Logo (only visible on mobile) -->
            <div class="mobile-logo">
                <div class="mobile-logo-circle">
                    <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="Trinity Logo">
                </div>
            </div>
            
            <div class="login-header">
                <h1>Student Login</h1>
            </div>
            
            <form id="loginForm" action="{{ route('student.auth') }}" method="POST" autocomplete="on">
                @csrf
                
                <div class="form-group">
                    <label for="student_number">Student Number</label>
                    <input type="text" 
                           class="form-control" 
                           id="student_number" 
                           name="student_number"
                           autocomplete="username"
                           placeholder="Enter your student number"
                           required 
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password"
                           autocomplete="current-password"
                           placeholder="Enter your password"
                           required>
                </div>
                
                <div class="remember-section">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                        <label class="custom-control-label" for="remember">Remember Me</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-signin" id="submitBtn">Sign in</button>
            </form>
        </div>
        
        <div class="login-right">
            <div class="school-logo">
                <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="Trinity Polytechnic College Logo">
            </div>
        </div>
    </div>
</body>
@endsection

@section('foot')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection