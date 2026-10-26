<?php
// includes/mailer.php - Real SMTP & HTML Email Notification Dispatcher for JKtech.LK
require_once __DIR__ . '/../config/mail_config.php';

/**
 * Send a professional HTML Login Notification Email to the user
 */
function sendLoginNotificationEmail($toEmail, $userName, $loginMethod = 'Email & Password') {
    $toEmail = trim($toEmail);
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    date_default_timezone_set('Asia/Colombo');
    $timestamp = date('F j, Y \a\t g:i A') . ' (SLST)';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Web Browser';

    // Detect clean browser/device name
    $deviceSummary = 'Web Browser';
    if (stripos($userAgent, 'Edg') !== false) $deviceSummary = 'Microsoft Edge';
    elseif (stripos($userAgent, 'Chrome') !== false) $deviceSummary = 'Google Chrome';
    elseif (stripos($userAgent, 'Firefox') !== false) $deviceSummary = 'Mozilla Firefox';
    elseif (stripos($userAgent, 'Safari') !== false) $deviceSummary = 'Apple Safari';

    if (stripos($userAgent, 'Windows') !== false) $deviceSummary .= ' on Windows';
    elseif (stripos($userAgent, 'Android') !== false) $deviceSummary .= ' on Android Mobile';
    elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) $deviceSummary .= ' on iOS';
    elseif (stripos($userAgent, 'Mac') !== false) $deviceSummary .= ' on macOS';

    $safeName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
    $safeMethod = htmlspecialchars($loginMethod, ENT_QUOTES, 'UTF-8');

    $subject = "Security Alert: New sign-in to your JKtech.LK account";

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background-color:#080c14;font-family:'Segoe UI',Arial,sans-serif;color:#e2e8f0;">
  <div style="max-width:560px;margin:30px auto;background-color:#0e1526;border:1px solid rgba(255,255,255,0.1);border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,0.6);">
    <div style="height:4px;background:linear-gradient(90deg,#00e5ff,#ff4d2d);"></div>
    <div style="padding:28px 32px;border-bottom:1px solid rgba(255,255,255,0.08);text-align:center;">
      <div style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:0.5px;">JKtech<span style="color:#00e5ff;">.LK</span></div>
      <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Direct Japanese Auto Parts &amp; Engines</div>
    </div>
    <div style="padding:30px 32px;">
      <h2 style="margin:0 0 12px;font-size:19px;color:#ffffff;">Hello {$safeName},</h2>
      <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#cbd5e1;">
        We noticed a new sign-in to your <strong>JKtech.LK</strong> account using your email address (<span style="color:#00e5ff;">{$safeEmail}</span>).
      </p>
      <div style="background-color:#080d18;border:1px solid rgba(0,229,255,0.2);border-radius:12px;padding:18px 20px;margin-bottom:22px;">
        <div style="font-size:11px;font-weight:800;color:#00e5ff;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Sign-In Details</div>
        <table style="width:100%;border-collapse:collapse;font-size:13.5px;color:#e2e8f0;">
          <tr>
            <td style="padding:6px 0;color:#94a3b8;width:38%;">Account Email:</td>
            <td style="padding:6px 0;font-weight:600;color:#ffffff;">{$safeEmail}</td>
          </tr>
          <tr>
            <td style="padding:6px 0;color:#94a3b8;">Login Method:</td>
            <td style="padding:6px 0;font-weight:600;color:#10b981;">{$safeMethod}</td>
          </tr>
          <tr>
            <td style="padding:6px 0;color:#94a3b8;">Date &amp; Time:</td>
            <td style="padding:6px 0;">{$timestamp}</td>
          </tr>
          <tr>
            <td style="padding:6px 0;color:#94a3b8;">Device / Browser:</td>
            <td style="padding:6px 0;">{$deviceSummary}</td>
          </tr>
          <tr>
            <td style="padding:6px 0;color:#94a3b8;">IP Address:</td>
            <td style="padding:6px 0;font-family:monospace;">{$ipAddress}</td>
          </tr>
        </table>
      </div>
      <p style="margin:0;font-size:13px;line-height:1.6;color:#94a3b8;">
        If this was you, no further action is needed. You can continue shopping for genuine reconditioned JDM parts at JKtech.LK.
      </p>
    </div>
    <div style="padding:18px 32px;background-color:#090d16;border-top:1px solid rgba(255,255,255,0.06);text-align:center;font-size:12px;color:#64748b;">
      &copy; 2026 JKtech.LK &bull; Hotline: +94 77 837 6481 &bull; jktech00@gmail.com
    </div>
  </div>
</body>
</html>
HTML;

    $sentViaSmtp = false;

    // 1. Attempt Direct TLS SMTP if SMTP_PASSWORD is provided
    if (defined('SMTP_ENABLED') && SMTP_ENABLED && !empty(SMTP_PASSWORD) && !empty(SMTP_USERNAME)) {
        $sentViaSmtp = sendRawSmtpEmail($toEmail, $userName, $subject, $htmlBody);
    }

    // 2. Fallback to standard PHP mail() if SMTP wasn't used
    if (!$sentViaSmtp) {
        $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'no-reply@jktech.lk';
        $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'JKtech.LK';
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        @mail($toEmail, $subject, $htmlBody, $headers);
    }

    // 3. Save a copy of the dispatched email & store session alert so user can view/verify it even on localhost
    $emailDir = __DIR__ . '/../assets/uploads/emails';
    if (!is_dir($emailDir)) {
        @mkdir($emailDir, 0777, true);
    }
    @file_put_contents($emailDir . '/latest_login_email.html', $htmlBody);

    $_SESSION['login_email_alert'] = [
        'to' => $toEmail,
        'name' => $userName,
        'method' => $loginMethod,
        'time' => $timestamp,
        'device' => $deviceSummary,
        'subject' => $subject
    ];

    return true;
}

/**
 * Lightweight Native PHP TLS SMTP Client (No external libraries required)
 */
function sendRawSmtpEmail($toEmail, $toName, $subject, $htmlBody) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USERNAME;
    $pass = SMTP_PASSWORD;
    $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : $user;
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'JKtech.LK';

    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 8);
    if (!$socket) return false;

    $read = function() use ($socket) {
        $data = '';
        while ($str = @fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $data;
    };

    $write = function($cmd) use ($socket) {
        @fwrite($socket, $cmd . "\r\n");
    };

    $read();
    $write("EHLO localhost");
    $read();

    $write("STARTTLS");
    $res = $read();
    if (strpos($res, '220') === false) {
        @fclose($socket);
        return false;
    }

    if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        @fclose($socket);
        return false;
    }

    $write("EHLO localhost");
    $read();

    $write("AUTH LOGIN");
    $read();
    $write(base64_encode($user));
    $read();
    $write(base64_encode($pass));
    $authRes = $read();
    if (strpos($authRes, '235') === false) {
        @fclose($socket);
        return false;
    }

    $write("MAIL FROM:<{$fromEmail}>");
    $read();
    $write("RCPT TO:<{$toEmail}>");
    $read();
    $write("DATA");
    $read();

    $headers  = "Date: " . date('r') . "\r\n";
    $headers .= "To: {$toName} <{$toEmail}>\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Subject: {$subject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";

    $message = $headers . chunk_split(base64_encode($htmlBody)) . "\r\n.";
    $write($message);
    $sendRes = $read();

    $write("QUIT");
    @fclose($socket);

    return strpos($sendRes, '250') !== false;
}
