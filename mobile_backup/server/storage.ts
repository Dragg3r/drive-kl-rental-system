import { customers, rentals, staff, type Customer, type InsertCustomer, type Rental, type InsertRental, type Staff } from "@shared/schema";
import { db } from "./db";
import { eq, desc } from "drizzle-orm";
import bcrypt from "bcrypt";

export interface IStorage {
  // Customer methods
  createCustomer(customer: InsertCustomer): Promise<Customer>;
  getCustomerByEmail(email: string): Promise<Customer | undefined>;
  getCustomerById(id: number): Promise<Customer | undefined>;
  updateCustomerTermsAcceptance(id: number): Promise<void>;
  updateCustomerStatus(id: number, status: string): Promise<void>;
  updateCustomerPassword(id: number, hashedPassword: string): Promise<void>;
  getAllCustomers(): Promise<Customer[]>;

  // Rental methods
  createRental(rental: InsertRental): Promise<Rental>;
  getRentalById(id: number): Promise<Rental | undefined>;
  updateRentalPdf(id: number, pdfUrl: string): Promise<void>;
  getRentalsByCustomer(customerId: number): Promise<Rental[]>;
  getAllRentals(): Promise<Rental[]>;

  // Staff methods
  getStaffByUsername(username: string): Promise<Staff | undefined>;

  // Auth helpers
  hashPassword(password: string): Promise<string>;
  comparePassword(password: string, hash: string): Promise<boolean>;
}

export class DatabaseStorage implements IStorage {
  async createCustomer(insertCustomer: InsertCustomer): Promise<Customer> {
    const customerData = {
      ...insertCustomer,
      utilityBillUrl: insertCustomer.utilityBillUrl || null,
      socialMediaHandle: insertCustomer.socialMediaHandle || null,
    };
    
    const [customer] = await db
      .insert(customers)
      .values(customerData)
      .returning();
    return customer;
  }

  async getCustomerByEmail(email: string): Promise<Customer | undefined> {
    const [customer] = await db.select().from(customers).where(eq(customers.email, email));
    return customer || undefined;
  }

  async getCustomerById(id: number): Promise<Customer | undefined> {
    const [customer] = await db.select().from(customers).where(eq(customers.id, id));
    return customer || undefined;
  }

  async updateCustomerTermsAcceptance(id: number): Promise<void> {
    await db
      .update(customers)
      .set({ hasAcceptedTerms: true })
      .where(eq(customers.id, id));
  }

  async updateCustomerStatus(id: number, status: string): Promise<void> {
    await db
      .update(customers)
      .set({ status })
      .where(eq(customers.id, id));
  }

  async updateCustomerPassword(id: number, hashedPassword: string): Promise<void> {
    await db
      .update(customers)
      .set({ hashedPassword })
      .where(eq(customers.id, id));
  }

  async getAllCustomers(): Promise<Customer[]> {
    return await db.select().from(customers).orderBy(customers.fullName);
  }

  async createRental(insertRental: InsertRental): Promise<Rental> {
    const [rental] = await db
      .insert(rentals)
      .values(insertRental)
      .returning();
    return rental;
  }

  async getRentalById(id: number): Promise<Rental | undefined> {
    const [rental] = await db.select().from(rentals).where(eq(rentals.id, id));
    return rental || undefined;
  }

  async updateRentalPdf(id: number, pdfUrl: string): Promise<void> {
    await db
      .update(rentals)
      .set({ agreementPdfUrl: pdfUrl, status: "completed" })
      .where(eq(rentals.id, id));
  }

  async getRentalsByCustomer(customerId: number): Promise<Rental[]> {
    return await db.select().from(rentals).where(eq(rentals.customerId, customerId)).orderBy(desc(rentals.createdAt));
  }

  async getAllRentals(): Promise<Rental[]> {
    return await db.select().from(rentals).orderBy(desc(rentals.createdAt));
  }

  async getStaffByUsername(username: string): Promise<Staff | undefined> {
    const [staffMember] = await db.select().from(staff).where(eq(staff.username, username));
    return staffMember || undefined;
  }

  async hashPassword(password: string): Promise<string> {
    return await bcrypt.hash(password, 12);
  }

  async comparePassword(password: string, hash: string): Promise<boolean> {
    return await bcrypt.compare(password, hash);
  }
}

export const storage = new DatabaseStorage();
