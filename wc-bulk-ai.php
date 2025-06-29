<?php
/**
 * Plugin Name: WooCommerce Bulk AI
 * Description: Update your WooCommerce products in bulk with AI.
 * Version:     1.0.0
 *
 * @package EPICWP\WC_Bulk_AI
 */

defined( 'ABSPATH' ) || exit;

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