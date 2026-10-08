import React from 'react';
import { Scale, Leaf } from 'lucide-react';

interface ClimatLocalLogoProps {
  className?: string;
  size?: 'sm' | 'md' | 'lg';
}

export const ClimatLocalLogo: React.FC<ClimatLocalLogoProps> = ({ className = '', size = 'md' }) => {
  const iconSizes = {
    sm: 'w-7 h-7 rounded-xl',
    md: 'w-10 h-10 rounded-2xl',
    lg: 'w-14 h-14 rounded-3xl',
  };

  const symbolSizes = {
    sm: 'w-3.5 h-3.5',
    md: 'w-5 h-5',
    lg: 'w-7 h-7',
  };

  return (
    <div className={`flex items-center space-x-3 ${className}`}>
      <div className={`${iconSizes[size]} bg-gradient-to-br from-emerald-600 via-teal-700 to-slate-900 flex items-center justify-center text-white shadow-md shadow-emerald-900/10 shrink-0 relative`}>
        <Scale className={`${symbolSizes[size]} stroke-[2.2]`} />
        <div className="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center">
          <Leaf className="w-2 h-2 text-white" />
        </div>
      </div>
    </div>
  );
};
