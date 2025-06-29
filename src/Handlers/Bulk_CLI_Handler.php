<?php
namespace EPICWP\WC_Bulk_AI\Handlers;

use EPICWP\WC_Bulk_AI\Services\Product_Collector;
use EPICWP\WC_Bulk_AI\Services\Job_Processor;
use EPICWP\WC_Bulk_AI\Models\Run;
use EPICWP\WC_Bulk_AI\Models\Job;

use WP_CLI;
use XWP\DI\Decorators\CLI_Command;
use XWP\DI\Decorators\CLI_Handler;

#[CLI_Handler( namespace: 'wc-bulk-ai', description: 'WooCommerce Bulk AI', container: 'wc-bulk-ai' )]
class Bulk_CLI_Handler {

    public function __construct(protected Product_Collector $product_collector) {
    }

    /**
     * Create a bulk task run.
     *
     * @param string $task
     * @param int $limit
     * @param string $lang
     */
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
    public function create_bulk_run(string $task, ?int $limit = -1, ?string $lang = null): void {
        $args = array(
            'lang'  => $lang ?? 'en', // TODO: Set to default language from the site without knowing the language.
            'limit' => $limit,
        );
        $product_ids = $this->product_collector->collect_ids( $args );
        if (!$product_ids) {
            \WP_CLI::error( 'No products found' );
            return;
        }
        $run = Run::create( $task );
        foreach ( $product_ids as $product_id ) {
            Job::create( $run->get_id(), $product_id );
        }
        \WP_CLI::log( 'Bulk run created: ' . $run->get_id() );
    }

    /**
     * Start the latest bulk run.
     */
    #[CLI_Command(
        command: 'start',
        summary: 'Start the latest bulk run',
        args: array(),
        params: array(),
    )]
    public function start_bulk_run(): void {
        \WP_CLI::log( 'Bulk run started' );
    }
}