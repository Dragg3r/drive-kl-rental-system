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
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple instances
if (defined('DKL_RENTAL_LOADED')) {
    return;
}
define('DKL_RENTAL_LOADED', true);

// Plugin constants
define('DKL_RENTAL_VERSION', '1.0.0');
define('DKL_RENTAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DKL_RENTAL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DKL_RENTAL_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'dkl_rental_activate');
register_deactivation_hook(__FILE__, 'dkl_rental_deactivate');

function dkl_rental_activate() {
    // Create upload directories
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
            file_put_contents($dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    // Set default options
    add_option('dkl_rental_version', DKL_RENTAL_VERSION);
    add_option('dkl_rental_settings', array(
        'email_notifications' => true,
        'auto_approve_customers' => false,
        'watermark_documents' => true
    ));
    
    // Create database tables
    dkl_rental_create_tables();
    
    flush_rewrite_rules();
}

function dkl_rental_deactivate() {
    flush_rewrite_rules();
}

function dkl_rental_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Customers table
    $customers_table = $wpdb->prefix . 'dkl_customers';
    $customers_sql = "CREATE TABLE $customers_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        address text NOT NULL,
        ic_passport_number varchar(50) DEFAULT NULL,
        ic_passport_url varchar(500) NOT NULL,
        utility_bill_url varchar(500) DEFAULT NULL,
        instagram_handle varchar(100) DEFAULT NULL,
        facebook_handle varchar(100) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        has_accepted_terms tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Rentals table
    $rentals_table = $wpdb->prefix . 'dkl_rentals';
    $rentals_sql = "CREATE TABLE $rentals_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        customer_name varchar(255) NOT NULL,
        customer_email varchar(100) NOT NULL,
        customer_phone varchar(20) NOT NULL,
        vehicle varchar(100) NOT NULL,
        color varchar(50) NOT NULL,
        start_date date NOT NULL,
        end_date date NOT NULL,
        total_days int(11) NOT NULL,
        rental_per_day decimal(10,2) NOT NULL,
        deposit decimal(10,2) NOT NULL,
        discount decimal(10,2) NOT NULL DEFAULT 0,
        subtotal decimal(10,2) NOT NULL,
        grand_total decimal(10,2) NOT NULL,
        pickup_location varchar(255) DEFAULT NULL,
        return_location varchar(255) DEFAULT NULL,
        fuel_level_pickup int(11) DEFAULT NULL,
        fuel_level_return int(11) DEFAULT NULL,
        mileage_pickup int(11) DEFAULT NULL,
        mileage_return int(11) DEFAULT NULL,
        payment_method varchar(50) DEFAULT NULL,
        payment_proof_url varchar(500) DEFAULT NULL,
        signature_url varchar(500) DEFAULT NULL,
        instagram_handle varchar(100) DEFAULT NULL,
        facebook_handle varchar(100) DEFAULT NULL,
        vehicle_photos longtext DEFAULT NULL,
        agreement_pdf_url varchar(500) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Staff table
    $staff_table = $wpdb->prefix . 'dkl_staff';
    $staff_sql = "CREATE TABLE $staff_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL,
        hashed_password varchar(255) NOT NULL,
        role varchar(20) NOT NULL DEFAULT 'staff',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($customers_sql);
    dbDelta($rentals_sql);
    dbDelta($staff_sql);
    
    // Insert default staff user
    $wpdb->replace($staff_table, array(
        'username' => 'Akib',
        'hashed_password' => password_hash('1234', PASSWORD_DEFAULT),
        'role' => 'admin'
    ));
}

// Load plugin files
add_action('plugins_loaded', 'dkl_rental_load_plugin');

function dkl_rental_load_plugin() {
    // Basic shortcode
    add_shortcode('dkl_rental_system', 'dkl_rental_system_shortcode');
    
    // Admin menu
    add_action('admin_menu', 'dkl_rental_admin_menu');
    
    // Enqueue scripts
    add_action('wp_enqueue_scripts', 'dkl_rental_enqueue_scripts');
    add_action('admin_enqueue_scripts', 'dkl_rental_admin_enqueue_scripts');
}

function dkl_rental_system_shortcode($atts) {
    ob_start();
    ?>
    <div id="dkl-rental-app" class="dkl-rental-container">
        <div class="dkl-header">
            <div class="dkl-header-content">
                <div class="dkl-logo-section">
                    <img src="<?php echo DKL_RENTAL_PLUGIN_URL; ?>assets/images/ak13-logo.png" alt="AK13" class="dkl-ak-logo">
                    <div class="dkl-title">
                        <h1>Drive KL Rental System</h1>
                        <p>By Akib</p>
                    </div>
                </div>
                <img src="<?php echo DKL_RENTAL_PLUGIN_URL; ?>assets/images/car-icon.png" alt="Car" class="dkl-car-icon">
            </div>
        </div>
        
        <div class="dkl-welcome-card">
            <div class="dkl-logo-container">
                <img src="<?php echo DKL_RENTAL_PLUGIN_URL; ?>assets/images/dkl-logo.png" alt="DKL" class="dkl-dkl-logo">
            </div>
            
            <h2>Welcome to Drive KL</h2>
            <p>Premium Car Rental Service</p>
            
            <div class="dkl-role-buttons">
                <button class="dkl-btn dkl-btn-primary" data-view="customer-login">
                    <span class="dkl-icon">👤</span>
                    Existing Customer Login
                </button>
                
                <button class="dkl-btn dkl-btn-secondary" data-view="customer-registration">
                    <span class="dkl-icon">📝</span>
                    New Customer Registration
                </button>
                
                <button class="dkl-btn dkl-btn-outline" data-view="staff-login">
                    <span class="dkl-icon">🔑</span>
                    Staff Access
                </button>
            </div>
        </div>
        
        <div id="dkl-role-selection" class="dkl-view active">
            <!-- Content populated by JavaScript -->
        </div>
        
        <div id="dkl-customer-login" class="dkl-view">
            <!-- Login form will be added here -->
        </div>
        
        <div id="dkl-customer-registration" class="dkl-view">
            <!-- Registration form will be added here -->
        </div>
        
        <div id="dkl-staff-login" class="dkl-view">
            <!-- Staff login will be added here -->
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function dkl_rental_admin_menu() {
    add_menu_page(
        'Drive KL Rental',
        'Drive KL Rental',
        'manage_options',
        'dkl-rental',
        'dkl_rental_admin_page',
        'dashicons-car',
        30
    );
}

function dkl_rental_admin_page() {
    global $wpdb;
    
    $customers_table = $wpdb->prefix . 'dkl_customers';
    $rentals_table = $wpdb->prefix . 'dkl_rentals';
    
    $total_customers = $wpdb->get_var("SELECT COUNT(*) FROM $customers_table");
    $total_rentals = $wpdb->get_var("SELECT COUNT(*) FROM $rentals_table");
    $monthly_revenue = $wpdb->get_var("SELECT SUM(grand_total) FROM $rentals_table WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    
    ?>
    <div class="wrap dkl-admin-page">
        <h1>Drive KL Rental Dashboard</h1>
        
        <div class="dkl-admin-header">
            <div class="dkl-branding">
                <h2>AK13 - Drive KL Rental System</h2>
                <p>By Akib</p>
            </div>
        </div>
        
        <div class="dkl-stats-grid">
            <div class="dkl-stat-card">
                <h3>Total Customers</h3>
                <span class="dkl-stat-number"><?php echo $total_customers ?: 0; ?></span>
            </div>
            
            <div class="dkl-stat-card">
                <h3>Total Rentals</h3>
                <span class="dkl-stat-number"><?php echo $total_rentals ?: 0; ?></span>
            </div>
            
            <div class="dkl-stat-card">
                <h3>Monthly Revenue</h3>
                <span class="dkl-stat-number">RM <?php echo number_format($monthly_revenue ?: 0, 2); ?></span>
            </div>
        </div>
        
        <div class="dkl-admin-section">
            <h3>Plugin Status</h3>
            <p><strong>Version:</strong> <?php echo DKL_RENTAL_VERSION; ?></p>
            <p><strong>Database Tables:</strong> Created Successfully</p>
            <p><strong>Upload Directory:</strong> <?php echo wp_upload_dir()['basedir'] . '/dkl-rental'; ?></p>
        </div>
    </div>
    <?php
}

function dkl_rental_enqueue_scripts() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dkl_rental_system')) {
        wp_enqueue_style('dkl-rental-public', DKL_RENTAL_PLUGIN_URL . 'assets/css/public.css', array(), DKL_RENTAL_VERSION);
        wp_enqueue_script('dkl-rental-public', DKL_RENTAL_PLUGIN_URL . 'assets/js/public.js', array('jquery'), DKL_RENTAL_VERSION, true);
        
        wp_localize_script('dkl-rental-public', 'dklRental', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dkl_rental_nonce'),
            'pluginUrl' => DKL_RENTAL_PLUGIN_URL
        ));
    }
}

function dkl_rental_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'dkl-rental') !== false) {
        wp_enqueue_style('dkl-rental-admin', DKL_RENTAL_PLUGIN_URL . 'assets/css/admin.css', array(), DKL_RENTAL_VERSION);
        wp_enqueue_script('dkl-rental-admin', DKL_RENTAL_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DKL_RENTAL_VERSION, true);
    }
}