# ربات آسمان (Aseman Robot)

**AI-Powered Content Generation Plugin for WordPress**

## Description

Aseman Robot is a production-ready WordPress plugin that generates high-quality, SEO-optimized articles using AI. It supports both remote OpenAI-compatible APIs and local AI setups (LM Studio, Ollama).

## Features

✅ **Multi-Language Support**: Persian (fa_IR), English (en_US), Arabic (ar) with RTL/LTR  
✅ **AI Provider Flexibility**: Remote APIs or Local AI (LM Studio, Ollama)  
✅ **Queue System**: Process multiple generation jobs asynchronously  
✅ **Anti-Duplicate Lock**: Prevent duplicate content generation  
✅ **Force Generate**: Bypass duplicate lock when needed  
✅ **Scheduling**: Schedule article publication with custom intervals  
✅ **Reports Dashboard**: Analytics and statistics  
✅ **SEO Optimized**: Meta descriptions, tags, structured content  
✅ **Security First**: Nonces, sanitization, capability checks  

## Requirements

- PHP 8.0 or higher
- WordPress 5.8 or higher
- WP-Cron enabled (for queue processing)

## Installation

1. Upload the `aseman-robot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure AI settings in **ربات آسمان > Settings**
4. Start generating content in **ربات آسمان > Generate Content**

## Configuration

### AI Provider Settings

**Remote API (OpenAI-compatible)**:
- API Base URL (e.g., `https://api.openai.com/v1`)
- API Token
- Model Name (e.g., `gpt-4`, `gpt-3.5-turbo`)

**Local AI (LM Studio / Ollama)**:
- Local Base URL (e.g., `http://localhost:1234/v1` or `http://localhost:11434`)
- Endpoint Type (OpenAI-Compatible or Ollama Native)
- Model Name

### Duplicate Lock Settings

- **Lock Window**: Time window to check for duplicates (default: 168 hours / 7 days)
- **Lock Scope**: 
  - Topic Only
  - Topic + Language
  - Topic + Language + Category
  - Topic + Keywords + Language + Category (Strict - default)
- **Allow Force Generate**: Enable bypass option for authorized users
- **Strict Lock**: Include failed jobs in duplicate check

## Usage

1. **Generate Content**: Enter topic, keywords, category, and publish settings
2. **Queue Manager**: Monitor job progress, retry failed jobs, delete jobs
3. **Reports Dashboard**: View statistics and recent activity
4. **Settings**: Configure AI provider, defaults, and duplicate lock

## Capabilities

- `aseman_robot_manage` - Full access (Administrators)
- `aseman_robot_generate` - Generate content (Administrators, Editors)
- `aseman_robot_view_reports` - View reports (Administrators, Editors)
- `aseman_robot_generate_force` - Force generate bypass (Administrators only)

## Database Tables

- `wp_aseman_queue` - Generation jobs queue
- `wp_aseman_logs` - Activity logs

## WP-Cron

The plugin uses WP-Cron to process the queue automatically every 5 minutes. Ensure WP-Cron is enabled and functioning.

## Uninstallation

When uninstalling, the plugin will:
- Drop database tables
- Remove plugin options
- Clear scheduled cron jobs
- Remove capabilities from roles
- Clean up post meta

## Support

For issues and feature requests, please visit:
https://github.com/aseman7soft/wp-ai-soft7/issues

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

Aseman Soft - https://aseman7soft.com
