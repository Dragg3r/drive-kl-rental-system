import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { loginSchema } from "@shared/schema";
import { useMutation } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";
import { useAuth } from "@/hooks/useAuth";
import { LogIn, ArrowLeft, Mail, Key } from "lucide-react";
import { z } from "zod";

type LoginData = z.infer<typeof loginSchema>;

interface CustomerLoginProps {
  onViewChange: (view: string) => void;
}

export default function CustomerLogin({ onViewChange }: CustomerLoginProps) {
  const { toast } = useToast();
  const { setCustomer } = useAuth();

  const form = useForm<LoginData>({
    resolver: zodResolver(loginSchema),
  });

  const loginMutation = useMutation({
    mutationFn: async (data: LoginData) => {
      const response = await apiRequest('POST', '/api/customers/login', data);
      return response.json();
    },
    onSuccess: (customer) => {
      setCustomer(customer);
      toast({
        title: "Login Successful",
        description: "Welcome back to Drive KL Executive!",
      });
      
      // Check if customer has accepted terms
      if (!customer.hasAcceptedTerms) {
        onViewChange('terms-conditions');
      } else {
        onViewChange('rental-form');
      }
    },
    onError: (error: Error) => {
      toast({
        title: "Login Failed",
        description: error.message,
        variant: "destructive",
      });
    },
  });

  const onSubmit = (data: LoginData) => {
    loginMutation.mutate(data);
  };

  return (
    <div className="max-w-md mx-auto animate-fade-in">
      <div className="glass rounded-2xl p-8">
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-2xl mx-auto mb-4 flex items-center justify-center">
            <LogIn className="text-white" size={24} />
          </div>
          <h2 className="text-2xl font-bold text-slate-800">Welcome back to Drive KL</h2>
          <p className="text-slate-600">Sign in to your account</p>
        </div>

        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <div>
            <Label className="flex items-center text-sm font-semibold text-slate-700 mb-2">
              <Mail className="mr-2" size={16} />
              Email Address
            </Label>
            <Input
              {...form.register('email')}
              type="email"
              className="input-glass"
              placeholder="Enter your email"
            />
            {form.formState.errors.email && (
              <p className="text-red-500 text-xs mt-1">{form.formState.errors.email.message}</p>
            )}
          </div>

          <div>
            <Label className="flex items-center text-sm font-semibold text-slate-700 mb-2">
              <Key className="mr-2" size={16} />
              IC / Passport Number
            </Label>
            <Input
              {...form.register('password')}
              type="password"
              className="input-glass"
              placeholder="Enter your IC/Passport number"
            />
            {form.formState.errors.password && (
              <p className="text-red-500 text-xs mt-1">{form.formState.errors.password.message}</p>
            )}
          </div>

          <div className="flex space-x-4">
            <Button
              type="button"
              onClick={() => onViewChange('role-selection')}
              variant="outline"
              className="flex-1"
            >
              <ArrowLeft className="mr-2" size={16} />
              Back
            </Button>
            <Button
              type="submit"
              disabled={loginMutation.isPending}
              className="flex-1 btn-gradient"
            >
              <LogIn className="mr-2" size={16} />
              {loginMutation.isPending ? 'Signing In...' : 'Sign In'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
