const db = require('./db');

async function seedAccionesControl() {
  console.log('=== SEMBRANDO PREGUNTAS DEL TEMA: Acciones de Control DIAN ===');

  try {
    // 1. Obtener perfil de Analista III
    const jobRes = await db.query("SELECT id FROM job_profiles WHERE code = 'AT-FL-2011'");
    if (jobRes.rows.length === 0) {
      console.error('No se encontró el perfil Analista III (AT-FL-2011)');
      return;
    }
    const analista3Id = jobRes.rows[0].id;

    // 2. Crear o actualizar el Eje Temático 'Acciones de Control DIAN'
    let axisControlRes = await db.query(
      "SELECT id FROM thematic_axes WHERE job_profile_id = $1 AND name = 'Acciones de Control DIAN'",
      [analista3Id]
    );

    let axisControlId;
    if (axisControlRes.rows.length === 0) {
      const insRes = await db.query(
        `INSERT INTO thematic_axes (job_profile_id, category, name, description, weight_pct)
         VALUES ($1, 'funcional_especifica', 'Acciones de Control DIAN', 'Procedimientos y facultades de control y fiscalización DIAN: acciones intensivas y masivas, visitas de verificación física e inspección ocular, actas de hechos, informes de cierre y trámites de investigación.', 15.00)
         RETURNING id`,
        [analista3Id]
      );
      axisControlId = insRes.rows[0].id;
      console.log(`Eje 'Acciones de Control DIAN' creado con ID: ${axisControlId}`);
    } else {
      axisControlId = axisControlRes.rows[0].id;
      console.log(`Eje 'Acciones de Control DIAN' existente con ID: ${axisControlId}`);
    }

    // 3. Crear o actualizar el Eje Temático 'ABC del LA - FT' para alinear con la carpeta
    let axisLaFtRes = await db.query(
      "SELECT id FROM thematic_axes WHERE job_profile_id = $1 AND (name = 'ABC del LA - FT' OR name LIKE 'ABC del LA%')",
      [analista3Id]
    );

    let axisLaFtId;
    if (axisLaFtRes.rows.length === 0) {
      const insRes = await db.query(
        `INSERT INTO thematic_axes (job_profile_id, category, name, description, weight_pct)
         VALUES ($1, 'funcional_especifica', 'ABC del LA - FT', 'Cuestionario y casos de estudio sobre prevención, detección y reporte de Lavado de Activos y Financiación del Terrorismo (LA/FT) en la DIAN.', 15.00)
         RETURNING id`,
        [analista3Id]
      );
      axisLaFtId = insRes.rows[0].id;
      console.log(`Eje 'ABC del LA - FT' creado con ID: ${axisLaFtId}`);
    } else {
      axisLaFtId = axisLaFtRes.rows[0].id;
      await db.query(
        "UPDATE thematic_axes SET name = 'ABC del LA - FT' WHERE id = $1",
        [axisLaFtId]
      );
      console.log(`Eje 'ABC del LA - FT' actualizado con ID: ${axisLaFtId}`);
    }

    // Reasignar las 12 preguntas de LA/FT al eje específico 'ABC del LA - FT'
    await db.query(
      `UPDATE questions 
       SET thematic_axis_id = $1 
       WHERE job_profile_id = $2 
         AND (case_text LIKE '%transacciones financieras inusuales%' 
              OR case_text LIKE '%joyas de alto valor%' 
              OR case_text LIKE '%empresa de comercio exterior constituida hace dos meses%'
              OR case_text LIKE '%operaciones no habituales en efectivo%')`,
      [axisLaFtId, analista3Id]
    );

    // 4. Limpiar preguntas previas del tema 'Acciones de Control DIAN' si existieran
    await db.query(
      `DELETE FROM questions 
       WHERE job_profile_id = $1 
         AND (stem LIKE '%acción de control de tipo intensivo%' 
              OR stem LIKE '%insumo%para identificar%' 
              OR stem LIKE '%sentido más general y autónomo%' 
              OR stem LIKE '%registro de locales%' 
              OR stem LIKE '%verificación física, ¿qué aspecto es fundamental%' 
              OR stem LIKE '%destino final del resultado%' 
              OR stem LIKE '%información externa al establecimiento%' 
              OR stem LIKE '%lugar calificado como crítico%' 
              OR stem LIKE '%acta de visita si esta muestra indicios%' 
              OR stem LIKE '%Acta de Hechos para acción de control posterior%' 
              OR stem LIKE '%informe de cierre de caso%' 
              OR stem LIKE '%solicitud de investigación que se deriva de un acta%')`,
      [analista3Id]
    );

    // 5. Definición de las 12 preguntas oficiales
    const caso1 = 'Una dirección seccional de una entidad fiscalizadora está diseñando su plan operativo anual. Dentro del plan, se contemplan intervenciones a gran escala para verificar la facturación en un sector económico completo y, simultáneamente, se planean auditorías detalladas para un grupo selecto de empresas que presentan inconsistencias significativas en sus declaraciones. Como funcionario encargado de la planeación, debe clasificar correctamente cada tipo de intervención para asegurar que se sigan los procedimientos y se asignen los recursos adecuados, diferenciando claramente entre las acciones de alcance general y las focalizadas en casos específicos.';

    const caso2 = 'Un equipo de fiscalización ha sido asignado para realizar una visita de campo a una empresa de manufactura. La información preliminar sugiere que la compañía podría estar utilizando una infraestructura no declarada. Como líder del equipo, usted debe dirigir la verificación física del sitio, asegurando que se recopile toda la información pertinente sobre las instalaciones, operaciones y condiciones geográficas del lugar. El resultado de esta visita será crucial para determinar los siguientes pasos en la investigación, por lo que la rigurosidad y el apego al procedimiento son fundamentales.';

    const caso3 = 'Tras concluir una acción de control posterior, el equipo de análisis ha levantado un acta de hechos. En dicha acta se consignan circunstancias que evidencian el incumplimiento de una obligación de carácter aduanero por parte de una empresa importadora. Como responsable de decidir el curso de acción, debe asegurarse de que la documentación generada cumpla con todos los requisitos formales y se tramite correctamente para iniciar la siguiente fase del proceso, o en su defecto, para cerrar el caso si no se encontraran méritos suficientes.';

    const questionsData = [
      // ================= CASO 1 =================
      {
        case_text: caso1,
        stem: '1. ¿Cuál es la característica principal que define una acción de control de tipo intensivo para la entidad?',
        options: [
          {
            key: 'A',
            text: 'La acción es emprendida de manera autónoma, priorizando criterios de oportunidad y efectividad.',
            is_correct: false,
            justification: 'Incorrecta. Las acciones emprendidas de manera autónoma sin criterios sistemáticos corresponden a intervenciones de alcance general no focalizadas.'
          },
          {
            key: 'B',
            text: 'La acción busca cubrir masivamente diversos clientes o sectores económicos para su verificación.',
            is_correct: false,
            justification: 'Incorrecta. La cobertura masiva de diversos sectores económicos corresponde al control extensivo o masivo de la administración.'
          },
          {
            key: 'C',
            text: 'La acción se dirige de manera particular a un cliente definido para verificar sus obligaciones.',
            is_correct: true,
            justification: 'Correcta. Son acciones orientadas a verificar el cumplimiento de las obligaciones fiscales, cuya característica principal es que la acción se dirige de manera particular a un cliente definido, para aplicar las normas previstas.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Manual de Fiscalización y Control Tributario DIAN - Clasificación de Acciones de Control Intensivo vs. Masivo.',
        explanation_summary: 'Son acciones orientadas a verificar el cumplimiento de las obligaciones fiscales, cuya característica principal es que la acción se dirige de manera particular a un cliente definido, para aplicar las normas previstas.'
      },
      {
        case_text: caso1,
        stem: '2. ¿Qué elemento es considerado un "insumo" para identificar, iniciar o generar una acción de control?',
        options: [
          {
            key: 'A',
            text: 'Factores, elementos o información que sirven para este propósito en la gestión de fiscalización.',
            is_correct: true,
            justification: 'Correcta. El término se define como los factores, elementos o información que sirven para identificar, iniciar o generar una acción de control por parte de las áreas de fiscalización tributaria, aduanera y cambiaria.'
          },
          {
            key: 'B',
            text: 'El conteo de todos los elementos de una población en un lugar y tiempo determinados.',
            is_correct: false,
            justification: 'Incorrecta. El conteo de una población corresponde a una operación de censo o levantamiento censal estadístico.'
          },
          {
            key: 'C',
            text: 'La resolución motivada que ordena el registro de oficinas y establecimientos comerciales.',
            is_correct: false,
            justification: 'Incorrecta. La resolución motivada es un acto administrativo que faculta una diligencia probatoria, no el insumo originador.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Proceso de Gestión de Fiscalización DIAN - Definición de Insumos y Fuentes de Riesgo.',
        explanation_summary: 'El término se define como los factores, elementos o información que sirven para identificar, iniciar o generar una acción de control por parte de las áreas de fiscalización tributaria, aduanera y cambiaria.'
      },
      {
        case_text: caso1,
        stem: '3. ¿Cómo se define una acción de control en su sentido más general y autónomo?',
        options: [
          {
            key: 'A',
            text: 'Acciones orientadas a prevenir incumplimientos, cubriendo masivamente diversos clientes y sectores económicos.',
            is_correct: false,
            justification: 'Incorrecta. Esta definición corresponde a las campañas de prevención y control masivo o persuasivo.'
          },
          {
            key: 'B',
            text: 'Acciones orientadas a verificar la correcta aplicación de normas en un caso ya seleccionado.',
            is_correct: false,
            justification: 'Incorrecta. Corresponde a la etapa de investigación o auditoría específica reglada en un programa previamente focalizado.'
          },
          {
            key: 'C',
            text: 'Acciones de fiscalización emprendidas autónomamente, donde no se aplican criterios sistemáticos de selección.',
            is_correct: true,
            justification: 'Correcta. Son acciones de fiscalización emprendidas de manera autónoma por la Dirección de Gestión de Fiscalización o sus subdirecciones, en las cuales no necesariamente se aplican criterios sistemáticos para establecer un grupo.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Dirección de Gestión de Fiscalización DIAN - Criterios de Selección y Ejecución Autónoma.',
        explanation_summary: 'Son acciones de fiscalización emprendidas de manera autónoma por la Dirección de Gestión de Fiscalización o sus subdirecciones, en las cuales no necesariamente se aplican criterios sistemáticos para establecer un grupo.'
      },
      {
        case_text: caso1,
        stem: '4. ¿Cuál es la facultad específica de la entidad para ordenar el registro de locales de un contribuyente?',
        options: [
          {
            key: 'A',
            text: 'Acceder a la casa de habitación de una persona natural para revisar documentos.',
            is_correct: false,
            justification: 'Incorrecta. La ley prohíbe taxativamente a la entidad ordenar por resolución administrativa el allanamiento de la casa de habitación, requiriéndose orden judicial penal.'
          },
          {
            key: 'B',
            text: 'Ordenar el registro de oficinas, establecimientos comerciales, industriales o de servicios mediante resolución.',
            is_correct: true,
            justification: 'Correcta. Es la facultad de la entidad para ordenar, mediante resolución motivada, el registro de oficinas, establecimientos comerciales, industriales o de servicios, y demás locales del contribuyente, excluyendo la casa de habitación.'
          },
          {
            key: 'C',
            text: 'Realizar un conteo de todos los elementos en los locales de un contribuyente.',
            is_correct: false,
            justification: 'Incorrecta. La toma o conteo físico de inventarios es una diligencia probatoria de inspección, no la definición de la facultad de orden de registro.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Artículo 779-1 del Estatuto Tributario Nacional - Facultades de Registro.',
        explanation_summary: 'Es la facultad de la entidad para ordenar, mediante resolución motivada, el registro de oficinas, establecimientos comerciales, industriales o de servicios, y demás locales del contribuyente, excluyendo la casa de habitación.'
      },

      // ================= CASO 2 =================
      {
        case_text: caso2,
        stem: '5. Al ejecutar una verificación física, ¿qué aspecto es fundamental para la inspección ocular del establecimiento?',
        options: [
          {
            key: 'A',
            text: 'Se debe analizar la estructura de costos y los márgenes de utilidad de la empresa.',
            is_correct: false,
            justification: 'Incorrecta. El análisis de márgenes y rentabilidad corresponde a la auditoría contable/financiera de gabinete o inspección tributaria.'
          },
          {
            key: 'B',
            text: 'Se debe solicitar el perfilamiento de los empleados y su historial laboral en la empresa.',
            is_correct: false,
            justification: 'Incorrecta. El historial laboral del personal no es el objeto de la verificación física de infraestructura no declarada.'
          },
          {
            key: 'C',
            text: 'Se debe verificar las instalaciones, equipos, accesos, flujo de personas y actividad económica.',
            is_correct: true,
            justification: 'Correcta. Se realiza la verificación ocular del establecimiento en cuanto a las instalaciones, equipos, accesos, flujo de personas, tamaño y actividad económica, entre otros aspectos operativos y físicos del lugar inspeccionado.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Procedimiento de Verificación Física y Visitas de Campo DIAN.',
        explanation_summary: 'Se realiza la verificación ocular del establecimiento en cuanto a las instalaciones, equipos, accesos, flujo de personas, tamaño y actividad económica, entre otros aspectos operativos y físicos del lugar inspeccionado.'
      },
      {
        case_text: caso2,
        stem: '6. ¿Cuál es el destino final del resultado obtenido tras la ejecución de la verificación física?',
        options: [
          {
            key: 'A',
            text: 'Debe ser utilizado para sancionar de forma inmediata al contribuyente en el mismo sitio.',
            is_correct: false,
            justification: 'Incorrecta. La sanción inmediata vulnera el debido proceso (art. 29 C.P.), el cual exige pliego de cargos y oportunidad de descargos.'
          },
          {
            key: 'B',
            text: 'Debe ser publicado inmediatamente para conocimiento de otras entidades de control del Estado.',
            is_correct: false,
            justification: 'Incorrecta. La información recopilada está amparada por la reserva tributaria y debe incorporarse al expediente respectivo.'
          },
          {
            key: 'C',
            text: 'Debe ser incorporado a los documentos relacionados con el análisis de la acción de control.',
            is_correct: true,
            justification: 'Correcta. El resultado de esta verificación se debe incorporar a los documentos relacionados con el análisis, ya que identifica los riesgos que puedan derivarse en el ejercicio de la acción de control.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Manual de Procedimientos de Fiscalización DIAN - Tratamiento de la Evidencia Recopilada.',
        explanation_summary: 'El resultado de esta verificación se debe incorporar a los documentos relacionados con el análisis, ya que identifica los riesgos que puedan derivarse en el ejercicio de la acción de control.'
      },
      {
        case_text: caso2,
        stem: '7. ¿Qué información externa al establecimiento se debe documentar durante la verificación física?',
        options: [
          {
            key: 'A',
            text: 'El nivel de seguridad de los establecimientos comerciales que se encuentran en la misma zona.',
            is_correct: false,
            justification: 'Incorrecta. El nivel de seguridad de locales aledaños no forma parte de la descripción del inmueble objeto de la acción.'
          },
          {
            key: 'B',
            text: 'Las rutas viales y peatonales de acceso y salida, junto a las condiciones geográficas.',
            is_correct: true,
            justification: 'Correcta. La verificación incluye el croquis y plano de acceso al lugar, las rutas viales y peatonales de acceso y salida, y las condiciones geográficas y topográficas, como parte integral de la inspección.'
          },
          {
            key: 'C',
            text: 'Los planes de ordenamiento territorial vigentes para el sector donde opera la empresa.',
            is_correct: false,
            justification: 'Incorrecta. Los planes de ordenamiento territorial corresponden al ámbito urbanístico municipal, no a la documentación externa de la inspección de campo.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Guía Metodológica para Diligencias de Campo e Inspección Ocular DIAN.',
        explanation_summary: 'La verificación incluye el croquis y plano de acceso al lugar, las rutas viales y peatonales de acceso y salida, y las condiciones geográficas y topográficas, como parte integral de la inspección.'
      },
      {
        case_text: caso2,
        stem: '8. En caso de realizar la acción de control en un lugar calificado como crítico, ¿qué apoyo se podría requerir?',
        options: [
          {
            key: 'A',
            text: 'El acompañamiento de auditores privados externos para garantizar la total transparencia del proceso.',
            is_correct: false,
            justification: 'Incorrecta. La función fiscalizadora es de orden público indelegable y no se delega en auditores privados.'
          },
          {
            key: 'B',
            text: 'La presencia de representantes de los gremios económicos del sector para actuar como veedores.',
            is_correct: false,
            justification: 'Incorrecta. Los gremios económicos no tienen facultades de veeduría procesal durante las diligencias de registro y control.'
          },
          {
            key: 'C',
            text: 'El acompañamiento de la Policía Fiscal y Aduanera o del Escuadrón Anti Disturbios.',
            is_correct: true,
            justification: 'Correcta. Si las acciones se realizan en centros comerciales o lugares críticos en materia de orden público, se requerirá el acompañamiento de la Policía Fiscal y Aduanera o del Escuadrón Anti Disturbios ESMAD.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Protocolo de Seguridad para Diligencias de Control en Zonas Críticas - POLFA / DIAN.',
        explanation_summary: 'Si las acciones se realizan en centros comerciales o lugares críticos en materia de orden público, se requerirá el acompañamiento de la Policía Fiscal y Aduanera o del Escuadrón Anti Disturbios ESMAD.'
      },

      // ================= CASO 3 =================
      {
        case_text: caso3,
        stem: '9. ¿Cuál es el procedimiento a seguir con el acta de visita si esta muestra indicios de incumplimiento?',
        options: [
          {
            key: 'A',
            text: 'Notificarla al contribuyente y otorgar un plazo para que presente sus descargos iniciales.',
            is_correct: false,
            justification: 'Incorrecta. El acta de visita consigna hechos constatados, no es el acto de apertura ni el pliego de cargos que otorga formalmente el traslado para descargos.'
          },
          {
            key: 'B',
            text: 'Archivarla como antecedente para futuras acciones de control sin iniciar una investigación formal.',
            is_correct: false,
            justification: 'Incorrecta. Si se detectan indicios claros de incumplimiento, existe el deber funcional de tramitar el inicio de la investigación.'
          },
          {
            key: 'C',
            text: 'Trasladarla a la división competente para que se incorpore a una investigación o se inicie una.',
            is_correct: true,
            justification: 'Correcta. El acta de visita o de hechos se debe trasladar a la División de Fiscalización y Liquidación competente, para que se incorpore a una investigación en curso o se dé apertura a otra.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Decreto 1165 de 2019 / Procedimiento de Fiscalización Aduanera y Tributaria DIAN.',
        explanation_summary: 'El acta de visita o de hechos se debe trasladar a la División de Fiscalización y Liquidación competente, para que se incorpore a una investigación en curso o se dé apertura a otra.'
      },
      {
        case_text: caso3,
        stem: '10. ¿Qué requisito fundamental debe cumplir el "Acta de Hechos para acción de control posterior" como producto final?',
        options: [
          {
            key: 'A',
            text: 'Contener la liquidación oficial de la sanción propuesta y los intereses moratorios calculados.',
            is_correct: false,
            justification: 'Incorrecta. El acta de hechos no liquida sanciones definitivas ni liquida intereses; estos corresponden a la liquidación oficial o resolución sancionatoria.'
          },
          {
            key: 'B',
            text: 'Estar diligenciada en su totalidad, sin tachaduras o enmendaduras, y con descripción de hechos.',
            is_correct: true,
            justification: 'Correcta. Como salida del proceso, este formato debe estar diligenciado en su totalidad, sin tachaduras o enmendaduras, y contener los soportes y la descripción de los hechos que fundamentan la acción.'
          },
          {
            key: 'C',
            text: 'Incluir la firma de un apoderado legal del contribuyente como constancia de aceptación.',
            is_correct: false,
            justification: 'Incorrecta. No requiere la firma de un apoderado ni aceptación de responsabilidad para tener plena validez probatoria.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Normas Técnicas y Formales para el Levantamiento de Actas Administrativas DIAN.',
        explanation_summary: 'Como salida del proceso, este formato debe estar diligenciado en su totalidad, sin tachaduras o enmendaduras, y contener los soportes y la descripción de los hechos que fundamentan la acción.'
      },
      {
        case_text: caso3,
        stem: '11. ¿En qué escenario se debe proceder a la elaboración de un informe de cierre de caso?',
        options: [
          {
            key: 'A',
            text: 'Cuando el contribuyente subsana voluntariamente la irregularidad detectada durante la visita.',
            is_correct: false,
            justification: 'Incorrecta. Si el contribuyente subsana, se deja constancia y se verifica si subsiste sanción reducida o pago de intereses, sin eximir de trámite formal.'
          },
          {
            key: 'B',
            text: 'Cuando, realizada la acción de control, no se evidencia incumplimiento de las obligaciones.',
            is_correct: true,
            justification: 'Correcta. Se realiza un informe de cierre del caso cuando se decide no realizar la acción de control o cuando, una vez realizada, no se evidencia incumplimiento de las obligaciones, finalizando el procedimiento.'
          },
          {
            key: 'C',
            text: 'Cuando el acta de hechos es devuelta por la división de fiscalización por errores de forma.',
            is_correct: false,
            justification: 'Incorrecta. Si el acta contiene inconsistencias de forma, se procede a su subsanación o aclaración interna, no al cierre del caso.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Manual del Proceso de Fiscalización - Cierre y Archivo de Diligencias de Control.',
        explanation_summary: 'Se realiza un informe de cierre del caso cuando se decide no realizar la acción de control o cuando, una vez realizada, no se evidencia incumplimiento de las obligaciones, finalizando el procedimiento.'
      },
      {
        case_text: caso3,
        stem: '12. ¿Qué información es esencial en el formato de solicitud de investigación que se deriva de un acta de hechos?',
        options: [
          {
            key: 'A',
            text: 'Un análisis comparativo del comportamiento del contribuyente frente a su sector económico.',
            is_correct: false,
            justification: 'Incorrecta. El benchmarking sectorial corresponde a estudios económicos de riesgos, no a los requisitos formales de la solicitud individual de investigación.'
          },
          {
            key: 'B',
            text: 'La proyección del tiempo estimado para la investigación y los recursos humanos requeridos.',
            is_correct: false,
            justification: 'Incorrecta. La asignación de recursos y tiempos es potestad del jefe de división al momento del reparto interno.'
          },
          {
            key: 'C',
            text: 'Los soportes que acrediten la causal, la identificación del investigado y descripción de hechos.',
            is_correct: true,
            justification: 'Correcta. El formato de solicitud de investigación debe contener los soportes que acrediten y evidencien la causal, incluyendo la identificación del investigado, los soportes y la descripción de los hechos.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Formato y Procedimiento Institucional de Solicitud de Investigación DIAN.',
        explanation_summary: 'El formato de solicitud de investigación debe contener los soportes que acrediten y evidencien la causal, incluyendo la identificación del investigado, los soportes y la descripción de los hechos.'
      }
    ];

    // 6. Insertar las 12 preguntas
    for (const q of questionsData) {
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
        ) VALUES ($1, $2, 'situacional', 'medio', $3, $4, $5, $6, $7, $8, TRUE)`,
        [
          analista3Id,
          axisControlId,
          q.case_text,
          q.stem,
          JSON.stringify(q.options),
          q.correct_option,
          q.legal_basis,
          q.explanation_summary
        ]
      );
    }

    console.log(`✅ ¡Se insertaron con éxito las 12 preguntas de 'Acciones de Control DIAN'!`);

    // 7. Mostrar resumen actualizado de ejes y preguntas en DB
    const countRes = await db.query(
      `SELECT ta.name, COUNT(q.id) as total_questions
       FROM thematic_axes ta
       LEFT JOIN questions q ON q.thematic_axis_id = ta.id AND q.is_active = TRUE
       WHERE ta.job_profile_id = $1
       GROUP BY ta.id, ta.name
       ORDER BY ta.id ASC`,
      [analista3Id]
    );

    console.log('\n--- Estado Actual de Ejes Temáticos y Preguntas ---');
    console.table(countRes.rows);

    const totalRes = await db.query(
      "SELECT COUNT(*) FROM questions WHERE job_profile_id = $1 AND is_active = TRUE",
      [analista3Id]
    );
    console.log(`Total general de preguntas activas para Analista III: ${totalRes.rows[0].count}`);

  } catch (error) {
    console.error('Error durante la siembra de preguntas de Acciones de Control DIAN:', error);
  } finally {
    process.exit(0);
  }
}

seedAccionesControl();
