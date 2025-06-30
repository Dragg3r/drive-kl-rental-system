import { Button } from "@/components/ui/button";
import { CheckCircle, Download, Home } from "lucide-react";

interface FinalConfirmationProps {
  onViewChange: (view: string) => void;
  agreementData: any;
}

export default function FinalConfirmation({ onViewChange, agreementData }: FinalConfirmationProps) {
  const handleDownload = () => {
    if (agreementData?.downloadUrl) {
      window.open(agreementData.downloadUrl, '_blank');
    }
  };

  return (
    <div className="max-w-4xl mx-auto animate-fade-in">
      <div className="glass rounded-2xl p-8 text-center">
        <div className="w-20 h-20 bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl mx-auto mb-6 flex items-center justify-center">
          <CheckCircle className="text-white" size={40} />
        </div>
        <h2 className="text-3xl font-bold text-slate-800 mb-4">Agreement Generated Successfully!</h2>
        <p className="text-slate-600 mb-8">Your rental agreement has been created and emailed to you.</p>
        
        <div className="glass-dark rounded-xl p-6 mb-8">
          <h3 className="text-lg font-semibold text-slate-800 mb-4">Agreement Summary</h3>
          <div className="grid md:grid-cols-2 gap-4 text-left">
            <div>
              <p className="text-sm text-slate-600">Customer</p>
              <p className="font-semibold text-slate-800">{agreementData?.customerName || 'N/A'}</p>
            </div>
            <div>
              <p className="text-sm text-slate-600">Vehicle</p>
              <p className="font-semibold text-slate-800">{agreementData?.vehicle || 'N/A'}</p>
            </div>
            <div>
              <p className="text-sm text-slate-600">Rental Period</p>
              <p className="font-semibold text-slate-800">{agreementData?.period || 'N/A'}</p>
            </div>
            <div>
              <p className="text-sm text-slate-600">Total Amount</p>
              <p className="font-semibold text-green-600 text-lg">
                RM {agreementData?.total || '0.00'}
              </p>
            </div>
          </div>
        </div>

        <div className="flex justify-center space-x-4">
          <Button 
            onClick={handleDownload}
            className="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-3"
          >
            <Download className="mr-2" size={16} />
            Download PDF
          </Button>
          <Button 
            onClick={() => onViewChange('role-selection')}
            variant="outline" 
            className="px-8 py-3"
          >
            <Home className="mr-2" size={16} />
            Return Home
          </Button>
        </div>
      </div>
    </div>
  );
}
