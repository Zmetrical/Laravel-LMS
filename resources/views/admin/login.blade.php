@extends('layouts.root')

@section('title', 'Login - Trinity Polytechnic College')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">


<style>
    :root {
        --primary-color: #343a40;
        --primary-hover: #4b545c;
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
        box-shadow: 0 0 0 0.2rem rgba(155, 135, 196, 0.15);
    }
    
    .remember-forgot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .custom-control-label {
        color: var(--text-dark);
        font-size: 14px;
    }
    
    .forgot-link {
        color: var(--text-dark);
        font-size: 14px;
        text-decoration: underline;
    }
    
    .forgot-link:hover {
        color: var(--primary-color);
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
    
    @media (max-width: 992px) {
        .login-container {
            flex-direction: column;
        }
        
        .login-left {
            max-width: 100%;
            padding: 40px 30px;
        }
        
        .login-right {
            min-height: 300px;
        }
        
        .school-logo {
            width: 250px;
            height: 250px;
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
            
            <div class="login-header">
                <h1>Login</h1>
            </div>
            
            <form id="loginForm" action="" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           id="password" name="password" required>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="remember-forgot">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                        <label class="custom-control-label" for="remember">Remember Account</label>
                    </div>
                    <a href="" class="forgot-link">Forgot password</a>
                </div>
                
                <button type="submit" class="btn btn-signin">Sign in</button>
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