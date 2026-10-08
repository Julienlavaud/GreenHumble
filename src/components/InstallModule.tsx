import React, { useState } from 'react';
import { CheckCircle, AlertTriangle, Lock } from 'lucide-react';

export const InstallModule: React.FC = () => {
  const [step, setStep] = useState(1);
  const [password, setPassword] = useState('');
  const [passwordError, setPasswordError] = useState('');

  const validatePassword = (pwd: string) => {
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/;
    return regex.test(pwd);
  };

  const handlePasswordChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const pwd = e.target.value;
    setPassword(pwd);
    if (!validatePassword(pwd)) {
      setPasswordError('Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.');
    } else {
      setPasswordError('');
    }
  };

  return (
    <div className="p-6 bg-slate-50 min-h-screen">
      <header className="mb-8">
        <h1 className="text-2xl font-black text-slate-900">Installation du Système</h1>
      </header>

      <div className="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
        {step === 1 && (
          <div className="space-y-4">
            <h2 className="text-lg font-black text-slate-900">1. Vérification des prérequis</h2>
            <div className="space-y-2 text-sm">
                <p className="text-emerald-700">✓ Version PHP 8.x</p>
                <p className="text-emerald-700">✓ Extensions PDO, CURL</p>
                <p className="text-emerald-700">✓ Droits en écriture (/config, /logs)</p>
            </div>
            <button onClick={() => setStep(2)} className="px-4 py-2 bg-emerald-700 text-white rounded-xl text-sm font-bold">Suivant</button>
          </div>
        )}
        {step === 2 && (
          <div className="space-y-4">
            <h2 className="text-lg font-black text-slate-900">2. Configuration Base & Admin</h2>
            <input placeholder="Host DB" className="w-full px-4 py-2 border rounded-xl" />
            <input placeholder="Utilisateur" className="w-full px-4 py-2 border rounded-xl" />
            
            <div className="space-y-1">
                <label className="text-xs font-bold text-slate-700 flex items-center gap-1">
                    <Lock className="w-3 h-3" /> Mot de passe admin fort*
                </label>
                <input 
                    type="password" 
                    value={password}
                    onChange={handlePasswordChange}
                    className={`w-full px-4 py-2 border rounded-xl ${passwordError ? 'border-rose-500' : 'border-slate-300'}`} 
                />
                {passwordError && <p className="text-rose-500 text-xs">{passwordError}</p>}
            </div>
            <button onClick={() => setStep(3)} disabled={!!passwordError || !password} className="px-4 py-2 bg-emerald-700 text-white rounded-xl text-sm font-bold disabled:bg-slate-300">Valider</button>
          </div>
        )}
      </div>
    </div>
  );
};
