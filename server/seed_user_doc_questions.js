const db = require('./db');

async function updateDocQuestionsWithOfficialFeedback() {
  console.log('--- Actualizando preguntas de LA/FT con los comentarios y justificaciones oficiales de Misión Mérito ---');

  try {
    const jobRes = await db.query("SELECT id FROM job_profiles WHERE code = 'AT-FL-2011'");
    if (jobRes.rows.length === 0) return;
    const analista3Id = jobRes.rows[0].id;

    // Buscar o verificar el eje temático
    let axisRes = await db.query(
      "SELECT id FROM thematic_axes WHERE job_profile_id = $1 AND name LIKE '%Lavado de Activos%'",
      [analista3Id]
    );
    const axisId = axisRes.rows[0]?.id;

    // Eliminar las 12 preguntas anteriores de este lote para reinsertar con la redacción y comentarios oficiales exactos
    await db.query(
      `DELETE FROM questions WHERE job_profile_id = $1 AND stem LIKE '%. %' AND stem LIKE '%funcionario%' AND case_text LIKE '%entidad pública%' OR case_text LIKE '%bienes de alto valor%' OR case_text LIKE '%corporación del sector real%' OR case_text LIKE '%agremiaciones económicas%'`,
      [analista3Id]
    );

    const docQuestions = [
      // ================= CASO 1 =================
      {
        case_text: 'Un funcionario que ejerce labores de inspección y vigilancia en una entidad pública recibe un informe sobre una empresa del sector real que ha comenzado a realizar transacciones financieras inusuales. El documento detalla que los recursos de la empresa son depositados en cuentas bancarias para luego ser transferidos rápidamente a otras instituciones financieras, buscando ocultar su rastro documentado y el verdadero origen de los fondos. Adicionalmente, el servidor público debe participar en una mesa de trabajo interinstitucional para mejorar el cruce de información dentro del modelo de gestión adoptado por el Estado, el cual busca una mayor efectividad en la prevención de estas conductas ilícitas de orden económico.',
        stem: '1. Frente a la etapa delictiva descrita en el informe donde los fondos se transfieren entre instituciones para disimular su fuente, el funcionario identifica que esto corresponde a',
        options: [
          {
            key: 'A',
            text: 'la fase de ocultamiento en la que se aplican capas de transacciones financieras complejas',
            is_correct: true,
            justification: 'Según la normativa, la etapa de ocultamiento involucra la conversión de los fondos de actividades ilícitas mediante capas de transacciones financieras, buscando disimular el rastro documentado, la fuente y la propiedad de los mismos dentro del circuito económico.'
          },
          {
            key: 'B',
            text: 'la etapa de colocación inicial mediante la introducción de capitales ilegales en la economía',
            is_correct: false,
            justification: 'Incorrecta. La colocación es la introducción inicial física de los dineros en el sistema financiero o económico, previa al movimiento sucesivo entre cuentas.'
          },
          {
            key: 'C',
            text: 'la consolidación de la integración de recursos regresándolos al mercado formal con fines lícitos',
            is_correct: false,
            justification: 'Incorrecta. La integración es la etapa final donde los activos ya lavados se reincorporan para la adquisición de bienes o inversiones legítimas.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Etapas de LA/FT (Colocación, Ocultamiento, Integración) - Estándares GAFI / UIAF.',
        explanation_summary: 'Según la normativa, la etapa de ocultamiento involucra la conversión de los fondos de actividades ilícitas mediante capas de transacciones financieras, buscando disimular el rastro documentado, la fuente y la propiedad de los mismos dentro del circuito económico.'
      },
      {
        case_text: 'Un funcionario que ejerce labores de inspección y vigilancia en una entidad pública recibe un informe sobre una empresa del sector real que ha comenzado a realizar transacciones financieras inusuales. El documento detalla que los recursos de la empresa son depositados en cuentas bancarias para luego ser transferidos rápidamente a otras instituciones financieras, buscando ocultar su rastro documentado y el verdadero origen de los fondos. Adicionalmente, el servidor público debe participar en una mesa de trabajo interinstitucional para mejorar el cruce de información dentro del modelo de gestión adoptado por el Estado, el cual busca una mayor efectividad en la prevención de estas conductas ilícitas de orden económico.',
        stem: '2. En relación con la mesa de trabajo y el modelo de gestión para la inteligencia económica, el servidor debe tener en cuenta que este enfoque busca fundamentalmente',
        options: [
          {
            key: 'A',
            text: 'operar de manera sistémica, amplia y bidireccional integrando nuevas fuentes de información y sectores',
            is_correct: true,
            justification: 'Correcta. El enfoque contemporáneo de inteligencia económica estatal articula transversalmente el sector financiero, el sector real y los órganos de supervisión de manera sistémica y bidireccional.'
          },
          {
            key: 'B',
            text: 'limitar la prevención al sector financiero mediante controles rígidos sobre transacciones de bajo valor',
            is_correct: false,
            justification: 'Incorrecta. Limitar la prevención solo a la banca excluye al sector real (comercio, aduanas) y vulnera el enfoque integral basado en riesgos.'
          },
          {
            key: 'C',
            text: 'garantizar la condena penal expedita mediante la recolección exclusiva de pruebas para la fiscalía',
            is_correct: false,
            justification: 'Incorrecta. La inteligencia económica busca prevenir, disuadir y alertar mediante cruces de información, no suplantar la investigación penal acusatoria.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Modelo de Inteligencia Económica y Financiera - UIAF / Decreto Ley 927 de 2023.',
        explanation_summary: 'El modelo de inteligencia económica busca operar de manera sistémica, amplia y bidireccional integrando nuevas fuentes de información y sectores.'
      },
      {
        case_text: 'Un funcionario que ejerce labores de inspección y vigilancia en una entidad pública recibe un informe sobre una empresa del sector real que ha comenzado a realizar transacciones financieras inusuales. El documento detalla que los recursos de la empresa son depositados en cuentas bancarias para luego ser transferidos rápidamente a otras instituciones financieras, buscando ocultar su rastro documentado y el verdadero origen de los fondos. Adicionalmente, el servidor público debe participar en una mesa de trabajo interinstitucional para mejorar el cruce de información dentro del modelo de gestión adoptado por el Estado, el cual busca una mayor efectividad en la prevención de estas conductas ilícitas de orden económico.',
        stem: '3. Para comprender el contexto de la economía criminal y sus efectos perjudiciales sobre el Estado, el funcionario debe considerar que estas prácticas ilícitas generan principalmente',
        options: [
          {
            key: 'A',
            text: 'el aumento desproporcionado de las exportaciones formales y una mayor rentabilidad del mercado nacional',
            is_correct: false,
            justification: 'Incorrecta. La economía criminal erosiona la competitividad formal y promueve el contrabando.'
          },
          {
            key: 'B',
            text: 'una distorsión en la inversión, el ahorro y un incremento artificial en los precios',
            is_correct: true,
            justification: 'Según los parámetros técnicos, el impacto macroeconómico de estas actividades ocasiona múltiples daños, entre los cuales se destacan la distorsión del consumo, la inversión y el ahorro, así como un incremento artificial en los precios y competencia desleal.'
          },
          {
            key: 'C',
            text: 'la estabilidad temporal de las tasas de interés y el fortalecimiento de la recaudación',
            is_correct: false,
            justification: 'Incorrecta. Los capitales criminales desestabilizan las variables macroeconómicas y socavan el recaudo tributario equitativo.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Doctrina Económica UIAF y Naciones Unidas (UNODC) sobre Efectos Macroeconómicos del Lavado de Activos.',
        explanation_summary: 'Según los parámetros técnicos, el impacto macroeconómico ocasiona la distorsión del consumo, la inversión y el ahorro, así como un incremento artificial en los precios y competencia desleal.'
      },

      // ================= CASO 2 =================
      {
        case_text: 'En el marco de sus funciones administrativas, un servidor público recibe el reporte interno de una compañía que comercializa bienes de alto valor. La empresa señala que varios clientes de estratos bajos, sin solvencia económica aparente, han adquirido productos costosos pagando con dinero en efectivo de forma fraccionada. Esta situación atípica ha superado los parámetros de normalidad del sector. El funcionario debe orientar a la compañía sobre la correcta tipificación de esta actividad y el procedimiento adecuado para informar al organismo de inteligencia correspondiente, garantizando el sigilo de la información y la protección del tejido empresarial frente al crimen organizado.',
        stem: '4. Al clasificar el comportamiento de los compradores que realizan transacciones atípicas que no pueden ser razonablemente justificadas, el servidor determina que se trata de',
        options: [
          {
            key: 'A',
            text: 'una operación sospechosa que amerita ser informada de manera inmediata al organismo de inteligencia',
            is_correct: true,
            justification: 'Las disposiciones indican que las operaciones sospechosas son aquellas que por su cuantía o características no se enmarcan en las prácticas normales y no pueden justificarse, debiendo reportarse inmediatamente a la unidad centralizada correspondiente de manera reservada.'
          },
          {
            key: 'B',
            text: 'una simple operación inusual que debe archivarse tras solicitar una explicación verbal al usuario',
            is_correct: false,
            justification: 'Incorrecta. Si no puede ser justificada razonablemente, no puede archivarse como simple inusualidad; se convierte en sospechosa y debe reportarse.'
          },
          {
            key: 'C',
            text: 'un acto delictivo comprobado que exige la detención preventiva de los implicados en ventanilla',
            is_correct: false,
            justification: 'Incorrecta. La entidad y sus empleados no tienen funciones judiciales de captura policiva preventiva.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 526 de 1999 Art. 10; Estatuto Orgánico del Sistema Financiero Art. 102; Manual Analista III Función 1.',
        explanation_summary: 'Las operaciones sospechosas son aquellas que por su cuantía o características no se enmarcan en las prácticas normales y no pueden justificarse, debiendo reportarse inmediatamente a la unidad centralizada correspondiente de manera reservada.'
      },
      {
        case_text: 'En el marco de sus funciones administrativas, un servidor público recibe el reporte interno de una compañía que comercializa bienes de alto valor. La empresa señala que varios clientes de estratos bajos, sin solvencia económica aparente, han adquirido productos costosos pagando con dinero en efectivo de forma fraccionada. Esta situación atípica ha superado los parámetros de normalidad del sector. El funcionario debe orientar a la compañía sobre la correcta tipificación de esta actividad y el procedimiento adecuado para informar al organismo de inteligencia correspondiente, garantizando el sigilo de la información y la protección del tejido empresarial frente al crimen organizado.',
        stem: '5. Frente a la preocupación de la empresa por las posibles implicaciones legales de enviar este reporte a la autoridad competente, el funcionario aclara que la notificación',
        options: [
          {
            key: 'A',
            text: 'mantiene estricta reserva y no constituye una denuncia penal ni genera responsabilidad para la entidad',
            is_correct: true,
            justification: 'La normatividad define que estos reportes tienen características específicas, resaltando que son completamente reservados, no se consideran denuncias penales, no requieren tener certeza absoluta del delito y no generan ningún tipo de responsabilidad para la entidad que reporta.'
          },
          {
            key: 'B',
            text: 'inicia automáticamente un proceso sancionatorio público contra el cliente por presunto enriquecimiento financiero ilícito',
            is_correct: false,
            justification: 'Incorrecta. El reporte a la UIAF es reservado de inteligencia; no es público ni inicia sanción directa inmediata.'
          },
          {
            key: 'C',
            text: 'requiere pruebas contundentes del delito para evitar demandas por difamación comercial contra la empresa',
            is_correct: false,
            justification: 'Incorrecta. No se exige certeza plena o prueba judicial del delito para el ROS, y la ley exonera de responsabilidad a la entidad reportante.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 526 de 1999 Art. 11; Estatuto Orgánico del Sistema Financiero Art. 102.',
        explanation_summary: 'La normatividad define que los reportes de operaciones sospechosas son completamente reservados, no se consideran denuncias penales y no generan ningún tipo de responsabilidad para la entidad que reporta.'
      },
      {
        case_text: 'En el marco de sus funciones administrativas, un servidor público recibe el reporte interno de una compañía que comercializa bienes de alto valor. La empresa señala que varios clientes de estratos bajos, sin solvencia económica aparente, han adquirido productos costosos pagando con dinero en efectivo de forma fraccionada. Esta situación atípica ha superado los parámetros de normalidad del sector. El funcionario debe orientar a la compañía sobre la correcta tipificación de esta actividad y el procedimiento adecuado para informar al organismo de inteligencia correspondiente, garantizando el sigilo de la información y la protección del tejido empresarial frente al crimen organizado.',
        stem: '6. Para fortalecer los controles de la compañía a partir de las situaciones atípicas presentadas, el servidor público le recomienda implementar un catálogo de señales de alerta que sirva para',
        options: [
          {
            key: 'A',
            text: 'reportar al supervisor a todos los clientes nuevos que operen exclusivamente mediante dinero en efectivo',
            is_correct: false,
            justification: 'Incorrecta. El uso de efectivo por sí solo no impone un reporte ciego indiscriminado de todos los clientes.'
          },
          {
            key: 'B',
            text: 'bloquear de inmediato cualquier transacción comercial que exceda los promedios transaccionales de la región',
            is_correct: false,
            justification: 'Incorrecta. El bloqueo automático sin análisis vulnera la actividad mercantil legítima.'
          },
          {
            key: 'C',
            text: 'llamar la atención sobre un cliente o hecho que requiere un análisis detallado y riguroso',
            is_correct: true,
            justification: 'Los manuales técnicos establecen que las señales de alerta sirven primordialmente para identificar comportamientos inusuales, llamando la atención sobre hechos que requieren mayor estudio para determinar la posible existencia de un mecanismo de lavado o financiación delictiva.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Guías de Señales de Alerta UIAF / Superintendencia de Sociedades (SAGRILAFT).',
        explanation_summary: 'Los manuales técnicos establecen que las señales de alerta sirven primordialmente para identificar comportamientos inusuales, llamando la atención sobre hechos que requieren mayor estudio.'
      },

      // ================= CASO 3 =================
      {
        case_text: 'Un servidor público asesora a una corporación del sector real que busca implementar buenas prácticas corporativas para blindarse contra la penetración de dineros ilícitos. La entidad teme que, al establecer vínculos con proveedores o clientes internacionales dudosos, termine siendo utilizada sin saberlo para dar apariencia legal a activos criminales. Durante las mesas de trabajo, el funcionario evalúa los posibles riesgos institucionales a los que se expondría la compañía si no cuenta con un sistema robusto de conocimiento del mercado y advierte sobre las repercusiones negativas que esto traería para su estabilidad operativa, prestigio institucional y viabilidad jurídica a largo plazo.',
        stem: '7. En caso de que la corporación decida omitir los controles preventivos y se vea involucrada en una investigación judicial, el funcionario señala que el impacto recaería directamente en el riesgo',
        options: [
          {
            key: 'A',
            text: 'soberano debido a la disminución del recaudo tributario esperado por las autoridades competentes del central',
            is_correct: false,
            justification: 'Incorrecta. El riesgo soberano compete al Estado y a la deuda pública nacional, no a una persona jurídica privada.'
          },
          {
            key: 'B',
            text: 'legal y de contagio al someter sus bienes a procesos como la extinción de dominio',
            is_correct: true,
            justification: 'La teoría de administración de riesgos precisa que el riesgo legal y de contagio ocurre cuando la empresa queda incluida en investigaciones judiciales o es juzgada, situación que puede derivar en que sus activos sean objeto de extinción.'
          },
          {
            key: 'C',
            text: 'crediticio al incrementarse las tasas de interés aplicadas a sus futuros proyectos de expansión productiva',
            is_correct: false,
            justification: 'Incorrecta. El riesgo crediticio es de naturaleza financiera sobre impago de deudas, ajeno al proceso penal de extinción de dominio.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Ley 1708 de 2014 (Extinción de Dominio); Sistema SARLAFT/SAGRILAFT de Gestión de Riesgos Asociados.',
        explanation_summary: 'La teoría de administración de riesgos precisa que el riesgo legal y de contagio ocurre cuando la empresa queda incluida en investigaciones judiciales o es juzgada, situación que puede derivar en que sus activos sean objeto de extinción.'
      },
      {
        case_text: 'Un servidor público asesora a una corporación del sector real que busca implementar buenas prácticas corporativas para blindarse contra la penetración de dineros ilícitos. La entidad teme que, al establecer vínculos con proveedores o clientes internacionales dudosos, termine siendo utilizada sin saberlo para dar apariencia legal a activos criminales. Durante las mesas de trabajo, el funcionario evalúa los posibles riesgos institucionales a los que se expondría la compañía si no cuenta con un sistema robusto de conocimiento del mercado y advierte sobre las repercusiones negativas que esto traería para su estabilidad operativa, prestigio institucional y viabilidad jurídica a largo plazo.',
        stem: '8. Para mitigar efectivamente estas vulnerabilidades, el servidor instruye a la corporación para que adopte como una buena práctica fundamental la implementación de mecanismos orientados a',
        options: [
          {
            key: 'A',
            text: 'priorizar transacciones en divisas extranjeras sin registro para evitar demoras en los intercambios comerciales formales',
            is_correct: false,
            justification: 'Incorrecta. Operar en divisas sin registro viola el régimen cambiario y constituye infracción aduanera y cambiaria.'
          },
          {
            key: 'B',
            text: 'aplicar el conocimiento del cliente e indagar sobre sus antecedentes y experiencia en el sector',
            is_correct: true,
            justification: 'Según los lineamientos de prevención, la parte esencial del sistema de administración es el debido conocimiento del cliente, lo cual implica indagar por la actividad económica, obtener referencias, verificar información y estar atento a solicitudes o circunstancias inusuales.'
          },
          {
            key: 'C',
            text: 'delegar la validación de usuarios a las autoridades aduaneras que operan en las zonas francas',
            is_correct: false,
            justification: 'Incorrecta. La debida diligencia de conocimiento de contrapartes es indelegable por la empresa.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Recomendaciones GAFI; SAGRILAFT Circular 100-000016 Supersociedades.',
        explanation_summary: 'Según los lineamientos de prevención, la parte esencial del sistema de administración es el debido conocimiento del cliente, lo cual implica indagar por la actividad económica, obtener referencias y verificar información.'
      },
      {
        case_text: 'Un servidor público asesora a una corporación del sector real que busca implementar buenas prácticas corporativas para blindarse contra la penetración de dineros ilícitos. La entidad teme que, al establecer vínculos con proveedores o clientes internacionales dudosos, termine siendo utilizada sin saberlo para dar apariencia legal a activos criminales. Durante las mesas de trabajo, el funcionario evalúa los posibles riesgos institucionales a los que se expondría la compañía si no cuenta con un sistema robusto de conocimiento del mercado y advierte sobre las repercusiones negativas que esto traería para su estabilidad operativa, prestigio institucional y viabilidad jurídica a largo plazo.',
        stem: '9. Cuando el funcionario expone las consecuencias que surgen a partir de los rumores sobre la participación de la empresa en operaciones ilícitas, hace énfasis en que se materializa un riesgo',
        options: [
          {
            key: 'A',
            text: 'sistémico que colapsa la infraestructura financiera del Estado por la fuga de divisas no reportadas',
            is_correct: false,
            justification: 'Incorrecta. El riesgo sistémico es la caída general del sistema financiero, no la reputación de una empresa individual.'
          },
          {
            key: 'B',
            text: 'reputacional que dificulta entablar relaciones comerciales, provocando la pérdida de inversiones y futuros compradores estratégicos',
            is_correct: true,
            justification: 'El riesgo reputacional ocurre por la pérdida de confianza de contrapartes, entidades financieras e inversionistas debido al descrédito o sospecha de relación con actividades ilícitas.'
          },
          {
            key: 'C',
            text: 'operativo generado por fallas tecnológicas que vulneran las bases de datos transaccionales de la entidad',
            is_correct: false,
            justification: 'Incorrecta. El riesgo operativo concierne a fallas en sistemas, procesos y talento humano interno, no a la percepción externa.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'SARLAFT/SAGRILAFT - Definición de Riesgo Reputacional.',
        explanation_summary: 'El riesgo reputacional dificulta entablar relaciones comerciales, provocando la pérdida de inversiones y futuros compradores estratégicos.'
      },

      // ================= CASO 4 =================
      {
        case_text: 'Durante un ciclo de capacitación estratégica impartida por un servidor público a diferentes agremiaciones económicas, se expone un caso de estudio sobre las tácticas de fraccionamiento utilizadas por grupos criminales. El funcionario detalla cómo una red trasladó recursos desde el exterior dividiendo el dinero en pequeños montos remitidos a múltiples beneficiarios sin relación aparente, quienes cobraron los giros aportando información falsa. El propósito de esta sesión es que los asistentes comprendan la diferencia entre las alertas tempranas y los esquemas históricos ya documentados, facilitando así el diseño de políticas institucionales y la formulación de hipótesis preventivas dentro de sus propios sectores.',
        stem: '10. Al explicar el esquema de fraccionamiento expuesto en el caso de estudio, el servidor indica que el mecanismo de dividir altas sumas de dinero busca principalmente',
        options: [
          {
            key: 'A',
            text: 'minimizar el pago de comisiones bancarias durante las transferencias electrónicas realizadas por medios digitales',
            is_correct: false,
            justification: 'Incorrecta. Dividir en múltiples giros incrementa el costo financiero de las comisiones; el objetivo es la evasión de control.'
          },
          {
            key: 'B',
            text: 'evadir los controles existentes tanto en el país de origen de los fondos como en su destino',
            is_correct: true,
            justification: 'Las tipologías documentadas precisan que el fraccionamiento o estructuración de operaciones se realiza dividiendo sumas considerables en montos pequeños para evadir los controles normativos establecidos por las entidades supervisoras en las diferentes jurisdicciones involucradas en la operación.'
          },
          {
            key: 'C',
            text: 'diversificar las inversiones en el mercado de valores para obtener mayores rendimientos a corto plazo',
            is_correct: false,
            justification: 'Incorrecta. La estructuración de giros clandestinos no constituye una diversificación de portafolio bursátil.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Tipología GAFILAT sobre Estructuración y Fraccionamiento de Fondos.',
        explanation_summary: 'Las tipologías documentadas precisan que el fraccionamiento o estructuración de operaciones se realiza dividiendo sumas considerables en montos pequeños para evadir los controles normativos establecidos.'
      },
      {
        case_text: 'Durante un ciclo de capacitación estratégica impartida por un servidor público a diferentes agremiaciones económicas, se expone un caso de estudio sobre las tácticas de fraccionamiento utilizadas por grupos criminales. El funcionario detalla cómo una red trasladó recursos desde el exterior dividiendo el dinero en pequeños montos remitidos a múltiples beneficiarios sin relación aparente, quienes cobraron los giros aportando información falsa. El propósito de esta sesión es que los asistentes comprendan la diferencia entre las alertas tempranas y los esquemas históricos ya documentados, facilitando así el diseño de políticas institucionales y la formulación de hipótesis preventivas dentro de sus propios sectores.',
        stem: '11. Para diferenciar los conceptos analizados en la capacitación, el funcionario aclara que mientras las señales de alerta diagnostican hechos inusuales específicos, las tipologías funcionan como',
        options: [
          {
            key: 'A',
            text: 'requerimientos regulatorios exclusivos para los clientes que manejan grandes volúmenes de dinero en efectivo diariamente',
            is_correct: false,
            justification: 'Incorrecta. Los requerimientos regulatorios son obligaciones legales de cumplimiento, no tipologías conceptuales.'
          },
          {
            key: 'B',
            text: 'pruebas documentales irrefutables que conducen al decomiso inmediato de bienes sin necesidad de un juicio',
            is_correct: false,
            justification: 'Incorrecta. Las tipologías son guías metodológicas de estudio criminológico, no pruebas irrefutables en juicios.'
          },
          {
            key: 'C',
            text: 'reportes de esquemas históricos que permiten formular hipótesis delictivas y diseñar mecanismos de prevención estructurales',
            is_correct: true,
            justification: 'Los marcos técnicos diferencian claramente ambos conceptos, definiendo a las tipologías como generalidades e informes históricos de comportamientos atípicos que resultan útiles para formular políticas, regular sectores, diseñar herramientas de prevención y construir hipótesis delictivas de alcance amplio.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Informes de Tipologías UIAF / GAFI.',
        explanation_summary: 'Los marcos técnicos definen a las tipologías como generalidades e informes históricos de comportamientos atípicos que resultan útiles para formular políticas, regular sectores y diseñar herramientas de prevención.'
      },
      {
        case_text: 'Durante un ciclo de capacitación estratégica impartida por un servidor público a diferentes agremiaciones económicas, se expone un caso de estudio sobre las tácticas de fraccionamiento utilizadas por grupos criminales. El funcionario detalla cómo una red trasladó recursos desde el exterior dividiendo el dinero en pequeños montos remitidos a múltiples beneficiarios sin relación aparente, quienes cobraron los giros aportando información falsa. El propósito de esta sesión es que los asistentes comprendan la diferencia entre las alertas tempranas y los esquemas históricos ya documentados, facilitando así el diseño de políticas institucionales y la formulación de hipótesis preventivas dentro de sus propios sectores.',
        stem: '12. En el desarrollo del análisis práctico sobre la modalidad de fraccionamiento, el servidor menciona que la utilización de múltiples beneficiarios para canalizar estas transacciones a menudo involucra a individuos denominados',
        options: [
          {
            key: 'A',
            text: 'supervisores encubiertos encargados de validar la legalidad de los giros internacionales de manera aleatoria',
            is_correct: false,
            justification: 'Incorrecta. Los supervisores encubiertos son funcionarios estatales de investigación, no los intermediarios de cobro.'
          },
          {
            key: 'B',
            text: 'pitufos que reciben una compensación por prestar sus datos para eludir la supervisión del sistema',
            is_correct: true,
            justification: 'En el estudio de las tipologías se identifica que el fraccionamiento suele emplear a múltiples intermediarios o beneficiarios, habitualmente conocidos como pitufos, quienes reciben y trasladan los recursos fragmentados a cambio de una comisión, facilitando el ocultamiento del dinero.'
          },
          {
            key: 'C',
            text: 'acopiadores que coordinan las grandes inversiones bursátiles en paraísos fiscales mediante el uso de intermediarios',
            is_correct: false,
            justification: 'Incorrecta. Los acopiadores coordinan grandes capitales, no el cobro atomizado de pequeñas sumas en ventanilla.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Tipología UIAF - Pitufeo (Smurfing) en Operaciones Cambiarias y Giros Internacionales.',
        explanation_summary: 'En el estudio de las tipologías se identifica que el fraccionamiento suele emplear a múltiples intermediarios conocidos como pitufos, quienes reciben y trasladan los recursos fragmentados a cambio de una comisión.'
      }
    ];

    let inserted = 0;
    for (const q of docQuestions) {
      await db.query(
        `INSERT INTO questions (
          job_profile_id, thematic_axis_id, question_type, difficulty,
          case_text, stem, options, correct_option, legal_basis, explanation_summary
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)`,
        [
          analista3Id,
          axisId,
          'situacional',
          'medio',
          q.case_text,
          q.stem,
          JSON.stringify(q.options),
          q.correct_option,
          q.legal_basis,
          q.explanation_summary
        ]
      );
      inserted++;
    }

    const totalCount = await db.query('SELECT COUNT(*) as total FROM questions WHERE job_profile_id = $1', [analista3Id]);
    console.log(`--- Preguntas actualizadas con comentarios oficiales de Misión Mérito. Total actual en PostgreSQL: ${totalCount.rows[0].total} ---`);

  } catch (err) {
    console.error('Error actualizando con comentarios oficiales:', err);
  } finally {
    process.exit(0);
  }
}

updateDocQuestionsWithOfficialFeedback();
