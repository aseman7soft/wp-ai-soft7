<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Security {
    
    public static function sanitize_topic($topic) {
        return sanitize_text_field(trim($topic));
    }
    
    public static function sanitize_keywords($keywords) {
        if (is_array($keywords)) {
            return array_map('sanitize_text_field', array_map('trim', $keywords));
        }
        return [];
    }
    
    public static function normalize_for_duplicate($text) {
        $text = trim($text);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/([^\w\s])\1+/u', '$1', $text);
        return $text;
    }
    
    public static function generate_duplicate_key($topic, $keywords, $category_id, $language, $scope) {
        $normalized_topic = self::normalize_for_duplicate($topic);
        $normalized_keywords = array_map([self::class, 'normalize_for_duplicate'], $keywords);
        sort($normalized_keywords);
        
        $key_parts = [$normalized_topic];
        
        switch ($scope) {
            case 'topic_only':
                break;
            case 'topic_language':
                $key_parts[] = $language;
                break;
            case 'topic_language_category':
                $key_parts[] = $language;
                $key_parts[] = $category_id;
                break;
            case 'topic_keywords_language_category':
            default:
                $key_parts[] = implode('|', $normalized_keywords);
                $key_parts[] = $language;
                $key_parts[] = $category_id;
                break;
        }
        
        return hash('sha256', implode('||', $key_parts));
    }
    
    public static function mask_token($token) {
        if (empty($token)) {
            return '';
        }
        
        $len = strlen($token);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }
        
        return substr($token, 0, 4) . str_repeat('*', $len - 8) . substr($token, -4);
    }
    
    public static function verify_nonce($action) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $action)) {
            wp_die(__('Security check failed', 'aseman-robot'));
        }
    }
    
    public static function check_capability($capability = 'aseman_robot_manage') {
        if (!current_user_can($capability)) {
            wp_die(__('You do not have permission to access this page', 'aseman-robot'));
        }
    }
}
