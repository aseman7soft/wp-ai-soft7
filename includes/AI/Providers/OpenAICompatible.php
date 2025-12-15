<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_AI_OpenAI_Compatible {
    
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
    }
    
    private function get_base_url() {
        $mode = $this->settings['provider_mode'] ?? 'remote';
        
        if ($mode === 'local') {
            return rtrim($this->settings['local_base_url'] ?? 'http://localhost:1234/v1', '/');
        }
        
        return rtrim($this->settings['remote_api_base_url'] ?? 'https://api.openai.com/v1', '/');
    }
    
    private function get_token() {
        $mode = $this->settings['provider_mode'] ?? 'remote';
        
        if ($mode === 'local') {
            return $this->settings['local_token'] ?? '';
        }
        
        return $this->settings['remote_api_token'] ?? '';
    }
    
    private function get_model() {
        $mode = $this->settings['provider_mode'] ?? 'remote';
        
        if ($mode === 'local') {
            return $this->settings['local_model_name'] ?? 'local-model';
        }
        
        return $this->settings['remote_model_name'] ?? 'gpt-4';
    }
    
    public function generate($prompt, $options = []) {
        $url = $this->get_base_url() . '/chat/completions';
        
        $body = [
            'model' => $this->get_model(),
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 4000,
        ];
        
        $args = [
            'timeout' => intval($this->settings['timeout'] ?? 120),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ];
        
        $token = $this->get_token();
        if (!empty($token)) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API Request Failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception("API Error (Status: {$status_code}): {$body}");
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response structure from API');
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    public function test_connection() {
        try {
            $url = $this->get_base_url() . '/models';
            
            $args = [
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ];
            
            $token = $this->get_token();
            if (!empty($token)) {
                $args['headers']['Authorization'] = 'Bearer ' . $token;
            }
            
            $response = wp_remote_get($url, $args);
            
            if (is_wp_error($response)) {
                return 'Connection Failed: ' . $response->get_error_message();
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 200 || $status_code === 404) {
                return 'Connection Successful! Model: ' . $this->get_model();
            }
            
            return 'Connection Failed (Status: ' . $status_code . ')';
            
        } catch (Exception $e) {
            return 'Test Failed: ' . $e->getMessage();
        }
    }
}
