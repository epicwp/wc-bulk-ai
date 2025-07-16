# WooCommerce Product Bulk Agent

A powerful WordPress plugin that uses AI to bulk update your WooCommerce products automatically. The plugin leverages OpenAI's API (chat and tool calling) to perform intelligent product content updates, from adding tags to generating descriptions, with full rollback capabilities.

## Features

- 🤖 **AI-Powered Processing**: Uses OpenAI's API with conversation management and tool calls
- 📦 **Bulk Operations**: Process hundreds of products automatically
- 🎯 **Smart Filtering**: Filter products by category, language, or other criteria
- 🔄 **Rollback Support**: Undo changes with complete rollback functionality
- ⚡ **Resume Capability**: Resume interrupted tasks or extend existing ones
- 📊 **Progress Tracking**: Monitor task progress and completion statistics
- 🛠️ **WP-CLI Integration**: Complete command-line interface for automation
- 📈 **Database Management**: Organized job and run tracking

## Demo Video

See the WooCommerce Product Bulk Agent in action:

[![WooCommerce Product Bulk Agent Demo](https://img.youtube.com/vi/nCkvmf8z5Q8/0.jpg)](https://www.youtube.com/watch?v=nCkvmf8z5Q8)

[Watch the demo video](https://www.youtube.com/watch?v=nCkvmf8z5Q8) to see how the plugin works with real WooCommerce products.

**Note**: Commands shown in the video may differ from the current version. Always refer to this documentation or use `wp product-bulk-agent --help` for the most up-to-date command syntax and options.

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 8.2 or higher
- OpenAI API key
- WP-CLI (for command-line operations)

## Installation

1. Upload the plugin files to `/wp-content/plugins/wc-bulk-ai/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to WooCommerce → Bulk AI to configure settings

## Configuration

### OpenAI API Key Setup

1. Go to **WooCommerce → Bulk AI** in your WordPress admin
2. Enter your OpenAI API key in the "OpenAI API Key" field
3. Click "Save Changes"

**Important**: Without a valid OpenAI API key, the plugin will not function.

## Usage

### Available WP-CLI Commands

The plugin provides a comprehensive set of WP-CLI commands under the `product-bulk-agent` namespace:

#### Create a New Task

```bash
wp product-bulk-agent create-task [task] [--lang=<language>] [--category=<category>]
```

**Examples:**

```bash
# Create a task interactively
wp product-bulk-agent create-task

# Create a task with specific parameters
wp product-bulk-agent create-task "Add SEO-optimized product tags" --lang=en --category=electronics

# Create a task for products in a specific category
wp product-bulk-agent create-task --category=clothing
```

**Parameters:**

- `task` (optional): The task description. If not provided, you'll be prompted to enter one or select from predefined tasks
- `--lang` (optional): Language code (e.g., en, fr, de) to filter products
- `--category` (optional): Category slug to filter products

#### Start a Task

```bash
wp product-bulk-agent start [--select] [--task=<task_id>]
```

**Examples:**

```bash
# Start the latest task
wp product-bulk-agent start

# Select from available tasks
wp product-bulk-agent start --select

# Start a specific task by ID
wp product-bulk-agent start --task=123
```

#### Resume a Task

```bash
wp product-bulk-agent resume-task <task_id> [--select] [--extend]
```

**Examples:**

```bash
# Resume a specific task
wp product-bulk-agent resume-task 123

# Resume with task selection
wp product-bulk-agent resume-task --select

# Resume and add more products
wp product-bulk-agent resume-task 123 --extend
```

#### List Tasks

```bash
wp product-bulk-agent list-tasks [--available]
```

**Examples:**

```bash
# List all tasks
wp product-bulk-agent list-tasks

# List only available (non-completed) tasks
wp product-bulk-agent list-tasks --available
```

#### Rollback a Task

```bash
wp product-bulk-agent rollback-task <task_id>
```

**Example:**

```bash
wp product-bulk-agent rollback-task 123
```

#### Clear All Data

```bash
wp product-bulk-agent clear [--force]
```

**Examples:**

```bash
# Clear with confirmation prompt
wp product-bulk-agent clear

# Force clear without confirmation
wp product-bulk-agent clear --force
```

### Predefined Tasks

The plugin includes several predefined tasks:

1. **Add Product Tags**: Automatically adds relevant tags to products based on their content
2. **Add Short Description**: Generates short descriptions for products based on available information

### Custom Tasks

You can create custom tasks by providing specific instructions:

**Examples:**

- "Add missing product attributes based on the product description"
- "Generate SEO-optimized meta descriptions"
- "Standardize product naming conventions"
- "Add size and color attributes to clothing products"

## Workflow

### Typical Usage Pattern

1. **Create a Task**: Define what you want to accomplish

   ```bash
   wp product-bulk-agent create-task "Add relevant product tags"
   ```

2. **Filter Products**: Specify criteria (category, language, limit)

   - The system will prompt you for the number of products to process
   - Optionally filter by category or language

3. **Review Selection**: The plugin shows you which products will be processed

4. **Start Processing**: Begin the AI-powered bulk operation

   ```bash
   wp product-bulk-agent start
   ```

5. **Monitor Progress**: Check task status and completion

   ```bash
   wp product-bulk-agent list-tasks
   ```

6. **Rollback if Needed**: Undo changes if required
   ```bash
   wp product-bulk-agent rollback-task 123
   ```

## Database Structure

The plugin creates three tables:

### `wcbai_jobs`

- Stores individual product processing jobs
- Tracks job status, product ID, and completion times
- Links to parent run and stores AI feedback

### `wcbai_runs`

- Stores bulk task runs
- Contains task descriptions and run statistics
- Tracks overall progress and completion

### `wcbai_product_rollbacks`

- Stores rollback information for each product change
- Enables complete restoration of previous values
- Organized by job and property type

## AI Tool System (MCP)

The plugin uses a Model Control Protocol (MCP) system that provides the AI agent with specific tools to interact with WooCommerce products. This system ensures controlled and safe product modifications.

### Available AI Tools

The AI agent has access to the following tools for product manipulation:

#### Product Information Tools

- **`get_product`**: Retrieve complete product details by ID
- **`get_products`**: Get a list of products with filtering options
- **`get_product_title`**: Get the current product title
- **`get_product_description`**: Get the current product description
- **`get_product_short_description`**: Get the current short description
- **`get_product_tags`**: Get current product tags
- **`get_available_product_tags`**: Get all available product tags in the system

#### Product Update Tools

- **`update_product_title`**: Update product titles
- **`update_product_description`**: Update product descriptions
- **`update_product_short_description`**: Update short descriptions
- **`update_product_tags`**: Add or replace product tags (with append option)

### How It Works

1. **AI Decision Making**: The AI agent analyzes the task and current product state
2. **Tool Selection**: Based on the task, the AI selects appropriate tools
3. **Safe Execution**: All tool calls are executed through the MCP system with proper validation
4. **Rollback Tracking**: All changes are tracked for potential rollback
5. **Iterative Process**: The AI can make multiple tool calls until the task is complete

### Example AI Workflow

For a task like "Add relevant product tags":

1. AI calls `get_product` to understand the product
2. AI calls `get_product_tags` to see existing tags
3. AI calls `get_available_product_tags` to see what tags are available
4. AI analyzes product content and determines relevant tags
5. AI calls `update_product_tags` to add new tags (with append: true)

### Tool Safety Features

- **Validation**: All product IDs are validated before operations
- **Error Handling**: Graceful handling of missing products or invalid data
- **Rollback Support**: All changes are tracked for complete rollback capability
- **Permission Checks**: Respects WordPress user capabilities
- **Audit Trail**: All tool executions are logged for debugging

### Extending the Tool System

Developers can extend the MCP system by:

```php
// Hook into tool execution
add_action('wcbai_mcp_function_before_execute', function($function_name, $arguments) {
    // Custom logic before tool execution
});

add_action('wcbai_mcp_function_executed', function($function_name, $arguments, $result) {
    // Custom logic after tool execution
});
```

## Advanced Features

### Task Resumption

Tasks can be resumed if interrupted:

- Resume from the last completed job
- Add additional products to existing tasks
- Maintain rollback capabilities across resume operations

### Product Filtering

Advanced filtering options:

- **Category**: Filter by WooCommerce product categories
- **Language**: Filter by language (useful for multilingual sites)
- **Exclusion**: Exclude products from previous runs when extending

### Rollback System

Complete rollback capabilities:

- Restore individual product properties
- Rollback entire task runs
- Maintain change history for audit trails

## Error Handling

The plugin includes comprehensive error handling:

- Graceful AI API failures
- Database transaction safety
- Detailed error logging
- Recovery mechanisms for interrupted tasks

## Performance Considerations

- **Batch Processing**: Processes products sequentially to avoid overwhelming the AI API
- **Memory Management**: Efficient handling of large product catalogs
- **Rate Limiting**: Respects OpenAI API rate limits
- **Database Optimization**: Indexed tables for fast query performance

## Hooks and Filters

### Available Filters

```php
// Modify the system prompt sent to the AI
add_filter('wcbai_system_prompt', function($prompt) {
    return $prompt . ' Additional instructions...';
});

// Add custom predefined tasks
add_filter('wcbai_default_tasks', function($tasks) {
    $tasks['custom_task'] = 'Your custom task description';
    return $tasks;
});
```

### Available Actions

```php
// Hook into plugin initialization
add_action('wc_bulk_ai_loaded', function() {
    // Plugin is fully loaded
});
```

## Troubleshooting

### Common Issues

1. **"OpenAI API key is not configured"**

   - Solution: Configure your API key in WooCommerce → Bulk AI settings

2. **"No matching products found"**

   - Solution: Check your filtering criteria or ensure products exist in the specified category

3. **Tasks not completing**

   - Solution: Check error logs and ensure your OpenAI API key has sufficient credits

4. **Memory issues with large catalogs**
   - Solution: Process products in smaller batches or increase PHP memory limit

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Security

- API keys are stored securely using WordPress options
- All database operations use prepared statements
- User capability checks ensure only authorized users can access features
- Input validation and sanitization throughout

## Support

For issues, feature requests, or contributions, please refer to the plugin's repository or contact the development team.

## License

This plugin is released under the GPL v2 or later license.
