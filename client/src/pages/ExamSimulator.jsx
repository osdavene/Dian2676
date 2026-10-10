import React, { useState, useEffect } from 'react';
import { 
  Clock, 
  Flag, 
  ChevronLeft, 
  ChevronRight, 
  CheckCircle2, 
  XCircle, 
  AlertCircle, 
  HelpCircle, 
  Send,
  Eye,
  FileSpreadsheet,
  Check,
  Scale
} from 'lucide-react';
import { submitAnswer, completeSession } from '../services/api';

export default function ExamSimulator({ 
  session, 
  questions = [], 
  onFinishExam, 
  isPractice = false 
}) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [userAnswers, setUserAnswers] = useState({}); // { [qId]: 'A' | 'B' | 'C' }
  const [flagged, setFlagged] = useState({}); // { [qId]: true/false }
  const [timeLeft, setTimeLeft] = useState(session?.time_limit_seconds || 18000);
  const [viewMode, setViewMode] = useState('cuadernillo'); // 'cuadernillo' | 'hoja_optica'
  const [showSubmitModal, setShowSubmitModal] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Feedback para modo práctica
  const [practiceFeedback, setPracticeFeedback] = useState({}); // { [qId]: { is_correct, options, legal_basis, explanation_summary } }
  const [showExplanation, setShowExplanation] = useState(false);

  // Temporizador
  useEffect(() => {
    if (timeLeft <= 0) {
      handleFinalSubmit();
      return;
    }
    const timer = setInterval(() => {
      setTimeLeft(prev => prev - 1);
    }, 1000);
    return () => clearInterval(timer);
  }, [timeLeft]);

  const currentQ = questions[currentIndex] || {};
  const currentQId = currentQ.id;

  const formatTime = (seconds) => {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  };

  const handleSelectOption = async (optionKey) => {
    setUserAnswers(prev => ({ ...prev, [currentQId]: optionKey }));

    try {
      const feedback = await submitAnswer(session.id, {
        question_id: currentQId,
        selected_option: optionKey,
        time_spent_seconds: 5
      });

      if (isPractice) {
        setPracticeFeedback(prev => ({
          ...prev,
          [currentQId]: feedback
        }));
        setShowExplanation(true);
      }
    } catch (err) {
      console.error('Error registrando respuesta:', err);
    }
  };

  const toggleFlag = (qId) => {
    setFlagged(prev => ({ ...prev, [qId]: !prev[qId] }));
  };

  const handleFinalSubmit = async () => {
    setIsSubmitting(true);
    try {
      const timeSpent = (session.time_limit_seconds || 18000) - timeLeft;
      const res = await completeSession(session.id, {
        time_spent_seconds: Math.max(0, timeSpent)
      });
      onFinishExam(session.id);
    } catch (err) {
      console.error('Error finalizando sesión:', err);
      alert('Error al calificar el examen');
    } finally {
      setIsSubmitting(false);
      setShowSubmitModal(false);
    }
  };

  const totalAnswered = Object.keys(userAnswers).length;
  const totalQuestions = questions.length;
  const totalOmitted = totalQuestions - totalAnswered;

  return (
    <div className="max-w-7xl mx-auto space-y-6 pb-16">
      
      {/* Barra de Control Superior / Encabezado de Examen */}
      <div className="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-lg border border-slate-800 flex flex-wrap items-center justify-between gap-4 sticky top-20 z-40">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-white shadow">
            {currentIndex + 1}
          </div>
          <div>
            <div className="text-xs text-slate-400 font-semibold uppercase tracking-wider">
              {isPractice ? 'MODO PRÁCTICA TEMÁTICA' : 'SIMULACRO OFICIAL DIAN 2676'}
            </div>
            <div className="text-sm sm:text-base font-bold text-white">
              Pregunta {currentIndex + 1} de {totalQuestions}
            </div>
          </div>
        </div>

        {/* Temporizador */}
        <div className="flex items-center space-x-2 bg-slate-800/90 px-4 py-2 rounded-xl border border-slate-700">
          <Clock className={`w-5 h-5 ${timeLeft < 600 ? 'text-rose-400 animate-pulse' : 'text-amber-400'}`} />
          <div>
            <span className="text-[10px] text-slate-400 block -mb-1">TIEMPO RESTANTE</span>
            <span className={`font-mono text-base sm:text-lg font-bold tracking-widest ${timeLeft < 600 ? 'text-rose-400' : 'text-amber-300'}`}>
              {formatTime(timeLeft)}
            </span>
          </div>
        </div>

        {/* Acciones y Conmutador de Hoja Óptica */}
        <div className="flex items-center space-x-2">
          <button
            onClick={() => setViewMode(viewMode === 'cuadernillo' ? 'hoja_optica' : 'cuadernillo')}
            className="flex items-center space-x-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-colors border border-slate-700"
            title="Conmutar vista de hoja óptica"
          >
            <FileSpreadsheet className="w-4 h-4 text-emerald-400" />
            <span className="hidden sm:inline">
              {viewMode === 'cuadernillo' ? 'Ver Hoja de Respuestas' : 'Ver Cuadernillo'}
            </span>
          </button>

          <button
            onClick={() => setShowSubmitModal(true)}
            className="flex items-center space-x-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow"
          >
            <Send className="w-4 h-4" />
            <span>Finalizar Examen</span>
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        {/* Columna Principal: Cuadernillo de Pregunta u Hoja Óptica */}
        <div className="lg:col-span-3 space-y-6">
          
          {viewMode === 'cuadernillo' ? (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
              
              {/* Header de la Pregunta */}
              <div className="bg-slate-50 border-b border-slate-200 p-4 sm:p-5 flex items-center justify-between">
                <div className="space-y-1">
                  <div className="flex items-center space-x-2">
                    <span className="bg-blue-100 text-blue-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full uppercase">
                      {currentQ.axis_name || 'Competencia Funcional'}
                    </span>
                    <span className="text-slate-400 text-xs">•</span>
                    <span className="text-xs text-slate-500 font-medium capitalize">
                      Dificultad: <strong>{currentQ.difficulty}</strong>
                    </span>
                  </div>
                  <div className="text-xs text-slate-500">
                    {currentQ.question_type === 'situacional' ? 'Prueba de Juicio Situacional (PJS)' : 'Competencia Comportamental (Likert)'}
                  </div>
                </div>

                <button
                  onClick={() => toggleFlag(currentQId)}
                  className={`flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors border ${
                    flagged[currentQId]
                      ? 'bg-amber-100 text-amber-800 border-amber-300'
                      : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'
                  }`}
                >
                  <Flag className={`w-3.5 h-3.5 ${flagged[currentQId] ? 'fill-amber-600 text-amber-600' : ''}`} />
                  <span>{flagged[currentQId] ? 'Marcada para revisar' : 'Marcar para revisar'}</span>
                </button>
              </div>

              <div className="p-6 sm:p-8 space-y-6">
                
                {/* 1. CASO SITUACIONAL */}
                {currentQ.case_text && (
                  <div className="bg-slate-50/80 rounded-xl p-5 border border-slate-200 space-y-2">
                    <div className="flex items-center space-x-2 text-xs font-extrabold text-blue-900 uppercase tracking-wider">
                      <Scale className="w-4 h-4 text-blue-700" />
                      <span>CASO HIPOTÉTICO (Contexto Laboral DIAN)</span>
                    </div>
                    <p className="text-sm sm:text-base text-slate-800 leading-relaxed font-serif">
                      {currentQ.case_text}
                    </p>
                  </div>
                )}

                {/* 2. ENUNCIADO */}
                <div className="space-y-2">
                  <div className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    ENUNCIADO
                  </div>
                  <h3 className="text-base sm:text-lg font-bold text-slate-900 leading-snug">
                    {currentQ.stem}
                  </h3>
                </div>

                {/* 3. OPCIONES DE RESPUESTA (A, B, C) */}
                <div className="space-y-3 pt-2">
                  <div className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    OPCIONES DE ACCIÓN
                  </div>

                  {currentQ.options?.map((opt) => {
                    const isSelected = userAnswers[currentQId] === opt.key;
                    const feedback = practiceFeedback[currentQId];
                    const isPracticeChecked = isPractice && feedback;
                    const isCorrectAnswer = isPracticeChecked && feedback.correct_option === opt.key;
                    const isWrongSelected = isPracticeChecked && isSelected && !isCorrectAnswer;

                    let optionBorder = 'border-slate-200 hover:border-blue-400 bg-white';
                    if (isSelected) {
                      optionBorder = 'border-blue-700 bg-blue-50/40 ring-1 ring-blue-700';
                    }
                    if (isPracticeChecked) {
                      if (isCorrectAnswer) {
                        optionBorder = 'border-emerald-600 bg-emerald-50/50 ring-1 ring-emerald-600';
                      } else if (isWrongSelected) {
                        optionBorder = 'border-rose-600 bg-rose-50/50 ring-1 ring-rose-600';
                      }
                    }

                    return (
                      <div
                        key={opt.key}
                        onClick={() => handleSelectOption(opt.key)}
                        className={`p-4 rounded-xl border transition-all cursor-pointer flex items-start space-x-3 ${optionBorder}`}
                      >
                        <div className={`optical-bubble shrink-0 mt-0.5 ${isSelected ? 'selected' : ''}`}>
                          {opt.key}
                        </div>
                        <div className="flex-1 text-xs sm:text-sm text-slate-800 leading-relaxed">
                          {opt.text}
                        </div>
                      </div>
                    );
                  })}
                </div>

                {/* Retroalimentación en Modo Práctica */}
                {isPractice && practiceFeedback[currentQId] && (
                  <div className={`mt-6 p-5 rounded-xl border ${
                    practiceFeedback[currentQId].is_correct 
                      ? 'bg-emerald-50 border-emerald-200 text-emerald-950' 
                      : 'bg-rose-50 border-rose-200 text-rose-950'
                  }`}>
                    <div className="flex items-center space-x-2 font-bold text-sm mb-2">
                      {practiceFeedback[currentQId].is_correct ? (
                        <>
                          <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                          <span className="text-emerald-800">¡Respuesta Correcta! Clave: {practiceFeedback[currentQId].correct_option}</span>
                        </>
                      ) : (
                        <>
                          <XCircle className="w-5 h-5 text-rose-600" />
                          <span className="text-rose-800">Respuesta Incorrecta. La opción correcta es la {practiceFeedback[currentQId].correct_option}</span>
                        </>
                      )}
                    </div>

                    <div className="space-y-3 text-xs sm:text-sm">
                      {practiceFeedback[currentQId].legal_basis && (
                        <div className="font-semibold text-slate-700 bg-white/70 p-2.5 rounded-lg border border-slate-200">
                          ⚖️ <strong>Fundamento Jurídico:</strong> {practiceFeedback[currentQId].legal_basis}
                        </div>
                      )}

                      <div className="bg-white/70 p-3 rounded-lg border border-slate-200 space-y-2">
                        <strong className="block text-slate-800 text-xs uppercase tracking-wider">Justificación de las Opciones:</strong>
                        {practiceFeedback[currentQId].options?.map(opt => (
                          <div key={opt.key} className="text-xs text-slate-700">
                            <span className={`font-bold ${opt.is_correct ? 'text-emerald-700' : 'text-slate-600'}`}>
                              Opción {opt.key}:
                            </span>{' '}
                            {opt.justification || (opt.is_correct ? 'Respuesta jurídicamente válida.' : 'No corresponde al procedimiento normativo aplicable.')}
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                )}

              </div>

              {/* Botonera de Navegación */}
              <div className="bg-slate-50 border-t border-slate-200 p-4 sm:p-5 flex items-center justify-between">
                <button
                  onClick={() => setCurrentIndex(Math.max(0, currentIndex - 1))}
                  disabled={currentIndex === 0}
                  className={`flex items-center space-x-1 px-4 py-2 rounded-xl text-xs font-semibold transition-colors ${
                    currentIndex === 0
                      ? 'text-slate-300 bg-slate-100 cursor-not-allowed'
                      : 'text-slate-700 bg-white hover:bg-slate-200 border border-slate-200 shadow-sm'
                  }`}
                >
                  <ChevronLeft className="w-4 h-4" />
                  <span>Anterior</span>
                </button>

                <div className="text-xs text-slate-500 font-medium">
                  {currentIndex + 1} de {totalQuestions}
                </div>

                <button
                  onClick={() => setCurrentIndex(Math.min(totalQuestions - 1, currentIndex + 1))}
                  disabled={currentIndex === totalQuestions - 1}
                  className={`flex items-center space-x-1 px-4 py-2 rounded-xl text-xs font-semibold transition-colors ${
                    currentIndex === totalQuestions - 1
                      ? 'text-slate-300 bg-slate-100 cursor-not-allowed'
                      : 'text-white bg-blue-700 hover:bg-blue-800 shadow-sm'
                  }`}
                >
                  <span>Siguiente</span>
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>

            </div>
          ) : (
            /* Vista Hoja Óptica Oficial */
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
              <div className="border-b border-slate-200 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <h3 className="text-lg font-black text-slate-900 tracking-wider">HOJA DE RESPUESTAS OFICIAL</h3>
                  <p className="text-xs text-slate-500">
                    Proceso de Selección DIAN 2676 • Llene completamente el círculo con lápiz de mina negra N.º 2
                  </p>
                </div>
                <button
                  onClick={() => setViewMode('cuadernillo')}
                  className="px-3 py-1.5 bg-blue-50 text-blue-700 font-semibold text-xs rounded-lg hover:bg-blue-100 transition-colors"
                >
                  Volver a Cuadernillo
                </button>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                {questions.map((q, idx) => {
                  const selected = userAnswers[q.id];
                  const isFlag = flagged[q.id];
                  return (
                    <div 
                      key={q.id}
                      className={`p-3 rounded-xl border flex items-center justify-between transition-colors ${
                        idx === currentIndex ? 'border-blue-700 bg-blue-50/20 shadow-sm' : 'border-slate-200'
                      }`}
                    >
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => {
                            setCurrentIndex(idx);
                            setViewMode('cuadernillo');
                          }}
                          className="font-bold text-xs text-slate-700 hover:text-blue-700"
                        >
                          Ítem {idx + 1}
                        </button>
                        {isFlag && <Flag className="w-3 h-3 text-amber-500 fill-amber-500" />}
                      </div>

                      <div className="flex items-center space-x-2">
                        {['A', 'B', 'C'].map((optKey) => (
                          <div
                            key={optKey}
                            onClick={() => {
                              setCurrentIndex(idx);
                              handleSelectOption(optKey);
                            }}
                            className={`optical-bubble ${selected === optKey ? 'selected' : ''}`}
                          >
                            {optKey}
                          </div>
                        ))}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

        </div>

        {/* Columna Lateral: Navegador del Cuadernillo y Resumen */}
        <div className="space-y-6">
          
          <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
            <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">
              Navegador de Preguntas
            </h4>

            {/* Cuadrícula de Preguntas */}
            <div className="grid grid-cols-5 gap-2 max-h-72 overflow-y-auto p-1">
              {questions.map((q, idx) => {
                const isAnswered = !!userAnswers[q.id];
                const isFlag = !!flagged[q.id];
                const isCurrent = idx === currentIndex;

                let bgClass = 'bg-slate-100 text-slate-700 border-slate-200';
                if (isCurrent) {
                  bgClass = 'ring-2 ring-blue-700 font-extrabold';
                }
                if (isAnswered) {
                  bgClass += ' bg-blue-700 text-white border-blue-700';
                }
                if (isFlag) {
                  bgClass += ' bg-amber-400 text-amber-950 border-amber-500';
                }

                return (
                  <button
                    key={q.id}
                    onClick={() => {
                      setCurrentIndex(idx);
                      setViewMode('cuadernillo');
                    }}
                    className={`h-9 rounded-lg font-bold text-xs flex items-center justify-center border transition-all ${bgClass}`}
                  >
                    {idx + 1}
                  </button>
                );
              })}
            </div>

            {/* Leyenda de Estados */}
            <div className="border-t border-slate-100 pt-3 space-y-1.5 text-[11px] text-slate-500">
              <div className="flex items-center space-x-2">
                <span className="w-3 h-3 rounded bg-blue-700"></span>
                <span>Respondida ({totalAnswered})</span>
              </div>
              <div className="flex items-center space-x-2">
                <span className="w-3 h-3 rounded bg-slate-200"></span>
                <span>Sin responder ({totalOmitted})</span>
              </div>
              <div className="flex items-center space-x-2">
                <span className="w-3 h-3 rounded bg-amber-400"></span>
                <span>Marcada para revisión ({Object.values(flagged).filter(Boolean).length})</span>
              </div>
            </div>

            <button
              onClick={() => setShowSubmitModal(true)}
              className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm flex items-center justify-center space-x-1"
            >
              <Send className="w-3.5 h-3.5" />
              <span>Entregar y Calificar</span>
            </button>
          </div>

        </div>

      </div>

      {/* Modal de Confirmación de Entrega */}
      {showSubmitModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 p-6 space-y-5">
            <div className="flex items-center space-x-3 text-amber-600">
              <AlertCircle className="w-8 h-8" />
              <h3 className="text-lg font-bold text-slate-900">¿Deseas finalizar la prueba?</h3>
            </div>

            <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs text-slate-700">
              <div className="flex justify-between font-semibold">
                <span>Total de preguntas:</span>
                <span>{totalQuestions}</span>
              </div>
              <div className="flex justify-between text-blue-700 font-semibold">
                <span>Preguntas contestadas:</span>
                <span>{totalAnswered}</span>
              </div>
              <div className="flex justify-between text-rose-700 font-semibold">
                <span>Preguntas omitidas (en blanco):</span>
                <span>{totalOmitted}</span>
              </div>
              {totalOmitted > 0 && (
                <p className="text-[11px] text-amber-800 bg-amber-50 p-2 rounded border border-amber-200 mt-2">
                  ⚠️ Atención: Según las reglas de la CNSC y la DIAN, las preguntas omitidas no otorgan puntaje y reducen la calificación general.
                </p>
              )}
            </div>

            <div className="flex justify-end space-x-3">
              <button
                type="button"
                onClick={() => setShowSubmitModal(false)}
                className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
              >
                Volver a la prueba
              </button>
              <button
                type="button"
                onClick={handleFinalSubmit}
                disabled={isSubmitting}
                className="px-5 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow transition-colors"
              >
                {isSubmitting ? 'Calificando...' : 'Sí, Calificar Examen'}
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
