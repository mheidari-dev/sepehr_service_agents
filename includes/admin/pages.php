<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the admin menu page for managing service agent capabilities.
 */
function sav_register_admin_pages() {
    add_submenu_page(
        'tools.php',
        __('Service Agent Access', 'sepehr-service-agents'),
        __('Service Agent Access', 'sepehr-service-agents'),
        'manage_service_agent_users',
        'sav-service-agent-access',
        'sav_render_access_page'
    );
}
add_action( 'admin_menu', 'sav_register_admin_pages' );

/**
 * Handle role/capability assignment form submissions.
 */
function sav_handle_access_form() {
    if ( ! isset( $_POST['sav_role_assignment_submit'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_service_agent_users' ) ) {
        return;
    }

    check_admin_referer( 'sav_role_assignment_action', 'sav_role_assignment_nonce' );

    $user_identifier = isset( $_POST['user_identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['user_identifier'] ) ) : '';
    $assign_role     = ! empty( $_POST['assign_role'] );

    $available_caps = array_keys( SAV_Capabilities::caps() );
    $selected_caps  = isset( $_POST['capabilities'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['capabilities'] ) ) : [];
    $selected_caps  = array_values( array_intersect( $available_caps, $selected_caps ) );

    if ( $user_identifier === '' ) {
        add_settings_error( 'sav_role_assignment', 'sav_role_assignment_missing_user', __( 'نام کاربری یا ایمیل را وارد کنید.', 'sepehr-service-agents' ), 'error' );
        return;
    }

    $user = get_user_by( 'login', $user_identifier );
    if ( ! $user && is_email( $user_identifier ) ) {
        $user = get_user_by( 'email', $user_identifier );
    }

    if ( ! $user ) {
        add_settings_error( 'sav_role_assignment', 'sav_role_assignment_user_not_found', __( 'کاربر یافت نشد.', 'sepehr-service-agents' ), 'error' );
        return;
    }

    if ( ! $assign_role && empty( $selected_caps ) ) {
        add_settings_error( 'sav_role_assignment', 'sav_role_assignment_no_change', __( 'هیچ نقش یا قابلیتی انتخاب نشده است.', 'sepehr-service-agents' ), 'error' );
        return;
    }

    if ( $assign_role ) {
        $user->set_role( SAV_Capabilities::ROLE );
    }

    foreach ( $selected_caps as $cap ) {
        $user->add_cap( $cap );
    }

    add_settings_error( 'sav_role_assignment', 'sav_role_assignment_success', __( 'دسترسی کاربر با موفقیت به‌روز شد.', 'sepehr-service-agents' ), 'updated' );
}
add_action( 'admin_init', 'sav_handle_access_form' );

/**
 * Retrieve users who have the service agent role or capabilities.
 *
 * @return WP_User[]
 */
function sav_get_service_agent_users() {
    $users = [];

    $queries = [
        new WP_User_Query( [ 'role__in' => [ SAV_Capabilities::ROLE ] ] ),
        new WP_User_Query( [ 'capability' => 'manage_service_agents' ] ),
    ];

    foreach ( $queries as $query ) {
        foreach ( (array) $query->get_results() as $user ) {
            if ( $user instanceof WP_User ) {
                $users[ $user->ID ] = $user;
            }
        }
    }

    uasort(
        $users,
        static function ( $a, $b ) {
            return strcmp( $a->user_login, $b->user_login );
        }
    );

    return array_values( $users );
}

/**
 * Render the service agent access management page.
 */
function sav_render_access_page() {
    if ( ! current_user_can( 'manage_service_agent_users' ) ) {
        wp_die( __( 'You do not have permission to access this page.', 'sepehr-service-agents' ) );
    }

    $available_caps = SAV_Capabilities::caps();
    $users          = sav_get_service_agent_users();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'مدیریت دسترسی نمایندگان خدمات', 'sepehr-service-agents' ); ?></h1>

        <?php settings_errors( 'sav_role_assignment' ); ?>

        <h2><?php esc_html_e( 'انتصاب نقش/قابلیت', 'sepehr-service-agents' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'sav_role_assignment_action', 'sav_role_assignment_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="user_identifier"><?php esc_html_e( 'نام کاربری یا ایمیل', 'sepehr-service-agents' ); ?></label></th>
                        <td>
                            <input type="text" name="user_identifier" id="user_identifier" class="regular-text" required />
                            <p class="description"><?php esc_html_e( 'کاربر مورد نظر برای دریافت نقش یا قابلیت‌های پلاگین را مشخص کنید.', 'sepehr-service-agents' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'نقش پلاگین', 'sepehr-service-agents' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="assign_role" value="1" checked />
                                <?php echo esc_html( SAV_Capabilities::ROLE_NAME ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'با فعال‌سازی، نقش اختصاصی پلاگین به کاربر داده می‌شود.', 'sepehr-service-agents' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'قابلیت‌های اختصاصی', 'sepehr-service-agents' ); ?></th>
                        <td>
                            <?php foreach ( $available_caps as $cap_key => $cap_enabled ) : ?>
                                <label style="display:block;margin-bottom:6px;">
                                    <input type="checkbox" name="capabilities[]" value="<?php echo esc_attr( $cap_key ); ?>" <?php checked( true, $cap_enabled ); ?> />
                                    <code><?php echo esc_html( $cap_key ); ?></code>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'قابلیت‌های مورد نیاز را برای کاربر فعال کنید.', 'sepehr-service-agents' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary" name="sav_role_assignment_submit" value="1">
                    <?php esc_html_e( 'ذخیره دسترسی', 'sepehr-service-agents' ); ?>
                </button>
            </p>
        </form>

        <h2><?php esc_html_e( 'کاربران دارای دسترسی', 'sepehr-service-agents' ); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'کاربر', 'sepehr-service-agents' ); ?></th>
                    <th><?php esc_html_e( 'ایمیل', 'sepehr-service-agents' ); ?></th>
                    <th><?php esc_html_e( 'نقش‌ها', 'sepehr-service-agents' ); ?></th>
                    <th><?php esc_html_e( 'قابلیت‌های پلاگین', 'sepehr-service-agents' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $users ) ) : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e( 'هیچ کاربری با دسترسی مورد نظر یافت نشد.', 'sepehr-service-agents' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $users as $user ) : ?>
                        <tr>
                            <td>
                                <?php echo esc_html( $user->user_login ); ?>
                                <br />
                                <span class="description"><?php echo esc_html( $user->display_name ); ?></span>
                            </td>
                            <td><?php echo esc_html( $user->user_email ); ?></td>
                            <td>
                                <?php echo esc_html( implode( ', ', $user->roles ) ); ?>
                            </td>
                            <td>
                                <?php
                                $user_caps = [];
                                foreach ( array_keys( $available_caps ) as $cap_key ) {
                                    if ( user_can( $user, $cap_key ) ) {
                                        $user_caps[] = $cap_key;
                                    }
                                }

                                if ( empty( $user_caps ) ) {
                                    esc_html_e( 'هیچ‌کدام', 'sepehr-service-agents' );
                                } else {
                                    echo esc_html( implode( ', ', $user_caps ) );
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
