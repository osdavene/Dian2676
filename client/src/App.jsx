import React, { useState, useEffect } from 'react';
import Navbar from './components/Navbar';
import JobModal from './components/JobModal';
import Dashboard from './pages/Dashboard';
import ExamSimulator from './pages/ExamSimulator';
import ExamResults from './pages/ExamResults';
import PracticeSelection from './pages/PracticeSelection';
import QuestionBankView from './pages/QuestionBankView';
import StatsAnalytics from './pages/StatsAnalytics';
import { getJobs, getJobDetails, getDashboardStats, startSession } from './services/api';

export default function App() {
  const [jobs, setJobs] = useState([]);
  const [selectedJob, setSelectedJob] = useState(null);
  const [axes, setAxes] = useState([]);
  const [currentTab, setCurrentTab] = useState('dashboard');
  
  // Estado de evaluación activa
  const [activeSession, setActiveSession] = useState(null);
  const [sessionQuestions, setSessionQuestions] = useState([]);
  const [isPracticeMode, setIsPracticeMode] = useState(false);
  const [reviewedSessionId, setReviewedSessionId] = useState(null);

  // Estadísticas globales
  const [stats, setStats] = useState(null);

  // Modal de nuevo cargo
  const [isJobModalOpen, setIsJobModalOpen] = useState(false);
  const [loading, setLoading] = useState(true);

  // 1. Cargar perfiles de cargo iniciales
  const loadJobs = async () => {
    try {
      const data = await getJobs();
      setJobs(data);
      if (data.length > 0 && !selectedJob) {
        // Seleccionar por defecto Analista III
        const analista3 = data.find(j => j.code === 'AT-FL-2011') || data[0];
        setSelectedJob(analista3);
      }
    } catch (err) {
      console.error('Error cargando cargos:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadJobs();
  }, []);

  // 2. Al cambiar el cargo seleccionado, cargar sus ejes y estadísticas
  const loadJobData = async (jobId) => {
    try {
      const [jobData, statsData] = await Promise.all([
        getJobDetails(jobId),
        getDashboardStats(jobId)
      ]);
      setAxes(jobData.axes || []);
      setStats(statsData);
    } catch (err) {
      console.error('Error cargando datos del cargo:', err);
    }
  };

  useEffect(() => {
    if (selectedJob?.id) {
      loadJobData(selectedJob.id);
    }
  }, [selectedJob]);

  // 3. Iniciar Examen Oficial o Práctica
  const handleStartExam = async (mode = 'simulacro_oficial', axisId = null) => {
    try {
      const isPractice = (mode === 'practica_eje');
      setIsPracticeMode(isPractice);

      const res = await startSession({
        job_profile_id: selectedJob.id,
        mode: mode,
        thematic_axis_id: axisId,
        time_limit_seconds: isPractice ? 7200 : 18000 // 5 horas oficiales para simulacro
      });

      setActiveSession(res.session);
      setSessionQuestions(res.questions);
      setCurrentTab('simulator');
    } catch (err) {
      alert(err.message || 'Error al iniciar la prueba');
    }
  };

  // 4. Iniciar Práctica por Eje
  const handleSelectAxis = (axisId) => {
    handleStartExam('practica_eje', axisId);
  };

  // 5. Iniciar Repaso de Fallos
  const handleStartReview = () => {
    handleStartExam('repaso_fallos');
  };

  // 6. Finalizar Examen
  const handleFinishExam = (sessionId) => {
    setReviewedSessionId(sessionId);
    setCurrentTab('results');
    if (selectedJob?.id) {
      loadJobData(selectedJob.id);
    }
  };

  // 7. Ver revisión histórica
  const handleViewHistoryItem = (sessionId) => {
    setReviewedSessionId(sessionId);
    setCurrentTab('results');
  };

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans">
      
      {/* Navegación Principal */}
      <Navbar
        currentTab={currentTab}
        setCurrentTab={(tab) => {
          if (tab === 'simulator' && !activeSession) {
            handleStartExam('simulacro_oficial');
            return;
          }
          setCurrentTab(tab);
        }}
        jobs={jobs}
        selectedJob={selectedJob}
        setSelectedJob={setSelectedJob}
        onOpenJobModal={() => setIsJobModalOpen(true)}
      />

      {/* Contenido Principal según Pestaña */}
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        {loading ? (
          <div className="flex flex-col items-center justify-center min-h-[60vh] space-y-3">
            <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <p className="text-sm font-semibold text-slate-600">Conectando con la base de datos de PostgreSQL...</p>
          </div>
        ) : (
          <>
            {currentTab === 'dashboard' && (
              <Dashboard
                selectedJob={selectedJob}
                stats={stats}
                onStartExam={(mode) => handleStartExam(mode)}
                onStartPractice={() => setCurrentTab('practice')}
                onStartReview={handleStartReview}
                onViewHistoryItem={handleViewHistoryItem}
                setCurrentTab={setCurrentTab}
              />
            )}

            {currentTab === 'simulator' && activeSession && (
              <ExamSimulator
                session={activeSession}
                questions={sessionQuestions}
                onFinishExam={handleFinishExam}
                isPractice={isPracticeMode}
              />
            )}

            {currentTab === 'practice' && (
              <PracticeSelection
                axes={axes}
                onSelectAxis={handleSelectAxis}
                onBack={() => setCurrentTab('dashboard')}
              />
            )}

            {currentTab === 'repaso' && (
              <div className="max-w-4xl mx-auto py-8 text-center space-y-4 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h2 className="text-xl font-bold text-slate-900">Modo Repaso de Preguntas Falladas</h2>
                <p className="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto">
                  El sistema extrae todas las preguntas en las que hayas fallado en intentos previos para que refuerces los artículos y conceptos clave.
                </p>
                <button
                  onClick={handleStartReview}
                  className="px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-colors shadow"
                >
                  Comenzar Repaso de Fallos Ahora
                </button>
              </div>
            )}

            {currentTab === 'bank' && (
              <QuestionBankView
                selectedJob={selectedJob}
                axes={axes}
              />
            )}

            {currentTab === 'stats' && (
              <StatsAnalytics
                stats={stats}
                selectedJob={selectedJob}
                onStartPracticeAxis={handleSelectAxis}
                onReviewFailed={handleStartReview}
              />
            )}

            {currentTab === 'results' && reviewedSessionId && (
              <ExamResults
                sessionId={reviewedSessionId}
                onRestartExam={(mode) => handleStartExam(mode)}
                onBackToDashboard={() => setCurrentTab('dashboard')}
              />
            )}
          </>
        )}
      </main>

      {/* Footer */}
      <footer className="bg-slate-900 text-slate-400 py-6 text-center text-xs border-t border-slate-800">
        <p className="font-semibold text-slate-300">
          Simulador Concurso DIAN 2676 • Guía de Orientación al Aspirante (CNSC - PAMC 006 de 2026 / FUAA)
        </p>
        <p className="text-[11px] text-slate-500 mt-1">
          Base de Datos PostgreSQL 18 • Cargo: Analista III (Ficha FT-TAH-1824) • Escalable para futuros grupos
        </p>
      </footer>

      {/* Modal de Crear Cargo */}
      <JobModal
        isOpen={isJobModalOpen}
        onClose={() => setIsJobModalOpen(false)}
        jobs={jobs}
        onJobCreated={(newJob) => {
          loadJobs();
          setSelectedJob(newJob);
        }}
      />

    </div>
  );
}
