<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Reports_Stats {
    
    public function get_stats($days = 7) {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'aseman_queue';
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $total_jobs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$queue_table} WHERE created_at >= %s",
            $date_from
        ));
        
        $done_jobs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$queue_table} WHERE status = 'done' AND created_at >= %s",
            $date_from
        ));
        
        $failed_jobs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$queue_table} WHERE status = 'failed' AND created_at >= %s",
            $date_from
        ));
        
        $pending_jobs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$queue_table} WHERE status = 'pending' AND created_at >= %s",
            $date_from
        ));
        
        $total_posts = $this->count_generated_posts($date_from);
        
        $posts_by_status = $this->count_posts_by_status($date_from);
        
        $top_errors = $this->get_top_errors($date_from, 5);
        
        return [
            'total_jobs' => (int) $total_jobs,
            'done_jobs' => (int) $done_jobs,
            'failed_jobs' => (int) $failed_jobs,
            'pending_jobs' => (int) $pending_jobs,
            'total_posts' => (int) $total_posts,
            'posts_by_status' => $posts_by_status,
            'top_errors' => $top_errors,
        ];
    }
    
    public function get_today_stats() {
        return $this->get_stats(1);
    }
    
    public function get_week_stats() {
        return $this->get_stats(7);
    }
    
    public function get_month_stats() {
        return $this->get_stats(30);
    }
    
    private function count_generated_posts($date_from) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_aseman_ai_generated'
             AND pm.meta_value = 'yes'
             AND p.post_date >= %s",
            $date_from
        );
        
        return $wpdb->get_var($query);
    }
    
    private function count_posts_by_status($date_from) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT p.post_status, COUNT(*) as count
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_aseman_ai_generated'
             AND pm.meta_value = 'yes'
             AND p.post_date >= %s
             GROUP BY p.post_status",
            $date_from
        );
        
        $results = $wpdb->get_results($query);
        
        $stats = [
            'publish' => 0,
            'draft' => 0,
            'future' => 0,
        ];
        
        foreach ($results as $row) {
            $stats[$row->post_status] = (int) $row->count;
        }
        
        return $stats;
    }
    
    private function get_top_errors($date_from, $limit = 5) {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'aseman_queue';
        
        $query = $wpdb->prepare(
            "SELECT error_message, COUNT(*) as count
             FROM {$queue_table}
             WHERE status = 'failed'
             AND error_message IS NOT NULL
             AND created_at >= %s
             GROUP BY error_message
             ORDER BY count DESC
             LIMIT %d",
            $date_from,
            $limit
        );
        
        return $wpdb->get_results($query);
    }
    
    public function get_recent_activity($limit = 20) {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'aseman_queue';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$queue_table} ORDER BY updated_at DESC LIMIT %d",
            $limit
        ));
    }
}
