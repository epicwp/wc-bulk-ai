<?php
namespace EPICWP\WC_Bulk_AI\Services;

use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;
use EPICWP\WC_Bulk_AI\Interfaces\Process_Logger;

use WC_Product;

/**
 * AI Agent for handling WooCommerce product tasks with conversation management
 */
class Agent {

    /**
     * The OpenAI client
     *
     * @var Client
     */
    protected Client $client;

    /**
     * The system prompt
     *
     * @var string
     */
    protected string $system_prompt;

    /**
     * The conversation messages
     *
     * @var array<array<string, mixed>>
     */
    protected array $messages = [];

    /**
     * Maximum iterations to prevent infinite loops
     *
     * @var int
     */
    protected int $max_iterations = 10;

    /**
     * Constructor
     *
     * @param MCP $mcp MCP service for tool execution
     * @param Process_Logger $logger Logger instance for output
     */
    public function __construct( protected MCP $mcp, protected Process_Logger $logger ) {
        $this->client = xwp_app('wc-bulk-ai')->get( 'app.client' );
        $this->system_prompt = \apply_filters( 
            'wcbai_system_prompt', 
            'You are a WooCommerce product content editor. You have access to tools to get and update product information. Complete the given task using the available tools. When you have completed the task, provide a summary of what was done.' 
        );
    }

    /**
     * Perform a task on a specific product with conversation management
     *
     * @param string $task The task description
     * @param int $product_id The product ID to work on
     * @return bool True if task completed successfully, false otherwise
     */
    public function perform_task( string $task, int $product_id ): bool {

        // Initialize the conversation with the system prompt and product id
        $this->initialize_conversation( $task, $product_id );
        
        $iteration = 0;
        
        while ( $iteration < $this->max_iterations ) {

            // Log each iteration
            $this->logger->log_iteration( $iteration + 1 );
            
            // Make a new request to the AI with the current messages
            $response = $this->complete( $this->messages );
            
            // Log the AI's response
            $this->logger->log_ai_response( $response );
            
            // Add assistant message to conversation
            $assistant_message = array(
                'role'    => 'assistant',
                'content' => $response->choices[0]->message->content,
            );
            
            // Check if AI wants to call tools
            $tool_calls = $response->choices[0]->message->toolCalls ?? null;
            if ( null !== $tool_calls && count( $tool_calls ) > 0 ) {
                // Add tool calls to assistant message
                $assistant_message['tool_calls'] = $tool_calls;
                $this->messages[] = $assistant_message;

                // Logs and executes the tool calls
                $this->handle_tool_calls( $tool_calls );
            } else {
                // Add assistant message to conversation when no tool calls
                $this->messages[] = $assistant_message;
            }

            // Check if task is complete based on finish reason
            if ( $response->choices[0]->finishReason === 'stop' ) {
                // Log the last ai response before completing the task
                $this->logger->log_task_complete( $response->choices[0]->message->content );
                return true;
            }
            
            $iteration++;
        }
        
        $this->logger->log_max_iterations_reached();
        return false;
    }

    /**
     * Complete a chat request with the current messages
     *
     * @param array<array<string, mixed>> $messages The conversation messages
     * @return CreateResponse The AI response
     */
    public function complete( array $messages ): CreateResponse {
        $response = $this->client->chat()->create(
            array(
                'model'    => \apply_filters( 'wcbai_model', 'gpt-4.1' ),
                'messages' => $messages,
                'tools'    => $this->mcp->get_tools(),
            )
        );
        return $response;
    }

    /**
     * Initialize the conversation with system prompt, task and product id
     *
     * @param string   $task The task description
     * @param int      $product_id The product ID
     * @return void
     */
    protected function initialize_conversation( string $task, int $product_id ): void {
        $this->messages = array(
            array(
                'role'    => 'system',
                'content' => $this->system_prompt,
            ),
            array(
                'role'    => 'user',
                'content' => 'Task: ' . $task,
            ),
            array(
                'role'    => 'user', 
                'content' => 'Product ID: ' . $product_id,
            ),
        );
        
        $this->logger->log_conversation_start( $task, $product_id );
    }

    /**
     * Handle tool calls from AI response
     *
     * @param array<int, object> $tool_calls Array of tool call objects
     * @return void
     */
    protected function handle_tool_calls( array $tool_calls ): void {

        // Loop through each tool call
        foreach ( $tool_calls as $tool_call ) {

            // Log the tool call
            $this->logger->log_tool_call( $tool_call->function->name, $tool_call->function->arguments );
            
            try {
                // Parse arguments
                $arguments = json_decode( $tool_call->function->arguments, true );
                
                // Execute the actual function via our MCP service
                $result = $this->mcp->execute_function( $tool_call->function->name, $arguments );
                
                // Convert result to JSON string for AI
                $result_content = $this->format_result_for_ai( $result );
                
                // Add tool result to conversation
                $this->messages[] = array(
                    'role'         => 'tool',
                    'tool_call_id' => $tool_call->id,
                    'content'      => $result_content,
                );
                
                // Log the tool result
                $this->logger->log_tool_result( $result );
                
            } catch ( \Exception $e ) {

                // Log the tool error
                $this->logger->log_tool_error( $tool_call->function->name, $e->getMessage() );
                
                // Add error result to conversation
                $this->messages[] = array(
                    'role'         => 'tool',
                    'tool_call_id' => $tool_call->id,
                    'content'      => 'Error: ' . $e->getMessage(),
                );
            }
        }
    }

    /**
     * Format function result for AI consumption
     *
     * @param mixed $result The function execution result
     * @return string JSON encoded result
     */
    protected function format_result_for_ai( mixed $result ): string {

        // If the result is a product object, get the product data
        if ( $result instanceof \WC_Product ) {
            $product_data = $this->get_product_data( $result );
            return \wp_json_encode( $product_data );
        }
        
        // If the result is an array of product objects, get the product data for each product
        if ( is_array( $result ) && count( $result ) > 0 && $result[0] instanceof \WC_Product ) {
            $products = array_map( array( $this, 'get_product_data' ), $result );
            return \wp_json_encode( $products );
        }
        
        // Otherwise, return the result as a JSON string
        return \wp_json_encode( $result );
    }

    /**
     * Get product data
     *
     * @param \WC_Product $product The product
     * @return array The product data
     */
    protected function get_product_data( \WC_Product $product ): array {
        return array(
            'id'                => $product->get_id(),
            'name'              => $product->get_name(),
            'description'       => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price'             => $product->get_price(),
            'sale_price'        => $product->get_sale_price(),
            'regular_price'     => $product->get_regular_price(),
            'stock_quantity'    => $product->get_stock_quantity(),
            'stock_status'      => $product->get_stock_status(),
            'sku'               => $product->get_sku(),
            'status'            => $product->get_status(),
        );
    }
}