<?php

namespace EPICWP\WC_Bulk_AI\Services;

use EPICWP\WC_Bulk_AI\Enums\ProductProperty;
use EPICWP\WC_Bulk_AI\Models\Job;
use EPICWP\WC_Bulk_AI\Models\Product_Rollback;
use EPICWP\WC_Bulk_AI\Services\MCP;

/**
 * Rollback service.
 */
class Rollback_Service {
    public function __construct( protected MCP $mcp ) {
    }

    /**
     * Logs the previous value of the product property.
     *
     * @param int    $job_id The job ID.
     * @param string $property The property to rollback.
     * @param mixed  $previous_value The previous value of the property.
     * @return void
     */
    public function log_previous_value( int $job_id, string $property, mixed $previous_value ): void {
        Product_Rollback::create( $job_id, $property, \maybe_serialize( $previous_value ) );
    }

    /**
     * Updates the product property to the previous value of the rollback.
     *
     * @param int    $job_id The job ID.
     * @param string $property The property to rollback.
     * @return void
     */
    public function apply_product_rollback( int $job_id ): void {
        try {

            $rollbacks = Product_Rollback::get_by_job_id( $job_id );
            if ( ! $rollbacks ) {
                return;
            }

            // Get the product id by the job ID.
            $product = \wc_get_product( Job::get_by_id( $job_id )->get_product_id() );

            if ( ! $product ) {
                return;
            }

            foreach ( $rollbacks as $rollback ) {

                // Get the previous value of the rollback.
                $previous_value = \maybe_unserialize( $rollback->get_previous_value() );

                // Get the property of the rollback.
                $property = ProductProperty::from( $rollback->get_property() );
                if ( ! $property->value ) {
                    continue;
                }

                // Get the update method name of the property.
                $method_name = $property->getUpdateMethodName();
                if ( ! $method_name ) {
                    continue;
                }

                // Execute the update method.
                $this->mcp->execute_function(
                    $method_name,
                    array(
                        'product_id' => $product->get_id(),
                        'value'      => $previous_value,
                    ),
                );

                // Mark the rollback as applied.
                $rollback->mark_as_applied();

            }
        } catch ( \Exception ) {
            \error_log( $e->getMessage() );
        }
    }
}
