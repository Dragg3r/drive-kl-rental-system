<?php

class DKL_Rental_Email_Service {
    
    public static function sendRentalAgreement($rental, $pdf_path) {
        if (!$rental || !file_exists($pdf_path)) {
            return new WP_Error('invalid_data', 'Invalid rental data or PDF file');
        }
        
        $to = $rental->customer_email;
        $subject = 'Your Car Rental Agreement - ' . $rental->vehicle;
        
        $message = self::generateEmailTemplate($rental);
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: AK13 Drive KL <noreply@drivekl.com>'
        );
        
        // Attachment
        $attachments = array($pdf_path);
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if (!$sent) {
            return new WP_Error('email_failed', 'Failed to send email');
        }
        
        return true;
    }
    
    public static function sendWelcomeEmail($customer) {
        $to = $customer->email;
        $subject = 'Welcome to Drive KL Rental System';
        
        $message = '
        <html>
        <head>
            <title>Welcome to Drive KL</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #d32f2f;">AK13</h1>
                <h2 style="color: #333;">Drive KL Rental System</h2>
                <p style="color: #666;">By Akib</p>
            </div>
            
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 8px;">
                <h3>Welcome ' . esc_html($customer->full_name) . '!</h3>
                
                <p>Thank you for registering with Drive KL Rental System. Your account has been successfully created.</p>
                
                <h4>Account Details:</h4>
                <ul>
                    <li><strong>Email:</strong> ' . esc_html($customer->email) . '</li>
                    <li><strong>Registration Date:</strong> ' . date('F j, Y') . '</li>
                </ul>
                
                <p>You can now log in to our system and start booking premium vehicles for your rental needs.</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . home_url('/rental') . '" style="background-color: #d32f2f; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                        Start Booking Now
                    </a>
                </div>
                
                <h4>What\'s Next?</h4>
                <ul>
                    <li>Browse our premium vehicle collection</li>
                    <li>Book your rental with instant confirmation</li>
                    <li>Enjoy our professional service</li>
                </ul>
                
                <p>If you have any questions, please don\'t hesitate to contact us.</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                <p>Drive KL Rental System by AK13<br>
                Premium Car Rental Service</p>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: AK13 Drive KL <noreply@drivekl.com>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    public static function sendRentalConfirmation($rental) {
        $to = $rental->customer_email;
        $subject = 'Rental Booking Confirmed - ' . $rental->vehicle;
        
        $message = '
        <html>
        <head>
            <title>Rental Confirmation</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #d32f2f;">AK13</h1>
                <h2 style="color: #333;">Drive KL Rental System</h2>
                <p style="color: #666;">By Akib</p>
            </div>
            
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 8px;">
                <h3>Rental Booking Confirmed!</h3>
                
                <p>Dear ' . esc_html($rental->customer_name) . ',</p>
                
                <p>Your rental booking has been confirmed. Here are the details:</p>
                
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <tr style="background-color: #e0e0e0;">
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Vehicle:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">' . esc_html($rental->vehicle) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Color:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">' . esc_html($rental->color) . '</td>
                    </tr>
                    <tr style="background-color: #e0e0e0;">
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Rental Period:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">' . date('M j, Y', strtotime($rental->start_date)) . ' - ' . date('M j, Y', strtotime($rental->end_date)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Total Days:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">' . $rental->total_days . ' days</td>
                    </tr>
                    <tr style="background-color: #e0e0e0;">
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Grand Total:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><strong>RM ' . number_format($rental->grand_total, 2) . '</strong></td>
                    </tr>
                </table>
                
                <p><strong>Booking ID:</strong> #' . $rental->id . '</p>
                
                <p>Your rental agreement will be generated and sent to you shortly. Please keep this confirmation for your records.</p>
                
                <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">Important Reminders:</h4>
                    <ul style="margin: 0; color: #856404;">
                        <li>Please bring a valid driving license</li>
                        <li>Vehicle inspection will be done before handover</li>
                        <li>Ensure you have valid insurance coverage</li>
                    </ul>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                <p>Drive KL Rental System by AK13<br>
                Premium Car Rental Service</p>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: AK13 Drive KL <noreply@drivekl.com>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    private static function generateEmailTemplate($rental) {
        return '
        <html>
        <head>
            <title>Car Rental Agreement</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #d32f2f;">AK13</h1>
                <h2 style="color: #333;">Drive KL Rental System</h2>
                <p style="color: #666;">By Akib</p>
            </div>
            
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 8px;">
                <h3>Your Car Rental Agreement is Ready!</h3>
                
                <p>Dear ' . esc_html($rental->customer_name) . ',</p>
                
                <p>Thank you for choosing Drive KL Rental System. Your rental agreement has been generated and is attached to this email.</p>
                
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <tr style="background-color: #e0e0e0;">
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Booking ID:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">#' . $rental->id . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Vehicle:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">' . esc_html($rental->vehicle) . '</td>
                    </tr>
                    <tr style="background-color: #e0e0e0;">
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Color:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">' . esc_html($rental->color) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Rental Period:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;">' . date('M j, Y', strtotime($rental->start_date)) . ' - ' . date('M j, Y', strtotime($rental->end_date)) . '</td>
                    </tr>
                    <tr style="background-color: #e0e0e0;">
                        <td style="padding: 10px; border: 1px solid #ccc; font-weight: bold;">Total Amount:</td>
                        <td style="padding: 10px; border: 1px solid #ccc;"><strong>RM ' . number_format($rental->grand_total, 2) . '</strong></td>
                    </tr>
                </table>
                
                <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #155724;">Next Steps:</h4>
                    <ol style="margin: 0; color: #155724;">
                        <li>Review the attached rental agreement</li>
                        <li>Bring the agreement and required documents for vehicle pickup</li>
                        <li>Contact us if you have any questions</li>
                    </ol>
                </div>
                
                <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">Required Documents for Pickup:</h4>
                    <ul style="margin: 0; color: #856404;">
                        <li>Valid driving license</li>
                        <li>IC/Passport (original)</li>
                        <li>Insurance documents</li>
                        <li>This rental agreement (printed or digital)</li>
                    </ul>
                </div>
                
                <p>We look forward to serving you with our premium vehicles and professional service.</p>
                
                <p>Best regards,<br>
                <strong>AK13 Team</strong><br>
                Drive KL Rental System</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                <p>Drive KL Rental System by AK13<br>
                Premium Car Rental Service<br>
                For support, contact us at support@drivekl.com</p>
            </div>
        </body>
        </html>';
    }
}