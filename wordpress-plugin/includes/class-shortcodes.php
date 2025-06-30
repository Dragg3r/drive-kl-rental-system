<?php

class DKL_Rental_Shortcodes {
    
    public function __construct() {
        add_shortcode('dkl_rental_system', array($this, 'renderRentalSystem'));
        add_shortcode('dkl_customer_login', array($this, 'renderCustomerLogin'));
        add_shortcode('dkl_customer_register', array($this, 'renderCustomerRegister'));
        add_shortcode('dkl_rental_form', array($this, 'renderRentalForm'));
    }
    
    public function renderRentalSystem($atts) {
        $atts = shortcode_atts(array(
            'view' => 'role-selection',
            'style' => 'modern'
        ), $atts);
        
        ob_start();
        ?>
        <div id="dkl-rental-app" class="dkl-rental-container" data-view="<?php echo esc_attr($atts['view']); ?>">
            <div class="dkl-header">
                <div class="dkl-header-content">
                    <div class="dkl-logo-section">
                        <img src="<?php echo DKL_RENTAL_PLUGIN_URL; ?>assets/images/ak-logo.png" alt="AK13 Logo" class="dkl-ak-logo">
                        <div class="dkl-title">
                            <h1>AK13</h1>
                        </div>
                    </div>
                    <div class="dkl-header-right">
                        <img src="<?php echo DKL_RENTAL_PLUGIN_URL; ?>assets/images/car-icon.png" alt="Car" class="dkl-car-icon">
                    </div>
                </div>
            </div>
            
            <div class="dkl-main-content">
                <div id="dkl-role-selection" class="dkl-view active">
                    <?php $this->renderRoleSelection(); ?>
                </div>
                
                <div id="dkl-customer-login" class="dkl-view">
                    <?php $this->renderCustomerLoginForm(); ?>
                </div>
                
                <div id="dkl-customer-registration" class="dkl-view">
                    <?php $this->renderCustomerRegistrationForm(); ?>
                </div>
                
                <div id="dkl-rental-form" class="dkl-view">
                    <?php $this->renderRentalBookingForm(); ?>
                </div>
                
                <div id="dkl-terms-conditions" class="dkl-view">
                    <?php $this->renderTermsAndConditions(); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize DKL Rental System
            if (typeof DKLRentalApp !== 'undefined') {
                DKLRentalApp.init();
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function renderRoleSelection() {
        ?>
        <div class="dkl-role-selection">
            <div class="dkl-welcome-card">
                <div class="dkl-logo-container">
                    <img src="<?php echo DKL_RENTAL_PLUGIN_URL; ?>assets/images/dkl-logo.png" alt="DKL Logo" class="dkl-dkl-logo">
                </div>
                <h2>Drive KL Rental System</h2>
                <p>By Akib</p>
                
                <div class="dkl-role-buttons">
                    <button class="dkl-btn dkl-btn-primary" data-view="customer-login">
                        <span class="dkl-icon">👤</span>
                        Existing Customer
                    </button>
                    
                    <button class="dkl-btn dkl-btn-secondary" data-view="customer-registration">
                        <span class="dkl-icon">➕</span>
                        New Customer
                    </button>
                    
                    <button class="dkl-btn dkl-btn-outline" data-view="staff-login">
                        <span class="dkl-icon">🚗</span>
                        DKL Staff Login
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function renderCustomerLoginForm() {
        ?>
        <div class="dkl-customer-login">
            <div class="dkl-form-card">
                <h2>Customer Login</h2>
                <p>Sign in to your account to book vehicles</p>
                
                <form id="dkl-customer-login-form" class="dkl-form">
                    <div class="dkl-form-group">
                        <label for="login_email">Email Address</label>
                        <input type="email" id="login_email" name="email" required>
                    </div>
                    
                    <div class="dkl-form-group">
                        <label for="login_password">Password (IC/Passport Number)</label>
                        <input type="password" id="login_password" name="password" required>
                    </div>
                    
                    <button type="submit" class="dkl-btn dkl-btn-primary dkl-btn-full">
                        Sign In
                    </button>
                    
                    <div class="dkl-form-footer">
                        <p>Don't have an account? <a href="#" data-view="customer-registration">Register here</a></p>
                        <p><a href="#" data-view="role-selection">← Back to main menu</a></p>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    private function renderCustomerRegistrationForm() {
        ?>
        <div class="dkl-customer-registration">
            <div class="dkl-form-card">
                <h2>Customer Registration</h2>
                <p>Create your account to start booking vehicles</p>
                
                <form id="dkl-customer-registration-form" class="dkl-form" enctype="multipart/form-data">
                    <div class="dkl-form-row">
                        <div class="dkl-form-group">
                            <label for="reg_full_name">Full Name</label>
                            <input type="text" id="reg_full_name" name="full_name" required>
                        </div>
                        
                        <div class="dkl-form-group">
                            <label for="reg_email">Email Address</label>
                            <input type="email" id="reg_email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="dkl-form-row">
                        <div class="dkl-form-group">
                            <label for="reg_phone">Phone Number</label>
                            <input type="tel" id="reg_phone" name="phone" required>
                        </div>
                        
                        <div class="dkl-form-group">
                            <label for="reg_ic_passport">IC/Passport Number</label>
                            <input type="text" id="reg_ic_passport" name="ic_passport_number" required>
                        </div>
                    </div>
                    
                    <div class="dkl-form-group">
                        <label for="reg_password">Password</label>
                        <input type="password" id="reg_password" name="password" required>
                        <small>This will be your login password</small>
                    </div>
                    
                    <div class="dkl-form-group">
                        <label for="reg_address">Address</label>
                        <textarea id="reg_address" name="address" required rows="3"></textarea>
                    </div>
                    
                    <div class="dkl-form-group">
                        <label for="reg_social_media">Social Media Handle (Optional)</label>
                        <input type="text" id="reg_social_media" name="social_media_handle" placeholder="Instagram username or Facebook ID">
                    </div>
                    
                    <div class="dkl-form-group">
                        <label>IC/Passport Front Page Upload</label>
                        <div class="dkl-file-upload">
                            <input type="file" id="reg_ic_passport_file" name="ic_passport" accept="image/*" required>
                            <div class="dkl-file-upload-text">
                                <span class="dkl-icon">📄</span>
                                <span>Click to upload or drag and drop</span>
                                <small>Image will be automatically compressed and watermarked</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dkl-form-group">
                        <label>Utility Bill Upload (TNB/Water/Internet)</label>
                        <div class="dkl-file-upload">
                            <input type="file" id="reg_utility_bill" name="utility_bill" accept="image/*" required>
                            <div class="dkl-file-upload-text">
                                <span class="dkl-icon">📄</span>
                                <span>Click to upload or drag and drop</span>
                                <small>Image will be automatically compressed and watermarked</small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="dkl-btn dkl-btn-primary dkl-btn-full">
                        Create Account
                    </button>
                    
                    <div class="dkl-form-footer">
                        <p>Already have an account? <a href="#" data-view="customer-login">Sign in here</a></p>
                        <p><a href="#" data-view="role-selection">← Back to main menu</a></p>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    private function renderRentalBookingForm() {
        ?>
        <div class="dkl-rental-form">
            <div class="dkl-form-card dkl-wide-card">
                <h2>Vehicle Rental Booking</h2>
                <p>Please fill in all details for your rental agreement</p>
                
                <form id="dkl-rental-booking-form" class="dkl-form" enctype="multipart/form-data">
                    <!-- Vehicle Selection -->
                    <div class="dkl-form-section">
                        <h3>Vehicle Information</h3>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="rental_vehicle">Vehicle</label>
                                <select id="rental_vehicle" name="vehicle" required>
                                    <option value="">Select Vehicle</option>
                                    <option value="AMG C63">AMG C63</option>
                                    <option value="AMG G63">AMG G63</option>
                                    <option value="BMW M3">BMW M3</option>
                                    <option value="Audi RS6">Audi RS6</option>
                                </select>
                            </div>
                            
                            <div class="dkl-form-group">
                                <label for="rental_color">Color</label>
                                <select id="rental_color" name="color" required>
                                    <option value="">Select Color</option>
                                    <option value="Black">Black</option>
                                    <option value="White">White</option>
                                    <option value="Silver">Silver</option>
                                    <option value="Blue">Blue</option>
                                    <option value="Red">Red</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="rental_mileage_limit">Mileage Limit (KM)</label>
                                <input type="number" id="rental_mileage_limit" name="mileage_limit" required>
                            </div>
                            
                            <div class="dkl-form-group">
                                <label for="rental_extra_charge">Extra Mileage Charge (RM)</label>
                                <input type="number" step="0.01" id="rental_extra_charge" name="extra_mileage_charge" required>
                            </div>
                        </div>
                        
                        <div class="dkl-form-group">
                            <label for="rental_fuel_level">Fuel Level</label>
                            <select id="rental_fuel_level" name="fuel_level" required>
                                <option value="">Select Fuel Level</option>
                                <option value="1">1/4 Tank</option>
                                <option value="2">1/2 Tank</option>
                                <option value="3">3/4 Tank</option>
                                <option value="4">Full Tank</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Rental Period -->
                    <div class="dkl-form-section">
                        <h3>Rental Period</h3>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="rental_start_date">Start Date</label>
                                <input type="date" id="rental_start_date" name="start_date" required>
                            </div>
                            
                            <div class="dkl-form-group">
                                <label for="rental_end_date">End Date</label>
                                <input type="date" id="rental_end_date" name="end_date" required>
                            </div>
                        </div>
                        
                        <div id="rental_total_days" class="dkl-info-display">
                            <strong>Total Days: <span id="total_days_count">0</span></strong>
                        </div>
                    </div>
                    
                    <!-- Pricing -->
                    <div class="dkl-form-section">
                        <h3>Pricing</h3>
                        
                        <div class="dkl-form-row">
                            <div class="dkl-form-group">
                                <label for="rental_per_day">Rental Per Day (RM)</label>
                                <input type="number" step="0.01" id="rental_per_day" name="rental_per_day" required>
                            </div>
                            
                            <div class="dkl-form-group">
                                <label for="rental_deposit">Deposit (RM)</label>
                                <input type="number" step="0.01" id="rental_deposit" name="deposit" required>
                            </div>
                        </div>
                        
                        <div class="dkl-form-group">
                            <label for="rental_discount">Discount (RM)</label>
                            <input type="number" step="0.01" id="rental_discount" name="discount" value="0">
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
                    
                    <!-- Vehicle Photos -->
                    <div class="dkl-form-section">
                        <h3>Vehicle Photos (Up to 7 photos)</h3>
                        <div id="vehicle_photos_container">
                            <?php for ($i = 0; $i < 7; $i++): ?>
                            <div class="dkl-form-group">
                                <label>Photo <?php echo $i + 1; ?></label>
                                <div class="dkl-file-upload">
                                    <input type="file" name="vehicle_photos[]" accept="image/*">
                                    <div class="dkl-file-upload-text">
                                        <span class="dkl-icon">📷</span>
                                        <span>Upload vehicle photo</span>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Proof -->
                    <div class="dkl-form-section">
                        <h3>Payment Proof</h3>
                        <div class="dkl-form-group">
                            <label>Upload Payment Screenshot</label>
                            <div class="dkl-file-upload">
                                <input type="file" name="payment_proof" accept="image/*" required>
                                <div class="dkl-file-upload-text">
                                    <span class="dkl-icon">💳</span>
                                    <span>Upload payment proof</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Digital Signature -->
                    <div class="dkl-form-section">
                        <h3>Digital Signature</h3>
                        <div class="dkl-signature-container">
                            <canvas id="signature_canvas" width="400" height="200"></canvas>
                            <div class="dkl-signature-controls">
                                <button type="button" id="clear_signature" class="dkl-btn dkl-btn-outline">Clear</button>
                            </div>
                            <p class="dkl-signature-note">Please sign above to agree to the terms and conditions</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="dkl-btn dkl-btn-primary dkl-btn-full">
                        Create Rental Agreement
                    </button>
                </form>
            </div>
        </div>
        <?php
    }
    
    private function renderTermsAndConditions() {
        ?>
        <div class="dkl-terms-conditions">
            <div class="dkl-form-card">
                <h2>Terms and Conditions</h2>
                
                <div class="dkl-terms-content">
                    <h3>Drive KL Rental System - Terms and Conditions</h3>
                    
                    <h4>1. Rental Agreement</h4>
                    <p>This agreement is between the customer and Drive KL Rental System (AK13). By signing this agreement, you agree to all terms and conditions.</p>
                    
                    <h4>2. Vehicle Usage</h4>
                    <ul>
                        <li>The vehicle must be used responsibly and lawfully</li>
                        <li>No smoking allowed in the vehicle</li>
                        <li>No pets allowed unless prior arrangement</li>
                        <li>Vehicle must not be used for illegal activities</li>
                    </ul>
                    
                    <h4>3. Mileage and Fuel</h4>
                    <ul>
                        <li>Additional charges apply for exceeding mileage limits</li>
                        <li>Vehicle must be returned with same fuel level</li>
                        <li>Fuel charges apply if returned with less fuel</li>
                    </ul>
                    
                    <h4>4. Damage and Insurance</h4>
                    <ul>
                        <li>Customer is responsible for any damage during rental period</li>
                        <li>Valid insurance coverage required</li>
                        <li>Immediate notification required for any accidents</li>
                    </ul>
                    
                    <h4>5. Late Returns</h4>
                    <ul>
                        <li>Additional charges apply for late returns</li>
                        <li>Grace period of 1 hour</li>
                        <li>Full day charge after 4 hours late</li>
                    </ul>
                    
                    <h4>6. Cancellation Policy</h4>
                    <ul>
                        <li>24 hours notice required for cancellation</li>
                        <li>Cancellation fees may apply</li>
                        <li>Refunds processed within 7 business days</li>
                    </ul>
                </div>
                
                <div class="dkl-terms-actions">
                    <label class="dkl-checkbox">
                        <input type="checkbox" id="accept_terms" required>
                        <span class="dkl-checkmark"></span>
                        I accept the terms and conditions
                    </label>
                    
                    <button id="proceed_to_rental" class="dkl-btn dkl-btn-primary" disabled>
                        Proceed to Rental Form
                    </button>
                </div>
                
                <div class="dkl-form-footer">
                    <p><a href="#" data-view="customer-login">← Back to login</a></p>
                </div>
            </div>
        </div>
        <?php
    }
}