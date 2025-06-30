// Vehicle categories and their mileage information as per requirements
export const vehicles = {
  "Sports Car": [
    "Porsche 911",
    "AMG CLA45s", 
    "AMG A45s",
    "Nissan GTR",
    "Mustang"
  ],
  "Sports SUV": [
    "Porsche Macan",
    "AMG G63"
  ],
  "Super Car": [
    "AMG GTC50"
  ],
  "Normal Sedan": [
    "Mercedes Benz A200/A35",
    "Mercedes Benz C200", 
    "BMW F30 328i"
  ],
  "Free Mileage": [
    "Alphard SC",
    "Honda Civic",
    "Ford Ranger",
    "Tesla Model 3"
  ]
};

export interface MileageInfo {
  limit: string;
  charge: string;
  numericLimit: number;
  numericCharge: number;
}

// Mileage limits and charges based on vehicle categories from requirements
export const vehicleMileageMap: Record<string, MileageInfo> = {
  // Sports Car
  "Porsche 911": { limit: "170 KM", charge: "RM 2.50", numericLimit: 170, numericCharge: 2.50 },
  "AMG CLA45s": { limit: "170 KM", charge: "RM 2.50", numericLimit: 170, numericCharge: 2.50 },
  "AMG A45s": { limit: "170 KM", charge: "RM 2.50", numericLimit: 170, numericCharge: 2.50 },
  "Nissan GTR": { limit: "170 KM", charge: "RM 2.50", numericLimit: 170, numericCharge: 2.50 },
  "Mustang": { limit: "170 KM", charge: "RM 2.50", numericLimit: 170, numericCharge: 2.50 },
  
  // Sports SUV  
  "Porsche Macan": { limit: "170 KM", charge: "RM 2.50", numericLimit: 170, numericCharge: 2.50 },
  "AMG G63": { limit: "170 KM", charge: "RM 2.50", numericLimit: 170, numericCharge: 2.50 },
  
  // Super Car
  "AMG GTC50": { limit: "150 KM", charge: "RM 3.50", numericLimit: 150, numericCharge: 3.50 },
  
  // Normal Sedan
  "Mercedes Benz A200/A35": { limit: "200 KM", charge: "RM 3.50", numericLimit: 200, numericCharge: 3.50 },
  "Mercedes Benz C200": { limit: "200 KM", charge: "RM 3.50", numericLimit: 200, numericCharge: 3.50 },
  "BMW F30 328i": { limit: "200 KM", charge: "RM 3.50", numericLimit: 200, numericCharge: 3.50 },
  
  // Free Mileage
  "Alphard SC": { limit: "499 KM", charge: "RM 1.00", numericLimit: 499, numericCharge: 1.00 },
  "Honda Civic": { limit: "499 KM", charge: "RM 1.00", numericLimit: 499, numericCharge: 1.00 },
  "Ford Ranger": { limit: "499 KM", charge: "RM 1.00", numericLimit: 499, numericCharge: 1.00 },
  "Tesla Model 3": { limit: "499 KM", charge: "RM 1.00", numericLimit: 499, numericCharge: 1.00 }
};

export function getVehicleMileageInfo(vehicle: string): MileageInfo | null {
  return vehicleMileageMap[vehicle] || null;
}

export function getVehicleCategory(vehicle: string): string | null {
  for (const [category, vehicleList] of Object.entries(vehicles)) {
    if (vehicleList.includes(vehicle)) {
      return category;
    }
  }
  return null;
}

export const availableColors = [
  "White",
  "Silver", 
  "Black",
  "Blue",
  "Red",
  "Green",
  "Maroon",
  "Others"
];

export const fuelLevels = [
  "Empty",
  "1/8",
  "1/4", 
  "3/8",
  "1/2",
  "5/8",
  "3/4",
  "7/8",
  "Full"
];

export function getFuelLevelText(level: number): string {
  return fuelLevels[level] || "Unknown";
}
