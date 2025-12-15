<?php
/**
 * Plugin Name: ربات آسمان
 * Plugin URI: https://github.com/aseman7soft/wp-ai-soft7
 * Description: AI-powered content generation plugin with queue support, multi-language UI, and local/remote AI provider compatibility
 * Version: 1.0.0
 * Author: Aseman Soft
 * Author URI: https://aseman7soft.com
 * Text Domain: aseman-robot
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ASEMAN_ROBOT_VERSION', '1.0.0');
define('ASEMAN_ROBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASEMAN_ROBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASEMAN_ROBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Activator.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Deactivator.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Helpers/Security.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/AI/Client.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/AI/Providers/OpenAICompatible.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/AI/Providers/OllamaNative.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Queue/QueueRepository.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Queue/Processor.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Posts/PostCreator.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Prompt/Template.php';
require_once ASEMAN_ROBOT_PLUGIN_DIR . 'includes/Reports/Stats.php';

register_activation_hook(__FILE__, ['Aseman_Robot_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Aseman_Robot_Deactivator', 'deactivate']);

class Aseman_Robot {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('aseman_robot_process_queue', ['Aseman_Robot_Queue_Processor', 'process_queue']);
        add_action('wp_ajax_aseman_robot_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_aseman_robot_retry_job', [$this, 'ajax_retry_job']);
        add_action('wp_ajax_aseman_robot_cancel_job', [$this, 'ajax_cancel_job']);
        add_action('wp_ajax_aseman_robot_delete_job', [$this, 'ajax_delete_job']);
        add_action('wp_ajax_aseman_robot_clone_force_job', [$this, 'ajax_clone_force_job']);
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
    }
    
    public function add_cron_schedules($schedules) {
        $schedules['every_five_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'aseman-robot')
        ];
        return $schedules;
    }

    public function load_textdomain() {
        load_plugin_textdomain('aseman-robot', false, dirname(ASEMAN_ROBOT_PLUGIN_BASENAME) . '/languages/');
    }

    public function register_admin_menu() {
        $capability = 'aseman_robot_manage';
        
        add_menu_page(
            __('ربات آسمان', 'aseman-robot'),
            __('ربات آسمان', 'aseman-robot'),
            $capability,
            'aseman-robot',
            [$this, 'render_generate_page'],
            'dashicons-robot',
            30
        );

        add_submenu_page(
            'aseman-robot',
            __('Generate Content', 'aseman-robot'),
            __('Generate Content', 'aseman-robot'),
            $capability,
            'aseman-robot',
            [$this, 'render_generate_page']
        );

        add_submenu_page(
            'aseman-robot',
            __('Queue Manager', 'aseman-robot'),
            __('Queue Manager', 'aseman-robot'),
            $capability,
            'aseman-robot-queue',
            [$this, 'render_queue_page']
        );

        add_submenu_page(
            'aseman-robot',
            __('Reports Dashboard', 'aseman-robot'),
            __('Reports Dashboard', 'aseman-robot'),
            'aseman_robot_view_reports',
            'aseman-robot-reports',
            [$this, 'render_reports_page']
        );

        add_submenu_page(
            'aseman-robot',
            __('Settings', 'aseman-robot'),
            __('Settings', 'aseman-robot'),
            $capability,
            'aseman-robot-settings',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'aseman-robot') === false) {
            return;
        }

        wp_enqueue_style(
            'aseman-robot-admin',
            ASEMAN_ROBOT_PLUGIN_URL . 'assets/admin.css',
            [],
            ASEMAN_ROBOT_VERSION
        );

        wp_enqueue_script(
            'aseman-robot-admin',
            ASEMAN_ROBOT_PLUGIN_URL . 'assets/admin.js',
            ['jquery'],
            ASEMAN_ROBOT_VERSION,
            true
        );

        wp_localize_script('aseman-robot-admin', 'asemanRobotAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aseman_robot_ajax'),
            'strings' => [
                'testing' => __('Testing connection...', 'aseman-robot'),
                'success' => __('Connection successful!', 'aseman-robot'),
                'failed' => __('Connection failed:', 'aseman-robot'),
                'confirm_delete' => __('Are you sure you want to delete this job?', 'aseman-robot'),
                'confirm_cancel' => __('Are you sure you want to cancel this job?', 'aseman-robot'),
            ]
        ]);
    }

    public function render_generate_page() {
        require_once ASEMAN_ROBOT_PLUGIN_DIR . 'admin/page-generate.php';
    }

    public function render_queue_page() {
        require_once ASEMAN_ROBOT_PLUGIN_DIR . 'admin/page-queue.php';
    }

    public function render_reports_page() {
        require_once ASEMAN_ROBOT_PLUGIN_DIR . 'admin/page-reports.php';
    }

    public function render_settings_page() {
        require_once ASEMAN_ROBOT_PLUGIN_DIR . 'admin/page-settings.php';
    }

    public function ajax_test_connection() {
        check_ajax_referer('aseman_robot_ajax', 'nonce');
        
        if (!current_user_can('aseman_robot_manage')) {
            wp_send_json_error(['message' => __('Permission denied', 'aseman-robot')]);
        }

        $settings = get_option('aseman_robot_settings', []);
        $client = new Aseman_Robot_AI_Client($settings);
        
        try {
            $result = $client->test_connection();
            wp_send_json_success(['message' => $result]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function ajax_retry_job() {
        check_ajax_referer('aseman_robot_ajax', 'nonce');
        
        if (!current_user_can('aseman_robot_manage')) {
            wp_send_json_error(['message' => __('Permission denied', 'aseman-robot')]);
        }

        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(['message' => __('Invalid job ID', 'aseman-robot')]);
        }

        $repo = new Aseman_Robot_Queue_Repository();
        $repo->retry_job($job_id);
        
        wp_send_json_success(['message' => __('Job queued for retry', 'aseman-robot')]);
    }

    public function ajax_cancel_job() {
        check_ajax_referer('aseman_robot_ajax', 'nonce');
        
        if (!current_user_can('aseman_robot_manage')) {
            wp_send_json_error(['message' => __('Permission denied', 'aseman-robot')]);
        }

        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(['message' => __('Invalid job ID', 'aseman-robot')]);
        }

        $repo = new Aseman_Robot_Queue_Repository();
        $repo->cancel_job($job_id);
        
        wp_send_json_success(['message' => __('Job cancelled', 'aseman-robot')]);
    }

    public function ajax_delete_job() {
        check_ajax_referer('aseman_robot_ajax', 'nonce');
        
        if (!current_user_can('aseman_robot_manage')) {
            wp_send_json_error(['message' => __('Permission denied', 'aseman-robot')]);
        }

        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(['message' => __('Invalid job ID', 'aseman-robot')]);
        }

        $repo = new Aseman_Robot_Queue_Repository();
        $repo->delete_job($job_id);
        
        wp_send_json_success(['message' => __('Job deleted', 'aseman-robot')]);
    }

    public function ajax_clone_force_job() {
        check_ajax_referer('aseman_robot_ajax', 'nonce');
        
        if (!current_user_can('aseman_robot_generate_force')) {
            wp_send_json_error(['message' => __('Permission denied', 'aseman-robot')]);
        }

        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(['message' => __('Invalid job ID', 'aseman-robot')]);
        }

        $repo = new Aseman_Robot_Queue_Repository();
        $new_job_id = $repo->clone_as_force_generate($job_id);
        
        if ($new_job_id) {
            wp_send_json_success([
                'message' => __('Job cloned with force generate enabled', 'aseman-robot'),
                'job_id' => $new_job_id
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to clone job', 'aseman-robot')]);
        }
    }
}

Aseman_Robot::get_instance();
