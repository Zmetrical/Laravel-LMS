<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - Trinity University</title>
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
        .verify-box {
            background: #f8f9fa;
            border-left: 4px solid #141d5c;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
            text-align: center;
        }
        .verify-button {
            display: inline-block;
            background: #141d5c;
            color: #ffffff !important;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 15px 0;
            font-size: 16px;
        }
        .verify-button:hover {
            background: #0f1642;
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
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Trinity University</h1>
            <p>Email Verification Required</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello, {{ $guardian_name }}!
            </div>
            
            <div class="message">
                <p>Thank you for registering as a guardian at Trinity University. Please verify your email address to access the Guardian Portal.</p>
            </div>

            <div class="verify-box">
                <p style="margin-bottom: 20px; font-size: 16px; color: #141d5c;">
                    <strong>Click the button below to verify your email:</strong>
                </p>
                <a href="{{ $verification_url }}" class="verify-button">Verify Email Address</a>
                
                <p style="margin-top: 20px; font-size: 13px; color: #666;">
                    Or copy and paste this link into your browser:
                </p>
                <div class="url-display">{{ $verification_url }}</div>
            </div>

            <div class="message">
                <p style="font-size: 13px; color: #666;">
                    If you did not request this verification or believe you received this email in error, please contact the school administration immediately.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Trinity University</strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>