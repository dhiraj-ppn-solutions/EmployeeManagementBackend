<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 40px 20px;
        }
        .container {
            max-width: 570px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff;
            padding: 35px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #e0e7ff;
            font-weight: 500;
        }
        .content {
            padding: 40px 30px;
            color: #334155;
            line-height: 1.6;
        }
        .content h2 {
            color: #0f172a;
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 15px;
        }
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transition: all 0.2s ease;
        }
        .btn:hover {
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }
        .divider {
            border: 0;
            border-top: 1px solid #f1f5f9;
            margin: 30px 0;
        }
        .help-text {
            font-size: 13px;
            color: #64748b;
        }
        .help-text a {
            color: #4f46e5;
            text-decoration: none;
            word-break: break-all;
        }
        .help-text a:hover {
            text-decoration: underline;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
        }
        .footer p {
            margin: 0;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>Employee Management System</h1>
                <p>Verify Your Identity</p>
            </div>
            <div class="content">
                <h2>Hello {{ $user->name }},</h2>
                <p>Welcome aboard! To complete your registration and activate your administrator profile, please click the button below to verify your email address:</p>
                
                <div class="btn-container">
                    <a href="{{ $verificationUrl }}" class="btn" target="_blank">Verify Email Address</a>
                </div>
                
                <p>This verification link is required to log in to the administrator portal. If you did not create an account, no further action is required.</p>
                
                <hr class="divider">
                
                <div class="help-text">
                    <p>If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser:</p>
                    <p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>
                </div>
            </div>
            <div class="footer">
                <p>EMS Admin Panel &copy; 2026. All rights reserved.</p>
                <p>Please do not reply directly to this automated email.</p>
            </div>
        </div>
    </div>
</body>
</html>
