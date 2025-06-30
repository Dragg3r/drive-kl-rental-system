<?php

require_once(DKL_RENTAL_PLUGIN_PATH . 'vendor/autoload.php');

class DKL_Rental_PDF_Generator {
    
    public static function generateRentalAgreement($rental_id) {
        $rental = DKL_Rental_Database::getRentalById($rental_id);
        if (!$rental) {
            return new WP_Error('rental_not_found', 'Rental not found');
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $agreements_dir = $upload_dir['basedir'] . '/dkl-rental/agreements';
        
        if (!file_exists($agreements_dir)) {
            wp_mkdir_p($agreements_dir);
        }
        
        // Generate PDF filename
        $filename = sanitize_file_name($rental->customer_name . '-' . date('Y-m-d') . '-agreement.pdf');
        $filepath = $agreements_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/dkl-rental/agreements/' . $filename;
        
        try {
            // Initialize PDF document
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Drive KL Rental System');
            $pdf->SetAuthor('AK13');
            $pdf->SetTitle('Car Rental Agreement - ' . $rental->customer_name);
            $pdf->SetSubject('Car Rental Agreement');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(TRUE, 25);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 10);
            
            // Generate content
            $html = self::generatePDFContent($rental);
            
            // Write HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Output to file
            $pdf->Output($filepath, 'F');
            
            // Update rental record with PDF URL
            DKL_Rental_Database::updateRentalPdf($rental_id, $file_url);
            
            return array(
                'success' => true,
                'pdf_path' => $filepath,
                'pdf_url' => $file_url,
                'filename' => $filename
            );
            
        } catch (Exception $e) {
            return new WP_Error('pdf_generation_failed', $e->getMessage());
        }
    }
    
    private static function generatePDFContent($rental) {
        $logo_path = DKL_RENTAL_PLUGIN_PATH . 'assets/images/ak-logo.png';
        $dkl_logo_path = DKL_RENTAL_PLUGIN_PATH . 'assets/images/dkl-logo.png';
        
        $html = '
        <style>
            .header { text-align: center; margin-bottom: 30px; }
            .company-info { text-align: center; margin-bottom: 20px; }
            .title { font-size: 18px; font-weight: bold; text-align: center; margin: 20px 0; }
            .section { margin: 15px 0; }
            .section-title { font-weight: bold; font-size: 12px; margin-bottom: 10px; color: #333; }
            .info-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .info-table td { padding: 5px; border: 1px solid #ddd; }
            .info-table .label { background-color: #f5f5f5; font-weight: bold; width: 30%; }
            .terms { font-size: 9px; line-height: 1.4; margin-top: 20px; }
            .signature-section { margin-top: 30px; }
            .signature-box { border: 1px solid #000; height: 60px; margin: 10px 0; }
        </style>
        
        <div class="header">
            <h1 style="color: #d32f2f; margin: 0;">AK13</h1>
            <h2 style="color: #333; margin: 5px 0;">Drive KL Rental System</h2>
            <p style="margin: 0;">By Akib</p>
        </div>
        
        <div class="title">CAR RENTAL AGREEMENT</div>
        
        <div class="section">
            <div class="section-title">CUSTOMER INFORMATION</div>
            <table class="info-table">
                <tr>
                    <td class="label">Full Name:</td>
                    <td>' . esc_html($rental->customer_name) . '</td>
                </tr>
                <tr>
                    <td class="label">Email:</td>
                    <td>' . esc_html($rental->customer_email) . '</td>
                </tr>
                <tr>
                    <td class="label">Phone:</td>
                    <td>' . esc_html($rental->customer_phone) . '</td>
                </tr>
                <tr>
                    <td class="label">Address:</td>
                    <td>' . esc_html($rental->customer_address) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">VEHICLE INFORMATION</div>
            <table class="info-table">
                <tr>
                    <td class="label">Vehicle:</td>
                    <td>' . esc_html($rental->vehicle) . '</td>
                </tr>
                <tr>
                    <td class="label">Color:</td>
                    <td>' . esc_html($rental->color) . '</td>
                </tr>
                <tr>
                    <td class="label">Mileage Limit:</td>
                    <td>' . esc_html($rental->mileage_limit) . ' KM</td>
                </tr>
                <tr>
                    <td class="label">Extra Mileage Charge:</td>
                    <td>RM ' . esc_html($rental->extra_mileage_charge) . '</td>
                </tr>
                <tr>
                    <td class="label">Fuel Level:</td>
                    <td>' . self::getFuelLevelText($rental->fuel_level) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">RENTAL DETAILS</div>
            <table class="info-table">
                <tr>
                    <td class="label">Start Date:</td>
                    <td>' . date('d/m/Y', strtotime($rental->start_date)) . '</td>
                </tr>
                <tr>
                    <td class="label">End Date:</td>
                    <td>' . date('d/m/Y', strtotime($rental->end_date)) . '</td>
                </tr>
                <tr>
                    <td class="label">Total Days:</td>
                    <td>' . esc_html($rental->total_days) . '</td>
                </tr>
                <tr>
                    <td class="label">Daily Rate:</td>
                    <td>RM ' . number_format($rental->rental_per_day, 2) . '</td>
                </tr>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td>RM ' . number_format($rental->rental_per_day * $rental->total_days, 2) . '</td>
                </tr>
                <tr>
                    <td class="label">Deposit:</td>
                    <td>RM ' . number_format($rental->deposit, 2) . '</td>
                </tr>
                <tr>
                    <td class="label">Discount:</td>
                    <td>RM ' . number_format($rental->discount, 2) . '</td>
                </tr>
                <tr>
                    <td class="label"><strong>Grand Total:</strong></td>
                    <td><strong>RM ' . number_format($rental->grand_total, 2) . '</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="terms">
            <div class="section-title">TERMS AND CONDITIONS</div>
            <p><strong>1. Rental Period:</strong> The vehicle must be returned by the agreed end date and time.</p>
            <p><strong>2. Mileage:</strong> Additional charges apply for exceeding the mileage limit.</p>
            <p><strong>3. Fuel:</strong> Vehicle must be returned with the same fuel level as received.</p>
            <p><strong>4. Damage:</strong> Renter is responsible for any damage beyond normal wear and tear.</p>
            <p><strong>5. Insurance:</strong> Valid insurance coverage is required throughout the rental period.</p>
            <p><strong>6. Late Return:</strong> Additional charges apply for late returns.</p>
            <p><strong>7. Traffic Violations:</strong> Renter is responsible for all traffic violations during the rental period.</p>
        </div>
        
        <div class="signature-section">
            <table style="width: 100%; margin-top: 30px;">
                <tr>
                    <td style="width: 45%; text-align: center;">
                        <div class="signature-box"></div>
                        <p><strong>Customer Signature</strong></p>
                        <p>Date: _______________</p>
                    </td>
                    <td style="width: 10%;"></td>
                    <td style="width: 45%; text-align: center;">
                        <div class="signature-box"></div>
                        <p><strong>AK13 Representative</strong></p>
                        <p>Date: _______________</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 30px; font-size: 8px; color: #666;">
            <p>Generated on ' . date('d/m/Y H:i:s') . ' | Drive KL Rental System by AK13</p>
        </div>';
        
        return $html;
    }
    
    private static function getFuelLevelText($level) {
        $levels = array(
            1 => '1/4 Tank',
            2 => '1/2 Tank', 
            3 => '3/4 Tank',
            4 => 'Full Tank'
        );
        
        return isset($levels[$level]) ? $levels[$level] : 'Unknown';
    }
}