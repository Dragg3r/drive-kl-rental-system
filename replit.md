# replit.md

## Overview
This is a comprehensive car rental management system for Drive KL Executive Sdn Bhd, built with a modern full-stack architecture. The system handles the complete rental workflow from customer registration and vehicle booking to PDF agreement generation and staff administration.

## System Architecture

### Frontend Architecture
- **Framework**: React 18 with TypeScript
- **Build Tool**: Vite for fast development and optimized builds
- **Routing**: Wouter for lightweight client-side routing
- **State Management**: TanStack Query (React Query) for server state management
- **UI Framework**: Radix UI components with shadcn/ui design system
- **Styling**: Tailwind CSS with custom CSS variables for theming
- **Forms**: React Hook Form with Zod validation

### Backend Architecture
- **Runtime**: Node.js with Express.js framework
- **Language**: TypeScript with ES modules
- **Database ORM**: Drizzle ORM with PostgreSQL dialect
- **File Handling**: Multer for multipart/form-data processing
- **Image Processing**: Sharp for image compression and watermarking
- **PDF Generation**: PDFKit for rental agreement documents
- **Email Service**: Nodemailer for automated email delivery
- **Authentication**: bcrypt for password hashing

## Key Components

### Database Schema (PostgreSQL + Drizzle)
- **customers**: User accounts with hashed passwords, IC/passport uploads, and status management
- **rentals**: Complete rental records with vehicle details, pricing, and document URLs
- **staff**: Administrative user accounts for system management

### Multi-Step User Flows
1. **Role Selection**: Initial user identification (existing customer, new customer, staff)
2. **Customer Registration**: Account creation with IC/passport verification
3. **Login & Authentication**: Secure login with terms acceptance tracking
4. **Rental Booking**: Multi-step form with vehicle selection, photo uploads, and digital signatures
5. **Staff Dashboard**: Administrative interface for customer and rental management

### File Management System
- **Image Processing**: Automatic compression, resizing, and watermarking for IC/passport uploads
- **PDF Generation**: Automated rental agreement creation with customer and vehicle details
- **File Storage**: Local filesystem storage with organized directory structure (/uploads, /backups)

### External Integrations
- **Neon Database**: Serverless PostgreSQL hosting
- **Email Service**: SMTP-based email delivery for rental agreements
- **Replit Development**: Development environment integration with runtime error handling

## Data Flow

### Customer Registration Flow
1. User uploads IC/passport image → Server processes and watermarks image → Stores in /uploads directory
2. Form validation with Zod schemas → Password hashing with bcrypt → Database insertion
3. Automatic account activation (removed approval workflow for streamlined experience)

### Rental Booking Flow
1. Multi-step form collection (vehicle details, photos, payment proof, signature)
2. Real-time cost calculations based on vehicle category and rental duration
3. File uploads processing (7 vehicle photos + payment proof + digital signature)
4. PDF agreement generation with all rental details and customer information
5. Email delivery of completed agreement to customer

### Staff Administration Flow
1. Secure staff authentication with hardcoded credentials
2. Customer management (status updates, password resets, blacklisting)
3. Rental history viewing and PDF agreement access
4. Real-time data updates with React Query invalidation

## External Dependencies

### Production Dependencies
- **Database**: @neondatabase/serverless for PostgreSQL connectivity
- **UI Components**: Extensive Radix UI component library
- **Form Handling**: @hookform/resolvers, react-hook-form, zod
- **File Processing**: multer, sharp for image handling
- **PDF & Email**: pdfkit, nodemailer for document generation and delivery
- **Security**: bcrypt for password hashing

### Development Dependencies
- **Build Tools**: vite, esbuild for development and production builds
- **TypeScript**: Full TypeScript support across client and server
- **Database Tools**: drizzle-kit for schema management and migrations

## Deployment Strategy

### Build Process
- **Development**: `npm run dev` - Concurrent client and server development with hot reload
- **Production Build**: `npm run build` - Vite frontend build + esbuild server bundle
- **Database**: `npm run db:push` - Drizzle schema synchronization

### Environment Configuration
- **DATABASE_URL**: Required PostgreSQL connection string (Neon serverless)
- **SMTP Configuration**: Email service credentials for agreement delivery
- **File System**: Automatic directory creation for uploads and backups

### Production Deployment
- **Server**: Express.js application serving both API and static frontend files
- **Database**: Drizzle ORM with connection pooling for production workloads
- **File Storage**: Local filesystem with organized directory structure

## Changelog
- June 30, 2025. Initial setup
- June 30, 2025. Fixed rental form validation and generate agreement functionality:
  - Updated form schema to match expected data types
  - Added automatic date calculation for total rental days
  - Fixed server-side rental retrieval for PDF generation
  - Added progress indicators for file uploads and PDF generation
  - Made pricing fields number-only inputs
  - Fixed signature pad scrolling issues by preventing touch event propagation
  - Implemented proper form validation with detailed error logging
- June 30, 2025. Added IC/Passport number display in rental agreements:
  - Added icPassportNumber field to customer database schema
  - Updated customer registration form to include IC/Passport number input
  - Modified PDF generator to display IC/Passport number in customer information section
  - Applied database schema changes with migration handling for existing data
- June 30, 2025. Created complete WordPress plugin conversion:
  - Built full-featured WordPress plugin maintaining all original functionality
  - Implemented WordPress admin integration with custom dashboard
  - Added shortcode system for easy page integration [dkl_rental_system]
  - Created secure AJAX handling with WordPress nonces
  - Developed responsive design with AK13/DKL branding
  - Packaged as installable .zip file (drive-kl-rental-plugin.zip)
  - Original web application remains fully functional and untouched

## User Preferences
Preferred communication style: Simple, everyday language.