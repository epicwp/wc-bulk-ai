<?php
namespace EPICWP\WC_Bulk_AI\Services;

use WC_Product;

/**
 * MCP class
 */
class MCP {

    /**
     * Available tools for OpenAI function calling.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $tools = [];

    /**
     * Function callbacks mapped by name.
     *
     * @var array<string, callable>
     */
    protected array $callbacks = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->register_tools();
    }

    /**
     * Get the tools for OpenAI function calling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_tools(): array {
        return $this->tools;
    }

    /**
     * Execute a function by name.
     *
     * @param string $function_name
     * @param array<string, mixed> $arguments
     * @return mixed
     */
    public function execute_function(string $function_name, array $arguments): mixed {
        if (!isset($this->callbacks[$function_name])) {
            throw new \Exception("Function {$function_name} not found");
        }

        return call_user_func($this->callbacks[$function_name], $arguments);
    }

    /**
     * Register the available tools
     *
     * @return void
     */
    protected function register_tools(): void {
        $this->tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_product',
                    'description' => 'Get a product details by ID',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => [
                                'type' => 'integer',
                                'description' => 'The product ID to retrieve',
                            ],
                        ],
                        'required' => ['product_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_products',
                    'description' => 'Get a list of products',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'description' => 'Product status: draft, pending, private, publish, or custom status',
                            ],
                            'type' => [
                                'type' => 'string',
                                'description' => 'Product type: external, grouped, simple, variable, or custom type',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Maximum number of results to retrieve (-1 for unlimited)',
                            ],
                            'sku' => [
                                'type' => 'string',
                                'description' => 'Product SKU to search for (supports partial matching)',
                            ],
                            'featured' => [
                                'type' => 'boolean',
                                'description' => 'Whether to retrieve featured products only',
                            ],
                            'on_sale' => [
                                'type' => 'boolean',
                                'description' => 'Whether to retrieve products on sale only',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_product_title',
                    'description' => 'Update a product title',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => [
                                'type' => 'integer',
                                'description' => 'The product ID to update',
                            ],
                            'title' => [
                                'type' => 'string',
                                'description' => 'The new title for the product',
                            ],
                        ],
                        'required' => ['product_id', 'title'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_product_description',
                    'description' => 'Update a product description',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => [
                                'type' => 'integer',
                                'description' => 'The product ID to update',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'The new description for the product',
                            ],
                        ],
                        'required' => ['product_id', 'description'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_product_short_description',
                    'description' => 'Update a product short description',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => [
                                'type' => 'integer',
                                'description' => 'The product ID to update',
                            ],
                            'short_description' => [
                                'type' => 'string',
                                'description' => 'The new short description for the product',
                            ],
                        ],
                        'required' => ['product_id', 'short_description'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_product_tags',
                    'description' => 'Get a list of already existing product tags as titles',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => [
                                'type' => 'integer',
                                'description' => 'The product ID to retrieve tags for',
                            ],
                        ],
                        'required' => ['product_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_product_tags',
                    'description' => 'Update a product\'s tags via a list of titles',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => [
                                'type' => 'integer',
                                'description' => 'The product ID to update tags for',
                            ],
                            'tags' => [
                                'type' => 'array',
                                'description' => 'The tags as titles to update',
                                'items' => [
                                    'type' => 'string'
                                ]
                            ],
                            'append' => [
                                'type' => 'boolean',
                                'description' => 'Whether to append the tags to the existing tags or replace them',
                            ],
                        ],
                        'required' => ['product_id', 'tags'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_available_product_tags',
                    'description' => 'Get a list of all available product tags including the title, slug, and ID',
                    'returns' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'slug' => ['type' => 'string'],
                                'id' => ['type' => 'integer'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Register callbacks
        $this->callbacks = [
            'get_product' => [$this, 'get_product'],
            'get_products' => [$this, 'get_products'],
            'update_product_title' => [$this, 'update_product_title'],
            'update_product_description' => [$this, 'update_product_description'],
            'update_product_short_description' => [$this, 'update_product_short_description'],
            'get_product_tags' => [$this, 'get_product_tags'],
            'update_product_tags' => [$this, 'update_product_tags'],
            'get_available_product_tags' => [$this, 'get_available_product_tags'],
        ];
    }

    /**
     * Get a product by ID
     *
     * @param array<string, mixed> $args
     * @return \WC_Product
     */
    public function get_product(array $args): \WC_Product {
        return wc_get_product($args['product_id']);
    }

    /**
     * Get a list of products
     *
     * @param array<string, mixed> $args
     * @return \WC_Product[]
     */
    public function get_products(array $args): array {
        return wc_get_products($args);
    }

    /**
     * Update a product title
     *
     * @param array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_title(array $args): \WC_Product {
        $product = wc_get_product($args['product_id']);
        $product->set_name($args['title']);
        $product->save();
        return $product;
    }

    /**
     * Update a product description
     *
     * @param array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_description(array $args): \WC_Product {
        $product = wc_get_product($args['product_id']);
        $product->set_description($args['description']);
        $product->save();
        return $product;
    }

    /**
     * Update a product short description
     *
     * @param array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_short_description(array $args): \WC_Product {
        $product = wc_get_product($args['product_id']);
        $product->set_short_description($args['short_description']);
        $product->save();
        return $product;
    }

    /**
     * Get a list of product tags as titles
     *
     * @param array<string, mixed> $args
     * @return array<string>
     */
    public function get_product_tags(array $args): array {
        $product = wc_get_product($args['product_id']);
        $tags = $product->get_tag_ids();
        $tag_titles = [];
        foreach ($tags as $tag_id) {
            $tag_titles[] = get_term_field('name', $tag_id, 'product_tag');
        }
        return $tag_titles;
    }

    /**
     * Update a product tags
     *
     * @param array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_tags(array $args): \WC_Product {
        wp_set_object_terms($args['product_id'], $args['tags'], 'product_tag', $args['append'] ?? false);
        return wc_get_product($args['product_id']);
    }

    /**
     * Get a list of available product tags
     *
     * @return array<string>
     */
    public function get_available_product_tags(): array {
        $tags = get_terms('product_tag', array('hide_empty' => false));
        $available_tags = [];
        foreach ($tags as $tag) {
            $available_tags[] = [
                'title' => $tag->name,
                'slug' => $tag->slug,
                'id' => $tag->term_id,
            ];
        }
        return $available_tags;
    }
}