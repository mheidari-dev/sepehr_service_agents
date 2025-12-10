<?php
if (!defined('ABSPATH')) exit;
class SAV_DB {
  public static function activate(){
    global $wpdb;
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    $agents = $wpdb->prefix.'service_agents';
    $sql_agents = "CREATE TABLE IF NOT EXISTS `$agents` (
      `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `row_no` INT NULL,
      `province` VARCHAR(50) NULL,
      `area` VARCHAR(100) NULL,
      `agency_name` VARCHAR(150) NULL,
      `agency_code` VARCHAR(20) NULL,
      `agency_phone` VARCHAR(20) NULL,
      `person_name` VARCHAR(100) NOT NULL,
      `national_id` VARCHAR(10) NULL,
      `person_phone` VARCHAR(20) NULL,
      `valid_until` DATE NULL,
      `activity_desc` VARCHAR(100) NULL,
      `photo_url` TEXT NULL,
      `token` VARCHAR(64) NOT NULL,
      `status` ENUM('active','inactive') DEFAULT 'active',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NULL,
      UNIQUE KEY `uk_token` (`token`),
      INDEX `idx_person_name` (`person_name`),
      INDEX `idx_agency_code` (`agency_code`),
      INDEX `idx_valid_until` (`valid_until`)
    ) $charset;";
    dbDelta($sql_agents);

    $admins = $wpdb->prefix.'service_agents_admins';
    $sql_admins = "CREATE TABLE IF NOT EXISTS `$admins` (
      `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL,
      `password` VARCHAR(255) NOT NULL,
      `full_name` VARCHAR(100) NULL,
      `role` ENUM('admin','editor') DEFAULT 'editor',
      `status` ENUM('active','inactive') DEFAULT 'active',
      `last_login` DATETIME NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY `uk_username` (`username`)
    ) $charset;";
    dbDelta($sql_admins);

    $count = intval($wpdb->get_var("SELECT COUNT(*) FROM `$admins`"));
    if ($count===0){
      $wpdb->insert($admins,[
        'username'=>'admin',
        'password'=>wp_hash_password('admin123'),
        'full_name'=>'System Admin',
        'role'=>'admin',
        'status'=>'active'
      ]);
    }
  }
  public static function ensure(){
    global $wpdb;
    $a = $wpdb->prefix.'service_agents';
    $u = $wpdb->prefix.'service_agents_admins';
    $a_ok = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$a))===$a);
    $u_ok = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$u))===$u);
    if(!$a_ok || !$u_ok){ self::activate(); }
  }
}
