const express = require('express');
const router = express.Router();
const db = require('../db');

// Listar preguntas con filtros
router.get('/', async (req, res) => {
  try {
    const { job_profile_id, thematic_axis_id, question_type, difficulty, search, limit = 100, offset = 0 } = req.query;

    let conditions = ['q.is_active = TRUE'];
    let values = [];
    let idx = 1;

    if (job_profile_id) {
      conditions.push(`q.job_profile_id = $${idx++}`);
      values.push(job_profile_id);
    }
    if (thematic_axis_id) {
      conditions.push(`q.thematic_axis_id = $${idx++}`);
      values.push(thematic_axis_id);
    }
    if (question_type) {
      conditions.push(`q.question_type = $${idx++}`);
      values.push(question_type);
    }
    if (difficulty) {
      conditions.push(`q.difficulty = $${idx++}`);
      values.push(difficulty);
    }
    if (search) {
      conditions.push(`(q.case_text ILIKE $${idx} OR q.stem ILIKE $${idx} OR q.legal_basis ILIKE $${idx})`);
      values.push(`%${search}%`);
      idx++;
    }

    const whereClause = conditions.length > 0 ? `WHERE ${conditions.join(' AND ')}` : '';

    const query = `
      SELECT q.*, 
             ta.name as axis_name, 
             ta.category as axis_category,
             jp.name as job_name,
             jp.code as job_code
      FROM questions q
      LEFT JOIN thematic_axes ta ON ta.id = q.thematic_axis_id
      LEFT JOIN job_profiles jp ON jp.id = q.job_profile_id
      ${whereClause}
      ORDER BY q.id ASC
      LIMIT $${idx++} OFFSET $${idx++};
    `;

    values.push(parseInt(limit, 10));
    values.push(parseInt(offset, 10));

    const result = await db.query(query, values);

    // Contar total
    const countQuery = `
      SELECT COUNT(*) as total 
      FROM questions q 
      ${whereClause};
    `;
    const countResult = await db.query(countQuery, values.slice(0, values.length - 2));

    res.json({
      total: parseInt(countResult.rows[0].total, 10),
      questions: result.rows
    });
  } catch (err) {
    console.error('Error fetching questions:', err);
    res.status(500).json({ error: 'Error al obtener preguntas' });
  }
});

// Obtener una pregunta específica
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const query = `
      SELECT q.*, 
             ta.name as axis_name, 
             ta.category as axis_category,
             jp.name as job_name
      FROM questions q
      LEFT JOIN thematic_axes ta ON ta.id = q.thematic_axis_id
      LEFT JOIN job_profiles jp ON jp.id = q.job_profile_id
      WHERE q.id = $1;
    `;
    const result = await db.query(query, [id]);
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Pregunta no encontrada' });
    }
    res.json(result.rows[0]);
  } catch (err) {
    console.error('Error fetching question:', err);
    res.status(500).json({ error: 'Error al obtener pregunta' });
  }
});

// Crear una nueva pregunta
router.post('/', async (req, res) => {
  try {
    const {
      job_profile_id,
      thematic_axis_id,
      question_type = 'situacional',
      difficulty = 'medio',
      case_text,
      stem,
      options,
      correct_option,
      legal_basis,
      explanation_summary
    } = req.body;

    if (!job_profile_id || !stem || !options || !correct_option) {
      return res.status(400).json({ error: 'Faltan campos obligatorios (perfil, enunciado, opciones, clave correcta)' });
    }

    const query = `
      INSERT INTO questions (
        job_profile_id, thematic_axis_id, question_type, difficulty,
        case_text, stem, options, correct_option, legal_basis, explanation_summary
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      RETURNING *;
    `;

    const result = await db.query(query, [
      job_profile_id,
      thematic_axis_id || null,
      question_type,
      difficulty,
      case_text || '',
      stem,
      typeof options === 'string' ? options : JSON.stringify(options),
      correct_option,
      legal_basis || '',
      explanation_summary || ''
    ]);

    res.status(201).json(result.rows[0]);
  } catch (err) {
    console.error('Error creating question:', err);
    res.status(500).json({ error: 'Error al registrar pregunta', details: err.message });
  }
});

// Actualizar pregunta
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const {
      thematic_axis_id,
      question_type,
      difficulty,
      case_text,
      stem,
      options,
      correct_option,
      legal_basis,
      explanation_summary,
      is_active
    } = req.body;

    const query = `
      UPDATE questions SET
        thematic_axis_id = COALESCE($1, thematic_axis_id),
        question_type = COALESCE($2, question_type),
        difficulty = COALESCE($3, difficulty),
        case_text = COALESCE($4, case_text),
        stem = COALESCE($5, stem),
        options = CASE WHEN $6::jsonb IS NOT NULL THEN $6::jsonb ELSE options END,
        correct_option = COALESCE($7, correct_option),
        legal_basis = COALESCE($8, legal_basis),
        explanation_summary = COALESCE($9, explanation_summary),
        is_active = COALESCE($10, is_active)
      WHERE id = $11
      RETURNING *;
    `;

    const result = await db.query(query, [
      thematic_axis_id,
      question_type,
      difficulty,
      case_text,
      stem,
      options ? (typeof options === 'string' ? options : JSON.stringify(options)) : null,
      correct_option,
      legal_basis,
      explanation_summary,
      is_active,
      id
    ]);

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Pregunta no encontrada' });
    }
    res.json(result.rows[0]);
  } catch (err) {
    console.error('Error updating question:', err);
    res.status(500).json({ error: 'Error al actualizar pregunta' });
  }
});

// Eliminar pregunta
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await db.query('DELETE FROM questions WHERE id = $1', [id]);
    res.json({ message: 'Pregunta eliminada exitosamente' });
  } catch (err) {
    console.error('Error deleting question:', err);
    res.status(500).json({ error: 'Error al eliminar pregunta' });
  }
});

// Exportar preguntas de un perfil
router.get('/export/:job_profile_id', async (req, res) => {
  try {
    const { job_profile_id } = req.params;
    const query = `
      SELECT q.*, ta.name as axis_name, jp.name as job_name
      FROM questions q
      JOIN job_profiles jp ON jp.id = q.job_profile_id
      LEFT JOIN thematic_axes ta ON ta.id = q.thematic_axis_id
      WHERE q.job_profile_id = $1 AND q.is_active = TRUE
      ORDER BY q.id ASC;
    `;
    const result = await db.query(query, [job_profile_id]);
    res.json(result.rows);
  } catch (err) {
    console.error('Error exporting questions:', err);
    res.status(500).json({ error: 'Error al exportar preguntas' });
  }
});

// Importar preguntas masivas
router.post('/import', async (req, res) => {
  try {
    const { job_profile_id, questions } = req.body;
    if (!job_profile_id || !Array.isArray(questions) || questions.length === 0) {
      return res.status(400).json({ error: 'Perfil de cargo y array de preguntas son requeridos' });
    }

    let inserted = 0;
    for (const q of questions) {
      await db.query(
        `INSERT INTO questions (
          job_profile_id, thematic_axis_id, question_type, difficulty,
          case_text, stem, options, correct_option, legal_basis, explanation_summary
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)`,
        [
          job_profile_id,
          q.thematic_axis_id || null,
          q.question_type || 'situacional',
          q.difficulty || 'medio',
          q.case_text || '',
          q.stem,
          typeof q.options === 'string' ? q.options : JSON.stringify(q.options),
          q.correct_option,
          q.legal_basis || '',
          q.explanation_summary || ''
        ]
      );
      inserted++;
    }
    res.json({ success: true, count: inserted });
  } catch (err) {
    console.error('Error importing questions:', err);
    res.status(500).json({ error: 'Error al importar preguntas' });
  }
});

module.exports = router;
