const express = require('express');
const router = express.Router();
const db = require('../db');

// Iniciar sesión de examen o práctica
router.post('/start', async (req, res) => {
  try {
    const {
      job_profile_id = 1,
      mode = 'simulacro_oficial',
      thematic_axis_id,
      question_limit,
      time_limit_seconds = 18000
    } = req.body;

    let selectedQuestions = [];

    if (mode === 'repaso_fallos') {
      // Obtener preguntas que el usuario ha contestado mal en sesiones anteriores
      const failedQuery = `
        SELECT DISTINCT q.id, q.job_profile_id, q.thematic_axis_id, q.question_type, 
               q.difficulty, q.case_text, q.stem, q.options, q.legal_basis, q.explanation_summary,
               ta.name as axis_name, ta.category as axis_category
        FROM questions q
        JOIN session_answers sa ON sa.question_id = q.id
        LEFT JOIN thematic_axes ta ON ta.id = q.thematic_axis_id
        WHERE q.job_profile_id = $1 AND sa.is_correct = FALSE AND q.is_active = TRUE
        ORDER BY RANDOM();
      `;
      const failedRes = await db.query(failedQuery, [job_profile_id]);
      selectedQuestions = failedRes.rows;

      if (selectedQuestions.length === 0) {
        return res.status(400).json({ 
          error: '¡Excelente! No tienes preguntas falladas registradas para repasar. Te sugerimos realizar un Simulacro Oficial primero.' 
        });
      }
    } else if (mode === 'practica_eje' && thematic_axis_id) {
      // Modo práctica por eje específico
      const axisQuery = `
        SELECT q.id, q.job_profile_id, q.thematic_axis_id, q.question_type, 
               q.difficulty, q.case_text, q.stem, q.options, q.legal_basis, q.explanation_summary,
               ta.name as axis_name, ta.category as axis_category
        FROM questions q
        LEFT JOIN thematic_axes ta ON ta.id = q.thematic_axis_id
        WHERE q.thematic_axis_id = $1 AND q.is_active = TRUE
        ORDER BY q.id ASC;
      `;
      const axisRes = await db.query(axisQuery, [thematic_axis_id]);
      selectedQuestions = axisRes.rows;
    } else {
      // Modo Simulacro Oficial
      const simQuery = `
        SELECT q.id, q.job_profile_id, q.thematic_axis_id, q.question_type, 
               q.difficulty, q.case_text, q.stem, q.options, q.legal_basis, q.explanation_summary,
               ta.name as axis_name, ta.category as axis_category
        FROM questions q
        LEFT JOIN thematic_axes ta ON ta.id = q.thematic_axis_id
        WHERE q.job_profile_id = $1 AND q.is_active = TRUE
        ORDER BY ta.category DESC, RANDOM();
      `;
      const simRes = await db.query(simQuery, [job_profile_id]);
      selectedQuestions = simRes.rows;
    }

    if (question_limit && parseInt(question_limit, 10) > 0) {
      selectedQuestions = selectedQuestions.slice(0, parseInt(question_limit, 10));
    }

    if (selectedQuestions.length === 0) {
      return res.status(400).json({ error: 'No se encontraron preguntas disponibles para los criterios seleccionados' });
    }

    // Crear sesión en base de datos
    const sessionRes = await db.query(
      `INSERT INTO exam_sessions (job_profile_id, mode, thematic_axis_id, total_questions, time_limit_seconds)
       VALUES ($1, $2, $3, $4, $5)
       RETURNING *;`,
      [job_profile_id, mode, thematic_axis_id || null, selectedQuestions.length, time_limit_seconds]
    );

    const session = sessionRes.rows[0];

    // Ocultar correct_option en la lista inicial enviada al cliente para evitar trampa
    const clientQuestions = selectedQuestions.map(q => {
      let sanitizedOptions = q.options;
      if (typeof sanitizedOptions === 'string') {
        sanitizedOptions = JSON.parse(sanitizedOptions);
      }
      return {
        id: q.id,
        thematic_axis_id: q.thematic_axis_id,
        axis_name: q.axis_name,
        axis_category: q.axis_category,
        question_type: q.question_type,
        difficulty: q.difficulty,
        case_text: q.case_text,
        stem: q.stem,
        options: sanitizedOptions.map(opt => ({
          key: opt.key,
          text: opt.text
        }))
      };
    });

    res.status(201).json({
      session,
      questions: clientQuestions
    });
  } catch (err) {
    console.error('Error starting session:', err);
    res.status(500).json({ error: 'Error al iniciar la sesión de evaluación' });
  }
});

// Registrar o cambiar respuesta de una pregunta en la sesión
router.post('/:id/answer', async (req, res) => {
  try {
    const { id } = req.params;
    const { question_id, selected_option, time_spent_seconds = 0 } = req.body;

    if (!question_id || !selected_option) {
      return res.status(400).json({ error: 'ID de pregunta y opción seleccionada requeridos' });
    }

    // Verificar respuesta en BD
    const qRes = await db.query('SELECT correct_option, options, legal_basis, explanation_summary FROM questions WHERE id = $1', [question_id]);
    if (qRes.rows.length === 0) {
      return res.status(404).json({ error: 'Pregunta no encontrada' });
    }

    const question = qRes.rows[0];
    const isCorrect = (question.correct_option === selected_option);

    // Upsert en session_answers
    const checkAnswer = await db.query(
      'SELECT id FROM session_answers WHERE session_id = $1 AND question_id = $2',
      [id, question_id]
    );

    if (checkAnswer.rows.length > 0) {
      await db.query(
        `UPDATE session_answers 
         SET selected_option = $1, is_correct = $2, time_spent_seconds = time_spent_seconds + $3, answered_at = CURRENT_TIMESTAMP
         WHERE id = $4;`,
        [selected_option, isCorrect, time_spent_seconds, checkAnswer.rows[0].id]
      );
    } else {
      await db.query(
        `INSERT INTO session_answers (session_id, question_id, selected_option, is_correct, time_spent_seconds)
         VALUES ($1, $2, $3, $4, $5);`,
        [id, question_id, selected_option, isCorrect, time_spent_seconds]
      );
    }

    // Devolver retroalimentación inmediata (útil para modo práctica)
    let fullOptions = question.options;
    if (typeof fullOptions === 'string') {
      fullOptions = JSON.parse(fullOptions);
    }

    res.json({
      success: true,
      question_id,
      selected_option,
      is_correct: isCorrect,
      correct_option: question.correct_option,
      options: fullOptions,
      legal_basis: question.legal_basis,
      explanation_summary: question.explanation_summary
    });
  } catch (err) {
    console.error('Error submitting answer:', err);
    res.status(500).json({ error: 'Error al registrar respuesta' });
  }
});

// Finalizar sesión y calcular resultados oficiales (Regla oficial DIAN 2676: corte 70.00 truncado a 2 decimales)
router.post('/:id/complete', async (req, res) => {
  try {
    const { id } = req.params;
    const { time_spent_seconds = 0 } = req.body;

    // Obtener sesión
    const sRes = await db.query('SELECT * FROM exam_sessions WHERE id = $1', [id]);
    if (sRes.rows.length === 0) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    const session = sRes.rows[0];

    // Obtener todas las respuestas registradas
    const ansRes = await db.query(
      'SELECT * FROM session_answers WHERE session_id = $1',
      [id]
    );

    const answers = ansRes.rows;
    let correctCount = 0;
    let incorrectCount = 0;

    answers.forEach(a => {
      if (a.is_correct === true) correctCount++;
      else if (a.is_correct === false) incorrectCount++;
    });

    const totalQuestions = session.total_questions || answers.length;
    const omittedCount = Math.max(0, totalQuestions - (correctCount + incorrectCount));

    // Regla Oficial DIAN 2676:
    // "Las pruebas escritas se calificarán en una escala de cero (0) a cien (100) puntos, con una parte entera y dos (2) decimales truncados."
    let rawScore = totalQuestions > 0 ? (correctCount / totalQuestions) * 100 : 0;
    // Truncado a 2 decimales exactos:
    let truncatedScore = Math.floor(rawScore * 100) / 100;
    let isPassed = truncatedScore >= 70.00;

    // Actualizar registro de sesión
    const updateRes = await db.query(
      `UPDATE exam_sessions SET
         correct_answers = $1,
         incorrect_answers = $2,
         omitted_answers = $3,
         score = $4,
         is_passed = $5,
         time_spent_seconds = $6,
         completed_at = CURRENT_TIMESTAMP
       WHERE id = $7
       RETURNING *;`,
      [correctCount, incorrectCount, omittedCount, truncatedScore, isPassed, time_spent_seconds, id]
    );

    res.json({
      session: updateRes.rows[0],
      stats: {
        total_questions: totalQuestions,
        correct_answers: correctCount,
        incorrect_answers: incorrectCount,
        omitted_answers: omittedCount,
        score: truncatedScore,
        is_passed: isPassed,
        min_passing_score: 70.00,
        elimination_status: isPassed ? 'APROBADO' : 'NO APROBADO'
      }
    });
  } catch (err) {
    console.error('Error completing session:', err);
    res.status(500).json({ error: 'Error al finalizar sesión de examen' });
  }
});

// Obtener revisión detallada de la sesión (Pregunta por pregunta con opciones y justificaciones)
router.get('/:id/results', async (req, res) => {
  try {
    const { id } = req.params;

    const sRes = await db.query(
      `SELECT es.*, jp.name as job_name, jp.code as job_code
       FROM exam_sessions es
       LEFT JOIN job_profiles jp ON jp.id = es.job_profile_id
       WHERE es.id = $1`,
      [id]
    );

    if (sRes.rows.length === 0) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    const session = sRes.rows[0];

    // Detalle de respuestas y preguntas
    const detailsQuery = `
      SELECT q.id, q.case_text, q.stem, q.options, q.correct_option, 
             q.legal_basis, q.explanation_summary, q.difficulty, q.question_type,
             ta.name as axis_name, ta.category as axis_category,
             sa.selected_option, sa.is_correct, sa.time_spent_seconds
      FROM questions q
      LEFT JOIN session_answers sa ON sa.question_id = q.id AND sa.session_id = $1
      LEFT JOIN thematic_axes ta ON ta.id = q.thematic_axis_id
      WHERE sa.session_id = $1 OR q.id IN (SELECT question_id FROM session_answers WHERE session_id = $1)
      ORDER BY q.id ASC;
    `;
    const detailsRes = await db.query(detailsQuery, [id]);

    // Rendimiento por eje temático
    const axisBreakdown = {};
    detailsRes.rows.forEach(row => {
      const axis = row.axis_name || 'General';
      if (!axisBreakdown[axis]) {
        axisBreakdown[axis] = {
          name: axis,
          category: row.axis_category,
          total: 0,
          correct: 0,
          incorrect: 0,
          omitted: 0
        };
      }
      axisBreakdown[axis].total++;
      if (row.is_correct === true) axisBreakdown[axis].correct++;
      else if (row.is_correct === false && row.selected_option) axisBreakdown[axis].incorrect++;
      else axisBreakdown[axis].omitted++;
    });

    Object.keys(axisBreakdown).forEach(k => {
      const ab = axisBreakdown[k];
      ab.accuracy_pct = ab.total > 0 ? Math.floor((ab.correct / ab.total) * 10000) / 100 : 0;
    });

    res.json({
      session,
      axis_breakdown: Object.values(axisBreakdown),
      questions_review: detailsRes.rows.map(r => ({
        ...r,
        options: typeof r.options === 'string' ? JSON.parse(r.options) : r.options
      }))
    });
  } catch (err) {
    console.error('Error fetching session results:', err);
    res.status(500).json({ error: 'Error al obtener revisión de la sesión' });
  }
});

// Listar historial de sesiones
router.get('/', async (req, res) => {
  try {
    const { job_profile_id, limit = 20 } = req.query;
    let query = `
      SELECT es.*, jp.name as job_name, jp.code as job_code, ta.name as axis_name
      FROM exam_sessions es
      LEFT JOIN job_profiles jp ON jp.id = es.job_profile_id
      LEFT JOIN thematic_axes ta ON ta.id = es.thematic_axis_id
    `;
    let values = [];
    if (job_profile_id) {
      query += ` WHERE es.job_profile_id = $1`;
      values.push(job_profile_id);
    }
    query += ` ORDER BY es.id DESC LIMIT $${values.length + 1}`;
    values.push(limit);

    const result = await db.query(query, values);
    res.json(result.rows);
  } catch (err) {
    console.error('Error fetching session history:', err);
    res.status(500).json({ error: 'Error al obtener historial de simulacros' });
  }
});

module.exports = router;
