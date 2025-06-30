<?php

class DKL_Rental_Management {
    
    public static function createRental($rental_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dkl_rentals';
        
        // Sanitize and prepare rental data
        $prepared_data = array(
            'customer_id' => intval($rental_data['customer_id']),
            'customer_name' => sanitize_text_field($rental_data['customer_name']),
            'customer_email' => sanitize_email($rental_data['customer_email']),
            'customer_phone' => sanitize_text_field($rental_data['customer_phone']),
            'vehicle' => sanitize_text_field($rental_data['vehicle']),
            'color' => sanitize_text_field($rental_data['color']),
            'start_date' => sanitize_text_field($rental_data['start_date']),
            'end_date' => sanitize_text_field($rental_data['end_date']),
            'total_days' => intval($rental_data['total_days']),
            'rental_per_day' => floatval($rental_data['rental_per_day']),
            'deposit' => floatval($rental_data['deposit']),
            'discount' => floatval($rental_data['discount']),
            'subtotal' => floatval($rental_data['subtotal']),
            'grand_total' => floatval($rental_data['grand_total']),
            'pickup_location' => sanitize_text_field($rental_data['pickup_location']),
            'return_location' => sanitize_text_field($rental_data['return_location']),
            'fuel_level_pickup' => intval($rental_data['fuel_level_pickup']),
            'fuel_level_return' => intval($rental_data['fuel_level_return']),
            'mileage_pickup' => intval($rental_data['mileage_pickup']),
            'mileage_return' => intval($rental_data['mileage_return']),
            'payment_method' => sanitize_text_field($rental_data['payment_method']),
            'payment_proof_url' => esc_url_raw($rental_data['payment_proof_url']),
            'signature_url' => esc_url_raw($rental_data['signature_url']),
            'instagram_handle' => sanitize_text_field($rental_data['instagram_handle']),
            'facebook_handle' => sanitize_text_field($rental_data['facebook_handle']),
            'agreement_pdf_url' => esc_url_raw($rental_data['agreement_pdf_url'] ?? ''),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        // Handle vehicle photos
        if (isset($rental_data['vehicle_photos']) && is_array($rental_data['vehicle_photos'])) {
            $prepared_data['vehicle_photos'] = wp_json_encode($rental_data['vehicle_photos']);
        }
        
        $result = $wpdb->insert($table_name, $prepared_data);
        
        if ($result === false) {
            return new WP_Error('rental_creation_failed', 'Failed to create rental record');
        }
        
        return $wpdb->insert_id;
    }
    
    public static function getRentalById($rental_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dkl_rentals';
        
        $rental = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $rental_id
        ));
        
        if ($rental && !empty($rental->vehicle_photos)) {
            $rental->vehicle_photos = json_decode($rental->vehicle_photos, true);
        }
        
        return $rental;
    }
    
    public static function updateRental($rental_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dkl_rentals';
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $rental_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    public static function updateRentalAgreement($rental_id, $pdf_url) {
        return self::updateRental($rental_id, array(
            'agreement_pdf_url' => esc_url_raw($pdf_url),
            'status' => 'confirmed'
        ));
    }
    
    public static function getAllRentals($limit = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dkl_rentals';
        
        $query = "SELECT * FROM $table_name ORDER BY created_at DESC";
        
        if ($limit) {
            $query .= $wpdb->prepare(" LIMIT %d", $limit);
        }
        
        $rentals = $wpdb->get_results($query);
        
        // Decode vehicle photos for each rental
        foreach ($rentals as $rental) {
            if (!empty($rental->vehicle_photos)) {
                $rental->vehicle_photos = json_decode($rental->vehicle_photos, true);
            }
        }
        
        return $rentals;
    }
    
    public static function getRentalsByCustomer($customer_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dkl_rentals';
        
        $rentals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE customer_id = %d ORDER BY created_at DESC",
            $customer_id
        ));
        
        foreach ($rentals as $rental) {
            if (!empty($rental->vehicle_photos)) {
                $rental->vehicle_photos = json_decode($rental->vehicle_photos, true);
            }
        }
        
        return $rentals;
    }
    
    public static function updateRentalStatus($rental_id, $status) {
        return self::updateRental($rental_id, array(
            'status' => sanitize_text_field($status)
        ));
    }
    
    public static function deleteRental($rental_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dkl_rentals';
        
        return $wpdb->delete($table_name, array('id' => $rental_id), array('%d'));
    }
    
    public static function getRevenueStats($period = 'month') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dkl_rentals';
        
        switch ($period) {
            case 'today':
                $where_clause = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where_clause = "WEEK(created_at) = WEEK(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
                break;
            case 'month':
                $where_clause = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
                break;
            case 'year':
                $where_clause = "YEAR(created_at) = YEAR(CURDATE())";
                break;
            default:
                $where_clause = "1=1";
        }
        
        $result = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_rentals,
                SUM(grand_total) as total_revenue,
                AVG(grand_total) as average_rental
            FROM $table_name 
            WHERE $where_clause AND status != 'cancelled'
        ");
        
        return $result;
    }
}