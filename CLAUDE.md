# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin boilerplate project called "FiloDataBroker Plugin" that provides a modern development stack for building WordPress plugins. The plugin uses React for the frontend, PHP for the backend, and includes a comprehensive build system. This specific instance implements an LLM.txt file generation feature for WordPress sites.

## Development Commands

### Setup

```bash
npm install
composer install
```

### Development

```bash
npm run dev                # Start both admin and frontend dev servers
npm run dev:admin          # Start admin dev server only (port 5174)
npm run dev:frontend       # Start frontend dev server only (port 5173)
npm run dev:server         # Start dev servers with wp-now local WordPress
npm run dev:all            # Start dev servers and block development
```

### Building

```bash
npm run build              # Build production assets for both admin and frontend
npm run block:build        # Build WordPress blocks
```

### Code Quality

```bash
npm run format:check       # Check code formatting with Prettier
npm run format:fix         # Fix code formatting with Prettier
```

### WordPress Blocks

```bash
npm run block:start        # Start block development
npm run block:build        # Build blocks for production
```

### Plugin Management

```bash
npm run rename             # Rename plugin using plugin-config.json
npm run release            # Create production release package
npm run change-text-domain # Change plugin text domain
npm run i18n               # Generate pot file for internationalization
```

### Storybook (for component development)

```bash
npm run storybook          # Start Storybook dev server
npm run build-storybook    # Build Storybook for production
```

## Architecture

### PHP Backend Structure

- **Namespace**: `FiloDataBrokerPlugin`
- **Main Class**: `FiloDataBrokerPlugin` in `plugin.php`
- **Entry Point**: `fidabr-plugin.php`
- **Plugin Configuration**: `plugin-config.json`

### Directory Structure

- `includes/` - PHP backend code
  - `Controllers/` - API endpoint handlers
  - `Models/` - Database models using wp-eloquent ORM
  - `Routes/` - API route definitions
  - `Core/` - Core plugin functionality
  - `Admin/` - WordPress admin integration
  - `Assets/` - Asset management
- `src/` - React frontend code
  - `admin/` - Admin panel React application
  - `frontend/` - Public-facing React components
  - `components/` - Shared React components (includes shadcn/ui)
  - `blocks/` - WordPress Gutenberg blocks
- `database/` - Database migrations and seeders
- `views/` - PHP template files
- `assets/` - Compiled assets and static files

### API Routes

API routes are defined in `includes/Routes/Api.php` using a Laravel-inspired routing system:

- **Route Registration**: Uses `Route::prefix()` with callback functions
- **Authentication**: Applied at the route level using `->auth()` method with closures
- **Nonce Verification**: REST API routes use `wp_rest` nonces for POST requests
- **Permission Checking**: Routes verify `manage_options` capability for admin functions
- **Base Prefix**: Defined by `FIDABR_PLUGIN_ROUTE_PREFIX` constant

Example route structure:

```php
Route::prefix(FIDABR_PLUGIN_ROUTE_PREFIX, function(Route $route) {
    $route->get('/llm/settings', 'Controller@method');
    $route->post('/llm/settings', 'Controller@method');
})->auth(function() {
    // Authentication logic
});
```

### Database Layer

Uses wp-eloquent ORM for database operations. Models are in `includes/Models/` and follow Laravel Eloquent patterns.

### MVC Architecture

The plugin follows a proper MVC separation:

- **Models (includes/Core/)**: Handle data logic and business rules (e.g., Settings.php)
- **Controllers (includes/Controllers/)**: Handle HTTP requests and coordinate between models and views
- **Views (src/admin/)**: React components for user interface
- **Routes (includes/Routes/)**: Define API endpoints and authentication

### REST API Authentication Pattern

Authentication is handled at the router level with:

1. User login verification (`is_user_logged_in()`)
2. Capability checking (`$user->has_cap('manage_options')`)
3. Nonce verification for POST requests (`wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'wp_rest')`)

### Data Flow Between PHP and JavaScript

Data is passed from PHP to JavaScript using WordPress `wp_localize_script()`:

- **PHP**: `includes/Assets/Admin.php` generates data via `get_data()` method
- **JavaScript**: Access via `window.fidabrAdmin` object (object name defined by `OBJ_NAME` constant)
- **Nonce**: Generated with `wp_create_nonce('wp_rest')` and sent as `X-WP-Nonce` header

### Frontend Stack

- **React 18** with React Router for navigation
- **Vite** for build tooling with separate configs for admin and frontend
- **Tailwind CSS** for styling
- **shadcn/ui** components (configured in `components.json`)
- **TypeScript** support enabled

### Build Configuration

- `vite.admin.config.js` - Admin panel build configuration
- `vite.frontend.config.js` - Frontend build configuration
- `tailwind.config.js` - Tailwind CSS configuration
- `gruntfile.cjs` - Plugin packaging and release tasks

### Code Standards

- **PHP**: WordPress coding standards (phpcs.xml.dist)
- **JavaScript/React**: ESLint with WordPress scripts
- **Formatting**: Prettier with WordPress presets

### LLM.txt Generation Feature

This plugin implements a feature to generate `llms.txt` files for WordPress sites:

- **Core Logic**: `includes/Core/Generator.php` handles file generation
- **Settings Management**: `includes/Core/Settings.php` manages plugin configuration
- **Content Crawling**: `includes/Core/ContentCrawler.php` extracts site content
- **API Endpoints**: Full REST API for settings management and file generation
- **Admin Interface**: React-based admin panel for configuration and generation
- **Auto-generation**: Hooks into WordPress post save/delete events for automatic updates

### Key Implementation Patterns

- **Separation of Concerns**: Models handle data, Controllers handle requests, Services handle business logic
- **Router-level Authentication**: Authentication applied to entire route groups, not individual controller methods
- **Proper Nonce Handling**: Uses WordPress `wp_rest` nonces for API security
- **React Integration**: Modern React 18 with shadcn/ui components and Tailwind CSS
- **WordPress Standards**: Follows WordPress coding standards and plugin development best practices

### Development Notes

- Uses WordPress REST API nonces (`wp_rest`) for security
- Supports internationalization (i18n) with text domain `fidabr`
- Hot module reloading in development via Vite
- Separate build processes for admin and frontend
- WordPress block development with @wordpress/scripts
- Storybook integration for component development
- shadcn/ui components can be added with `npx shadcn@latest add [component]`

### Plugin Constants

Key constants defined in `plugin.php`:

- `FIDABR_PLUGIN_VERSION` - Plugin version
- `FIDABR_PLUGIN_DIR` - Plugin directory path
- `FIDABR_PLUGIN_URL` - Plugin URL
- `FIDABR_PLUGIN_ASSETS_URL` - Assets URL
- `FIDABR_PLUGIN_ROUTE_PREFIX` - API route prefix

### Adding New API Routes

Add routes in `includes/Routes/Api.php`:

```php
Route::prefix(FIDABR_PLUGIN_ROUTE_PREFIX, function(Route $route) {
    $route->get('/endpoint', '\FiloDataBrokerPlugin\Controllers\ControllerName@method');
    $route->post('/endpoint', '\FiloDataBrokerPlugin\Controllers\ControllerName@method');
})->auth(function() {
    // Authentication logic
});
```

### Adding New Controllers

1. Create controller in `includes/Controllers/[Domain]/Actions.php`
2. Follow MVC pattern: Controllers orchestrate, Models handle data
3. Return REST API responses: `rest_ensure_response($data)` or `new \WP_Error()`

### Adding Shortcodes

Use the Shortcode class for easy shortcode registration:

```php
Shortcode::add()
    ->tag('myshortcode')
    ->attrs(['id', 'name'])
    ->render(plugin_dir_path(__FILE__) . 'views/shortcode/myshortcode.php');
```

### Frontend Component Development

- Components use shadcn/ui and Tailwind CSS
- Add new components: `npx shadcn@latest add [component]`
- Shared components go in `src/components/`
- Admin-specific components go in `src/admin/components/`
- Use Storybook for component development and testing
