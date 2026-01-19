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
            margin: 0 0 10px 0;
            color: #141d5c;
            font-size: 15px;
        }
        .student-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .student-item:last-child {
            border-bottom: none;
        }
        .student-name {
            font-weight: bold;
            color: #333;
        }
        .student-number {
            color: #666;
            font-size: 13px;
        }
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 15px;
        }
        .info-box ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 5px 0;
            color: #856404;
            font-size: 14px;
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
        .url-display {
            word-break: break-all;
            background: #fff;
            padding: 10px;
            border: 1px dashed #ccc;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            color: #666;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Trinity University</h1>
            <p>Guardian Portal Access</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello, {{ $guardian_name }}!
            </div>
            
            <div class="message">
                <p>Welcome to Trinity University's Guardian Portal! You have been granted access to monitor the academic progress of your student(s).</p>
            </div>

            @if(count($students) > 0)
            <div class="students-list">
                <h4>Your Students:</h4>
                @foreach($students as $student)
                <div class="student-item">
                    <div class="student-name">{{ $student->first_name }} {{ $student->last_name }}</div>
                    <div class="student-number">Student Number: {{ $student->student_number }}</div>
                </div>
                @endforeach
            </div>
            @endif

            <div class="access-box">
                <h3>Access Your Portal</h3>
                <p>Click the button below to access the Guardian Portal:</p>
                <a href="{{ $access_url }}" class="access-link">Access Guardian Portal</a>
                
                <p style="margin-top: 15px; font-size: 13px; color: #666;">
                    Or copy and paste this link into your browser:
                </p>
                <div class="url-display">{{ $access_url }}</div>
            </div>

            <div class="info-box">
                <h4>Important Information:</h4>
                <ul>
                    <li><strong>No Password Required:</strong> Your unique link provides direct access to the portal</li>
                    <li><strong>Keep this link secure:</strong> Anyone with this link can view your students' grades</li>
                    <li><strong>Bookmark for easy access:</strong> Save this link for future use</li>
                    <li><strong>No expiration:</strong> This link remains active unless deactivated by the school</li>
                </ul>
            </div>

            <div class="message">
                <p><strong>What you can do in the Guardian Portal:</strong></p>
                <ul style="padding-left: 20px;">
                    <li>View your students' grades across all subjects</li>
                    <li>Monitor quarterly and final grades</li>
                    <li>Track academic performance over time</li>
                    <li>Access grade history by semester</li>
                </ul>
            </div>

            <div class="message">
                <p>If you have any questions or concerns about your students' academic progress, please don't hesitate to contact the school.</p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Trinity University</strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>If you did not request this access or believe you received this email in error, please contact the school administration immediately.</p>
        </div>
    </div>
</body>
</html>