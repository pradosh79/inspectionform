<?php
require_once 'auth.php';
require_once 'config.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Enter both username and password.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, password_hash FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — A &amp; D Inspections</title>
<link rel="stylesheet" href="report.css">
<style>
  .login-sheet{ max-width:380px; margin:60px auto; }
  .login-title{ font-family:Arial,sans-serif; font-size:19px; font-weight:bold; color:#a9c09a; text-align:center; margin:0 0 20px; }
  .login-sheet label{ display:block; font-family:Arial,sans-serif; font-weight:bold; font-size:13px; margin:14px 0 5px; }
  .login-sheet input[type=text], .login-sheet input[type=password]{
    width:100%; font-family:Arial,sans-serif; font-size:14px; padding:9px 10px;
    border:1px solid #999; background:#FDF3EC; box-sizing:border-box;
  }
  .login-btn{ margin-top:20px; width:100%; padding:12px; border-radius:4px; font-family:Arial,sans-serif;
    font-weight:bold; font-size:15px; cursor:pointer; border:2px solid #a9c09a; background:#a9c09a; color:#fff; }
  .login-error{ color:#A6432F; font-family:Arial,sans-serif; font-size:13px; text-align:center; margin-top:12px; }
  .login-links{ text-align:center; margin-top:16px; font-family:Arial,sans-serif; font-size:13px; }
  .login-links a{ color:#a9c09a; text-decoration:none; }
</style>
</head>
<body>
<div class="rp-sheet no-pagenum login-sheet" style="border-color:#999;">
  <div class="login-title">A &amp; D Inspections — Sign In</div>
  <form method="post" novalidate>
    <label for="username">Username or email</label>
    <input type="text" id="username" name="username" autocomplete="username" required>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
    <button class="login-btn" type="submit">Sign In</button>
  </form>
  <?php if ($error): ?><p class="login-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
  <div class="login-links"><a href="forgot_password.php">Forgot password?</a></div>
</div>
</body>
</html>
