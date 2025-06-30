<?php
/**
 * Plugin Name: Drive KL Rental System
 * Plugin URI: https://drivekl.com
 * Description: Complete car rental management system with customer registration, document uploads, PDF agreement generation, and staff dashboard.
 * Version: 1.0.0
 * Author: Akib
 * Author URI: https://ak13.com
 * License: GPL v2 or later
 * Text Domain: drive-kl-rental
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple plugin instances
if (defined('DKL_RENTAL_VERSION')) {
    return;
}

// Define plugin constants
define('DKL_RENTAL_VERSION', '1.0.0');
define('DKL_RENTAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DKL_RENTAL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DKL_RENTAL_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Prevent class redefinition
if (!class_exists('DriveKLRental')) {

// Main plugin class
class DriveKLRental {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new DriveKLRental();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Use plugins_loaded instead of init to ensure WordPress is fully loaded
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load dependencies
        $this->loadDependencies();
        
        // Initialize components
        $this->initializeComponents();
        
        // Add hooks
        $this->addHooks();
    }
    
    private function loadDependencies() {
        $files = array(
            'includes/class-database.php',
            'includes/class-customer.php',
            'includes/class-rental.php',
            'includes/class-pdf-generator.php',
            'includes/class-image-processor.php',
            'includes/class-email-service.php',
            'includes/class-ajax-handler.php',
            'includes/class-shortcodes.php',
            'admin/class-admin.php',
            'public/class-public.php'
        );
        
        foreach ($files as $file) {
            $file_path = DKL_RENTAL_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("DKL Rental Plugin: Missing file - $file_path");
            }
        }
    }
    
    private function initializeComponents() {
        // Initialize components only if classes exist
        if (class_exists('DKL_Rental_Database')) {
            new DKL_Rental_Database();
        }
        
        if (class_exists('DKL_Rental_Admin')) {
            new DKL_Rental_Admin();
        }
        
        if (class_exists('DKL_Rental_Public')) {
            new DKL_Rental_Public();
        }
        
        if (class_exists('DKL_Rental_Ajax_Handler')) {
            new DKL_Rental_Ajax_Handler();
        }
        
        if (class_exists('DKL_Rental_Shortcodes')) {
            new DKL_Rental_Shortcodes();
        }
    }
    
    private function addHooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueuePublicScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_ajax_dkl_rental_action', array('DKL_Rental_Ajax_Handler', 'handleAjax'));
        add_action('wp_ajax_nopriv_dkl_rental_action', array('DKL_Rental_Ajax_Handler', 'handleAjax'));
    }
    
    public function activate() {
        try {
            // Load dependencies first
            $this->loadDependencies();
            
            // Create upload directories
            $this->createUploadDirectories();
            
            // Set default options
            $this->setDefaultOptions();
            
            // Create database tables
            if (class_exists('DKL_Rental_Database')) {
                DKL_Rental_Database::createTables();
            }
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            // Log error and prevent fatal error
            error_log('DKL Rental Plugin Activation Error: ' . $e->getMessage());
            wp_die('Plugin activation failed. Please check error logs.');
        }
    }
    
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('dkl_rental_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function createUploadDirectories() {
        $upload_dir = wp_upload_dir();
        $dkl_dirs = array(
            $upload_dir['basedir'] . '/dkl-rental',
            $upload_dir['basedir'] . '/dkl-rental/documents',
            $upload_dir['basedir'] . '/dkl-rental/vehicle-photos',
            $upload_dir['basedir'] . '/dkl-rental/signatures',
            $upload_dir['basedir'] . '/dkl-rental/agreements'
        );
        
        foreach ($dkl_dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                // Add index.php for security
                file_put_contents($dir . '/index.php', '<?php // Silence is golden');
            }
        }
    }
    
    private function setDefaultOptions() {
        add_option('dkl_rental_version', DKL_RENTAL_VERSION);
        add_option('dkl_rental_settings', array(
            'email_notifications' => true,
            'auto_approve_customers' => false,
            'require_staff_approval' => true,
            'watermark_documents' => true,
            'compress_images' => true
        ));
    }
    
    public function enqueuePublicScripts() {
        wp_enqueue_style('dkl-rental-public', DKL_RENTAL_PLUGIN_URL . 'assets/css/public.css', array(), DKL_RENTAL_VERSION);
        wp_enqueue_script('dkl-rental-public', DKL_RENTAL_PLUGIN_URL . 'assets/js/public.js', array('jquery'), DKL_RENTAL_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('dkl-rental-public', 'dklRental', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dkl_rental_nonce'),
            'pluginUrl' => DKL_RENTAL_PLUGIN_URL
        ));
    }
    
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'dkl-rental') !== false) {
            wp_enqueue_style('dkl-rental-admin', DKL_RENTAL_PLUGIN_URL . 'assets/css/admin.css', array(), DKL_RENTAL_VERSION);
            wp_enqueue_script('dkl-rental-admin', DKL_RENTAL_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DKL_RENTAL_VERSION, true);
            
            wp_localize_script('dkl-rental-admin', 'dklRentalAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dkl_rental_admin_nonce')
            ));
        }
    }
}

} // End class existence check

// Initialize the plugin only if class exists
if (class_exists('DriveKLRental')) {
    DriveKLRental::getInstance();
}