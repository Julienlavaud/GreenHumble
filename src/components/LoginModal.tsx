import React, { useState } from 'react';
import { Lock, Eye, EyeOff, User, X, KeyRound, ShieldAlert } from 'lucide-react';

interface LoginModalProps {
  isOpen: boolean;
  onClose: () => void;
  onLogin: (identifier: string, pass: string) => Promise<boolean>;
  onSwitchToPublic?: () => void;
}

export const LoginModal: React.FC<LoginModalProps> = ({
  isOpen,
  onClose,
  onLogin,
  onSwitchToPublic,
}) => {
  const [identifier, setIdentifier] = useState<string>('admin');
  const [password, setPassword] = useState<string>('');
  const [showPassword, setShowPassword] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!password.trim()) {
      setError('Veuillez saisir le mot de passe.');
      return;
    }

    setError(null);
    setIsLoading(true);

    try {
      const idToUse = identifier.trim() || 'admin';
      const success = await onLogin(idToUse, password.trim());
      if (!success) {
        setError('Mot de passe ou identifiant incorrect.');
      } else {
        setPassword('');
        onClose();
      }
    } catch {
      setError('Erreur de connexion au serveur.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
      <div className="bg-white rounded-3xl max-w-md w-full border border-slate-200 p-6 sm:p-8 space-y-6 shadow-2xl relative text-slate-800">
        {/* Close button */}
        <button
          onClick={onClose}
          className="absolute top-4 right-4 p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Header */}
        <div className="text-center space-y-2">
          <div className="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-2 border border-blue-100">
            <KeyRound className="w-6 h-6" />
          </div>
          <h3 className="text-xl font-black text-slate-900 tracking-tight font-serif">Accès Espace Consultant & Admin</h3>
          <p className="text-xs text-slate-500">
            Veuillez renseigner votre mot de passe pour accéder au cockpit métier.
          </p>
        </div>

        {error && (
          <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs text-center font-medium flex items-center justify-center space-x-2">
            <ShieldAlert className="w-4 h-4 shrink-0 text-rose-600" />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Identifiant / Email
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <User className="w-4 h-4" />
              </div>
              <input
                type="text"
                value={identifier}
                onChange={(e) => setIdentifier(e.target.value)}
                placeholder="admin ou email"
                className="w-full pl-10 pr-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Mot de passe requis *
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <Lock className="w-4 h-4" />
              </div>
              <input
                type={showPassword ? 'text' : 'password'}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                autoFocus
                placeholder="Saisissez votre mot de passe"
                className="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={isLoading || !password.trim()}
            className={`w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-sm cursor-pointer ${
              isLoading || !password.trim() ? 'opacity-50 cursor-not-allowed' : 'active:scale-98'
            }`}
          >
            {isLoading ? 'Vérification...' : 'Déverrouiller le Cockpit'}
          </button>

          {onSwitchToPublic && (
            <button
              type="button"
              onClick={() => {
                onSwitchToPublic();
                onClose();
              }}
              className="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 text-xs font-semibold border border-slate-200 transition-colors cursor-pointer"
            >
              Mode Public (Scanner Visiteur)
            </button>
          )}
        </form>
      </div>
    </div>
  );
};
