<?php

if (!defined('ABSPATH')) {
    exit;
}

Aseman_Robot_Security::check_capability('aseman_robot_manage');

if (isset($_POST['aseman_robot_save_settings'])) {
    Aseman_Robot_Security::verify_nonce('aseman_robot_settings');
    
    $settings = [
        'provider_mode' => sanitize_text_field($_POST['provider_mode'] ?? 'remote'),
        'remote_api_base_url' => esc_url_raw($_POST['remote_api_base_url'] ?? ''),
        'remote_api_token' => sanitize_text_field($_POST['remote_api_token'] ?? ''),
        'remote_model_name' => sanitize_text_field($_POST['remote_model_name'] ?? ''),
        'temperature' => floatval($_POST['temperature'] ?? 0.7),
        'max_tokens' => intval($_POST['max_tokens'] ?? 4000),
        'timeout' => intval($_POST['timeout'] ?? 120),
        'local_base_url' => esc_url_raw($_POST['local_base_url'] ?? ''),
        'local_token' => sanitize_text_field($_POST['local_token'] ?? ''),
        'local_model_name' => sanitize_text_field($_POST['local_model_name'] ?? ''),
        'local_endpoint_type' => sanitize_text_field($_POST['local_endpoint_type'] ?? 'openai_compatible'),
        'default_post_status' => sanitize_text_field($_POST['default_post_status'] ?? 'draft'),
        'default_schedule_spacing' => intval($_POST['default_schedule_spacing'] ?? 10),
        'default_min_words' => intval($_POST['default_min_words'] ?? 1000),
        'duplicate_lock_window_hours' => intval($_POST['duplicate_lock_window_hours'] ?? 168),
        'duplicate_lock_scope' => sanitize_text_field($_POST['duplicate_lock_scope'] ?? 'topic_keywords_language_category'),
        'allow_force_generate' => isset($_POST['allow_force_generate']) ? 1 : 0,
        'strict_lock_including_failed' => isset($_POST['strict_lock_including_failed']) ? 1 : 0,
        'jobs_per_run' => intval($_POST['jobs_per_run'] ?? 2),
    ];
    
    update_option('aseman_robot_settings', $settings);
    
    if (!empty($_POST['prompt_template'])) {
        update_option('aseman_robot_prompt_template', wp_kses_post($_POST['prompt_template']));
    }
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'aseman-robot') . '</p></div>';
}

$settings = get_option('aseman_robot_settings', []);
$prompt_template = get_option('aseman_robot_prompt_template', '');

$is_rtl = is_rtl();
$text_align = $is_rtl ? 'right' : 'left';
?>

<div class="wrap aseman-robot-wrap">
    <h1><?php echo esc_html__('Settings', 'aseman-robot'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('aseman_robot_settings'); ?>
        
        <div class="aseman-tabs">
            <div class="aseman-tab-nav">
                <button type="button" class="aseman-tab-btn active" data-tab="ai-config"><?php echo esc_html__('AI Configuration', 'aseman-robot'); ?></button>
                <button type="button" class="aseman-tab-btn" data-tab="prompt-template"><?php echo esc_html__('Prompt Template', 'aseman-robot'); ?></button>
                <button type="button" class="aseman-tab-btn" data-tab="defaults"><?php echo esc_html__('Defaults', 'aseman-robot'); ?></button>
                <button type="button" class="aseman-tab-btn" data-tab="duplicate-lock"><?php echo esc_html__('Duplicate Lock', 'aseman-robot'); ?></button>
            </div>
            
            <div class="aseman-tab-content active" id="ai-config">
                <h2><?php echo esc_html__('AI Provider Configuration', 'aseman-robot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="provider_mode"><?php echo esc_html__('Provider Mode', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <select name="provider_mode" id="provider_mode">
                                <option value="remote" <?php selected($settings['provider_mode'] ?? 'remote', 'remote'); ?>>
                                    <?php echo esc_html__('Remote API (OpenAI-compatible)', 'aseman-robot'); ?>
                                </option>
                                <option value="local" <?php selected($settings['provider_mode'] ?? 'remote', 'local'); ?>>
                                    <?php echo esc_html__('Local AI (LM Studio / Ollama)', 'aseman-robot'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <div id="remote-settings" style="display: none;">
                    <h3><?php echo esc_html__('Remote API Settings', 'aseman-robot'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="remote_api_base_url"><?php echo esc_html__('API Base URL', 'aseman-robot'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="remote_api_base_url" id="remote_api_base_url" 
                                       value="<?php echo esc_attr($settings['remote_api_base_url'] ?? 'https://api.openai.com/v1'); ?>" 
                                       class="regular-text">
                                <p class="description"><?php echo esc_html__('Example: https://api.openai.com/v1', 'aseman-robot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="remote_api_token"><?php echo esc_html__('API Token', 'aseman-robot'); ?></label>
                            </th>
                            <td>
                                <input type="password" name="remote_api_token" id="remote_api_token" 
                                       value="<?php echo esc_attr($settings['remote_api_token'] ?? ''); ?>" 
                                       class="regular-text">
                                <p class="description"><?php echo esc_html__('Your API key (stored securely)', 'aseman-robot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="remote_model_name"><?php echo esc_html__('Model Name', 'aseman-robot'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="remote_model_name" id="remote_model_name" 
                                       value="<?php echo esc_attr($settings['remote_model_name'] ?? 'gpt-4'); ?>" 
                                       class="regular-text">
                                <p class="description"><?php echo esc_html__('Example: gpt-4, gpt-3.5-turbo', 'aseman-robot'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="local-settings" style="display: none;">
                    <h3><?php echo esc_html__('Local AI Settings', 'aseman-robot'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="local_base_url"><?php echo esc_html__('Local Base URL', 'aseman-robot'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="local_base_url" id="local_base_url" 
                                       value="<?php echo esc_attr($settings['local_base_url'] ?? 'http://localhost:1234/v1'); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php echo esc_html__('LM Studio: http://localhost:1234/v1', 'aseman-robot'); ?><br>
                                    <?php echo esc_html__('Ollama: http://localhost:11434', 'aseman-robot'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="local_endpoint_type"><?php echo esc_html__('Endpoint Type', 'aseman-robot'); ?></label>
                            </th>
                            <td>
                                <select name="local_endpoint_type" id="local_endpoint_type">
                                    <option value="openai_compatible" <?php selected($settings['local_endpoint_type'] ?? 'openai_compatible', 'openai_compatible'); ?>>
                                        <?php echo esc_html__('OpenAI-Compatible Chat Completions', 'aseman-robot'); ?>
                                    </option>
                                    <option value="ollama_native" <?php selected($settings['local_endpoint_type'] ?? 'openai_compatible', 'ollama_native'); ?>>
                                        <?php echo esc_html__('Ollama Native', 'aseman-robot'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="local_token"><?php echo esc_html__('Token (Optional)', 'aseman-robot'); ?></label>
                            </th>
                            <td>
                                <input type="password" name="local_token" id="local_token" 
                                       value="<?php echo esc_attr($settings['local_token'] ?? ''); ?>" 
                                       class="regular-text">
                                <p class="description"><?php echo esc_html__('Leave empty if not required', 'aseman-robot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="local_model_name"><?php echo esc_html__('Model Name', 'aseman-robot'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="local_model_name" id="local_model_name" 
                                       value="<?php echo esc_attr($settings['local_model_name'] ?? 'local-model'); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <h3><?php echo esc_html__('Generation Parameters', 'aseman-robot'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="temperature"><?php echo esc_html__('Temperature', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="temperature" id="temperature" 
                                   value="<?php echo esc_attr($settings['temperature'] ?? 0.7); ?>" 
                                   min="0" max="1.2" step="0.1" class="small-text">
                            <p class="description"><?php echo esc_html__('0.0 - 1.2 (Higher = more creative)', 'aseman-robot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_tokens"><?php echo esc_html__('Max Tokens', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="max_tokens" id="max_tokens" 
                                   value="<?php echo esc_attr($settings['max_tokens'] ?? 4000); ?>" 
                                   min="500" max="32000" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="timeout"><?php echo esc_html__('Timeout (seconds)', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="timeout" id="timeout" 
                                   value="<?php echo esc_attr($settings['timeout'] ?? 120); ?>" 
                                   min="30" max="600" class="small-text">
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" id="test-connection-btn" class="button button-secondary">
                        <?php echo esc_html__('Test Connection', 'aseman-robot'); ?>
                    </button>
                    <span id="test-connection-result"></span>
                </p>
            </div>
            
            <div class="aseman-tab-content" id="prompt-template">
                <h2><?php echo esc_html__('Prompt Template', 'aseman-robot'); ?></h2>
                <p><?php echo esc_html__('Use placeholders: {topic}, {keyword1}, {keyword2}, {keyword3}, {category}, {language}, {tone}, {min_words}', 'aseman-robot'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="prompt_template"><?php echo esc_html__('Template', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <textarea name="prompt_template" id="prompt_template" rows="20" class="large-text code"><?php echo esc_textarea($prompt_template); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="aseman-tab-content" id="defaults">
                <h2><?php echo esc_html__('Default Settings', 'aseman-robot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_post_status"><?php echo esc_html__('Default Post Status', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <select name="default_post_status" id="default_post_status">
                                <option value="draft" <?php selected($settings['default_post_status'] ?? 'draft', 'draft'); ?>>
                                    <?php echo esc_html__('Draft', 'aseman-robot'); ?>
                                </option>
                                <option value="publish" <?php selected($settings['default_post_status'] ?? 'draft', 'publish'); ?>>
                                    <?php echo esc_html__('Publish', 'aseman-robot'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_schedule_spacing"><?php echo esc_html__('Schedule Spacing (minutes)', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="default_schedule_spacing" id="default_schedule_spacing" 
                                   value="<?php echo esc_attr($settings['default_schedule_spacing'] ?? 10); ?>" 
                                   min="1" max="1440" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_min_words"><?php echo esc_html__('Minimum Words', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="default_min_words" id="default_min_words" 
                                   value="<?php echo esc_attr($settings['default_min_words'] ?? 1000); ?>" 
                                   min="300" max="5000" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="jobs_per_run"><?php echo esc_html__('Jobs Per Cron Run', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="jobs_per_run" id="jobs_per_run" 
                                   value="<?php echo esc_attr($settings['jobs_per_run'] ?? 2); ?>" 
                                   min="1" max="10" class="small-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="aseman-tab-content" id="duplicate-lock">
                <h2><?php echo esc_html__('Anti-Duplicate Settings', 'aseman-robot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="duplicate_lock_window_hours"><?php echo esc_html__('Lock Window (hours)', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="duplicate_lock_window_hours" id="duplicate_lock_window_hours" 
                                   value="<?php echo esc_attr($settings['duplicate_lock_window_hours'] ?? 168); ?>" 
                                   min="1" max="8760" class="small-text">
                            <p class="description"><?php echo esc_html__('168 hours = 7 days', 'aseman-robot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="duplicate_lock_scope"><?php echo esc_html__('Lock Scope', 'aseman-robot'); ?></label>
                        </th>
                        <td>
                            <select name="duplicate_lock_scope" id="duplicate_lock_scope">
                                <option value="topic_only" <?php selected($settings['duplicate_lock_scope'] ?? 'topic_keywords_language_category', 'topic_only'); ?>>
                                    <?php echo esc_html__('Topic Only', 'aseman-robot'); ?>
                                </option>
                                <option value="topic_language" <?php selected($settings['duplicate_lock_scope'] ?? 'topic_keywords_language_category', 'topic_language'); ?>>
                                    <?php echo esc_html__('Topic + Language', 'aseman-robot'); ?>
                                </option>
                                <option value="topic_language_category" <?php selected($settings['duplicate_lock_scope'] ?? 'topic_keywords_language_category', 'topic_language_category'); ?>>
                                    <?php echo esc_html__('Topic + Language + Category', 'aseman-robot'); ?>
                                </option>
                                <option value="topic_keywords_language_category" <?php selected($settings['duplicate_lock_scope'] ?? 'topic_keywords_language_category', 'topic_keywords_language_category'); ?>>
                                    <?php echo esc_html__('Topic + Keywords + Language + Category (Strict)', 'aseman-robot'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php echo esc_html__('Options', 'aseman-robot'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="allow_force_generate" value="1" 
                                       <?php checked($settings['allow_force_generate'] ?? true); ?>>
                                <?php echo esc_html__('Allow Force Generate (Bypass Lock)', 'aseman-robot'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="strict_lock_including_failed" value="1" 
                                       <?php checked($settings['strict_lock_including_failed'] ?? false); ?>>
                                <?php echo esc_html__('Strict Lock (Include Failed Jobs)', 'aseman-robot'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="aseman_robot_save_settings" class="button button-primary" 
                   value="<?php echo esc_attr__('Save Settings', 'aseman-robot'); ?>">
        </p>
    </form>
</div>
