# Product Requirements Document (PRD)

## LLM File Generator WordPress Plugin

### **Project Overview**

Transform the FiloDataBroker Plugin boilerplate into a specialized WordPress plugin that automatically generates and manages `llms.txt` files according to the [llmstxt.org](https://llmstxt.org) standard. The plugin offloads content storage to the FiloDataBroker CDN infrastructure, with the local `llms.txt` file containing links to external resources.

### **Problem Statement**

WordPress websites need an automated way to create structured, machine-readable content summaries (`llms.txt` files) that help LLMs and AI systems better understand and navigate website content. Additionally, sites require efficient content delivery through CDN infrastructure to reduce server load and improve performance for AI content consumption.

### **Target Users**

- WordPress website owners and administrators
- Content creators who want their sites to be AI/LLM-friendly
- Developers building AI-integrated WordPress solutions

---

## **Core Features (MVP)**

### **1. LLM File Generation Engine**

- **Auto-generate llms.txt** at website root (`/llms.txt`) using the llms-txt-php library
- **Content crawler** to analyze WordPress content (posts, pages, custom post types)
- **Smart content filtering** and summarization
- **CDN integration** - Upload processed content to FiloDataBroker CDN
- **External link generation** - Local llms.txt contains links to CDN-hosted resources
- **Real-time updates** when content changes

### **2. Admin Interface (React Components)**

- **Settings page** for basic configuration
- **Manual regeneration** controls
- **Status dashboard** showing last generation time and file status

### **3. Content Processing**

- **Intelligent content extraction** from WordPress posts/pages
- **HTML cleanup** and text normalization
- **Excerpt generation** for concise descriptions
- **Link collection** with proper formatting according to llmstxt.org standard

---

## **Technical Architecture**

### **Technology Stack**

- **Backend**: PHP with WordPress APIs
- **Frontend**: React components with TypeScript
- **Build System**: Vite (keeping existing configs)
- **Styling**: Tailwind CSS with shadcn/ui components
- **LLM File Generation**: llms-txt-php library (composer package)
- **CDN Integration**: HTTP API client for FiloDataBroker CDN

### **Core PHP Classes**

```
includes/
├── Core/
│   ├── Plugin.php           # Main plugin class
│   ├── Generator.php        # LLM file generation logic (using llms-txt-php)
│   ├── ContentCrawler.php   # WordPress content analysis
│   └── CDNClient.php        # FiloDataBroker CDN HTTP API client
├── Admin/
│   ├── Settings.php         # Admin settings page integration
│   └── Ajax.php            # AJAX handlers for React components
└── Models/
    └── LLMContent.php      # Content data structure
```

### **React Components Structure**

```
src/
├── components/
│   ├── ui/                  # shadcn/ui components (keep existing)
│   ├── LLMGenerator/
│   │   ├── Settings.tsx     # Main settings component
│   │   ├── StatusDashboard.tsx
│   │   └── ManualControls.tsx
└── admin/
    └── app.tsx             # Main admin app (simplified)
```

---

## **Feature Specifications**

### **File Generation**

- **Output Format**: Strictly follows llmstxt.org standard using llms-txt-php library:

  ```markdown
  # Site Title

  > Optional site description

  Optional detailed information

  ## Pages

  - [Page Title](https://cdn.filodatabroker.com/site123/page1.md): Brief page description

  ## Posts

  - [Post Title](https://cdn.filodatabroker.com/site123/post1.md): Brief post description
  ```

- **CDN Architecture**: Local llms.txt contains links to CDN-hosted content files
- **Content Upload**: Individual posts/pages uploaded as separate files to CDN
- **Link Generation**: URLs point to FiloDataBroker CDN infrastructure

### **Content Processing Rules**

- Extract title, URL, and brief description for each piece of content
- Clean HTML tags and normalize text
- Generate concise descriptions (1-2 sentences per item)
- Group content by type (Pages, Posts, Custom Post Types)
- **CDN Upload Process**: Upload full content as individual files to CDN
- **URL Mapping**: Generate CDN URLs for each piece of content
- **Local File Generation**: Create llms.txt with CDN links using llms-txt-php

### **CDN Integration Architecture**

- **Content Upload Flow**:
  1. Extract and process WordPress content
  2. Upload individual content files to CDN via HTTP POST
  3. Receive CDN URLs for uploaded content
  4. Generate local llms.txt with CDN links using llms-txt-php
- **API Configuration**:
  - **Endpoint**: Configurable via source code constants (default: `https://example.com`)
  - **Upload Method**: HTTP POST requests for content upload
  - **Response Format**: CDN returns public URLs for uploaded content
- **Content Structure on CDN**:
  - Individual files for each post/page
  - Organized by site identifier
  - Full content accessible via direct CDN links

### **Admin Controls**

- **Basic Settings**:
  - Enable/disable automatic generation
  - Select post types to include
  - Manual "Generate Now" button
- **Status Information**:
  - Last generation timestamp
  - Number of items included
  - File size and location

---

## **Implementation Plan**

### **Phase 1: Core Implementation**

1. **Clean up boilerplate** - Remove unnecessary pages, keep React components
2. **Add llms-txt-php dependency** - Install and configure composer package
3. **Implement CDNClient class** - HTTP API integration for content upload
4. **Implement Generator class** - Core file creation logic using llms-txt-php
5. **Build ContentCrawler** - WordPress content extraction and CDN upload
6. **Create basic React admin interface** - Settings and controls

### **Phase 2: Polish & Integration**

1. **Real-time updates** - Hook into WordPress post save/delete events
2. **CDN synchronization** - Ensure content updates are reflected on CDN
3. **Error handling** - Graceful failure and user feedback for CDN operations
4. **Testing** - Edge cases, CDN connectivity, and performance validation

---

## **Stretch Goals**

_Features to implement after MVP completion_

### **Advanced Features**

- **Content filtering options** - Exclude specific posts/pages, taxonomy-based filtering
- **SEO integration** - Yoast SEO and RankMath compatibility for enhanced descriptions
- **Configurable content limits** - Word count and post count restrictions
- **Preview functionality** - See generated content before publishing
- **Template customization** - Custom file structure and formatting options
- **Automation & Caching** - Scheduled updates, cache management, background processing

---

## **Success Metrics**

- Successfully generates valid llms.txt files following the standard
- Integrates seamlessly with WordPress admin interface
- Automatically updates when site content changes
- Provides clear user feedback and status information
- Maintains good performance on sites with large amounts of content

---

## **Technical Constraints**

- Must maintain compatibility with existing WordPress installations
- Keep React components and Vite build system from boilerplate
- Follow WordPress coding standards and security best practices
- Ensure generated files are accessible at `/llms.txt` URL
- Handle file permissions and directory access gracefully
- **CDN API Configuration**: Use configurable constants for CDN endpoint URLs
- **HTTP Client Requirements**: Reliable error handling for CDN communication
- **Composer Dependencies**: Require llms-txt-php library via composer

---

## **Out of Scope**

- Complex content analysis or AI-powered summarization
- Multi-site network support
- Advanced caching mechanisms
- Extensive customization options
- Full FiloDataBroker HTTP API implementation (placeholder endpoints used)
- Advanced CDN management features (automatic cleanup, versioning)
- Complex authentication mechanisms for CDN access
