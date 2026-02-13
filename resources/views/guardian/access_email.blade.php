<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian Portal Access - Trinity University</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: #141d5c;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #141d5c;
        }
        .message {
            margin-bottom: 25px;
            font-size: 15px;
        }
        .access-box {
            background: #f8f9fa;
            border-left: 4px solid #141d5c;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .access-box h3 {
            margin: 0 0 15px 0;
            color: #141d5c;
            font-size: 16px;
        }
        .access-link {
            display: inline-block;
            background: #141d5c;
            color: #ffffff !important;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 0;
        }
        .access-link:hover {
            background: #0f1642;
        }
        .students-list {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .students-list h4 {
            margin: 0 0 15px 0;
            color: #141d5c;
            font-size: 15px;
        }
        .student-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 15px;
            background: #fafafa;
        }
        .student-item:last-child {
            margin-bottom: 0;
        }
        .student-name {
            font-weight: bold;
            color: #141d5c;
            font-size: 15px;
            margin-bottom: 8px;
        }
        .student-info {
            margin: 5px 0;
            font-size: 14px;
        }
        .student-info strong {
            color: #666;
            display: inline-block;
            width: 120px;
        }
        .credential-box {
            background: #fff;
            border: 1px dashed #141d5c;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
        }
        .credential-item {
            font-family: monospace;
            font-size: 13px;
            margin: 5px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            margin: 5px 0;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box p {
            margin: 5px 0;
            color: #856404;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Trinity Polytechic College</h1>
            <p>Guardian Portal Access</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello, {{ $guardian_name }}!
            </div>
            
            <div class="message">
                <p>You have been granted access to Trinity's Guardian Portal.</p>
            </div>

            <div class="access-box">
                <h3>Guardian Portal Access</h3>
                <a href="{{ $access_url }}" class="access-link">Access Guardian Portal</a>
            </div>

            @if(count($students) > 0)
            <div class="students-list">
                <h4>Student Information & Credentials</h4>
                @foreach($students as $student)
                <div class="student-item">
                    <div class="student-name">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</div>
                    <div class="student-info">
                        <strong>Student Number:</strong> {{ $student->student_number }}
                    </div>
                    
                    @if($student->plain_password)
                    <div class="credential-box">
                        <div class="credential-item">
                            <strong>Student Portal:</strong> <a href="{{ $student_portal_url }}">{{ $student_portal_url }}</a>
                        </div>
                        <div class="credential-item">
                            <strong>Username:</strong> {{ $student->student_number }}
                        </div>
                        <div class="credential-item">
                            <strong>Password:</strong> {{ $student->plain_password }}
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            <div class="message">
                <p><strong>Trinity Polytechic College</strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Trinity Polytechic College. All rights reserved.</p>
        </div>
    </div>
</body>
</html>