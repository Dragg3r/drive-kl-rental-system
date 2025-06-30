# Drive KL Rental System - WordPress Plugin

A comprehensive car rental management system built as a WordPress plugin. Developed by Akib (AK13).

## Features

### Customer Management
- **Customer Registration**: Complete registration with IC/Passport and utility bill uploads
- **Document Verification**: Automatic image compression and watermarking
- **Social Media Integration**: Optional Instagram/Facebook handle collection
- **Account Management**: Password-based authentication with WordPress integration

### Rental Booking System
- **Multi-Step Booking Process**: Guided rental form with real-time calculations
- **Vehicle Selection**: Support for premium vehicle categories (AMG C63, AMG G63, BMW M3, Audi RS6)
- **Pricing Calculator**: Dynamic pricing with deposits, discounts, and total calculations
- **Document Collection**: Up to 7 vehicle photos, payment proof, and digital signatures
- **PDF Agreement Generation**: Automated rental agreement creation using TCPDF

### Staff Administration
- **Admin Dashboard**: Complete WordPress admin integration
- **Customer Management**: View, approve, and manage customer accounts
- **Rental Management**: Track all rentals with status updates
- **Agreement Downloads**: Access and download all generated agreements
- **System Statistics**: Dashboard with key metrics and recent activity

### Technical Features
- **Image Processing**: Automatic compression, resizing, and watermarking
- **Email Integration**: Automated email delivery using WordPress mail functions
- **Database Integration**: Custom tables with WordPress compatibility
- **Security**: Nonce verification, input sanitization, and proper authentication
- **Responsive Design**: Mobile-friendly glass-effect UI with red color scheme

## Installation

1. **Upload Plugin Files**
   ```
   wp-content/plugins/drive-kl-rental/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Drive KL Rental System"
   - Click "Activate"

3. **Configure Settings**
   - Navigate to "Drive KL Rental" in admin menu
   - Configure email and system settings

## Usage

### Frontend Implementation

Add the rental system to any page or post using the shortcode:

```
[dkl_rental_system]
```

### Available Shortcodes

- `[dkl_rental_system]` - Complete rental system interface
- `[dkl_customer_login]` - Customer login form only
- `[dkl_customer_register]` - Customer registration form only
- `[dkl_rental_form]` - Rental booking form only

### Staff Access

Default staff credentials:
- **Username**: Akib
- **Password**: 1234

Access admin features through:
- WordPress Admin → Drive KL Rental

## File Structure

```
drive-kl-rental/
├── drive-kl-rental.php          # Main plugin file
├── includes/                    # Core PHP classes
│   ├── class-database.php       # Database management
│   ├── class-customer.php       # Customer operations
│   ├── class-rental.php         # Rental management
│   ├── class-pdf-generator.php  # PDF generation
│   ├── class-image-processor.php # Image handling
│   ├── class-email-service.php  # Email functionality
│   ├── class-ajax-handler.php   # AJAX endpoints
│   └── class-shortcodes.php     # Frontend shortcodes
├── admin/                       # WordPress admin interface
│   └── class-admin.php          # Admin dashboard
├── public/                      # Frontend functionality
│   └── class-public.php         # Public-facing features
└── assets/                      # Static assets
    ├── css/
    │   └── public.css           # Frontend styles
    ├── js/
    │   └── public.js            # Frontend JavaScript
    └── images/                  # Plugin images and logos
```

## Database Schema

The plugin creates three custom tables:

### wp_dkl_customers
- Customer information and documents
- WordPress user integration
- Status management (active/blacklisted)

### wp_dkl_rentals
- Complete rental records
- Vehicle and pricing details
- File attachments and agreements

### wp_dkl_staff
- Staff user management
- Role-based access control

## Configuration

### Email Settings
Configure SMTP settings in WordPress for email delivery:
- Customer welcome emails
- Rental confirmations
- Agreement delivery

### File Upload Settings
- Maximum file size: 5MB per file
- Supported formats: JPEG, PNG
- Automatic compression and watermarking
- Secure storage in wp-content/uploads/dkl-rental/

### Security Features
- WordPress nonce verification
- Input sanitization and validation
- Secure file handling
- SQL injection prevention

## Customization

### Styling
Modify `assets/css/public.css` to customize:
- Color scheme (currently red-themed)
- Glass-effect styling
- Responsive breakpoints

### Vehicle Options
Update vehicle lists in `includes/class-shortcodes.php`:
```php
<option value="Custom Vehicle">Custom Vehicle</option>
```

### Pricing Logic
Modify pricing calculations in `assets/js/public.js`:
```javascript
calculateTotals: function() {
    // Custom pricing logic here
}
```

## API Endpoints

### AJAX Actions
- `dkl_customer_register` - Customer registration
- `dkl_customer_login` - Customer authentication
- `dkl_create_rental` - Create new rental
- `dkl_generate_agreement` - Generate PDF agreement
- `dkl_staff_login` - Staff authentication
- `dkl_get_customers` - Fetch customer list (staff only)
- `dkl_get_rentals` - Fetch rental list (staff only)

## Requirements

### WordPress
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

### PHP Extensions
- GD library (for image processing)
- TCPDF (included via composer)
- ZIP extension (for file handling)

### Server Requirements
- Upload limit: 32MB recommended
- Memory limit: 256MB recommended
- Execution time: 60 seconds recommended

## Support & Updates

### Version Information
- Current Version: 1.0.0
- Compatibility: WordPress 5.0+
- Last Updated: June 30, 2025

### Developer Contact
- **Author**: Akib (AK13)
- **System**: Drive KL Rental System
- **Website**: https://ak13.com

## Changelog

### Version 1.0.0 (June 30, 2025)
- Initial release
- Complete rental management system
- WordPress admin integration
- PDF agreement generation
- Email notifications
- Image processing and watermarking
- Responsive design implementation

## License

This plugin is proprietary software developed for Drive KL Executive Sdn Bhd by AK13.

## Troubleshooting

### Common Issues

**Plugin won't activate**
- Check PHP version (7.4+ required)
- Verify file permissions
- Check error logs

**Images not uploading**
- Increase PHP upload limits
- Check wp-content/uploads permissions
- Verify GD library installation

**PDFs not generating**
- Check TCPDF installation
- Verify write permissions in uploads directory
- Check PHP memory limits

**Emails not sending**
- Configure WordPress SMTP settings
- Test with WP Mail SMTP plugin
- Check spam folders

### Debug Mode
Enable WordPress debug mode in wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in wp-content/debug.log for detailed error information.