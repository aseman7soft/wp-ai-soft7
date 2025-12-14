<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_AI_Client {
    
    private $settings;
    private $provider;
    
    public function __construct($settings = []) {
        $this->settings = wp_parse_args($settings, [
            'provider_mode' => 'remote',
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'timeout' => 120,
        ]);
        
        $this->provider = $this->get_provider();
    }
    
    private function get_provider() {
        $mode = $this->settings['provider_mode'] ?? 'remote';
        
        if ($mode === 'local') {
            $endpoint_type = $this->settings['local_endpoint_type'] ?? 'openai_compatible';
            
            if ($endpoint_type === 'ollama_native') {
                return new Aseman_Robot_AI_Ollama_Native($this->settings);
            }
        }
        
        return new Aseman_Robot_AI_OpenAI_Compatible($this->settings);
    }
    
    public function generate_content($prompt) {
        return $this->provider->generate($prompt, [
            'temperature' => floatval($this->settings['temperature']),
            'max_tokens' => intval($this->settings['max_tokens']),
        ]);
    }
    
    public function test_connection() {
        return $this->provider->test_connection();
    }
}
