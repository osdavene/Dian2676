import React, { useState, useEffect } from 'react';
import confetti from 'canvas-confetti';
import { 
  Award, 
  CheckCircle2, 
  XCircle, 
  Clock, 
  RotateCcw, 
  ArrowLeft, 
  Scale, 
  Filter, 
  Check, 
  AlertCircle,
  HelpCircle,
  FileText
} from 'lucide-react';
import { getSessionResults } from '../services/api';

export default function ExamResults({ sessionId, onRestartExam, onBackToDashboard }) {
  const [resultsData, setResultsData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [filterMode, setFilterMode] = useState('all'); // 'all' | 'incorrect' | 'correct'

  useEffect(() => {
    async function loadResults() {
      try {
        const data = await getSessionResults(sessionId);
        setResultsData(data);
        if (data?.session?.is_passed) {
          confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
          });
        }
      } catch (err) {
        console.error('Error cargando resultados:', err);
      } finally {
        setLoading(false);
      }
    }
    if (sessionId) {
      loadResults();
    }
  }, [sessionId]);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        <p className="text-sm font-semibold text-slate-600">Calculando resultados oficiales...</p>
      </div>
    );
  }

  if (!resultsData) {
    return (
      <div className="text-center py-12">
        <p className="text-slate-600">No se pudieron cargar los resultados de la sesión.</p>
        <button onClick={onBackToDashboard} className="mt-4 px-4 py-2 bg-blue-700 text-white rounded-lg text-xs font-bold">
          Volver al Inicio
        </button>
      </div>
    );
  }

  const { session, axis_breakdown = [], questions_review = [] } = resultsData;
  const isPassed = session.is_passed;
  const score = parseFloat(session.score || 0).toFixed(2);

  const filteredQuestions = questions_review.filter(q => {
    if (filterMode === 'correct') return q.is_correct === true;
    if (filterMode === 'incorrect') return q.is_correct === false || !q.selected_option;
    return true;
  });

  const formatSeconds = (sec) => {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}m ${s}s`;
  };

  return (
    <div className="max-w-5xl mx-auto space-y-8 pb-16">
      
      {/* Botón Volver */}
      <div className="flex items-center justify-between">
        <button
          onClick={onBackToDashboard}
          className="flex items-center space-x-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Volver al Panel Principal</span>
        </button>

        <div className="flex space-x-2">
          <button
            onClick={() => onRestartExam('simulacro_oficial')}
            className="flex items-center space-x-1 px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition-colors shadow-sm"
          >
            <RotateCcw className="w-4 h-4" />
            <span>Repetir Simulacro</span>
          </button>
        </div>
      </div>

      {/* Tarjeta Gigante de Calificación Oficial */}
      <div className={`rounded-3xl p-6 sm:p-10 border shadow-lg relative overflow-hidden text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6 ${
        isPassed 
          ? 'bg-gradient-to-br from-emerald-900 via-teal-950 to-slate-900 border-emerald-800 text-white' 
          : 'bg-gradient-to-br from-rose-950 via-slate-900 to-slate-950 border-rose-900 text-white'
      }`}>
        <div className="space-y-3 max-w-xl">
          <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border backdrop-blur-md">
            {isPassed ? (
              <span className="bg-emerald-500/20 text-emerald-300 border-emerald-500/40">
                ✓ PUNTUALIDAD APROBATORIA ALCANZADA
              </span>
            ) : (
              <span className="bg-rose-500/20 text-rose-300 border-rose-500/40">
                ✗ NO ALCANZA PUNTAJE MÍNIMO ELIMINATORIO
              </span>
            )}
          </div>

          <h1 className="text-2xl sm:text-4xl font-black tracking-tight">
            {isPassed ? '¡PRUEBA APROBADA!' : 'PRUEBA NO APROBADA'}
          </h1>

          <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
            {isPassed 
              ? 'Has superado el puntaje mínimo eliminatorio de 70.00 puntos requerido por la CNSC y la DIAN para continuar en la lista de elegibles del proceso de selección 2676.' 
              : 'El puntaje obtenido es inferior al mínimo aprobatorio de 70.00 establecido en el Acuerdo 21 de 2025. En el concurso real, las pruebas funcionales tienen carácter eliminatorio. Te sugerimos revisar las justificaciones y volver a intentarlo.'}
          </p>
        </div>

        {/* Puntaje en escala oficial */}
        <div className="bg-white/10 backdrop-blur-md p-6 rounded-2xl border border-white/20 text-center min-w-[200px]">
          <span className="text-xs font-semibold text-slate-300 uppercase tracking-wider block">
            PUNTAJE OFICIAL DIAN
          </span>
          <span className={`text-5xl font-black block my-1 ${isPassed ? 'text-emerald-400' : 'text-rose-400'}`}>
            {score}
          </span>
          <span className="text-xs text-slate-300 font-medium">de 100.00 Puntos</span>
          <div className="text-[10px] text-slate-400 mt-2">
            Regla: Corte ≥ 70.00 (Truncado 2 decimales)
          </div>
        </div>
      </div>

      {/* Métricas de Respuestas */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3">
          <div className="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
            <CheckCircle2 className="w-5 h-5" />
          </div>
          <div>
            <div className="text-xl font-bold text-emerald-600">{session.correct_answers}</div>
            <div className="text-xs text-slate-500 font-medium">Aciertos (Buenas)</div>
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3">
          <div className="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
            <XCircle className="w-5 h-5" />
          </div>
          <div>
            <div className="text-xl font-bold text-rose-600">{session.incorrect_answers}</div>
            <div className="text-xs text-slate-500 font-medium">Errores (Malas)</div>
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3">
          <div className="w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-bold">
            <HelpCircle className="w-5 h-5" />
          </div>
          <div>
            <div className="text-xl font-bold text-slate-700">{session.omitted_answers || 0}</div>
            <div className="text-xs text-slate-500 font-medium">Omitidas (En blanco)</div>
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center space-x-3">
          <div className="w-10 h-10 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold">
            <Clock className="w-5 h-5" />
          </div>
          <div>
            <div className="text-xl font-bold text-blue-700">{formatSeconds(session.time_spent_seconds)}</div>
            <div className="text-xs text-slate-500 font-medium">Tiempo Empleado</div>
          </div>
        </div>
      </div>

      {/* Desglose de Rendimiento por Eje Temático */}
      {axis_breakdown.length > 0 && (
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
          <h2 className="text-base font-bold text-slate-900">
            Rendimiento por Eje Temático en este Simulacro
          </h2>
          <div className="space-y-3">
            {axis_breakdown.map((ab, i) => {
              const pct = ab.accuracy_pct || 0;
              const isGood = pct >= 70;
              return (
                <div key={i} className="space-y-1">
                  <div className="flex justify-between items-center text-xs">
                    <span className="font-semibold text-slate-800">{ab.name}</span>
                    <span className={`font-bold ${isGood ? 'text-emerald-600' : 'text-rose-600'}`}>
                      {ab.correct} / {ab.total} correctas ({pct.toFixed(1)}%)
                    </span>
                  </div>
                  <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div 
                      className={`h-full ${isGood ? 'bg-emerald-500' : 'bg-rose-500'}`}
                      style={{ width: `${Math.min(100, pct)}%` }}
                    ></div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Revisión Completa Pregunta a Pregunta */}
      <div className="space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
          <div>
            <h2 className="text-lg font-bold text-slate-900">
              Revisión Detallada de Preguntas y Justificaciones
            </h2>
            <p className="text-xs text-slate-500">
              Consulta por qué cada opción es correcta o incorrecta con su fundamento jurídico.
            </p>
          </div>

          {/* Filtros */}
          <div className="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl">
            <button
              onClick={() => setFilterMode('all')}
              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
                filterMode === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Todas ({questions_review.length})
            </button>
            <button
              onClick={() => setFilterMode('incorrect')}
              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
                filterMode === 'incorrect' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-600 hover:text-rose-700'
              }`}
            >
              Solo Errores ({session.incorrect_answers + (session.omitted_answers || 0)})
            </button>
            <button
              onClick={() => setFilterMode('correct')}
              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
                filterMode === 'correct' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-emerald-700'
              }`}
            >
              Solo Correctas ({session.correct_answers})
            </button>
          </div>
        </div>

        {/* Lista de Preguntas */}
        <div className="space-y-6">
          {filteredQuestions.map((q, idx) => {
            const isCorrect = q.is_correct === true;
            const isOmitted = !q.selected_option;

            return (
              <div 
                key={q.id}
                className={`bg-white rounded-2xl border p-6 shadow-sm transition-all ${
                  isCorrect 
                    ? 'border-emerald-200/80 hover:border-emerald-300' 
                    : 'border-rose-200/80 hover:border-rose-300'
                }`}
              >
                
                {/* Encabezado del Ítem */}
                <div className="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                  <div className="flex items-center space-x-2">
                    <span className="font-bold text-xs text-slate-500">Pregunta #{idx + 1}</span>
                    <span className="text-slate-300">•</span>
                    <span className="text-xs font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">
                      {q.axis_name}
                    </span>
                  </div>

                  <div>
                    {isCorrect ? (
                      <span className="inline-flex items-center space-x-1 px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">
                        <CheckCircle2 className="w-3.5 h-3.5" />
                        <span>Correcta</span>
                      </span>
                    ) : isOmitted ? (
                      <span className="inline-flex items-center space-x-1 px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-bold">
                        <HelpCircle className="w-3.5 h-3.5" />
                        <span>Omitida</span>
                      </span>
                    ) : (
                      <span className="inline-flex items-center space-x-1 px-2.5 py-1 bg-rose-100 text-rose-800 rounded-full text-xs font-bold">
                        <XCircle className="w-3.5 h-3.5" />
                        <span>Incorrecta</span>
                      </span>
                    )}
                  </div>
                </div>

                {/* Caso Situacional */}
                {q.case_text && (
                  <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-4 text-xs sm:text-sm text-slate-800 font-serif leading-relaxed">
                    <div className="flex items-center space-x-1.5 text-xs font-bold text-slate-600 mb-1">
                      <Scale className="w-3.5 h-3.5 text-blue-700" />
                      <span>CASO:</span>
                    </div>
                    {q.case_text}
                  </div>
                )}

                {/* Enunciado */}
                <h4 className="text-sm sm:text-base font-bold text-slate-900 mb-4">
                  {q.stem}
                </h4>

                {/* Opciones */}
                <div className="space-y-2 mb-4">
                  {q.options?.map((opt) => {
                    const wasChosen = q.selected_option === opt.key;
                    const isRightAnswer = q.correct_option === opt.key;

                    let optStyle = 'border-slate-200 bg-white text-slate-700';
                    if (isRightAnswer) {
                      optStyle = 'border-emerald-500 bg-emerald-50/60 text-emerald-950 font-semibold ring-1 ring-emerald-500';
                    } else if (wasChosen && !isRightAnswer) {
                      optStyle = 'border-rose-400 bg-rose-50/60 text-rose-950 ring-1 ring-rose-400';
                    }

                    return (
                      <div 
                        key={opt.key}
                        className={`p-3 rounded-xl border flex items-start space-x-3 text-xs sm:text-sm ${optStyle}`}
                      >
                        <div className={`optical-bubble shrink-0 ${isRightAnswer ? 'selected' : ''}`}>
                          {opt.key}
                        </div>
                        <div className="flex-1">
                          <div>{opt.text}</div>
                          {wasChosen && (
                            <span className="inline-block mt-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-700">
                              Tu elección
                            </span>
                          )}
                          {isRightAnswer && (
                            <span className="inline-block mt-1 ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-800">
                              Respuesta correcta
                            </span>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>

                {/* Justificación y Sustento Jurídico */}
                <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3 text-xs text-slate-700">
                  {q.legal_basis && (
                    <div className="font-semibold text-slate-800 bg-white p-2.5 rounded-lg border border-slate-200">
                      ⚖️ <strong>Fundamento Jurídico:</strong> {q.legal_basis}
                    </div>
                  )}

                  <div className="space-y-2 pt-1">
                    <strong className="block text-slate-800 uppercase tracking-wider text-[11px]">
                      Justificación de las Alternativas:
                    </strong>
                    {q.options?.map((opt) => (
                      <div key={opt.key} className="text-xs leading-relaxed">
                        <span className={`font-bold ${opt.key === q.correct_option ? 'text-emerald-700' : 'text-slate-600'}`}>
                          Opción {opt.key}:
                        </span>{' '}
                        {opt.justification || (opt.key === q.correct_option ? 'Es la alternativa jurídicamente procedente.' : 'No corresponde a los términos normativos vigentes.')}
                      </div>
                    ))}
                  </div>
                </div>

              </div>
            );
          })}
        </div>
      </div>

    </div>
  );
}
