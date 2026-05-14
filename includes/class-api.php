<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NL_API {

    static function call( string $endpoint, array $payload ): ?object {
        $host   = parse_url( home_url(), PHP_URL_HOST );
        $actual = $_SERVER['HTTP_HOST'] ?? '';
        if ( $host !== $actual ) return null;

        $payload['domain'] = $host;
        $url  = NL_API_BASE . ltrim( $endpoint, '/' );
        $body = wp_json_encode( $payload );

        if ( ini_get( 'allow_url_fopen' ) ) {
            $ctx = stream_context_create( [ 'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nUser-Agent: Netlinking-SEO/" . NL_VERSION . "\r\n",
                'content' => $body,
                'timeout' => 5,
                'ignore_errors' => true,
            ] ] );
            $r = @file_get_contents( $url, false, $ctx );
        } else {
            $resp = wp_remote_post( $url, [
                'body'    => $body,
                'timeout' => 5,
                'headers' => [ 'Content-Type' => 'application/json' ],
            ] );
            $r = is_wp_error( $resp ) ? false : wp_remote_retrieve_body( $resp );
        }

        return ( $r && $r !== false ) ? json_decode( $r ) : null;
    }

    static function register(): void {
        self::call( 'register', [
            'email'      => get_option( 'admin_email' ),
            'site_url'   => home_url(),
            'wp_version' => get_bloginfo( 'version' ),
            'php_version'=> PHP_VERSION,
            'locale'     => get_locale(),
        ] );
    }

    static function sync_page( int $post_id, WP_Post $post ): void {
        $opts = get_option( 'nl_options', [] );
        self::call( 'pages/sync', [
            'license_key' => $opts['license_key'] ?? '',
            'post_id'     => $post_id,
            'post_type'   => $post->post_type,
            'slug'        => $post->post_name,
            'title'       => $post->post_title,
            'url'         => get_permalink( $post_id ),
            'lang'        => get_locale(),
        ] );
    }

    static function verify_license( string $email, string $key ): ?object {
        return self::call( 'license/verify', [
            'email'       => $email,
            'license_key' => $key,
        ] );
    }
}
