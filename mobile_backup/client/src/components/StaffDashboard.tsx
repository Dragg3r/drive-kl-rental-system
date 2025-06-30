import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { getQueryFn, apiRequest } from "@/lib/queryClient";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { 
  Users, FileText, Eye, Ban, Key, Check, Download, 
  Search, Filter, LogOut 
} from "lucide-react";

interface StaffDashboardProps {
  onViewChange: (view: string) => void;
}

export default function StaffDashboard({ onViewChange }: StaffDashboardProps) {
  const [resetPasswordId, setResetPasswordId] = useState<number | null>(null);
  const [newPassword, setNewPassword] = useState("");
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const { data: customers, isLoading: customersLoading } = useQuery({
    queryKey: ['/api/staff/customers'],
    queryFn: getQueryFn({ on401: "throw" }),
  });

  const { data: rentals, isLoading: rentalsLoading } = useQuery({
    queryKey: ['/api/staff/rentals'],
    queryFn: getQueryFn({ on401: "throw" }),
  });

  const updateStatusMutation = useMutation({
    mutationFn: async ({ customerId, status }: { customerId: number; status: string }) => {
      return apiRequest('PATCH', `/api/staff/customers/${customerId}/status`, { status });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/staff/customers'] });
      toast({
        title: "Status Updated",
        description: "Customer status has been updated successfully.",
      });
    },
    onError: (error: Error) => {
      toast({
        title: "Update Failed",
        description: error.message,
        variant: "destructive",
      });
    },
  });

  const resetPasswordMutation = useMutation({
    mutationFn: async ({ customerId, newPassword }: { customerId: number; newPassword: string }) => {
      return apiRequest('PATCH', `/api/staff/customers/${customerId}/reset-password`, { newPassword });
    },
    onSuccess: () => {
      setResetPasswordId(null);
      setNewPassword("");
      toast({
        title: "Password Reset",
        description: "Customer password has been reset successfully.",
      });
    },
    onError: (error: Error) => {
      toast({
        title: "Reset Failed",
        description: error.message,
        variant: "destructive",
      });
    },
  });

  const handleStatusToggle = (customerId: number, currentStatus: string) => {
    const newStatus = currentStatus === 'active' ? 'blacklisted' : 'active';
    updateStatusMutation.mutate({ customerId, status: newStatus });
  };

  const handleResetPassword = (customerId: number) => {
    if (!newPassword.trim()) {
      toast({
        title: "Invalid Password",
        description: "Please enter a new IC/Passport number.",
        variant: "destructive",
      });
      return;
    }
    resetPasswordMutation.mutate({ customerId, newPassword });
  };

  const getStatusBadgeVariant = (status: string) => {
    switch (status) {
      case 'active':
        return 'default';
      case 'blacklisted':
        return 'destructive';
      default:
        return 'secondary';
    }
  };

  if (customersLoading || rentalsLoading) {
    return (
      <div className="flex items-center justify-center min-h-96">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="animate-fade-in">
      <div className="flex flex-col lg:flex-row gap-8">
        {/* Customer Database */}
        <div className="flex-1">
          <div className="glass rounded-2xl p-6">
            <div className="flex items-center justify-between mb-6">
              <h3 className="text-xl font-bold text-slate-800 flex items-center">
                <Users className="mr-2" size={24} />
                Customer Database
              </h3>
              <div className="flex items-center space-x-2">
                <Button variant="outline" size="sm">
                  <Search size={16} />
                </Button>
                <Button variant="outline" size="sm">
                  <Filter size={16} />
                </Button>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-slate-200">
                    <th className="text-left py-3 px-4 text-sm font-semibold text-slate-700">Customer</th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-slate-700">Status</th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-slate-700">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {customers?.map((customer: any) => (
                    <tr key={customer.id} className="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                      <td className="py-4 px-4">
                        <div>
                          <p className="font-semibold text-slate-800">{customer.fullName}</p>
                          <p className="text-sm text-slate-600">{customer.email}</p>
                        </div>
                      </td>
                      <td className="py-4 px-4">
                        <Badge variant={getStatusBadgeVariant(customer.status)}>
                          {customer.status === 'active' ? 'Active' : 'Blacklisted'}
                        </Badge>
                      </td>
                      <td className="py-4 px-4">
                        <div className="flex items-center space-x-2">
                          <Button
                            size="sm"
                            variant="outline"
                            className="p-2"
                            onClick={() => window.open(customer.icPassportUrl, '_blank')}
                            title="View IC"
                          >
                            <Eye size={16} />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            className={`p-2 ${customer.status === 'active' ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'}`}
                            onClick={() => handleStatusToggle(customer.id, customer.status)}
                            title={customer.status === 'active' ? 'Blacklist' : 'Un-blacklist'}
                            disabled={updateStatusMutation.isPending}
                          >
                            {customer.status === 'active' ? <Ban size={16} /> : <Check size={16} />}
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            className="p-2 text-orange-600 hover:text-orange-700"
                            onClick={() => setResetPasswordId(customer.id)}
                            title="Reset Password"
                          >
                            <Key size={16} />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Rental Agreements */}
        <div className="lg:w-96">
          <div className="glass rounded-2xl p-6">
            <h3 className="text-xl font-bold text-slate-800 mb-6 flex items-center">
              <FileText className="mr-2" size={24} />
              Rental Agreements
            </h3>

            <div className="space-y-4">
              {rentals?.slice(0, 10).map((rental: any) => (
                <div key={rental.id} className="glass-dark rounded-xl p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="font-semibold text-slate-800">
                        {rental.agreementPdfUrl ? rental.agreementPdfUrl.split('/').pop() : `Rental-${rental.id}.pdf`}
                      </p>
                      <p className="text-sm text-slate-600">
                        {new Date(rental.createdAt).toLocaleDateString()}
                      </p>
                    </div>
                    <div className="flex space-x-2">
                      <Button
                        size="sm"
                        variant="outline"
                        className="p-2 text-blue-600 hover:text-blue-700"
                        title="View"
                        onClick={() => {
                          if (rental.agreementPdfUrl) {
                            window.open(rental.agreementPdfUrl, '_blank');
                          }
                        }}
                      >
                        <Eye size={16} />
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        className="p-2 text-green-600 hover:text-green-700"
                        title="Download"
                        onClick={() => {
                          if (rental.agreementPdfUrl) {
                            window.open(`/api/rentals/${rental.id}/download-agreement`, '_blank');
                          }
                        }}
                      >
                        <Download size={16} />
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Reset Password Modal */}
      {resetPasswordId && (
        <div className="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50">
          <div className="glass rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 className="text-xl font-bold text-slate-800 mb-4">Reset Customer Password</h3>
            <p className="text-slate-600 mb-6">Enter the new IC/Passport number for this customer:</p>
            
            <Input
              type="password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              placeholder="New IC/Passport number"
              className="input-glass mb-6"
            />

            <div className="flex space-x-4">
              <Button
                variant="outline"
                onClick={() => {
                  setResetPasswordId(null);
                  setNewPassword("");
                }}
                className="flex-1"
              >
                Cancel
              </Button>
              <Button
                onClick={() => handleResetPassword(resetPasswordId)}
                disabled={resetPasswordMutation.isPending}
                className="flex-1 btn-gradient"
              >
                {resetPasswordMutation.isPending ? 'Resetting...' : 'Reset Password'}
              </Button>
            </div>
          </div>
        </div>
      )}

      <div className="mt-8 text-center">
        <Button
          onClick={() => onViewChange('role-selection')}
          className="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white px-8 py-3"
        >
          <LogOut className="mr-2" size={16} />
          Logout
        </Button>
      </div>
    </div>
  );
}
