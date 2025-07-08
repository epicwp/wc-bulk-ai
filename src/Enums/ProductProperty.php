<?php
namespace EPICWP\WC_Bulk_AI\Enums;

enum ProductProperty: string {
    case TITLE             = 'title';
    case DESCRIPTION       = 'description';
    case SHORT_DESCRIPTION = 'short_description';
    case TAGS              = 'tags';

    public static function getByMethodName( string $method_name ): ?self {
        return match ( $method_name ) {
            'update_product_title' => self::TITLE,
            'update_product_description' => self::DESCRIPTION,
            'update_product_short_description' => self::SHORT_DESCRIPTION,
            'update_product_tags' => self::TAGS,
            default => null
        };
    }

    public function getUpdateMethodName(): string {
        return match ( $this ) {
            self::TITLE => 'update_product_title',
            self::DESCRIPTION => 'update_product_description',
            self::SHORT_DESCRIPTION => 'update_product_short_description',
            self::TAGS => 'update_product_tags',
        };
    }

    public function getFetchMethodName(): string {
        return match ( $this ) {
            self::TITLE => 'get_product_title',
            self::DESCRIPTION => 'get_product_description',
            self::SHORT_DESCRIPTION => 'get_product_short_description',
            self::TAGS => 'get_product_tags',
        };
    }
}
