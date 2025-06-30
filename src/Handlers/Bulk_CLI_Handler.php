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

    public function __construct(protected Product_Collector $product_collector, protected Job_Processor $job_processor) {
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
    public function create_bulk_run(string $task, ?int $limit = 10, ?string $lang = 'en'): void {
        $args = array(
            'limit' => $limit,
            'lang' => $lang,
        );
        $product_ids = $this->product_collector->collect_ids( $args );
        if (!$product_ids) {
            \WP_CLI::error( 'No matching products found.' );
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