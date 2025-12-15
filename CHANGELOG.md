# Changelog

All notable changes to the Aseman Robot WordPress plugin will be documented in this file.

## [1.0.0] - 2024-12-14

### Added
- Initial release of Aseman Robot (ربات آسمان) WordPress plugin
- Multi-language support (Persian, English, Arabic) with automatic RTL/LTR detection
- AI content generation with remote API support (OpenAI-compatible)
- AI content generation with local AI support (LM Studio, Ollama)
- Queue system for asynchronous content generation via WP-Cron
- Anti-duplicate lock mechanism with configurable scope and time window
- Force generate capability to bypass duplicate detection
- Generate Content admin page with form submission
- Queue Manager admin page with job filtering and actions
- Reports Dashboard with statistics and analytics
- Settings page with AI configuration, prompt template, defaults, and duplicate lock settings
- Test Connection feature for AI provider verification
- Scheduled article publication with custom intervals
- SEO-optimized content generation (meta descriptions, tags, structured HTML)
- Integration with Yoast SEO and Rank Math
- Security features (nonces, sanitization, capability checks)
- Custom capabilities system (manage, generate, view reports, force generate)
- Database tables: wp_aseman_queue and wp_aseman_logs
- WP-Cron scheduled event (every 5 minutes)
- Retry logic for failed jobs with configurable max attempts
- AJAX handlers for job management (retry, cancel, delete, clone)
- Admin CSS with RTL/LTR support
- Admin JavaScript for interactive features
- Complete uninstall cleanup
- Translation-ready with .pot file
- Comprehensive documentation (README, Technical Guide, User Guide)

### Features
- Generates exactly 3 unique, SEO-optimized articles per job
- Customizable prompt template with placeholders
- Configurable duplicate lock window (default: 168 hours / 7 days)
- Four duplicate lock scope options (topic only to strict)
- Support for draft, publish, and scheduled post statuses
- Category and tag assignment for generated posts
- Post meta tracking for AI-generated content
- Error logging and reporting
- Recent activity tracking
- Top error messages reporting
- Statistics by time period (today, 7 days, 30 days)
- Job status tracking (pending, processing, done, failed, scheduled)
- Progress tracking (0/3, 1/3, 2/3, 3/3)
- Direct links to generated posts in admin
- Highlighting of specific jobs in Queue Manager
- Configurable generation parameters (temperature, max tokens, timeout)
- Configurable jobs per cron run

### Technical
- PHP 8.0+ compatibility
- WordPress 5.8+ compatibility
- WordPress Coding Standards compliance
- Prepared SQL statements for security
- Input sanitization and output escaping
- Object-oriented architecture with separation of concerns
- Repository pattern for database access
- Provider pattern for AI integrations
- Database indexes for performance
- Proper error handling with try-catch blocks
- Graceful degradation on failures
- No external dependencies (uses only WordPress core)

### Security
- Nonce verification on all forms and AJAX requests
- Capability checks on all admin pages and actions
- Input sanitization (sanitize_text_field, esc_url_raw)
- Output escaping (esc_html, esc_attr, esc_url, wp_kses_post)
- API token masking in UI
- SQL injection prevention with prepared statements
- XSS prevention with proper escaping
- CSRF protection with nonces
- Role-based access control with custom capabilities

### Documentation
- README.md: Plugin overview and quick start
- TECHNICAL.md: Architecture, API, and development guide
- USER_GUIDE.md: Comprehensive user manual with troubleshooting
- CHANGELOG.md: Version history and changes
- Inline code documentation with PHPDoc comments
- Translation template (.pot file)

### Files Structure
```
aseman-robot/
├── aseman-robot.php (Main plugin file)
├── uninstall.php (Cleanup on uninstall)
├── README.md
├── TECHNICAL.md
├── USER_GUIDE.md
├── CHANGELOG.md
├── admin/
│   ├── page-generate.php
│   ├── page-queue.php
│   ├── page-reports.php
│   └── page-settings.php
├── assets/
│   ├── admin.css
│   └── admin.js
├── includes/
│   ├── Activator.php
│   ├── Deactivator.php
│   ├── AI/
│   │   ├── Client.php
│   │   └── Providers/
│   │       ├── OpenAICompatible.php
│   │       └── OllamaNative.php
│   ├── Queue/
│   │   ├── QueueRepository.php
│   │   └── Processor.php
│   ├── Posts/
│   │   └── PostCreator.php
│   ├── Prompt/
│   │   └── Template.php
│   ├── Reports/
│   │   └── Stats.php
│   └── Helpers/
│       └── Security.php
└── languages/
    └── aseman-robot.pot
```

### Database Schema
- wp_aseman_queue: Job queue with 17 columns including duplicate_key and force_generate
- wp_aseman_logs: Activity logs with job_id, level, message, and context

### Known Limitations
- Requires WP-Cron to be enabled for automatic processing
- Generates exactly 3 articles per job (not configurable by design)
- Single AI provider can be configured at a time (can be switched in settings)
- Local AI requires separate installation and configuration of LM Studio or Ollama

### Future Considerations
- Support for more AI providers (Anthropic, Cohere, etc.)
- Bulk job creation from CSV
- Custom article structure templates
- Integration with more SEO plugins
- Image generation support
- Multi-site network support
- REST API endpoints
- Enhanced analytics and reporting
- Export/import settings
- Job prioritization

---

## Version Format

The version number follows [Semantic Versioning](https://semver.org/):
- MAJOR version for incompatible API changes
- MINOR version for new functionality in a backwards compatible manner
- PATCH version for backwards compatible bug fixes

## Categories

- **Added**: New features
- **Changed**: Changes in existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Vulnerability fixes
