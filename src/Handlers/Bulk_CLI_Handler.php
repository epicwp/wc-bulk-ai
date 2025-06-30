<?php
namespace EPICWP\WC_Bulk_AI\Handlers;

use EPICWP\WC_Bulk_AI\Services\Product_Collector;
use EPICWP\WC_Bulk_AI\Services\Job_Processor;
use EPICWP\WC_Bulk_AI\Models\Run;
use EPICWP\WC_Bulk_AI\Models\Job;

use WP_CLI;
use XWP\DI\Decorators\CLI_Command;
use XWP\DI\Decorators\CLI_Handler;

#[CLI_Handler( namespace: 'product-bulk-agent', description: 'WooCommerce Product Bulk Agent', container: 'wc-bulk-ai' )]
class Bulk_CLI_Handler {

    public function __construct(protected Product_Collector $product_collector, protected Job_Processor $job_processor) {
    }

    /**
     * Create a bulk task run.
     *
     * @param string $task
     * @param array $default_tasks
     */
    #[CLI_Command(
        command: 'create-bulk-task',
        summary: 'Create a bulk task',
        args: array(
            array(
                'description' => 'Task',
                'name'        => 'task',
                'type'        => 'positional',
                'var'         => 'task',
                'optional'    => true,
                'default'     => '',
            )
        ),
        params: array('default_tasks' => 'app.default_tasks'),
    )]
    public function create_bulk_run(string $task, array $default_tasks = []): void {

        $limit = (int) CLI_Handler::prompt( 'Enter the number of products to process: (default: 10)' ) ?? 10;

        $args = array(
            'limit' => $limit,
            'lang' => 'en',
        );
        $product_ids = $this->product_collector->collect_ids( $args );
        if (!$product_ids) {
            \WP_CLI::error( 'No matching products found.' );
            return;
        }
        \WP_CLI::log( 'Found matching ' . count( $product_ids ) . ' product(s).' );

        if ('' === $task) {
            $task = CLI_Handler::prompt( 'What task would you like me to perform on these products? (leave empty to select from predefined tasks):' );
        }

        if ('' === $task) {
            $task = CLI_Handler::choice( 'Select from predefined tasks', $default_tasks );
        }
        
        $run = Run::create( $task );
        foreach ( $product_ids as $product_id ) {
            Job::create( $run->get_id(), $product_id );
        }
        \WP_CLI::log( 'Bulk run created with ID: ' . $run->get_id() );
        \WP_CLI::log( 'Added ' . count( $product_ids ) . ' jobs.' );
        \WP_CLI::log( 'Use `wp product-bulk-agent start` to start this run.' );
    }

    /**
     * Start the latest bulk run.
     */
    #[CLI_Command(
        command: 'start',
        summary: 'Start the latest bulk task',
        args: array(),
        params: array(),
    )]
    public function start_bulk_run(): void {
        $run = Run::get_latest();
        if ( null === $run ) {
            \WP_CLI::error( 'No bulk runs found.' );
            return;
        }
        $job = $run->get_next_job();
        if ( null === $job ) {
            \WP_CLI::error( 'No jobs found.' );
            return;
        }
        $run->start();
        while ( null !== $job ) {
            $this->job_processor->process_job( $job );
            $job = $run->get_next_job();
        }
        $run->complete();
        \WP_CLI::log( 'Bulk run completed: ' . $run->get_id() );
    }
}