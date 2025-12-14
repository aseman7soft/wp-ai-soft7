# Aseman Robot Plugin - Technical Documentation

## Overview

Aseman Robot (ربات آسمان) is a production-ready WordPress plugin that leverages AI to generate high-quality, SEO-optimized content. The plugin supports both remote OpenAI-compatible APIs and local AI solutions like LM Studio and Ollama.

## Architecture

### Core Components

1. **Main Plugin Class** (`aseman-robot.php`)
   - Singleton pattern implementation
   - Hooks registration
   - AJAX handlers
   - Custom cron schedule registration

2. **Activation/Deactivation** (`includes/Activator.php`, `includes/Deactivator.php`)
   - Database table creation
   - Capability registration
   - WP-Cron scheduling
   - Default settings initialization

3. **AI Integration** (`includes/AI/`)
   - `Client.php`: Main AI client that routes to appropriate provider
   - `Providers/OpenAICompatible.php`: Handles OpenAI-compatible APIs (remote and LM Studio)
   - `Providers/OllamaNative.php`: Handles Ollama native API

4. **Queue System** (`includes/Queue/`)
   - `QueueRepository.php`: Database operations for queue management
   - `Processor.php`: Asynchronous job processing via WP-Cron

5. **Content Management** (`includes/Posts/`)
   - `PostCreator.php`: WordPress post creation with SEO meta injection

6. **Utilities**
   - `Prompt/Template.php`: Prompt template management
   - `Reports/Stats.php`: Analytics and statistics
   - `Helpers/Security.php`: Security utilities (sanitization, nonce verification, duplicate key generation)

### Database Schema

#### wp_aseman_queue
```sql
- id (bigint, PK, auto_increment)
- created_at (datetime)
- updated_at (datetime)
- status (varchar: pending, processing, done, failed, scheduled)
- topic (text)
- keywords_json (text)
- category_id (bigint, nullable)
- language (varchar)
- provider_mode (varchar: remote, local)
- schedule_at (datetime, nullable)
- publish_interval_minutes (int)
- result_post_ids_json (text, nullable)
- error_message (text, nullable)
- attempts (int, default 0)
- max_attempts (int, default 3)
- locked_at (datetime, nullable)
- duplicate_key (varchar(64), indexed)
- force_generate (tinyint)
```

#### wp_aseman_logs
```sql
- id (bigint, PK, auto_increment)
- created_at (datetime)
- job_id (bigint, nullable)
- level (varchar: info, warning, error, success)
- message (text)
- context (text, JSON)
```

## Features

### 1. Multi-Language Support
- Auto-detection based on WordPress site language
- Supported languages: Persian (fa_IR), English (en_US), Arabic (ar)
- RTL/LTR layout switching
- Translation-ready with .pot file

### 2. AI Provider Flexibility

**Remote API (OpenAI-compatible)**
- Configurable base URL
- API token management (masked in UI)
- Model selection
- Parameters: temperature, max_tokens, timeout

**Local AI (LM Studio / Ollama)**
- Support for LM Studio (OpenAI-compatible endpoint)
- Support for Ollama (both native and OpenAI-compatible modes)
- No API token required for most local setups
- Custom model names

### 3. Anti-Duplicate Lock System

**Purpose**: Prevent generating duplicate content for the same topic/keywords combination

**Features**:
- Configurable lock window (default: 168 hours / 7 days)
- Multiple lock scope options:
  - Topic only
  - Topic + Language
  - Topic + Language + Category
  - Topic + Keywords + Language + Category (strict)
- Force generate capability to bypass lock
- Normalized comparison (case-insensitive, whitespace-normalized)
- SHA-256 hash key generation

**Implementation**:
```php
// Normalized comparison
$normalized_topic = mb_strtolower(trim($topic), 'UTF-8');
$normalized_keywords = array_map('normalize', $keywords);
sort($normalized_keywords);

// Generate hash
$key = hash('sha256', implode('||', $key_parts));
```

### 4. Queue System

**Processing Flow**:
1. User submits generation request
2. Job added to queue with duplicate check
3. WP-Cron processes queue every 5 minutes
4. Processor picks N jobs (configurable, default: 2)
5. Job locked to prevent double processing
6. AI generates 3 articles
7. Articles created as WordPress posts
8. Job marked as done/failed

**Job Statuses**:
- `pending`: Waiting to be processed
- `processing`: Currently being processed
- `done`: Successfully completed
- `failed`: Failed after max attempts
- `scheduled`: Scheduled for future publication

**Retry Logic**:
- Automatic retry up to max_attempts (default: 3)
- Exponential backoff via attempt counter
- Manual retry available in Queue Manager

### 5. Content Generation

**Prompt Template**:
- Customizable in Settings
- Placeholders: {topic}, {keyword1}, {keyword2}, {keyword3}, {category}, {language}, {tone}, {min_words}
- Default template generates SEO-optimized formal articles

**Article Structure**:
- Unique title per article
- 5-8 H2 sections
- H3 subsections where appropriate
- Conclusion with practical takeaways
- FAQ section (3-6 questions)
- Meta description (150-160 chars)
- URL slug suggestion
- 5-10 tags

**Output Format** (JSON):
```json
{
  "articles": [
    {
      "title": "Article Title",
      "slug": "article-slug",
      "meta_description": "Brief description",
      "tags": ["tag1", "tag2"],
      "content_html": "<h2>Section</h2><p>Content...</p>"
    }
  ]
}
```

### 6. Post Creation & SEO

**WordPress Integration**:
- Creates posts with wp_insert_post
- Supports draft, publish, and scheduled statuses
- Category assignment
- Tag assignment
- Author assignment (current user or default)

**Post Meta**:
- `_aseman_ai_topic`: Original topic
- `_aseman_ai_keywords`: Keywords (JSON)
- `_aseman_ai_provider`: Provider mode used
- `_aseman_ai_model`: Model name
- `_aseman_ai_job_id`: Queue job ID
- `_aseman_ai_generated`: Flag (yes/no)
- `_aseman_ai_meta_description`: Meta description
- `_aseman_duplicate_key`: Duplicate key
- `_aseman_force_generate`: Force flag (yes/no)

**SEO Plugin Integration**:
- Yoast SEO: `_yoast_wpseo_metadesc`
- Rank Math: `rank_math_description`

### 7. Reports Dashboard

**Statistics Displayed**:
- Total jobs (today / 7 days / 30 days)
- Completed jobs
- Pending jobs
- Failed jobs
- Total posts generated
- Posts by status (published, draft, scheduled)
- Top error messages
- Recent activity

### 8. Security

**Measures Implemented**:
- Nonce verification on all forms and AJAX requests
- Capability checks on all admin pages
- Input sanitization (sanitize_text_field, esc_url_raw, etc.)
- Output escaping (esc_html, esc_attr, esc_url)
- API token masking in UI
- SQL injection prevention (prepared statements)
- XSS prevention (wp_kses_post for HTML content)

**Capabilities**:
- `aseman_robot_manage`: Full access (Administrator)
- `aseman_robot_generate`: Generate content (Administrator, Editor)
- `aseman_robot_view_reports`: View reports (Administrator, Editor)
- `aseman_robot_generate_force`: Force generate (Administrator only)

## API Endpoints (AJAX)

1. `aseman_robot_test_connection`: Test AI provider connection
2. `aseman_robot_retry_job`: Retry a failed job
3. `aseman_robot_cancel_job`: Cancel a pending/processing job
4. `aseman_robot_delete_job`: Delete a job
5. `aseman_robot_clone_force_job`: Clone job with force generate

## WP-Cron

**Hook**: `aseman_robot_process_queue`  
**Schedule**: Every 5 minutes  
**Callback**: `Aseman_Robot_Queue_Processor::process_queue()`

## Uninstallation

When uninstalled, the plugin:
1. Drops database tables (wp_aseman_queue, wp_aseman_logs)
2. Deletes plugin options
3. Removes post meta (all _aseman_* keys)
4. Clears scheduled cron hooks
5. Removes capabilities from all roles

## Best Practices Implemented

1. **WordPress Coding Standards**: PSR-style naming, WordPress hooks, proper escaping
2. **Security First**: Multiple layers of security checks
3. **Scalability**: Queue system handles high volume
4. **Error Handling**: Graceful degradation with retry logic
5. **I18n Ready**: All strings translatable
6. **No External Dependencies**: Uses only WordPress core APIs
7. **Database Optimization**: Indexed columns for performance
8. **Clean Code**: Separation of concerns, single responsibility

## Configuration Examples

### Remote OpenAI API
```php
'provider_mode' => 'remote',
'remote_api_base_url' => 'https://api.openai.com/v1',
'remote_api_token' => 'sk-...',
'remote_model_name' => 'gpt-4',
```

### LM Studio
```php
'provider_mode' => 'local',
'local_base_url' => 'http://localhost:1234/v1',
'local_endpoint_type' => 'openai_compatible',
'local_model_name' => 'local-model',
```

### Ollama (OpenAI-compatible)
```php
'provider_mode' => 'local',
'local_base_url' => 'http://localhost:11434/v1',
'local_endpoint_type' => 'openai_compatible',
'local_model_name' => 'llama2',
```

### Ollama (Native)
```php
'provider_mode' => 'local',
'local_base_url' => 'http://localhost:11434',
'local_endpoint_type' => 'ollama_native',
'local_model_name' => 'llama2',
```

## Troubleshooting

### Jobs Not Processing
1. Check WP-Cron is enabled
2. Verify cron schedule: `wp cron event list`
3. Check server PHP error logs
4. Review plugin logs in wp_aseman_logs table

### AI Connection Failed
1. Test connection in Settings page
2. Verify API credentials
3. Check firewall/network access
4. Ensure correct endpoint URL format

### Duplicate Lock Issues
1. Review lock scope setting
2. Check lock window duration
3. Use Force Generate if intentional duplicate needed
4. Check duplicate_key in database

## Development

### Adding Custom AI Provider
1. Create new provider class in `includes/AI/Providers/`
2. Implement `generate()` and `test_connection()` methods
3. Update `Client.php` to instantiate your provider
4. Add provider option in Settings page

### Extending Prompt Template
1. Add new placeholder in Settings documentation
2. Update `Processor.php` to pass placeholder value
3. Update `Template.php` if custom logic needed

### Custom Post Processing
1. Hook into WordPress `wp_insert_post` action
2. Filter `_aseman_ai_generated` post meta to identify plugin-generated posts
3. Add custom post meta or modifications as needed

## Performance Considerations

- Queue processing limited to N jobs per run (configurable)
- Database queries use indexes for efficiency
- AJAX requests use nonces for security without performance impact
- WP-Cron runs in background, doesn't block user requests
- Large HTML content sanitized with wp_kses_post (safe but thorough)

## License

GPL v2 or later - Compatible with WordPress core
