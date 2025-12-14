<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Queue_Processor {
    
    public static function process_queue() {
        $settings = get_option('aseman_robot_settings', []);
        $jobs_per_run = intval($settings['jobs_per_run'] ?? 2);
        
        $repo = new Aseman_Robot_Queue_Repository();
        $jobs = $repo->get_pending_jobs($jobs_per_run);
        
        foreach ($jobs as $job) {
            self::process_job($job);
        }
    }
    
    private static function process_job($job) {
        $repo = new Aseman_Robot_Queue_Repository();
        
        $repo->lock_job($job->id);
        $repo->increment_attempts($job->id);
        
        try {
            $settings = get_option('aseman_robot_settings', []);
            $prompt_template = get_option('aseman_robot_prompt_template', '');
            
            $keywords = json_decode($job->keywords_json, true) ?: [];
            $category = get_category($job->category_id);
            $category_name = $category ? $category->name : 'General';
            
            $template = new Aseman_Robot_Prompt_Template();
            $prompt = $template->build($prompt_template, [
                'topic' => $job->topic,
                'keyword1' => $keywords[0] ?? '',
                'keyword2' => $keywords[1] ?? '',
                'keyword3' => $keywords[2] ?? '',
                'category' => $category_name,
                'language' => $job->language,
                'tone' => 'Formal',
                'min_words' => $settings['default_min_words'] ?? 1000,
            ]);
            
            $client = new Aseman_Robot_AI_Client($settings);
            $response = $client->generate_content($prompt);
            
            $articles_data = self::parse_response($response);
            
            if (empty($articles_data) || count($articles_data) < 3) {
                throw new Exception('Invalid response: Expected 3 articles, got ' . count($articles_data));
            }
            
            $post_creator = new Aseman_Robot_Post_Creator();
            $post_ids = [];
            
            $schedule_at = $job->schedule_at ? strtotime($job->schedule_at) : null;
            $interval = intval($job->publish_interval_minutes) * 60;
            
            foreach ($articles_data as $index => $article) {
                $post_date = null;
                if ($schedule_at) {
                    $post_date = date('Y-m-d H:i:s', $schedule_at + ($index * $interval));
                }
                
                $post_id = $post_creator->create_post(
                    $article,
                    $job,
                    $settings,
                    $post_date
                );
                
                if ($post_id) {
                    $post_ids[] = $post_id;
                }
            }
            
            if (count($post_ids) < 3) {
                throw new Exception('Failed to create all 3 posts. Created: ' . count($post_ids));
            }
            
            $repo->mark_done($job->id, $post_ids);
            
            self::log_success($job->id, "Generated 3 articles successfully", $post_ids);
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            $job_fresh = $repo->get_job($job->id);
            if ($job_fresh && $job_fresh->attempts >= $job_fresh->max_attempts) {
                $repo->mark_failed($job->id, $error_message);
                self::log_error($job->id, "Job failed after {$job_fresh->attempts} attempts: {$error_message}");
            } else {
                $repo->update_job($job->id, [
                    'status' => 'pending',
                    'error_message' => $error_message,
                    'locked_at' => null,
                ]);
                self::log_warning($job->id, "Job attempt failed, will retry: {$error_message}");
            }
        }
    }
    
    private static function parse_response($response) {
        $response = trim($response);
        
        $response = preg_replace('/^```json\s*/i', '', $response);
        $response = preg_replace('/\s*```$/i', '', $response);
        $response = trim($response);
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse JSON response: ' . json_last_error_msg());
        }
        
        if (!isset($data['articles']) || !is_array($data['articles'])) {
            throw new Exception('Invalid response structure: missing articles array');
        }
        
        $articles = [];
        foreach ($data['articles'] as $article) {
            if (empty($article['title']) || empty($article['content_html'])) {
                continue;
            }
            
            $articles[] = [
                'title' => sanitize_text_field($article['title']),
                'slug' => sanitize_title($article['slug'] ?? $article['title']),
                'meta_description' => sanitize_text_field($article['meta_description'] ?? ''),
                'tags' => is_array($article['tags'] ?? null) ? array_map('sanitize_text_field', $article['tags']) : [],
                'content_html' => wp_kses_post($article['content_html']),
            ];
        }
        
        return $articles;
    }
    
    private static function log_success($job_id, $message, $post_ids = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'aseman_logs';
        
        $wpdb->insert($table, [
            'job_id' => $job_id,
            'level' => 'success',
            'message' => $message,
            'context' => wp_json_encode(['post_ids' => $post_ids]),
        ]);
    }
    
    private static function log_error($job_id, $message) {
        global $wpdb;
        $table = $wpdb->prefix . 'aseman_logs';
        
        $wpdb->insert($table, [
            'job_id' => $job_id,
            'level' => 'error',
            'message' => $message,
        ]);
    }
    
    private static function log_warning($job_id, $message) {
        global $wpdb;
        $table = $wpdb->prefix . 'aseman_logs';
        
        $wpdb->insert($table, [
            'job_id' => $job_id,
            'level' => 'warning',
            'message' => $message,
        ]);
    }
}
