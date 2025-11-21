<?php
/**
 * Email Handler with PHPMailer
 * Support SMTP & PHP mail()
 * Tanpa logging ke file
 * 
 * @author BTIKP Kalsel
 * @version 1.2
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email {
    private $db;
    private $settings = [];
    private $mailer;
    
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
                    ' . (!empty($data) ? '
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>';
                        
        foreach ($data as $key => $value) {
            $html .= '<tr><td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        
        $html .= '
                        </tbody>
                    </table>
                    ' : '') . '
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
}
