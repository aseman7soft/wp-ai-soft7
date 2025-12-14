<?php

if (!defined('ABSPATH')) {
    exit;
}

class Aseman_Robot_Prompt_Template {
    
    public function build($template, $placeholders) {
        $search = [];
        $replace = [];
        
        foreach ($placeholders as $key => $value) {
            $search[] = '{' . $key . '}';
            $replace[] = $value;
        }
        
        return str_replace($search, $replace, $template);
    }
    
    public function get_default_template() {
        return get_option('aseman_robot_prompt_template', '');
    }
    
    public function update_template($template) {
        return update_option('aseman_robot_prompt_template', $template);
    }
}
