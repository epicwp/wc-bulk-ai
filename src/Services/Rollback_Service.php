<?php

namespace EPICWP\WC_Bulk_AI\Services;

use EPICWP\WC_Bulk_AI\Models\Product_Rollback;

/**
 * Rollback service.
 */
class Rollback_Service {
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
    public function apply_product_rollback( int $job_id, string $property ): void {
        try {
            // Try to get the last rollback by job ID and property.
            $rollback = Product_Rollback::get_by_job_id_and_property( $job_id, $property );
            if ( ! $rollback ) {
                return;
            }

            // Get the previous value of the rollback.
            $previous_value = \maybe_unserialize( $rollback->get_previous_value() );

            // Get the product id by the job ID.
            $product = \wc_get_product( Job::get_by_id( $job_id )->get_product_id() );

            // Set the previous value to the product property.
            $product->set_prop( $property, $previous_value );

            // Save the product.
            $product->save();

            // Mark the rollback as applied.
            $rollback->mark_as_applied();
        } catch ( \Exception ) {
            // \error_log( $e->getMessage() );
        }
    }
}
