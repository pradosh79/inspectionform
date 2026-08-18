<?php
require_once 'auth.php';
require_once 'config.php';

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
$message = '';
$isError = false;
$done = false;

if ($token === '') {
    $message = 'Missing or invalid reset link.';
    $isError = true;
} else {
    $stmt = mysqli_prepare($conn, "SELECT id, reset_token_expires FROM users WHERE reset_token = ?");
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || strtotime($user['reset_token_expires']) < time()) {
        $message = 'This reset link is invalid or has expired. Request a new one.';
        $isError = true;
        $token = '';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm  = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

        if (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters.';
            $isError = true;
        } elseif ($password !== $confirm) {
            $message = 'Passwords do not match.';
            $isError = true;
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = mysqli_prepare($conn, "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'si', $hash, $user['id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — A &amp; D Inspections</title>
<link rel="stylesheet" href="report.css">
<style>
  .login-sheet{ max-width:380px; margin:60px auto; }
  .login-title{ font-family:Arial,sans-serif; font-size:19px; font-weight:bold; color:#2E7D32; text-align:center; margin:0 0 20px; }
  .login-sheet label{ display:block; font-family:Arial,sans-serif; font-weight:bold; font-size:13px; margin:14px 0 5px; }
  .login-sheet input[type=password]{
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
  <div class="login-title">Set a New Password</div>

  <?php if ($done): ?>
    <p class="login-msg ok">Password updated. You can sign in now.</p>
  <?php elseif ($token): ?>
    <form method="post" novalidate>
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
      <label for="password">New password (min. 8 characters)</label>
      <input type="password" id="password" name="password" required minlength="8">
      <label for="password_confirm">Confirm new password</label>
      <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
      <button class="login-btn" type="submit">Update Password</button>
    </form>
  <?php endif; ?>

  <?php if ($message): ?><p class="login-msg <?php echo $isError ? 'error' : 'ok'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
  <div class="login-links"><a href="login.php">&larr; Back to sign in</a></div>
</div>
</body>
</html>
