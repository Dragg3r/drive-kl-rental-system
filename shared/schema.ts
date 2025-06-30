import { pgTable, text, serial, integer, boolean, timestamp, jsonb, decimal } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

export const customers = pgTable("customers", {
  id: serial("id").primaryKey(),
  fullName: text("full_name").notNull(),
  email: text("email").notNull().unique(),
  hashedPassword: text("hashed_password").notNull(), // IC/Passport hashed
  phone: text("phone").notNull(),
  address: text("address").notNull(),
  icPassportNumber: text("ic_passport_number"),
  icPassportUrl: text("ic_passport_url").notNull(),
  utilityBillUrl: text("utility_bill_url"),
  socialMediaHandle: text("social_media_handle"), // Instagram username or Facebook ID
  status: text("status").notNull().default("active"), // active, blacklisted
  hasAcceptedTerms: boolean("has_accepted_terms").notNull().default(false),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const rentals = pgTable("rentals", {
  id: serial("id").primaryKey(),
  customerId: integer("customer_id").references(() => customers.id).notNull(),
  vehicle: text("vehicle").notNull(),
  color: text("color").notNull(),
  mileageLimit: integer("mileage_limit").notNull(),
  extraMileageCharge: decimal("extra_mileage_charge", { precision: 10, scale: 2 }).notNull(),
  fuelLevel: integer("fuel_level").notNull(), // 0-8 scale
  startDate: timestamp("start_date").notNull(),
  endDate: timestamp("end_date").notNull(),
  totalDays: integer("total_days").notNull(),
  rentalPerDay: decimal("rental_per_day", { precision: 10, scale: 2 }).notNull(),
  deposit: decimal("deposit", { precision: 10, scale: 2 }).notNull(),
  discount: decimal("discount", { precision: 10, scale: 2 }).default("0").notNull(),
  grandTotal: decimal("grand_total", { precision: 10, scale: 2 }).notNull(),
  vehiclePhotos: jsonb("vehicle_photos").default({}), // URLs of photos (optional)
  paymentProofUrl: text("payment_proof_url").default(""),
  signatureUrl: text("signature_url").default(""),
  agreementPdfUrl: text("agreement_pdf_url"),
  status: text("status").notNull().default("pending"), // pending, completed
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const staff = pgTable("staff", {
  id: serial("id").primaryKey(),
  username: text("username").notNull().unique(),
  hashedPassword: text("hashed_password").notNull(),
  createdAt: timestamp("created_at").defaultNow().notNull(),
});

export const insertCustomerSchema = createInsertSchema(customers).omit({
  id: true,
  createdAt: true,
  hasAcceptedTerms: true,
  status: true,
}).partial({
  utilityBillUrl: true,
  socialMediaHandle: true,
});

export const insertRentalSchema = createInsertSchema(rentals).omit({
  id: true,
  createdAt: true,
  status: true,
  agreementPdfUrl: true,
});

export const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

export const staffLoginSchema = z.object({
  username: z.string().min(1),
  password: z.string().min(1),
});

export type Customer = typeof customers.$inferSelect;
export type InsertCustomer = z.infer<typeof insertCustomerSchema>;
export type Rental = typeof rentals.$inferSelect;
export type InsertRental = z.infer<typeof insertRentalSchema>;
export type Staff = typeof staff.$inferSelect;
