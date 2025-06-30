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
      const { fullName, email, hashedPassword, phone, address } = req.body;
      
      if (!req.file) {
        return res.status(400).json({ message: "IC/Passport image is required" });
      }

      // Validate required fields
      if (!fullName || !email || !hashedPassword || !phone || !address) {
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
      const rentalData = insertRentalSchema.parse(req.body);
      const files = req.files as { [fieldname: string]: Express.Multer.File[] };

      if (!files.vehiclePhotos || files.vehiclePhotos.length !== 7) {
        return res.status(400).json({ message: "All 7 vehicle photos are required" });
      }

      if (!files.paymentProof || files.paymentProof.length !== 1) {
        return res.status(400).json({ message: "Payment proof is required" });
      }

      // Process vehicle photos
      const vehiclePhotos: Record<string, string> = {};
      const photoTypes = ['frontWithCustomer', 'front', 'back', 'left', 'right', 'interiorMileage', 'knownDamage'];
      
      for (let i = 0; i < files.vehiclePhotos.length; i++) {
        const photoUrl = await imageProcessor.processVehiclePhoto(
          files.vehiclePhotos[i].buffer,
          files.vehiclePhotos[i].originalname
        );
        vehiclePhotos[photoTypes[i]] = photoUrl;
      }

      // Process payment proof
      const paymentProofUrl = await imageProcessor.processVehiclePhoto(
        files.paymentProof[0].buffer,
        files.paymentProof[0].originalname
      );

      // Process signature
      let signatureUrl = '';
      if (req.body.signatureData) {
        signatureUrl = await imageProcessor.processSignature(
          req.body.signatureData,
          'signature.png'
        );
      }

      const rental = await storage.createRental({
        ...rentalData,
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

      // Send email with PDF attachment
      await emailService.sendRentalAgreement(customer, pdfUrl, rentalRecord);

      res.json({ 
        message: "Agreement generated and emailed successfully",
        pdfUrl,
        downloadUrl: `/api/rentals/${rentalRecord.id}/download-agreement`
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
      const rental = await storage.getRentalsByCustomer(rentalId);
      
      if (!rental.length || !rental[0].agreementPdfUrl) {
        return res.status(404).json({ message: "Agreement not found" });
      }

      const pdfPath = path.join(process.cwd(), rental[0].agreementPdfUrl.replace('/', ''));
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
