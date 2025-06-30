import { UserCheck, UserPlus, Bus } from "lucide-react";

interface RoleSelectionProps {
  onViewChange: (view: string) => void;
}

export default function RoleSelection({ onViewChange }: RoleSelectionProps) {
  return (
    <div className="max-w-md mx-auto animate-fade-in">
      <div className="glass rounded-2xl p-8 text-center">
        <div className="w-20 h-20 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl mx-auto mb-6 flex items-center justify-center">
          <UserCheck className="text-white" size={32} />
        </div>
        <h2 className="text-2xl font-bold text-slate-800 mb-2">Welcome to Drive KL</h2>
        <p className="text-slate-600 mb-8">Choose your role to get started</p>
        
        <div className="space-y-4">
          <button 
            onClick={() => onViewChange('customer-login')}
            className="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all duration-300 flex items-center justify-center space-x-3"
          >
            <UserCheck size={20} />
            <span>Existing Customer</span>
          </button>
          
          <button 
            onClick={() => onViewChange('customer-registration')}
            className="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-4 rounded-xl font-semibold hover:from-purple-700 hover:to-purple-800 transition-all duration-300 flex items-center justify-center space-x-3"
          >
            <UserPlus size={20} />
            <span>New Customer</span>
          </button>
          
          <button 
            onClick={() => onViewChange('staff-dashboard')}
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
