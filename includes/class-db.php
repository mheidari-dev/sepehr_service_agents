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

  }
  public static function ensure(){
    global $wpdb;
    $a = $wpdb->prefix.'service_agents';
    $a_ok = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$a))===$a);
    if(!$a_ok){ self::activate(); }
  }
}
