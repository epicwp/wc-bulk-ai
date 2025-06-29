<?php
namespace EPICWP\WC_Bulk_AI\Handlers;

use EPICWP\WC_Bulk_AI\Services\Product_Collector;

use WP_CLI;
use XWP\DI\Decorators\CLI_Command;
use XWP\DI\Decorators\CLI_Handler;

#[CLI_Handler( namespace: 'wc-bulk-ai', description: 'WooCommerce Bulk AI', container: 'wc-bulk-ai' )]
class Bulk_CLI_Handler {

    public function __construct(protected Product_Collector $product_collector) {
    }

    #[CLI_Command(
        command: 'create',
        summary: 'Create a bulk task run',
        args: array(
            array(
                'description' => 'Task',
                'name'        => 'task',
                'type'        => 'positional',
                'var'         => 'task',
                'optional'    => false,
            ),
            array(
                'description' => 'Limit',
                'name'        => 'limit',
                'type'        => 'positional',
                'var'         => 'limit',
                'optional'    => true,
            ),
            array(
                'description' => 'Language',
                'name'        => 'lang',
                'type'        => 'positional',
                'var'         => 'lang',
                'optional'    => true,
            ),
        ),
        params: array(),
    )]
    public function create_bulk_run(string $task, ?int $limit = null, ?string $lang = null): void {
        \WP_CLI::log( 'Bulk run created' );
    }

    #[CLI_Command(
        command: 'start',
        summary: 'Start the latest bulk run',
        args: array(),
        params: array(),
    )]
    public function start_bulk_run(): void {
        \WP_CLI::log( 'Bulk run started' );
    }

    #[CLI_Command(
        command: 'collect-products',
        summary: 'Collect products from the database',
        args: array(
            array(
                'description' => 'Limit',
                'name'        => 'limit',
                'type'        => 'positional',
                'var'         => 'limit',
                'optional'    => true,
            ),
            array(
                'description' => 'Language',
                'name'        => 'lang',
                'type'        => 'positional',
                'var'         => 'lang',
                'optional'    => true,
            ),
        ),
        params: array(),
    )]
    public function collect_products(?int $limit = null, ?string $lang = null): void {
        $args = array(
            'lang'  => $lang ?? 'en', // TODO: Set to default language from the site without knowing the language.
            'limit' => $limit,
        );
        $products = $this->product_collector->collect_ids( $args );
        foreach ( $products as $product ) {
            \WP_CLI::log( 'Product: ' . wc_get_product( $product )->get_title() );
        }
        \WP_CLI::log( 'Products collected' );
    }

}