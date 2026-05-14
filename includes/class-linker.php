<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NL_Linker {

    static function init(): void {
        add_filter( 'the_content', [ __CLASS__, 'process' ], 20 );
    }

    static function process( string $content ): string {
        if ( is_admin() || ! is_singular() ) return $content;
        $post_id = get_the_ID();
        $cached  = get_transient( 'nl_post_' . $post_id );
        if ( $cached !== false ) return $cached;

        $keywords = NL_Keywords::get_active();
        if ( empty( $keywords ) ) return $content;

        [ $masked, $map ] = self::mask( $content );
        $masked = self::inject( $masked, $keywords, $post_id );
        $result = self::unmask( $masked, $map );

        self::track_external( $result, $post_id );
        set_transient( 'nl_post_' . $post_id, $result, 12 * HOUR_IN_SECONDS );
        return $result;
    }

    private static function mask( string $content ): array {
        $map     = [];
        $counter = 0;
        $patterns = [
            '/(<a\b[^>]*>.*?<\/a>)/si',
            '/(<script\b[^>]*>.*?<\/script>)/si',
            '/(<style\b[^>]*>.*?<\/style>)/si',
            '/(<[^>]+>)/s',
        ];
        foreach ( $patterns as $p ) {
            $content = preg_replace_callback( $p, function( $m ) use ( &$map, &$counter ) {
                $ph = ' ##NL' . $counter++ . '## ';
                $map[ $ph ] = $m[0];
                return $ph;
            }, $content );
        }
        return [ $content, $map ];
    }

    private static function unmask( string $content, array $map ): string {
        return str_replace( array_keys( $map ), array_values( $map ), $content );
    }

    private static function inject( string $content, array $keywords, int $post_id ): string {
        $current_url = get_permalink( $post_id );
        $linked      = [];

        usort( $keywords, fn( $a, $b ) => $b->weight <=> $a->weight );

        foreach ( $keywords as $kw ) {
            if ( untrailingslashit( $kw->target_url ) === untrailingslashit( $current_url ) ) continue;
            $phrase  = preg_quote( $kw->keyword, '/' );
            $pattern = '/(?<!\w)(' . $phrase . ')(?!\w)/iu';
            if ( isset( $linked[ $kw->target_url ] ) ) continue;

            $new = preg_replace_callback( $pattern, function( $m ) use ( $kw, &$linked ) {
                if ( isset( $linked[ $kw->target_url ] ) ) return $m[0];
                $linked[ $kw->target_url ] = true;
                $rel = $kw->type === 'sponsored' ? ' rel="sponsored noopener"' : ' rel="noopener"';
                return '<a href="' . esc_url( $kw->target_url ) . '"' . $rel . '>' . esc_html( $m[1] ) . '</a>';
            }, $content, 1 );

            if ( $new !== $content ) $content = $new;
        }
        return $content;
    }

    private static function track_external( string $content, int $post_id ): void {
        global $wpdb;
        $host = parse_url( home_url(), PHP_URL_HOST );
        preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/si', $content, $m );
        if ( empty( $m[1] ) ) return;
        foreach ( $m[1] as $i => $url ) {
            $url_host = parse_url( $url, PHP_URL_HOST );
            if ( ! $url_host || $url_host === $host ) continue;
            $anchor = wp_strip_all_tags( $m[2][ $i ] ?? '' );
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}nl_links (source_post_id, target_url, anchor, type, status)
                 VALUES (%d,%s,%s,'external','active')
                 ON DUPLICATE KEY UPDATE anchor=VALUES(anchor)",
                $post_id, $url, $anchor
            ) );
        }
    }
}
