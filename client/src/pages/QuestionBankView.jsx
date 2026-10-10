import React, { useState, useEffect } from 'react';
import { 
  HelpCircle, 
  Search, 
  Plus, 
  Download, 
  Upload, 
  ChevronDown, 
  ChevronUp, 
  Scale, 
  Trash2, 
  Filter,
  CheckCircle2,
  FileText
} from 'lucide-react';
import { getQuestions, deleteQuestion, exportQuestions, importQuestions } from '../services/api';
import NewQuestionModal from '../components/NewQuestionModal';

export default function QuestionBankView({ selectedJob, axes = [] }) {
  const [questions, setQuestions] = useState([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [expandedId, setExpandedId] = useState(null);
  
  // Filtros
  const [selectedAxis, setSelectedAxis] = useState('');
  const [selectedDifficulty, setSelectedDifficulty] = useState('');
  const [search, setSearch] = useState('');
  
  // Modal de creación
  const [isNewModalOpen, setIsNewModalOpen] = useState(false);

  const fetchBank = async () => {
    setLoading(true);
    try {
      const data = await getQuestions({
        job_profile_id: selectedJob?.id || 1,
        thematic_axis_id: selectedAxis || undefined,
        difficulty: selectedDifficulty || undefined,
        search: search || undefined,
        limit: 100
      });
      setQuestions(data.questions || []);
      setTotal(data.total || 0);
    } catch (err) {
      console.error('Error cargando banco:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (selectedJob) {
      fetchBank();
    }
  }, [selectedJob, selectedAxis, selectedDifficulty, search]);

  const handleDelete = async (id, e) => {
    e.stopPropagation();
    if (!window.confirm('¿Seguro que deseas eliminar esta pregunta del banco?')) return;
    try {
      await deleteQuestion(id);
      fetchBank();
    } catch (err) {
      alert('Error al eliminar pregunta');
    }
  };

  const handleExport = async () => {
    try {
      const data = await exportQuestions(selectedJob.id);
      const jsonStr = JSON.stringify(data, null, 2);
      const blob = new Blob([jsonStr], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `banco_preguntas_${selectedJob.code}.json`;
      a.click();
      URL.revokeObjectURL(url);
    } catch (err) {
      alert('Error exportando preguntas');
    }
  };

  const handleImport = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = async (event) => {
      try {
        const imported = JSON.parse(event.target.result);
        if (Array.isArray(imported)) {
          await importQuestions(selectedJob.id, imported);
          alert(`¡Se importaron ${imported.length} preguntas exitosamente!`);
          fetchBank();
        } else {
          alert('El archivo JSON debe contener un arreglo de preguntas');
        }
      } catch (err) {
        alert('Archivo JSON inválido');
      }
    };
    reader.readAsText(file);
  };

  return (
    <div className="max-w-6xl mx-auto space-y-6 pb-16">
      
      {/* Encabezado y Acciones */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <HelpCircle className="w-7 h-7 text-purple-700" />
            <span>Banco de Preguntas en PostgreSQL</span>
          </h1>
          <p className="text-xs sm:text-sm text-slate-500">
            {total} preguntas registradas para <strong>{selectedJob?.name} ({selectedJob?.code})</strong>
          </p>
        </div>

        <div className="flex items-center space-x-2">
          <label className="cursor-pointer px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors flex items-center space-x-1 border border-slate-200">
            <Upload className="w-3.5 h-3.5" />
            <span>Importar JSON</span>
            <input type="file" accept=".json" onChange={handleImport} className="hidden" />
          </label>

          <button
            onClick={handleExport}
            className="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors flex items-center space-x-1 border border-slate-200"
          >
            <Download className="w-3.5 h-3.5" />
            <span>Exportar JSON</span>
          </button>

          <button
            onClick={() => setIsNewModalOpen(true)}
            className="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition-colors flex items-center space-x-1 shadow-sm"
          >
            <Plus className="w-4 h-4" />
            <span>Nueva Pregunta</span>
          </button>
        </div>
      </div>

      {/* Barra de Filtros y Búsqueda */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="relative">
          <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
          <input
            type="text"
            placeholder="Buscar por caso, norma o palabra clave..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <div>
          <select
            value={selectedAxis}
            onChange={(e) => setSelectedAxis(e.target.value)}
            className="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option value="">Todos los Ejes Temáticos</option>
            {axes.map(ax => (
              <option key={ax.id} value={ax.id}>{ax.name}</option>
            ))}
          </select>
        </div>

        <div>
          <select
            value={selectedDifficulty}
            onChange={(e) => setSelectedDifficulty(e.target.value)}
            className="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option value="">Todas las Dificultades</option>
            <option value="básico">Básico</option>
            <option value="medio">Medio</option>
            <option value="avanzado">Avanzado</option>
          </select>
        </div>
      </div>

      {/* Lista de Preguntas */}
      {loading ? (
        <div className="text-center py-12">
          <div className="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
          <span className="text-xs text-slate-500">Cargando preguntas de la base de datos...</span>
        </div>
      ) : questions.length === 0 ? (
        <div className="text-center py-12 bg-white rounded-2xl border border-slate-200">
          <p className="text-slate-500 text-xs">No se encontraron preguntas con los filtros seleccionados.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {questions.map((q, idx) => {
            const isExpanded = expandedId === q.id;
            const options = typeof q.options === 'string' ? JSON.parse(q.options) : q.options;

            return (
              <div
                key={q.id}
                className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all"
              >
                {/* Header de la Tarjeta */}
                <div
                  onClick={() => setExpandedId(isExpanded ? null : q.id)}
                  className="p-4 sm:p-5 flex items-start justify-between cursor-pointer hover:bg-slate-50/80 transition-colors"
                >
                  <div className="space-y-1.5 flex-1 pr-4">
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="font-bold text-xs text-slate-700">#{idx + 1}</span>
                      <span className="bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        {q.axis_name}
                      </span>
                      <span className="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full capitalize">
                        {q.difficulty}
                      </span>
                      <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                        Clave: {q.correct_option}
                      </span>
                    </div>

                    <h3 className="text-xs sm:text-sm font-bold text-slate-900 line-clamp-2">
                      {q.stem}
                    </h3>
                  </div>

                  <div className="flex items-center space-x-2">
                    <button
                      onClick={(e) => handleDelete(q.id, e)}
                      title="Eliminar pregunta"
                      className="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                    {isExpanded ? (
                      <ChevronUp className="w-5 h-5 text-slate-400" />
                    ) : (
                      <ChevronDown className="w-5 h-5 text-slate-400" />
                    )}
                  </div>
                </div>

                {/* Detalle Expandido */}
                {isExpanded && (
                  <div className="p-5 pt-0 border-t border-slate-100 space-y-4 text-xs bg-slate-50/30">
                    
                    {/* Caso */}
                    {q.case_text && (
                      <div className="bg-white p-3.5 rounded-xl border border-slate-200 text-slate-800 font-serif leading-relaxed">
                        <strong className="block text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                          CASO SITUACIONAL:
                        </strong>
                        {q.case_text}
                      </div>
                    )}

                    {/* Opciones */}
                    <div className="space-y-2">
                      <strong className="block text-slate-700 uppercase tracking-wider text-[10px]">
                        OPCIONES DE RESPUESTA:
                      </strong>
                      {options?.map(opt => (
                        <div
                          key={opt.key}
                          className={`p-3 rounded-xl border ${
                            opt.key === q.correct_option
                              ? 'bg-emerald-50/70 border-emerald-300 text-emerald-950 font-medium'
                              : 'bg-white border-slate-200 text-slate-700'
                          }`}
                        >
                          <div className="flex items-center space-x-2 mb-1">
                            <span className="font-bold">Opción {opt.key}:</span>
                            {opt.key === q.correct_option && (
                              <span className="text-[10px] font-bold bg-emerald-200 text-emerald-800 px-1.5 rounded">
                                CLAVE CORRECTA
                              </span>
                            )}
                          </div>
                          <div>{opt.text}</div>
                          {opt.justification && (
                            <div className="text-[11px] text-slate-500 mt-1 italic">
                              Justificación: {opt.justification}
                            </div>
                          )}
                        </div>
                      ))}
                    </div>

                    {/* Fundamento Jurídico */}
                    {q.legal_basis && (
                      <div className="bg-blue-50/50 p-2.5 rounded-xl border border-blue-200 text-blue-950">
                        ⚖️ <strong>Fundamento Jurídico:</strong> {q.legal_basis}
                      </div>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Modal para Crear Pregunta */}
      <NewQuestionModal
        isOpen={isNewModalOpen}
        onClose={() => setIsNewModalOpen(false)}
        selectedJob={selectedJob}
        axes={axes}
        onQuestionCreated={() => fetchBank()}
      />

    </div>
  );
}
