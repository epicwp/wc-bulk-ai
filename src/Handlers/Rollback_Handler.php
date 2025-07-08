<?php

namespace EPICWP\WC_Bulk_AI\Handlers;

use EPICWP\WC_Bulk_AI\Enums\ProductProperty;
use EPICWP\WC_Bulk_AI\Models\Job;
use EPICWP\WC_Bulk_AI\Services\MCP;
use EPICWP\WC_Bulk_AI\Services\Rollback_Service;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Handler;

/**
 * Rollback handler.
 */
#[Handler( tag: 'init', priority: 10 )]
class Rollback_Handler {
    const TRANSIENT_EXPIRATION = 60;
    const TRANSIENT_PREFIX     = 'wcbai_current_job_processing_';

    /**
     * Constructor.
     *
     * @param Rollback_Service $rollback_service The rollback service.
     */
    public function __construct( protected Rollback_Service $rollback_service, protected MCP $mcp ) {
    }

    /**
     * Handles the logging of the previous value of a product property before the exection of a tool.
     *
     * @param string $function_name The function name.
     * @param array  $arguments The arguments.
     * @return void
     */
    #[Action(
        tag: 'wcbai_mcp_function_before_execute',
        priority: 10,
        params: array(
            'arguments'     => 'arguments',
            'function_name' => 'function_name',
        ),
    )]
    public function handle_before_execute( string $function_name, array $arguments ): void {
        $current_job_id = $this->get_current_job_processing( $arguments['job_id'] );
        if ( ! $current_job_id ) {
            return;
        }

        $property          = ProductProperty::getByMethodName( $function_name );
        $fetch_method_name = $property->getFetchMethodName();
        $product_id        = Job::get_by_id( $current_job_id )->get_product_id();
        $previous_value    = $this->mcp->execute_function(
            $fetch_method_name,
            array( 'product_id' => $product_id ),
        );

        $this->rollback_service->log_previous_value( $current_job_id, $property->value, $previous_value );
    }

    /**
     * Handles the rollback of a product property after the execution of a tool.
     *
     * @param string $function_name The function name.
     * @param array  $arguments The arguments.
     * @param mixed  $result The result.
     * @return void
     */
    #[Action( tag: 'wcbai_job_finished', priority: 10, params: array( 'job' => 'job' ) )]
    public function handle_job_finished( Job $job ): void {
        $current_job_id = $this->get_current_job_processing( $job->get_id() );
        if ( ! $current_job_id ) {
            return;
        }
        $this->unset_current_job_processing( $current_job_id );
    }

    /**
     * Store current job ID in transient to use in other actions.
     *
     * @param Job $job The job.
     * @return void
     */
    #[Action( tag: 'wcbai_job_before_perform_task', priority: 10, params: array( 'job' => 'job' ) )]
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
        \set_transient( self::TRANSIENT_PREFIX . $job_id, 1, self::TRANSIENT_EXPIRATION );
    }

    /**
     * Get the current job processing.
     *
     * @return int|null
     */
    public function get_current_job_processing( int $job_id ): bool {
        return \get_transient( self::TRANSIENT_PREFIX . $job_id );
    }

    /**
     * Unset the current job processing.
     *
     * @param int $job_id The job ID.
     * @return void
     */
    public function unset_current_job_processing( int $job_id ) {
        \delete_transient( self::TRANSIENT_PREFIX . $job_id );
    }
}
