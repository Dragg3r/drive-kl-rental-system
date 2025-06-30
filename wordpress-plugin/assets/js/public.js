/**
 * Drive KL Rental System - Frontend JavaScript
 * Handles all frontend interactions and AJAX calls
 */

(function($) {
    'use strict';
    
    window.DKLRentalApp = {
        currentView: 'role-selection',
        currentCustomer: null,
        currentStaff: null,
        signaturePad: null,
        
        init: function() {
            this.bindEvents();
            this.loadSignaturePad();
        },
        
        bindEvents: function() {
            // View navigation
            $(document).on('click', '[data-view]', this.switchView.bind(this));
            
            // Form submissions
            $(document).on('submit', '#dkl-customer-login-form', this.handleCustomerLogin.bind(this));
            $(document).on('submit', '#dkl-customer-registration-form', this.handleCustomerRegistration.bind(this));
            $(document).on('submit', '#dkl-staff-login-form', this.handleStaffLogin.bind(this));
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
                            this.showRentalForm();
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
        
        handleStaffLogin: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'dkl_staff_login');
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
                        this.currentStaff = response.data.staff;
                        this.showSuccess(response.data.message);
                        this.showStaffDashboard();
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Staff login failed. Please try again.');
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
                        this.showRentalForm();
                    }
                }
            });
        },
        
        showRentalForm: function() {
            const rentalFormHtml = this.generateRentalFormHtml();
            $('#dkl-rental-form').html(rentalFormHtml);
            this.showView('rental-form');
            this.loadSignaturePad();
        },
        
        generateRentalFormHtml: function() {
            return `
                <div class="dkl-form-card dkl-wide-card">
                    <h2>Rental Booking Form</h2>
                    <p>Please fill in all details for your vehicle rental.</p>
                    
                    <form id="dkl-rental-booking-form" class="dkl-form" enctype="multipart/form-data">
                        <div class="dkl-form-section">
                            <h3>Vehicle Details</h3>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label for="rental_vehicle">Vehicle *</label>
                                    <select id="rental_vehicle" name="vehicle" required>
                                        <option value="">Select Vehicle</option>
                                        <option value="AMG C63">AMG C63</option>
                                        <option value="AMG G63">AMG G63</option>
                                        <option value="BMW M3">BMW M3</option>
                                        <option value="Audi RS6">Audi RS6</option>
                                        <option value="BMW 520i">BMW 520i</option>
                                        <option value="Mercedes E200">Mercedes E200</option>
                                    </select>
                                </div>
                                <div class="dkl-form-group">
                                    <label for="rental_color">Color *</label>
                                    <select id="rental_color" name="color" required>
                                        <option value="">Select Color</option>
                                        <option value="Black">Black</option>
                                        <option value="White">White</option>
                                        <option value="Silver">Silver</option>
                                        <option value="Red">Red</option>
                                        <option value="Blue">Blue</option>
                                        <option value="Gray">Gray</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dkl-form-section">
                            <h3>Rental Period</h3>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label for="rental_start_date">Start Date *</label>
                                    <input type="date" id="rental_start_date" name="start_date" required>
                                </div>
                                <div class="dkl-form-group">
                                    <label for="rental_end_date">End Date *</label>
                                    <input type="date" id="rental_end_date" name="end_date" required>
                                </div>
                            </div>
                            
                            <div class="dkl-info-display">
                                Total Days: <span id="total_days_count">0</span> days
                            </div>
                        </div>
                        
                        <div class="dkl-form-section">
                            <h3>Pricing Details</h3>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label for="rental_per_day">Rental Per Day (RM) *</label>
                                    <input type="number" id="rental_per_day" name="rental_per_day" step="0.01" required>
                                </div>
                                <div class="dkl-form-group">
                                    <label for="rental_deposit">Deposit (RM) *</label>
                                    <input type="number" id="rental_deposit" name="deposit" step="0.01" required>
                                </div>
                            </div>
                            
                            <div class="dkl-form-group">
                                <label for="rental_discount">Discount (RM)</label>
                                <input type="number" id="rental_discount" name="discount" step="0.01" value="0">
                            </div>
                            
                            <div class="dkl-pricing-summary">
                                <div class="dkl-price-row">
                                    <span>Subtotal:</span>
                                    <span id="price_subtotal">RM 0.00</span>
                                </div>
                                <div class="dkl-price-row">
                                    <span>Deposit:</span>
                                    <span id="price_deposit">RM 0.00</span>
                                </div>
                                <div class="dkl-price-row">
                                    <span>Discount:</span>
                                    <span id="price_discount">RM 0.00</span>
                                </div>
                                <div class="dkl-price-row dkl-total">
                                    <span><strong>Grand Total:</strong></span>
                                    <span id="price_grand_total"><strong>RM 0.00</strong></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dkl-form-section">
                            <h3>Vehicle Photos (Upload up to 7 photos)</h3>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label>Photo 1</label>
                                    <div class="dkl-file-upload">
                                        <input type="file" name="vehicle_photo_1" accept="image/*">
                                        <div class="dkl-file-upload-text">
                                            <span class="dkl-icon">📷</span>
                                            <span>Upload Photo 1</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dkl-form-group">
                                    <label>Photo 2</label>
                                    <div class="dkl-file-upload">
                                        <input type="file" name="vehicle_photo_2" accept="image/*">
                                        <div class="dkl-file-upload-text">
                                            <span class="dkl-icon">📷</span>
                                            <span>Upload Photo 2</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label>Photo 3</label>
                                    <div class="dkl-file-upload">
                                        <input type="file" name="vehicle_photo_3" accept="image/*">
                                        <div class="dkl-file-upload-text">
                                            <span class="dkl-icon">📷</span>
                                            <span>Upload Photo 3</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dkl-form-group">
                                    <label>Photo 4</label>
                                    <div class="dkl-file-upload">
                                        <input type="file" name="vehicle_photo_4" accept="image/*">
                                        <div class="dkl-file-upload-text">
                                            <span class="dkl-icon">📷</span>
                                            <span>Upload Photo 4</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label>Photo 5</label>
                                    <div class="dkl-file-upload">
                                        <input type="file" name="vehicle_photo_5" accept="image/*">
                                        <div class="dkl-file-upload-text">
                                            <span class="dkl-icon">📷</span>
                                            <span>Upload Photo 5</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dkl-form-group">
                                    <label>Photo 6</label>
                                    <div class="dkl-file-upload">
                                        <input type="file" name="vehicle_photo_6" accept="image/*">
                                        <div class="dkl-file-upload-text">
                                            <span class="dkl-icon">📷</span>
                                            <span>Upload Photo 6</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dkl-form-group">
                                <label>Photo 7</label>
                                <div class="dkl-file-upload">
                                    <input type="file" name="vehicle_photo_7" accept="image/*">
                                    <div class="dkl-file-upload-text">
                                        <span class="dkl-icon">📷</span>
                                        <span>Upload Photo 7</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dkl-form-section">
                            <h3>Additional Details</h3>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label for="pickup_location">Pickup Location</label>
                                    <input type="text" id="pickup_location" name="pickup_location">
                                </div>
                                <div class="dkl-form-group">
                                    <label for="return_location">Return Location</label>
                                    <input type="text" id="return_location" name="return_location">
                                </div>
                            </div>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label for="fuel_level_pickup">Fuel Level at Pickup</label>
                                    <select id="fuel_level_pickup" name="fuel_level_pickup">
                                        <option value="1">1/4 Tank</option>
                                        <option value="2">1/2 Tank</option>
                                        <option value="3">3/4 Tank</option>
                                        <option value="4" selected>Full Tank</option>
                                    </select>
                                </div>
                                <div class="dkl-form-group">
                                    <label for="fuel_level_return">Expected Fuel Level at Return</label>
                                    <select id="fuel_level_return" name="fuel_level_return">
                                        <option value="1">1/4 Tank</option>
                                        <option value="2">1/2 Tank</option>
                                        <option value="3">3/4 Tank</option>
                                        <option value="4" selected>Full Tank</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label for="mileage_pickup">Mileage at Pickup</label>
                                    <input type="number" id="mileage_pickup" name="mileage_pickup">
                                </div>
                                <div class="dkl-form-group">
                                    <label for="mileage_return">Expected Mileage at Return</label>
                                    <input type="number" id="mileage_return" name="mileage_return">
                                </div>
                            </div>
                        </div>
                        
                        <div class="dkl-form-section">
                            <h3>Payment Information</h3>
                            
                            <div class="dkl-form-row">
                                <div class="dkl-form-group">
                                    <label for="payment_method">Payment Method</label>
                                    <select id="payment_method" name="payment_method">
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cash">Cash</option>
                                        <option value="online_banking">Online Banking</option>
                                    </select>
                                </div>
                                <div class="dkl-form-group">
                                    <label for="payment_proof">Payment Proof</label>
                                    <div class="dkl-file-upload">
                                        <input type="file" id="payment_proof" name="payment_proof" accept="image/*">
                                        <div class="dkl-file-upload-text">
                                            <span class="dkl-icon">💳</span>
                                            <span>Upload Payment Receipt</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dkl-form-section">
                            <h3>Digital Signature</h3>
                            
                            <div class="dkl-signature-container">
                                <canvas id="signature_canvas" width="500" height="200"></canvas>
                                
                                <div class="dkl-signature-controls">
                                    <button type="button" id="clear_signature" class="dkl-btn dkl-btn-outline">
                                        Clear Signature
                                    </button>
                                </div>
                                
                                <p class="dkl-signature-note">
                                    Please sign above to confirm your rental agreement
                                </p>
                            </div>
                        </div>
                        
                        <button type="submit" class="dkl-btn dkl-btn-primary dkl-btn-full">
                            Submit Rental Request
                        </button>
                        
                        <div class="dkl-form-footer">
                            <a href="#" data-view="role-selection">← Back to Main Menu</a>
                        </div>
                    </form>
                </div>
            `;
        },
        
        showStaffDashboard: function() {
            const staffDashboardHtml = this.generateStaffDashboardHtml();
            $('#dkl-staff-dashboard').html(staffDashboardHtml);
            this.showView('staff-dashboard');
        },
        
        generateStaffDashboardHtml: function() {
            return `
                <div class="dkl-form-card dkl-wide-card">
                    <h2>Staff Dashboard</h2>
                    <p>Welcome back, ${this.currentStaff.username}!</p>
                    
                    <div class="dkl-admin-section">
                        <h3>Quick Actions</h3>
                        <div class="dkl-quick-actions">
                            <button class="dkl-btn dkl-btn-primary" onclick="window.open('${dklRental.pluginUrl}/../wp-admin/admin.php?page=dkl-rental', '_blank')">
                                Open Admin Dashboard
                            </button>
                            <button class="dkl-btn dkl-btn-outline" data-view="role-selection">
                                Logout
                            </button>
                        </div>
                    </div>
                    
                    <div class="dkl-admin-section">
                        <h3>Staff Features</h3>
                        <ul>
                            <li>✅ Customer Management</li>
                            <li>✅ Rental Tracking</li>
                            <li>✅ Agreement Generation</li>
                            <li>✅ System Statistics</li>
                        </ul>
                        
                        <p>Access the full admin dashboard for complete management features.</p>
                    </div>
                </div>
            `;
        },
        
        loadSignaturePad: function() {
            // Load signature pad when it becomes available
            setTimeout(() => {
                const canvas = document.getElementById('signature_canvas');
                if (canvas && typeof SignaturePad !== 'undefined') {
                    this.signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255,255,255,1)',
                        penColor: 'rgb(0, 0, 0)'
                    });
                }
            }, 500);
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