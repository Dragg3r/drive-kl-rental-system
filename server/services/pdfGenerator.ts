import PDFDocument from "pdfkit";
import fs from "fs";
import path from "path";
import { Rental, Customer } from "@shared/schema";

export class PDFGenerator {
  private backupsDir = path.join(process.cwd(), "backups");

  constructor() {
    this.ensureBackupsDirectory();
  }

  private async ensureBackupsDirectory() {
    try {
      await fs.promises.mkdir(this.backupsDir, { recursive: true });
    } catch (error) {
      console.error("Error creating backups directory:", error);
    }
  }

  async generateRentalAgreement(rental: Rental, customer: Customer): Promise<string> {
    const filename = `${customer.fullName.replace(/\s+/g, '-')}-${new Date().toISOString().split('T')[0]}-agreement.pdf`;
    const filePath = path.join(this.backupsDir, filename);

    const doc = new PDFDocument({ margin: 50 });
    doc.pipe(fs.createWriteStream(filePath));

    // Header
    doc.fontSize(20).font('Helvetica-Bold').text('DRIVE KL EXECUTIVE SDN BHD', { align: 'center' });
    doc.fontSize(16).font('Helvetica').text('CAR RENTAL AGREEMENT', { align: 'center' });
    doc.moveDown();

    // Customer Information
    doc.fontSize(14).font('Helvetica-Bold').text('CUSTOMER INFORMATION');
    doc.fontSize(12).font('Helvetica');
    doc.text(`Full Name: ${customer.fullName}`);
    doc.text(`Email: ${customer.email}`);
    doc.text(`Phone: ${customer.phone}`);
    doc.text(`Address: ${customer.address}`);
    doc.moveDown();

    // Vehicle Information
    doc.fontSize(14).font('Helvetica-Bold').text('VEHICLE INFORMATION');
    doc.fontSize(12).font('Helvetica');
    doc.text(`Vehicle: ${rental.vehicle}`);
    doc.text(`Color: ${rental.color}`);
    doc.text(`Mileage Limit: ${rental.mileageLimit} KM`);
    doc.text(`Extra Mileage Charge: RM ${rental.extraMileageCharge}/km`);
    doc.text(`Fuel Level at Pickup: ${this.getFuelLevelText(rental.fuelLevel)}`);
    doc.moveDown();

    // Rental Details
    doc.fontSize(14).font('Helvetica-Bold').text('RENTAL DETAILS');
    doc.fontSize(12).font('Helvetica');
    doc.text(`Start Date: ${new Date(rental.startDate).toLocaleDateString()}`);
    doc.text(`End Date: ${new Date(rental.endDate).toLocaleDateString()}`);
    doc.text(`Total Days: ${rental.totalDays}`);
    doc.moveDown();

    // Payment Information
    doc.fontSize(14).font('Helvetica-Bold').text('PAYMENT INFORMATION');
    doc.fontSize(12).font('Helvetica');
    doc.text(`Rental Per Day: RM ${rental.rentalPerDay}`);
    doc.text(`Deposit: RM ${rental.deposit}`);
    doc.text(`Discount: RM ${rental.discount}`);
    doc.fontSize(14).font('Helvetica-Bold').text(`GRAND TOTAL: RM ${rental.grandTotal}`);
    doc.moveDown();

    // Terms and Conditions
    doc.addPage();
    doc.fontSize(16).font('Helvetica-Bold').text('TERMS AND CONDITIONS', { align: 'center' });
    doc.moveDown();
    
    this.addTermsAndConditions(doc);

    // Vehicle Photos
    doc.addPage();
    doc.fontSize(14).font('Helvetica-Bold').text('VEHICLE CONDITION PHOTOS');
    doc.moveDown();

    // Add photos if they exist
    const photos = rental.vehiclePhotos as Record<string, string>;
    let yPosition = doc.y;
    let xPosition = 50;
    let photosPerRow = 2;
    let photoCount = 0;

    for (const [photoType, photoUrl] of Object.entries(photos)) {
      if (photoUrl && fs.existsSync(path.join(process.cwd(), photoUrl.replace('/', '')))) {
        try {
          doc.fontSize(10).text(photoType, xPosition, yPosition);
          doc.image(path.join(process.cwd(), photoUrl.replace('/', '')), xPosition, yPosition + 15, { 
            width: 200, 
            height: 150 
          });
          
          photoCount++;
          if (photoCount % photosPerRow === 0) {
            yPosition += 180;
            xPosition = 50;
          } else {
            xPosition += 250;
          }

          if (yPosition > 650) {
            doc.addPage();
            yPosition = 50;
            xPosition = 50;
          }
        } catch (error) {
          console.error(`Error adding image ${photoType}:`, error);
        }
      }
    }

    // Signature
    doc.addPage();
    doc.fontSize(14).font('Helvetica-Bold').text('DIGITAL SIGNATURE');
    doc.moveDown();

    if (rental.signatureUrl && fs.existsSync(path.join(process.cwd(), rental.signatureUrl.replace('/', '')))) {
      try {
        doc.image(path.join(process.cwd(), rental.signatureUrl.replace('/', '')), 50, doc.y, { 
          width: 300, 
          height: 100 
        });
      } catch (error) {
        console.error('Error adding signature:', error);
      }
    }

    doc.moveDown(8);
    doc.text(`Customer Name: ${customer.fullName}`);
    doc.text(`Date: ${new Date().toLocaleDateString()}`);

    doc.end();

    return `/backups/${filename}`;
  }

  private getFuelLevelText(level: number): string {
    const levels = ['Empty', '1/8', '1/4', '3/8', '1/2', '5/8', '3/4', '7/8', 'Full'];
    return levels[level] || 'Unknown';
  }

  private addTermsAndConditions(doc: PDFKit.PDFDocument) {
    doc.fontSize(12).font('Helvetica');
    
    const terms = `
This Agreement outlines the terms for vehicle rental from Drive KL Executive Sdn Bhd. By signing, the Renter agrees to these clauses. Breaches may lead to penalties, deposit forfeiture, agreement termination, and legal action.

1. RENTAL PERIOD AND VEHICLE USAGE

1.1. Genting Highland Usage Fee: An additional surcharge (RM150-RM350, vehicle-dependent) applies for Genting Highlands travel. Declare and settle this fee with Drive KL Executive Sdn Bhd before departure.

1.2. Early Termination of Rental: No refunds or partial refunds are provided for early returns. The Renter must honor the original booking duration.

1.3. Late Return Penalty: Vehicles returned late incur a RM 25-300 per hour penalty, unless Drive KL Executive Sdn Bhd provides prior written agreement.

1.4. Mileage Limits & Charges: Exceeding any daily mileage cap results in an overage charge (RM1.50–RM5.00/km), payable to Drive KL Executive Sdn Bhd.

1.5. Fuel Level Requirement: Return vehicles with the same fuel level as received. Drive KL Executive Sdn Bhd will impose a RM50–RM200 refueling charge if not met.

2. DRIVER AUTHORIZATION & RESPONSIBILITIES

2.1. Unregistered Drivers Prohibited: Only authorized individuals listed in this Agreement may drive the vehicle. Any unregistered driver voids this Agreement and forfeits the full deposit to Drive KL Executive Sdn Bhd.

2.2. Traffic Violations & Summons: The Renter is solely responsible for all traffic fines, parking summons, and toll charges incurred during the rental period. Outstanding penalties will be deducted from the deposit by Drive KL Executive Sdn Bhd.

3. VEHICLE CARE AND PROHIBITED ACTIONS

3.1. Unauthorized Workshop Visits: Renters are strictly prohibited from sending the vehicle to any external workshop. Violations result in immediate agreement termination by Drive KL Executive Sdn Bhd and full deposit forfeiture. All repairs must be coordinated with Drive KL Executive Sdn Bhd.

3.2. Vehicle Misuse & Reckless Behavior: Vehicle abuse (e.g., drifting, burnouts, unauthorized decals/stickers, aggressive revving, off-road use, redlining while idle) is strictly forbidden. This results in full deposit forfeiture to Drive KL Executive Sdn Bhd and potential legal action.

3.3. Speed Limit Violations: Speeding is monitored via GPS/dash cam. A first offense results in a written warning from Drive KL Executive Sdn Bhd. A second offense leads to immediate rental termination and full deposit forfeiture, with no exceptions.

3.4. Smoking & Vaping Strictly Prohibited: A RM300 cleaning fee will be charged by Drive KL Executive Sdn Bhd if the interior smells of smoke, vape, or strong odors.

4. INSURANCE COVERAGE AND DAMAGES

All rental vehicles are covered by comprehensive insurance. However, the Renter is responsible for the first RM2,000-RM5,000 of any damage claim, depending on the vehicle category. This excess amount will be deducted from the deposit.

By signing below, the Renter acknowledges reading, understanding, and agreeing to all terms and conditions outlined in this Agreement.
    `;

    doc.text(terms, {
      width: 500,
      align: 'justify'
    });
  }
}

export const pdfGenerator = new PDFGenerator();
