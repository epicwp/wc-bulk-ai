<?php
namespace EPICWP\WC_Bulk_AI\Services;

use EPICWP\WC_Bulk_AI\Models\Job;

class Job_Processor {

    public function __construct(protected Agent $agent) {
    }

    /**
     * Process a job.
     *
     * @param Job $job
     * @return void
     */
    public function process_job(Job $job): void {
        $this->agent->perform_task($job->get_task(), $job->get_product_id());
    }

}