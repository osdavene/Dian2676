import React from 'react';
import { 
  BarChart3, 
  TrendingUp, 
  CheckCircle2, 
  XCircle, 
  Clock, 
  AlertTriangle, 
  Scale, 
  Award,
  BookOpen,
  ArrowRight
} from 'lucide-react';

export default function StatsAnalytics({ stats, selectedJob, onStartPracticeAxis, onReviewFailed }) {
  const summary = stats?.summary || {};
  const axisPerformance = stats?.axis_performance || [];
  const evolution = stats?.evolution || [];

  const isApproved = (summary.average_score || 0) >= 70.00;
  const totalAnswered = (summary.total_correct_answers || 0) + (summary.total_incorrect_answers || 0);
  const accuracyRate = totalAnswered > 0 
    ? ((summary.total_correct_answers / totalAnswered) * 100).toFixed(1) 
    : '0.0';

  // Identificar ejes débiles (< 70% o con más fallas)
  const weakAxes = axisPerformance.filter(a => (a.accuracy_pct || 0) < 70 && a.total_answered > 0);

  const formatHours = (seconds) => {
    const hours = (seconds / 3600).toFixed(1);
    return `${hours} hrs`;
  };

  return (
    <div className="max-w-6xl mx-auto space-y-8 pb-16">
      
      {/* Encabezado */}
      <div>
        <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
          <BarChart3 className="w-7 h-7 text-amber-500" />
          <span>Analítica de Rendimiento & Estadísticas DIAN 2676</span>
        </h1>
        <p className="text-xs sm:text-sm text-slate-500">
          Métricas de preparación para el cargo: <strong>{selectedJob?.name} ({selectedJob?.code})</strong>
        </p>
      </div>

      {/* Tarjetas Principales */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
          <div className="text-xs text-slate-500 font-semibold">TASA DE APROBACIÓN</div>
          <div className={`text-3xl font-black ${isApproved ? 'text-emerald-600' : 'text-amber-500'}`}>
            {summary.pass_rate_pct || 0}%
          </div>
          <div className="text-[11px] text-slate-400">
            {summary.total_passed || 0} de {summary.total_exams || 0} simulacros superados
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
          <div className="text-xs text-slate-500 font-semibold">PROMEDIO OFICIAL</div>
          <div className={`text-3xl font-black ${isApproved ? 'text-emerald-600' : 'text-slate-800'}`}>
            {(summary.average_score || 0).toFixed(2)}
          </div>
          <div className="text-[11px] text-slate-400">
            Corte eliminatorio: 70.00 pts
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
          <div className="text-xs text-slate-500 font-semibold">PRECISIÓN GLOBAL</div>
          <div className="text-3xl font-black text-blue-700">
            {accuracyRate}%
          </div>
          <div className="text-[11px] text-slate-400">
            {summary.total_correct_answers || 0} de {totalAnswered} aciertos
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
          <div className="text-xs text-slate-500 font-semibold">TIEMPO DE ESTUDIO</div>
          <div className="text-3xl font-black text-purple-700">
            {formatHours(summary.total_study_time_seconds || 0)}
          </div>
          <div className="text-[11px] text-slate-400">
            Tiempo efectivo acumulado
          </div>
        </div>
      </div>

      {/* Diagnóstico y Recomendaciones Personalizadas de Estudio */}
      <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        <div className="flex items-center space-x-2 text-slate-900 font-bold text-base">
          <TrendingUp className="w-5 h-5 text-blue-700" />
          <h2>Diagnóstico Pedagógico y Plan de Refuerzo</h2>
        </div>

        {weakAxes.length === 0 ? (
          <div className="p-4 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 text-xs">
            🎉 <strong>¡Excelente nivel de preparación!</strong> No tienes ejes temáticos por debajo del 70% de efectividad. Te recomendamos continuar realizando simulacros completos para afianzar la gestión del tiempo.
          </div>
        ) : (
          <div className="space-y-3">
            <p className="text-xs text-slate-600">
              Con base en tus respuestas registradas en PostgreSQL, se detectaron los siguientes temas que requieren refuerzo prioritario:
            </p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              {weakAxes.map(wa => (
                <div key={wa.axis_id} className="p-4 rounded-xl border border-amber-200 bg-amber-50/50 flex flex-col justify-between space-y-3">
                  <div>
                    <div className="flex items-center justify-between text-xs mb-1">
                      <span className="font-bold text-amber-900">{wa.axis_name}</span>
                      <span className="font-extrabold text-amber-700">{wa.accuracy_pct}% acierto</span>
                    </div>
                    <p className="text-[11px] text-slate-600 leading-relaxed">
                      {wa.description}
                    </p>
                  </div>

                  <button
                    onClick={() => onStartPracticeAxis(wa.axis_id)}
                    className="self-start px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition-colors flex items-center space-x-1"
                  >
                    <span>Entrenar este Eje</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Desglose Completo por Eje Temático */}
      <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        <h2 className="text-base font-bold text-slate-900">
          Dominio Consolidado por Eje Temático en PostgreSQL
        </h2>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 text-slate-600 border-b border-slate-200">
              <tr>
                <th className="p-3">Eje Temático</th>
                <th className="p-3">Categoría</th>
                <th className="p-3">Respondidas</th>
                <th className="p-3">Buenas / Malas</th>
                <th className="p-3">% Dominio</th>
                <th className="p-3">Diagnóstico</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {axisPerformance.map(axis => {
                const pct = axis.accuracy_pct || 0;
                const isGood = pct >= 70;
                const isEmpty = (axis.total_answered || 0) === 0;

                return (
                  <tr key={axis.axis_id} className="hover:bg-slate-50/80 transition-colors">
                    <td className="p-3 font-semibold text-slate-800">
                      {axis.axis_name}
                    </td>
                    <td className="p-3 uppercase text-[10px] font-bold text-slate-500">
                      {axis.axis_category.replace('_', ' ')}
                    </td>
                    <td className="p-3 text-slate-600">
                      {axis.total_answered || 0}
                    </td>
                    <td className="p-3">
                      <span className="text-emerald-600 font-semibold">{axis.correct_count || 0} ✓</span>
                      <span className="text-slate-300 mx-1">/</span>
                      <span className="text-rose-600 font-semibold">{axis.incorrect_count || 0} ✗</span>
                    </td>
                    <td className="p-3 font-extrabold text-sm">
                      <span className={isGood ? 'text-emerald-600' : (isEmpty ? 'text-slate-400' : 'text-amber-600')}>
                        {isEmpty ? '0.0%' : `${pct.toFixed(1)}%`}
                      </span>
                    </td>
                    <td className="p-3">
                      <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                        isEmpty ? 'bg-slate-100 text-slate-500' : (isGood ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800')
                      }`}>
                        {isEmpty ? 'Sin Evaluar' : axis.status}
                      </span>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

    </div>
  );
}
