<?php
namespace EPICWP\WC_Bulk_AI\Handlers;

use EPICWP\WC_Bulk_AI\Models\Job;
use EPICWP\WC_Bulk_AI\Models\Run;
use EPICWP\WC_Bulk_AI\Services\Job_Processor;
use EPICWP\WC_Bulk_AI\Services\Product_Collector;
use WP_CLI;
use XWP\DI\Decorators\CLI_Command;
use XWP\DI\Decorators\CLI_Handler;

#[CLI_Handler(
    namespace: 'product-bulk-agent',
    description: 'WooCommerce Product Bulk Agent',
    container: 'wc-bulk-ai',
)]
class Bulk_CLI_Handler {
    public function __construct( protected Product_Collector $product_collector, protected Job_Processor $job_processor ) {
    }

    /**
     * Create a bulk task run.
     *
     * @param string $task
     * @param array  $flags
     * @param array  $default_tasks
     */
    #[CLI_Command(
        command: 'create-bulk-task',
        summary: 'Create a bulk task',
        args: array(
            array(
                'default'     => '',
                'description' => 'Task',
                'name'        => 'task',
                'optional'    => true,
                'type'        => 'positional',
                'var'         => 'task',
            ),
            array(
                'default'     => '',
                'description' => 'Language code (e.g., en, fr, de). Leave empty to select products from all languages.',
                'name'        => 'lang',
                'optional'    => true,
                'type'        => 'assoc',
                'var'         => 'lang',
            ),
            array(
                'default'     => '',
                'description' => 'Category slug to filter products. Leave empty to select products from all categories.',
                'name'        => 'category',
                'optional'    => true,
                'type'        => 'assoc',
                'var'         => 'category',
            ),
        ),
        params: array( 'default_tasks' => 'app.default_tasks' ),
    )]
    public function create_bulk_run( string $task, array $flags, array $default_tasks = array() ): void {
        // Extract parameters from the flags array
        $lang     = $flags['lang'] ?? '';
        $category = $flags['category'] ?? '';

        // First prompt for required parameters.
        $limit = $this->prompt_for_number_of_products();

        // Collect product IDs.
        $product_ids = $this->collect_product_ids( $limit, $category, $lang );
        $this->communicate_matching_products( $product_ids );

        // Prompt for task if not provided.
        $task = $this->prompt_for_task( $task, $default_tasks );

        // Create run and jobs.
        $run = Run::create( $task );
        foreach ( $product_ids as $product_id ) {
            Job::create( $run->get_id(), $product_id );
        }

        // Communicate results.
        \WP_CLI::log( 'Bulk run created with ID: ' . $run->get_id() );
        \WP_CLI::log( 'Added ' . \count( $product_ids ) . ' jobs.' );
        \WP_CLI::log( 'Use `wp product-bulk-agent start` to start this run.' );
    }

    /**
     * Start the latest bulk run.
     */
    #[CLI_Command( command: 'start', summary: 'Start the latest bulk task', args: array(), params: array() )]
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

    /**
     * Prompt for the number of products to process.
     *
     * @return int
     */
    protected function prompt_for_number_of_products(): int {
        $limit = (int) CLI_Handler::prompt( 'Enter the number of products to process: (default: 10)' ) ?? 10;
        return $limit;
    }

    /**
     * Prompt for the task to perform.
     *
     * @param string $task
     * @param array  $default_tasks
     * @return string
     */
    protected function prompt_for_task( string $task, array $default_tasks ): string {
        $task = CLI_Handler::prompt(
            'Enter the task to perform: (leave empty to select from predefined tasks):',
        );
        if ( '' === $task ) {
            $task = CLI_Handler::choice( 'Select from predefined tasks', $default_tasks );
        }
        return $task;
    }

    /**
     * Prompt for the category to process.
     *
     * @return string
     */
    protected function prompt_for_category(): string {
        $category = CLI_Handler::prompt(
            'Enter the category slug to process: (leave empty to select from predefined categories):',
        );
        if ( '' === $category ) {
            $category = CLI_Handler::choice( 'Select from predefined categories', $default_categories );
        }
        return $category;
    }

    /**
     * Communicate the number of matching products.
     *
     * @param array $product_ids
     */
    protected function communicate_matching_products( array $product_ids ): void {
        if ( 0 === \count( $product_ids ) ) {
            \WP_CLI::error( 'No matching products found.' );
            return;
        }
        \WP_CLI::log( 'Found matching ' . \count( $product_ids ) . ' product(s).' );
    }

    /**
     * Collect product IDs.
     *
     * @param int    $limit
     * @param string $category
     * @param string $lang
     * @return array
     */
    protected function collect_product_ids( int $limit, string $category, string $lang = '' ): array {
        $args = array(
            'limit' => $limit,
        );

        // Add language filter if specified
        if ( '' !== $lang ) {
            $args['lang'] = $lang;
        }

        if ( '' !== $category ) {
            $args['tax_query'] = array(
                array(
                    'field'    => 'slug',
                    'taxonomy' => 'product_cat',
                    'terms'    => $category,
                ),
            );
        }

        $product_ids = $this->product_collector->collect_ids( $args );
        return $product_ids;
    }
}
