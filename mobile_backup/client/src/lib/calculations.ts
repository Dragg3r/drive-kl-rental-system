export interface RentalCosts {
  subtotal: number;
  deposit: number;
  discount: number;
  grandTotal: number;
}

export interface RentalCalculationInput {
  rentalPerDay: number;
  deposit: number;
  discount: number;
  totalDays: number;
}

export function calculateRentalCosts(input: RentalCalculationInput): RentalCosts {
  const { rentalPerDay, deposit, discount, totalDays } = input;
  
  // Calculate rental subtotal (per day × total days)
  const subtotal = rentalPerDay * totalDays;
  
  // Calculate grand total (subtotal + deposit - discount)
  const grandTotal = Math.max(0, subtotal + deposit - discount);
  
  return {
    subtotal,
    deposit,
    discount,
    grandTotal
  };
}

export function calculateTotalDays(startDate: string, endDate: string): number {
  if (!startDate || !endDate) return 0;
  
  const start = new Date(startDate);
  const end = new Date(endDate);
  
  // Calculate difference in time
  const timeDifference = end.getTime() - start.getTime();
  
  // Calculate difference in days
  const dayDifference = Math.ceil(timeDifference / (1000 * 3600 * 24));
  
  return Math.max(1, dayDifference); // Minimum 1 day
}

export function formatCurrency(amount: number): string {
  return `RM ${amount.toFixed(2)}`;
}

export function calculateMileageOverage(
  actualMileage: number,
  allowedMileage: number,
  ratePerKm: number
): number {
  if (actualMileage <= allowedMileage) return 0;
  
  const overage = actualMileage - allowedMileage;
  return overage * ratePerKm;
}

export interface PenaltyCalculation {
  lateReturnFee: number;
  mileageOverage: number;
  fuelCharge: number;
  smokingFee: number;
  total: number;
}

export function calculatePenalties(options: {
  hoursLate?: number;
  actualMileage?: number;
  allowedMileage?: number;
  mileageRate?: number;
  fuelLevelDifference?: number;
  hasSmokingViolation?: boolean;
  vehicleCategory?: string;
}): PenaltyCalculation {
  const {
    hoursLate = 0,
    actualMileage = 0,
    allowedMileage = 0,
    mileageRate = 0,
    fuelLevelDifference = 0,
    hasSmokingViolation = false,
    vehicleCategory = ''
  } = options;

  // Late return penalty: RM 25-300 per hour based on vehicle category
  let lateReturnRate = 25; // Default rate
  if (vehicleCategory.includes('Super Car')) {
    lateReturnRate = 300;
  } else if (vehicleCategory.includes('Sports')) {
    lateReturnRate = 150;
  } else if (vehicleCategory.includes('Normal')) {
    lateReturnRate = 100;
  }
  
  const lateReturnFee = hoursLate * lateReturnRate;
  
  // Mileage overage
  const mileageOverage = calculateMileageOverage(actualMileage, allowedMileage, mileageRate);
  
  // Fuel charge: RM50–RM200 if not returned with same fuel level
  let fuelCharge = 0;
  if (fuelLevelDifference > 0) {
    fuelCharge = Math.min(200, Math.max(50, fuelLevelDifference * 25));
  }
  
  // Smoking fee: RM300
  const smokingFee = hasSmokingViolation ? 300 : 0;
  
  const total = lateReturnFee + mileageOverage + fuelCharge + smokingFee;
  
  return {
    lateReturnFee,
    mileageOverage,
    fuelCharge,
    smokingFee,
    total
  };
}

export function calculateGentingHighlandFee(vehicle: string): number {
  const category = getVehicleCategory(vehicle);
  
  // Genting Highland Usage Fee: RM150-RM350, vehicle-dependent
  switch (category) {
    case 'Super Car':
      return 350;
    case 'Sports Car':
    case 'Sports SUV':
      return 250;
    case 'Normal Sedan':
      return 200;
    case 'Free Mileage':
      return 150;
    default:
      return 200;
  }
}

function getVehicleCategory(vehicle: string): string | null {
  const vehicles = {
    "Sports Car": ["Porsche 911", "AMG CLA45s", "AMG A45s", "Nissan GTR", "Mustang"],
    "Sports SUV": ["Porsche Macan", "AMG G63"],
    "Super Car": ["AMG GTC50"],
    "Normal Sedan": ["Mercedes Benz A200/A35", "Mercedes Benz C200", "BMW F30 328i"],
    "Free Mileage": ["Alphard SC", "Honda Civic", "Ford Ranger", "Tesla Model 3"]
  };
  
  for (const [category, vehicleList] of Object.entries(vehicles)) {
    if (vehicleList.includes(vehicle)) {
      return category;
    }
  }
  return null;
}
