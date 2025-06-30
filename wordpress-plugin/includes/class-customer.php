<?php

class DKL_Rental_Customer {
    
    public static function register($data, $files) {
        // Validate required fields
        $required_fields = ['full_name', 'email', 'password', 'phone', 'address', 'ic_passport_number'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Field $field is required");
            }
        }
        
        // Check if customer already exists
        if (DKL_Rental_Database::getCustomerByEmail($data['email'])) {
            return new WP_Error('customer_exists', 'Customer with this email already exists');
        }
        
        // Process IC/Passport upload
        if (!isset($files['ic_passport']) || $files['ic_passport']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('missing_file', 'IC/Passport image is required');
        }
        
        // Process utility bill upload
        if (!isset($files['utility_bill']) || $files['utility_bill']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('missing_file', 'Utility bill image is required');
        }
        
        // Process and upload IC/Passport
        $ic_passport_result = DKL_Rental_Image_Processor::processDocument($files['ic_passport'], 'ic_passport');
        if (is_wp_error($ic_passport_result)) {
            return $ic_passport_result;
        }
        
        // Process and upload utility bill
        $utility_bill_result = DKL_Rental_Image_Processor::processDocument($files['utility_bill'], 'utility_bill');
        if (is_wp_error($utility_bill_result)) {
            return $utility_bill_result;
        }
        
        // Create customer record
        $customer_data = array(
            'full_name' => sanitize_text_field($data['full_name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone']),
            'address' => sanitize_textarea_field($data['address']),
            'ic_passport_number' => sanitize_text_field($data['ic_passport_number']),
            'ic_passport_url' => $ic_passport_result['url'],
            'utility_bill_url' => $utility_bill_result['url'],
            'social_media_handle' => isset($data['social_media_handle']) ? sanitize_text_field($data['social_media_handle']) : null
        );
        
        $customer = DKL_Rental_Database::createCustomer($customer_data);
        
        if ($customer) {
            // Create WordPress user account
            $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
            if (!is_wp_error($user_id)) {
                // Update customer with user ID
                global $wpdb;
                $table = $wpdb->prefix . 'dkl_customers';
                $wpdb->update(
                    $table,
                    array('user_id' => $user_id),
                    array('id' => $customer->id),
                    array('%d'),
                    array('%d')
                );
                
                // Set user role
                $user = new WP_User($user_id);
                $user->set_role('dkl_customer');
                
                // Update user meta
                update_user_meta($user_id, 'first_name', $customer_data['full_name']);
                update_user_meta($user_id, 'dkl_customer_id', $customer->id);
            }
            
            return $customer;
        }
        
        return new WP_Error('create_failed', 'Failed to create customer');
    }
    
    public static function login($email, $password) {
        // Try WordPress authentication first
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            // Try custom customer authentication
            $customer = DKL_Rental_Database::getCustomerByEmail($email);
            if (!$customer) {
                return new WP_Error('invalid_credentials', 'Invalid email or password');
            }
            
            // For backward compatibility, check if password matches IC/Passport number
            if ($password === $customer->ic_passport_number) {
                return $customer;
            }
            
            return new WP_Error('invalid_credentials', 'Invalid email or password');
        }
        
        // Get customer data
        $customer_id = get_user_meta($user->ID, 'dkl_customer_id', true);
        if ($customer_id) {
            return DKL_Rental_Database::getCustomerById($customer_id);
        }
        
        return new WP_Error('no_customer_data', 'No customer data found');
    }
    
    public static function acceptTerms($customer_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_customers';
        
        return $wpdb->update(
            $table,
            array('has_accepted_terms' => 1),
            array('id' => $customer_id),
            array('%d'),
            array('%d')
        );
    }
    
    public static function updatePassword($customer_id, $new_password) {
        $customer = DKL_Rental_Database::getCustomerById($customer_id);
        if (!$customer) {
            return new WP_Error('customer_not_found', 'Customer not found');
        }
        
        // Update WordPress user password if exists
        if ($customer->user_id) {
            wp_set_password($new_password, $customer->user_id);
        }
        
        return true;
    }
    
    public static function getRentalHistory($customer_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_rentals';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE customer_id = %d ORDER BY created_at DESC",
            $customer_id
        ));
    }
    
    public static function getCurrentUser() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        $customer_id = get_user_meta($user->ID, 'dkl_customer_id', true);
        
        if ($customer_id) {
            return DKL_Rental_Database::getCustomerById($customer_id);
        }
        
        return false;
    }
}