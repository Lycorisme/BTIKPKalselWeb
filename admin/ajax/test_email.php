<?php
/**
 * Test Email Configuration AJAX Handler
 * Mengirim test email untuk verifikasi konfigurasi SMTP
 */

// Start output buffering to catch any unwanted output
ob_start();

// Suppress errors from being displayed (will be logged instead)
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Helper.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Clear any buffered output from includes
ob_clean();

// Check if logged in and has admin role
session_start();
if (!isLoggedIn() || !hasRole(['super_admin', 'admin'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get parameters
$testEmail = clean($_POST['test_email'] ?? '');
$smtpHost = clean($_POST['smtp_host'] ?? '');
$smtpPort = (int)($_POST['smtp_port'] ?? 587);
$smtpUsername = clean($_POST['smtp_username'] ?? '');
$smtpPassword = $_POST['smtp_password'] ?? ''; // Don't clean password
$smtpEncryption = clean($_POST['smtp_encryption'] ?? 'tls');
$smtpEnable = $_POST['smtp_enable'] ?? '0';
$fromName = clean($_POST['from_name'] ?? 'BTIKP Kalimantan Selatan');
$fromAddress = clean($_POST['from_address'] ?? 'noreply@btikpkalsel.id');

// Validate email
if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Alamat email tidak valid']);
    exit;
}

try {
    $mailer = new PHPMailer(true);
    
    if ($smtpEnable == '1') {
        // SMTP Configuration
        $mailer->isSMTP();
        $mailer->Host = $smtpHost;
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtpUsername;
        $mailer->Password = $smtpPassword;
        
        // Encryption
        if ($smtpEncryption === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mailer->Port = $smtpPort ?: 465;
        } else {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port = $smtpPort ?: 587;
        }
        
        // Debug mode off for production
        $mailer->SMTPDebug = 0;
    } else {
        $mailer->isMail();
    }
    
    // Set FROM
    $mailer->setFrom($fromAddress, $fromName);
    
    // Set encoding & charset
    $mailer->CharSet = 'UTF-8';
    $mailer->Encoding = 'base64';
    
    // Set recipient
    $mailer->addAddress($testEmail, 'Test User');
    $mailer->addReplyTo($fromAddress, $fromName);
    
    // Email content
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $mailer->isHTML(true);
    $mailer->Subject = "[{$siteName}] Test Email Configuration";
    
    $mailer->Body = '
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
                <p>Jika Anda menerima email ini, berarti <strong>konfigurasi email sudah benar</strong>.</p>
                
                <table class="info-table">
                    <tr>
                        <th>SMTP Enabled</th>
                        <td>' . ($smtpEnable == '1' ? 'Ya' : 'Tidak (PHP mail())') . '</td>
                    </tr>
                    <tr>
                        <th>SMTP Host</th>
                        <td>' . htmlspecialchars($smtpHost ?: '-') . '</td>
                    </tr>
                    <tr>
                        <th>SMTP Port</th>
                        <td>' . htmlspecialchars($smtpPort ?: '-') . '</td>
                    </tr>
                    <tr>
                        <th>Encryption</th>
                        <td>' . htmlspecialchars(strtoupper($smtpEncryption)) . '</td>
                    </tr>
                    <tr>
                        <th>From Email</th>
                        <td>' . htmlspecialchars($fromAddress) . '</td>
                    </tr>
                    <tr>
                        <th>Waktu Kirim</th>
                        <td>' . date('d F Y, H:i:s') . ' WIB</td>
                    </tr>
                </table>
                
                <p style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 8px; color: #155724;">
                    <strong>🎉 Selamat!</strong> Email support Anda sudah siap digunakan untuk:
                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>Verifikasi pendaftaran akun baru</li>
                        <li>Reset password</li>
                        <li>Notifikasi pesan kontak</li>
                        <li>Auto-response messages</li>
                    </ul>
                </p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    $mailer->AltBody = 'Test Email - Jika Anda menerima email ini, berarti konfigurasi email sudah benar. Waktu kirim: ' . date('d F Y, H:i:s') . ' WIB';
    
    // Send
    if ($mailer->send()) {
        // Log activity
        logActivity('EMAIL_TEST', "Test email berhasil dikirim ke {$testEmail}", 'settings');
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => "Test email berhasil dikirim ke {$testEmail}! Silakan cek inbox atau folder spam."
        ]);
    } else {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengirim email: ' . $mailer->ErrorInfo
        ]);
    }
    
} catch (Exception $e) {
    // Log error
    error_log('Test email error: ' . $e->getMessage());
    
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
