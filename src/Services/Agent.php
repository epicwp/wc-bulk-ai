<?php
namespace EPICWP\WC_Bulk_AI\Services;

use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;

class Agent {

    public function __construct(protected Client $client, protected MCP $mcp) {
    }

    /**
     * Perform the task.
     *
     * @param string $task
     * @param int $product_id
     * @return CreateResponse
     */
    public function perform_task(string $task, int $product_id): CreateResponse {
        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are a WooCommerce product content editor. You are given a task to perform on a product. You are also given the product id.',
            ),
            array(
                'role' => 'user',
                'content' => 'Task: ' . $task,
            ),
            array(
                'role' => 'user',
                'content' => 'Product ID: ' . $product_id,
            ),
        );
        $response = $this->complete($messages);
        return $response;
    }

    /**
     * Complete the task.
     *
     * @param array<array<string, mixed>> $messages
     * @return CreateResponse
     */
    public function complete(array $messages): CreateResponse {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4.1',
            'messages' => $messages,
            'tools' => $this->mcp->get_tools(),
        ]);
        return $response;
    }
}