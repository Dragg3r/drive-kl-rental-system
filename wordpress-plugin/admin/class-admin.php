<?php

class DKL_Rental_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_init', array($this, 'initAdmin'));
    }
    
    public function addAdminMenu() {
        // Main menu
        add_menu_page(
            'Drive KL Rental',
            'Drive KL Rental',
            'manage_options',
            'dkl-rental',
            array($this, 'dashboardPage'),
            'dashicons-car',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'dkl-rental',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'dkl-rental',
            array($this, 'dashboardPage')
        );
        
        add_submenu_page(
            'dkl-rental',
            'Customers',
            'Customers',
            'manage_options',
            'dkl-rental-customers',
            array($this, 'customersPage')
        );
        
        add_submenu_page(
            'dkl-rental',
            'Rentals',
            'Rentals',
            'manage_options',
            'dkl-rental-rentals',
            array($this, 'rentalsPage')
        );
        
        add_submenu_page(
            'dkl-rental',
            'Settings',
            'Settings',
            'manage_options',
            'dkl-rental-settings',
            array($this, 'settingsPage')
        );
    }
    
    public function initAdmin() {
        // Register settings
        register_setting('dkl_rental_settings', 'dkl_rental_options');
        
        // Add settings sections
        add_settings_section(
            'dkl_rental_general',
            'General Settings',
            array($this, 'generalSectionCallback'),
            'dkl_rental_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'email_notifications',
            'Email Notifications',
            array($this, 'emailNotificationsCallback'),
            'dkl_rental_settings',
            'dkl_rental_general'
        );
        
        add_settings_field(
            'auto_approve_customers',
            'Auto Approve Customers',
            array($this, 'autoApproveCallback'),
            'dkl_rental_settings',
            'dkl_rental_general'
        );
        
        add_settings_field(
            'watermark_documents',
            'Watermark Documents',
            array($this, 'watermarkCallback'),
            'dkl_rental_settings',
            'dkl_rental_general'
        );
    }
    
    public function dashboardPage() {
        // Get statistics
        $stats = $this->getSystemStats();
        
        ?>
        <div class="wrap">
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
                    <span class="dkl-stat-number"><?php echo $stats['total_customers']; ?></span>
                </div>
                
                <div class="dkl-stat-card">
                    <h3>Active Rentals</h3>
                    <span class="dkl-stat-number"><?php echo $stats['active_rentals']; ?></span>
                </div>
                
                <div class="dkl-stat-card">
                    <h3>Total Rentals</h3>
                    <span class="dkl-stat-number"><?php echo $stats['total_rentals']; ?></span>
                </div>
                
                <div class="dkl-stat-card">
                    <h3>Monthly Revenue</h3>
                    <span class="dkl-stat-number">RM <?php echo number_format($stats['monthly_revenue'], 2); ?></span>
                </div>
            </div>
            
            <div class="dkl-admin-grid">
                <div class="dkl-admin-section">
                    <h3>Recent Rentals</h3>
                    <div class="dkl-table-container">
                        <?php $this->renderRecentRentals(); ?>
                    </div>
                </div>
                
                <div class="dkl-admin-section">
                    <h3>Quick Actions</h3>
                    <div class="dkl-quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=dkl-rental-customers'); ?>" class="button button-primary">
                            Manage Customers
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=dkl-rental-rentals'); ?>" class="button button-primary">
                            View All Rentals
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=dkl-rental-settings'); ?>" class="button">
                            Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .dkl-admin-header {
            background: linear-gradient(135deg, #d32f2f, #f44336);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .dkl-branding h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .dkl-branding p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .dkl-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .dkl-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .dkl-stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .dkl-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #d32f2f;
        }
        
        .dkl-admin-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .dkl-admin-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dkl-admin-section h3 {
            margin-top: 0;
            color: #333;
        }
        
        .dkl-quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .dkl-table-container {
            overflow-x: auto;
        }
        
        .dkl-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .dkl-table th,
        .dkl-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .dkl-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        </style>
        <?php
    }
    
    public function customersPage() {
        ?>
        <div class="wrap">
            <h1>Customer Management</h1>
            
            <div id="dkl-customers-container">
                <div class="dkl-loading">Loading customers...</div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            loadCustomers();
            
            function loadCustomers() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dkl_get_customers',
                        nonce: '<?php echo wp_create_nonce('dkl_rental_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            renderCustomersTable(response.data);
                        } else {
                            $('#dkl-customers-container').html('<div class="error">Failed to load customers</div>');
                        }
                    }
                });
            }
            
            function renderCustomersTable(customers) {
                let html = '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr>';
                html += '<th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Registered</th><th>Actions</th>';
                html += '</tr></thead><tbody>';
                
                customers.forEach(function(customer) {
                    html += '<tr>';
                    html += '<td>' + customer.id + '</td>';
                    html += '<td>' + customer.full_name + '</td>';
                    html += '<td>' + customer.email + '</td>';
                    html += '<td>' + customer.phone + '</td>';
                    html += '<td><span class="status-' + customer.status + '">' + customer.status + '</span></td>';
                    html += '<td>' + new Date(customer.created_at).toLocaleDateString() + '</td>';
                    html += '<td>';
                    html += '<button class="button" onclick="viewCustomer(' + customer.id + ')">View</button> ';
                    if (customer.status === 'active') {
                        html += '<button class="button" onclick="updateCustomerStatus(' + customer.id + ', \'blacklisted\')">Blacklist</button>';
                    } else {
                        html += '<button class="button" onclick="updateCustomerStatus(' + customer.id + ', \'active\')">Activate</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#dkl-customers-container').html(html);
            }
            
            window.updateCustomerStatus = function(customerId, status) {
                if (confirm('Are you sure you want to update this customer status?')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'dkl_update_customer_status',
                            customer_id: customerId,
                            status: status,
                            nonce: '<?php echo wp_create_nonce('dkl_rental_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                loadCustomers(); // Reload table
                            } else {
                                alert('Failed to update customer status');
                            }
                        }
                    });
                }
            };
        });
        </script>
        
        <style>
        .status-active { color: green; font-weight: bold; }
        .status-blacklisted { color: red; font-weight: bold; }
        .dkl-loading { text-align: center; padding: 20px; }
        </style>
        <?php
    }
    
    public function rentalsPage() {
        ?>
        <div class="wrap">
            <h1>Rental Management</h1>
            
            <div id="dkl-rentals-container">
                <div class="dkl-loading">Loading rentals...</div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            loadRentals();
            
            function loadRentals() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dkl_get_rentals',
                        nonce: '<?php echo wp_create_nonce('dkl_rental_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            renderRentalsTable(response.data);
                        } else {
                            $('#dkl-rentals-container').html('<div class="error">Failed to load rentals</div>');
                        }
                    }
                });
            }
            
            function renderRentalsTable(rentals) {
                let html = '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr>';
                html += '<th>ID</th><th>Customer</th><th>Vehicle</th><th>Period</th><th>Total</th><th>Status</th><th>Actions</th>';
                html += '</tr></thead><tbody>';
                
                rentals.forEach(function(rental) {
                    html += '<tr>';
                    html += '<td>' + rental.id + '</td>';
                    html += '<td>' + rental.customer_name + '</td>';
                    html += '<td>' + rental.vehicle + ' (' + rental.color + ')</td>';
                    html += '<td>' + new Date(rental.start_date).toLocaleDateString() + ' - ' + new Date(rental.end_date).toLocaleDateString() + '</td>';
                    html += '<td>RM ' + parseFloat(rental.grand_total).toFixed(2) + '</td>';
                    html += '<td><span class="status-' + rental.status + '">' + rental.status + '</span></td>';
                    html += '<td>';
                    if (rental.agreement_pdf_url) {
                        html += '<a href="' + rental.agreement_pdf_url + '" class="button" target="_blank">View Agreement</a>';
                    } else {
                        html += '<button class="button" onclick="generateAgreement(' + rental.id + ')">Generate Agreement</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#dkl-rentals-container').html(html);
            }
        });
        </script>
        <?php
    }
    
    public function settingsPage() {
        ?>
        <div class="wrap">
            <h1>Drive KL Rental Settings</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('dkl_rental_settings');
                do_settings_sections('dkl_rental_settings');
                submit_button();
                ?>
            </form>
            
            <div class="dkl-settings-info">
                <h3>Plugin Information</h3>
                <table class="form-table">
                    <tr>
                        <th>Version</th>
                        <td><?php echo DKL_RENTAL_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th>Database Status</th>
                        <td><?php echo $this->checkDatabaseStatus(); ?></td>
                    </tr>
                    <tr>
                        <th>Upload Directory</th>
                        <td><?php echo wp_upload_dir()['basedir'] . '/dkl-rental'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function generalSectionCallback() {
        echo '<p>Configure general settings for the Drive KL Rental System.</p>';
    }
    
    public function emailNotificationsCallback() {
        $options = get_option('dkl_rental_options');
        $value = isset($options['email_notifications']) ? $options['email_notifications'] : true;
        echo '<input type="checkbox" name="dkl_rental_options[email_notifications]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>Enable email notifications for new rentals and registrations</label>';
    }
    
    public function autoApproveCallback() {
        $options = get_option('dkl_rental_options');
        $value = isset($options['auto_approve_customers']) ? $options['auto_approve_customers'] : false;
        echo '<input type="checkbox" name="dkl_rental_options[auto_approve_customers]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>Automatically approve new customer registrations</label>';
    }
    
    public function watermarkCallback() {
        $options = get_option('dkl_rental_options');
        $value = isset($options['watermark_documents']) ? $options['watermark_documents'] : true;
        echo '<input type="checkbox" name="dkl_rental_options[watermark_documents]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>Add watermark to uploaded documents</label>';
    }
    
    private function getSystemStats() {
        global $wpdb;
        
        $customers_table = $wpdb->prefix . 'dkl_customers';
        $rentals_table = $wpdb->prefix . 'dkl_rentals';
        
        $total_customers = $wpdb->get_var("SELECT COUNT(*) FROM $customers_table");
        $total_rentals = $wpdb->get_var("SELECT COUNT(*) FROM $rentals_table");
        $active_rentals = $wpdb->get_var("SELECT COUNT(*) FROM $rentals_table WHERE status = 'active'");
        
        $monthly_revenue = $wpdb->get_var("SELECT SUM(grand_total) FROM $rentals_table WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        
        return array(
            'total_customers' => $total_customers ?: 0,
            'total_rentals' => $total_rentals ?: 0,
            'active_rentals' => $active_rentals ?: 0,
            'monthly_revenue' => $monthly_revenue ?: 0
        );
    }
    
    private function renderRecentRentals() {
        $rentals = DKL_Rental_Database::getRentals(5);
        
        if (empty($rentals)) {
            echo '<p>No rentals found.</p>';
            return;
        }
        
        echo '<table class="dkl-table">';
        echo '<thead><tr><th>Customer</th><th>Vehicle</th><th>Date</th><th>Amount</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($rentals as $rental) {
            echo '<tr>';
            echo '<td>' . esc_html($rental->customer_name) . '</td>';
            echo '<td>' . esc_html($rental->vehicle) . '</td>';
            echo '<td>' . date('M j, Y', strtotime($rental->created_at)) . '</td>';
            echo '<td>RM ' . number_format($rental->grand_total, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function checkDatabaseStatus() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'dkl_customers',
            $wpdb->prefix . 'dkl_rentals',
            $wpdb->prefix . 'dkl_staff'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return '<span style="color: red;">Missing tables - Please deactivate and reactivate the plugin</span>';
            }
        }
        
        return '<span style="color: green;">All tables exist</span>';
    }
}