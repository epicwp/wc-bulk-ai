<?php
namespace EPICWP\WC_Bulk_AI\Services;

use WC_Product;

/**
 * MCP class
 */
class MCP {

    /**
     * Available functions to call from the AI.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $available_functions = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->register_functions();
    }

    /**
     * Get the all available functions for function calling.
     *
     * @return array<string, array<string, mixed>>
     */
    public function get_available_functions(): array {
        return $this->available_functions;
    }

    /**
     * Register the available functions
     *
     * @return void
     */
    protected function register_functions(): void {
        $this->available_functions = [
            'get_product' => [
                'description' => 'Get a product details by ID',
                'parameters' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'The product ID to retrieve',
                        'required' => true
                    ]
                ],
                'callback' => [$this, 'get_product'],
            ],
            'get_products' => [
                'description' => 'Get a list of products',
                'parameters' => [
                    'status' => [
                        'type' => 'string|array',
                        'description' => 'Product status: draft, pending, private, publish, or custom status',
                        'required' => false
                    ],
                    'type' => [
                        'type' => 'string|array',
                        'description' => 'Product type: external, grouped, simple, variable, or custom type',
                        'required' => false
                    ],
                    'include' => [
                        'type' => 'array',
                        'description' => 'Array of product IDs to include',
                        'required' => false
                    ],
                    'exclude' => [
                        'type' => 'array',
                        'description' => 'Array of product IDs to exclude',
                        'required' => false
                    ],
                    'parent' => [
                        'type' => 'integer',
                        'description' => 'Post ID of the product parent',
                        'required' => false
                    ],
                    'parent_exclude' => [
                        'type' => 'array',
                        'description' => 'Array of parent IDs to exclude',
                        'required' => false
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of results to retrieve (-1 for unlimited)',
                        'required' => false
                    ],
                    'page' => [
                        'type' => 'integer',
                        'description' => 'Page of results to retrieve',
                        'required' => false
                    ],
                    'paginate' => [
                        'type' => 'boolean',
                        'description' => 'True for pagination, false for not',
                        'required' => false
                    ],
                    'orderby' => [
                        'type' => 'string',
                        'description' => 'Field to order by (date, id, include, title, slug, etc.)',
                        'required' => false
                    ],
                    'order' => [
                        'type' => 'string',
                        'description' => 'Order direction: ASC or DESC',
                        'required' => false
                    ],
                    'return' => [
                        'type' => 'string',
                        'description' => 'Format of return: ids, objects (default)',
                        'required' => false
                    ],
                    'sku' => [
                        'type' => 'string',
                        'description' => 'Product SKU to search for (supports partial matching)',
                        'required' => false
                    ],
                    'tag' => [
                        'type' => 'string|array',
                        'description' => 'Product tag slug(s)',
                        'required' => false
                    ],
                    'category' => [
                        'type' => 'string|array',
                        'description' => 'Product category slug(s)',
                        'required' => false
                    ],
                    'featured' => [
                        'type' => 'boolean',
                        'description' => 'Whether to retrieve featured products only',
                        'required' => false
                    ],
                    'on_sale' => [
                        'type' => 'boolean',
                        'description' => 'Whether to retrieve products on sale only',
                        'required' => false
                    ],
                    'downloadable' => [
                        'type' => 'boolean',
                        'description' => 'Whether to retrieve downloadable products only',
                        'required' => false
                    ],
                    'virtual' => [
                        'type' => 'boolean',
                        'description' => 'Whether to retrieve virtual products only',
                        'required' => false
                    ],
                    'date_created' => [
                        'type' => 'string',
                        'description' => 'Date range for product creation (format: YYYY-MM-DD...YYYY-MM-DD)',
                        'required' => false
                    ],
                    'date_modified' => [
                        'type' => 'string',
                        'description' => 'Date range for product modification (format: YYYY-MM-DD...YYYY-MM-DD)',
                        'required' => false
                    ]
                ],
                'callback' => [$this, 'get_products'],
            ],
            'update_product_title' => [
                'description' => 'Update a product title',
                'parameters' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'The product ID to update',
                        'required' => true
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'The new title for the product',
                        'required' => true
                    ]
                ],
                'callback' => [$this, 'update_product_title'],
            ],
            'update_product_description' => [
                'description' => 'Update a product description',
                'parameters' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'The product ID to update',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'The new description for the product',
                        'required' => true
                    ]
                ],
                'callback' => [$this, 'update_product_description'],
            ],
            'update_product_short_description' => [
                'description' => 'Update a product short description',
                'parameters' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'The product ID to update',
                        'required' => true
                    ],
                    'short_description' => [
                        'type' => 'string',
                        'description' => 'The new short description for the product',
                        'required' => true
                    ]
                ],
                'callback' => [$this, 'update_product_short_description'],
            ],
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
}