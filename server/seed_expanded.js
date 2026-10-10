const db = require('./db');

async function seedMoreQuestions() {
  console.log('--- Agregando más preguntas especializadas para Analista III ---');

  try {
    const jobRes = await db.query("SELECT id FROM job_profiles WHERE code = 'AT-FL-2011'");
    if (jobRes.rows.length === 0) {
      console.error('Perfil Analista III no encontrado');
      return;
    }
    const analista3Id = jobRes.rows[0].id;

    // Obtener mapas de ejes
    const axesRes = await db.query('SELECT id, name FROM thematic_axes WHERE job_profile_id = $1', [analista3Id]);
    const axisMap = {};
    axesRes.rows.forEach(r => { axisMap[r.name] = r.id; });

    const extraQuestions = [
      // 1. Fiscalización Tributaria - Visitas y Facultades Art. 684
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En desarrollo de un programa de fiscalización para verificar la correcta facturación electrónica en establecimientos de comercio, una comisión de la DIAN se presenta en un almacén. El propietario se niega a suministrar el computador donde se encuentra el sistema de facturación argumentando que contiene fotos familiares.',
        stem: 'Conforme a las amplias facultades de fiscalización e investigación tributaria del artículo 684 del Estatuto Tributario, la comisión de fiscalización:',
        options: [
          {
            key: 'A',
            text: 'puede ordenar la exhibición de los equipos y archivos magnéticos que soporten la facturación y la contabilidad, limitando la inspección técnica estrictamente a los archivos de contenido contable y fiscal.',
            is_correct: true,
            justification: 'Es correcta. El artículo 684 del E.T. faculta expresamente a la administración para ordenar la exhibición y examen parcial de los libros, comprobantes y documentos, incluidos archivos electrónicos y magnéticos que soporten las operaciones gravadas, respetando los datos estrictamente personales ajenos al objeto tributario.'
          },
          {
            key: 'B',
            text: 'debe retirarse inmediatamente sin levantar ninguna constancia puesto que la presencia de fotos familiares invalida la facultad legal de control.',
            is_correct: false,
            justification: 'Es incorrecta. Los servidores no pueden abdicar de sus facultades legales; la presencia de información no comercial no anula la obligación legal del contribuyente de exhibir los soportes fiscales de su actividad mercantil.'
          },
          {
            key: 'C',
            text: 'puede incautar definitivamente todos los computadores y llevarlos a subasta pública en la misma tarde.',
            is_correct: false,
            justification: 'Es incorrecta. La administración tributaria no puede decomisar de plano ni subastar equipos de cómputo en una visita de control tributario ordinaria sin un debido proceso sancionatorio.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Art. 684, literal c); Sentencia C-505 de 1999 Corte Constitucional.',
        explanation_summary: 'La DIAN puede examinar sistemas y archivos magnéticos soporte de la facturación, garantizando la reserva de datos no fiscales.'
      },

      // 2. Liquidación Oficial de Revisión - Término Art. 710
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'El contribuyente respondió oportunamente el Requerimiento Especial dentro de los tres meses fijados en el artículo 707 del E.T. El expediente regresa al área de fiscalización y liquidación para resolver y proyectar la Liquidación Oficial de Revisión. El superior solicita al Analista III calcular el término legal máximo con el que cuenta la DIAN para notificar dicha liquidación oficial.',
        stem: 'De conformidad con el artículo 710 del Estatuto Tributario, la Liquidación Oficial de Revisión debe notificarse a más tardar dentro de los:',
        options: [
          {
            key: 'A',
            text: 'seis (6) meses siguientes a la fecha de vencimiento del término para dar respuesta al Requerimiento Especial.',
            is_correct: true,
            justification: 'Es correcta. El artículo 710 del Estatuto Tributario fija de manera perentoria que dentro de los seis (6) meses siguientes a la fecha de vencimiento del término para dar respuesta al Requerimiento Especial, la administración deberá practicar y notificar la liquidación oficial de revisión.'
          },
          {
            key: 'B',
            text: 'dos (2) años contados desde la presentación de la declaración de renta.',
            is_correct: false,
            justification: 'Es incorrecta. El término de dos (o tres) años corresponde a la oportunidad inicial para notificar el Requerimiento Especial (Art. 705 E.T.), no para proferir la Liquidación Oficial de Revisión tras la respuesta.'
          },
          {
            key: 'C',
            text: 'treinta (30) días calendario improrrogables.',
            is_correct: false,
            justification: 'Es incorrecta. Treinta días es un término excesivamente breve no contemplado en el E.T. para la complejidad probatoria de la liquidación oficial de revisión.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Art. 710.',
        explanation_summary: 'La DIAN tiene 6 meses contados a partir del vencimiento del término de respuesta al Requerimiento Especial para notificar la Liquidación Oficial de Revisión.'
      },

      // 3. Inspección Contable - Notificación Art. 782
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En una investigación de fiscalización a un gran contribuyente, la DIAN decreta formalmente una Inspección Contable mediante auto de trámite para verificar cruces de retención en la fuente y pasivos fiscales.',
        stem: 'De acuerdo con el artículo 779-1 y 706 del Estatuto Tributario, la notificación del auto que ordena la inspección tributaria o contable produce el siguiente efecto procesal:',
        options: [
          {
            key: 'A',
            text: 'suspende el término para notificar el Requerimiento Especial por el término de tres (3) meses contados a partir de su notificación.',
            is_correct: true,
            justification: 'Es correcta. El artículo 706 del Estatuto Tributario señala que el término para notificar el requerimiento especial se suspenderá cuando se practique inspección tributaria, por el término de tres meses contados a partir de la notificación del auto que la decrete.'
          },
          {
            key: 'B',
            text: 'declara la prescripción inmediata de todas las deudas tributarias del contribuyente.',
            is_correct: false,
            justification: 'Es incorrecta. La inspección es un medio de prueba de la administración para fiscalizar, en ningún caso extingue ni prescribe la deuda tributaria.'
          },
          {
            key: 'C',
            text: 'impide al contribuyente volver a presentar declaraciones en los próximos diez años.',
            is_correct: false,
            justification: 'Es incorrecta. La práctica de inspección no anula el deber formal ni el derecho del contribuyente a continuar declarando sus tributos.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Arts. 706 y 779.',
        explanation_summary: 'El auto que ordena la inspección tributaria suspende por 3 meses el término para formular el requerimiento especial.'
      },

      // 4. Fiscalización Aduanera - Allanamiento y Reducción Sancionatoria
      {
        thematic_axis: 'Fiscalización Aduanera y Destrucción de Mercancías',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'A un declarante de aduanas se le notifica un Requerimiento Especial Aduanero en el que se le propone una sanción por haber incurrido en error en la subpartida arancelaria que conllevó a un menor pago de tributos aduaneros. El usuario decide allanarse a los cargos antes de que se expida la Resolución Sancionatoria.',
        stem: 'Conforme al régimen sancionatorio aduanero vigente (Decreto 920 de 2023), el allanamiento voluntario oportuno a los cargos permite:',
        options: [
          {
            key: 'A',
            text: 'reducir la sanción económica propuesta en los porcentajes legalmente establecidos en la norma, siempre que se cancelen los mayores tributos aduaneros y la sanción reducida.',
            is_correct: true,
            justification: 'Es correcta. El Decreto 920 de 2023 prevé la figura del allanamiento a las infracciones aduaneras con reducción porcentual de la sanción (generalmente al 20% o 50% según la etapa procesal), incentivando el pago voluntario de los tributos aduaneros debidos.'
          },
          {
            key: 'B',
            text: 'cancelar la licencia aduanera del usuario por el resto de su vida comercial.',
            is_correct: false,
            justification: 'Es incorrecta. El allanamiento es una causal de atenuación y reducción sancionatoria, no de cancelación o agravación de sanciones.'
          },
          {
            key: 'C',
            text: 'reclamar una indemnización económica a la DIAN por haberlo investigado.',
            is_correct: false,
            justification: 'Es incorrecta. Reconocer una infracción no genera derecho a reclamar indemnizaciones contra el Estado.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto 920 de 2023, Artículos sobre Allanamiento y Graduación Sancionatoria.',
        explanation_summary: 'El allanamiento en materia aduanera otorga el beneficio de reducción pecuniaria de la sanción al subsanar la infracción y pagar los tributos adeudados.'
      },

      // 5. Destrucción de Mercancías - Competencia y Presupuestos
      {
        thematic_axis: 'Fiscalización Aduanera y Destrucción de Mercancías',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En una diligencia aduanera programada de destrucción de mercancías decomisadas consistentes en cigarrillos adulterados de contrabando, un auxiliar operativo sugiere al Analista III que parte de los cigarrillos se regale a recicladores locales.',
        stem: 'Frente a esta sugerencia, la conducta estricta que debe adoptar el Analista III de acuerdo con el régimen de disposición de mercancías decomisadas es:',
        options: [
          {
            key: 'A',
            text: 'rechazar rotundamente la propuesta y asegurar la destrucción material completa e inmediata de todos los productos adulterados, dado que representan riesgo contra la salud pública y su destinación legal obligatoria es la destrucción total sin aprovechamiento comercial.',
            is_correct: true,
            justification: 'Es correcta. Los artículos 718 y subsiguientes del Decreto 1165 de 2019 y la normatividad sanitaria prohíben expresamente la comercialización, donación o entrega de mercancías que atenten contra la salubridad pública (como tabaco adulterado o licor no apto). Su único destino admisible es la destrucción total con acta oficial.'
          },
          {
            key: 'B',
            text: 'aceptar la propuesta si los recicladores firman una carta informal en servilleta.',
            is_correct: false,
            justification: 'Es incorrecta. El servidor público incurriría en el delito de peculado por apropiación o facilitación de contrabando y falta gravísima disciplinaria.'
          },
          {
            key: 'C',
            text: 'dejar las cajas de cigarrillos abandonadas en la calle para que cualquier transeúnte las tome.',
            is_correct: false,
            justification: 'Es incorrecta. Constituye una grave negligencia funcional en la custodia y disposición de bienes bajo custodia de la autoridad aduanera.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto 1165 de 2019, Modalidades de Disposición de Mercancías Decomisadas; Código Penal Art. 397.',
        explanation_summary: 'Los productos que atenten contra la salubridad pública decomisados por la DIAN deben ser destruidos en su totalidad sin excepción alguna.'
      },

      // 6. Régimen Cambiario - Prescripción de la Acción Sancionatoria
      {
        thematic_axis: 'Régimen Cambiario y Detección de Lavado de Activos (UIAF / ROS)',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'El área de fiscalización cambiaria revisa una denuncia por presunta indebida canalización de divisas por importaciones de maquinaria ocurrida en enero de 2018. El Analista III debe verificar si la DIAN aún se encuentra dentro del término legal para formular pliego de cargos.',
        stem: 'De conformidad con el artículo 5 del Decreto Ley 2245 de 2011, la acción sancionatoria de la DIAN por infracciones al régimen de cambios internacionales caduca en el término de:',
        options: [
          {
            key: 'A',
            text: 'cinco (5) años contados a partir de la ocurrencia de los hechos constitutivos de la infracción cambiaria.',
            is_correct: true,
            justification: 'Es correcta. El artículo 5 del Decreto Ley 2245 de 2011 consagra que la acción administrativa sancionatoria cambiaria caduca en el término de cinco (5) años contados a partir de la fecha en que ocurrieron los hechos constitutivos de la infracción.'
          },
          {
            key: 'B',
            text: 'veinte (20) años continuos de manera imprescriptible.',
            is_correct: false,
            justification: 'Es incorrecta. En el derecho administrativo sancionador colombiano las faltas no son imprescriptibles; están sujetas a plazos perentorios de caducidad fijados por ley.'
          },
          {
            key: 'C',
            text: 'seis (6) meses únicamente.',
            is_correct: false,
            justification: 'Es incorrecta. Seis meses es el término de algunas actuaciones preliminares, no la caducidad general de la potestad sancionatoria cambiaria de la DIAN.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto Ley 2245 de 2011, Art. 5 (Caducidad de la acción sancionatoria cambiaria).',
        explanation_summary: 'La acción sancionatoria cambiaria de la DIAN caduca a los cinco (5) años contados desde la ocurrencia de la infracción.'
      },

      // 7. CPACA - Silencio Administrativo Negativo
      {
        thematic_axis: 'Procedimiento Administrativo General (CPACA Ley 1437 de 2011)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Un contribuyente interpuso un recurso de reconsideración contra una sanción tributaria y transcurrió el término legal de un (1) año previsto en el artículo 732 del Estatuto Tributario sin que la DIAN haya notificado la resolución que resuelve el recurso.',
        stem: 'En el procedimiento tributario (Art. 734 E.T.), si no se resuelve el recurso de reconsideración dentro del término legal de un año, opera:',
        options: [
          {
            key: 'A',
            text: 'el Silencio Administrativo Positivo a favor del contribuyente, entendiéndose fallado el recurso a su favor, debiendo la administración protocolizarlo u ordenar su reconocimiento oficial.',
            is_correct: true,
            justification: 'Es correcta. El artículo 734 del Estatuto Tributario establece expresamente una excepción a la regla general administrativa: si transcurrido el año para resolver el recurso de reconsideración no se ha notificado la decisión, operará el silencio administrativo positivo y se entenderá fallado a favor del recurrente.'
          },
          {
            key: 'B',
            text: 'el Silencio Administrativo Negativo, ratificando automáticamente la sanción en contra del contribuyente.',
            is_correct: false,
            justification: 'Es incorrecta. Aunque en el CPACA general la regla supletoria es el silencio negativo (Art. 83 CPACA), en materia de recurso de reconsideración tributaria el Art. 734 del E.T. consagra de forma expresa y garantista el silencio positivo.'
          },
          {
            key: 'C',
            text: 'la anulación de la cédula de ciudadanía del funcionario sustanciador.',
            is_correct: false,
            justification: 'Es incorrecta. Las consecuencias del silencio administrativo operan sobre la relación jurídica del acto tributario, no sobre la personalidad jurídica del servidor.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Arts. 732 y 734.',
        explanation_summary: 'En el recurso de reconsideración tributario, la omisión de decisión en el plazo legal de un año genera silencio administrativo positivo a favor del contribuyente.'
      },

      // 8. Evasión vs Contrabando
      {
        thematic_axis: 'Conceptos de Evasión, Elusión, Contrabando y Ley de Transparencia (Ley 1712)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Una investigación revela que una empresa ingresó al país tres contenedores con teléfonos móviles declarándolos ante la aduana como "cables de caucho genéricos" para pagar un arancel e IVA notablemente inferior al correspondiente a los teléfonos.',
        stem: 'Técnicamente, ingresar mercancías al territorio nacional bajo una descripción arancelaria deliberadamente engañosa para liquidar menos impuestos corresponde a la figura de:',
        options: [
          {
            key: 'A',
            text: 'Contrabando Técnico (infracción y delito tipificado en el Código Penal y legislación aduanera por engaño en valor, cantidad o clasificación arancelaria).',
            is_correct: true,
            justification: 'Es correcta. El contrabando técnico se presenta cuando las mercancías ingresan a través de los canales aduaneros habilitados pero con documentos alterados, falsos o con descripción fraudulenta de precio, cantidad o clasificación para tributar menos de lo debido, a diferencia del contrabando abierto que elude completamente el control aduanero.'
          },
          {
            key: 'B',
            text: 'Economía de opción legítima respaldada por los tratados de libre comercio.',
            is_correct: false,
            justification: 'Es incorrecta. Falsear la naturaleza arancelaria de un producto es un fraude y delito aduanero, nunca una economía de opción legítima.'
          },
          {
            key: 'C',
            text: 'Error involuntario de digitación que no genera responsabilidad alguna.',
            is_correct: false,
            justification: 'Es incorrecta. El cambio intencional de la naturaleza de la mercancía con ánimo de evadir tributos aduaneros constituye dolo e infracción gravísima.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Código Penal Colombiano, Art. 319-1 (Contrabando Técnico); Decreto 1165 de 2019.',
        explanation_summary: 'El contrabando técnico consiste en someter mercancías al trámite aduanero valiéndose de descripciones, valores o clasificaciones fraudulentas.'
      },

      // 9. Gestión Documental - Cadena de Custodia
      {
        thematic_axis: 'Gestión Documental y Modelo MIPG',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Durante un allanamiento tributario con acompañamiento judicial, se recopilan libros contables dobles y memorias USB con registros de facturación no declarada. Como Analista III asignado a apoyar la práctica de pruebas (Función 5 del MERF), se le entrega el material para incorporarlo al expediente.',
        stem: 'Para garantizar la validez probatoria de estos elementos materiales probatorios ante eventuales demandas contenciosas o penales, el servidor debe asegurar:',
        options: [
          {
            key: 'A',
            text: 'la estricta cadena de custodia documental, levantando el registro de inventario detallado, rotulado de evidencias, firmas de entrega y recepción, y almacenamiento seguro en custodia oficial.',
            is_correct: true,
            justification: 'Es correcta. La validez de las pruebas recaudadas en investigaciones de fiscalización depende de la inalterabilidad y autenticidad del material probatorio (cadena de custodia, Art. 742 E.T. y CPACA). Cualquier manipulación o falta de registro invalida la prueba en juicio.'
          },
          {
            key: 'B',
            text: 'copiar los archivos a un blog público en internet para que cualquiera pueda opinar.',
            is_correct: false,
            justification: 'Es incorrecta. Viola la reserva tributaria legal del Art. 583 E.T. y destruye la cadena de custodia probatoria.'
          },
          {
            key: 'C',
            text: 'borrar la mitad de los archivos para que el expediente ocupe menos espacio en el archivo.',
            is_correct: false,
            justification: 'Es incorrecta. Destruir u ocultar pruebas públicas es un delito penal de destrucción de documento público y falta disciplinaria gravísima.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario, Arts. 742, 743; Ley 594 de 2000; Código General del Proceso Arts. 243 y subsiguientes.',
        explanation_summary: 'La cadena de custodia y el inventario formal de evidencias salvaguardan la autenticidad e idoneidad de la prueba tributaria.'
      },

      // 10. Código de Integridad DIAN - Justicia
      {
        thematic_axis: 'Código de Integridad y Ética Pública DIAN',
        question_type: 'situacional',
        difficulty: 'básico',
        case_text: 'Un contribuyente sancionado por la DIAN alega que el funcionario que lo auditó le aplicó un criterio mucho más estricto que al vecino de enfrente, por rencillas personales del pasado en el barrio.',
        stem: 'A la luz del valor de la JUSTICIA en el Código de Integridad de la DIAN y el artículo 209 de la Constitución Política, las decisiones del servidor público deben basarse exclusivamente en:',
        options: [
          {
            key: 'A',
            text: 'la estricta aplicación imparcial de la ley, la igualdad de trato y la valoración objetiva de las pruebas, desprovistas de cualquier sesgo, simpatía o animadversión personal.',
            is_correct: true,
            justification: 'Es correcta. El valor de la Justicia en el Código de Integridad de la DIAN prescribe: "Actúo con imparcialidad garantizando los derechos de las personas, con una atención equitativa y sin discriminación". La Constitución (Art. 209) exige igualdad e imparcialidad en la función pública.'
          },
          {
            key: 'B',
            text: 'el afecto personal y los intereses económicos de sus allegados.',
            is_correct: false,
            justification: 'Es incorrecta. La actuación basada en intereses personales o animadversión destruye la moralidad y la imparcialidad del Estado.'
          },
          {
            key: 'C',
            text: 'el número de seguidores que tenga el contribuyente en redes sociales.',
            is_correct: false,
            justification: 'Es incorrecta. Las redes sociales no son criterio de legalidad ni de graduación sancionatoria en el derecho público.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Constitución Política de Colombia Art. 209; Código de Integridad de la DIAN.',
        explanation_summary: 'El valor de la justicia impone decisiones imparciales, objetivas e iguales para todos los ciudadanos sin sesgos subjetivos.'
      }
    ];

    for (const q of extraQuestions) {
      const axisId = axisMap[q.thematic_axis];
      await db.query(
        `INSERT INTO questions (
          job_profile_id, thematic_axis_id, question_type, difficulty,
          case_text, stem, options, correct_option, legal_basis, explanation_summary
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)`,
        [
          analista3Id,
          axisId || null,
          q.question_type,
          q.difficulty,
          q.case_text,
          q.stem,
          JSON.stringify(q.options),
          q.correct_option,
          q.legal_basis,
          q.explanation_summary
        ]
      );
    }

    const totalCount = await db.query('SELECT COUNT(*) as total FROM questions WHERE job_profile_id = $1', [analista3Id]);
    console.log(`--- Preguntas adicionales agregadas. Total actual en PostgreSQL: ${totalCount.rows[0].total} ---`);

  } catch (err) {
    console.error('Error agregando preguntas:', err);
  } finally {
    process.exit(0);
  }
}

seedMoreQuestions();
