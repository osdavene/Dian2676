import React, { useState } from 'react';
import { X, Briefcase, Plus, Check } from 'lucide-react';
import { createJob } from '../services/api';

export default function JobModal({ isOpen, onClose, jobs, onJobCreated }) {
  const [formData, setFormData] = useState({
    code: '',
    name: '',
    level: 'Nivel Técnico',
    grade: '02',
    process: 'MISIONAL: Cumplimiento de obligaciones tributarias, aduaneras y cambiarias',
    subprocess: 'Fiscalización y liquidación',
    purpose: ''
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.code || !formData.name) {
      setError('Por favor completa código y nombre del cargo');
      return;
    }
    setLoading(true);
    setError('');
    try {
      const newJob = await createJob(formData);
      onJobCreated(newJob);
      onClose();
    } catch (err) {
      setError(err.message || 'Error al guardar el cargo');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
        
        {/* Header */}
        <div className="bg-slate-900 text-white p-5 flex items-center justify-between border-b border-slate-800">
          <div className="flex items-center space-x-2">
            <Briefcase className="w-5 h-5 text-amber-400" />
            <h2 className="text-lg font-bold">Gestión de Perfiles y Cargos DIAN</h2>
          </div>
          <button 
            onClick={onClose}
            className="text-slate-400 hover:text-white transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6">
          <p className="text-xs text-slate-500 mb-4">
            Actualmente el simulador está optimizado para <strong>Analista III</strong>. Aquí puedes registrar otros grupos convocados en el concurso DIAN 2676 (Analista II, Gestor, etc.) para asociarles sus propios ejes y preguntas.
          </p>

          {error && (
            <div className="mb-4 p-3 bg-red-50 text-red-700 text-xs rounded-lg border border-red-200">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Código de Empleo / Ficha *
                </label>
                <input
                  type="text"
                  placeholder="ej. AT-FL-2010"
                  value={formData.code}
                  onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                  className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Denominación del Empleo *
                </label>
                <input
                  type="text"
                  placeholder="ej. Analista II"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                  required
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Nivel Jerárquico *
                </label>
                <select
                  value={formData.level}
                  onChange={(e) => setFormData({ ...formData, level: e.target.value })}
                  className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                >
                  <option value="Nivel Asistencial">Nivel Asistencial</option>
                  <option value="Nivel Técnico">Nivel Técnico</option>
                  <option value="Nivel Profesional">Nivel Profesional</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Grado *
                </label>
                <input
                  type="text"
                  placeholder="ej. 02 o 01"
                  value={formData.grade}
                  onChange={(e) => setFormData({ ...formData, grade: e.target.value })}
                  className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Subproceso
              </label>
              <input
                type="text"
                placeholder="ej. Fiscalización y liquidación"
                value={formData.subprocess}
                onChange={(e) => setFormData({ ...formData, subprocess: e.target.value })}
                className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Propósito Principal del Cargo
              </label>
              <textarea
                rows="2"
                placeholder="Breve resumen del propósito funcional según el MERF..."
                value={formData.purpose}
                onChange={(e) => setFormData({ ...formData, purpose: e.target.value })}
                className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-600 focus:outline-none resize-none"
              ></textarea>
            </div>

            <div className="flex justify-end space-x-3 pt-3 border-t border-slate-100">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
              >
                Cancelar
              </button>
              <button
                type="submit"
                disabled={loading}
                className="px-5 py-2 text-xs font-bold bg-blue-700 hover:bg-blue-800 text-white rounded-lg shadow transition-colors flex items-center space-x-1"
              >
                <Plus className="w-4 h-4" />
                <span>{loading ? 'Guardando...' : 'Crear Perfil de Cargo'}</span>
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  );
}
