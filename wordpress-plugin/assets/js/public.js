/**
 * Drive KL Rental System - Frontend JavaScript
 * Handles all frontend interactions and AJAX calls
 */

(function($) {
    'use strict';
    
    window.DKLRentalApp = {
        currentView: 'role-selection',
        currentCustomer: null,
        signaturePad: null,
        
        init: function() {
            this.bindEvents();
            this.initializeSignaturePad();
            this.calculateTotals();
        },
        
        bindEvents: function() {
            // View navigation
            $(document).on('click', '[data-view]', this.switchView.bind(this));
            
            // Form submissions
            $(document).on('submit', '#dkl-customer-login-form', this.handleCustomerLogin.bind(this));
            $(document).on('submit', '#dkl-customer-registration-form', this.handleCustomerRegistration.bind(this));
            $(document).on('submit', '#dkl-rental-booking-form', this.handleRentalBooking.bind(this));
            
            // Terms acceptance
            $(document).on('change', '#accept_terms', this.handleTermsAcceptance.bind(this));
            $(document).on('click', '#proceed_to_rental', this.proceedToRental.bind(this));
            
            // Date changes for total days calculation
            $(document).on('change', '#rental_start_date, #rental_end_date', this.calculateTotalDays.bind(this));
            
            // Price changes for total calculation
            $(document).on('input', '#rental_per_day, #rental_deposit, #rental_discount', this.calculateTotals.bind(this));
            
            // File upload previews
            $(document).on('change', 'input[type="file"]', this.handleFilePreview.bind(this));
            
            // Signature pad controls
            $(document).on('click', '#clear_signature', this.clearSignature.bind(this));
        },
        
        switchView: function(e) {
            e.preventDefault();
            const targetView = $(e.currentTarget).data('view');
            this.showView(targetView);
        },
        
        showView: function(viewName) {
            $('.dkl-view').removeClass('active');
            $('#dkl-' + viewName).addClass('active');
            this.currentView = viewName;
            
            // Initialize view-specific functionality
            if (viewName === 'rental-form') {
                this.initializeSignaturePad();
            }
        },
        
        handleCustomerLogin: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'dkl_customer_login');
            formData.append('nonce', dklRental.nonce);
            
            this.showLoading('Signing in...');
            
            $.ajax({
                url: dklRental.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.currentCustomer = response.data.customer;
                        this.showSuccess(response.data.message);
                        
                        if (response.data.customer.has_accepted_terms) {
                            this.showView('rental-form');
                        } else {
                            this.showView('terms-conditions');
                        }
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Login failed. Please try again.');
                }
            });
        },
        
        handleCustomerRegistration: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'dkl_customer_register');
            formData.append('nonce', dklRental.nonce);
            
            // Validate files
            const icPassport = formData.get('ic_passport');
            const utilityBill = formData.get('utility_bill');
            
            if (!icPassport || icPassport.size === 0) {
                this.showError('Please upload your IC/Passport image');
                return;
            }
            
            if (!utilityBill || utilityBill.size === 0) {
                this.showError('Please upload your utility bill image');
                return;
            }
            
            this.showLoading('Creating your account...');
            
            $.ajax({
                url: dklRental.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        this.showView('customer-login');
                        $('#dkl-customer-registration-form')[0].reset();
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Registration failed. Please try again.');
                }
            });
        },
        
        handleRentalBooking: function(e) {
            e.preventDefault();
            
            if (!this.currentCustomer) {
                this.showError('Please login first');
                return;
            }
            
            // Validate signature
            if (!this.signaturePad || this.signaturePad.isEmpty()) {
                this.showError('Please provide your signature');
                return;
            }
            
            const formData = new FormData(e.target);
            formData.append('action', 'dkl_create_rental');
            formData.append('nonce', dklRental.nonce);
            formData.append('signature_data', this.signaturePad.toDataURL());
            formData.append('total_days', $('#total_days_count').text());
            
            this.showLoading('Creating rental agreement...');
            
            $.ajax({
                url: dklRental.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Rental created successfully!');
                        this.generateAgreement(response.data.rental_id);
                    } else {
                        this.hideLoading();
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Failed to create rental. Please try again.');
                }
            });
        },
        
        generateAgreement: function(rentalId) {
            this.showLoading('Generating your agreement...');
            
            $.ajax({
                url: dklRental.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dkl_generate_agreement',
                    rental_id: rentalId,
                    nonce: dklRental.nonce
                },
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showAgreementSuccess(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Failed to generate agreement. Please try again.');
                }
            });
        },
        
        showAgreementSuccess: function(data) {
            const modal = $(`
                <div class="dkl-modal-overlay">
                    <div class="dkl-modal">
                        <div class="dkl-modal-header">
                            <h3>✅ Agreement Generated Successfully!</h3>
                        </div>
                        <div class="dkl-modal-content">
                            <p><strong>Customer:</strong> ${data.customer_name}</p>
                            <p><strong>Vehicle:</strong> ${data.vehicle}</p>
                            <p><strong>Period:</strong> ${data.period}</p>
                            <p><strong>Total:</strong> RM ${data.total}</p>
                            
                            ${data.email_sent ? 
                                '<p class="dkl-success">📧 Agreement has been sent to your email!</p>' :
                                '<p class="dkl-warning">⚠️ Email delivery unavailable, but you can download below.</p>'
                            }
                            
                            <div class="dkl-modal-actions">
                                <a href="${data.download_url}" class="dkl-btn dkl-btn-primary" download>
                                    📄 Download Agreement
                                </a>
                                <button class="dkl-btn dkl-btn-outline" onclick="DKLRentalApp.closeModal()">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
        },
        
        closeModal: function() {
            $('.dkl-modal-overlay').remove();
            // Reset form and go back to role selection
            $('#dkl-rental-booking-form')[0].reset();
            this.clearSignature();
            this.showView('role-selection');
        },
        
        handleTermsAcceptance: function(e) {
            const isChecked = $(e.target).is(':checked');
            $('#proceed_to_rental').prop('disabled', !isChecked);
        },
        
        proceedToRental: function(e) {
            e.preventDefault();
            
            if (!this.currentCustomer) {
                this.showError('Please login first');
                return;
            }
            
            // Accept terms for customer
            $.ajax({
                url: dklRental.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dkl_accept_terms',
                    customer_id: this.currentCustomer.id,
                    nonce: dklRental.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.currentCustomer.has_accepted_terms = true;
                        this.showView('rental-form');
                    }
                }
            });
        },
        
        calculateTotalDays: function() {
            const startDate = $('#rental_start_date').val();
            const endDate = $('#rental_end_date').val();
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const timeDiff = end.getTime() - start.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (daysDiff > 0) {
                    $('#total_days_count').text(daysDiff);
                    this.calculateTotals();
                } else {
                    $('#total_days_count').text('0');
                    this.showError('End date must be after start date');
                }
            }
        },
        
        calculateTotals: function() {
            const totalDays = parseInt($('#total_days_count').text()) || 0;
            const rentalPerDay = parseFloat($('#rental_per_day').val()) || 0;
            const deposit = parseFloat($('#rental_deposit').val()) || 0;
            const discount = parseFloat($('#rental_discount').val()) || 0;
            
            const subtotal = totalDays * rentalPerDay;
            const grandTotal = subtotal + deposit - discount;
            
            $('#price_subtotal').text('RM ' + subtotal.toFixed(2));
            $('#price_deposit').text('RM ' + deposit.toFixed(2));
            $('#price_discount').text('RM ' + discount.toFixed(2));
            $('#price_grand_total').text('RM ' + grandTotal.toFixed(2));
        },
        
        initializeSignaturePad: function() {
            const canvas = document.getElementById('signature_canvas');
            if (canvas && typeof SignaturePad !== 'undefined') {
                this.signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgba(255,255,255,1)',
                    penColor: 'rgb(0, 0, 0)'
                });
            }
        },
        
        clearSignature: function() {
            if (this.signaturePad) {
                this.signaturePad.clear();
            }
        },
        
        handleFilePreview: function(e) {
            const input = e.target;
            const file = input.files[0];
            
            if (file) {
                const uploadText = $(input).siblings('.dkl-file-upload-text');
                uploadText.find('span:first').text('✅');
                uploadText.find('span:nth-child(2)').text(file.name);
            }
        },
        
        showLoading: function(message) {
            if ($('.dkl-loading-overlay').length === 0) {
                $('body').append(`
                    <div class="dkl-loading-overlay">
                        <div class="dkl-loading-content">
                            <div class="dkl-spinner"></div>
                            <p>${message}</p>
                        </div>
                    </div>
                `);
            } else {
                $('.dkl-loading-content p').text(message);
            }
        },
        
        hideLoading: function() {
            $('.dkl-loading-overlay').remove();
        },
        
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },
        
        showError: function(message) {
            this.showNotification(message, 'error');
        },
        
        showNotification: function(message, type) {
            const notification = $(`
                <div class="dkl-notification dkl-notification-${type}">
                    <span>${message}</span>
                    <button class="dkl-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
            
            // Manual close
            notification.find('.dkl-notification-close').click(() => {
                notification.fadeOut(() => notification.remove());
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#dkl-rental-app').length > 0) {
            DKLRentalApp.init();
        }
    });
    
})(jQuery);