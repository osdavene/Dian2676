const express = require('express');
const router = express.Router();
const db = require('../db');

// Resumen estadístico general del usuario
router.get('/dashboard', async (req, res) => {
  try {
    const { job_profile_id = 1 } = req.query;

    // 1. Resumen de sesiones
    const sessionsSummaryQuery = `
      SELECT 
        COUNT(*) as total_exams,
        COUNT(CASE WHEN is_passed = TRUE THEN 1 END) as total_passed,
        COUNT(CASE WHEN is_passed = FALSE AND completed_at IS NOT NULL THEN 1 END) as total_failed,
        COALESCE(AVG(score), 0) as average_score,
        COALESCE(MAX(score), 0) as best_score,
        COALESCE(SUM(correct_answers), 0) as total_correct_answers,
        COALESCE(SUM(incorrect_answers), 0) as total_incorrect_answers,
        COALESCE(SUM(omitted_answers), 0) as total_omitted_answers,
        COALESCE(SUM(time_spent_seconds), 0) as total_study_time_seconds
      FROM exam_sessions
      WHERE job_profile_id = $1 AND completed_at IS NOT NULL;
    `;
    const sessionsRes = await db.query(sessionsSummaryQuery, [job_profile_id]);
    const summary = sessionsRes.rows[0];

    // 2. Rendimiento acumulado por Eje Temático
    const axisPerformanceQuery = `
      SELECT 
        ta.id as axis_id,
        ta.name as axis_name,
        ta.category as axis_category,
        ta.weight_pct,
        COUNT(sa.id) as total_answered,
        COUNT(CASE WHEN sa.is_correct = TRUE THEN 1 END) as correct_count,
        COUNT(CASE WHEN sa.is_correct = FALSE THEN 1 END) as incorrect_count
      FROM thematic_axes ta
      JOIN questions q ON q.thematic_axis_id = ta.id AND q.job_profile_id = $1
      LEFT JOIN session_answers sa ON sa.question_id = q.id
      WHERE ta.job_profile_id = $1
      GROUP BY ta.id, ta.name, ta.category, ta.weight_pct
      ORDER BY ta.category, ta.id;
    `;
    const axisRes = await db.query(axisPerformanceQuery, [job_profile_id]);

    const axisStats = axisRes.rows.map(row => {
      const total = parseInt(row.total_answered, 10);
      const correct = parseInt(row.correct_count, 10);
      const accuracy = total > 0 ? Math.floor((correct / total) * 10000) / 100 : 0;
      return {
        ...row,
        accuracy_pct: accuracy,
        status: accuracy >= 70 ? 'Fuerte' : (total === 0 ? 'Sin evaluar' : 'Por mejorar')
      };
    });

    // 3. Conteo de preguntas falladas pendientes de repaso
    const failedCountQuery = `
      SELECT COUNT(DISTINCT q.id) as failed_count
      FROM questions q
      JOIN session_answers sa ON sa.question_id = q.id
      WHERE q.job_profile_id = $1 AND sa.is_correct = FALSE AND q.is_active = TRUE;
    `;
    const failedRes = await db.query(failedCountQuery, [job_profile_id]);
    const failedQuestionsCount = parseInt(failedRes.rows[0].failed_count, 10);

    // 4. Evolución temporal de los últimos 10 exámenes completados
    const evolutionQuery = `
      SELECT id, mode, score, is_passed, total_questions, correct_answers, incorrect_answers, 
             time_spent_seconds, completed_at
      FROM exam_sessions
      WHERE job_profile_id = $1 AND completed_at IS NOT NULL
      ORDER BY completed_at ASC
      LIMIT 15;
    `;
    const evoRes = await db.query(evolutionQuery, [job_profile_id]);

    // 5. Total de preguntas en banco para este cargo
    const bankRes = await db.query(
      'SELECT COUNT(*) as total FROM questions WHERE job_profile_id = $1 AND is_active = TRUE;',
      [job_profile_id]
    );

    res.json({
      summary: {
        total_exams: parseInt(summary.total_exams, 10),
        total_passed: parseInt(summary.total_passed, 10),
        total_failed: parseInt(summary.total_failed, 10),
        pass_rate_pct: parseInt(summary.total_exams, 10) > 0 
          ? Math.floor((parseInt(summary.total_passed, 10) / parseInt(summary.total_exams, 10)) * 10000) / 100 
          : 0,
        average_score: Math.floor(parseFloat(summary.average_score) * 100) / 100,
        best_score: parseFloat(summary.best_score),
        total_correct_answers: parseInt(summary.total_correct_answers, 10),
        total_incorrect_answers: parseInt(summary.total_incorrect_answers, 10),
        total_omitted_answers: parseInt(summary.total_omitted_answers, 10),
        total_study_time_seconds: parseInt(summary.total_study_time_seconds, 10),
        total_bank_questions: parseInt(bankRes.rows[0].total, 10),
        failed_questions_pending: failedQuestionsCount
      },
      axis_performance: axisStats,
      evolution: evoRes.rows
    });
  } catch (err) {
    console.error('Error fetching dashboard stats:', err);
    res.status(500).json({ error: 'Error al obtener estadísticas del dashboard' });
  }
});

module.exports = router;
