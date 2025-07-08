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
     * Start the latest bulk run or select from available runs.
     *
     * @param array $flags
     */
    #[CLI_Command(
        command: 'start',
        summary: 'Start the latest bulk task or select from available runs',
        args: array(
            array(
                'default'     => false,
                'description' => 'Show a selection of available runs to choose from',
                'name'        => 'select',
                'optional'    => true,
                'type'        => 'flag',
                'var'         => 'select',
            ),
        ),
        params: array(),
    )]
    public function start_bulk_run( array $flags ): void {
        $select_mode = $flags['select'] ?? false;

        $run = $select_mode ? $this->select_run_from_available() : Run::get_latest();

        if ( null === $run ) {
            \WP_CLI::error( 'No bulk runs found.' );
            return;
        }

        // Check if run is in a final state
        if ( $run->get_status()->isFinal() ) {
            \WP_CLI::error( 'Selected run is already completed, failed, or cancelled.' );
            return;
        }

        $job = $run->get_next_job();
        if ( null === $job ) {
            \WP_CLI::error( 'No pending jobs found in this run.' );
            return;
        }

        \WP_CLI::log( 'Starting run: ' . $run->get_display_string() );

        $run->start();
        while ( null !== $job ) {
            $this->job_processor->process_job( $job );
            $job = $run->get_next_job();
        }
        $run->complete();
        \WP_CLI::log( 'Bulk run completed: ' . $run->get_id() );
    }

    /**
     * Clear all runs and jobs from the database.
     *
     * @param array $flags
     */
    #[CLI_Command(
        command: 'clear',
        summary: 'Clear all runs and jobs from the database',
        args: array(
            array(
                'default'     => false,
                'description' => 'Force clear without confirmation prompt',
                'name'        => 'force',
                'optional'    => true,
                'type'        => 'flag',
                'var'         => 'force',
            ),
        ),
        params: array(),
    )]
    public function clear_runs( array $flags ): void {
        $force = $flags['force'] ?? false;

        // Get counts before clearing
        $runs_count = Run::get_count();
        $jobs_count = Job::get_count();

        if ( 0 === $runs_count && 0 === $jobs_count ) {
            \WP_CLI::success( 'Database is already empty. No runs or jobs to clear.' );
            return;
        }

        // Show what will be cleared
        \WP_CLI::log( 'This will clear:' );
        \WP_CLI::log( "- {$runs_count} run(s)" );
        \WP_CLI::log( "- {$jobs_count} job(s)" );

        // Confirmation prompt unless forced
        if ( ! $force ) {
            \WP_CLI::confirm( 'Are you sure you want to clear all runs and jobs? This cannot be undone.' );
        }

        // Clear the tables (jobs first due to foreign key constraints)
        $cleared_jobs = Job::clear_all();
        $cleared_runs = Run::clear_all();

        \WP_CLI::success( "Cleared {$cleared_runs} run(s) and {$cleared_jobs} job(s) from the database." );
    }

    /**
     * List all runs with their statistics in a table format.
     *
     * @param array $flags
     */
    #[CLI_Command(
        command: 'list',
        summary: 'List all runs with their statistics',
        args: array(
            array(
                'default'     => false,
                'description' => 'Show only available (non-completed) runs',
                'name'        => 'available',
                'optional'    => true,
                'type'        => 'flag',
                'var'         => 'available',
            ),
        ),
        params: array(),
    )]
    public function list_runs( array $flags ): void {
        $available_only = $flags['available'] ?? false;

        // Get runs based on filter
        $runs = $available_only ? Run::get_available() : Run::list();

        if ( empty( $runs ) ) {
            \WP_CLI::warning( $available_only ? 'No available runs found.' : 'No runs found.' );
            return;
        }

        // Prepare table data
        $table_data = array();
        foreach ( $runs as $run ) {
            $table_data[] = $this->format_run_for_table( $run );
        }

        // Display table
        \WP_CLI\Utils\format_items(
            'table',
            $table_data,
            array(
                'ID',
                'Status',
                'Progress',
                'Jobs',
                'Task',
                'Created',
            ),
        );

        \WP_CLI::log( '' );
        \WP_CLI::log( 'Total: ' . \count( $runs ) . ' run(s)' );
    }

    /**
     * Format a run for table display.
     *
     * @param Run $run
     * @return array
     */
    protected function format_run_for_table( Run $run ): array {
        $progress  = \round( $run->get_progress() * 100, 1 );
        $completed = $run->get_completed_jobs_count();
        $total     = $run->get_total_jobs_count();
        $status    = $run->get_status()->value;

        // Format task (truncate if too long)
        $task = $run->get_task();
        if ( \strlen( $task ) > 50 ) {
            $task = \substr( $task, 0, 47 ) . '...';
        }

        // Format created date
        $created = $run->get_created_at()->format( 'Y-m-d H:i' );

        return array(
            'Created'  => $created,
            'ID'       => $run->get_id(),
            'Jobs'     => "{$completed}/{$total}",
            'Progress' => $progress . '%',
            'Status'   => \strtoupper( $status ),
            'Task'     => $task,
        );
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

    /**
     * Select a run from available runs.
     *
     * @return Run|null
     */
    protected function select_run_from_available(): ?Run {
        $available_runs = Run::get_available();

        if ( empty( $available_runs ) ) {
            \WP_CLI::error( 'No available runs found.' );
            return null;
        }

        // Create choices array with display strings (indexed by position)
        $choices = array();
        foreach ( $available_runs as $run ) {
            $choices[] = $run->get_display_string();
        }

        \WP_CLI::log( 'Available runs:' );

        $selected_display_string = CLI_Handler::choice( 'Select a run to start:', $choices );

        // Find the run that matches the selected display string
        foreach ( $available_runs as $run ) {
            if ( $run->get_display_string() === $selected_display_string ) {
                return $run;
            }
        }

        \WP_CLI::error( 'Could not find the selected run.' );
        return null;
    }
}
