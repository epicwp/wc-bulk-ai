<?php
namespace EPICWP\WC_Bulk_AI\Handlers;

use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Handler;

#[Handler( tag: 'admin_menu', priority: 5 )]
class Settings_Handler {
    /**
     * Add the sub menu pages.
     */
    #[Action( tag: 'admin_menu' )]
    public function add_menu_page(): void {
        \add_submenu_page(
            parent_slug: 'woocommerce',
            menu_slug: 'wc-bulk-ai',
            page_title: 'Bulk AI',
            menu_title: 'Bulk AI',
            capability: 'manage_options',
            callback: array( $this, 'render_settings_page' ),
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . \esc_html__( 'Bulk AI', 'wc-bulk-ai' ) . '</h1>';

        echo '<form method="post" action="options.php">';
        \settings_fields( 'wc-bulk-ai' );
        \do_settings_sections( 'wc-bulk-ai' );
        \submit_button();
        echo '</form>';

        echo '</div>';
    }

    /**
     * Register the settings.
     */
    #[Action( tag: 'admin_init' )]
    public function register_settings(): void {
        $page    = 'wc-bulk-ai';
        $section = 'wc-bulk-ai-api-settings';

        \register_setting( $page, 'wcbai_openai_api_key' );
        \add_settings_section( $section, 'API Settings', array( $this, 'render_settings_section' ), $page );
        \add_settings_field(
            'wcbai_openai_api_key',
            'OpenAI API Key',
            array( $this, 'render_settings_field' ),
            $page,
            $section,
            array( 'label_for' => 'wcbai_openai_api_key' ),
        );
    }

    /**
     * Render the settings section.
     */
    public function render_settings_section(): void {
        echo '<p>' . \esc_html__( 'API Settings', 'wc-bulk-ai' ) . '</p>';
    }

    /**
     * Render the settings field.
     */
    public function render_settings_field(): void {
        echo '<input type="password" name="wcbai_openai_api_key" value="' . \esc_attr(
            \get_option( 'wcbai_openai_api_key' ),
        ) . '" class="regular-text" />';
    }
}
