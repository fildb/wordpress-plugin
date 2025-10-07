=== FiloDataBroker ===
Contributors: denismajus
Tags: llms.txt, ai, machine learning, content generation, seo
Requires at least: 5.9
Tested up to: 6.8
Stable tag: 1.1.1
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate llms.txt files for WordPress, offloading AI traffic to CDN while protecting your server infrastructure.

== Description ==

Creates llms.txt files following the [llmstxt.org](https://llmstxt.org) standard. Content is uploaded to FiloDataBroker CDN in markdown format, protecting your WordPress server from AI crawler traffic while making your content accessible to AI applications.

> Note: This is a **Beta version** of the plugin.

== Key Features ==

* **Automatic llms.txt generation** at `/llms.txt`
* **CDN content offloading** - AI traffic goes to CDN, not your server
* **Markdown format** - Optimized for AI applications
* **Real-time updates** when content changes
* **Modern React admin interface**
* **Server protection** from high-volume AI requests

Perfect for content creators wanting AI-accessible websites without server performance impact.

== Installation ==

1. Navigate to FiloDataBroker in your WordPress admin to configure the plugin.
2. Configure your settings and click "Generate Now" to create your first llms.txt file.
3. Your llms.txt file will be available at `yoursite.com/llms.txt`

== Roadmap ==

**Version 1.0 (beta):** Enhanced filtering, CDN analytics, SEO integration
**Version 2.0:** Content monetization platform with licensing, usage analytics, and revenue dashboard
**Future:** Multi-language support, enterprise features

== Frequently Asked Questions ==

= What is llms.txt? =

A standard format for making website content easily consumable by AI systems. Learn more at [llmstxt.org](https://llmstxt.org).

= Will this slow down my website? =

No. Content is stored on CDN, AI traffic doesn't hit your server, and file generation happens in background.

= How does CDN offloading work? =

Content is uploaded to FiloDataBroker CDN in markdown format. Your llms.txt contains CDN links, so AI applications access content from CDN instead of your WordPress server.

= Is this a beta version? =

Yes, all 1.\*.\* versions are beta. Advanced features including content monetization are in development.

== Source Code ==

The complete source code for this plugin, including uncompiled JavaScript and CSS, is available on GitHub:
https://github.com/fildb/wordpress-plugin

The plugin includes:
* React source files in the `src/` directory
* Build configuration files (Vite, Tailwind, PostCSS)
* PHP source code
* All third-party libraries are managed via Composer and npm

To build from source:
1. Install dependencies: `npm install && composer install`
2. Build assets: `npm run build`

== External Services ==

This plugin uses the FiloDataBroker CDN service to host your website content in a format optimized for AI applications. This is a core feature of the plugin that protects your WordPress server from high-volume AI traffic.

= What is the service used for? =

The FiloDataBroker CDN stores your website content in markdown format and serves it to AI applications. This offloads AI crawler traffic from your WordPress server, improving performance and reducing server load.

= What data is sent? =

When you generate or update your llms.txt file, the plugin sends the following data to the FiloDataBroker CDN:
* **Website content**: Posts, pages, and custom post types converted to markdown format
* **Site identifier**: A sanitized version of your site URL (e.g., "example-com") used to organize your content on the CDN
* **Filenames**: Names for the markdown files being uploaded

No personal data, user information, or sensitive site data is transmitted.

= When is data sent? =

Data is sent to the FiloDataBroker CDN in the following situations:
* When you manually click "Generate Now" in the plugin settings
* When you save or update a post/page and automatic generation is enabled
* When you delete content and automatic generation is enabled

= Service information =

* Service provider: FiloDataBroker
* Terms of Service: https://fildb.github.io/terms.html
* Privacy Policy: https://fildb.github.io/privacy.html
* API Endpoint: https://fildb.majus.app/api/upload

By using this plugin, you acknowledge that your website content will be uploaded to and stored on the FiloDataBroker CDN service.

== Changelog ==

= 1.0.0 =
* Beta release
* Automatic llms.txt file generation
* Real-time content updates
* Modern React-based admin interface
* Support for posts, pages, and custom post types
* WordPress coding standards compliance

== Upgrade Notice ==

= 1.0.0 =
Beta release of the FiloDataBroker plugin.
