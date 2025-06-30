<?php

class DKL_Rental_Database {
    
    public function __construct() {
        // Database initialization handled in activation
    }
    
    public static function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Customers table
        $customers_table = $wpdb->prefix . 'dkl_customers';
        $customers_sql = "CREATE TABLE $customers_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            full_name varchar(255) NOT NULL,
            email varchar(100) NOT NULL UNIQUE,
            phone varchar(20) NOT NULL,
            address text NOT NULL,
            ic_passport_number varchar(50) DEFAULT NULL,
            ic_passport_url varchar(500) NOT NULL,
            utility_bill_url varchar(500) DEFAULT NULL,
            social_media_handle varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            has_accepted_terms tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";
        
        // Rentals table
        $rentals_table = $wpdb->prefix . 'dkl_rentals';
        $rentals_sql = "CREATE TABLE $rentals_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            vehicle varchar(100) NOT NULL,
            color varchar(50) NOT NULL,
            mileage_limit int(11) NOT NULL,
            extra_mileage_charge decimal(10,2) NOT NULL,
            fuel_level int(11) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            total_days int(11) NOT NULL,
            rental_per_day decimal(10,2) NOT NULL,
            deposit decimal(10,2) NOT NULL,
            discount decimal(10,2) NOT NULL DEFAULT 0,
            grand_total decimal(10,2) NOT NULL,
            vehicle_photos longtext DEFAULT NULL,
            payment_proof_url varchar(500) DEFAULT NULL,
            signature_url varchar(500) DEFAULT NULL,
            agreement_pdf_url varchar(500) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY start_date (start_date)
        ) $charset_collate;";
        
        // Staff table
        $staff_table = $wpdb->prefix . 'dkl_staff';
        $staff_sql = "CREATE TABLE $staff_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            username varchar(50) NOT NULL UNIQUE,
            hashed_password varchar(255) NOT NULL,
            role varchar(20) NOT NULL DEFAULT 'staff',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY username (username)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($customers_sql);
        dbDelta($rentals_sql);
        dbDelta($staff_sql);
        
        // Insert default staff user
        self::insertDefaultStaff();
    }
    
    private static function insertDefaultStaff() {
        global $wpdb;
        
        $staff_table = $wpdb->prefix . 'dkl_staff';
        
        // Check if staff already exists
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $staff_table WHERE username = %s", 'Akib'));
        
        if (!$existing) {
            $hashed_password = password_hash('1234', PASSWORD_DEFAULT);
            $wpdb->insert(
                $staff_table,
                array(
                    'username' => 'Akib',
                    'hashed_password' => $hashed_password,
                    'role' => 'admin'
                ),
                array('%s', '%s', '%s')
            );
        }
    }
    
    public static function getCustomers($limit = 100, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_customers';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }
    
    public static function getCustomerById($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_customers';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function getCustomerByEmail($email) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_customers';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email = %s", $email));
    }
    
    public static function createCustomer($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_customers';
        
        $result = $wpdb->insert(
            $table,
            array(
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'ic_passport_number' => $data['ic_passport_number'],
                'ic_passport_url' => $data['ic_passport_url'],
                'utility_bill_url' => $data['utility_bill_url'],
                'social_media_handle' => $data['social_media_handle'],
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return self::getCustomerById($wpdb->insert_id);
        }
        
        return false;
    }
    
    public static function updateCustomerStatus($id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_customers';
        
        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    public static function getRentals($limit = 100, $offset = 0) {
        global $wpdb;
        $rentals_table = $wpdb->prefix . 'dkl_rentals';
        $customers_table = $wpdb->prefix . 'dkl_customers';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, c.full_name as customer_name, c.email as customer_email 
             FROM $rentals_table r 
             LEFT JOIN $customers_table c ON r.customer_id = c.id 
             ORDER BY r.created_at DESC 
             LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }
    
    public static function getRentalById($id) {
        global $wpdb;
        $rentals_table = $wpdb->prefix . 'dkl_rentals';
        $customers_table = $wpdb->prefix . 'dkl_customers';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, c.full_name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address
             FROM $rentals_table r 
             LEFT JOIN $customers_table c ON r.customer_id = c.id 
             WHERE r.id = %d",
            $id
        ));
    }
    
    public static function createRental($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_rentals';
        
        $result = $wpdb->insert(
            $table,
            array(
                'customer_id' => $data['customer_id'],
                'vehicle' => $data['vehicle'],
                'color' => $data['color'],
                'mileage_limit' => $data['mileage_limit'],
                'extra_mileage_charge' => $data['extra_mileage_charge'],
                'fuel_level' => $data['fuel_level'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_days' => $data['total_days'],
                'rental_per_day' => $data['rental_per_day'],
                'deposit' => $data['deposit'],
                'discount' => $data['discount'],
                'grand_total' => $data['grand_total'],
                'vehicle_photos' => maybe_serialize($data['vehicle_photos']),
                'payment_proof_url' => $data['payment_proof_url'],
                'signature_url' => $data['signature_url'],
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%d', '%f', '%d', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return self::getRentalById($wpdb->insert_id);
        }
        
        return false;
    }
    
    public static function updateRentalPdf($id, $pdf_url) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_rentals';
        
        return $wpdb->update(
            $table,
            array('agreement_pdf_url' => $pdf_url),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    public static function getStaffByUsername($username) {
        global $wpdb;
        $table = $wpdb->prefix . 'dkl_staff';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE username = %s", $username));
    }
    
    public static function verifyStaffPassword($username, $password) {
        $staff = self::getStaffByUsername($username);
        if ($staff && password_verify($password, $staff->hashed_password)) {
            return $staff;
        }
        return false;
    }
}