<?php
namespace EPICWP\WC_Bulk_AI\Interfaces;

use OpenAI\Responses\Chat\CreateResponse;

interface Process_Logger {
    
    public function log_conversation_start(string $task, int $product_id): void;
    
    public function log_iteration(int $iteration): void;
    
    public function log_ai_response(CreateResponse $response): void;
    
    public function log_tool_call(string $function_name, string $arguments): void;
    
    public function log_tool_result(mixed $result): void;
    
    public function log_tool_error(string $function_name, string $error): void;
    
    public function log_task_complete(string $final_message): void;
    
    public function log_max_iterations_reached(): void;
    
    public function log_job_start(int $job_id): void;
    
    public function log_job_complete(int $job_id): void;
    
    public function log_job_failed(int $job_id, string $error): void;
} 