<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NL_Activator {

    static function activate() {
        self::create_tables();
        if ( ! wp_next_scheduled( 'nl_daily_cron' ) )
            wp_schedule_event( time(), 'daily', 'nl_daily_cron' );
        add_action( 'nl_daily_cron', [ 'NL_GSC', 'daily_sync' ] );
        NL_API::register();
    }

    static function deactivate() {
        $ts = wp_next_scheduled( 'nl_daily_cron' );
        if ( $ts ) wp_unschedule_event( $ts, 'nl_daily_cron' );
    }

    private static function create_tables() {
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( "CREATE TABLE {$wpdb->prefix}nl_links (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_post_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            target_url varchar(500) NOT NULL,
            anchor varchar(255) NOT NULL DEFAULT '',
            type enum('internal','external','sponsored') NOT NULL DEFAULT 'internal',
            status enum('active','disabled','pending') NOT NULL DEFAULT 'active',
            clicks int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_post_id (source_post_id),
            KEY type (type),
            KEY status (status)
        ) $c;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}nl_keywords (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NULL DEFAULT NULL,
            keyword varchar(255) NOT NULL,
            target_url varchar(500) NOT NULL,
            weight tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
            type enum('internal','sponsored') NOT NULL DEFAULT 'internal',
            active tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY keyword (keyword(191)),
            KEY active (active)
        ) $c;" );

        update_option( 'nl_db_version', NL_VERSION );
    }

    static function uninstall() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_links" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_keywords" );
        foreach ( [ 'nl_db_version','nl_options','nl_gsc_token','nl_plan' ] as $o )
            delete_option( $o );
    }
}
