<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Queue_Repository {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aseman_queue';
    }
    
    public function create_job($data) {
        global $wpdb;
        
        $defaults = [
            'status' => 'pending',
            'topic' => '',
            'keywords_json' => '[]',
            'category_id' => null,
            'language' => 'en_US',
            'provider_mode' => 'remote',
            'schedule_at' => null,
            'publish_interval_minutes' => 10,
            'attempts' => 0,
            'max_attempts' => 3,
            'duplicate_key' => null,
            'force_generate' => 0,
        ];
        
        $job_data = wp_parse_args($data, $defaults);
        
        $wpdb->insert($this->table_name, $job_data);
        
        return $wpdb->insert_id;
    }
    
    public function get_job($job_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $job_id
        ));
    }
    
    public function get_jobs($filters = [], $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['language'])) {
            $where[] = 'language = %s';
            $params[] = $filters['language'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $params[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query);
    }
    
    public function count_jobs($filters = []) {
        global $wpdb;
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['language'])) {
            $where[] = 'language = %s';
            $params[] = $filters['language'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $params[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return (int) $wpdb->get_var($query);
    }
    
    public function get_pending_jobs($limit = 2) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'pending' 
             AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE))
             AND attempts < max_attempts
             ORDER BY created_at ASC 
             LIMIT %d",
            $limit
        ));
    }
    
    public function lock_job($job_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'locked_at' => current_time('mysql'),
                'status' => 'processing'
            ],
            ['id' => $job_id]
        );
    }
    
    public function update_job($job_id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $job_id]
        );
    }
    
    public function increment_attempts($job_id) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} SET attempts = attempts + 1 WHERE id = %d",
            $job_id
        ));
    }
    
    public function mark_done($job_id, $post_ids) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'done',
                'result_post_ids_json' => wp_json_encode($post_ids),
                'locked_at' => null,
            ],
            ['id' => $job_id]
        );
    }
    
    public function mark_failed($job_id, $error_message) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'failed',
                'error_message' => $error_message,
                'locked_at' => null,
            ],
            ['id' => $job_id]
        );
    }
    
    public function retry_job($job_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'pending',
                'attempts' => 0,
                'error_message' => null,
                'locked_at' => null,
            ],
            ['id' => $job_id]
        );
    }
    
    public function cancel_job($job_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'failed',
                'error_message' => 'Cancelled by user',
                'locked_at' => null,
            ],
            ['id' => $job_id]
        );
    }
    
    public function delete_job($job_id) {
        global $wpdb;
        
        return $wpdb->delete($this->table_name, ['id' => $job_id]);
    }
    
    public function check_duplicate($duplicate_key, $window_hours, $include_failed = false) {
        global $wpdb;
        
        $statuses = ['pending', 'processing', 'done', 'scheduled'];
        if ($include_failed) {
            $statuses[] = 'failed';
        }
        
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        $params = array_merge(
            [$duplicate_key],
            $statuses,
            [$window_hours]
        );
        
        $query = $wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE duplicate_key = %s 
             AND status IN ({$placeholders})
             AND created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
             LIMIT 1",
            $params
        );
        
        return $wpdb->get_var($query);
    }
    
    public function clone_as_force_generate($job_id) {
        global $wpdb;
        
        $job = $this->get_job($job_id);
        if (!$job) {
            return false;
        }
        
        $new_job_data = [
            'topic' => $job->topic,
            'keywords_json' => $job->keywords_json,
            'category_id' => $job->category_id,
            'language' => $job->language,
            'provider_mode' => $job->provider_mode,
            'schedule_at' => $job->schedule_at,
            'publish_interval_minutes' => $job->publish_interval_minutes,
            'duplicate_key' => $job->duplicate_key,
            'force_generate' => 1,
            'status' => 'pending',
        ];
        
        return $this->create_job($new_job_data);
    }
}
