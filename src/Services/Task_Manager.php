<?php
namespace EPICWP\WC_Bulk_AI\Services;

class Task_Manager {
    protected array $tasks = array();

    public function __construct() {
        $default_tasks = array(
            'add_product_tags'      => 'Add relevant product tags to the product. Check first if the product already has tags and which tags already are available. If you think there a tags missing you are allowed to create new ones yourself.',
            'add_short_description' => 'Add a short description to the product based on the the information you can find about this product.',
        );
        $this->tasks   = \apply_filters( 'wcbai_default_tasks', $default_tasks );
    }

    public function get_tasks(): array {
        return $this->tasks;
    }
}
