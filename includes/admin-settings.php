<?php
/**
 * BadgeOS LearnDash Settings
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class BadgeOS_ld_Admin_Settings
 */
class BadgeOS_ld_Admin_Settings {

    public $page_tab;

    public function __construct() {

        $this->page_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

        add_filter( 'admin_footer_text', [ $this, 'remove_footer_admin' ] );
        add_action( 'admin_menu', [ $this, 'wn_bos_ld_admin_settings_page'] );
        add_action( 'admin_post_wn_bos_ld_admin_settings', [ $this, 'wn_bos_ld_admin_settings_save' ] );
        add_action( 'admin_notices', [ $this, 'wn_bos_ld_admin_notices'] );
    }

    /**
     *  Save plugin options
     */
    public function wn_bos_ld_admin_settings_save() {

        if( isset($_POST['wn_bos_ld_settings_submit']) ) {

            $wn_bos_ld_options = array();

            $wn_bos_ld_options['quiz_points_as_badgeos_points'] = isset( $_POST['quiz_points_as_badgeos_points'] ) ? $_POST['quiz_points_as_badgeos_points'] : 'no';
            $wn_bos_ld_options['badgeos_learndash_quiz_score_multiplier'] = isset( $_POST['badgeos_learndash_quiz_score_multiplier'] ) ? (int) $_POST['badgeos_learndash_quiz_score_multiplier'] : 1;

            if( isset($_POST['bos_ld_quiz_point_type']) ) {
                $bos_ld_quiz_point_type = absint($_POST['bos_ld_quiz_point_type']);
                $wn_bos_ld_options['bos_ld_quiz_point_type'] = $bos_ld_quiz_point_type;
            }

            update_option( 'wn_bos_ld_options', $wn_bos_ld_options );
            wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $_POST['_wp_http_referer'] ) );
            exit;
        }
    }

    /**
     * Display Notices
     */
    public function wn_bos_ld_admin_notices() {

        $screen = get_current_screen();
        if( $screen->base != 'badgeos_page_badgeos_learndash_settings' ) {
            return;
        }

        if( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == 'true' ) {
            $class = 'notice notice-success is-dismissible';
            $message = __( 'Settings Saved', 'badgeos-learndash' );
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        }
    }

    /**
     * Create admin settings page
     */
    public function wn_bos_ld_admin_settings_page() {

        add_submenu_page(
            'badgeos_badgeos',
            __( 'BadgeOS LearnDash', 'badgeos-learndash' ),
            __( 'BadgeOS LearnDash', 'badgeos-learndash' ),
            'manage_options',
            'badgeos_learndash_settings',
            [ $this, 'wn_bos_ld_settings_callback_func' ]
        );
    }

    /**
     * Callback function for Setting Page
     */
    public function wn_bos_ld_settings_callback_func() {
        ?>
        <div class="wrap">
            <div class="icon-options-general icon32"></div>
            <h1><?php echo __( 'BadgeOS LearnDash Settings', 'badgeos-learndash' ); ?></h1>

            <div class="nav-tab-wrapper">
                <?php
                $wn_bos_ld_settings_sections = $this->wn_bos_ld_get_setting_sections();
                foreach( $wn_bos_ld_settings_sections as $key => $wn_bos_ld_settings_section ) {
                    ?>
                    <a href="?page=badgeos_learndash_settings&tab=<?php echo $key; ?>"
                       class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo $wn_bos_ld_settings_section['icon']; ?>"></span>
                        <?php _e( $wn_bos_ld_settings_section['title'], 'badgeos-learndash' ); ?>
                    </a>
                    <?php
                }
                ?>
            </div>

            <?php
            foreach( $wn_bos_ld_settings_sections as $key => $wn_bos_ld_settings_section ) {
                if( $this->page_tab == $key ) {
                    include( 'admin-templates/' . $key . '.php' );
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Settings Sections
     *
     * @return mixed|void
     */
    public function wn_bos_ld_get_setting_sections() {

        $wn_bos_ld_settings_sections = array(
            'general' => array(
                'title' => __( 'General Option', 'badgeos-learndash' ),
                'icon' => 'dashicons-admin-generic',
            )
        );

        return apply_filters( 'wn_bos_ld_settings_sections', $wn_bos_ld_settings_sections );
    }

    /**
     * Add footer branding
     *
     * @param $footer_text
     * @return mixed
     */
    function remove_footer_admin ( $footer_text ) {
        if( isset( $_GET['page'] ) && ( $_GET['page'] == 'badgeos_learndash_settings' ) ) {
            _e('Fueled by <a href="http://www.wordpress.org" target="_blank">WordPress</a> | developed and designed by <a href="https://wooninjas.com" target="_blank">The WooNinjas</a></p>', 'badgeos-learndash' );
        } else {
            return $footer_text;
        }
    }
}

$GLOBALS['badgeos_learndash_options'] = new BadgeOS_ld_Admin_Settings();