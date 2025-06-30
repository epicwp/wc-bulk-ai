<?php
namespace EPICWP\WC_Bulk_AI\Services;

use EPICWP\WC_Bulk_AI\Models\Job;

/**
 * Processes individual jobs by coordinating with the AI Agent
 */
class Job_Processor {

    /**
     * Constructor
     *
     * @param Agent $agent The AI agent instance
     */
    public function __construct( protected Agent $agent ) {
    }

    /**
     * Process a single job
     *
     * @param Job $job The job to process
     * @return bool True if job completed successfully, false otherwise
     */
    public function process_job( Job $job ): bool {
        try {
            // Start the job
            $job->start();
            
            // Perform the task using the AI agent
            $success = $this->agent->perform_task( 
                $job->get_task(), 
                $job->get_product_id()
            );
            
            if ( $success ) {
                $job->complete();
                return true;
            } else {
                $job->fail();
                return false;
            }
            
        } catch ( \Exception $e ) {
            $job->fail( $e->getMessage() );
            return false;
        }
    }
}