import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { insertCustomerSchema, loginSchema, staffLoginSchema, insertRentalSchema } from "@shared/schema";
import { imageProcessor } from "./services/imageProcessor";
import { pdfGenerator } from "./services/pdfGenerator";
import { emailService } from "./services/emailService";
import multer from "multer";
import express from "express";
import path from "path";

const upload = multer({ 
  storage: multer.memoryStorage(),
  limits: { 
    fileSize: 10 * 1024 * 1024 // 10MB limit
  }
});

export async function registerRoutes(app: Express): Promise<Server> {
  // Serve uploaded files
  app.use('/uploads', express.static(path.join(process.cwd(), 'uploads')));
  app.use('/backups', express.static(path.join(process.cwd(), 'backups')));

  // Customer Registration
  app.post("/api/customers/register", upload.single('icPassport'), async (req, res) => {
    try {
      // Validate only the required fields from the request body
      const { fullName, email, hashedPassword, phone, address, icPassportNumber } = req.body;
      
      if (!req.file) {
        return res.status(400).json({ message: "IC/Passport image is required" });
      }

      // Validate required fields
      if (!fullName || !email || !hashedPassword || !phone || !address || !icPassportNumber) {
        return res.status(400).json({ message: "All fields are required" });
      }

      // Check if customer already exists
      const existingCustomer = await storage.getCustomerByEmail(email);
      if (existingCustomer) {
        return res.status(400).json({ message: "Customer with this email already exists" });
      }

      // Process and watermark IC/Passport image
      const icPassportUrl = await imageProcessor.processAndWatermarkImage(
        req.file.buffer, 
        req.file.originalname
      );

      // Hash the IC/Passport number (password)
      const hashedPasswordValue = await storage.hashPassword(hashedPassword);

      const newCustomer = await storage.createCustomer({
        fullName,
        email,
        hashedPassword: hashedPasswordValue,
        phone,
        address,
        icPassportNumber,
        icPassportUrl,
      });

      // Remove sensitive data from response
      const { hashedPassword: _password, ...safeCustomer } = newCustomer;
      res.json(safeCustomer);
    } catch (error) {
      console.error("Registration error:", error);
      res.status(400).json({ message: "Registration failed", error: error instanceof Error ? error.message : "Unknown error" });
    }
  });

  // Customer Login
  app.post("/api/customers/login", async (req, res) => {
    try {
      const { email, password } = loginSchema.parse(req.body);
      
      const customer = await storage.getCustomerByEmail(email);
      if (!customer) {
        return res.status(401).json({ message: "Invalid credentials" });
      }

      if (customer.status === "blacklisted") {
        return res.status(403).json({ message: "Account has been suspended" });
      }

      const isValidPassword = await storage.comparePassword(password, customer.hashedPassword);
      if (!isValidPassword) {
        return res.status(401).json({ message: "Invalid credentials" });
      }

      const { hashedPassword: _, ...safeCustomer } = customer;
      res.json(safeCustomer);
    } catch (error) {
      console.error("Login error:", error);
      res.status(400).json({ message: "Login failed", error: error.message });
    }
  });

  // Accept Terms & Conditions
  app.post("/api/customers/:id/accept-terms", async (req, res) => {
    try {
      const customerId = parseInt(req.params.id);
      await storage.updateCustomerTermsAcceptance(customerId);
      res.json({ message: "Terms accepted successfully" });
    } catch (error) {
      console.error("Terms acceptance error:", error);
      res.status(400).json({ message: "Failed to accept terms", error: error instanceof Error ? error.message : "Unknown error" });
    }
  });

  // Create Rental
  app.post("/api/rentals", upload.fields([
    { name: 'vehiclePhotos', maxCount: 7 },
    { name: 'paymentProof', maxCount: 1 }
  ]), async (req, res) => {
    try {
      // Transform form data to match schema expectations
      const transformedData = {
        customerId: parseInt(req.body.customerId),
        vehicle: req.body.vehicle,
        color: req.body.color,
        mileageLimit: parseInt(req.body.mileageLimit.replace(/[^\d]/g, '')) || 0, // Extract number from "170 KM"
        extraMileageCharge: (parseFloat(req.body.extraMileageCharge.replace(/[^\d.]/g, '')) || 0).toString(), // Extract number from "RM 2.50"
        fuelLevel: parseInt(req.body.fuelLevel),
        startDate: new Date(req.body.startDate),
        endDate: new Date(req.body.endDate),
        totalDays: parseInt(req.body.totalDays),
        rentalPerDay: req.body.rentalPerDay,
        deposit: req.body.deposit,
        discount: req.body.discount || "0",
        grandTotal: req.body.grandTotal
      };
      
      console.log("Transformed rental data:", transformedData);
      const files = req.files as { [fieldname: string]: Express.Multer.File[] };

      // Vehicle photos are now optional
      const hasVehiclePhotos = files.vehiclePhotos && files.vehiclePhotos.length > 0;
      const hasPaymentProof = files.paymentProof && files.paymentProof.length > 0;

      // Process vehicle photos (optional)
      const vehiclePhotos: Record<string, string> = {};
      const photoTypes = ['frontWithCustomer', 'front', 'back', 'left', 'right', 'interiorMileage', 'knownDamage'];
      
      if (hasVehiclePhotos) {
        for (let i = 0; i < files.vehiclePhotos.length; i++) {
          const photoUrl = await imageProcessor.processVehiclePhoto(
            files.vehiclePhotos[i].buffer,
            files.vehiclePhotos[i].originalname
          );
          vehiclePhotos[photoTypes[i]] = photoUrl;
        }
      }

      // Process payment proof (optional)
      let paymentProofUrl = '';
      if (hasPaymentProof) {
        paymentProofUrl = await imageProcessor.processVehiclePhoto(
          files.paymentProof[0].buffer,
          files.paymentProof[0].originalname
        );
      }

      // Process signature
      let signatureUrl = '';
      if (req.body.signatureData) {
        signatureUrl = await imageProcessor.processSignature(
          req.body.signatureData,
          'signature.png'
        );
      }

      const rental = await storage.createRental({
        ...transformedData,
        vehiclePhotos,
        paymentProofUrl,
        signatureUrl,
      });

      res.json(rental);
    } catch (error) {
      console.error("Rental creation error:", error);
      res.status(400).json({ message: "Failed to create rental", error: error.message });
    }
  });

  // Generate Agreement PDF
  app.post("/api/rentals/:id/generate-agreement", async (req, res) => {
    try {
      const rentalId = parseInt(req.params.id);
      const rentalRecord = await storage.getRentalById(rentalId);
      
      if (!rentalRecord) {
        return res.status(404).json({ message: "Rental not found" });
      }
      const customer = await storage.getCustomerById(rentalRecord.customerId);
      
      if (!customer) {
        return res.status(404).json({ message: "Customer not found" });
      }

      // Generate PDF
      const pdfUrl = await pdfGenerator.generateRentalAgreement(rentalRecord, customer);
      
      // Update rental with PDF URL
      await storage.updateRentalPdf(rentalRecord.id, pdfUrl);

      // Try to send email with PDF attachment (optional)
      let emailSent = false;
      try {
        await emailService.sendRentalAgreement(customer, pdfUrl, rentalRecord);
        emailSent = true;
        console.log("Email sent successfully to:", customer.email);
      } catch (emailError) {
        console.error("Error sending email:", emailError);
        // Continue without email - PDF generation was successful
      }

      res.json({ 
        message: emailSent ? "Agreement generated and emailed successfully" : "Agreement generated successfully (email delivery unavailable)",
        pdfUrl,
        downloadUrl: `/api/rentals/${rentalRecord.id}/download-agreement`,
        emailSent,
        // Include rental data for display
        customerName: customer.fullName,
        vehicle: rentalRecord.vehicle,
        period: `${new Date(rentalRecord.startDate).toLocaleDateString()} - ${new Date(rentalRecord.endDate).toLocaleDateString()}`,
        total: parseFloat(rentalRecord.grandTotal).toFixed(2),
        rental: rentalRecord
      });
    } catch (error) {
      console.error("Agreement generation error:", error);
      res.status(500).json({ message: "Failed to generate agreement", error: error.message });
    }
  });

  // Download Agreement
  app.get("/api/rentals/:id/download-agreement", async (req, res) => {
    try {
      const rentalId = parseInt(req.params.id);
      const rental = await storage.getRentalById(rentalId);
      
      if (!rental || !rental.agreementPdfUrl) {
        return res.status(404).json({ message: "Agreement not found" });
      }

      const pdfPath = path.join(process.cwd(), rental.agreementPdfUrl.replace('/', ''));
      res.download(pdfPath);
    } catch (error) {
      console.error("Download error:", error);
      res.status(500).json({ message: "Failed to download agreement", error: error.message });
    }
  });

  // Staff Login
  app.post("/api/staff/login", async (req, res) => {
    try {
      const { username, password } = staffLoginSchema.parse(req.body);
      
      // For now, use hardcoded credentials as specified
      if (username === "Akib" && password === "1234") {
        res.json({ 
          id: 1, 
          username: "Akib",
          message: "Login successful" 
        });
      } else {
        res.status(401).json({ message: "Invalid staff credentials" });
      }
    } catch (error) {
      console.error("Staff login error:", error);
      res.status(400).json({ message: "Staff login failed", error: error instanceof Error ? error.message : "Unknown error" });
    }
  });

  // Get All Customers (Staff)
  app.get("/api/staff/customers", async (req, res) => {
    try {
      const customers = await storage.getAllCustomers();
      const safeCustomers = customers.map(({ hashedPassword, ...customer }) => customer);
      res.json(safeCustomers);
    } catch (error) {
      console.error("Get customers error:", error);
      res.status(500).json({ message: "Failed to get customers", error: error.message });
    }
  });

  // Update Customer Status (Staff)
  app.patch("/api/staff/customers/:id/status", async (req, res) => {
    try {
      const customerId = parseInt(req.params.id);
      const { status } = req.body;
      
      if (!['active', 'blacklisted'].includes(status)) {
        return res.status(400).json({ message: "Invalid status" });
      }

      await storage.updateCustomerStatus(customerId, status);
      res.json({ message: "Customer status updated successfully" });
    } catch (error) {
      console.error("Update status error:", error);
      res.status(500).json({ message: "Failed to update customer status", error: error.message });
    }
  });

  // Reset Customer Password (Staff)
  app.patch("/api/staff/customers/:id/reset-password", async (req, res) => {
    try {
      const customerId = parseInt(req.params.id);
      const { newPassword } = req.body;
      
      const hashedPassword = await storage.hashPassword(newPassword);
      await storage.updateCustomerPassword(customerId, hashedPassword);
      
      res.json({ message: "Password reset successfully" });
    } catch (error) {
      console.error("Reset password error:", error);
      res.status(500).json({ message: "Failed to reset password", error: error.message });
    }
  });

  // Get All Rentals (Staff)
  app.get("/api/staff/rentals", async (req, res) => {
    try {
      const rentals = await storage.getAllRentals();
      res.json(rentals);
    } catch (error) {
      console.error("Get rentals error:", error);
      res.status(500).json({ message: "Failed to get rentals", error: error.message });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}
