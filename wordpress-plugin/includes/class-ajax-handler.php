<?php

class DKL_Rental_Ajax_Handler {
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_dkl_customer_register', array($this, 'handleCustomerRegistration'));
        add_action('wp_ajax_nopriv_dkl_customer_register', array($this, 'handleCustomerRegistration'));
        
        add_action('wp_ajax_dkl_customer_login', array($this, 'handleCustomerLogin'));
        add_action('wp_ajax_nopriv_dkl_customer_login', array($this, 'handleCustomerLogin'));
        
        add_action('wp_ajax_dkl_create_rental', array($this, 'handleCreateRental'));
        add_action('wp_ajax_nopriv_dkl_create_rental', array($this, 'handleCreateRental'));
        
        add_action('wp_ajax_dkl_generate_agreement', array($this, 'handleGenerateAgreement'));
        add_action('wp_ajax_nopriv_dkl_generate_agreement', array($this, 'handleGenerateAgreement'));
        
        add_action('wp_ajax_dkl_staff_login', array($this, 'handleStaffLogin'));
        add_action('wp_ajax_nopriv_dkl_staff_login', array($this, 'handleStaffLogin'));
        
        add_action('wp_ajax_dkl_get_customers', array($this, 'handleGetCustomers'));
        add_action('wp_ajax_dkl_get_rentals', array($this, 'handleGetRentals'));
        
        add_action('wp_ajax_dkl_update_customer_status', array($this, 'handleUpdateCustomerStatus'));
        add_action('wp_ajax_dkl_download_agreement', array($this, 'handleDownloadAgreement'));
    }
    
    public function handleCustomerRegistration() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dkl_rental_nonce')) {
            wp_die('Security check failed');
        }
        
        try {
            // Process customer registration
            $result = DKL_Rental_Customer::register($_POST, $_FILES);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message()
                ));
            }
            
            wp_send_json_success(array(
                'message' => 'Registration successful! You can now sign in.',
                'customer' => array(
                    'id' => $result->id,
                    'full_name' => $result->full_name,
                    'email' => $result->email
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Registration failed: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleCustomerLogin() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dkl_rental_nonce')) {
            wp_die('Security check failed');
        }
        
        try {
            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);
            
            $customer = DKL_Rental_Customer::login($email, $password);
            
            if (is_wp_error($customer)) {
                wp_send_json_error(array(
                    'message' => $customer->get_error_message()
                ));
            }
            
            // Set session/login customer
            if ($customer->user_id) {
                wp_set_current_user($customer->user_id);
                wp_set_auth_cookie($customer->user_id);
            }
            
            wp_send_json_success(array(
                'message' => 'Login successful!',
                'customer' => array(
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'email' => $customer->email,
                    'has_accepted_terms' => $customer->has_accepted_terms
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Login failed: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleCreateRental() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dkl_rental_nonce')) {
            wp_die('Security check failed');
        }
        
        try {
            // Get current customer
            $customer = DKL_Rental_Customer::getCurrentUser();
            if (!$customer) {
                wp_send_json_error(array('message' => 'Please login first'));
            }
            
            // Process vehicle photos
            $vehicle_photos = array();
            if (isset($_FILES['vehicle_photos'])) {
                $vehicle_photos = DKL_Rental_Image_Processor::processVehiclePhotos($_FILES['vehicle_photos']);
            }
            
            // Process payment proof
            $payment_proof_url = '';
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                $result = DKL_Rental_Image_Processor::processDocument($_FILES['payment_proof'], 'payment_proof');
                if (!is_wp_error($result)) {
                    $payment_proof_url = $result['url'];
                }
            }
            
            // Process signature
            $signature_url = '';
            if (!empty($_POST['signature_data'])) {
                $result = DKL_Rental_Image_Processor::processSignature($_POST['signature_data']);
                if (!is_wp_error($result)) {
                    $signature_url = $result['url'];
                }
            }
            
            // Calculate totals
            $total_days = intval($_POST['total_days']);
            $rental_per_day = floatval($_POST['rental_per_day']);
            $deposit = floatval($_POST['deposit']);
            $discount = floatval($_POST['discount']);
            $grand_total = ($rental_per_day * $total_days) + $deposit - $discount;
            
            // Prepare rental data
            $rental_data = array(
                'customer_id' => $customer->id,
                'vehicle' => sanitize_text_field($_POST['vehicle']),
                'color' => sanitize_text_field($_POST['color']),
                'mileage_limit' => intval($_POST['mileage_limit']),
                'extra_mileage_charge' => floatval($_POST['extra_mileage_charge']),
                'fuel_level' => intval($_POST['fuel_level']),
                'start_date' => sanitize_text_field($_POST['start_date']),
                'end_date' => sanitize_text_field($_POST['end_date']),
                'total_days' => $total_days,
                'rental_per_day' => $rental_per_day,
                'deposit' => $deposit,
                'discount' => $discount,
                'grand_total' => $grand_total,
                'vehicle_photos' => $vehicle_photos,
                'payment_proof_url' => $payment_proof_url,
                'signature_url' => $signature_url
            );
            
            // Create rental
            $rental = DKL_Rental_Database::createRental($rental_data);
            
            if (!$rental) {
                wp_send_json_error(array('message' => 'Failed to create rental'));
            }
            
            wp_send_json_success(array(
                'message' => 'Rental created successfully!',
                'rental_id' => $rental->id,
                'rental' => $rental
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Failed to create rental: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleGenerateAgreement() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dkl_rental_nonce')) {
            wp_die('Security check failed');
        }
        
        try {
            $rental_id = intval($_POST['rental_id']);
            
            $result = DKL_Rental_PDF_Generator::generateRentalAgreement($rental_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message()
                ));
            }
            
            // Get rental and customer info for email
            $rental = DKL_Rental_Database::getRentalById($rental_id);
            
            // Try to send email
            $email_sent = false;
            try {
                $email_result = DKL_Rental_Email_Service::sendRentalAgreement($rental, $result['pdf_path']);
                $email_sent = !is_wp_error($email_result);
            } catch (Exception $e) {
                // Email failed but continue with success response
            }
            
            wp_send_json_success(array(
                'message' => $email_sent ? 
                    'Agreement generated and sent to your email!' : 
                    'Agreement generated successfully (email delivery unavailable)',
                'pdf_url' => $result['pdf_url'],
                'download_url' => admin_url('admin-ajax.php?action=dkl_download_agreement&rental_id=' . $rental_id),
                'email_sent' => $email_sent,
                'customer_name' => $rental->customer_name,
                'vehicle' => $rental->vehicle,
                'period' => date('n/j/Y', strtotime($rental->start_date)) . ' - ' . date('n/j/Y', strtotime($rental->end_date)),
                'total' => number_format($rental->grand_total, 2)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Failed to generate agreement: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleStaffLogin() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dkl_rental_nonce')) {
            wp_die('Security check failed');
        }
        
        try {
            $username = sanitize_text_field($_POST['username']);
            $password = sanitize_text_field($_POST['password']);
            
            $staff = DKL_Rental_Database::verifyStaffPassword($username, $password);
            
            if (!$staff) {
                wp_send_json_error(array(
                    'message' => 'Invalid username or password'
                ));
            }
            
            // Set staff session
            $_SESSION['dkl_staff_id'] = $staff->id;
            $_SESSION['dkl_staff_username'] = $staff->username;
            
            wp_send_json_success(array(
                'message' => 'Login successful!',
                'staff' => array(
                    'id' => $staff->id,
                    'username' => $staff->username,
                    'role' => $staff->role
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Login failed: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleGetCustomers() {
        // Verify staff session
        if (!isset($_SESSION['dkl_staff_id'])) {
            wp_send_json_error(array('message' => 'Staff authentication required'));
        }
        
        try {
            $customers = DKL_Rental_Database::getCustomers();
            
            wp_send_json_success($customers);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Failed to fetch customers: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleGetRentals() {
        // Verify staff session
        if (!isset($_SESSION['dkl_staff_id'])) {
            wp_send_json_error(array('message' => 'Staff authentication required'));
        }
        
        try {
            $rentals = DKL_Rental_Database::getRentals();
            
            wp_send_json_success($rentals);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Failed to fetch rentals: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleUpdateCustomerStatus() {
        // Verify staff session and nonce
        if (!isset($_SESSION['dkl_staff_id']) || !wp_verify_nonce($_POST['nonce'], 'dkl_rental_admin_nonce')) {
            wp_send_json_error(array('message' => 'Authentication required'));
        }
        
        try {
            $customer_id = intval($_POST['customer_id']);
            $status = sanitize_text_field($_POST['status']);
            
            $result = DKL_Rental_Database::updateCustomerStatus($customer_id, $status);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Customer status updated successfully'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Failed to update customer status'
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Update failed: ' . $e->getMessage()
            ));
        }
    }
    
    public function handleDownloadAgreement() {
        $rental_id = intval($_GET['rental_id']);
        
        if (!$rental_id) {
            wp_die('Invalid rental ID');
        }
        
        $rental = DKL_Rental_Database::getRentalById($rental_id);
        
        if (!$rental || !$rental->agreement_pdf_url) {
            wp_die('Agreement not found');
        }
        
        // Get file path from URL
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $rental->agreement_pdf_url);
        
        if (!file_exists($file_path)) {
            wp_die('Agreement file not found');
        }
        
        // Force download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}