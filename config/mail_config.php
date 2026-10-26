<?php
// config/mail_config.php - Email & Google OAuth Configuration for JKtech.LK

/**
 * 1. SMTP EMAIL CONFIGURATION
 * To send real emails directly to Gmail/Yahoo/Outlook inboxes from localhost (XAMPP):
 * - Enter your Gmail address in SMTP_USERNAME
 * - Enter your 16-character Google App Password in SMTP_PASSWORD
 *   (Generate at: Google Account -> Security -> 2-Step Verification -> App Passwords)
 */
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'jktech00@gmail.com'); // Your sender Gmail address
define('SMTP_PASSWORD', '');                   // Paste 16-char Google App Password here for live SMTP
define('MAIL_FROM_EMAIL', 'jktech00@gmail.com');
define('MAIL_FROM_NAME', 'JKtech.LK Auto Parts');

/**
 * 2. GOOGLE SIGN-IN (OAUTH 2.0 / GOOGLE IDENTITY SERVICES)
 * Optional: Paste your Google Cloud OAuth 2.0 Web Client ID below if you have one.
 * Even if left empty, the built-in Interactive Google Account Chooser will work seamlessly!
 */
define('GOOGLE_CLIENT_ID', '');
