<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html lang="fa">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود به پنل نمایندگان</title>
<link rel="stylesheet" href="<?php echo esc_url(SAV_URL . 'assets/css/dashboard.css'); ?>">
</head>
<body class="sav-login-body">
  <div class="sav-login-card">
    <h2>ورود به پنل نمایندگان</h2>
    <?php if (!empty($_SESSION['sav_error'])): ?>
      <div class="sav-alert"><?php echo esc_html($_SESSION['sav_error']); unset($_SESSION['sav_error']); ?></div>
    <?php endif; ?>
    <form method="post">
      <?php wp_nonce_field('sav_login_nonce', 'sav_login_nonce'); ?>
      <label>نام کاربری</label>
      <input type="text" name="username" required>
      <label>رمز عبور</label>
      <input type="password" name="password" required>
      <button type="submit" name="sav_login" class="sav-btn-primary">ورود</button>
    </form>
   
  </div>
</body>
</html>
