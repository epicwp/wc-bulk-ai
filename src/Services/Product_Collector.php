<?php
namespace EPICWP\WC_Bulk_AI\Services;

class Product_Collector {
    /**
     * Collect product IDs from the database.
     *
     * @param array<string, mixed> $args
     * @return array<int>
     */
    public function collect_ids( array $args ): array {
        $args['return'] = 'ids';
        return \wc_get_products( $args );
    }
}
