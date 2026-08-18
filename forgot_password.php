<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'smtp_mailer.php';

$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address.';
        $isError = true;
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Always show the same message whether or not the email exists,
        // so this page can't be used to discover valid accounts.
        $message = 'If that email is registered, a reset link has been sent.';

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $upd = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'ssi', $token, $expires, $user['id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $resetUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset_password.php?token=' . $token;

            $subject = 'Password reset — A & D Inspections form';
            $htmlBody = '<p style="font-family:Arial,sans-serif;font-size:14px;">A password reset was requested for this account.</p>' .
                        '<p style="font-family:Arial,sans-serif;font-size:14px;"><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Click here to set a new password</a> (link expires in 1 hour).</p>' .
                        '<p style="font-family:Arial,sans-serif;font-size:12px;color:#666;">If you didn\'t request this, you can ignore this email.</p>';

            $mimeHeaders = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n";
            $smtpCfg = ['host' => $SMTP_HOST, 'port' => $SMTP_PORT, 'encryption' => $SMTP_ENCRYPTION, 'username' => $SMTP_USERNAME, 'password' => $SMTP_PASSWORD];
            smtp_send_mail($smtpCfg, 'A & D Inspections <' . $COMPANY_EMAIL . '>', $email, '', $subject, $mimeHeaders . $htmlBody);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — A &amp; D Inspections</title>
<link rel="stylesheet" href="report.css">
<style>
  .login-sheet{ max-width:380px; margin:60px auto; }
  .login-title{ font-family:Arial,sans-serif; font-size:19px; font-weight:bold; color:#2E7D32; text-align:center; margin:0 0 20px; }
  .login-sheet label{ display:block; font-family:Arial,sans-serif; font-weight:bold; font-size:13px; margin:14px 0 5px; }
  .login-sheet input[type=email]{
    width:100%; font-family:Arial,sans-serif; font-size:14px; padding:9px 10px;
    border:1px solid #999; background:#FDF3EC; box-sizing:border-box;
  }
  .login-btn{ margin-top:20px; width:100%; padding:12px; border-radius:4px; font-family:Arial,sans-serif;
    font-weight:bold; font-size:15px; cursor:pointer; border:2px solid #2E7D32; background:#2E7D32; color:#fff; }
  .login-msg{ font-family:Arial,sans-serif; font-size:13px; text-align:center; margin-top:12px; }
  .login-msg.error{ color:#A6432F; }
  .login-msg.ok{ color:#2E7D32; }
  .login-links{ text-align:center; margin-top:16px; font-family:Arial,sans-serif; font-size:13px; }
  .login-links a{ color:#2E7D32; text-decoration:none; }
</style>
</head>
<body>
<div class="rp-sheet no-pagenum login-sheet" style="border-color:#999;">
  <div class="login-title">Reset Your Password</div>
  <form method="post" novalidate>
    <label for="email">Account email</label>
    <input type="email" id="email" name="email" required>
    <button class="login-btn" type="submit">Send Reset Link</button>
  </form>
  <?php if ($message): ?><p class="login-msg <?php echo $isError ? 'error' : 'ok'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
  <div class="login-links"><a href="login.php">&larr; Back to sign in</a></div>
</div>
</body>
</html>
