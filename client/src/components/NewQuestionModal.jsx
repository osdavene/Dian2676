import React, { useState } from 'react';
import { X, Plus, HelpCircle, Scale, Check } from 'lucide-react';
import { createQuestion } from '../services/api';

export default function NewQuestionModal({ isOpen, onClose, selectedJob, axes, onQuestionCreated }) {
  const [formData, setFormData] = useState({
    job_profile_id: selectedJob?.id || 1,
    thematic_axis_id: axes[0]?.id || '',
    question_type: 'situacional',
    difficulty: 'medio',
    case_text: '',
    stem: '',
    optA_text: '',
    optA_just: '',
    optB_text: '',
    optB_just: '',
    optC_text: '',
    optC_just: '',
    correct_option: 'A',
    legal_basis: '',
    explanation_summary: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.stem || !formData.optA_text || !formData.optB_text || !formData.optC_text) {
      setError('Por favor completa el enunciado y las 3 opciones de respuesta');
      return;
    }

    const options = [
      {
        key: 'A',
        text: formData.optA_text,
        is_correct: formData.correct_option === 'A',
        justification: formData.optA_just || (formData.correct_option === 'A' ? 'Respuesta jurídicamente procedente.' : 'No corresponde a los términos normativos vigentes.')
      },
      {
        key: 'B',
        text: formData.optB_text,
        is_correct: formData.correct_option === 'B',
        justification: formData.optB_just || (formData.correct_option === 'B' ? 'Respuesta jurídicamente procedente.' : 'No corresponde a los términos normativos vigentes.')
      },
      {
        key: 'C',
        text: formData.optC_text,
        is_correct: formData.correct_option === 'C',
        justification: formData.optC_just || (formData.correct_option === 'C' ? 'Respuesta jurídicamente procedente.' : 'No corresponde a los términos normativos vigentes.')
      }
    ];

    setLoading(true);
    setError('');

    try {
      const payload = {
        job_profile_id: formData.job_profile_id,
        thematic_axis_id: formData.thematic_axis_id ? parseInt(formData.thematic_axis_id, 10) : null,
        question_type: formData.question_type,
        difficulty: formData.difficulty,
        case_text: formData.case_text,
        stem: formData.stem,
        options,
        correct_option: formData.correct_option,
        legal_basis: formData.legal_basis,
        explanation_summary: formData.explanation_summary
      };

      const newQ = await createQuestion(payload);
      onQuestionCreated(newQ);
      onClose();
    } catch (err) {
      setError(err.message || 'Error al guardar la pregunta en PostgreSQL');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
      <div className="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 my-8">
        
        {/* Header */}
        <div className="bg-slate-900 text-white p-5 flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center space-x-2">
            <HelpCircle className="w-5 h-5 text-amber-400" />
            <h2 className="text-lg font-bold">Crear Pregunta de Juicio Situacional (PJS)</h2>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-white transition-colors">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 max-h-[80vh] overflow-y-auto space-y-4">
          {error && (
            <div className="p-3 bg-rose-50 text-rose-700 text-xs rounded-lg border border-rose-200">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4 text-xs">
            
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Eje Temático *</label>
                <select
                  value={formData.thematic_axis_id}
                  onChange={(e) => setFormData({ ...formData, thematic_axis_id: e.target.value })}
                  className="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                  required
                >
                  <option value="">Seleccione eje...</option>
                  {axes.map(ax => (
                    <option key={ax.id} value={ax.id}>{ax.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Dificultad *</label>
                <select
                  value={formData.difficulty}
                  onChange={(e) => setFormData({ ...formData, difficulty: e.target.value })}
                  className="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                >
                  <option value="básico">Básico</option>
                  <option value="medio">Medio</option>
                  <option value="avanzado">Avanzado</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block font-semibold text-slate-700 mb-1">
                Caso Hipotético (Contexto laboral de la DIAN)
              </label>
              <textarea
                rows="3"
                placeholder="Describe la situación real de fiscalización, aduanas, despacho o control..."
                value={formData.case_text}
                onChange={(e) => setFormData({ ...formData, case_text: e.target.value })}
                className="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none resize-none font-serif"
              ></textarea>
            </div>

            <div>
              <label className="block font-semibold text-slate-700 mb-1">
                Enunciado (La pregunta o tarea del funcionario) *
              </label>
              <textarea
                rows="2"
                placeholder="Frente a la situación descrita, al Analista III le corresponde..."
                value={formData.stem}
                onChange={(e) => setFormData({ ...formData, stem: e.target.value })}
                className="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none resize-none font-bold"
                required
              ></textarea>
            </div>

            {/* Opciones A, B, C */}
            <div className="space-y-3 pt-2 border-t border-slate-100">
              <span className="font-bold text-slate-800 uppercase tracking-wider block">
                Opciones de Respuesta (3 alternativas)
              </span>

              {/* Opción A */}
              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-bold text-blue-900">Opción A:</span>
                  <label className="flex items-center space-x-1 cursor-pointer">
                    <input
                      type="radio"
                      name="correct_option"
                      checked={formData.correct_option === 'A'}
                      onChange={() => setFormData({ ...formData, correct_option: 'A' })}
                      className="accent-blue-700"
                    />
                    <span className="font-bold text-[11px] text-blue-700">Clave Correcta</span>
                  </label>
                </div>
                <input
                  type="text"
                  placeholder="Texto de la alternativa A..."
                  value={formData.optA_text}
                  onChange={(e) => setFormData({ ...formData, optA_text: e.target.value })}
                  className="w-full p-2 rounded border border-slate-300 bg-white"
                  required
                />
                <input
                  type="text"
                  placeholder="Justificación técnica / jurídica de la Opción A..."
                  value={formData.optA_just}
                  onChange={(e) => setFormData({ ...formData, optA_just: e.target.value })}
                  className="w-full p-2 rounded border border-slate-200 bg-white text-[11px] text-slate-600"
                />
              </div>

              {/* Opción B */}
              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-bold text-blue-900">Opción B:</span>
                  <label className="flex items-center space-x-1 cursor-pointer">
                    <input
                      type="radio"
                      name="correct_option"
                      checked={formData.correct_option === 'B'}
                      onChange={() => setFormData({ ...formData, correct_option: 'B' })}
                      className="accent-blue-700"
                    />
                    <span className="font-bold text-[11px] text-blue-700">Clave Correcta</span>
                  </label>
                </div>
                <input
                  type="text"
                  placeholder="Texto de la alternativa B..."
                  value={formData.optB_text}
                  onChange={(e) => setFormData({ ...formData, optB_text: e.target.value })}
                  className="w-full p-2 rounded border border-slate-300 bg-white"
                  required
                />
                <input
                  type="text"
                  placeholder="Justificación técnica / jurídica de la Opción B..."
                  value={formData.optB_just}
                  onChange={(e) => setFormData({ ...formData, optB_just: e.target.value })}
                  className="w-full p-2 rounded border border-slate-200 bg-white text-[11px] text-slate-600"
                />
              </div>

              {/* Opción C */}
              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-bold text-blue-900">Opción C:</span>
                  <label className="flex items-center space-x-1 cursor-pointer">
                    <input
                      type="radio"
                      name="correct_option"
                      checked={formData.correct_option === 'C'}
                      onChange={() => setFormData({ ...formData, correct_option: 'C' })}
                      className="accent-blue-700"
                    />
                    <span className="font-bold text-[11px] text-blue-700">Clave Correcta</span>
                  </label>
                </div>
                <input
                  type="text"
                  placeholder="Texto de la alternativa C..."
                  value={formData.optC_text}
                  onChange={(e) => setFormData({ ...formData, optC_text: e.target.value })}
                  className="w-full p-2 rounded border border-slate-300 bg-white"
                  required
                />
                <input
                  type="text"
                  placeholder="Justificación técnica / jurídica de la Opción C..."
                  value={formData.optC_just}
                  onChange={(e) => setFormData({ ...formData, optC_just: e.target.value })}
                  className="w-full p-2 rounded border border-slate-200 bg-white text-[11px] text-slate-600"
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3 pt-2">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">
                  Fundamento Jurídico (Artículo y Ley)
                </label>
                <input
                  type="text"
                  placeholder="ej. Estatuto Tributario Art. 703; CPACA Art. 14"
                  value={formData.legal_basis}
                  onChange={(e) => setFormData({ ...formData, legal_basis: e.target.value })}
                  className="w-full p-2 rounded border border-slate-300 focus:outline-none"
                />
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">
                  Síntesis Explicativa
                </label>
                <input
                  type="text"
                  placeholder="Breve resumen de por qué es la solución eficaz..."
                  value={formData.explanation_summary}
                  onChange={(e) => setFormData({ ...formData, explanation_summary: e.target.value })}
                  className="w-full p-2 rounded border border-slate-300 focus:outline-none"
                />
              </div>
            </div>

            <div className="flex justify-end space-x-3 pt-4 border-t border-slate-100">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors font-semibold"
              >
                Cancelar
              </button>
              <button
                type="submit"
                disabled={loading}
                className="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg shadow font-bold transition-colors flex items-center space-x-1"
              >
                <Plus className="w-4 h-4" />
                <span>{loading ? 'Guardando...' : 'Guardar en PostgreSQL'}</span>
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  );
}
