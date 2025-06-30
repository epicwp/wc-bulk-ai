<?php
/**
 * Plugin Name: WooCommerce Bulk AI
 * Description: Update your WooCommerce products in bulk with AI.
 * Version:     1.0.0
 *
 * @package EPICWP\WC_Bulk_AI
 */

define( 'WC_BULK_AI_VERSION', '0.0.0' );

require __DIR__ . '/vendor/autoload_packages.php';

/**
 * This loads the main application module.
 *
 * We use the `xwp_load_app` function to do all the heavy lifting.
 * You can also use `xwp_create_app` to create a new container immediately.
 * However this has caveats.
 *
 * If you use the `jetpack-autloader` autoloading will be done on the `plugins_loaded` hook.
 * Using `xwp_create_app` will immediately start autoloading the classes and dependencies, which will prevent latest module versions from being loaded.
 *
 * @see https://github.com/Automattic/jetpack-autoloader
 */
xwp_load_app(
    app: array(
        'app_file'       => __FILE__,
        'app_id'         => 'wc-bulk-ai',
        'app_module'     => \EPICWP\WC_Bulk_AI\App::class,
        'app_version'    => WC_BULK_AI_VERSION,
        'cache_app'      => false,
        'cache_defs'     => false,
        'cache_dir'      => __DIR__ . '/cache',
        'cache_hooks'    => false,
        'public'         => true,
        'use_attributes' => true,
        'use_autowiring' => true,
    ),
    hook: 'plugins_loaded',
    priority: 0,
);

// Register activation hook
\register_activation_hook( __FILE__, function() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Ensure WordPress is loaded
    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }
    
    // Create jobs table
    $table_name = $wpdb->prefix . 'wcbai_jobs';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        status varchar(20) NOT NULL,
        product_id int(11) NOT NULL,
        run_id int(11) NOT NULL,
        created_at datetime NOT NULL,
        started_at datetime NOT NULL,
        finished_at datetime NOT NULL,
        error_message text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta( $sql );

    // Create runs table
    $table_name = $wpdb->prefix . 'wcbai_runs';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        task varchar(255) NOT NULL,
        status varchar(20) NOT NULL,
        created_at datetime NOT NULL,
        started_at datetime NOT NULL,
        finished_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta( $sql );
});

// Register deactivation hook
\register_deactivation_hook( __FILE__, function() {
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wcbai_jobs" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wcbai_runs" );
});

// \register_deactivation_hook( __FILE__, function() {
//     global $wpdb;
//     $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wcbai_jobs" );
//     $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wcbai_runs" );
// });