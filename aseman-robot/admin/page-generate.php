<?php

if (!defined('ABSPATH')) {
    exit;
}

Aseman_Robot_Security::check_capability('aseman_robot_generate');

if (isset($_POST['aseman_robot_generate'])) {
    Aseman_Robot_Security::verify_nonce('aseman_robot_generate');
    
    $topic = Aseman_Robot_Security::sanitize_topic($_POST['topic'] ?? '');
    $keywords = [
        Aseman_Robot_Security::sanitize_topic($_POST['keyword1'] ?? ''),
        Aseman_Robot_Security::sanitize_topic($_POST['keyword2'] ?? ''),
        Aseman_Robot_Security::sanitize_topic($_POST['keyword3'] ?? ''),
    ];
    $keywords = array_filter($keywords);
    
    $category_id = intval($_POST['category_id'] ?? 0);
    $publish_mode = sanitize_text_field($_POST['publish_mode'] ?? 'draft');
    $schedule_datetime = sanitize_text_field($_POST['schedule_datetime'] ?? '');
    $interval_minutes = intval($_POST['interval_minutes'] ?? 10);
    $force_generate = isset($_POST['force_generate']) ? 1 : 0;
    
    if (empty($topic)) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Topic is required', 'aseman-robot') . '</p></div>';
    } else {
        $settings = get_option('aseman_robot_settings', []);
        $site_language = get_locale();
        
        $duplicate_key = Aseman_Robot_Security::generate_duplicate_key(
            $topic,
            $keywords,
            $category_id,
            $site_language,
            $settings['duplicate_lock_scope'] ?? 'topic_keywords_language_category'
        );
        
        if (!$force_generate) {
            $repo = new Aseman_Robot_Queue_Repository();
            $window_hours = intval($settings['duplicate_lock_window_hours'] ?? 168);
            $include_failed = !empty($settings['strict_lock_including_failed']);
            
            $existing_job_id = $repo->check_duplicate($duplicate_key, $window_hours, $include_failed);
            
            if ($existing_job_id) {
                $queue_url = admin_url('admin.php?page=aseman-robot-queue&job_id=' . $existing_job_id);
                echo '<div class="notice notice-warning"><p>';
                echo esc_html__('A similar generation job already exists in the last', 'aseman-robot') . ' ';
                echo esc_html($window_hours) . ' ' . esc_html__('hours.', 'aseman-robot') . ' ';
                echo '<a href="' . esc_url($queue_url) . '">' . esc_html__('View existing job', 'aseman-robot') . '</a>';
                echo '</p></div>';
            } else {
                $job_created = create_queue_job($topic, $keywords, $category_id, $publish_mode, $schedule_datetime, $interval_minutes, $duplicate_key, $force_generate);
            }
        } else {
            $job_created = create_queue_job($topic, $keywords, $category_id, $publish_mode, $schedule_datetime, $interval_minutes, $duplicate_key, $force_generate);
        }
        
        if (!empty($job_created)) {
            $queue_url = admin_url('admin.php?page=aseman-robot-queue');
            echo '<div class="notice notice-success"><p>';
            echo esc_html__('Job added to queue successfully!', 'aseman-robot') . ' ';
            echo '<a href="' . esc_url($queue_url) . '">' . esc_html__('View queue', 'aseman-robot') . '</a>';
            echo '</p></div>';
        }
    }
}

function create_queue_job($topic, $keywords, $category_id, $publish_mode, $schedule_datetime, $interval_minutes, $duplicate_key, $force_generate) {
    $settings = get_option('aseman_robot_settings', []);
    $site_language = get_locale();
    
    $schedule_at = null;
    if ($publish_mode === 'schedule' && !empty($schedule_datetime)) {
        $schedule_at = date('Y-m-d H:i:s', strtotime($schedule_datetime));
    }
    
    $repo = new Aseman_Robot_Queue_Repository();
    $job_id = $repo->create_job([
        'topic' => $topic,
        'keywords_json' => wp_json_encode(array_values($keywords)),
        'category_id' => $category_id ?: null,
        'language' => $site_language,
        'provider_mode' => $settings['provider_mode'] ?? 'remote',
        'schedule_at' => $schedule_at,
        'publish_interval_minutes' => $interval_minutes,
        'duplicate_key' => $duplicate_key,
        'force_generate' => $force_generate,
    ]);
    
    return $job_id;
}

$categories = get_categories(['hide_empty' => false]);
$settings = get_option('aseman_robot_settings', []);
$can_force_generate = current_user_can('aseman_robot_manage') || current_user_can('aseman_robot_generate_force');
$allow_force = !empty($settings['allow_force_generate']);
?>

<div class="wrap aseman-robot-wrap">
    <h1><?php echo esc_html__('Generate Content', 'aseman-robot'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('aseman_robot_generate'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="topic"><?php echo esc_html__('Topic', 'aseman-robot'); ?> *</label>
                </th>
                <td>
                    <input type="text" name="topic" id="topic" class="regular-text" required>
                    <p class="description"><?php echo esc_html__('Main topic for the articles', 'aseman-robot'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Keywords', 'aseman-robot'); ?>
                </th>
                <td>
                    <input type="text" name="keyword1" id="keyword1" placeholder="<?php echo esc_attr__('Keyword 1', 'aseman-robot'); ?>" class="regular-text" style="margin-bottom: 5px;"><br>
                    <input type="text" name="keyword2" id="keyword2" placeholder="<?php echo esc_attr__('Keyword 2', 'aseman-robot'); ?>" class="regular-text" style="margin-bottom: 5px;"><br>
                    <input type="text" name="keyword3" id="keyword3" placeholder="<?php echo esc_attr__('Keyword 3', 'aseman-robot'); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="category_id"><?php echo esc_html__('Category', 'aseman-robot'); ?></label>
                </th>
                <td>
                    <select name="category_id" id="category_id">
                        <option value=""><?php echo esc_html__('Select Category', 'aseman-robot'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Number of Articles', 'aseman-robot'); ?>
                </th>
                <td>
                    <strong>3</strong> <?php echo esc_html__('(Fixed)', 'aseman-robot'); ?>
                    <p class="description"><?php echo esc_html__('Each job generates exactly 3 unique articles', 'aseman-robot'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Tone', 'aseman-robot'); ?>
                </th>
                <td>
                    <strong><?php echo esc_html__('Formal/Official', 'aseman-robot'); ?></strong> <?php echo esc_html__('(Fixed)', 'aseman-robot'); ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="publish_mode"><?php echo esc_html__('Publish Mode', 'aseman-robot'); ?></label>
                </th>
                <td>
                    <select name="publish_mode" id="publish_mode">
                        <option value="draft"><?php echo esc_html__('Draft Now', 'aseman-robot'); ?></option>
                        <option value="publish"><?php echo esc_html__('Publish Now', 'aseman-robot'); ?></option>
                        <option value="schedule"><?php echo esc_html__('Schedule', 'aseman-robot'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr id="schedule-row" style="display: none;">
                <th scope="row">
                    <label for="schedule_datetime"><?php echo esc_html__('Schedule Start', 'aseman-robot'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" name="schedule_datetime" id="schedule_datetime">
                    <p class="description"><?php echo esc_html__('Start date/time for the first article', 'aseman-robot'); ?></p>
                </td>
            </tr>
            
            <tr id="interval-row" style="display: none;">
                <th scope="row">
                    <label for="interval_minutes"><?php echo esc_html__('Interval (minutes)', 'aseman-robot'); ?></label>
                </th>
                <td>
                    <input type="number" name="interval_minutes" id="interval_minutes" 
                           value="<?php echo esc_attr($settings['default_schedule_spacing'] ?? 10); ?>" 
                           min="1" max="1440" class="small-text">
                    <p class="description"><?php echo esc_html__('Time between each article publication', 'aseman-robot'); ?></p>
                </td>
            </tr>
            
            <?php if ($can_force_generate && $allow_force): ?>
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Advanced Options', 'aseman-robot'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="force_generate" value="1">
                        <?php echo esc_html__('Force Generate (Ignore Duplicate Lock)', 'aseman-robot'); ?>
                    </label>
                    <p class="description"><?php echo esc_html__('Bypass duplicate detection and create job anyway', 'aseman-robot'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        
        <p class="submit">
            <input type="submit" name="aseman_robot_generate" class="button button-primary" 
                   value="<?php echo esc_attr__('Add to Queue', 'aseman-robot'); ?>">
        </p>
    </form>
    
    <hr>
    
    <h2><?php echo esc_html__('How It Works', 'aseman-robot'); ?></h2>
    <ol>
        <li><?php echo esc_html__('Submit this form to add a job to the queue', 'aseman-robot'); ?></li>
        <li><?php echo esc_html__('The job will be processed automatically by WP-Cron', 'aseman-robot'); ?></li>
        <li><?php echo esc_html__('3 unique articles will be generated using AI', 'aseman-robot'); ?></li>
        <li><?php echo esc_html__('Articles will be created as WordPress posts', 'aseman-robot'); ?></li>
        <li><?php echo esc_html__('Check Queue Manager for progress', 'aseman-robot'); ?></li>
    </ol>
</div>

<script>
jQuery(document).ready(function($) {
    $('#publish_mode').on('change', function() {
        if ($(this).val() === 'schedule') {
            $('#schedule-row, #interval-row').show();
        } else {
            $('#schedule-row, #interval-row').hide();
        }
    });
});
</script>
