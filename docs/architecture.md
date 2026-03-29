# Snelstack Feature Architecture

Standard structure for building new features in Snelstack themes. Based on FileBird's Laravel-inspired MVC pattern, simplified for our needs.

## Directory Structure

```
inc/feature-name/
├── index.php           # Loads all files, registers hooks on init
├── Rest.php            # REST API route definitions → maps to Controller methods
├── Controller.php      # Business logic, request validation, calls Model
├── Model.php           # Database queries only (static methods, no logic)
├── QueryFilter.php     # WordPress hook modifications (posts_clauses, pre_get_posts, etc.)
├── Install.php         # Database table creation (dbDelta), runs on theme activation

src/admin/feature-name/
├── index.js            # React entry point
├── components/         # UI components
├── styles/             # CSS if needed (usually Tailwind)
```

## Layer Responsibilities

### Rest.php — Route Definitions
- Registers REST endpoints via `register_rest_route()`
- Maps each route to a Controller method
- Handles permission callbacks
- Does NOT contain business logic

```php
namespace Snel\MediaFolders;

class Rest {
    private $controller;

    public function __construct() {
        $this->controller = new Controller();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('snel/v1', '/folders', [
            'methods'             => 'GET',
            'callback'            => [$this->controller, 'get_folders'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('snel/v1', '/folders', [
            'methods'             => 'POST',
            'callback'            => [$this->controller, 'create_folder'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function permission_check() {
        return current_user_can('upload_files');
    }
}
```

### Controller.php — Business Logic
- Receives WP_REST_Request, validates input
- Calls Model for database operations
- Returns WP_REST_Response or WP_Error
- Where the "thinking" happens

```php
namespace Snel\MediaFolders;

class Controller {
    public function create_folder(\WP_REST_Request $request) {
        $name   = sanitize_text_field($request->get_param('name'));
        $parent = intval($request->get_param('parent'));

        if (empty($name)) {
            return new \WP_Error('validation', 'Name is required');
        }

        $id = Model::create($name, $parent);

        if (!$id) {
            return new \WP_Error('create_failed', 'Folder already exists');
        }

        return rest_ensure_response(['id' => $id, 'name' => $name]);
    }

    public function get_folders(\WP_REST_Request $request) {
        $folders = Model::all();
        return rest_ensure_response($folders);
    }
}
```

### Model.php — Database Queries
- Static methods only
- Pure SQL via $wpdb
- No request handling, no validation, no WordPress hooks
- Returns raw data (arrays, objects, IDs, booleans)

```php
namespace Snel\MediaFolders;

class Model {
    private static $table = 'snel_folders';

    public static function all() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}" . self::$table . " ORDER BY ord ASC"
        );
    }

    public static function create($name, $parent = 0) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . self::$table, [
            'name'   => $name,
            'parent' => $parent,
        ]);
        return $wpdb->insert_id;
    }
}
```

### QueryFilter.php — WordPress Hook Modifications
- Hooks into WordPress query system (posts_clauses, pre_get_posts, ajax_query_attachments_args)
- Modifies queries to filter by feature data (e.g. show only attachments in a folder)
- Keeps hook logic separate from business logic

```php
namespace Snel\MediaFolders;

class QueryFilter {
    public function __construct() {
        add_filter('ajax_query_attachments_args', [$this, 'filter_media_query']);
        add_filter('posts_clauses', [$this, 'modify_query'], 10, 2);
    }

    public function filter_media_query($query) {
        // Add folder filtering to media library AJAX queries
        return $query;
    }

    public function modify_query($clauses, $query) {
        // JOIN folder tables, filter by active folder
        return $clauses;
    }
}
```

### Install.php — Database Setup
- Creates custom tables via dbDelta()
- Runs on theme/plugin activation
- Handles version upgrades

```php
namespace Snel\MediaFolders;

class Install {
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->prefix}snel_folders (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(250) NOT NULL,
            parent bigint(20) unsigned DEFAULT 0,
            ord int DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
```

### index.php — Entry Point
- Loads all PHP files
- Initializes classes
- Registers activation hooks

```php
<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Rest.php';
require_once __DIR__ . '/QueryFilter.php';
require_once __DIR__ . '/Install.php';

new Snel\MediaFolders\Rest();
new Snel\MediaFolders\QueryFilter();
```

## Request Flow

```
Client (React)
    ↓ fetch('/wp-json/snel/v1/folders', { method: 'POST', body: { name: 'Photos' } })
    ↓
Rest.php
    ↓ register_rest_route maps to Controller::create_folder
    ↓
Controller.php
    ↓ Validates input (sanitize, check required fields)
    ↓ Calls Model::create('Photos', 0)
    ↓
Model.php
    ↓ $wpdb->insert(...)
    ↓ Returns insert_id
    ↓
Controller.php
    ↓ Returns rest_ensure_response(['id' => 5, 'name' => 'Photos'])
    ↓
Client (React)
    ↓ Updates UI
```

## When to Use Each Layer

| Need to... | Use |
|---|---|
| Define a URL endpoint | Rest.php |
| Validate input, orchestrate logic | Controller.php |
| Read/write database | Model.php |
| Hook into WordPress queries | QueryFilter.php |
| Create/update database tables | Install.php |
| Build admin UI | src/admin/feature-name/ |

## Rules

1. **Model never touches requests.** It doesn't know about WP_REST_Request.
2. **Controller never writes SQL.** It calls Model methods.
3. **Rest never contains logic.** It only maps routes to controller methods.
4. **QueryFilter is for WordPress hooks only.** Not for REST or business logic.
5. **Start flat.** One file per layer. Only split into subfolders if a layer grows beyond 200 lines.
6. **Namespace everything.** Use `Snel\FeatureName` namespace.

## Naming Convention

- Feature folder: `kebab-case` (e.g. `media-folders`, `review-emails`)
- PHP classes: `PascalCase` (e.g. `Controller`, `Model`, `QueryFilter`)
- PHP namespace: `Snel\PascalCase` (e.g. `Snel\MediaFolders`)
- REST namespace: `snel/v1` (shared across all features)
- Database tables: `snel_feature_name` (e.g. `snel_folders`, `snel_folder_attachments`)
- React entry: `src/admin/feature-name/index.js`

## Existing Features Reference

| Feature | Location | Pattern |
|---|---|---|
| Translations | `inc/translations/` | Older pattern (pre-architecture doc) |
| Snel SEO | Plugin: `snel-seo/` | Separate plugin, own structure |
| Media Folders | `inc/media-folders/` | First feature using this architecture |
