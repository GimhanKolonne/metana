<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Job Application Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Application Under Review</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $jobApplication->name }},</p>
        
        <p>Thank you for submitting your job application with us. We wanted to inform you that your CV is currently under review by our team.</p>
        
        <p>Here's what happens next:</p>
        <ol>
            <li>Our team will evaluate your application based on your qualifications and experience</li>
            <li>If your profile matches our requirements, we'll contact you for the next steps</li>
            <li>If we determine there's not a good fit at this time, we'll let you know</li>
        </ol>
        
        <p>We appreciate your interest in joining our team and thank you for your patience during the review process.</p>
        
        <p>Best regards,<br>
        The Recruitment Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>