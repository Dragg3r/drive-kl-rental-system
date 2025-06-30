<?php
/**
 * Plugin Name: Drive KL Rental System
 * Plugin URI: https://drivekl.com
 * Description: Complete car rental management system with customer registration, document uploads, PDF agreement generation, and staff dashboard.
 * Version: 1.1.0
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
define('DKL_RENTAL_VERSION', '1.1.0');
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
    
    // AJAX handlers
    add_action('wp_ajax_dkl_customer_register', 'dkl_handle_customer_register');
    add_action('wp_ajax_nopriv_dkl_customer_register', 'dkl_handle_customer_register');
    
    add_action('wp_ajax_dkl_customer_login', 'dkl_handle_customer_login');
    add_action('wp_ajax_nopriv_dkl_customer_login', 'dkl_handle_customer_login');
    
    add_action('wp_ajax_dkl_staff_login', 'dkl_handle_staff_login');
    add_action('wp_ajax_nopriv_dkl_staff_login', 'dkl_handle_staff_login');
    
    add_action('wp_ajax_dkl_accept_terms', 'dkl_handle_accept_terms');
    add_action('wp_ajax_nopriv_dkl_accept_terms', 'dkl_handle_accept_terms');
    
    add_action('wp_ajax_dkl_create_rental', 'dkl_handle_create_rental');
    add_action('wp_ajax_nopriv_dkl_create_rental', 'dkl_handle_create_rental');
    
    add_action('wp_ajax_dkl_generate_agreement', 'dkl_handle_generate_agreement');
    add_action('wp_ajax_nopriv_dkl_generate_agreement', 'dkl_handle_generate_agreement');
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
        
        <!-- Role Selection View -->
        <div id="dkl-role-selection" class="dkl-view active">
            <div class="dkl-welcome-card">
                <div class="dkl-logo-container">
                    <img src="<?php echo DKL_RENTAL_PLUGIN_URL; ?>assets/images/dkl-logo.png" alt="DKL" class="dkl-dkl-logo">
                </div>
                
                <h2>Welcome to Drive KL</h2>
                <p>Premium Car Rental Service</p>
                
                <div class="dkl-role-buttons">
                    <button class="dkl-btn dkl-btn-primary dkl-btn-full" data-view="customer-login">
                        <span class="dkl-icon">👤</span>
                        Existing Customer Login
                    </button>
                    
                    <button class="dkl-btn dkl-btn-secondary dkl-btn-full" data-view="customer-registration">
                        <span class="dkl-icon">📝</span>
                        New Customer Registration
                    </button>
                    
                    <button class="dkl-btn dkl-btn-outline dkl-btn-full" data-view="staff-login">
                        <span class="dkl-icon">🔑</span>
                        Staff Access
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Customer Login View -->
        <div id="dkl-customer-login" class="dkl-view">
            <div class="dkl-form-card">
                <h2>Customer Login</h2>
                <p>Welcome back! Please sign in to your account.</p>
                
                <form id="dkl-customer-login-form" class="dkl-form">
                    <div class="dkl-form-group">
                        <label for="login_email">Email Address</label>
                        <input type="email" id="login_email" name="email" required>
                    </div>
                    
                    <div class="dkl-form-group">
                        <label for="login_phone">Phone Number</label>
                        <input type="tel" id="login_phone" name="phone" required>
                        <small>Use the same phone number from registration</small>
                    </div>
                    
                    <button type="submit" class="dkl-btn dkl-btn-primary dkl-btn-full">
                        Sign In
                    </button>
                    
                    <div class="dkl-form-footer">
                        <a href="#" data-view="customer-registration">Don't have an account? Register here</a><br>
                        <a href="#" data-view="role-selection">← Back to Main Menu</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Customer Registration View -->
        <div id="dkl-customer-registration" class="dkl-view">
            <div class="dkl-form-card dkl-wide-card">
                <h2>Customer Registration</h2>
                <p>Create your account to start booking premium vehicles.</p>
                
                <form id="dkl-customer-registration-form" class="dkl-form" enctype="multipart/form-data">
                    <div class="dkl-form-section">
                        <h3>Personal Information</h3>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="reg_full_name">Full Name *</label>
                                <input type="text" id="reg_full_name" name="full_name" required>
                            </div>
                            <div class="dkl-form-group">
                                <label for="reg_email">Email Address *</label>
                                <input type="email" id="reg_email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="reg_phone">Phone Number *</label>
                                <input type="tel" id="reg_phone" name="phone" required>
                            </div>
                            <div class="dkl-form-group">
                                <label for="reg_ic_number">IC/Passport Number *</label>
                                <input type="text" id="reg_ic_number" name="ic_passport_number" required>
                            </div>
                        </div>
                        
                        <div class="dkl-form-group">
                            <label for="reg_address">Full Address *</label>
                            <textarea id="reg_address" name="address" rows="3" required></textarea>
                        </div>
                    </div>
                    
                    <div class="dkl-form-section">
                        <h3>Document Upload</h3>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="reg_ic_passport">IC/Passport Image *</label>
                                <div class="dkl-file-upload">
                                    <input type="file" id="reg_ic_passport" name="ic_passport" accept="image/*" required>
                                    <div class="dkl-file-upload-text">
                                        <span class="dkl-icon">📄</span>
                                        <span>Click to upload IC/Passport</span>
                                        <small>Maximum 5MB, JPEG/PNG only</small>
                                    </div>
                                </div>
                            </div>
                            <div class="dkl-form-group">
                                <label for="reg_utility_bill">Utility Bill *</label>
                                <div class="dkl-file-upload">
                                    <input type="file" id="reg_utility_bill" name="utility_bill" accept="image/*" required>
                                    <div class="dkl-file-upload-text">
                                        <span class="dkl-icon">💡</span>
                                        <span>Click to upload Utility Bill</span>
                                        <small>Maximum 5MB, JPEG/PNG only</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dkl-form-section">
                        <h3>Social Media (Optional)</h3>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="reg_instagram">Instagram Handle</label>
                                <input type="text" id="reg_instagram" name="instagram_handle" placeholder="@username">
                            </div>
                            <div class="dkl-form-group">
                                <label for="reg_facebook">Facebook Profile</label>
                                <input type="text" id="reg_facebook" name="facebook_handle" placeholder="facebook.com/username">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="dkl-btn dkl-btn-primary dkl-btn-full">
                        Create Account
                    </button>
                    
                    <div class="dkl-form-footer">
                        <a href="#" data-view="customer-login">Already have an account? Login here</a><br>
                        <a href="#" data-view="role-selection">← Back to Main Menu</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Terms & Conditions View -->
        <div id="dkl-terms-conditions" class="dkl-view">
            <div class="dkl-form-card dkl-wide-card">
                <h2>Terms & Conditions</h2>
                <p>Please read and accept our terms before proceeding.</p>
                
                <div class="dkl-terms-content">
                    <h3>Drive KL Rental Terms & Conditions</h3>
                    
                    <h4>1. General Terms</h4>
                    <ul>
                        <li>Renter must be at least 21 years old with a valid driving license</li>
                        <li>Valid IC/Passport required for verification</li>
                        <li>All rentals subject to vehicle availability</li>
                    </ul>
                    
                    <h4>2. Payment Terms</h4>
                    <ul>
                        <li>Full payment required before vehicle handover</li>
                        <li>Security deposit is refundable upon satisfactory return</li>
                        <li>Additional charges may apply for damages or violations</li>
                    </ul>
                    
                    <h4>3. Vehicle Usage</h4>
                    <ul>
                        <li>Vehicles to be returned in same condition as received</li>
                        <li>No smoking allowed in vehicles (RM200 penalty)</li>
                        <li>Mileage restrictions apply as per agreement</li>
                        <li>Late return charges: RM50 per hour</li>
                    </ul>
                    
                    <h4>4. Insurance & Liability</h4>
                    <ul>
                        <li>Renter responsible for all traffic violations</li>
                        <li>Valid insurance coverage required</li>
                        <li>Report any accidents immediately</li>
                    </ul>
                </div>
                
                <div class="dkl-terms-actions">
                    <label class="dkl-checkbox">
                        <input type="checkbox" id="accept_terms">
                        <span>I have read and accept the Terms & Conditions</span>
                    </label>
                    
                    <button id="proceed_to_rental" class="dkl-btn dkl-btn-primary dkl-btn-full" disabled>
                        Proceed to Rental Booking
                    </button>
                    
                    <div class="dkl-form-footer">
                        <a href="#" data-view="role-selection">← Back to Main Menu</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Staff Login View -->
        <div id="dkl-staff-login" class="dkl-view">
            <div class="dkl-form-card">
                <h2>Staff Access</h2>
                <p>Staff members only. Please enter your credentials.</p>
                
                <form id="dkl-staff-login-form" class="dkl-form">
                    <div class="dkl-form-group">
                        <label for="staff_username">Username</label>
                        <input type="text" id="staff_username" name="username" required>
                    </div>
                    
                    <div class="dkl-form-group">
                        <label for="staff_password">Password</label>
                        <input type="password" id="staff_password" name="password" required>
                    </div>
                    
                    <button type="submit" class="dkl-btn dkl-btn-primary dkl-btn-full">
                        Staff Login
                    </button>
                    
                    <div class="dkl-form-footer">
                        <a href="#" data-view="role-selection">← Back to Main Menu</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Rental Form View (populated by JavaScript after login) -->
        <div id="dkl-rental-form" class="dkl-view">
            <!-- Will be populated after successful login -->
        </div>
        
        <!-- Staff Dashboard View -->
        <div id="dkl-staff-dashboard" class="dkl-view">
            <!-- Will be populated after staff login -->
        </div>
        
        <!-- Final Confirmation View -->
        <div id="dkl-final-confirmation" class="dkl-view">
            <!-- Will be populated after rental submission -->
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
        
        // Signature pad library from CDN
        wp_enqueue_script('signature-pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', array(), '4.0.0', true);
        
        wp_enqueue_script('dkl-rental-public', DKL_RENTAL_PLUGIN_URL . 'assets/js/public.js', array('jquery', 'signature-pad'), DKL_RENTAL_VERSION, true);
        
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

// AJAX Handler Functions
function dkl_handle_customer_register() {
    check_ajax_referer('dkl_rental_nonce', 'nonce');
    
    global $wpdb;
    $customers_table = $wpdb->prefix . 'dkl_customers';
    
    // Validate required fields
    $required_fields = ['full_name', 'email', 'phone', 'ic_passport_number', 'address'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(array('message' => "Missing required field: $field"));
        }
    }
    
    // Check if email already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $customers_table WHERE email = %s",
        sanitize_email($_POST['email'])
    ));
    
    if ($existing) {
        wp_send_json_error(array('message' => 'Email already registered. Please login instead.'));
    }
    
    // Handle file uploads
    $ic_passport_url = '';
    $utility_bill_url = '';
    
    if (!empty($_FILES['ic_passport'])) {
        $ic_upload = dkl_handle_file_upload($_FILES['ic_passport'], 'documents');
        if (is_wp_error($ic_upload)) {
            wp_send_json_error(array('message' => 'IC/Passport upload failed: ' . $ic_upload->get_error_message()));
        }
        $ic_passport_url = $ic_upload;
    }
    
    if (!empty($_FILES['utility_bill'])) {
        $bill_upload = dkl_handle_file_upload($_FILES['utility_bill'], 'documents');
        if (is_wp_error($bill_upload)) {
            wp_send_json_error(array('message' => 'Utility bill upload failed: ' . $bill_upload->get_error_message()));
        }
        $utility_bill_url = $bill_upload;
    }
    
    // Insert customer
    $result = $wpdb->insert($customers_table, array(
        'full_name' => sanitize_text_field($_POST['full_name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'ic_passport_number' => sanitize_text_field($_POST['ic_passport_number']),
        'address' => sanitize_textarea_field($_POST['address']),
        'ic_passport_url' => $ic_passport_url,
        'utility_bill_url' => $utility_bill_url,
        'instagram_handle' => sanitize_text_field($_POST['instagram_handle'] ?? ''),
        'facebook_handle' => sanitize_text_field($_POST['facebook_handle'] ?? ''),
        'status' => 'active',
        'has_accepted_terms' => 0,
        'created_at' => current_time('mysql')
    ));
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Registration failed. Please try again.'));
    }
    
    wp_send_json_success(array(
        'message' => 'Registration successful! You can now login.',
        'customer_id' => $wpdb->insert_id
    ));
}

function dkl_handle_customer_login() {
    check_ajax_referer('dkl_rental_nonce', 'nonce');
    
    global $wpdb;
    $customers_table = $wpdb->prefix . 'dkl_customers';
    
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    
    $customer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $customers_table WHERE email = %s AND phone = %s AND status = 'active'",
        $email, $phone
    ));
    
    if (!$customer) {
        wp_send_json_error(array('message' => 'Invalid credentials or account not found.'));
    }
    
    // Store customer in session
    session_start();
    $_SESSION['dkl_customer'] = array(
        'id' => $customer->id,
        'full_name' => $customer->full_name,
        'email' => $customer->email,
        'phone' => $customer->phone,
        'has_accepted_terms' => $customer->has_accepted_terms
    );
    
    wp_send_json_success(array(
        'message' => 'Login successful!',
        'customer' => $_SESSION['dkl_customer'],
        'redirect' => $customer->has_accepted_terms ? 'rental-form' : 'terms-conditions'
    ));
}

function dkl_handle_staff_login() {
    check_ajax_referer('dkl_rental_nonce', 'nonce');
    
    global $wpdb;
    $staff_table = $wpdb->prefix . 'dkl_staff';
    
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    $staff = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $staff_table WHERE username = %s",
        $username
    ));
    
    if (!$staff || !password_verify($password, $staff->hashed_password)) {
        wp_send_json_error(array('message' => 'Invalid staff credentials.'));
    }
    
    session_start();
    $_SESSION['dkl_staff'] = array(
        'id' => $staff->id,
        'username' => $staff->username,
        'role' => $staff->role
    );
    
    wp_send_json_success(array(
        'message' => 'Staff login successful!',
        'staff' => $_SESSION['dkl_staff']
    ));
}

function dkl_handle_accept_terms() {
    check_ajax_referer('dkl_rental_nonce', 'nonce');
    
    session_start();
    if (!isset($_SESSION['dkl_customer'])) {
        wp_send_json_error(array('message' => 'Please login first.'));
    }
    
    global $wpdb;
    $customers_table = $wpdb->prefix . 'dkl_customers';
    
    $result = $wpdb->update(
        $customers_table,
        array('has_accepted_terms' => 1),
        array('id' => $_SESSION['dkl_customer']['id']),
        array('%d'),
        array('%d')
    );
    
    if ($result !== false) {
        $_SESSION['dkl_customer']['has_accepted_terms'] = 1;
        wp_send_json_success(array('message' => 'Terms accepted successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to accept terms.'));
    }
}

function dkl_handle_create_rental() {
    check_ajax_referer('dkl_rental_nonce', 'nonce');
    
    session_start();
    if (!isset($_SESSION['dkl_customer'])) {
        wp_send_json_error(array('message' => 'Please login first.'));
    }
    
    global $wpdb;
    $rentals_table = $wpdb->prefix . 'dkl_rentals';
    $customer = $_SESSION['dkl_customer'];
    
    // Handle file uploads
    $vehicle_photos = array();
    $payment_proof_url = '';
    $signature_url = '';
    
    // Process vehicle photos
    for ($i = 1; $i <= 7; $i++) {
        if (!empty($_FILES["vehicle_photo_$i"])) {
            $upload = dkl_handle_file_upload($_FILES["vehicle_photo_$i"], 'vehicle-photos');
            if (!is_wp_error($upload)) {
                $vehicle_photos[] = $upload;
            }
        }
    }
    
    // Process payment proof
    if (!empty($_FILES['payment_proof'])) {
        $payment_proof_url = dkl_handle_file_upload($_FILES['payment_proof'], 'documents');
    }
    
    // Process signature
    if (!empty($_POST['signature_data'])) {
        $signature_url = dkl_save_signature($_POST['signature_data']);
    }
    
    // Insert rental record
    $result = $wpdb->insert($rentals_table, array(
        'customer_id' => $customer['id'],
        'customer_name' => $customer['full_name'],
        'customer_email' => $customer['email'],
        'customer_phone' => $customer['phone'],
        'vehicle' => sanitize_text_field($_POST['vehicle']),
        'color' => sanitize_text_field($_POST['color']),
        'start_date' => sanitize_text_field($_POST['start_date']),
        'end_date' => sanitize_text_field($_POST['end_date']),
        'total_days' => intval($_POST['total_days']),
        'rental_per_day' => floatval($_POST['rental_per_day']),
        'deposit' => floatval($_POST['deposit']),
        'discount' => floatval($_POST['discount']),
        'subtotal' => floatval($_POST['subtotal']),
        'grand_total' => floatval($_POST['grand_total']),
        'pickup_location' => sanitize_text_field($_POST['pickup_location']),
        'return_location' => sanitize_text_field($_POST['return_location']),
        'fuel_level_pickup' => intval($_POST['fuel_level_pickup']),
        'fuel_level_return' => intval($_POST['fuel_level_return']),
        'mileage_pickup' => intval($_POST['mileage_pickup']),
        'mileage_return' => intval($_POST['mileage_return']),
        'payment_method' => sanitize_text_field($_POST['payment_method']),
        'payment_proof_url' => $payment_proof_url,
        'signature_url' => $signature_url,
        'instagram_handle' => sanitize_text_field($_POST['instagram_handle'] ?? ''),
        'facebook_handle' => sanitize_text_field($_POST['facebook_handle'] ?? ''),
        'vehicle_photos' => wp_json_encode($vehicle_photos),
        'status' => 'pending',
        'created_at' => current_time('mysql')
    ));
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to create rental. Please try again.'));
    }
    
    wp_send_json_success(array(
        'message' => 'Rental created successfully!',
        'rental_id' => $wpdb->insert_id
    ));
}

function dkl_handle_generate_agreement() {
    check_ajax_referer('dkl_rental_nonce', 'nonce');
    
    global $wpdb;
    $rentals_table = $wpdb->prefix . 'dkl_rentals';
    
    $rental_id = intval($_POST['rental_id']);
    $rental = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $rentals_table WHERE id = %d",
        $rental_id
    ));
    
    if (!$rental) {
        wp_send_json_error(array('message' => 'Rental not found.'));
    }
    
    // Simple PDF generation (basic version)
    $pdf_filename = "rental-agreement-{$rental_id}.txt";
    $upload_dir = wp_upload_dir();
    $pdf_path = $upload_dir['basedir'] . '/dkl-rental/agreements/' . $pdf_filename;
    
    // Create agreements directory if it doesn't exist
    wp_mkdir_p(dirname($pdf_path));
    
    // Generate simple text agreement (in full version would use TCPDF)
    $agreement_content = "DRIVE KL RENTAL AGREEMENT\n\n";
    $agreement_content .= "Rental ID: {$rental->id}\n";
    $agreement_content .= "Customer: {$rental->customer_name}\n";
    $agreement_content .= "Email: {$rental->customer_email}\n";
    $agreement_content .= "Phone: {$rental->customer_phone}\n\n";
    $agreement_content .= "Vehicle: {$rental->vehicle}\n";
    $agreement_content .= "Color: {$rental->color}\n";
    $agreement_content .= "Period: {$rental->start_date} to {$rental->end_date}\n";
    $agreement_content .= "Total Days: {$rental->total_days}\n";
    $agreement_content .= "Grand Total: RM " . number_format($rental->grand_total, 2) . "\n\n";
    $agreement_content .= "Generated on: " . current_time('Y-m-d H:i:s') . "\n";
    
    file_put_contents($pdf_path, $agreement_content);
    
    // Update rental with PDF URL
    $pdf_url = $upload_dir['baseurl'] . '/dkl-rental/agreements/' . $pdf_filename;
    $wpdb->update(
        $rentals_table,
        array('agreement_pdf_url' => $pdf_url, 'status' => 'confirmed'),
        array('id' => $rental_id)
    );
    
    wp_send_json_success(array(
        'message' => 'Agreement generated successfully!',
        'download_url' => $pdf_url,
        'customer_name' => $rental->customer_name,
        'vehicle' => $rental->vehicle,
        'period' => $rental->start_date . ' to ' . $rental->end_date,
        'total' => number_format($rental->grand_total, 2),
        'email_sent' => false // In full version would actually send email
    ));
}

// Helper functions
function dkl_handle_file_upload($file, $subfolder = 'documents') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'File upload failed.');
    }
    
    // Validate file type
    $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
    if (!in_array($file['type'], $allowed_types)) {
        return new WP_Error('invalid_type', 'Only JPEG and PNG images are allowed.');
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'File size must be less than 5MB.');
    }
    
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/dkl-rental/' . $subfolder . '/';
    wp_mkdir_p($target_dir);
    
    $filename = time() . '_' . sanitize_file_name($file['name']);
    $target_path = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $upload_dir['baseurl'] . '/dkl-rental/' . $subfolder . '/' . $filename;
    }
    
    return new WP_Error('move_failed', 'Failed to save uploaded file.');
}

function dkl_save_signature($signature_data) {
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/dkl-rental/signatures/';
    wp_mkdir_p($target_dir);
    
    // Remove data URL prefix
    $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
    $signature_data = base64_decode($signature_data);
    
    $filename = 'signature_' . time() . '.png';
    $target_path = $target_dir . $filename;
    
    if (file_put_contents($target_path, $signature_data)) {
        return $upload_dir['baseurl'] . '/dkl-rental/signatures/' . $filename;
    }
    
    return '';
}