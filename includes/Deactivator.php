<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Deactivator {
    
    public static function deactivate() {
        self::clear_scheduled_cron();
        flush_rewrite_rules();
    }
    
    private static function clear_scheduled_cron() {
        $timestamp = wp_next_scheduled('aseman_robot_process_queue');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aseman_robot_process_queue');
        }
        wp_clear_scheduled_hook('aseman_robot_process_queue');
    }
}
