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

    self::migrate_legacy_admins();

  }
  public static function ensure(){
    global $wpdb;
    $a = $wpdb->prefix.'service_agents';
    $a_ok = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$a))===$a);
    if(!$a_ok){ self::activate(); }
    else { self::migrate_legacy_admins(); }
  }

  /**
   * Migrate legacy admin records from the custom service_agents_admins table to wp_users.
   */
  protected static function migrate_legacy_admins(){
    global $wpdb;

    if (get_option('sav_admins_migrated')) { return; }

    $legacy_table = $wpdb->prefix.'service_agents_admins';
    $exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $legacy_table)) === $legacy_table);
    if (!$exists) { return; }

    $columns = $wpdb->get_col("SHOW COLUMNS FROM `$legacy_table`", 0);
    if (empty($columns)) { return; }

    $users = $wpdb->get_results("SELECT * FROM `$legacy_table`");
    if (empty($users)) { update_option('sav_admins_migrated', 1); $wpdb->query("DROP TABLE IF EXISTS `$legacy_table`"); return; }

    foreach ($users as $user) {
      $username = self::field_value($user, ['username', 'user_name', 'login', 'user']);
      $email    = self::field_value($user, ['email', 'user_email']);
      $full     = self::field_value($user, ['full_name', 'name', 'display_name']);
      $role     = self::field_value($user, ['role']) ?: 'service_agent_manager';
      $status   = self::field_value($user, ['status']) === 'inactive' ? 'inactive' : 'active';

      $username = sanitize_user($username ?: '');
      if ($username === '') { continue; }

      if (!in_array($role, ['administrator','editor','service_agent_manager'], true)) {
        $role = 'service_agent_manager';
      }

      $password = wp_generate_password(16);

      $userdata = [
        'user_login'   => $username,
        'display_name' => $full ?: $username,
        'first_name'   => $full ?: '',
        'role'         => $role,
      ];

      if ($email && is_email($email) && !email_exists($email)) {
        $userdata['user_email'] = $email;
      }

      $existing = username_exists($username);
      if ($existing) {
        $userdata['ID'] = $existing;
        $result = wp_update_user($userdata);
        $user_id = is_wp_error($result) ? 0 : $existing;
      } else {
        $userdata['user_pass'] = $password;
        $result = wp_insert_user($userdata);
        $user_id = is_wp_error($result) ? 0 : $result;
      }

      if ($user_id) {
        update_user_meta($user_id, 'sav_user_status', $status);
      }
    }

    update_option('sav_admins_migrated', 1);
    $wpdb->query("DROP TABLE IF EXISTS `$legacy_table`");
  }

  protected static function field_value($object, array $keys){
    foreach ($keys as $key){
      if (isset($object->$key) && $object->$key !== ''){ return $object->$key; }
    }
    return null;
  }
}
