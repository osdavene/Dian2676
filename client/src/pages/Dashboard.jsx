import React from 'react';
import { 
  Award, 
  CheckCircle, 
  XCircle, 
  Clock, 
  FileCheck2, 
  BookOpen, 
  RotateCcw, 
  HelpCircle, 
  BarChart3, 
  AlertTriangle, 
  ArrowRight,
  TrendingUp,
  ShieldCheck,
  Building2,
  ChevronRight
} from 'lucide-react';

export default function Dashboard({ 
  selectedJob, 
  stats, 
  onStartExam, 
  onStartPractice, 
  onStartReview, 
  onViewHistoryItem, 
  setCurrentTab 
}) {
  const summary = stats?.summary || {};
  const axisPerformance = stats?.axis_performance || [];
  const evolution = stats?.evolution || [];

  const isApproved = (summary.average_score || 0) >= 70.00;

  return (
    <div className="space-y-8 pb-12">
      
      {/* Banner de Cargo y Resumen del Concurso */}
      <div className="bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 rounded-2xl p-6 sm:p-8 text-white shadow-xl border border-slate-800 relative overflow-hidden">
        <div className="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div className="space-y-2 max-w-2xl">
            <div className="inline-flex items-center space-x-2 bg-amber-500/20 text-amber-300 text-xs px-3 py-1 rounded-full font-bold border border-amber-500/30">
              <ShieldCheck className="w-4 h-4" />
              <span>Concurso DIAN 2676 • CNSC & Universidad del Área Andina</span>
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
              Preparación para: <span className="text-amber-400">{selectedJob?.name}</span>
            </h1>
            <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
              <strong>{selectedJob?.code}</strong> • {selectedJob?.level} • Grado {selectedJob?.grade} • Subproceso: <em>{selectedJob?.subprocess}</em>
            </p>
            <div className="pt-1 flex flex-wrap items-center gap-3 text-xs text-slate-400">
              <span className="flex items-center gap-1 text-slate-300">
                <span className="w-2 h-2 rounded-full bg-emerald-400"></span> 
                Prueba Funcional Eliminatoria (Corte: <strong>70.00 / 100</strong>)
              </span>
              <span>•</span>
              <span className="text-slate-300">Formato: Juicio Situacional (1 Caso, 1 Enunciado, 3 Opciones)</span>
            </div>
          </div>

          <div className="bg-white/5 backdrop-blur-md p-4 rounded-xl border border-white/10 flex flex-col items-center justify-center min-w-[180px]">
            <span className="text-xs font-semibold text-slate-300">PROMEDIO OFICIAL</span>
            <span className={`text-4xl font-black mt-1 ${isApproved ? 'text-emerald-400' : 'text-amber-400'}`}>
              {(summary.average_score || 0).toFixed(2)}
            </span>
            <span className="text-[11px] text-slate-400 mt-1">escala 0 - 100 pts</span>
            <div className={`mt-2 px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
              isApproved ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
            }`}>
              {summary.total_exams > 0 ? (isApproved ? 'Zona Aprobatoria' : 'En Preparación') : 'Sin Intentos'}
            </div>
          </div>
        </div>
      </div>

      {/* Métricas Resumidas */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-4">
          <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold">
            <FileCheck2 className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-bold text-slate-800">{summary.total_exams || 0}</div>
            <div className="text-xs text-slate-500 font-medium">Simulacros Realizados</div>
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-4">
          <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
            <CheckCircle className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-bold text-emerald-600">{summary.total_correct_answers || 0}</div>
            <div className="text-xs text-slate-500 font-medium">Preguntas Acertadas (Buenas)</div>
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-4">
          <div className="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
            <XCircle className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-bold text-rose-600">{summary.total_incorrect_answers || 0}</div>
            <div className="text-xs text-slate-500 font-medium">Preguntas Falladas (Malas)</div>
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-4">
          <div className="w-12 h-12 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <HelpCircle className="w-6 h-6" />
          </div>
          <div>
            <div className="text-2xl font-bold text-purple-700">{summary.total_bank_questions || 0}</div>
            <div className="text-xs text-slate-500 font-medium">Preguntas en Banco PostgreSQL</div>
          </div>
        </div>
      </div>

      {/* Selector de Modos de Estudio */}
      <div>
        <h2 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
          <BookOpen className="w-5 h-5 text-blue-700" />
          <span>Modos de Estudio y Evaluación</span>
        </h2>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          {/* Tarjeta 1: Simulacro Oficial */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
            <div>
              <div className="w-12 h-12 rounded-xl bg-blue-700 text-white flex items-center justify-center mb-4 shadow">
                <FileCheck2 className="w-6 h-6" />
              </div>
              <span className="text-xs font-bold text-blue-700 uppercase tracking-wider">Modo Examen Real</span>
              <h3 className="text-lg font-bold text-slate-900 mt-1 mb-2">Simulacro Oficial DIAN 2676</h3>
              <p className="text-xs text-slate-600 leading-relaxed mb-4">
                Prueba cronometrada con formato de Juicio Situacional (Casos con 3 opciones A, B, C) y preguntas Likert de Competencias e Integridad. Simula las 5 horas y calcula puntaje eliminatorio con corte en 70.00 pts.
              </p>
            </div>
            <button
              onClick={() => onStartExam('simulacro_oficial')}
              className="w-full py-2.5 px-4 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition-colors flex items-center justify-center space-x-2 shadow-sm"
            >
              <span>Iniciar Simulacro Completo</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>

          {/* Tarjeta 2: Práctica por Eje Temático */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
            <div>
              <div className="w-12 h-12 rounded-xl bg-sky-600 text-white flex items-center justify-center mb-4 shadow">
                <BookOpen className="w-6 h-6" />
              </div>
              <span className="text-xs font-bold text-sky-600 uppercase tracking-wider">Entrenamiento Temático</span>
              <h3 className="text-lg font-bold text-slate-900 mt-1 mb-2">Práctica con Feedback Inmediato</h3>
              <p className="text-xs text-slate-600 leading-relaxed mb-4">
                Selecciona un eje temático específico (Procedimiento Tributario, Fiscalización Aduanera, Régimen Cambiario, CPACA, MIPG, etc.) con retroalimentación inmediata, justificación de cada opción y cita legal.
              </p>
            </div>
            <button
              onClick={onStartPractice}
              className="w-full py-2.5 px-4 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition-colors flex items-center justify-center space-x-2 shadow-sm"
            >
              <span>Seleccionar Eje Temático</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>

          {/* Tarjeta 3: Repaso de Fallos */}
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
            <div>
              <div className="w-12 h-12 rounded-xl bg-rose-600 text-white flex items-center justify-center mb-4 shadow">
                <RotateCcw className="w-6 h-6" />
              </div>
              <span className="text-xs font-bold text-rose-600 uppercase tracking-wider">Refuerzo Adaptativo</span>
              <h3 className="text-lg font-bold text-slate-900 mt-1 mb-2">Repaso de Preguntas Falladas</h3>
              <p className="text-xs text-slate-600 leading-relaxed mb-4">
                Entrena exclusivamente con las preguntas que has contestado mal en simulacros anteriores para fijar los conceptos legales clave y convertir debilidades en fortalezas.
              </p>
              <div className="text-xs font-semibold text-rose-700 bg-rose-50 p-2 rounded-lg mb-4 inline-block">
                Pendientes de repaso: {summary.failed_questions_pending || 0} preguntas
              </div>
            </div>
            <button
              onClick={onStartReview}
              disabled={!summary.failed_questions_pending}
              className={`w-full py-2.5 px-4 rounded-xl text-xs font-bold transition-colors flex items-center justify-center space-x-2 shadow-sm ${
                summary.failed_questions_pending
                  ? 'bg-rose-600 hover:bg-rose-700 text-white'
                  : 'bg-slate-200 text-slate-400 cursor-not-allowed'
              }`}
            >
              <span>{summary.failed_questions_pending ? 'Repasar Preguntas Falladas' : 'Sin Preguntas Falladas'}</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>

        </div>
      </div>

      {/* Rendimiento por Eje Temático */}
      <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div className="flex items-center justify-between mb-5">
          <div>
            <h2 className="text-base font-bold text-slate-900">Rendimiento por Eje Temático</h2>
            <p className="text-xs text-slate-500">Evaluación de dominio por cada componente del perfil Analista III</p>
          </div>
          <button
            onClick={() => setCurrentTab('stats')}
            className="text-xs font-bold text-blue-700 hover:underline flex items-center gap-1"
          >
            <span>Ver análisis completo</span>
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>

        <div className="space-y-4">
          {axisPerformance.map(axis => {
            const pct = axis.accuracy_pct || 0;
            const isGood = pct >= 70;
            return (
              <div key={axis.axis_id} className="space-y-1.5">
                <div className="flex justify-between items-center text-xs">
                  <div className="flex items-center space-x-2">
                    <span className="font-semibold text-slate-800">{axis.axis_name}</span>
                    <span className="text-[10px] text-slate-400">({axis.total_answered || 0} respondidas)</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className={`font-bold ${isGood ? 'text-emerald-600' : 'text-amber-600'}`}>
                      {pct.toFixed(1)}%
                    </span>
                    <span className={`px-2 py-0.5 rounded text-[10px] font-semibold ${
                      isGood ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                    }`}>
                      {axis.status}
                    </span>
                  </div>
                </div>
                <div className="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                  <div 
                    className={`h-full transition-all duration-500 ${isGood ? 'bg-emerald-500' : 'bg-amber-500'}`}
                    style={{ width: `${Math.min(100, pct)}%` }}
                  ></div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Historial Reciente de Simulacros */}
      {evolution.length > 0 && (
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-base font-bold text-slate-900 mb-4">Historial de Exámenes y Prácticas</h2>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 text-slate-600 border-b border-slate-200">
                <tr>
                  <th className="p-3">ID / Fecha</th>
                  <th className="p-3">Modo</th>
                  <th className="p-3">Preguntas</th>
                  <th className="p-3">Buenas / Malas</th>
                  <th className="p-3">Puntaje</th>
                  <th className="p-3">Resultado</th>
                  <th className="p-3 text-right">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {evolution.slice().reverse().map(ex => {
                  const pass = ex.is_passed;
                  return (
                    <tr key={ex.id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="p-3 font-mono font-medium text-slate-700">
                        #{ex.id} • {new Date(ex.completed_at).toLocaleDateString()} {new Date(ex.completed_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                      </td>
                      <td className="p-3 capitalize font-semibold text-slate-800">
                        {ex.mode.replace('_', ' ')}
                      </td>
                      <td className="p-3 text-slate-600">
                        {ex.total_questions} preguntas
                      </td>
                      <td className="p-3">
                        <span className="text-emerald-600 font-semibold">{ex.correct_answers} ✓</span>
                        <span className="text-slate-400 mx-1">/</span>
                        <span className="text-rose-600 font-semibold">{ex.incorrect_answers} ✗</span>
                      </td>
                      <td className="p-3 font-extrabold text-sm">
                        <span className={pass ? 'text-emerald-600' : 'text-rose-600'}>
                          {parseFloat(ex.score).toFixed(2)}
                        </span>
                        <span className="text-slate-400 text-[10px]"> / 100</span>
                      </td>
                      <td className="p-3">
                        <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                          pass ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                        }`}>
                          {pass ? 'APROBADO' : 'NO APROBADO'}
                        </span>
                      </td>
                      <td className="p-3 text-right">
                        <button
                          onClick={() => onViewHistoryItem(ex.id)}
                          className="px-3 py-1 bg-slate-100 hover:bg-blue-50 text-blue-700 rounded-md font-semibold text-xs transition-colors"
                        >
                          Ver Revisión
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

    </div>
  );
}
