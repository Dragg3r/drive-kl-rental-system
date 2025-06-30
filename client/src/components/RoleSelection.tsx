import { UserCheck, UserPlus, Bus } from "lucide-react";
import dklLogoPath from "@assets/dkl_1751303894484.png";

interface RoleSelectionProps {
  onViewChange: (view: string) => void;
}

export default function RoleSelection({ onViewChange }: RoleSelectionProps) {
  return (
    <div className="max-w-md mx-auto animate-fade-in">
      <div className="glass rounded-2xl p-8 text-center">
        <div className="flex items-center justify-center mb-6">
          <img src={dklLogoPath} alt="DKL Logo" className="w-24 h-20 object-contain" />
        </div>
        <h2 className="text-2xl font-bold text-slate-800 mb-2">Drive KL Rental System</h2>
        <p className="text-slate-600 mb-8">By Akib</p>
        
        <div className="space-y-4">
          <button 
            onClick={() => onViewChange('customer-login')}
            className="w-full bg-gradient-to-r from-red-600 to-red-700 text-white py-4 rounded-xl font-semibold hover:from-red-700 hover:to-red-800 transition-all duration-300 flex items-center justify-center space-x-3"
          >
            <UserCheck size={20} />
            <span>Existing Customer</span>
          </button>
          
          <button 
            onClick={() => onViewChange('customer-registration')}
            className="w-full bg-gradient-to-r from-red-500 to-red-600 text-white py-4 rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-300 flex items-center justify-center space-x-3"
          >
            <UserPlus size={20} />
            <span>New Customer</span>
          </button>
          
          <button 
            onClick={() => onViewChange('staff-login')}
            className="w-full border-2 border-slate-300 text-slate-700 py-4 rounded-xl font-semibold hover:bg-slate-50 transition-all duration-300 flex items-center justify-center space-x-3"
          >
            <Bus size={20} />
            <span>DKL Staff Login</span>
          </button>
        </div>
      </div>
    </div>
  );
}
