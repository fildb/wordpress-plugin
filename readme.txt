=== FiloDataBroker ===
Contributors: denismajus
Tags: llms.txt, ai, machine learning, content generation, seo
Requires at least: 5.9
Tested up to: 6.8
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate llms.txt files for WordPress, offloading AI traffic to CDN while protecting your server infrastructure.

== Description ==

**Beta Plugin** - Creates llms.txt files following the [llmstxt.org](https://llmstxt.org) standard. Content is uploaded to FiloDataBroker CDN in markdown format, protecting your WordPress server from AI crawler traffic while making your content accessible to AI applications.

= Key Features =

* **Automatic llms.txt generation** at `/llms.txt`
* **CDN content offloading** - AI traffic goes to CDN, not your server
* **Markdown format** - Optimized for AI applications
* **Real-time updates** when content changes
* **Modern React admin interface**
* **Server protection** from high-volume AI requests

Perfect for content creators wanting AI-accessible websites without server performance impact.

= Roadmap =

**Version 1.0:** Enhanced filtering, CDN analytics, SEO integration
**Version 2.0:** Content monetization platform with licensing, usage analytics, and revenue dashboard
**Future:** Multi-language support, enterprise features

== Installation ==

1. Navigate to FiloDataBroker in your WordPress admin to configure the plugin.
2. Configure your settings and click "Generate Now" to create your first llms.txt file.
3. Your llms.txt file will be available at `yoursite.com/llms.txt`

== Frequently Asked Questions ==

= What is llms.txt? =

A standard format for making website content easily consumable by AI systems. Learn more at [llmstxt.org](https://llmstxt.org).

= Will this slow down my website? =

No. Content is stored on CDN, AI traffic doesn't hit your server, and file generation happens in background.

= How does CDN offloading work? =

Content is uploaded to FiloDataBroker CDN in markdown format. Your llms.txt contains CDN links, so AI applications access content from CDN instead of your WordPress server.

= Is this a beta version? =

Yes, version 1.0.0 (beta). Advanced features including content monetization are in development.

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
