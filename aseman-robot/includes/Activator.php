<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Activator {
    
    public static function activate() {
        global $wpdb;
        
        self::create_tables();
        self::create_capabilities();
        self::schedule_cron();
        self::set_default_options();
        
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $queue_table = $wpdb->prefix . 'aseman_queue';
        $logs_table = $wpdb->prefix . 'aseman_logs';
        
        $queue_sql = "CREATE TABLE IF NOT EXISTS {$queue_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL DEFAULT 'pending',
            topic text NOT NULL,
            keywords_json text,
            category_id bigint(20) unsigned DEFAULT NULL,
            language varchar(10) NOT NULL DEFAULT 'en_US',
            provider_mode varchar(20) NOT NULL DEFAULT 'remote',
            schedule_at datetime DEFAULT NULL,
            publish_interval_minutes int(11) DEFAULT 10,
            result_post_ids_json text,
            error_message text,
            attempts int(11) NOT NULL DEFAULT 0,
            max_attempts int(11) NOT NULL DEFAULT 3,
            locked_at datetime DEFAULT NULL,
            duplicate_key varchar(64) DEFAULT NULL,
            force_generate tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at),
            KEY schedule_at (schedule_at),
            KEY duplicate_key (duplicate_key, created_at, status)
        ) {$charset_collate};";
        
        $logs_sql = "CREATE TABLE IF NOT EXISTS {$logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            job_id bigint(20) unsigned DEFAULT NULL,
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context text,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY level (level),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($queue_sql);
        dbDelta($logs_sql);
    }
    
    private static function create_capabilities() {
        $admin_role = get_role('administrator');
        $editor_role = get_role('editor');
        
        $capabilities = [
            'aseman_robot_manage',
            'aseman_robot_generate',
            'aseman_robot_view_reports',
            'aseman_robot_generate_force',
        ];
        
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        if ($editor_role) {
            $editor_role->add_cap('aseman_robot_generate');
            $editor_role->add_cap('aseman_robot_view_reports');
        }
    }
    
    private static function schedule_cron() {
        if (!wp_next_scheduled('aseman_robot_process_queue')) {
            wp_schedule_event(time(), 'every_five_minutes', 'aseman_robot_process_queue');
        }
    }
    
    private static function set_default_options() {
        $default_settings = [
            'provider_mode' => 'remote',
            'remote_api_base_url' => 'https://api.openai.com/v1',
            'remote_api_token' => '',
            'remote_model_name' => 'gpt-4',
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'timeout' => 120,
            'local_base_url' => 'http://localhost:1234/v1',
            'local_token' => '',
            'local_model_name' => 'local-model',
            'local_endpoint_type' => 'openai_compatible',
            'default_post_status' => 'draft',
            'default_schedule_spacing' => 10,
            'default_min_words' => 1000,
            'duplicate_lock_window_hours' => 168,
            'duplicate_lock_scope' => 'topic_keywords_language_category',
            'allow_force_generate' => true,
            'strict_lock_including_failed' => false,
            'jobs_per_run' => 2,
        ];
        
        if (!get_option('aseman_robot_settings')) {
            update_option('aseman_robot_settings', $default_settings);
        }
        
        $default_prompt_template = self::get_default_prompt_template();
        if (!get_option('aseman_robot_prompt_template')) {
            update_option('aseman_robot_prompt_template', $default_prompt_template);
        }
    }
    
    private static function get_default_prompt_template() {
        return 'You are a professional content writer creating SEO-optimized, formal articles.

Generate exactly 3 unique, high-quality articles about: {topic}

Requirements for EACH article:
- Unique, compelling title (must be different from other articles)
- Minimum {min_words} words
- Language: {language}
- Tone: {tone}
- Category context: {category}
- Naturally incorporate these keywords: {keyword1}, {keyword2}, {keyword3}

Structure for each article:
1. Compelling introduction (2-3 paragraphs)
2. 5-8 H2 sections with substantial content
3. H3 subsections where appropriate
4. Conclusion with practical takeaways
5. FAQ section with 3-6 questions and concise answers

SEO Requirements:
- Meta description (150-160 characters)
- Suggested slug (URL-friendly)
- 5-10 relevant tags
- Natural keyword integration (NO stuffing)
- Clear headings hierarchy

Output MUST be valid JSON:
{
  "articles": [
    {
      "title": "Article Title Here",
      "slug": "article-slug-here",
      "meta_description": "Brief description 150-160 chars",
      "tags": ["tag1", "tag2", "tag3", "tag4", "tag5"],
      "content_html": "<h2>Section Title</h2><p>Content...</p><h2>FAQ</h2><h3>Question 1?</h3><p>Answer...</p>"
    }
  ]
}

CRITICAL: Return ONLY valid JSON. No markdown, no code blocks, no explanations.';
    }
}
