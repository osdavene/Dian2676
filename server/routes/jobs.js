const express = require('express');
const router = express.Router();
const db = require('../db');

// Listar todos los cargos / perfiles con conteo de preguntas
router.get('/', async (req, res) => {
  try {
    const query = `
      SELECT jp.*, 
             COUNT(DISTINCT q.id) as question_count,
             COUNT(DISTINCT ta.id) as axis_count
      FROM job_profiles jp
      LEFT JOIN thematic_axes ta ON ta.job_profile_id = jp.id
      LEFT JOIN questions q ON q.job_profile_id = jp.id AND q.is_active = TRUE
      GROUP BY jp.id
      ORDER BY jp.id ASC;
    `;
    const result = await db.query(query);
    res.json(result.rows);
  } catch (err) {
    console.error('Error fetching jobs:', err);
    res.status(500).json({ error: 'Error al obtener perfiles de cargo' });
  }
});

// Obtener detalle de un cargo
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const profile = await db.query('SELECT * FROM job_profiles WHERE id = $1', [id]);
    if (profile.rows.length === 0) {
      return res.status(404).json({ error: 'Perfil no encontrado' });
    }
    const axes = await db.query(
      `SELECT ta.*, COUNT(q.id) as question_count 
       FROM thematic_axes ta
       LEFT JOIN questions q ON q.thematic_axis_id = ta.id AND q.is_active = TRUE
       WHERE ta.job_profile_id = $1
       GROUP BY ta.id
       ORDER BY ta.category, ta.id`,
      [id]
    );
    res.json({
      ...profile.rows[0],
      axes: axes.rows
    });
  } catch (err) {
    console.error('Error fetching job details:', err);
    res.status(500).json({ error: 'Error al obtener detalles del cargo' });
  }
});

// Crear un nuevo perfil de cargo (Analista II, Gestor, Inspector, etc.)
router.post('/', async (req, res) => {
  try {
    const { code, name, level, grade, process, subprocess, purpose } = req.body;
    if (!code || !name || !level || !grade) {
      return res.status(400).json({ error: 'Código, nombre, nivel y grado son requeridos' });
    }
    const result = await db.query(
      `INSERT INTO job_profiles (code, name, level, grade, process, subprocess, purpose)
       VALUES ($1, $2, $3, $4, $5, $6, $7)
       RETURNING *;`,
      [code, name, level, grade, process || '', subprocess || '', purpose || '']
    );
    res.status(201).json(result.rows[0]);
  } catch (err) {
    console.error('Error creating job:', err);
    res.status(500).json({ error: 'Error al crear perfil de cargo', details: err.message });
  }
});

// Obtener ejes temáticos de un cargo
router.get('/:id/axes', async (req, res) => {
  try {
    const { id } = req.params;
    const result = await db.query(
      `SELECT ta.*, COUNT(q.id) as question_count 
       FROM thematic_axes ta
       LEFT JOIN questions q ON q.thematic_axis_id = ta.id AND q.is_active = TRUE
       WHERE ta.job_profile_id = $1
       GROUP BY ta.id
       ORDER BY ta.id ASC`,
      [id]
    );
    res.json(result.rows);
  } catch (err) {
    console.error('Error fetching axes:', err);
    res.status(500).json({ error: 'Error al obtener ejes temáticos' });
  }
});

// Crear nuevo eje temático
router.post('/:id/axes', async (req, res) => {
  try {
    const { id } = req.params;
    const { category, name, description, weight_pct } = req.body;
    if (!name || !category) {
      return res.status(400).json({ error: 'Nombre y categoría son requeridos' });
    }
    const result = await db.query(
      `INSERT INTO thematic_axes (job_profile_id, category, name, description, weight_pct)
       VALUES ($1, $2, $3, $4, $5)
       RETURNING *;`,
      [id, category, name, description || '', weight_pct || 0]
    );
    res.status(201).json(result.rows[0]);
  } catch (err) {
    console.error('Error creating axis:', err);
    res.status(500).json({ error: 'Error al crear eje temático' });
  }
});

module.exports = router;
