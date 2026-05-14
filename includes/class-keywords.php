<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NL_Keywords {

    static function get_active(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT keyword, target_url, weight, type FROM {$wpdb->prefix}nl_keywords WHERE active=1 ORDER BY weight DESC"
        );
    }

    static function count(): int {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}nl_keywords" );
    }

    static function save( array $data ): void {
        global $wpdb;
        $limit = NL_License::kw_limit();
        if ( ! NL_License::is_pro() && self::count() >= $limit ) return;
        $wpdb->replace( "{$wpdb->prefix}nl_keywords", [
            'id'         => $data['id'] ?? null,
            'post_id'    => $data['post_id'] ?? null,
            'keyword'    => sanitize_text_field( $data['keyword'] ),
            'target_url' => esc_url_raw( $data['target_url'] ),
            'weight'     => (int) ( $data['weight'] ?? 5 ),
            'type'       => in_array( $data['type'] ?? '', [ 'internal','sponsored' ] ) ? $data['type'] : 'internal',
            'active'     => 1,
        ] );
    }

    static function delete( int $id ): void {
        global $wpdb;
        $wpdb->delete( "{$wpdb->prefix}nl_keywords", [ 'id' => $id ] );
    }

    static function expand_with_openai( int $kw_id ): void {
        global $wpdb;
        $opts = get_option( 'nl_options', [] );
        if ( empty( $opts['openai_key'] ) ) return;

        $kw = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nl_keywords WHERE id=%d", $kw_id
        ) );
        if ( ! $kw ) return;

        $model = $opts['openai_model'] ?? 'gpt-4.1-nano';
        $prompt = 'Give 5 short SEO synonyms or semantic variants for the keyword "' . $kw->keyword . '". Return only a JSON array of strings, no explanation.';

        $resp = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $opts['openai_key'],
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( [
                'model'    => $model,
                'messages' => [ [ 'role' => 'user', 'content' => $prompt ] ],
                'max_tokens' => 120,
            ] ),
        ] );

        if ( is_wp_error( $resp ) ) return;
        $body = json_decode( wp_remote_retrieve_body( $resp ), true );
        $variants = json_decode( $body['choices'][0]['message']['content'] ?? '[]', true );
        if ( ! is_array( $variants ) ) return;

        foreach ( array_slice( $variants, 0, 5 ) as $v ) {
            $v = sanitize_text_field( $v );
            if ( $v && $v !== $kw->keyword ) {
                $wpdb->insert( "{$wpdb->prefix}nl_keywords", [
                    'post_id'    => $kw->post_id,
                    'keyword'    => $v,
                    'target_url' => $kw->target_url,
                    'weight'     => max( 1, (int) $kw->weight - 2 ),
                    'type'       => $kw->type,
                    'active'     => 1,
                ] );
            }
        }
    }
}
