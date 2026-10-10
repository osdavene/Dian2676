import React from 'react';
import { 
  Award, 
  BookOpen, 
  CheckCircle, 
  RotateCcw, 
  BarChart3, 
  HelpCircle, 
  Briefcase, 
  ChevronDown, 
  PlusCircle,
  FileCheck2
} from 'lucide-react';

export default function Navbar({ 
  currentTab, 
  setCurrentTab, 
  jobs, 
  selectedJob, 
  setSelectedJob, 
  onOpenJobModal 
}) {
  return (
    <header className="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-50 shadow-md">
      {/* Barra superior con bandera institucional */}
      <div className="bg-gradient-to-r from-amber-600 via-amber-500 to-yellow-500 h-1.5 w-full"></div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          
          {/* Logo y Convocatoria */}
          <div className="flex items-center space-x-3 cursor-pointer" onClick={() => setCurrentTab('dashboard')}>
            <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-700 to-indigo-900 flex items-center justify-center font-black text-amber-400 text-xl shadow-inner border border-amber-400/30">
              D
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <span className="font-extrabold text-lg tracking-wider text-white">DIAN 2676</span>
                <span className="bg-amber-500/20 text-amber-300 text-xs px-2 py-0.5 rounded-full font-semibold border border-amber-500/40">
                  CNSC - PAMC
                </span>
              </div>
              <p className="text-xs text-slate-400">Simulador Oficial de Pruebas Escritas</p>
            </div>
          </div>

          {/* Selector de Cargo (Analista III, Analista II, etc.) */}
          <div className="hidden md:flex items-center bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700">
            <Briefcase className="w-4 h-4 text-amber-400 mr-2" />
            <div className="text-xs mr-2">
              <span className="text-slate-400 block text-[10px]">CARGO OBJETIVO:</span>
              <select
                value={selectedJob?.id || ''}
                onChange={(e) => {
                  const job = jobs.find(j => j.id === parseInt(e.target.value, 10));
                  if (job) setSelectedJob(job);
                }}
                className="bg-transparent text-white font-semibold text-xs focus:outline-none cursor-pointer pr-1"
              >
                {jobs.map(job => (
                  <option key={job.id} value={job.id} className="bg-slate-900 text-white">
                    {job.name} ({job.code}) - {job.level}
                  </option>
                ))}
              </select>
            </div>
            <button
              onClick={onOpenJobModal}
              title="Añadir o gestionar nuevos cargos"
              className="text-slate-400 hover:text-amber-400 transition-colors p-1"
            >
              <PlusCircle className="w-4 h-4" />
            </button>
          </div>

          {/* Menú de Navegación */}
          <nav className="flex items-center space-x-1 lg:space-x-2">
            <button
              onClick={() => setCurrentTab('dashboard')}
              className={`flex items-center space-x-1.5 px-3 py-2 rounded-md text-xs font-medium transition-all ${
                currentTab === 'dashboard'
                  ? 'bg-blue-700 text-white shadow'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <Award className="w-4 h-4 text-amber-400" />
              <span>Inicio</span>
            </button>

            <button
              onClick={() => setCurrentTab('simulator')}
              className={`flex items-center space-x-1.5 px-3 py-2 rounded-md text-xs font-medium transition-all ${
                currentTab === 'simulator'
                  ? 'bg-blue-700 text-white shadow'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <FileCheck2 className="w-4 h-4 text-green-400" />
              <span>Simulacro</span>
            </button>

            <button
              onClick={() => setCurrentTab('practice')}
              className={`flex items-center space-x-1.5 px-3 py-2 rounded-md text-xs font-medium transition-all ${
                currentTab === 'practice'
                  ? 'bg-blue-700 text-white shadow'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <BookOpen className="w-4 h-4 text-sky-400" />
              <span>Práctica</span>
            </button>

            <button
              onClick={() => setCurrentTab('repaso')}
              className={`flex items-center space-x-1.5 px-3 py-2 rounded-md text-xs font-medium transition-all ${
                currentTab === 'repaso'
                  ? 'bg-blue-700 text-white shadow'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <RotateCcw className="w-4 h-4 text-rose-400" />
              <span>Repasar Fallos</span>
            </button>

            <button
              onClick={() => setCurrentTab('bank')}
              className={`flex items-center space-x-1.5 px-3 py-2 rounded-md text-xs font-medium transition-all ${
                currentTab === 'bank'
                  ? 'bg-blue-700 text-white shadow'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <HelpCircle className="w-4 h-4 text-purple-400" />
              <span className="hidden sm:inline">Banco Preguntas</span>
            </button>

            <button
              onClick={() => setCurrentTab('stats')}
              className={`flex items-center space-x-1.5 px-3 py-2 rounded-md text-xs font-medium transition-all ${
                currentTab === 'stats'
                  ? 'bg-blue-700 text-white shadow'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <BarChart3 className="w-4 h-4 text-amber-400" />
              <span className="hidden sm:inline">Estadísticas</span>
            </button>
          </nav>

        </div>
      </div>
    </header>
  );
}
