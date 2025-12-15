<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$queue_table = $wpdb->prefix . 'aseman_queue';
$logs_table = $wpdb->prefix . 'aseman_logs';

$wpdb->query("DROP TABLE IF EXISTS {$queue_table}");
$wpdb->query("DROP TABLE IF EXISTS {$logs_table}");

delete_option('aseman_robot_settings');
delete_option('aseman_robot_prompt_template');

$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_aseman_%'");

wp_clear_scheduled_hook('aseman_robot_process_queue');

$roles = ['administrator', 'editor', 'author'];
$capabilities = [
    'aseman_robot_manage',
    'aseman_robot_generate',
    'aseman_robot_view_reports',
    'aseman_robot_generate_force',
];

foreach ($roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        foreach ($capabilities as $cap) {
            $role->remove_cap($cap);
        }
    }
}
