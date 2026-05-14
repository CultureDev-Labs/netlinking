<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NL_Admin {

    static function init(): void {
        add_action( 'admin_menu',    [ __CLASS__, 'menus' ] );
        add_action( 'admin_post_nl_save_settings', [ __CLASS__, 'save_settings' ] );
        add_action( 'admin_post_nl_save_keyword',  [ __CLASS__, 'save_keyword' ] );
        add_action( 'admin_post_nl_delete_keyword',[ __CLASS__, 'delete_keyword' ] );
        add_action( 'wp_ajax_nl_expand_kw',        [ __CLASS__, 'ajax_expand' ] );
        add_action( 'wp_ajax_nl_check_link',       [ __CLASS__, 'ajax_check_link' ] );
    }

    static function menus(): void {
        add_menu_page( 'Netlinking SEO', 'Netlinking SEO', 'manage_options', 'netlinking', [ __CLASS__, 'page_dashboard' ], 'dashicons-admin-links', 30 );
        add_submenu_page( 'netlinking', 'Dashboard',         'Dashboard',         'manage_options', 'netlinking',           [ __CLASS__, 'page_dashboard' ] );
        add_submenu_page( 'netlinking', 'Keywords',          'Keywords',          'manage_options', 'netlinking-keywords',  [ __CLASS__, 'page_keywords' ] );
        add_submenu_page( 'netlinking', 'Backlink Monitor',  'Backlink Monitor',  'manage_options', 'netlinking-backlinks', [ __CLASS__, 'page_backlinks' ] );
        add_submenu_page( 'netlinking', 'Settings',          'Settings',          'manage_options', 'netlinking-settings',  [ __CLASS__, 'page_settings' ] );
    }

    static function page_dashboard():  void { include NL_PATH . 'admin/views/dashboard.php'; }
    static function page_keywords():   void { include NL_PATH . 'admin/views/keywords.php'; }
    static function page_backlinks():  void { include NL_PATH . 'admin/views/backlinks.php'; }
    static function page_settings():   void { include NL_PATH . 'admin/views/settings.php'; }

    static function save_settings(): void {
        check_admin_referer( 'nl_settings' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        $opts = [
            'license_key'       => sanitize_text_field( $_POST['license_key'] ?? '' ),
            'email'             => sanitize_email( $_POST['email'] ?? '' ),
            'openai_key'        => sanitize_text_field( $_POST['openai_key'] ?? '' ),
            'openai_model'      => sanitize_text_field( $_POST['openai_model'] ?? 'gpt-4.1-nano' ),
            'gsc_client_id'     => sanitize_text_field( $_POST['gsc_client_id'] ?? '' ),
            'gsc_client_secret' => sanitize_text_field( $_POST['gsc_client_secret'] ?? '' ),
        ];
        update_option( 'nl_options', $opts );
        delete_transient( 'nl_plan' );
        wp_safe_redirect( admin_url( 'admin.php?page=netlinking-settings&saved=1' ) );
        exit;
    }

    static function save_keyword(): void {
        check_admin_referer( 'nl_keyword' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        NL_Keywords::save( $_POST );
        wp_safe_redirect( admin_url( 'admin.php?page=netlinking-keywords&saved=1' ) );
        exit;
    }

    static function delete_keyword(): void {
        check_admin_referer( 'nl_del_kw_' . (int) $_POST['id'] );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        NL_Keywords::delete( (int) $_POST['id'] );
        wp_safe_redirect( admin_url( 'admin.php?page=netlinking-keywords&deleted=1' ) );
        exit;
    }

    static function ajax_expand(): void {
        check_ajax_referer( 'nl_expand' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        NL_Keywords::expand_with_openai( (int) ( $_POST['kw_id'] ?? 0 ) );
        wp_send_json_success();
    }

    static function ajax_check_link(): void {
        check_ajax_referer( 'nl_check' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        $url  = esc_url_raw( $_POST['url'] ?? '' );
        $resp = wp_remote_head( $url, [ 'timeout' => 5, 'redirection' => 3 ] );
        wp_send_json_success( [ 'code' => is_wp_error( $resp ) ? 0 : wp_remote_retrieve_response_code( $resp ) ] );
    }
}
