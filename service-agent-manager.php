<?php
/**
 * Plugin Name: SepehrElectric Service Agent Manager
 * Description: Manage after-sales service agents with a front-end dashboard, custom DB tables, and QR verification.
 * Version: 1.0.3
 * Author: Mohammad Heidari
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
add_action( 'plugins_loaded', function () {
    if ( headers_sent( $file, $line ) ) {
        error_log( 'HEADERS ALREADY SENT in ' . $file . ' on line ' . $line );
    }
}, -1 );


// مسیر و URL پلاگین
define( 'SAV_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAV_URL',  plugin_dir_url(  __FILE__ ) );

// شروع سشن در اولین لحظه ممکن (قبل از هر خروجی)
add_action( 'plugins_loaded', function() {
    if ( session_id() === '' ) {
        session_start();
    }
}, 0 );

// لود فایل‌های مورد نیاز
require_once SAV_PATH . 'includes/class-db.php';
require_once SAV_PATH . 'includes/class-auth.php';
require_once SAV_PATH . 'includes/functions.php';
require_once SAV_PATH . 'includes/class-jdate.php';

function sav_register_roles_and_caps() {
    $caps = [
        'manage_service_agents'       => true,
        'manage_service_agent_users'  => true,
        'read'                        => true,
    ];

    add_role('service_agent_manager', 'Service Agent Manager', array_merge($caps, [
        'upload_files' => true,
    ]));

    if ($admin = get_role('administrator')) {
        foreach (array_keys($caps) as $cap) {
            $admin->add_cap($cap);
        }
    }
}

// فعال‌سازی پلاگین
register_activation_hook( __FILE__, [ 'SAV_DB', 'activate' ] );
register_activation_hook( __FILE__, 'sav_create_upload_dir' );
register_activation_hook( __FILE__, 'sav_register_roles_and_caps' );
add_action('init', 'sav_register_roles_and_caps');

function sav_create_upload_dir() 
    {
    $upload_dir = wp_upload_dir();
    $custom_dir = $upload_dir['basedir'] . '/sepehr-service-agents-pics';

    if ( ! file_exists( $custom_dir ) ) {
        wp_mkdir_p( $custom_dir );
    }

    $htaccess = $custom_dir . '/.htaccess';
    if ( ! file_exists( $htaccess ) ) {
        file_put_contents( $htaccess, "Options -Indexes\n<Files ~ \"\\.(php|phtml|phps|php\\d+)$\">\ndeny from all\n</Files>" );
    }

    $index = $custom_dir . '/index.php';
    if ( ! file_exists( $index ) ) {
        file_put_contents( $index, "<?php // Silence is golden" );
    }
}