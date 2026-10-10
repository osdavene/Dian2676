const fs = require('fs');
const path = require('path');
const db = require('./db');

async function seedExtractedMaterials() {
  console.log('=== SEMBRANDO 150 PREGUNTAS EXTRAÍDAS DE LOS MATERIALES DIAN 2676 ===\n');

  try {
    const jsonPath = path.join(__dirname, 'extracted_questions.json');
    if (!fs.existsSync(jsonPath)) {
      console.error('No se encontró el archivo extracted_questions.json');
      return;
    }

    const questionsData = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
    console.log(`Cargadas ${questionsData.length} preguntas desde JSON.`);

    // 1. Obtener perfiles de Analista III y Gestor I
    const analistaRes = await db.query("SELECT id FROM job_profiles WHERE code = 'AT-FL-2011'");
    if (analistaRes.rows.length === 0) {
      console.error('No se encontró el perfil Analista III');
      return;
    }
    const analista3Id = analistaRes.rows[0].id;

    const gestorRes = await db.query("SELECT id FROM job_profiles WHERE code = 'PF-FL-3011'");
    const gestorId = gestorRes.rows[0]?.id;

    // Helper para asegurar la existencia de un eje temático
    async function ensureAxis(jobId, name, category, description, weight = 10.00) {
      let res = await db.query(
        'SELECT id FROM thematic_axes WHERE job_profile_id = $1 AND name = $2',
        [jobId, name]
      );
      if (res.rows.length === 0) {
        const ins = await db.query(
          `INSERT INTO thematic_axes (job_profile_id, category, name, description, weight_pct)
           VALUES ($1, $2, $3, $4, $5) RETURNING id`,
          [jobId, category, name, description, weight]
        );
        return ins.rows[0].id;
      }
      return res.rows[0].id;
    }

    // Definir descripciones de ejes
    const axisMeta = {
      'Código de Integridad y Ética Pública DIAN': {
        category: 'integridad',
        desc: 'Valores institucionales: Honestidad, Respeto, Compromiso, Diligencia, Justicia y dilemas éticos anticorrupción (Estatuto Anticorrupción Ley 1474 / Ley 1952).',
        weight: 10.00
      },
      'Competencias Comportamentales (Nivel Técnico)': {
        category: 'comportamental',
        desc: 'Situaciones de atención al ciudadano, trabajo en equipo, orientación al logro ético, adaptabilidad y liderazgo institucional.',
        weight: 15.00
      },
      'Competencias Comportamentales (Nivel Profesional)': {
        category: 'comportamental',
        desc: 'Situaciones de liderazgo colaborativo, toma de decisiones, análisis estratégico y orientación al logro en nivel profesional.',
        weight: 15.00
      },
      'Gestión Documental y Modelo MIPG': {
        category: 'funcional_basica',
        desc: 'Ley 594 de 2000, Tablas de Retención Documental (TRD), foliación técnica, AGN, ciclo vital del documento y MIPG.',
        weight: 10.00
      },
      'Carrera Administrativa y Función Pública': {
        category: 'funcional_basica',
        desc: 'Ley 909 de 2004, Decreto 1083 de 2015, situaciones administrativas, evaluación del desempeño laboral (EDL), Comisión de Personal y Régimen Disciplinario.',
        weight: 10.00
      },
      'Constitución Política y Derechos Fundamentales': {
        category: 'funcional_basica',
        desc: 'Principios fundamentales de la Constitución de 1991, debido proceso (art. 29), derecho de petición (art. 23), tutela y estructura administrativa.',
        weight: 10.00
      }
    };

    // Preparar ejes para Analista III
    const analistaAxisMap = {};
    for (const [name, meta] of Object.entries(axisMeta)) {
      if (name.includes('Nivel Profesional')) continue;
      analistaAxisMap[name] = await ensureAxis(analista3Id, name, meta.category, meta.desc, meta.weight);
    }

    // Preparar ejes para Gestor I si existe
    const gestorAxisMap = {};
    if (gestorId) {
      for (const [name, meta] of Object.entries(axisMeta)) {
        if (name.includes('Nivel Técnico')) continue;
        const targetName = name === 'Competencias Comportamentales (Nivel Profesional)' 
          ? 'Competencias Comportamentales (Nivel Profesional)' 
          : name;
        gestorAxisMap[name] = await ensureAxis(gestorId, targetName, meta.category, meta.desc, meta.weight);
      }
    }

    // Función para insertar preguntas
    async function insertQuestionsForJob(jobId, axisMap, isGestor = false) {
      let inserted = 0;
      let skipped = 0;

      for (const q of questionsData) {
        let axisName = q.axis_name;
        if (isGestor && axisName.includes('Nivel Técnico')) {
          axisName = 'Competencias Comportamentales (Nivel Profesional)';
        }

        const axisId = axisMap[axisName];
        if (!axisId) continue;

        // Comprobar si ya existe una pregunta con ese stem exacto
        const exists = await db.query(
          'SELECT id FROM questions WHERE job_profile_id = $1 AND stem = $2',
          [jobId, q.stem]
        );

        if (exists.rows.length === 0) {
          await db.query(
            `INSERT INTO questions (
              job_profile_id,
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
            ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, TRUE)`,
            [
              jobId,
              axisId,
              q.question_type || 'situacional',
              q.difficulty || 'medio',
              q.case_text,
              q.stem,
              JSON.stringify(q.options),
              q.correct_option,
              q.legal_basis,
              q.explanation_summary
            ]
          );
          inserted++;
        } else {
          skipped++;
        }
      }
      return { inserted, skipped };
    }

    // 2. Insertar para Analista III
    console.log('--- Insertando en Analista III ---');
    const analistaResStats = await insertQuestionsForJob(analista3Id, analistaAxisMap, false);
    console.log(`Analista III -> Nuevas insertadas: ${analistaResStats.inserted}, Omitidas/Preexistentes: ${analistaResStats.skipped}`);

    // 3. Insertar para Gestor I
    if (gestorId) {
      console.log('--- Insertando en Gestor I ---');
      const gestorResStats = await insertQuestionsForJob(gestorId, gestorAxisMap, true);
      console.log(`Gestor I -> Nuevas insertadas: ${gestorResStats.inserted}, Omitidas/Preexistentes: ${gestorResStats.skipped}`);
    }

    // 4. Mostrar resumen consolidado
    const summaryRes = await db.query(
      `SELECT jp.name as cargo, ta.name as eje, COUNT(q.id) as total_preguntas
       FROM job_profiles jp
       JOIN thematic_axes ta ON ta.job_profile_id = jp.id
       LEFT JOIN questions q ON q.thematic_axis_id = ta.id AND q.is_active = TRUE
       WHERE jp.id IN ($1, $2)
       GROUP BY jp.name, jp.id, ta.name, ta.id
       ORDER BY jp.id, ta.id`,
      [analista3Id, gestorId || 0]
    );

    console.log('\n--- Estado Consolidado del Banco de Preguntas ---');
    console.table(summaryRes.rows);

    const totalAnalista = await db.query(
      "SELECT COUNT(*) FROM questions WHERE job_profile_id = $1 AND is_active = TRUE",
      [analista3Id]
    );
    console.log(`\n🎉 Total General de Preguntas Activas para ANALISTA III: ${totalAnalista.rows[0].count}`);

    if (gestorId) {
      const totalGestor = await db.query(
        "SELECT COUNT(*) FROM questions WHERE job_profile_id = $1 AND is_active = TRUE",
        [gestorId]
      );
      console.log(`🎉 Total General de Preguntas Activas para GESTOR I: ${totalGestor.rows[0].count}`);
    }

  } catch (error) {
    console.error('Error durante la siembra de preguntas:', error);
  } finally {
    process.exit(0);
  }
}

seedExtractedMaterials();
