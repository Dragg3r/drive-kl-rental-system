/**
 * Drive KL Rental System - Admin JavaScript
 * Handles admin dashboard functionality
 */

(function($) {
    'use strict';
    
    window.DKLRentalAdmin = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Customer management
            $(document).on('click', '.dkl-update-customer-status', this.updateCustomerStatus.bind(this));
            $(document).on('click', '.dkl-view-customer', this.viewCustomer.bind(this));
            
            // Rental management
            $(document).on('click', '.dkl-generate-agreement', this.generateAgreement.bind(this));
            $(document).on('click', '.dkl-update-rental-status', this.updateRentalStatus.bind(this));
            
            // Refresh buttons
            $(document).on('click', '.dkl-refresh-data', this.refreshData.bind(this));
        },
        
        updateCustomerStatus: function(e) {
            e.preventDefault();
            
            const customerId = $(e.currentTarget).data('customer-id');
            const newStatus = $(e.currentTarget).data('status');
            
            if (!confirm('Are you sure you want to update this customer status?')) {
                return;
            }
            
            $.ajax({
                url: dklRentalAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dkl_update_customer_status',
                    customer_id: customerId,
                    status: newStatus,
                    nonce: dklRentalAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Simple refresh for now
                    } else {
                        alert('Failed to update customer status: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating customer status');
                }
            });
        },
        
        viewCustomer: function(e) {
            e.preventDefault();
            
            const customerId = $(e.currentTarget).data('customer-id');
            
            // For now, just show an alert - in full version would open modal
            alert('Customer details for ID: ' + customerId + '\n\nThis would open a detailed view in the full version.');
        },
        
        generateAgreement: function(e) {
            e.preventDefault();
            
            const rentalId = $(e.currentTarget).data('rental-id');
            
            $(e.currentTarget).prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: dklRentalAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dkl_generate_agreement',
                    rental_id: rentalId,
                    nonce: dklRentalAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Agreement generated successfully!');
                        location.reload();
                    } else {
                        alert('Failed to generate agreement: ' + response.data.message);
                        $(e.currentTarget).prop('disabled', false).text('Generate Agreement');
                    }
                },
                error: function() {
                    alert('An error occurred while generating agreement');
                    $(e.currentTarget).prop('disabled', false).text('Generate Agreement');
                }
            });
        },
        
        updateRentalStatus: function(e) {
            e.preventDefault();
            
            const rentalId = $(e.currentTarget).data('rental-id');
            const newStatus = $(e.currentTarget).data('status');
            
            if (!confirm('Are you sure you want to update this rental status?')) {
                return;
            }
            
            $.ajax({
                url: dklRentalAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dkl_update_rental_status',
                    rental_id: rentalId,
                    status: newStatus,
                    nonce: dklRentalAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to update rental status: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating rental status');
                }
            });
        },
        
        refreshData: function(e) {
            e.preventDefault();
            location.reload();
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.dkl-admin-page').length > 0) {
            DKLRentalAdmin.init();
        }
    });
    
})(jQuery);