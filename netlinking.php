<?php
/**
 * Plugin Name: Netlinking SEO
 * Plugin URI:  https://culture-dev.eu
 * Description: Internal linking automation, keyword expansion via OpenAI, and Google Search Console backlink monitor.
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:      Edouard Chelbi
 * Author URI:  https://apps.culture-dev.eu
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: netlinking
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NL_VERSION',         '1.0.0' );
define( 'NL_FILE',            __FILE__ );
define( 'NL_PATH',            plugin_dir_path( __FILE__ ) );
define( 'NL_URL',             plugin_dir_url( __FILE__ ) );
define( 'NL_API_BASE',        'https://api.culture-dev.eu/plugins/wp/v1/' );
define( 'NL_FREE_PAGES',      500 );
define( 'NL_FREE_KW',         50 );

foreach ( [
    'includes/class-activator',
    'includes/class-api',
    'includes/class-license',
    'includes/class-keywords',
    'includes/class-linker',
    'includes/class-gsc',
    'admin/class-admin',
] as $f ) {
    require_once NL_PATH . $f . '.php';
}

register_activation_hook( NL_FILE, [ 'NL_Activator', 'activate' ] );
register_deactivation_hook( NL_FILE, [ 'NL_Activator', 'deactivate' ] );

add_action( 'plugins_loaded', function() {
    NL_Admin::init();
    NL_Linker::init();
    NL_GSC::init();
} );

add_action( 'save_post', 'nl_on_publish', 10, 3 );
function nl_on_publish( $id, $post, $update ) {
    if ( wp_is_post_revision( $id ) || $post->post_status !== 'publish' ) return;
    NL_API::sync_page( $id, $post );
    delete_transient( 'nl_post_' . $id );
}
