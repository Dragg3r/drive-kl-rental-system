import { useState, useEffect, createContext, useContext, ReactNode } from "react";
import { Customer } from "@shared/schema";

interface AuthContextType {
  customer: Customer | null;
  staff: any | null;
  setCustomer: (customer: Customer | null) => void;
  setStaff: (staff: any | null) => void;
  logout: () => void;
  isAuthenticated: boolean;
  isStaff: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [customer, setCustomer] = useState<Customer | null>(null);
  const [staff, setStaff] = useState<any | null>(null);

  // Load auth state from localStorage on mount
  useEffect(() => {
    const savedCustomer = localStorage.getItem('dkl_customer');
    const savedStaff = localStorage.getItem('dkl_staff');
    
    if (savedCustomer) {
      try {
        setCustomer(JSON.parse(savedCustomer));
      } catch (error) {
        console.error('Error parsing saved customer:', error);
        localStorage.removeItem('dkl_customer');
      }
    }
    
    if (savedStaff) {
      try {
        setStaff(JSON.parse(savedStaff));
      } catch (error) {
        console.error('Error parsing saved staff:', error);
        localStorage.removeItem('dkl_staff');
      }
    }
  }, []);

  // Save customer to localStorage when it changes
  useEffect(() => {
    if (customer) {
      localStorage.setItem('dkl_customer', JSON.stringify(customer));
    } else {
      localStorage.removeItem('dkl_customer');
    }
  }, [customer]);

  // Save staff to localStorage when it changes
  useEffect(() => {
    if (staff) {
      localStorage.setItem('dkl_staff', JSON.stringify(staff));
    } else {
      localStorage.removeItem('dkl_staff');
    }
  }, [staff]);

  const logout = () => {
    setCustomer(null);
    setStaff(null);
    localStorage.removeItem('dkl_customer');
    localStorage.removeItem('dkl_staff');
  };

  const isAuthenticated = customer !== null || staff !== null;
  const isStaff = staff !== null;

  const value: AuthContextType = {
    customer,
    staff,
    setCustomer,
    setStaff,
    logout,
    isAuthenticated,
    isStaff
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

// Hook specifically for customer authentication
export function useCustomerAuth() {
  const { customer, setCustomer } = useAuth();
  
  return {
    customer,
    setCustomer,
    isLoggedIn: customer !== null,
    hasAcceptedTerms: customer?.hasAcceptedTerms || false,
    isActive: customer?.status === 'active'
  };
}

// Hook specifically for staff authentication  
export function useStaffAuth() {
  const { staff, setStaff } = useAuth();
  
  return {
    staff,
    setStaff,
    isLoggedIn: staff !== null,
    username: staff?.username
  };
}