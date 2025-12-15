<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Post_Creator {
    
    public function create_post($article, $job, $settings, $post_date = null) {
        $post_status = $this->determine_post_status($settings, $post_date);
        
        $post_data = [
            'post_title' => $article['title'],
            'post_content' => $article['content_html'],
            'post_status' => $post_status,
            'post_author' => get_current_user_id() ?: 1,
            'post_category' => $job->category_id ? [$job->category_id] : [],
            'tags_input' => $article['tags'],
        ];
        
        if ($post_date && $post_status === 'future') {
            $post_data['post_date'] = $post_date;
            $post_data['post_date_gmt'] = get_gmt_from_date($post_date);
        }
        
        if (!empty($article['slug'])) {
            $post_data['post_name'] = $article['slug'];
        }
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create post: ' . $post_id->get_error_message());
        }
        
        $keywords = json_decode($job->keywords_json, true) ?: [];
        
        update_post_meta($post_id, '_aseman_ai_topic', $job->topic);
        update_post_meta($post_id, '_aseman_ai_keywords', wp_json_encode($keywords));
        update_post_meta($post_id, '_aseman_ai_provider', $job->provider_mode);
        update_post_meta($post_id, '_aseman_ai_model', $this->get_model_name($settings, $job->provider_mode));
        update_post_meta($post_id, '_aseman_ai_job_id', $job->id);
        update_post_meta($post_id, '_aseman_ai_generated', 'yes');
        update_post_meta($post_id, '_aseman_duplicate_key', $job->duplicate_key);
        update_post_meta($post_id, '_aseman_force_generate', $job->force_generate ? 'yes' : 'no');
        
        if (!empty($article['meta_description'])) {
            update_post_meta($post_id, '_aseman_ai_meta_description', $article['meta_description']);
            
            $this->inject_seo_meta($post_id, $article['meta_description']);
        }
        
        return $post_id;
    }
    
    private function determine_post_status($settings, $post_date) {
        $default_status = $settings['default_post_status'] ?? 'draft';
        
        if ($post_date) {
            return 'future';
        }
        
        if ($default_status === 'publish') {
            return 'publish';
        }
        
        return 'draft';
    }
    
    private function get_model_name($settings, $provider_mode) {
        if ($provider_mode === 'local') {
            return $settings['local_model_name'] ?? 'local-model';
        }
        return $settings['remote_model_name'] ?? 'gpt-4';
    }
    
    private function inject_seo_meta($post_id, $meta_description) {
        if (defined('WPSEO_VERSION')) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
        }
        
        if (defined('RANK_MATH_VERSION')) {
            update_post_meta($post_id, 'rank_math_description', $meta_description);
        }
    }
}
