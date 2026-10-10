-- Esquema de Base de Datos para Simulador Concurso DIAN 2676
-- PostgreSQL 18+

CREATE TABLE IF NOT EXISTS job_profiles (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    level VARCHAR(50) NOT NULL,
    grade VARCHAR(20) NOT NULL,
    process VARCHAR(255) NOT NULL,
    subprocess VARCHAR(255) NOT NULL,
    purpose TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS thematic_axes (
    id SERIAL PRIMARY KEY,
    job_profile_id INTEGER REFERENCES job_profiles(id) ON DELETE CASCADE,
    category VARCHAR(50) NOT NULL, -- 'funcional_basica', 'funcional_especifica', 'comportamental', 'integridad'
    name VARCHAR(255) NOT NULL,
    description TEXT,
    weight_pct NUMERIC(5,2) DEFAULT 0.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS questions (
    id SERIAL PRIMARY KEY,
    job_profile_id INTEGER REFERENCES job_profiles(id) ON DELETE CASCADE,
    thematic_axis_id INTEGER REFERENCES thematic_axes(id) ON DELETE SET NULL,
    question_type VARCHAR(50) NOT NULL DEFAULT 'situacional', -- 'situacional', 'likert'
    difficulty VARCHAR(20) DEFAULT 'medio', -- 'básico', 'medio', 'avanzado'
    case_text TEXT,
    stem TEXT NOT NULL,
    options JSONB NOT NULL,
    correct_option VARCHAR(10),
    legal_basis TEXT,
    explanation_summary TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS exam_sessions (
    id SERIAL PRIMARY KEY,
    job_profile_id INTEGER REFERENCES job_profiles(id) ON DELETE SET NULL,
    mode VARCHAR(50) NOT NULL DEFAULT 'simulacro_oficial', -- 'simulacro_oficial', 'practica_eje', 'repaso_fallos'
    thematic_axis_id INTEGER REFERENCES thematic_axes(id) ON DELETE SET NULL,
    total_questions INTEGER NOT NULL DEFAULT 0,
    correct_answers INTEGER NOT NULL DEFAULT 0,
    incorrect_answers INTEGER NOT NULL DEFAULT 0,
    omitted_answers INTEGER NOT NULL DEFAULT 0,
    score NUMERIC(5,2) DEFAULT 0.00,
    is_passed BOOLEAN DEFAULT FALSE,
    time_spent_seconds INTEGER DEFAULT 0,
    time_limit_seconds INTEGER DEFAULT 18000, -- 5 horas oficiales
    completed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS session_answers (
    id SERIAL PRIMARY KEY,
    session_id INTEGER REFERENCES exam_sessions(id) ON DELETE CASCADE,
    question_id INTEGER REFERENCES questions(id) ON DELETE CASCADE,
    selected_option VARCHAR(10),
    is_correct BOOLEAN,
    time_spent_seconds INTEGER DEFAULT 0,
    answered_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Índices de consulta rápida
CREATE INDEX IF NOT EXISTS idx_questions_job ON questions(job_profile_id);
CREATE INDEX IF NOT EXISTS idx_questions_axis ON questions(thematic_axis_id);
CREATE INDEX IF NOT EXISTS idx_questions_type ON questions(question_type);
CREATE INDEX IF NOT EXISTS idx_sessions_job ON exam_sessions(job_profile_id);
CREATE INDEX IF NOT EXISTS idx_session_answers_session ON session_answers(session_id);
CREATE INDEX IF NOT EXISTS idx_session_answers_question ON session_answers(question_id);
