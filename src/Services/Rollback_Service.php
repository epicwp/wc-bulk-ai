<?php

namespace EPICWP\WC_Bulk_AI\Services;

use EPICWP\WC_Bulk_AI\Models\Product_Rollback;

class Rollback_Service {
    public function log_product_previous_value( int $job_id, string $property, mixed $previous_value ): void {
        Product_Rollback::create( $job_id, $property, \maybe_serialize( $previous_value ) );
    }

    public function apply_product_rollback( int $job_id, string $property ): void {
        $rollback = Product_Rollback::get_by_job_id_and_property( $job_id, $property );
        if ( ! $rollback ) {
            return;
        }
        $rollback->mark_as_applied();
    }
}
