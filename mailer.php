<?php
// ============================================
// PRO MAILER - Single File Email Sender
// ============================================

// Load Composer
require_once __DIR__ . '/vendor/autoload.php';

use Leaf\Mail;

// ============================================
// SMTP CONFIGURATION - EDIT THESE!
// ============================================
$smtpConfig = [
    'host' => 'smtp.gmail.com',        // Your SMTP host
    'port' => 587,                      // 587 for TLS, 465 for SSL
    'security' => 'tls',               // 'tls' or 'ssl'
    'auth' => [
        'username' => 'your-email@gmail.com',  // CHANGE THIS
        'password' => 'your-app-password'      // CHANGE THIS
    ]
];

// ============================================
// PROCESS FORM SUBMISSION
// ============================================
$response = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $name = $_POST['name'] ?? 'Customer';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $template = $_POST['template'] ?? 'custom';

    // Validate
    if (empty($to) || empty($subject) || empty($message)) {
        $response = 'Please fill in all required fields.';
    } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $response = 'Please enter a valid email address.';
    } else {
        try {
            // Connect to SMTP
            Mail::connect($smtpConfig);

            // Replace template variables
            $message = str_replace('{{name}}', $name, $message);

            // Build HTML email
            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .body { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0; border-top: none; }
                    .body p { line-height: 1.6; }
                    .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 20px; padding: 10px; }
                    .badge { display: inline-block; background: #667eea; color: white; padding: 2px 12px; border-radius: 20px; font-size: 11px; }
                    hr { border: 1px solid #e0e0e0; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>📧 Pro Mailer</h1>
                        <p style='margin: 5px 0 0; opacity: 0.9;'>Professional Email Service</p>
                    </div>
                    <div class='body'>
                        <p><strong>Dear " . htmlspecialchars($name) . ",</strong></p>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        <hr>
                        <p style='font-size: 13px; color: #666;'>This email was sent using Pro Mailer.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2026 Pro Mailer. All rights reserved. <span class='badge'>Secure</span></p>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Send email
            Mail::send([
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
                'text' => $message
            ]);

            $response = "✅ Email sent successfully to <strong>" . htmlspecialchars($to) . "</strong>";
            $success = true;

        } catch (Exception $e) {
            $response = "❌ SMTP Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Mailer - Send Professional Emails</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 580px;
            width: 100%;
            padding: 40px;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 4px;
        }
        .header h1 span { background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .header p {
            color: #888;
            font-size: 14px;
        }
        .badge-header {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2px 14px;
            border-radius: 20px;
            font-size: 11px;
            margin-top: 6px;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: <?php echo $response ? 'block' : 'none'; ?>;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .form-group label .required {
            color: #e74c3c;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 11px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
            background: #fafafa;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }
        .form-group textarea {
            min-height: 110px;
            resize: vertical;
        }
        .template-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .template-btn {
            padding: 10px;
            border: 2px solid #e8e8e8;
            background: #fafafa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
            font-weight: 500;
            color: #555;
            text-align: center;
        }
        .template-btn:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .template-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        .btn-send {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 5px;
        }
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .btn-send:active {
            transform: translateY(0);
        }
        .btn-send:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-send .icon { margin-right: 8px; }
        .spinner {
            display: none;
            text-align: center;
            margin: 15px 0;
        }
        .spinner.active { display: block; }
        .spinner div {
            width: 35px;
            height: 35px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #ccc;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #2ecc71;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        <?php if ($success): ?>
        .container { border: 3px solid #2ecc71; }
        <?php elseif ($response && !$success): ?>
        .container { border: 3px solid #e74c3c; }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 <span>Pro Mailer</span></h1>
            <p>Send professional emails to your customers</p>
            <span class="badge-header"><span class="status-dot"></span> SMTP Ready</span>
        </div>

        <?php if ($response): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $response; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="emailForm">
            <div class="form-group">
                <label for="to">Recipient Email <span class="required">*</span></label>
                <input type="email" id="to" name="to" placeholder="customer@example.com" value="<?php echo $_POST['to'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="name">Recipient Name</label>
                <input type="text" id="name" name="name" placeholder="John Doe" value="<?php echo $_POST['name'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="subject">Subject <span class="required">*</span></label>
                <input type="text" id="subject" name="subject" placeholder="Your Invoice #12345" value="<?php echo $_POST['subject'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Quick Templates</label>
                <div class="template-grid">
                    <button type="button" class="template-btn" data-subject="Your Invoice #12345" data-msg="Dear {{name}},\n\nThank you for your business. Your invoice #12345 is attached.\n\nTotal Amount: $499.99\nDue Date: July 15, 2026\n\nBest regards,\nYour Company">📄 Invoice</button>
                    <button type="button" class="template-btn" data-subject="Welcome to Our Service!" data-msg="Dear {{name}},\n\nWelcome to our community! We're excited to have you on board.\n\nHere's what you can expect:\n- 24/7 support\n- Regular updates\n- Exclusive offers\n\nLet us know if you need any assistance.\n\nBest regards,\nYour Company">👋 Welcome</button>
                    <button type="button" class="template-btn" data-subject="Monthly Newsletter - July 2026" data-msg="Hello {{name}},\n\nHere's your monthly newsletter with the latest updates:\n\n📌 Feature Update: New dashboard\n📌 Tip: How to maximize your results\n📌 Coming soon: Mobile app\n\nRead more on our blog.\n\nBest regards,\nYour Company">📰 Newsletter</button>
                    <button type="button" class="template-btn active" data-subject="" data-msg="">✏️ Custom</button>
                </div>
            </div>

            <div class="form-group">
                <label for="message">Message <span class="required">*</span></label>
                <textarea id="message" name="message" placeholder="Your message here..." required><?php echo $_POST['message'] ?? "Dear Customer,\n\nThank you for your business. Your invoice #12345 is attached.\n\nBest regards,\nYour Company"; ?></textarea>
            </div>

            <input type="hidden" name="template" id="templateInput" value="custom">

            <button type="submit" class="btn-send" id="sendBtn">
                <span class="icon">📤</span> Send Email
            </button>
        </form>

        <div class="spinner" id="spinner">
            <div></div>
            <p style="margin-top: 8px; color: #888; font-size: 13px;">Sending your email...</p>
        </div>

        <div class="footer">
            <p>⚡ Powered by Leaf PHP Mailer &bull; Your emails are sent securely via SMTP</p>
        </div>
    </div>

    <script>
        // Template buttons
        document.querySelectorAll('.template-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.template-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const subject = this.dataset.subject;
                const msg = this.dataset.msg;

                document.getElementById('subject').value = subject;
                document.getElementById('message').value = msg;

                // Set template value
                const templateMap = {
                    '📄 Invoice': 'invoice',
                    '👋 Welcome': 'welcome',
                    '📰 Newsletter': 'newsletter',
                    '✏️ Custom': 'custom'
                };
                document.getElementById('templateInput').value = templateMap[this.textContent.trim()] || 'custom';
            });
        });

        // Form submit with spinner
        document.getElementById('emailForm').addEventListener('submit', function() {
            const btn = document.getElementById('sendBtn');
            const spinner = document.getElementById('spinner');
            btn.disabled = true;
            btn.innerHTML = '⏳ Sending...';
            spinner.classList.add('active');
        });

        // Auto-hide alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            }
        }, 1000);
    </script>
</body>
</html>
