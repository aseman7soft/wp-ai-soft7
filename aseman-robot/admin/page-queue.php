<?php

if (!defined('ABSPATH')) {
    exit;
}

Aseman_Robot_Security::check_capability('aseman_robot_manage');

$repo = new Aseman_Robot_Queue_Repository();

$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$language_filter = isset($_GET['language']) ? sanitize_text_field($_GET['language']) : '';
$page_num = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

$filters = [];
if ($status_filter) {
    $filters['status'] = $status_filter;
}
if ($language_filter) {
    $filters['language'] = $language_filter;
}

$total_jobs = $repo->count_jobs($filters);
$total_pages = ceil($total_jobs / $per_page);
$offset = ($page_num - 1) * $per_page;

$jobs = $repo->get_jobs($filters, $per_page, $offset);

$highlight_job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
?>

<div class="wrap aseman-robot-wrap">
    <h1><?php echo esc_html__('Queue Manager', 'aseman-robot'); ?></h1>
    
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="aseman-robot-queue">
            
            <select name="status">
                <option value=""><?php echo esc_html__('All Statuses', 'aseman-robot'); ?></option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php echo esc_html__('Pending', 'aseman-robot'); ?></option>
                <option value="processing" <?php selected($status_filter, 'processing'); ?>><?php echo esc_html__('Processing', 'aseman-robot'); ?></option>
                <option value="done" <?php selected($status_filter, 'done'); ?>><?php echo esc_html__('Done', 'aseman-robot'); ?></option>
                <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php echo esc_html__('Failed', 'aseman-robot'); ?></option>
                <option value="scheduled" <?php selected($status_filter, 'scheduled'); ?>><?php echo esc_html__('Scheduled', 'aseman-robot'); ?></option>
            </select>
            
            <select name="language">
                <option value=""><?php echo esc_html__('All Languages', 'aseman-robot'); ?></option>
                <option value="fa_IR" <?php selected($language_filter, 'fa_IR'); ?>>Persian (fa_IR)</option>
                <option value="en_US" <?php selected($language_filter, 'en_US'); ?>>English (en_US)</option>
                <option value="ar" <?php selected($language_filter, 'ar'); ?>>Arabic (ar)</option>
            </select>
            
            <input type="submit" class="button" value="<?php echo esc_attr__('Filter', 'aseman-robot'); ?>">
        </form>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('ID', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Topic', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Status', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Progress', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Language', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Provider', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Force', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Created', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Actions', 'aseman-robot'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jobs)): ?>
                <tr>
                    <td colspan="9"><?php echo esc_html__('No jobs found', 'aseman-robot'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <?php 
                    $row_class = ($highlight_job_id === $job->id) ? 'highlight-job' : '';
                    $post_ids = json_decode($job->result_post_ids_json, true) ?: [];
                    $progress = count($post_ids) . '/3';
                    ?>
                    <tr class="<?php echo esc_attr($row_class); ?>">
                        <td><?php echo esc_html($job->id); ?></td>
                        <td>
                            <strong><?php echo esc_html(mb_substr($job->topic, 0, 50)); ?></strong>
                            <?php if (!empty($job->duplicate_key)): ?>
                                <br><small title="<?php echo esc_attr($job->duplicate_key); ?>">
                                    <?php echo esc_html__('Key:', 'aseman-robot'); ?> <?php echo esc_html(substr($job->duplicate_key, 0, 12)); ?>...
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($job->status); ?>">
                                <?php echo esc_html(ucfirst($job->status)); ?>
                            </span>
                            <?php if ($job->status === 'failed' && $job->error_message): ?>
                                <br><small class="error-message" title="<?php echo esc_attr($job->error_message); ?>">
                                    <?php echo esc_html(mb_substr($job->error_message, 0, 50)); ?>...
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($progress); ?>
                            <?php if (!empty($post_ids)): ?>
                                <br>
                                <?php foreach ($post_ids as $post_id): ?>
                                    <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>" target="_blank">
                                        #<?php echo esc_html($post_id); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($job->language); ?></td>
                        <td><?php echo esc_html(ucfirst($job->provider_mode)); ?></td>
                        <td>
                            <?php if ($job->force_generate): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="<?php echo esc_attr__('Force Generate', 'aseman-robot'); ?>"></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-minus" style="color: #ccc;"></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($job->created_at))); ?></td>
                        <td>
                            <?php if ($job->status === 'failed' || $job->status === 'pending'): ?>
                                <button class="button button-small aseman-retry-job" data-job-id="<?php echo esc_attr($job->id); ?>">
                                    <?php echo esc_html__('Retry', 'aseman-robot'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($job->status === 'pending' || $job->status === 'processing'): ?>
                                <button class="button button-small aseman-cancel-job" data-job-id="<?php echo esc_attr($job->id); ?>">
                                    <?php echo esc_html__('Cancel', 'aseman-robot'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if (current_user_can('aseman_robot_generate_force')): ?>
                                <button class="button button-small aseman-clone-force-job" data-job-id="<?php echo esc_attr($job->id); ?>">
                                    <?php echo esc_html__('Clone Force', 'aseman-robot'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button class="button button-small button-link-delete aseman-delete-job" data-job-id="<?php echo esc_attr($job->id); ?>">
                                <?php echo esc_html__('Delete', 'aseman-robot'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $page_links = paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $page_num
                ]);
                
                if ($page_links) {
                    echo '<span class="pagination-links">' . $page_links . '</span>';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div id="aseman-ajax-message" style="display: none; margin-top: 20px;"></div>
</div>

<style>
.highlight-job {
    background-color: #fffbcc !important;
}
.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}
.status-pending { background: #f0f0f1; color: #646970; }
.status-processing { background: #72aee6; color: #fff; }
.status-done { background: #00a32a; color: #fff; }
.status-failed { background: #d63638; color: #fff; }
.status-scheduled { background: #dba617; color: #fff; }
.error-message {
    color: #d63638;
    font-style: italic;
}
</style>
