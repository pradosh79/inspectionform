<?php
/**
 * ONE-TIME USE: creates the first login account.
 * Visit this file once in the browser, create the account, then
 * DELETE THIS FILE from the server. Leaving it live would let
 * anyone create new login accounts.
 */
require_once 'config.php';

$message = '';
$isError = false;
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($username === '' || $email === '' || strlen($password) < 8) {
        $message = 'Fill in all fields; password must be at least 8 characters.';
        $isError = true;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password_hash, created_at) VALUES (?,?,?,?)");
        $now = date('Y-m-d H:i:s');
        mysqli_stmt_bind_param($stmt, 'ssss', $username, $email, $hash, $now);
        if (mysqli_stmt_execute($stmt)) {
            $done = true;
        } else {
            $message = 'Could not create account: ' . mysqli_stmt_error($stmt);
            $isError = true;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Create Account (one-time setup)</title>
<style>body{font-family:Arial,sans-serif;max-width:400px;margin:60px auto;} label{display:block;font-weight:bold;margin:12px 0 4px;} input{width:100%;padding:8px;box-sizing:border-box;} button{margin-top:16px;padding:10px 20px;background:#2E7D32;color:#fff;border:none;border-radius:4px;cursor:pointer;}</style>
</head><body>
<h2>Create the first login account</h2>
<p style="color:#A6432F;font-weight:bold;">Delete this file (setup_user.php) from the server immediately after use.</p>
<?php if ($done): ?>
  <p style="color:#2E7D32;">Account created. <a href="login.php">Go to sign in</a>.</p>
<?php else: ?>
  <form method="post">
    <label>Username</label><input type="text" name="username" required>
    <label>Email</label><input type="email" name="email" required>
    <label>Password (min. 8 characters)</label><input type="password" name="password" required minlength="8">
    <button type="submit">Create Account</button>
  </form>
  <?php if ($message): ?><p style="color:#A6432F;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php endif; ?>
</body></html>
