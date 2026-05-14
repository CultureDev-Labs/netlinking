<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NL_License {

    static function get_plan(): array {
        $plan = get_transient( 'nl_plan' );
        if ( $plan ) return (array) $plan;
        $opts = get_option( 'nl_options', [] );
        if ( empty( $opts['license_key'] ) || empty( $opts['email'] ) )
            return [ 'type' => 'free', 'pages' => NL_FREE_PAGES, 'kw' => NL_FREE_KW ];
        $r = NL_API::verify_license( $opts['email'], $opts['license_key'] );
        if ( $r && ! empty( $r->type ) ) {
            set_transient( 'nl_plan', $r, DAY_IN_SECONDS );
            return (array) $r;
        }
        return [ 'type' => 'free', 'pages' => NL_FREE_PAGES, 'kw' => NL_FREE_KW ];
    }

    static function is_pro(): bool {
        return ( self::get_plan()['type'] ?? 'free' ) === 'pro';
    }

    static function pages_limit(): int {
        return (int) ( self::get_plan()['pages'] ?? NL_FREE_PAGES );
    }

    static function kw_limit(): int {
        return (int) ( self::get_plan()['kw'] ?? NL_FREE_KW );
    }
}
