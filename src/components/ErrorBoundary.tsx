import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertOctagon, RefreshCw, Trash2, Download, Home, ShieldAlert } from 'lucide-react';

interface Props {
  children: ReactNode;
  fallbackTitle?: string;
}

interface State {
  hasError: boolean;
  error: Error | null;
  errorInfo: ErrorInfo | null;
  showDetails: boolean;
}

export class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false,
    error: null,
    errorInfo: null,
    showDetails: false,
  };

  public static getDerivedStateFromError(error: Error): State {
    return {
      hasError: true,
      error,
      errorInfo: null,
      showDetails: false,
    };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('CRITICAL APPLET ERROR CAUGHT BY ERROR_BOUNDARY:', error, errorInfo);
    this.setState({ errorInfo });

    try {
      // Record in local error log for diagnostic module
      const logs = JSON.parse(localStorage.getItem('climat_local_error_logs') || '[]');
      logs.unshift({
        timestamp: new Date().toISOString(),
        message: error.message,
        stack: error.stack,
        componentStack: errorInfo.componentStack,
        source: 'ReactErrorBoundary',
      });
      localStorage.setItem('climat_local_error_logs', JSON.stringify(logs.slice(0, 50)));
    } catch {
      // Ignore local storage error
    }
  }

  private handleReset = () => {
    this.setState({ hasError: false, error: null, errorInfo: null });
    window.location.reload();
  };

  private handleClearStorageAndReload = () => {
    if (confirm("Réinitialiser le cache local de l'application ? Vos données d'exemple seront restaurées par défaut.")) {
      try {
        localStorage.removeItem('climat_local_current_audit');
        localStorage.removeItem('climat_local_error_logs');
      } catch {}
      this.setState({ hasError: false, error: null, errorInfo: null });
      window.location.reload();
    }
  };

  private handleDownloadErrorReport = () => {
    const report = {
      timestamp: new Date().toISOString(),
      appName: 'Climat Local - Cockpit & Scanner',
      error: {
        message: this.state.error?.message,
        name: this.state.error?.name,
        stack: this.state.error?.stack,
      },
      componentStack: this.state.errorInfo?.componentStack,
      userAgent: navigator.userAgent,
      url: window.location.href,
    };

    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(report, null, 2));
    const a = document.createElement('a');
    a.href = dataStr;
    a.download = `rapport-erreur-climat-local-${Date.now()}.json`;
    a.click();
    a.remove();
  };

  public render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center p-4 font-sans">
          <div className="max-w-2xl w-full bg-slate-800/90 border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 backdrop-blur-md">
            {/* Header */}
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center shrink-0">
                <AlertOctagon className="w-6 h-6" />
              </div>
              <div>
                <span className="text-2xs font-extrabold uppercase tracking-widest text-rose-400 block">
                  Protection Anti-Page Blanche • Climat Local
                </span>
                <h1 className="text-xl sm:text-2xl font-black text-white font-serif tracking-tight">
                  {this.props.fallbackTitle || "Une interruption est survenue"}
                </h1>
              </div>
            </div>

            <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
              L'application a intercepté une anomalie d'exécution pour éviter tout affichage de page blanche. Vos dossiers et configurations sont préservés.
            </p>

            {/* Error Message Box */}
            <div className="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs font-mono text-rose-300 space-y-2">
              <div className="flex items-center justify-between text-2xs text-slate-400 font-sans font-bold">
                <span>Message d'erreur système :</span>
                <span className="text-slate-500">{new Date().toLocaleTimeString('fr-FR')}</span>
              </div>
              <p className="font-semibold break-words">
                {this.state.error?.message || "Erreur indéterminée"}
              </p>
            </div>

            {/* Technical stack toggle */}
            <div>
              <button
                type="button"
                onClick={() => this.setState({ showDetails: !this.state.showDetails })}
                className="text-2xs font-bold text-slate-400 hover:text-slate-200 underline"
              >
                {this.state.showDetails ? 'Masquer les détails techniques' : 'Afficher les détails techniques (Stack Trace)'}
              </button>
              {this.state.showDetails && (
                <div className="mt-2 p-3 rounded-xl bg-slate-950 border border-slate-800 text-3xs font-mono text-slate-400 max-h-48 overflow-y-auto whitespace-pre-wrap">
                  {this.state.error?.stack}
                  {this.state.errorInfo?.componentStack}
                </div>
              )}
            </div>

            {/* Action buttons */}
            <div className="pt-2 border-t border-slate-700/80 flex flex-wrap items-center justify-between gap-3">
              <div className="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  onClick={this.handleReset}
                  className="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center space-x-1.5 shadow-md transition-colors"
                >
                  <RefreshCw className="w-3.5 h-3.5" />
                  <span>Recharger l'application</span>
                </button>

                <button
                  type="button"
                  onClick={this.handleClearStorageAndReload}
                  className="px-3.5 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 font-bold text-xs flex items-center space-x-1.5 transition-colors"
                  title="Efface les données corrompues en mémoire locale"
                >
                  <Trash2 className="w-3.5 h-3.5 text-amber-400" />
                  <span>Purger le cache</span>
                </button>
              </div>

              <button
                type="button"
                onClick={this.handleDownloadErrorReport}
                className="px-3 py-2 rounded-xl bg-slate-900 hover:bg-black text-slate-300 font-semibold text-xs border border-slate-700 flex items-center space-x-1.5 transition-colors"
              >
                <Download className="w-3.5 h-3.5" />
                <span>Rapport d'incident (.json)</span>
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
