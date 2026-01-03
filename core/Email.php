<?php
/**
 * Email Handler with PHPMailer
 * Support SMTP & PHP mail()
 * Support Gmail App Password & cPanel/Hosting Email
 * 
 * @author BTIKP Kalsel
 * @version 2.0
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email {
    private $db;
    private $settings = [];
    private $mailer;
    private $lastError = '';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadSettings();
        $this->initializeMailer();
    }
    
    /**
     * Load email settings from database
     */
    private function loadSettings() {
        $stmt = $this->db->query("SELECT `key`, `value` FROM settings WHERE `group` IN ('email', 'notification', 'general', 'contact')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->settings[$row['key']] = $row['value'];
        }
    }
    
    /**
     * Initialize PHPMailer
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Check if SMTP is enabled
            if (!empty($this->settings['email_smtp_enable']) && $this->settings['email_smtp_enable'] == '1') {
                // SMTP Configuration
                $this->mailer->isSMTP();
                $this->mailer->Host = $this->settings['email_smtp_host'] ?? 'smtp.gmail.com';
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $this->settings['email_smtp_username'] ?? '';
                $this->mailer->Password = $this->settings['email_smtp_password'] ?? '';
                
                // Encryption
                $encryption = $this->settings['email_smtp_encryption'] ?? 'tls';
                if ($encryption === 'ssl') {
                    $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $this->mailer->Port = $this->settings['email_smtp_port'] ?? 465;
                } else {
                    $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $this->mailer->Port = $this->settings['email_smtp_port'] ?? 587;
                }
                
                // Debug (set to 0 in production)
                $this->mailer->SMTPDebug = 0;
            } else {
                // Use PHP mail()
                $this->mailer->isMail();
            }
            
            // Set default FROM
            $fromEmail = $this->settings['email_from_address'] ?? 'noreply@btikpkalsel.id';
            $fromName = $this->settings['email_from_name'] ?? 'BTIKP Kalimantan Selatan';
            $this->mailer->setFrom($fromEmail, $fromName);
            
            // Set encoding & charset
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            
        } catch (Exception $e) {
            error_log('Email initialization error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send email
     * @param string $to Recipient email
     * @param string $name Recipient name
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody Alternative plain text body
     * @return bool
     */
    public function send($to, $name, $subject, $body, $altBody = '') {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Set recipient
            $this->mailer->addAddress($to, $name);
            
            // Set reply-to (optional)
            $replyTo = $this->settings['contact_email'] ?? $this->settings['email_from_address'];
            $this->mailer->addReplyTo($replyTo, $this->settings['site_name'] ?? 'BTIKP Kalsel');
            
            // Email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody ?: strip_tags($body);
            
            // Send
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log('Email send error: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send auto-reply for contact form
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $originalMessage
     * @return bool
     */
    public function sendAutoReply($recipientEmail, $recipientName, $originalMessage = '') {
        // Check if auto-reply is enabled
        if (empty($this->settings['contact_auto_reply']) || $this->settings['contact_auto_reply'] != '1') {
            return false;
        }
        
        // Check working hours for different message
        $officeHours = new OfficeHours();
        $isWorkingHours = $officeHours->isWorkingHours();
        
        // Determine message based on working hours
        if (!$isWorkingHours && !empty($this->settings['contact_working_hours_only']) && $this->settings['contact_working_hours_only'] == '1') {
            $message = $this->settings['contact_non_working_message'] ?? 'Kantor sedang tutup. Pesan Anda akan dibalas pada hari kerja berikutnya.';
        } else {
            $message = $this->settings['contact_auto_reply_message'] ?? 'Terima kasih atas pesan Anda. Kami akan merespon dalam 1x24 jam.';
        }
        
        // Build email body
        $subject = 'Konfirmasi Pesan Anda - ' . ($this->settings['site_name'] ?? 'BTIKP Kalsel');
        $body = $this->buildAutoReplyTemplate($recipientName, $message, $originalMessage);
        
        return $this->send($recipientEmail, $recipientName, $subject, $body);
    }
    
    /**
     * Send notification to admin
     * @param string $subject
     * @param string $message
     * @param array $data Additional data
     * @return bool
     */
    public function sendAdminNotification($subject, $message, $data = []) {
        // Check if notification is enabled
        if (empty($this->settings['notification_new_contact']) || $this->settings['notification_new_contact'] != '1') {
            return false;
        }
        
        $adminEmail = $this->settings['notification_email_admin'] ?? 'admin@btikpkalsel.id';
        $adminName = 'Administrator';
        
        $body = $this->buildAdminNotificationTemplate($subject, $message, $data);
        
        return $this->send($adminEmail, $adminName, $subject, $body);
    }
    
    /**
     * Build auto-reply email template
     */
    private function buildAutoReplyTemplate($name, $message, $originalMessage = '') {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $siteUrl = BASE_URL ?? '#';
        
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; }
                .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                .message-box { background: white; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0; }
                h1 { margin: 0; font-size: 24px; }
                p { margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✉️ Konfirmasi Pesan</h1>
                </div>
                <div class="content">
                    <p>Halo <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>' . nl2br(htmlspecialchars($message)) . '</p>
                    ' . (!empty($originalMessage) ? '
                    <div class="message-box">
                        <strong>Pesan Anda:</strong><br>
                        ' . nl2br(htmlspecialchars(substr($originalMessage, 0, 200))) . (strlen($originalMessage) > 200 ? '...' : '') . '
                    </div>
                    ' : '') . '
                    <p>Tim kami akan segera menghubungi Anda melalui email atau telepon.</p>
                    <p>Terima kasih atas kepercayaan Anda kepada <strong>' . htmlspecialchars($siteName) . '</strong>.</p>
                    <a href="' . htmlspecialchars($siteUrl) . '" class="button" style="color: white;">Kunjungi Website Kami</a>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '. All Rights Reserved.</p>
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $html;
    }
    
    /**
     * Build admin notification template
     */
    private function buildAdminNotificationTemplate($subject, $message, $data = []) {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $adminUrl = ADMIN_URL ?? BASE_URL . 'admin/';
        
        // Build data table if data exists
        $dataTableHtml = '';
        if (!empty($data)) {
            $dataTableHtml = '
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            foreach ($data as $key => $value) {
                $dataTableHtml .= '<tr><td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
            }
            
            $dataTableHtml .= '
                        </tbody>
                    </table>';
        }
        
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 20px; border: 1px solid #ddd; border-top: none; }
                .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .data-table th, .data-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                .data-table th { background: #f5f5f5; font-weight: bold; }
                .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                h2 { margin: 0; font-size: 22px; }
                p { margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>🔔 ' . htmlspecialchars($subject) . '</h2>
                </div>
                <div class="content">
                    <p><strong>Notifikasi dari:</strong> ' . htmlspecialchars($siteName) . '</p>
                    <p>' . nl2br(htmlspecialchars($message)) . '</p>
                    ' . $dataTableHtml . '
                    <p><small>Dikirim pada: ' . date('d F Y, H:i:s') . ' WIB</small></p>
                    <a href="' . htmlspecialchars($adminUrl) . '" class="button" style="color: white;">Buka Admin Panel</a>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $html;
    }
    
    /**
     * Test email configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection() {
        try {
            if (!empty($this->settings['email_smtp_enable']) && $this->settings['email_smtp_enable'] == '1') {
                $this->mailer->SMTPDebug = 2;
                ob_start();
                $result = $this->mailer->smtpConnect();
                $debug = ob_get_clean();
                $this->mailer->smtpClose();
                
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Koneksi SMTP berhasil!',
                        'debug' => $debug
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Koneksi SMTP gagal!',
                        'debug' => $debug
                    ];
                }
            } else {
                return [
                    'success' => true,
                    'message' => 'Menggunakan PHP mail() function',
                    'debug' => 'SMTP tidak diaktifkan, menggunakan mail() default'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get last error message
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Generate verification code
     * @param int $length Code length (default 6)
     * @return string
     */
    public function generateVerificationCode($length = 6) {
        $codeLength = (int)($this->settings['email_verification_code_length'] ?? $length);
        if ($codeLength < 4) $codeLength = 4;
        if ($codeLength > 10) $codeLength = 10;
        
        // Generate numeric code
        $code = '';
        for ($i = 0; $i < $codeLength; $i++) {
            $code .= mt_rand(0, 9);
        }
        
        return $code;
    }
    
    /**
     * Check if email verification is enabled
     * @return bool
     */
    public function isVerificationEnabled() {
        return !empty($this->settings['email_verification_enabled']) && 
               $this->settings['email_verification_enabled'] == '1';
    }
    
    /**
     * Get verification code expiry in minutes
     * @return int
     */
    public function getVerificationExpiry() {
        return (int)($this->settings['email_verification_expiry_minutes'] ?? 15);
    }
    
    /**
     * Send verification code for registration
     * @param string $email
     * @param string $name
     * @param string $code
     * @return bool
     */
    public function sendRegistrationVerification($email, $name, $code) {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $expiry = $this->getVerificationExpiry();
        
        $subject = "[{$siteName}] Kode Verifikasi Pendaftaran Anda";
        $body = $this->buildVerificationEmailTemplate($name, $code, 'register', $expiry);
        
        return $this->send($email, $name, $subject, $body);
    }
    
    /**
     * Send verification code for password reset
     * @param string $email
     * @param string $name
     * @param string $code
     * @return bool
     */
    public function sendPasswordResetCode($email, $name, $code) {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $expiry = $this->getVerificationExpiry();
        
        $subject = "[{$siteName}] Kode Reset Password Anda";
        $body = $this->buildVerificationEmailTemplate($name, $code, 'password_reset', $expiry);
        
        return $this->send($email, $name, $subject, $body);
    }
    
    /**
     * Send email change verification
     * @param string $newEmail
     * @param string $name
     * @param string $code
     * @return bool
     */
    public function sendEmailChangeVerification($newEmail, $name, $code) {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $expiry = $this->getVerificationExpiry();
        
        $subject = "[{$siteName}] Konfirmasi Perubahan Email";
        $body = $this->buildVerificationEmailTemplate($name, $code, 'email_change', $expiry);
        
        return $this->send($newEmail, $name, $subject, $body);
    }
    
    /**
     * Send welcome email after registration
     * @param string $email
     * @param string $name
     * @return bool
     */
    public function sendWelcomeEmail($email, $name) {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $siteUrl = BASE_URL ?? '#';
        $adminUrl = ADMIN_URL ?? BASE_URL . 'admin/';
        
        $subject = "Selamat Datang di {$siteName}!";
        $body = $this->buildWelcomeEmailTemplate($name, $siteName, $siteUrl, $adminUrl);
        
        return $this->send($email, $name, $subject, $body);
    }
    
    /**
     * Build verification email template
     * @param string $name
     * @param string $code
     * @param string $type (register, password_reset, email_change)
     * @param int $expiryMinutes
     * @return string
     */
    private function buildVerificationEmailTemplate($name, $code, $type, $expiryMinutes) {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $siteUrl = BASE_URL ?? '#';
        
        // Determine message based on type
        switch ($type) {
            case 'register':
                $title = 'Verifikasi Pendaftaran';
                $description = 'Terima kasih telah mendaftar di <strong>' . htmlspecialchars($siteName) . '</strong>. Gunakan kode verifikasi berikut untuk menyelesaikan pendaftaran Anda:';
                $iconEmoji = '🎉';
                $headerColor = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                break;
                
            case 'password_reset':
                $title = 'Reset Password';
                $description = 'Kami menerima permintaan untuk reset password akun Anda. Gunakan kode berikut untuk melanjutkan:';
                $iconEmoji = '🔐';
                $headerColor = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
                break;
                
            case 'email_change':
                $title = 'Konfirmasi Perubahan Email';
                $description = 'Anda meminta untuk mengubah alamat email akun Anda. Gunakan kode berikut untuk konfirmasi:';
                $iconEmoji = '📧';
                $headerColor = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
                break;
                
            default:
                $title = 'Verifikasi';
                $description = 'Gunakan kode verifikasi berikut:';
                $iconEmoji = '✉️';
                $headerColor = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        }
        
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 520px; 
                    margin: 20px auto; 
                    background: #ffffff;
                    border-radius: 16px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header { 
                    background: ' . $headerColor . '; 
                    color: white; 
                    padding: 40px 30px; 
                    text-align: center; 
                }
                .header-icon {
                    font-size: 48px;
                    margin-bottom: 15px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 26px;
                    font-weight: 600;
                }
                .content { 
                    padding: 35px 30px; 
                    text-align: center;
                }
                .content p {
                    margin: 0 0 25px 0;
                    color: #555;
                    font-size: 15px;
                }
                .code-box {
                    background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
                    border: 2px dashed #667eea;
                    border-radius: 12px;
                    padding: 25px;
                    margin: 25px 0;
                }
                .code {
                    font-size: 36px;
                    font-weight: 700;
                    letter-spacing: 8px;
                    color: #333;
                    font-family: "Monaco", "Consolas", monospace;
                }
                .expiry-notice {
                    background: #fff3cd;
                    border: 1px solid #ffc107;
                    color: #856404;
                    padding: 12px 20px;
                    border-radius: 8px;
                    font-size: 14px;
                    margin-top: 20px;
                }
                .expiry-notice strong {
                    color: #d63384;
                }
                .security-notice {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-top: 25px;
                    text-align: left;
                    font-size: 13px;
                    color: #6c757d;
                }
                .security-notice strong {
                    color: #495057;
                    display: block;
                    margin-bottom: 8px;
                }
                .security-notice ul {
                    margin: 0;
                    padding-left: 20px;
                }
                .security-notice li {
                    margin-bottom: 5px;
                }
                .footer { 
                    background: #2d3748; 
                    color: #a0aec0; 
                    padding: 25px; 
                    text-align: center; 
                    font-size: 12px; 
                }
                .footer a {
                    color: #667eea;
                    text-decoration: none;
                }
                .footer p {
                    margin: 5px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="header-icon">' . $iconEmoji . '</div>
                    <h1>' . htmlspecialchars($title) . '</h1>
                </div>
                <div class="content">
                    <p>Halo <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>' . $description . '</p>
                    
                    <div class="code-box">
                        <div class="code">' . htmlspecialchars($code) . '</div>
                    </div>
                    
                    <div class="expiry-notice">
                        ⏰ Kode ini berlaku selama <strong>' . $expiryMinutes . ' menit</strong>
                    </div>
                    
                    <div class="security-notice">
                        <strong>🔒 Tips Keamanan:</strong>
                        <ul>
                            <li>Jangan bagikan kode ini kepada siapapun</li>
                            <li>Kami tidak akan pernah meminta kode melalui telepon atau pesan</li>
                            <li>Jika Anda tidak melakukan permintaan ini, abaikan email ini</li>
                        </ul>
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '</p>
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                    <p><a href="' . htmlspecialchars($siteUrl) . '">' . htmlspecialchars($siteName) . '</a></p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $html;
    }
    
    /**
     * Build welcome email template
     * @param string $name
     * @param string $siteName
     * @param string $siteUrl
     * @param string $adminUrl
     * @return string
     */
    private function buildWelcomeEmailTemplate($name, $siteName, $siteUrl, $adminUrl) {
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 520px; 
                    margin: 20px auto; 
                    background: #ffffff;
                    border-radius: 16px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header { 
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); 
                    color: white; 
                    padding: 40px 30px; 
                    text-align: center; 
                }
                .header-icon {
                    font-size: 48px;
                    margin-bottom: 15px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 26px;
                    font-weight: 600;
                }
                .content { 
                    padding: 35px 30px;
                }
                .content p {
                    margin: 0 0 20px 0;
                    color: #555;
                    font-size: 15px;
                }
                .success-box {
                    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                    border: 1px solid #28a745;
                    border-radius: 12px;
                    padding: 20px;
                    margin: 20px 0;
                    text-align: center;
                }
                .success-box p {
                    margin: 0;
                    color: #155724;
                    font-weight: 500;
                }
                .button { 
                    display: inline-block; 
                    padding: 14px 35px; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 8px; 
                    margin-top: 20px;
                    font-weight: 500;
                }
                .info-box {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-top: 25px;
                }
                .info-box h4 {
                    margin: 0 0 15px 0;
                    color: #495057;
                    font-size: 15px;
                }
                .info-box ul {
                    margin: 0;
                    padding-left: 20px;
                    color: #6c757d;
                }
                .info-box li {
                    margin-bottom: 8px;
                }
                .footer { 
                    background: #2d3748; 
                    color: #a0aec0; 
                    padding: 25px; 
                    text-align: center; 
                    font-size: 12px; 
                }
                .footer a {
                    color: #667eea;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="header-icon">🎊</div>
                    <h1>Selamat Datang!</h1>
                </div>
                <div class="content">
                    <p>Halo <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    
                    <div class="success-box">
                        <p>✅ Akun Anda telah berhasil diverifikasi!</p>
                    </div>
                    
                    <p>Selamat! Pendaftaran Anda di <strong>' . htmlspecialchars($siteName) . '</strong> telah berhasil. Akun Anda saat ini dalam status <strong>menunggu persetujuan admin</strong>.</p>
                    
                    <p>Setelah akun Anda disetujui, Anda akan dapat mengakses dashboard dan fitur-fitur lainnya.</p>
                    
                    <div class="info-box">
                        <h4>📌 Langkah Selanjutnya:</h4>
                        <ul>
                            <li>Tunggu email konfirmasi persetujuan dari admin</li>
                            <li>Setelah disetujui, login ke dashboard</li>
                            <li>Lengkapi profil Anda</li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="' . htmlspecialchars($adminUrl) . 'login.php" class="button">Kunjungi Dashboard</a>
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '</p>
                    <p>Email ini dikirim secara otomatis.</p>
                    <p><a href="' . htmlspecialchars($siteUrl) . '">' . htmlspecialchars($siteName) . '</a></p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $html;
    }
    
    /**
     * Send test email to verify configuration
     * @param string $testEmail
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendTestEmail($testEmail) {
        $siteName = $this->settings['site_name'] ?? 'BTIKP Kalimantan Selatan';
        $subject = "[{$siteName}] Test Email Configuration";
        
        $body = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 520px; margin: 20px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .info-table th, .info-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                .info-table th { background: #f5f5f5; width: 40%; }
                .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>✅ Test Email Berhasil!</h2>
                </div>
                <div class="content">
                    <p>Jika Anda menerima email ini, berarti konfigurasi email sudah benar.</p>
                    
                    <table class="info-table">
                        <tr>
                            <th>SMTP Enabled</th>
                            <td>' . ($this->settings['email_smtp_enable'] ?? '0' == '1' ? 'Ya' : 'Tidak') . '</td>
                        </tr>
                        <tr>
                            <th>SMTP Host</th>
                            <td>' . htmlspecialchars($this->settings['email_smtp_host'] ?? '-') . '</td>
                        </tr>
                        <tr>
                            <th>SMTP Port</th>
                            <td>' . htmlspecialchars($this->settings['email_smtp_port'] ?? '-') . '</td>
                        </tr>
                        <tr>
                            <th>Encryption</th>
                            <td>' . htmlspecialchars($this->settings['email_smtp_encryption'] ?? '-') . '</td>
                        </tr>
                        <tr>
                            <th>From Email</th>
                            <td>' . htmlspecialchars($this->settings['email_from_address'] ?? '-') . '</td>
                        </tr>
                        <tr>
                            <th>Waktu Kirim</th>
                            <td>' . date('d F Y, H:i:s') . ' WIB</td>
                        </tr>
                    </table>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        try {
            $result = $this->send($testEmail, 'Test User', $subject, $body);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Email test berhasil dikirim ke ' . $testEmail
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim email test. Error: ' . $this->mailer->ErrorInfo
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
