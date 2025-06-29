<?php
namespace EPICWP\WC_Bulk_AI;
use EPICWP\WC_Bulk_AI\Handlers\Bulk_CLI_Handler;
use EPICWP\WC_Bulk_AI\Services\Product_Collector;
use EPICWP\WC_Bulk_AI\Services\MCP;
use EPICWP\WC_Bulk_AI\Services\Agent;
use XWP\DI\Decorators\Module;
use XWP\DI\Interfaces\On_Initialize;

#[Module(
    container: 'wc-bulk-ai',
    hook: 'plugins_loaded', 
    priority: 10,
    handlers: array(
        Bulk_CLI_Handler::class,
    ),
    services: array(
        Product_Collector::class,
        MCP::class,
        Agent::class,
    ),
)]
class App implements On_Initialize {
    /**
     * Returns the PHP-DI container definition.
     *
     * @see https://php-di.org/doc/php-definitions.html
     *
     * @return array<string,mixed>
     */
    public static function configure(): array {
        return array();
    }

    public function on_initialize(): void {
        \do_action( 'wc_bulk_ai_loaded' );
    }
}