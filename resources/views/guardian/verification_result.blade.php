<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification - Trinity University</title>
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition">
    <div class="container">
        <div class="row justify-content-center" style="margin-top: 100px;">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center" style="background-color: #141d5c; color: white;">
                        <h3 class="mb-0">Trinity University</h3>
                        <p class="mb-0">Guardian Portal</p>
                    </div>
                    <div class="card-body text-center">
                        @if($success)
                            <div class="mb-4">
                                <i class="fas fa-check-circle" style="font-size: 64px; color: #28a745;"></i>
                            </div>
                            <h4 class="mb-3">Email Verified Successfully!</h4>
                            <p class="text-muted">Thank you, {{ $guardian_name }}. Your email has been verified.</p>
                            
                            <div class="alert alert-success mt-4">
                                <p class="mb-2"><strong><i class="fas fa-envelope-open-text mr-2"></i>Access Email Sent</strong></p>
                                <p class="mb-0">An email with your Guardian Portal access link has been automatically sent to your inbox.</p>
                            </div>

                            @if(isset($access_url))
                            <div class="mt-4">
                                <a href="{{ $access_url }}" class="btn btn-lg" style="background-color: #141d5c; color: white;">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Access Guardian Portal Now
                                </a>
                            </div>
                            <p class="text-muted mt-3">
                                <small>Or check your email for the access link</small>
                            </p>
                            @endif
                        @else
                            <div class="mb-4">
                                <i class="fas fa-times-circle" style="font-size: 64px; color: #dc3545;"></i>
                            </div>
                            <h4 class="mb-3">Verification Failed</h4>
                            <p class="text-muted">{{ $message }}</p>
                            
                            <div class="alert alert-warning mt-4">
                                <p class="mb-0">This could happen if:</p>
                                <ul class="text-left mb-0">
                                    <li>The verification link has already been used</li>
                                    <li>The link has expired</li>
                                    <li>The link is invalid</li>
                                </ul>
                            </div>
                            
                            <p class="mt-4">Please contact the school administration for assistance.</p>
                        @endif
                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>&copy; {{ date('Y') }} Trinity University. All rights reserved.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>