const API_URL = '/api';

export async function fetchJson(endpoint, options = {}) {
  const response = await fetch(`${API_URL}${endpoint}`, {
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
    ...options,
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.error || `Error HTTP ${response.status}`);
  }

  return data;
}

// Perfiles de Cargo
export const getJobs = () => fetchJson('/jobs');
export const getJobDetails = (id) => fetchJson(`/jobs/${id}`);
export const createJob = (jobData) => fetchJson('/jobs', {
  method: 'POST',
  body: JSON.stringify(jobData),
});
export const getAxes = (jobId) => fetchJson(`/jobs/${jobId}/axes`);
export const createAxis = (jobId, axisData) => fetchJson(`/jobs/${jobId}/axes`, {
  method: 'POST',
  body: JSON.stringify(axisData),
});

// Preguntas
export const getQuestions = (params = {}) => {
  const query = new URLSearchParams(params).toString();
  return fetchJson(`/questions?${query}`);
};
export const getQuestion = (id) => fetchJson(`/questions/${id}`);
export const createQuestion = (questionData) => fetchJson('/questions', {
  method: 'POST',
  body: JSON.stringify(questionData),
});
export const updateQuestion = (id, questionData) => fetchJson(`/questions/${id}`, {
  method: 'PUT',
  body: JSON.stringify(questionData),
});
export const deleteQuestion = (id) => fetchJson(`/questions/${id}`, {
  method: 'DELETE',
});
export const exportQuestions = (jobId) => fetchJson(`/questions/export/${jobId}`);
export const importQuestions = (jobId, questions) => fetchJson('/questions/import', {
  method: 'POST',
  body: JSON.stringify({ job_profile_id: jobId, questions }),
});

// Sesiones de Examen y Práctica
export const startSession = (sessionConfig) => fetchJson('/sessions/start', {
  method: 'POST',
  body: JSON.stringify(sessionConfig),
});
export const submitAnswer = (sessionId, answerData) => fetchJson(`/sessions/${sessionId}/answer`, {
  method: 'POST',
  body: JSON.stringify(answerData),
});
export const completeSession = (sessionId, completeData) => fetchJson(`/sessions/${sessionId}/complete`, {
  method: 'POST',
  body: JSON.stringify(completeData),
});
export const getSessionResults = (sessionId) => fetchJson(`/sessions/${sessionId}/results`);
export const getSessionHistory = (params = {}) => {
  const query = new URLSearchParams(params).toString();
  return fetchJson(`/sessions?${query}`);
};

// Estadísticas del Dashboard
export const getDashboardStats = (jobId) => fetchJson(`/stats/dashboard?job_profile_id=${jobId}`);
