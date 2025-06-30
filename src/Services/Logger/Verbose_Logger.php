<?php
namespace EPICWP\WC_Bulk_AI\Services\Logger;

use EPICWP\WC_Bulk_AI\Interfaces\Process_Logger;
use OpenAI\Responses\Chat\CreateResponse;

/**
 * Verbose logger for detailed AI conversation logging
 */
class Verbose_Logger implements Process_Logger {

    public function log_conversation_start(string $task, int $product_id): void {
        \WP_CLI::log(\WP_CLI::colorize("%g=== Starting AI Conversation ===%n"));
        \WP_CLI::log("Task: " . $task);
        \WP_CLI::log("Product ID: " . $product_id);
        \WP_CLI::log("");
    }
    
    public function log_iteration(int $iteration): void {
        \WP_CLI::log(\WP_CLI::colorize("%b--- Iteration {$iteration} ---%n"));
    }
    
    public function log_ai_response(CreateResponse $response): void {
        \WP_CLI::log(\WP_CLI::colorize("%c🤖 AI Response:%n"));
        
        $choice = $response->choices[0];
        
        if ($choice->message->content) {
            \WP_CLI::log("Message: " . $choice->message->content);
        }
        
        if (!empty($choice->message->toolCalls)) {
            \WP_CLI::log(\WP_CLI::colorize("%y🔧 AI wants to call " . count($choice->message->toolCalls) . " tool(s):%n"));
        }
        
        \WP_CLI::log("Finish reason: " . $choice->finishReason);
        
        // Show token usage
        if ($response->usage) {
            \WP_CLI::log(\WP_CLI::colorize("%m📊 Tokens used: {$response->usage->totalTokens} (prompt: {$response->usage->promptTokens}, completion: {$response->usage->completionTokens})%n"));
        }
        
        \WP_CLI::log("");
    }
    
    public function log_tool_call(string $function_name, string $arguments): void {
        \WP_CLI::log(\WP_CLI::colorize("%y🔧 Calling tool: {$function_name}%n"));
        \WP_CLI::log("Arguments: " . $arguments);
    }
    
    public function log_tool_result(mixed $result): void {
        \WP_CLI::log(\WP_CLI::colorize("%g✅ Tool result:%n"));
        
        if ($result instanceof \WC_Product) {
            \WP_CLI::log("Product: " . $result->get_name() . " (ID: " . $result->get_id() . ")");
        } elseif (is_array($result) && !empty($result) && $result[0] instanceof \WC_Product) {
            \WP_CLI::log("Found " . count($result) . " products");
        } else {
            \WP_CLI::log("Result: " . print_r($result, true));
        }
        \WP_CLI::log("");
    }
    
    public function log_tool_error(string $function_name, string $error): void {
        \WP_CLI::log(\WP_CLI::colorize("%r❌ Tool error in {$function_name}: {$error}%n"));
        \WP_CLI::log("");
    }
    
    public function log_task_complete(string $final_message): void {
        \WP_CLI::log(\WP_CLI::colorize("%g🎉 Task completed!%n"));
        \WP_CLI::log("Final message: " . $final_message);
        \WP_CLI::log("");
    }
    
    public function log_max_iterations_reached(): void {
        \WP_CLI::log(\WP_CLI::colorize("%r⚠️  Maximum iterations reached - task may not be complete%n"));
        \WP_CLI::log("");
    }
    
    public function log_job_start(int $job_id): void {
        \WP_CLI::log(\WP_CLI::colorize("%b🚀 Starting job {$job_id}%n"));
    }
    
    public function log_job_complete(int $job_id): void {
        \WP_CLI::log(\WP_CLI::colorize("%g✅ Job {$job_id} completed successfully%n"));
        \WP_CLI::log("");
    }
    
    public function log_job_failed(int $job_id, string $error): void {
        \WP_CLI::log(\WP_CLI::colorize("%r❌ Job {$job_id} failed: {$error}%n"));
        \WP_CLI::log("");
    }
} 