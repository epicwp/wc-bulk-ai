<?php
namespace EPICWP\WC_Bulk_AI;
use EPICWP\WC_Bulk_AI\Handlers\Bulk_CLI_Handler;
use EPICWP\WC_Bulk_AI\Handlers\Settings_Handler;
use EPICWP\WC_Bulk_AI\Services\Product_Collector;
use EPICWP\WC_Bulk_AI\Services\MCP;
use EPICWP\WC_Bulk_AI\Services\Agent;
use EPICWP\WC_Bulk_AI\Services\Job_Processor;
use EPICWP\WC_Bulk_AI\Services\Logger\Verbose_Logger;
use EPICWP\WC_Bulk_AI\Interfaces\Process_Logger;
use XWP\DI\Decorators\Module;
use XWP\DI\Interfaces\On_Initialize;

#[Module(
    container: 'wc-bulk-ai',
    hook: 'plugins_loaded', 
    priority: 10,
    handlers: array(
        Bulk_CLI_Handler::class,
        Settings_Handler::class,
    ),
    services: array(
        Product_Collector::class,
        MCP::class,
        Agent::class,
        Job_Processor::class,
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
        return array(
            'app.client' => \OpenAI::client( \get_option( 'wcbai_openai_api_key' ) ),
            Process_Logger::class => \DI\autowire(Verbose_Logger::class),
        );
    }

    public function on_initialize(): void {
        \do_action( 'wc_bulk_ai_loaded' );
    }
}