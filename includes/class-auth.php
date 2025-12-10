<?php
if (!defined('ABSPATH')) exit;

class SAV_Auth {

  public static function init(){
    // مطمئن شو سشن بالا هست
    // add_action('init', function () {
    //   if (!session_id()) {
    //     session_start();
    //   }
    // }, 1);

    add_action('init',[__CLASS__,'handle_login']);
    add_action('init',[__CLASS__,'handle_logout']);
  }

  // آیا لاگین هست؟
  public static function is_logged_in(){
    return !empty($_SESSION['sav_logged_in']) && !empty($_SESSION['sav_user_id']);
  }

  // گرفتن کاربر جاری از جدول اختصاصی
  public static function current_user(){
    if (!self::is_logged_in()) return null;
    global $wpdb;
    $t = $wpdb->prefix.'service_agents_admins';
    $id = intval($_SESSION['sav_user_id']);
    if (!$id) return null;

    // با id و active بگیر
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$t` WHERE `id`=%d AND `status`='active'", $id));
    if (!$row) {
      // اگر به هر دلیل کاربر حذف شده بود، سشن را خالی کن
      self::logout_session();
      return null;
    }
    return $row;
  }

  // بررسی دسترسی
  public static function user_can($cap){
    $u = self::current_user(); 
    if (!$u) return false;

    $map = [
      'view_dashboard' => ['admin','editor'],
      'manage_agents'  => ['admin','editor'],
      'manage_users'   => ['admin'],
    ];
    $allowed = isset($map[$cap]) ? $map[$cap] : ['admin'];
    return in_array($u->role, $allowed, true);
  }

  // هندل لاگین
  public static function handle_login(){
    if (isset($_POST['sav_login'])) {
      check_admin_referer('sav_login_nonce','sav_login_nonce');

      $username = sanitize_user($_POST['username'] ?? '');
      $password = (string)($_POST['password'] ?? '');

      if ($username === '' || $password === '') {
        $_SESSION['sav_error'] = 'نام کاربری یا رمز عبور خالی است.';
        wp_safe_redirect(home_url('/service-agent-management'));
        exit;
      }

      global $wpdb;
      $t = $wpdb->prefix.'service_agents_admins';

      $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM `$t` WHERE `username`=%s AND `status`='active'",
        $username
      ));

      if ($row && wp_check_password($password, $row->password)) {
        // قبل از گذاشتن کاربر جدید، سشن قبلی را پاک کن
        self::logout_session(false);

        $_SESSION['sav_logged_in'] = true;
        $_SESSION['sav_user_id']   = intval($row->id);
        $_SESSION['sav_username']  = $row->username; // فقط برای نمایش

        // ثبت آخرین ورود
        $wpdb->update($t, ['last_login' => current_time('mysql')], ['id' => $row->id]);

        wp_safe_redirect(home_url('/service-agent-management/dashboard'));
        exit;
      } else {
        $_SESSION['sav_error'] = 'نام کاربری یا رمز عبور اشتباه است.';
        wp_safe_redirect(home_url('/service-agent-management'));
        exit;
      }
    }
  }

  // هندل خروج
  public static function handle_logout(){
    if (isset($_GET['sav_logout'])) {
      self::logout_session();
      wp_safe_redirect(home_url('/service-agent-management'));
      exit;
    }
  }

  // پاک کردن سشن
  protected static function logout_session($destroy = true){
    $_SESSION['sav_logged_in'] = false;
    unset($_SESSION['sav_user_id'], $_SESSION['sav_username']);
    if ($destroy) {
      // اختیاری: کل سشن را نابود کن
       session_destroy();
    }
  }
}

SAV_Auth::init();
