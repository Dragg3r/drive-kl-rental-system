<?php

class DKL_Rental_Public {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('init', array($this, 'initPublic'));
        add_action('wp_head', array($this, 'addSignaturePadScript'));
    }
    
    public function initPublic() {
        // Start session for customer management
        if (!session_id()) {
            session_start();
        }
        
        // Add custom user role for customers
        add_role(
            'dkl_customer',
            'DKL Customer',
            array(
                'read' => true,
                'dkl_book_rental' => true
            )
        );
    }
    
    public function enqueueScripts() {
        // Only load on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dkl_rental_system')) {
            
            // Main plugin styles and scripts
            wp_enqueue_style(
                'dkl-rental-public',
                DKL_RENTAL_PLUGIN_URL . 'assets/css/public.css',
                array(),
                DKL_RENTAL_VERSION
            );
            
            wp_enqueue_script(
                'dkl-rental-public',
                DKL_RENTAL_PLUGIN_URL . 'assets/js/public.js',
                array('jquery'),
                DKL_RENTAL_VERSION,
                true
            );
            
            // Signature pad library from CDN
            wp_enqueue_script(
                'signature-pad',
                'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js',
                array(),
                '4.0.0',
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('dkl-rental-public', 'dklRental', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dkl_rental_nonce'),
                'pluginUrl' => DKL_RENTAL_PLUGIN_URL,
                'currentUser' => $this->getCurrentUserData()
            ));
        }
    }
    
    public function addSignaturePadScript() {
        // Add any additional head scripts if needed
        echo '<!-- Drive KL Rental System Loaded -->';
    }
    
    private function getCurrentUserData() {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $customer_id = get_user_meta($user->ID, 'dkl_customer_id', true);
            
            if ($customer_id) {
                $customer = DKL_Rental_Database::getCustomerById($customer_id);
                if ($customer) {
                    return array(
                        'id' => $customer->id,
                        'full_name' => $customer->full_name,
                        'email' => $customer->email,
                        'has_accepted_terms' => $customer->has_accepted_terms
                    );
                }
            }
        }
        
        return null;
    }
}