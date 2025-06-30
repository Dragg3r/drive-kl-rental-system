import { useState } from "react";
import RoleSelection from "@/components/RoleSelection";
import CustomerRegistration from "@/components/CustomerRegistration";
import CustomerLogin from "@/components/CustomerLogin";
import TermsConditions from "@/components/TermsConditions";
import RentalForm from "@/components/RentalForm";
import StaffDashboard from "@/components/StaffDashboard";
import FinalConfirmation from "@/components/FinalConfirmation";
import { useAuth } from "@/hooks/useAuth";
import { Car, Bell } from "lucide-react";

type View = 'role-selection' | 'customer-registration' | 'customer-login' | 'terms-conditions' | 'rental-form' | 'staff-dashboard' | 'final-confirmation';

export default function HomePage() {
  const [currentView, setCurrentView] = useState<View>('role-selection');
  const [agreementData, setAgreementData] = useState<any>(null);
  const { customer, staff } = useAuth();

  const renderView = () => {
    switch (currentView) {
      case 'role-selection':
        return <RoleSelection onViewChange={setCurrentView} />;
      case 'customer-registration':
        return <CustomerRegistration onViewChange={setCurrentView} />;
      case 'customer-login':
        return <CustomerLogin onViewChange={setCurrentView} />;
      case 'terms-conditions':
        return <TermsConditions onViewChange={setCurrentView} />;
      case 'rental-form':
        return <RentalForm onViewChange={setCurrentView} onComplete={setAgreementData} />;
      case 'staff-dashboard':
        return <StaffDashboard onViewChange={setCurrentView} />;
      case 'final-confirmation':
        return <FinalConfirmation onViewChange={setCurrentView} agreementData={agreementData} />;
      default:
        return <RoleSelection onViewChange={setCurrentView} />;
    }
  };

  return (
    <div className="min-h-screen">
      {/* Header */}
      <header className="glass fixed top-0 left-0 right-0 z-50 px-4 py-3">
        <div className="max-w-7xl mx-auto flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
              <Car className="text-white" size={20} />
            </div>
            <div>
              <h1 className="text-xl font-bold text-slate-800">Drive KL Executive</h1>
              <p className="text-sm text-slate-600">Car Rental Management</p>
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <button className="p-2 rounded-lg glass-dark text-slate-700 hover:text-primary transition-colors">
              <Bell size={16} />
            </button>
            <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full"></div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="pt-20 pb-8">
        <div className="max-w-7xl mx-auto px-4">
          {renderView()}
        </div>
      </main>
    </div>
  );
}
