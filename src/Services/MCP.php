<?php
namespace EPICWP\WC_Bulk_AI\Services;

use WC_Product;

/**
 * MCP class for OpenAI tool calling.
 */
class MCP {
    /**
     * Available tools for OpenAI function calling.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $tools = array();

    /**
     * Function callbacks mapped by name.
     *
     * @var array<string, callable>
     */
    protected array $callbacks = array();

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
     * @param  string               $function_name
     * @param  array<string, mixed> $arguments
     * @return mixed
     */
    public function execute_function( string $function_name, array $arguments ): mixed {
        if ( ! isset( $this->callbacks[ $function_name ] ) ) {
            throw new \Exception( "Function {$function_name} not found" );
        }

        // Trigger action before function execution
        \do_action( 'wcbai_mcp_function_before_execute', $function_name, $arguments );

        // Call the actual function
        $result = \call_user_func( $this->callbacks[ $function_name ], $arguments );

        // Trigger action after function execution
        \do_action( 'wcbai_mcp_function_executed', $function_name, $arguments, $result );
        
        return $result;
    }

    /**
     * Get a product by ID
     *
     * @param  array<string, mixed> $args
     * @return \WC_Product
     * @throws \Exception
     */
    public function get_product( array $args ): \WC_Product {
        $product = \wc_get_product( $args['product_id'] );

        if ( false === $product ) {
            throw new \Exception( "Product with ID {$args['product_id']} not found or does not exist." );
        }

        return $product;
    }

    /**
     * Get a list of products
     *
     * @param  array<string, mixed> $args
     * @return \WC_Product[]
     */
    public function get_products( array $args ): array {
        return \wc_get_products( $args );
    }

    /**
     * Update a product title
     *
     * @param  array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_title( array $args ): \WC_Product {
        $product = \wc_get_product( $args['product_id'] );
        $product->set_name( $args['title'] );
        $product->save();
        return $product;
    }

    /**
     * Update a product description
     *
     * @param  array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_description( array $args ): \WC_Product {
        $product = \wc_get_product( $args['product_id'] );
        $product->set_description( $args['description'] );
        $product->save();
        return $product;
    }

    /**
     * Update a product short description
     *
     * @param  array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_short_description( array $args ): \WC_Product {
        $product = \wc_get_product( $args['product_id'] );
        $product->set_short_description( $args['short_description'] );
        $product->save();
        return $product;
    }

    /**
     * Get a list of product tags as titles
     *
     * @param  array<string, mixed> $args
     * @return array<string>
     */
    public function get_product_tags( array $args ): array {
        $product    = \wc_get_product( $args['product_id'] );
        $tags       = $product->get_tag_ids();
        $tag_titles = array();
        foreach ( $tags as $tag_id ) {
            $tag_titles[] = \get_term_field( 'name', $tag_id, 'product_tag' );
        }
        return $tag_titles;
    }

    /**
     * Update a product tags
     *
     * @param  array<string, mixed> $args
     * @return \WC_Product
     */
    public function update_product_tags( array $args ): \WC_Product {
        \wp_set_object_terms( $args['product_id'], $args['tags'], 'product_tag', $args['append'] ?? false );
        return \wc_get_product( $args['product_id'] );
    }

    /**
     * Get a list of available product tags
     *
     * @return array<string>
     */
    public function get_available_product_tags(): array {
        $tags           = \get_terms( 'product_tag', array( 'hide_empty' => false ) );
        $available_tags = array();
        foreach ( $tags as $tag ) {
            $available_tags[] = array(
                'id'    => $tag->term_id,
                'slug'  => $tag->slug,
                'title' => $tag->name,
            );
        }
        return $available_tags;
    }

    /**
     * Register the available tools
     *
     * @return void
     */
    protected function register_tools(): void {
        $this->tools = array(
            array(
                'function' => array(
                    'description' => 'Get a product details by ID',
                    'name'        => 'get_product',
                    'parameters'  => array(
                        'properties' => array(
                            'product_id' => array(
                                'description' => 'The product ID to retrieve',
                                'type'        => 'integer',
                            ),
                        ),
                        'required'   => array( 'product_id' ),
                        'type'       => 'object',
                    ),
                ),
                'type'     => 'function',
            ),
            array(
                'function' => array(
                    'description' => 'Get a list of products',
                    'name'        => 'get_products',
                    'parameters'  => array(
                        'properties' => array(
                            'featured' => array(
                                'description' => 'Whether to retrieve featured products only',
                                'type'        => 'boolean',
                            ),
                            'limit'    => array(
                                'description' => 'Maximum number of results to retrieve (-1 for unlimited)',
                                'type'        => 'integer',
                            ),
                            'on_sale'  => array(
                                'description' => 'Whether to retrieve products on sale only',
                                'type'        => 'boolean',
                            ),
                            'sku'      => array(
                                'description' => 'Product SKU to search for (supports partial matching)',
                                'type'        => 'string',
                            ),
                            'status'   => array(
                                'description' => 'Product status: draft, pending, private, publish, or custom status',
                                'type'        => 'string',
                            ),
                            'type'     => array(
                                'description' => 'Product type: external, grouped, simple, variable, or custom type',
                                'type'        => 'string',
                            ),
                        ),
                        'required'   => array(),
                        'type'       => 'object',
                    ),
                ),
                'type'     => 'function',
            ),
            array(
                'function' => array(
                    'description' => 'Update a product title',
                    'name'        => 'update_product_title',
                    'parameters'  => array(
                        'properties' => array(
                            'product_id' => array(
                                'description' => 'The product ID to update',
                                'type'        => 'integer',
                            ),
                            'title'      => array(
                                'description' => 'The new title for the product',
                                'type'        => 'string',
                            ),
                        ),
                        'required'   => array( 'product_id', 'title' ),
                        'type'       => 'object',
                    ),
                ),
                'type'     => 'function',
            ),
            array(
                'function' => array(
                    'description' => 'Update a product description',
                    'name'        => 'update_product_description',
                    'parameters'  => array(
                        'properties' => array(
                            'description' => array(
                                'description' => 'The new description for the product',
                                'type'        => 'string',
                            ),
                            'product_id'  => array(
                                'description' => 'The product ID to update',
                                'type'        => 'integer',
                            ),
                        ),
                        'required'   => array( 'product_id', 'description' ),
                        'type'       => 'object',
                    ),
                ),
                'type'     => 'function',
            ),
            array(
                'function' => array(
                    'description' => 'Update a product short description',
                    'name'        => 'update_product_short_description',
                    'parameters'  => array(
                        'properties' => array(
                            'product_id'        => array(
                                'description' => 'The product ID to update',
                                'type'        => 'integer',
                            ),
                            'short_description' => array(
                                'description' => 'The new short description for the product',
                                'type'        => 'string',
                            ),
                        ),
                        'required'   => array( 'product_id', 'short_description' ),
                        'type'       => 'object',
                    ),
                ),
                'type'     => 'function',
            ),
            array(
                'function' => array(
                    'description' => 'Get a list of already existing product tags as titles',
                    'name'        => 'get_product_tags',
                    'parameters'  => array(
                        'properties' => array(
                            'product_id' => array(
                                'description' => 'The product ID to retrieve tags for',
                                'type'        => 'integer',
                            ),
                        ),
                        'required'   => array( 'product_id' ),
                        'type'       => 'object',
                    ),
                ),
                'type'     => 'function',
            ),
            array(
                'function' => array(
                    'description' => 'Update a product\'s tags via a list of titles',
                    'name'        => 'update_product_tags',
                    'parameters'  => array(
                        'properties' => array(
                            'append'     => array(
                                'description' => 'Whether to append the tags to the existing tags or replace them',
                                'type'        => 'boolean',
                            ),
                            'product_id' => array(
                                'description' => 'The product ID to update tags for',
                                'type'        => 'integer',
                            ),
                            'tags'       => array(
                                'description' => 'The tags as titles to update',
                                'items'       => array(
                                    'type' => 'string',
                                ),
                                'type'        => 'array',
                            ),
                        ),
                        'required'   => array( 'product_id', 'tags' ),
                        'type'       => 'object',
                    ),
                ),
                'type'     => 'function',
            ),
            array(
                'function' => array(
                    'description' => 'Get a list of all available product tags including the title, slug, and ID',
                    'name'        => 'get_available_product_tags',
                    'returns'     => array(
                        'items' => array(
                            'properties' => array(
                                'id'    => array( 'type' => 'integer' ),
                                'slug'  => array( 'type' => 'string' ),
                                'title' => array( 'type' => 'string' ),
                            ),
                            'type'       => 'object',
                        ),
                        'type'  => 'array',
                    ),
                ),
                'type'     => 'function',
            ),
        );

        // Register callbacks
        $this->callbacks = array(
            'get_available_product_tags'       => array( $this, 'get_available_product_tags' ),
            'get_product'                      => array( $this, 'get_product' ),
            'get_products'                     => array( $this, 'get_products' ),
            'get_product_tags'                 => array( $this, 'get_product_tags' ),
            'update_product_description'       => array( $this, 'update_product_description' ),
            'update_product_short_description' => array( $this, 'update_product_short_description' ),
            'update_product_tags'              => array( $this, 'update_product_tags' ),
            'update_product_title'             => array( $this, 'update_product_title' ),
        );
    }
}
