<?php
// ============================================
// PRO BULK MAILER - Email Campaign Manager
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer
require_once __DIR__ . '/vendor/autoload.php';
use Leaf\Mail;

// ============================================
// CONFIGURATION STORAGE
// ============================================
$configFile = __DIR__ . '/smtp_config.json';
$config = [];

// Load existing config
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
}

// ============================================
// PROCESS FORM SUBMISSIONS
// ============================================
$response = null;
$success = false;
$activeTab = 'send'; // send, config, history

// Handle SMTP Configuration
if (isset($_POST['save_smtp'])) {
    $config = [
        'host' => trim($_POST['smtp_host']),
        'port' => intval($_POST['smtp_port']),
        'security' => $_POST['smtp_security'],
        'username' => trim($_POST['smtp_username']),
        'password' => trim($_POST['smtp_password']),
        'from_email' => trim($_POST['from_email']),
        'from_name' => trim($_POST['from_name'])
    ];
    
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    $response = "✅ SMTP configuration saved successfully!";
    $success = true;
    $activeTab = 'send';
}

// Handle Email Sending
if (isset($_POST['send_emails']) && !empty($config)) {
    $recipients = array_filter(array_map('trim', explode("\n", $_POST['recipients'])));
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $campaign_name = trim($_POST['campaign_name']) ?: 'Campaign ' . date('Y-m-d H:i');
    
    if (empty($recipients) || empty($subject) || empty($message)) {
        $response = "❌ Please fill in all required fields.";
    } else {
        try {
            // Connect to SMTP
            Mail::connect([
                'host' => $config['host'],
                'port' => $config['port'],
                'security' => $config['security'],
                'auth' => [
                    'username' => $config['username'],
                    'password' => $config['password']
                ]
            ]);
            
            $sent = 0;
            $failed = 0;
            $log = [];
            
            foreach ($recipients as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $failed++;
                    continue;
                }
                
                try {
                    // Build HTML email
                    $html = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center; }
                            .body { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0; border-top: none; }
                            .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>📧 " . htmlspecialchars($config['from_name'] ?? 'Pro Mailer') . "</h1>
                            </div>
                            <div class='body'>
                                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                                <hr style='margin: 20px 0;'>
                                <p style='font-size: 13px; color: #666;'>This email was sent to " . htmlspecialchars($email) . "</p>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " " . htmlspecialchars($config['from_name'] ?? 'Pro Mailer') . "</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    Mail::send([
                        'to' => $email,
                        'subject' => $subject,
                        'html' => $html,
                        'text' => $message,
                        'from' => [
                            'email' => $config['from_email'] ?? $config['username'],
                            'name' => $config['from_name'] ?? 'Pro Mailer'
                        ]
                    ]);
                    
                    $sent++;
                    $log[] = "✅ Sent to: $email";
                    
                } catch (Exception $e) {
                    $failed++;
                    $log[] = "❌ Failed: $email - " . $e->getMessage();
                }
            }
            
            // Save history
            $history = [];
            if (file_exists(__DIR__ . '/history.json')) {
                $history = json_decode(file_get_contents(__DIR__ . '/history.json'), true) ?: [];
            }
            
            $history[] = [
                'date' => date('Y-m-d H:i:s'),
                'campaign' => $campaign_name,
                'subject' => $subject,
                'recipients' => count($recipients),
                'sent' => $sent,
                'failed' => $failed,
                'log' => $log
            ];
            
            file_put_contents(__DIR__ . '/history.json', json_encode($history, JSON_PRETTY_PRINT));
            
            $response = "✅ Campaign complete! Sent: $sent, Failed: $failed";
            $success = true;
            
        } catch (Exception $e) {
            $response = "❌ SMTP Error: " . $e->getMessage();
        }
    }
}

// Load history
$history = [];
if (file_exists(__DIR__ . '/history.json')) {
    $history = json_decode(file_get_contents(__DIR__ . '/history.json'), true) ?: [];
}

$hasConfig = !empty($config) && isset($config['host']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Bulk Mailer - Email Campaign Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .header h1 { font-size: 32px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 16px; }
        .header .badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 8px;
        }
        .tabs {
            display: flex;
            background: white;
            border-bottom: 2px solid #e8e8e8;
            overflow: hidden;
        }
        .tabs button {
            flex: 1;
            padding: 15px 20px;
            border: none;
            background: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #888;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        .tabs button:hover { background: #f8f9fa; }
        .tabs button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content { display: none; background: white; padding: 30px; border-radius: 0 0 15px 15px; }
        .tab-content.active { display: block; }
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .form-group label .required { color: #e74c3c; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 11px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
            background: #fafafa;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group textarea.recipients { min-height: 150px; font-size: 13px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-info { background: #3498db; color: white; }
        .btn-sm { padding: 6px 15px; font-size: 12px; }
        
        .status-card {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat .number { font-size: 28px; font-weight: 700; color: #333; }
        .stat .label { font-size: 12px; color: #888; margin-top: 5px; }
        .stat.success .number { color: #2ecc71; }
        .stat.failed .number { color: #e74c3c; }
        .stat.total .number { color: #667eea; }
        
        .history-item {
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .history-item .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .history-item .campaign { font-weight: 600; color: #333; }
        .history-item .date { color: #888; font-size: 13px; }
        .history-item .stats { font-size: 13px; }
        .history-item .stats .sent { color: #2ecc71; }
        .history-item .stats .failed { color: #e74c3c; }
        
        .config-status {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .config-status.active { background: #d4edda; color: #155724; }
        .config-status.inactive { background: #f8d7da; color: #721c24; }
        
        .tip {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
            border-left: 4px solid #667eea;
            margin: 10px 0;
        }
        
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
        
        .footer { text-align: center; margin-top: 20px; color: #aaa; font-size: 12px; }
        
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .header h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📧 Pro Bulk Mailer</h1>
        <p>Professional Email Campaign Manager</p>
        <span class="badge">
            <?php if ($hasConfig): ?>
            ✅ SMTP Configured
            <?php else: ?>
            ⚙️ Setup Required
            <?php endif; ?>
        </span>
    </div>
    
    <div class="tabs">
        <button class="tab-btn <?php echo $activeTab === 'send' ? 'active' : ''; ?>" data-tab="send">📤 Send Campaign</button>
        <button class="tab-btn <?php echo $activeTab === 'config' ? 'active' : ''; ?>" data-tab="config">⚙️ SMTP Setup</button>
        <button class="tab-btn <?php echo $activeTab === 'history' ? 'active' : ''; ?>" data-tab="history">📊 History</button>
    </div>
    
    <!-- ==================== SEND TAB ==================== -->
    <div id="tab-send" class="tab-content <?php echo $activeTab === 'send' ? 'active' : ''; ?>">
        <?php if ($response): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $response; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$hasConfig): ?>
        <div class="alert alert-info">
            ⚠️ Please configure your SMTP settings first. Go to the <strong>SMTP Setup</strong> tab.
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Campaign Name</label>
                <input type="text" name="campaign_name" placeholder="e.g., July Newsletter 2026" value="<?php echo $_POST['campaign_name'] ?? 'Campaign ' . date('Y-m-d H:i'); ?>">
            </div>
            
            <div class="form-group">
                <label>Email Subject <span class="required">*</span></label>
                <input type="text" name="subject" placeholder="Your email subject" required value="<?php echo $_POST['subject'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Email Message <span class="required">*</span></label>
                <textarea name="message" placeholder="Write your email message here..." required><?php echo $_POST['message'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Recipients (one email per line) <span class="required">*</span></label>
                <textarea class="recipients" name="recipients" placeholder="customer1@example.com&#10;customer2@example.com&#10;customer3@example.com" required><?php echo $_POST['recipients'] ?? ''; ?></textarea>
                <div class="tip">
                    💡 Tip: Add one email per line. You can paste from Excel or CSV.
                </div>
            </div>
            
            <div class="form-group">
                <label>Estimated Recipients</label>
                <input type="text" id="recipientCount" value="0" disabled style="background:#f0f0f0; font-weight:600; color:#667eea;">
            </div>
            
            <button type="submit" name="send_emails" class="btn btn-primary" id="sendBtn" <?php echo !$hasConfig ? 'disabled' : ''; ?>>
                <?php echo $hasConfig ? '🚀 Send Campaign' : '⚠️ Configure SMTP First'; ?>
            </button>
        </form>
        
        <div class="spinner" id="spinner">
            <div></div>
            <p style="margin-top: 8px; color: #888; font-size: 13px;">Sending emails...</p>
        </div>
    </div>
    
    <!-- ==================== CONFIG TAB ==================== -->
    <div id="tab-config" class="tab-content <?php echo $activeTab === 'config' ? 'active' : ''; ?>">
        <h3 style="margin-bottom: 15px; color: #333;">⚙️ SMTP Configuration</h3>
        <p style="color: #888; margin-bottom: 20px;">Configure your SMTP settings to start sending emails.</p>
        
        <?php if ($hasConfig): ?>
        <div class="alert alert-success">
            ✅ SMTP is currently configured: <strong><?php echo htmlspecialchars($config['host']); ?></strong>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Host <span class="required">*</span></label>
                    <input type="text" name="smtp_host" placeholder="smtp.gmail.com" required value="<?php echo htmlspecialchars($config['host'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>SMTP Port <span class="required">*</span></label>
                    <input type="number" name="smtp_port" placeholder="587" required value="<?php echo htmlspecialchars($config['port'] ?? '587'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Security <span class="required">*</span></label>
                <select name="smtp_security" required>
                    <option value="tls" <?php echo ($config['security'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                    <option value="ssl" <?php echo ($config['security'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="none" <?php echo ($config['security'] ?? '') === 'none' ? 'selected' : ''; ?>>None (Not Recommended)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>SMTP Username <span class="required">*</span></label>
                <input type="text" name="smtp_username" placeholder="your-email@gmail.com" required value="<?php echo htmlspecialchars($config['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>SMTP Password <span class="required">*</span></label>
                <input type="password" name="smtp_password" placeholder="Your app password" required value="<?php echo htmlspecialchars($config['password'] ?? ''); ?>">
                <?php if (isset($config['password'])): ?>
                <div style="font-size: 12px; color: #888; margin-top: 5px;">🔒 Password is stored securely</div>
                <?php endif; ?>
            </div>
            
            <hr style="margin: 20px 0; border-color: #eee;">
            <h4 style="margin-bottom: 15px;">Sender Details</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label>From Email</label>
                    <input type="email" name="from_email" placeholder="sender@yourdomain.com" value="<?php echo htmlspecialchars($config['from_email'] ?? $config['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>From Name</label>
                    <input type="text" name="from_name" placeholder="Your Company Name" value="<?php echo htmlspecialchars($config['from_name'] ?? 'Pro Mailer'); ?>">
                </div>
            </div>
            
            <div class="tip">
                💡 For Gmail: Enable 2FA and generate an <strong>App Password</strong>. Never use your regular Gmail password.
            </div>
            
            <button type="submit" name="save_smtp" class="btn btn-primary">💾 Save SMTP Configuration</button>
        </form>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="margin-bottom: 10px;">🔧 Common SMTP Providers</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                <div><strong>Gmail:</strong> smtp.gmail.com:587 (TLS)</div>
                <div><strong>Outlook:</strong> smtp.office365.com:587 (TLS)</div>
                <div><strong>SendGrid:</strong> smtp.sendgrid.net:587 (TLS)</div>
                <div><strong>Mailgun:</strong> smtp.mailgun.org:587 (TLS)</div>
                <div><strong>Brevo:</strong> smtp-relay.brevo.com:587 (TLS)</div>
                <div><strong>SMTP2GO:</strong> mail.smtp2go.com:587 (TLS)</div>
            </div>
        </div>
    </div>
    
    <!-- ==================== HISTORY TAB ==================== -->
    <div id="tab-history" class="tab-content <?php echo $activeTab === 'history' ? 'active' : ''; ?>">
        <h3 style="margin-bottom: 15px; color: #333;">📊 Campaign History</h3>
        
        <?php if (empty($history)): ?>
        <div class="alert alert-info">No campaigns sent yet. Start your first campaign!</div>
        <?php else: ?>
        
        <div class="status-card">
            <div class="stat total">
                <div class="number"><?php echo count($history); ?></div>
                <div class="label">Total Campaigns</div>
            </div>
            <div class="stat success">
                <div class="number"><?php echo array_sum(array_column($history, 'sent')); ?></div>
                <div class="label">Total Sent</div>
            </div>
            <div class="stat failed">
                <div class="number"><?php echo array_sum(array_column($history, 'failed')); ?></div>
                <div class="label">Total Failed</div>
            </div>
        </div>
        
        <?php foreach (array_reverse($history) as $campaign): ?>
        <div class="history-item">
            <div class="top">
                <div>
                    <span class="campaign">📧 <?php echo htmlspecialchars($campaign['campaign']); ?></span>
                    <br>
                    <span class="date"><?php echo htmlspecialchars($campaign['date']); ?></span>
                </div>
                <div class="stats">
                    <span class="sent">✅ <?php echo $campaign['sent']; ?> sent</span>
                    <?php if ($campaign['failed'] > 0): ?>
                    <span class="failed">❌ <?php echo $campaign['failed']; ?> failed</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top: 8px; font-size: 13px; color: #888;">
                Subject: <?php echo htmlspecialchars($campaign['subject']); ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>⚡ Powered by Leaf PHP Mailer &bull; Secured with SMTP</p>
    </div>
</div>

<script>
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });
    
    // Count recipients
    const recipients = document.querySelector('textarea[name="recipients"]');
    if (recipients) {
        recipients.addEventListener('input', function() {
            const count = this.value.split('\n').filter(line => line.trim() !== '').length;
            document.getElementById('recipientCount').value = count;
        });
        // Trigger on load
        recipients.dispatchEvent(new Event('input'));
    }
    
    // Show spinner on send
    document.querySelector('form[action=""]')?.addEventListener('submit', function(e) {
        const btn = document.getElementById('sendBtn');
        const spinner = document.getElementById('spinner');
        if (btn && !btn.disabled) {
            btn.disabled = true;
            btn.innerHTML = '⏳ Sending...';
            if (spinner) spinner.classList.add('active');
        }
    });
</script>
</body>
</html>
