import React, { useState } from 'react';
import { 
  Printer, 
  ShieldCheck, 
  LogIn, 
  LogOut, 
  FileSearch, 
  Database, 
  ClipboardList, 
  LayoutDashboard, 
  PhoneCall,
  Menu,
  X,
  ChevronRight
} from 'lucide-react';
import { AgencyProfile, UserRole } from '../types.js';

export type NavTab = 'audit' | 'prospection' | 'dashboard' | 'scans' | 'projets' | 'groupes' | 'pipeline' | 'listes';

interface HeaderProps {
  activeTab: NavTab;
  setActiveTab: (tab: NavTab) => void;
  agency?: AgencyProfile;
  userName?: string;
  userRole?: UserRole;
  onOpenSettings?: () => void;
  onOpenMethodology?: () => void;
  onOpenAdmin?: () => void;
  onOpenLogin?: () => void;
  onLogout?: () => void;
  onPrint?: () => void;
  hasCurrentAudit?: boolean;
  auditsCount?: number;
}

export const Header: React.FC<HeaderProps> = ({
  activeTab,
  setActiveTab,
  agency,
  userName,
  userRole = 'admin',
  onOpenAdmin,
  onOpenLogin,
  onLogout,
  onPrint,
  hasCurrentAudit,
  auditsCount = 0,
}) => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const displayUser = userName || agency?.consultantName || 'Julien Lavaud';
  const isPublic = userRole === 'public';

  const navItems: Array<{ id: NavTab; label: string; shortLabel: string; icon: any }> = [
    { id: 'audit', label: '1. Audit', shortLabel: 'Audit', icon: FileSearch },
    { id: 'prospection', label: '2. Prospection', shortLabel: 'Prospection', icon: PhoneCall },
    { id: 'dashboard', label: '3. Pipeline', shortLabel: 'Pipeline', icon: LayoutDashboard },
    { id: 'scans', label: '4. Scans', shortLabel: 'Scans', icon: Database },
    { id: 'listes', label: '5. Listes', shortLabel: 'Listes', icon: ClipboardList },
  ];

  const handleSelectTab = (tab: NavTab) => {
    setActiveTab(tab);
    setIsMobileMenuOpen(false);
  };

  return (
    <>
      <header className="sticky top-0 z-40 bg-white text-slate-800 shadow-sm border-b border-slate-200 no-print">
        <div className="max-w-[1850px] mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16 sm:h-18">
            {/* Logo GreenHumble */}
            <div 
              className="flex items-center space-x-3 cursor-pointer" 
              onClick={() => handleSelectTab('audit')}
            >
              <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#1e40af] flex items-center justify-center text-white font-bold shadow-sm shrink-0">
                <span className="text-lg sm:text-xl font-black">G</span>
              </div>
              <div>
                <div className="flex items-center space-x-2">
                  <span className="text-lg sm:text-2xl font-black tracking-tight text-slate-900 font-serif">
                    GreenHumble
                  </span>
                  <span className="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-widest">
                    {userRole}
                  </span>
                </div>
              </div>
            </div>

            {/* Desktop Navigation */}
            <nav className="hidden lg:flex items-center space-x-1 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
              {navItems.map((item) => (
                <button
                  key={item.id}
                  onClick={() => handleSelectTab(item.id)}
                  className={`flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    activeTab === item.id || (item.id === 'listes' && (activeTab === 'projets' || activeTab === 'groupes'))
                      ? 'bg-[#1e40af] text-white shadow-sm'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-white'
                  }`}
                >
                  <item.icon className="w-3.5 h-3.5" />
                  <span>{item.label}</span>
                  {item.id === 'scans' && auditsCount > 0 && (
                    <span className={`ml-1 px-1.5 py-0.5 text-[10px] font-black rounded-full ${
                      activeTab === 'scans' ? 'bg-white/25 text-white' : 'bg-slate-200 text-slate-700'
                    }`}>
                      {auditsCount}
                    </span>
                  )}
                </button>
              ))}
            </nav>

            {/* Desktop Actions */}
            <div className="hidden lg:flex items-center space-x-2.5">
              {hasCurrentAudit && onPrint && (
                <button
                  onClick={onPrint}
                  className="flex items-center space-x-1.5 px-3 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition-colors cursor-pointer shadow-sm"
                  title="Imprimer / Exporter PDF"
                >
                  <Printer className="w-3.5 h-3.5 text-emerald-600" />
                  <span>PDF</span>
                </button>
              )}

              {onOpenAdmin && (
                <button
                  onClick={onOpenAdmin}
                  className="flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 cursor-pointer shadow-sm"
                >
                  <ShieldCheck className="w-4 h-4 text-emerald-600" />
                  <span>Admin</span>
                </button>
              )}

              <div className="flex items-center space-x-2 pl-2 border-l border-slate-200">
                {isPublic ? (
                  <button
                    onClick={onOpenLogin}
                    className="flex items-center space-x-1.5 px-3.5 py-2 text-xs font-bold text-white bg-[#1e40af] hover:bg-[#1d4ed8] rounded-xl transition-all cursor-pointer shadow-sm"
                  >
                    <LogIn className="w-3.5 h-3.5" />
                    <span>Connexion</span>
                  </button>
                ) : (
                  <div className="flex items-center space-x-2">
                    <div className="w-7 h-7 rounded-lg bg-[#1e40af] text-white flex items-center justify-center font-bold text-xs">
                      {displayUser.substring(0, 1).toUpperCase()}
                    </div>
                    <span className="text-xs font-semibold text-slate-700 hidden xl:inline">
                      {displayUser}
                    </span>
                    <button
                      onClick={onLogout}
                      className="flex items-center space-x-1 px-3 py-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-colors cursor-pointer"
                    >
                      <LogOut className="w-3.5 h-3.5 text-rose-500" />
                      <span>Déconnexion</span>
                    </button>
                  </div>
                )}
              </div>
            </div>

            {/* Mobile & Tablet Header Controls */}
            <div className="flex lg:hidden items-center space-x-2">
              {onOpenAdmin && (
                <button
                  onClick={onOpenAdmin}
                  className="flex items-center space-x-1 px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 min-h-[44px]"
                  title="Console Admin"
                >
                  <ShieldCheck className="w-4 h-4" />
                  <span className="hidden sm:inline">Console Admin</span>
                </button>
              )}

              <button
                type="button"
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="p-2.5 rounded-xl bg-slate-100 text-slate-800 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1e40af] min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer"
                aria-label="Menu principal"
                aria-expanded={isMobileMenuOpen}
              >
                {isMobileMenuOpen ? <X className="w-6 h-6 text-rose-600" /> : <Menu className="w-6 h-6 text-slate-700" />}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Slide-down Drawer Overlay */}
        {isMobileMenuOpen && (
          <div className="lg:hidden border-t border-slate-200 bg-white/98 backdrop-blur-xl animate-fadeIn p-4 space-y-4 shadow-xl">
            <div className="space-y-1">
              <span className="text-[10px] font-black uppercase text-slate-500 tracking-wider px-3">
                Navigation Mobile & Tablette
              </span>
              <div className="space-y-1.5 pt-1">
                {navItems.map((item) => {
                  const isActive = activeTab === item.id || (item.id === 'listes' && (activeTab === 'projets' || activeTab === 'groupes'));
                  return (
                    <button
                      key={item.id}
                      onClick={() => handleSelectTab(item.id)}
                      className={`w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold transition-all min-h-[48px] cursor-pointer ${
                        isActive
                          ? 'bg-[#1e40af] text-white shadow-sm'
                          : 'bg-slate-50 text-slate-700 hover:bg-slate-100'
                      }`}
                    >
                      <div className="flex items-center space-x-3">
                        <item.icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-[#1e40af]'}`} />
                        <span className="text-sm font-bold">{item.label}</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        {item.id === 'scans' && auditsCount > 0 && (
                          <span className="px-2 py-0.5 text-[10px] font-black bg-white/20 rounded-full text-white">
                            {auditsCount}
                          </span>
                        )}
                        <ChevronRight className="w-4 h-4 opacity-50" />
                      </div>
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Mobile Actions Drawer */}
            <div className="pt-3 border-t border-slate-200 space-y-2">
              {hasCurrentAudit && onPrint && (
                <button
                  onClick={() => {
                    onPrint();
                    setIsMobileMenuOpen(false);
                  }}
                  className="w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold bg-white text-slate-700 hover:bg-slate-50 border border-slate-200 min-h-[44px]"
                >
                  <div className="flex items-center space-x-3">
                    <Printer className="w-4 h-4 text-emerald-600" />
                    <span>Exporter Rapport PDF</span>
                  </div>
                  <ChevronRight className="w-4 h-4 opacity-50" />
                </button>
              )}

              {onOpenAdmin && (
                <button
                  onClick={() => {
                    onOpenAdmin();
                    setIsMobileMenuOpen(false);
                  }}
                  className="w-full flex items-center justify-between px-4 py-3 rounded-2xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 min-h-[44px]"
                >
                  <div className="flex items-center space-x-3">
                    <ShieldCheck className="w-4 h-4 text-emerald-600" />
                    <span>Console Administration</span>
                  </div>
                  <ChevronRight className="w-4 h-4 opacity-50" />
                </button>
              )}

              {isPublic ? (
                <button
                  onClick={() => {
                    onOpenLogin?.();
                    setIsMobileMenuOpen(false);
                  }}
                  className="w-full flex items-center justify-center space-x-2 px-4 py-3 rounded-2xl text-xs font-bold text-white bg-[#1e40af] hover:bg-[#1d4ed8] shadow-sm min-h-[44px]"
                >
                  <LogIn className="w-4 h-4" />
                  <span>Se connecter</span>
                </button>
              ) : (
                <div className="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                  <div className="flex items-center space-x-2.5">
                    <div className="w-8 h-8 rounded-xl bg-[#1e40af] text-white flex items-center justify-center font-bold text-xs">
                      {displayUser.substring(0, 1).toUpperCase()}
                    </div>
                    <div>
                      <span className="text-xs font-bold text-slate-800 block">{displayUser}</span>
                      <span className="text-[10px] text-slate-500">Connecté</span>
                    </div>
                  </div>
                  <button
                    onClick={() => {
                      onLogout?.();
                      setIsMobileMenuOpen(false);
                    }}
                    className="flex items-center space-x-1 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-xl"
                  >
                    <LogOut className="w-3.5 h-3.5 text-rose-500" />
                    <span>Déconnexion</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        )}
      </header>

      {/* Fixed Bottom Mobile Navigation Bar for Ergonomic Thumb Use */}
      <nav className="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-xl border-t border-slate-200 lg:hidden shadow-lg no-print">
        <div className="grid grid-cols-5 h-16 max-w-md mx-auto">
          {navItems.map((item) => {
            const isActive = activeTab === item.id || (item.id === 'listes' && (activeTab === 'projets' || activeTab === 'groupes'));
            return (
              <button
                key={item.id}
                onClick={() => handleSelectTab(item.id)}
                className={`flex flex-col items-center justify-center space-y-1 transition-all relative cursor-pointer min-h-[44px] ${
                  isActive ? 'text-[#1e40af]' : 'text-slate-500 hover:text-slate-700'
                }`}
              >
                <div className="relative">
                  <item.icon className="w-5 h-5" />
                  {item.id === 'scans' && auditsCount > 0 && (
                    <span className="absolute -top-1.5 -right-2 px-1 py-0.2 text-[8px] font-black bg-[#1e40af] text-white rounded-full">
                      {auditsCount}
                    </span>
                  )}
                </div>
                <span className={`text-[10px] font-bold ${isActive ? 'text-slate-900 font-extrabold' : 'text-slate-500'}`}>
                  {item.shortLabel}
                </span>
                {isActive && (
                  <span className="absolute top-0 w-8 h-0.5 bg-[#1e40af] rounded-full" />
                )}
              </button>
            );
          })}
        </div>
      </nav>
    </>
  );
};
