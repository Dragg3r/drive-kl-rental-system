import nodemailer from "nodemailer";
import { Customer } from "@shared/schema";

export class EmailService {
  private transporter;

  constructor() {
    this.transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST || "smtp.gmail.com",
      port: parseInt(process.env.SMTP_PORT || "587"),
      secure: false,
      auth: {
        user: process.env.SMTP_USER || process.env.EMAIL_USER,
        pass: process.env.SMTP_PASS || process.env.EMAIL_PASS,
      },
    });
  }

  async sendRentalAgreement(customer: Customer, pdfPath: string, rentalDetails: any) {
    const attachmentPath = pdfPath.startsWith('/') ? pdfPath.substring(1) : pdfPath;
    
    const mailOptions = {
      from: process.env.SMTP_FROM || "noreply@drivekl.com",
      to: customer.email,
      subject: "Your Drive KL Rental Agreement",
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
          <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: white;">
            <h1 style="margin: 0; font-size: 28px;">Drive KL Executive</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Your Rental Agreement is Ready</p>
          </div>
          
          <div style="padding: 30px; background: #f8f9fa;">
            <h2 style="color: #333; margin-top: 0;">Dear ${customer.fullName},</h2>
            
            <p style="color: #666; line-height: 1.6;">
              Thank you for choosing Drive KL Executive for your car rental needs. Your rental agreement has been successfully generated and is attached to this email.
            </p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;">
              <h3 style="color: #333; margin-top: 0;">Rental Summary:</h3>
              <ul style="color: #666; line-height: 1.8;">
                <li><strong>Vehicle:</strong> ${rentalDetails.vehicle} - ${rentalDetails.color}</li>
                <li><strong>Rental Period:</strong> ${new Date(rentalDetails.startDate).toLocaleDateString()} - ${new Date(rentalDetails.endDate).toLocaleDateString()}</li>
                <li><strong>Total Amount:</strong> RM ${rentalDetails.grandTotal}</li>
              </ul>
            </div>
            
            <p style="color: #666; line-height: 1.6;">
              Please review the attached agreement carefully and keep it for your records. If you have any questions or concerns, please don't hesitate to contact us.
            </p>
            
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;">
              <p style="margin: 0; color: #1976d2; font-weight: 500;">
                <strong>Important:</strong> Please bring a printed copy of this agreement when picking up your vehicle.
              </p>
            </div>
            
            <p style="color: #666; line-height: 1.6;">
              Thank you for your business!<br>
              <strong>Drive KL Executive Team</strong>
            </p>
          </div>
          
          <div style="background: #333; padding: 20px; text-align: center; color: #999; font-size: 14px;">
            <p style="margin: 0;">Drive KL Executive Sdn Bhd</p>
            <p style="margin: 5px 0 0 0;">Premium Car Rental Services</p>
          </div>
        </div>
      `,
      attachments: [
        {
          filename: `rental-agreement-${customer.fullName.replace(/\s+/g, '-')}.pdf`,
          path: attachmentPath,
        },
      ],
    };

    try {
      await this.transporter.sendMail(mailOptions);
      console.log(`Rental agreement email sent to ${customer.email}`);
    } catch (error) {
      console.error("Error sending email:", error);
      throw error;
    }
  }
}

export const emailService = new EmailService();
