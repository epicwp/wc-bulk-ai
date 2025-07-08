<?php
namespace EPICWP\WC_Bulk_AI\Enums;

enum Tool {
    case UPDATE_PRODUCT_TITLE;
    case UPDATE_PRODUCT_DESCRIPTION;
    case UPDATE_PRODUCT_SHORT_DESCRIPTION;
    case UPDATE_PRODUCT_TAGS;

    public function get_name(): string {
        return match ( $this ) {
            self::UPDATE_PRODUCT_TITLE => 'update_product_title',
            self::UPDATE_PRODUCT_DESCRIPTION => 'update_product_description',
            self::UPDATE_PRODUCT_SHORT_DESCRIPTION => 'update_product_short_description',
            self::UPDATE_PRODUCT_TAGS => 'update_product_tags',
        };
    }

    public function get_property(): string {
        return match ( $this ) {
            self::UPDATE_PRODUCT_TITLE => 'title',
            self::UPDATE_PRODUCT_DESCRIPTION => 'description',
            self::UPDATE_PRODUCT_SHORT_DESCRIPTION => 'short_description',
            self::UPDATE_PRODUCT_TAGS => 'tags',
        };
    }

    public function get_previous_value( array $arguments ): mixed {
        return match ( $this ) {
            self::UPDATE_PRODUCT_TITLE => \get_product_title( $arguments['product_id'] ),
            self::UPDATE_PRODUCT_DESCRIPTION => \get_product_description( $arguments['product_id'] ),
            self::UPDATE_PRODUCT_SHORT_DESCRIPTION => \get_product_short_description(
                $arguments['product_id'],
            ),
            self::UPDATE_PRODUCT_TAGS => \get_product_tags( $arguments['product_id'] ),
        };
    }
}
