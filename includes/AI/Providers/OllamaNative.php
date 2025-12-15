<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_AI_Ollama_Native {
    
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
    }
    
    private function get_base_url() {
        return rtrim($this->settings['local_base_url'] ?? 'http://localhost:11434', '/');
    }
    
    private function get_model() {
        return $this->settings['local_model_name'] ?? 'llama2';
    }
    
    public function generate($prompt, $options = []) {
        $url = $this->get_base_url() . '/api/generate';
        
        $body = [
            'model' => $this->get_model(),
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens'] ?? 4000,
            ]
        ];
        
        $args = [
            'timeout' => intval($this->settings['timeout'] ?? 120),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ];
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Ollama Request Failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception("Ollama Error (Status: {$status_code}): {$body}");
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Ollama');
        }
        
        if (!isset($data['response'])) {
            throw new Exception('Invalid response structure from Ollama');
        }
        
        return $data['response'];
    }
    
    public function test_connection() {
        try {
            $url = $this->get_base_url() . '/api/tags';
            
            $args = [
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ];
            
            $response = wp_remote_get($url, $args);
            
            if (is_wp_error($response)) {
                return 'Ollama Connection Failed: ' . $response->get_error_message();
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 200) {
                return 'Ollama Connection Successful! Model: ' . $this->get_model();
            }
            
            return 'Ollama Connection Failed (Status: ' . $status_code . ')';
            
        } catch (Exception $e) {
            return 'Test Failed: ' . $e->getMessage();
        }
    }
}
