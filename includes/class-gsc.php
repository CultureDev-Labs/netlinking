<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NL_GSC {

    const OPT_TOKEN   = 'nl_gsc_token';
    const OAUTH_URL   = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL   = 'https://oauth2.googleapis.com/token';
    const SC_API      = 'https://www.googleapis.com/webmasters/v3/';
    const SCOPE       = 'https://www.googleapis.com/auth/webmasters.readonly';

    static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'handle_oauth_callback' ] );
    }

    static function daily_sync(): void {
        $token = self::get_valid_token();
        if ( ! $token ) return;

        $site = home_url();
        $end  = gmdate( 'Y-m-d' );
        $start = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

        $resp = wp_remote_post( self::SC_API . 'sites/' . urlencode( $site ) . '/searchAnalytics/query', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( [
                'startDate'  => $start,
                'endDate'    => $end,
                'dimensions' => [ 'page', 'query' ],
                'rowLimit'   => 1000,
            ] ),
        ] );

        if ( is_wp_error( $resp ) ) return;
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['rows'] ) ) return;

        global $wpdb;
        foreach ( $data['rows'] as $row ) {
            $url    = $row['keys'][0] ?? '';
            $anchor = $row['keys'][1] ?? '';
            if ( ! $url ) continue;
            $post_id = url_to_postid( $url );
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}nl_links (source_post_id, target_url, anchor, type, status)
                 VALUES (%d,%s,%s,'external','active')
                 ON DUPLICATE KEY UPDATE anchor=VALUES(anchor)",
                $post_id ?: 0, $url, sanitize_text_field( $anchor )
            ) );
        }
        update_option( 'nl_gsc_last_sync', current_time( 'mysql' ) );
    }

    static function get_auth_url(): string {
        $opts   = get_option( 'nl_options', [] );
        $client = $opts['gsc_client_id'] ?? '';
        if ( ! $client ) return '';
        return add_query_arg( [
            'client_id'     => $client,
            'redirect_uri'  => admin_url( 'admin.php?page=netlinking-backlinks' ),
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ], self::OAUTH_URL );
    }

    static function handle_oauth_callback(): void {
        if ( empty( $_GET['code'] ) || ( $_GET['page'] ?? '' ) !== 'netlinking-backlinks' ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $opts   = get_option( 'nl_options', [] );
        $resp   = wp_remote_post( self::TOKEN_URL, [
            'body' => [
                'code'          => sanitize_text_field( $_GET['code'] ),
                'client_id'     => $opts['gsc_client_id'] ?? '',
                'client_secret' => $opts['gsc_client_secret'] ?? '',
                'redirect_uri'  => admin_url( 'admin.php?page=netlinking-backlinks' ),
                'grant_type'    => 'authorization_code',
            ],
        ] );
        if ( is_wp_error( $resp ) ) return;
        $token = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! empty( $token['access_token'] ) ) {
            $token['expires_at'] = time() + (int) ( $token['expires_in'] ?? 3600 );
            update_option( self::OPT_TOKEN, $token );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=netlinking-backlinks&gsc=connected' ) );
        exit;
    }

    static function get_valid_token(): ?string {
        $token = get_option( self::OPT_TOKEN );
        if ( empty( $token['access_token'] ) ) return null;
        if ( time() < ( $token['expires_at'] - 60 ) ) return $token['access_token'];
        if ( empty( $token['refresh_token'] ) ) return null;

        $opts = get_option( 'nl_options', [] );
        $resp = wp_remote_post( self::TOKEN_URL, [
            'body' => [
                'refresh_token' => $token['refresh_token'],
                'client_id'     => $opts['gsc_client_id'] ?? '',
                'client_secret' => $opts['gsc_client_secret'] ?? '',
                'grant_type'    => 'refresh_token',
            ],
        ] );
        if ( is_wp_error( $resp ) ) return null;
        $new = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $new['access_token'] ) ) return null;
        $new['refresh_token'] = $token['refresh_token'];
        $new['expires_at']    = time() + (int) ( $new['expires_in'] ?? 3600 );
        update_option( self::OPT_TOKEN, $new );
        return $new['access_token'];
    }

    static function is_connected(): bool {
        return ! empty( get_option( self::OPT_TOKEN )['access_token'] );
    }
}
