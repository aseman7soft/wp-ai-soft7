<?php

if (!defined('ABSPATH')) {
    exit;
}

Aseman_Robot_Security::check_capability('aseman_robot_view_reports');

$stats_helper = new Aseman_Robot_Reports_Stats();
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '7';

switch ($period) {
    case '1':
        $stats = $stats_helper->get_today_stats();
        $period_label = __('Today', 'aseman-robot');
        break;
    case '30':
        $stats = $stats_helper->get_month_stats();
        $period_label = __('Last 30 Days', 'aseman-robot');
        break;
    case '7':
    default:
        $stats = $stats_helper->get_week_stats();
        $period_label = __('Last 7 Days', 'aseman-robot');
        break;
}

$recent_activity = $stats_helper->get_recent_activity(15);
?>

<div class="wrap aseman-robot-wrap">
    <h1><?php echo esc_html__('Reports Dashboard', 'aseman-robot'); ?></h1>
    
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="aseman-robot-reports">
            <label><?php echo esc_html__('Period:', 'aseman-robot'); ?></label>
            <select name="period" onchange="this.form.submit()">
                <option value="1" <?php selected($period, '1'); ?>><?php echo esc_html__('Today', 'aseman-robot'); ?></option>
                <option value="7" <?php selected($period, '7'); ?>><?php echo esc_html__('Last 7 Days', 'aseman-robot'); ?></option>
                <option value="30" <?php selected($period, '30'); ?>><?php echo esc_html__('Last 30 Days', 'aseman-robot'); ?></option>
            </select>
        </form>
    </div>
    
    <h2><?php echo esc_html($period_label); ?></h2>
    
    <div class="aseman-stats-grid">
        <div class="aseman-stat-card">
            <div class="stat-icon dashicons dashicons-list-view"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['total_jobs']); ?></div>
                <div class="stat-label"><?php echo esc_html__('Total Jobs', 'aseman-robot'); ?></div>
            </div>
        </div>
        
        <div class="aseman-stat-card success">
            <div class="stat-icon dashicons dashicons-yes-alt"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['done_jobs']); ?></div>
                <div class="stat-label"><?php echo esc_html__('Completed Jobs', 'aseman-robot'); ?></div>
            </div>
        </div>
        
        <div class="aseman-stat-card warning">
            <div class="stat-icon dashicons dashicons-clock"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['pending_jobs']); ?></div>
                <div class="stat-label"><?php echo esc_html__('Pending Jobs', 'aseman-robot'); ?></div>
            </div>
        </div>
        
        <div class="aseman-stat-card error">
            <div class="stat-icon dashicons dashicons-dismiss"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['failed_jobs']); ?></div>
                <div class="stat-label"><?php echo esc_html__('Failed Jobs', 'aseman-robot'); ?></div>
            </div>
        </div>
        
        <div class="aseman-stat-card info">
            <div class="stat-icon dashicons dashicons-admin-post"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['total_posts']); ?></div>
                <div class="stat-label"><?php echo esc_html__('Posts Generated', 'aseman-robot'); ?></div>
            </div>
        </div>
        
        <div class="aseman-stat-card">
            <div class="stat-icon dashicons dashicons-visibility"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['posts_by_status']['publish'] ?? 0); ?></div>
                <div class="stat-label"><?php echo esc_html__('Published', 'aseman-robot'); ?></div>
            </div>
        </div>
        
        <div class="aseman-stat-card">
            <div class="stat-icon dashicons dashicons-edit"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['posts_by_status']['draft'] ?? 0); ?></div>
                <div class="stat-label"><?php echo esc_html__('Drafts', 'aseman-robot'); ?></div>
            </div>
        </div>
        
        <div class="aseman-stat-card">
            <div class="stat-icon dashicons dashicons-calendar-alt"></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['posts_by_status']['future'] ?? 0); ?></div>
                <div class="stat-label"><?php echo esc_html__('Scheduled', 'aseman-robot'); ?></div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($stats['top_errors'])): ?>
        <h2><?php echo esc_html__('Top Error Messages', 'aseman-robot'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Error Message', 'aseman-robot'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Count', 'aseman-robot'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['top_errors'] as $error): ?>
                    <tr>
                        <td><?php echo esc_html($error->error_message); ?></td>
                        <td><?php echo esc_html($error->count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <h2><?php echo esc_html__('Recent Activity', 'aseman-robot'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Time', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Topic', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Status', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Language', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Provider', 'aseman-robot'); ?></th>
                <th><?php echo esc_html__('Posts', 'aseman-robot'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_activity)): ?>
                <tr>
                    <td colspan="6"><?php echo esc_html__('No recent activity', 'aseman-robot'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($recent_activity as $job): ?>
                    <?php $post_ids = json_decode($job->result_post_ids_json, true) ?: []; ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($job->updated_at))); ?></td>
                        <td>
                            <strong><?php echo esc_html(mb_substr($job->topic, 0, 40)); ?></strong>
                            <?php if ($job->error_message): ?>
                                <br><small class="error-message"><?php echo esc_html(mb_substr($job->error_message, 0, 60)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($job->status); ?>">
                                <?php echo esc_html(ucfirst($job->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($job->language); ?></td>
                        <td><?php echo esc_html(ucfirst($job->provider_mode)); ?></td>
                        <td>
                            <?php if (!empty($post_ids)): ?>
                                <?php foreach ($post_ids as $post_id): ?>
                                    <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>" target="_blank">
                                        #<?php echo esc_html($post_id); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.aseman-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.aseman-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.aseman-stat-card.success { border-left: 4px solid #00a32a; }
.aseman-stat-card.warning { border-left: 4px solid #dba617; }
.aseman-stat-card.error { border-left: 4px solid #d63638; }
.aseman-stat-card.info { border-left: 4px solid #72aee6; }
.stat-icon {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #646970;
}
.success .stat-icon { color: #00a32a; }
.warning .stat-icon { color: #dba617; }
.error .stat-icon { color: #d63638; }
.info .stat-icon { color: #72aee6; }
.stat-content {
    flex: 1;
}
.stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 5px;
}
.stat-label {
    font-size: 14px;
    color: #646970;
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
