# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin boilerplate project called "FiloDataBroker Plugin" that provides a modern development stack for building WordPress plugins. The plugin uses React for the frontend, PHP for the backend, and includes a comprehensive build system.

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
```

## Architecture

### PHP Backend Structure

- **Namespace**: `FiloDataBrokerPlugin`
- **Main Class**: `FiloDataBrokerPlugin` in `plugin.php`
- **Entry Point**: `fdb-wp-plugin.php`
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

API routes are defined in `includes/Routes/Api.php` using a Laravel-inspired routing system. The base prefix is defined by `FDBPLUGIN_ROUTE_PREFIX` constant.

### Database Layer

Uses wp-eloquent ORM for database operations. Models are in `includes/Models/` and follow Laravel Eloquent patterns.

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

### Development Notes

- Uses WordPress nonces for security
- Supports internationalization (i18n) with text domain `fdb-wp-plugin`
- Hot module reloading in development
- Separate build processes for admin and frontend
- WordPress block development with @wordpress/scripts

### Plugin Constants

Key constants defined in `plugin.php`:

- `FDBPLUGIN_VERSION` - Plugin version
- `FDBPLUGIN_DIR` - Plugin directory path
- `FDBPLUGIN_URL` - Plugin URL
- `FDBPLUGIN_ASSETS_URL` - Assets URL
- `FDBPLUGIN_ROUTE_PREFIX` - API route prefix
