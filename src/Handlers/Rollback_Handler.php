<?php

namespace EPICWP\WC_Bulk_AI\Handlers;

use EPICWP\WC_Bulk_AI\Services\Rollback_Service;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'init', priority: 10 )]
class Rollback_Handler {
    /**
     * Constructor.
     *
     * @param Rollback_Service $rollback_service The rollback service.
     */
    public function __construct( protected Rollback_Service $rollback_service ) {
        \add_action( 'wcbai_job_before_perform_task', array( $this, 'handle_job_before_perform_task' ), 10, 1 );
        \add_action( 'wcbai_mcp_function_before_execute', array( $this, 'handle_before_execute' ), 10, 2 );
    }

    /**
     * Handles the logging of the previous value of a product property before the exection of a tool.
     *
     * @param string $function_name The function name.
     * @param array  $arguments The arguments.
     * @return void
     */
    public function handle_before_execute( string $function_name, array $arguments ): void {
        $current_job_id = $this->get_current_job_processing();
        if ( ! $current_job_id ) {
            return;
        }

        $property       = Tool::from( $function_name )->get_property();
        $previous_value = Tool::from( $function_name )->get_previous_value( $arguments );

        $this->rollback_service->log_product_previous_value( $current_job_id, $property, $previous_value );
    }

    /**
     * Store current job ID in transient to use in other actions.
     *
     * @param Job $job The job.
     * @return void
     */
    public function handle_job_before_perform_task( Job $job ) {
        $this->set_current_job_processing( $job->get_id() );
    }

    /**
     * Set the current job processing.
     *
     * @param int $job_id The job ID.
     * @return void
     */
    public function set_current_job_processing( int $job_id ) {
        \set_transient( 'wcbai_current_job_processing', $job_id, 10 );
    }

    /**
     * Get the current job processing.
     *
     * @return int|null
     */
    public function get_current_job_processing() {
        return \get_transient( 'wcbai_current_job_processing' );
    }
}
