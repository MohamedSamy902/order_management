<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9fafb;
            border-radius: 8px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #4F46E5;
            letter-spacing: 10px;
            text-align: center;
            padding: 25px;
            background: #ffffff;
            border: 2px dashed #4F46E5;
            border-radius: 8px;
            margin: 25px 0;
        }
        .info {
            background: #EEF2FF;
            border-left: 4px solid #4F46E5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #6B7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Email Verification</h2>
        </div>

        <p>Hi <strong>{{ $userName }}</strong>,</p>

        <p>Thank you for registering! To complete your registration, please verify your email address using the OTP code below:</p>

        <div class="otp-code">
            {{ $otp }}
        </div>

        <div class="info">
            <strong>⏱️ This code will expire in 10 minutes.</strong>
        </div>

        <p>If you didn't create an account, please ignore this email and no further action is required.</p>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
