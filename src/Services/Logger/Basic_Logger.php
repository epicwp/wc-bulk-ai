<?php
namespace EPICWP\WC_Bulk_AI\Services\Logger;

use EPICWP\WC_Bulk_AI\Interfaces\Process_Logger;
use OpenAI\Responses\Chat\CreateResponse;

/**
 * Basic logger for minimal progress logging
 */
class Basic_Logger implements Process_Logger {

    public function log_conversation_start(string $task, int $product_id): void {
        // Silent in basic mode
    }
    
    public function log_iteration(int $iteration): void {
        // Silent in basic mode
    }
    
    public function log_ai_response(CreateResponse $response): void {
        // Silent in basic mode
    }
    
    public function log_tool_call(string $function_name, string $arguments): void {
        // Silent in basic mode
    }
    
    public function log_tool_result(mixed $result): void {
        // Silent in basic mode
    }
    
    public function log_tool_error(string $function_name, string $error): void {
        \WP_CLI::warning("Tool error: {$error}");
    }
    
    public function log_task_complete(string $final_message): void {
        // Silent in basic mode
    }
    
    public function log_max_iterations_reached(): void {
        \WP_CLI::warning("Task reached maximum iterations");
    }
    
    public function log_job_start(int $job_id): void {
        \WP_CLI::log("Processing job {$job_id}...");
    }
    
    public function log_job_complete(int $job_id): void {
        \WP_CLI::success("Job {$job_id} completed");
    }
    
    public function log_job_failed(int $job_id, string $error): void {
        \WP_CLI::error("Job {$job_id} failed: {$error}");
    }
} 