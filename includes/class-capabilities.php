<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SAV_Capabilities {
    const ROLE = 'service_agent_manager';
    const ROLE_NAME = 'Service Agent Manager';

    /**
     * Return the base capability map for service agent managers.
     */
    public static function caps() {
        return [
            'read'                       => true,
            'upload_files'               => true,
            'manage_service_agents'      => true,
            'manage_service_agent_users' => true,
            'sav_agent_save'             => true,
            'sav_agent_delete'           => true,
            'sav_user_save'              => true,
            'sav_user_delete'            => true,
        ];
    }

    /**
     * Register the service agent manager role and attach capabilities to admin.
     */
    public static function register() {
        $caps = self::caps();

        add_role( self::ROLE, self::ROLE_NAME, $caps );

        if ( $admin = get_role( 'administrator' ) ) {
            foreach ( array_keys( $caps ) as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    /**
     * Remove custom capabilities from admin and delete the role.
     */
    public static function unregister() {
        $caps = self::caps();

        if ( $admin = get_role( 'administrator' ) ) {
            foreach ( array_keys( $caps ) as $cap ) {
                $admin->remove_cap( $cap );
            }
        }

        remove_role( self::ROLE );
    }
}
