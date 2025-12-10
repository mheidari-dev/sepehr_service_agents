<?php
if (!defined('ABSPATH')) exit;

class SAV_Auth {

  public static function init(){
    add_action('init',[__CLASS__,'handle_login']);
    add_action('init',[__CLASS__,'handle_logout']);
  }

  // آیا لاگین هست؟
  public static function is_logged_in(){
    return is_user_logged_in();
  }

  // گرفتن کاربر جاری از وردپرس
  public static function current_user(){
    if (!self::is_logged_in()) return null;
    $user = wp_get_current_user();
    return ($user && $user->exists()) ? $user : null;
  }

  // بررسی دسترسی
  public static function user_can($cap){
    return current_user_can($cap);
  }

  // هندل لاگین
  public static function handle_login(){
    if (isset($_POST['sav_login'])) {
      check_admin_referer('sav_login_nonce','sav_login_nonce');

      $username = sanitize_user($_POST['username'] ?? '');
      $password = (string)($_POST['password'] ?? '');

      if ($username === '' || $password === '') {
        sav_redirect_with_notice('/service-agent-management', 'error', 'نام کاربری یا رمز عبور خالی است.');
      }

      $user = wp_signon([
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true,
      ], false);

      if (is_wp_error($user)) {
        sav_redirect_with_notice('/service-agent-management', 'error', $user->get_error_message());
      }

      // چک دسترسی به داشبورد
      if (!user_can($user, 'manage_service_agents')) {
        wp_logout();
        sav_redirect_with_notice('/service-agent-management', 'error', 'دسترسی لازم برای ورود به این بخش را ندارید.');
      }

      update_user_meta($user->ID, 'sav_last_login', current_time('mysql'));

      wp_safe_redirect(home_url('/service-agent-management/dashboard'));
      exit;
    }
  }

  // هندل خروج
  public static function handle_logout(){
    if (isset($_GET['sav_logout'])) {
      wp_logout();
      wp_safe_redirect(home_url('/service-agent-management'));
      exit;
    }
  }
}

SAV_Auth::init();
