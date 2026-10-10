const db = require('./db');

async function seed() {
  console.log('--- Iniciando Seeding de Base de Datos Concurso DIAN 2676 ---');

  try {
    // 1. Limpiar o asegurar tablas
    await db.query('BEGIN');

    // 2. Insertar perfiles de cargo
    const jobProfilesData = [
      {
        code: 'AT-FL-2011',
        name: 'Analista III',
        level: 'Nivel Técnico',
        grade: '03',
        process: 'MISIONAL: Cumplimiento de obligaciones tributarias, cumplimiento de obligaciones aduaneras y cambiarias',
        subprocess: 'Fiscalización y liquidación',
        purpose: 'Ejecutar actividades técnicas y administrativas de apoyo dirigidas a la verificación del cumplimiento de las obligaciones tributarias, aduaneras o cambiarias, detección de lavado de activos y financiación del terrorismo, de acuerdo con la normativa vigente, los procedimientos establecidos y los lineamientos institucionales.'
      },
      {
        code: 'AT-FL-2010',
        name: 'Analista II',
        level: 'Nivel Técnico',
        grade: '02',
        process: 'MISIONAL: Cumplimiento de obligaciones tributarias, cumplimiento de obligaciones aduaneras y cambiarias',
        subprocess: 'Fiscalización y liquidación',
        purpose: 'Apoyar técnicamente las actividades operativas y de registro en los procesos de fiscalización tributaria y aduanera.'
      },
      {
        code: 'PF-FL-3011',
        name: 'Gestor I',
        level: 'Nivel Profesional',
        grade: '01',
        process: 'MISIONAL: Fiscalización y liquidación',
        subprocess: 'Determinación de tributos',
        purpose: 'Adelantar investigaciones tributarias y proyectar actos administrativos de determinación y sanción.'
      }
    ];

    const profileMap = {};
    for (const jp of jobProfilesData) {
      const res = await db.query(
        `INSERT INTO job_profiles (code, name, level, grade, process, subprocess, purpose)
         VALUES ($1, $2, $3, $4, $5, $6, $7)
         ON CONFLICT (code) DO UPDATE SET
           name = EXCLUDED.name,
           level = EXCLUDED.level,
           grade = EXCLUDED.grade,
           process = EXCLUDED.process,
           subprocess = EXCLUDED.subprocess,
           purpose = EXCLUDED.purpose
         RETURNING id, code;`,
        [jp.code, jp.name, jp.level, jp.grade, jp.process, jp.subprocess, jp.purpose]
      );
      profileMap[jp.code] = res.rows[0].id;
    }
    console.log('Perfiles registrados:', profileMap);

    const analista3Id = profileMap['AT-FL-2011'];

    // 3. Ejes Temáticos para Analista III
    const axesData = [
      {
        category: 'funcional_especifica',
        name: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        description: 'Estatuto Tributario Nacional: Facultades de fiscalización (Art. 684), Requerimiento Especial, Emplazamiento para Corregir, Inspección Tributaria y Contable, Liquidación Oficial de Revisión y Pliegos de Cargos.',
        weight_pct: 25.0
      },
      {
        category: 'funcional_especifica',
        name: 'Fiscalización Aduanera y Destrucción de Mercancías',
        description: 'Decreto 1165 de 2019 y Decreto 920 de 2023: Procedimiento de control posterior, aprehensión y decomiso, y diligencias de destrucción de mercancía averiada, defectuosa o impropia.',
        weight_pct: 20.0
      },
      {
        category: 'funcional_especifica',
        name: 'Régimen Cambiario y Detección de Lavado de Activos (UIAF / ROS)',
        description: 'Decreto Ley 2245 de 2011, Circular DCIN-83 Banco de la República, operaciones canalizables, infracciones cambiarias y Reportes de Operaciones Sospechosas (ROS).',
        weight_pct: 15.0
      },
      {
        category: 'funcional_basica',
        name: 'Procedimiento Administrativo General (CPACA Ley 1437 de 2011)',
        description: 'Disposiciones generales, derecho de petición, notificaciones, recursos en sede administrativa, revocatoria directa y firmeza de los actos administrativos.',
        weight_pct: 10.0
      },
      {
        category: 'funcional_basica',
        name: 'Conceptos de Evasión, Elusión, Contrabando y Ley de Transparencia (Ley 1712)',
        description: 'Definiciones doctrinales y normativas de evasión, elusión tributaria, contrabando abierto y técnico, y aplicación de la Ley de Transparencia y Acceso a la Información Pública.',
        weight_pct: 10.0
      },
      {
        category: 'funcional_basica',
        name: 'Gestión Documental y Modelo MIPG',
        description: 'Ley 594 de 2000, Tablas de Retención Documental (TRD), expediente electrónico, trazabilidad probatoria y Dimensiones del Modelo Integrado de Planeación y Gestión.',
        weight_pct: 10.0
      },
      {
        category: 'comportamental',
        name: 'Competencias Comportamentales (Nivel Técnico)',
        description: 'Comportamiento Ético (Nivel 4), Comunicación Efectiva (Nivel 2), Adaptabilidad (Nivel 2), Trabajo en Equipo (Nivel 2) y Orientación al Logro y al Usuario.',
        weight_pct: 15.0
      },
      {
        category: 'integridad',
        name: 'Código de Integridad y Ética Pública DIAN',
        description: 'Valores institucionales: Honestidad, Respeto, Compromiso, Diligencia, Justicia y subescalas de control de deseabilidad social.',
        weight_pct: 10.0
      }
    ];

    const axisMap = {};
    for (const ax of axesData) {
      const existing = await db.query(
        'SELECT id FROM thematic_axes WHERE job_profile_id = $1 AND name = $2',
        [analista3Id, ax.name]
      );
      if (existing.rows.length > 0) {
        axisMap[ax.name] = existing.rows[0].id;
      } else {
        const res = await db.query(
          `INSERT INTO thematic_axes (job_profile_id, category, name, description, weight_pct)
           VALUES ($1, $2, $3, $4, $5) RETURNING id;`,
          [analista3Id, ax.category, ax.name, ax.description, ax.weight_pct]
        );
        axisMap[ax.name] = res.rows[0].id;
      }
    }
    console.log('Ejes temáticos listos');

    // Limpiar preguntas anteriores para re-sembrar banco completo actualizado
    await db.query('DELETE FROM questions WHERE job_profile_id = $1', [analista3Id]);

    // 4. Banco Exhaustivo de Preguntas de Juicio Situacional (PJS) y Likert
    const questionsData = [
      // --- EJE: Determinación y Control Tributario ---
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En el marco de una investigación de fiscalización a un contribuyente del sector comercial, el equipo auditor detecta que la declaración de renta presentada para la vigencia anterior contiene costos por compras que no cuentan con factura electrónica de venta ni soporte de pago en medios bancarios. Como Analista III de la división de fiscalización y liquidación, su superior le encomienda proyectar el acto preparatorio preliminar que pone en conocimiento del investigado las glosas u objeciones identificadas.',
        stem: 'Atendiendo a la normativa procedimental tributaria, al Analista III le corresponde proyectar:',
        options: [
          {
            key: 'A',
            text: 'un Requerimiento Especial, detallando los puntos que se propone modificar de la liquidación privada y la cuantificación estimada del tributo y sanciones.',
            is_correct: true,
            justification: 'Es correcta. De acuerdo con el artículo 703 del Estatuto Tributario, antes de efectuar la liquidación de revisión, la administración enviará al contribuyente un requerimiento especial que contenga todos los puntos que se proponga modificar, con explicación de las razones en que se sustenta y la cuantificación de los mayores impuestos y sanciones propuestas.'
          },
          {
            key: 'B',
            text: 'una Liquidación Oficial de Revisión directamente, dado que la omisión de factura electrónica constituye plena prueba de inexactitud insubsanable.',
            is_correct: false,
            justification: 'Es incorrecta. El artículo 702 y 703 del Estatuto Tributario consagran el principio de contradicción y debido proceso; la administración no puede expedir directamente la liquidación oficial de revisión sin haber notificado previamente el requerimiento especial para que el contribuyente responda o formule objeciones.'
          },
          {
            key: 'C',
            text: 'un Auto de Archivo definitivo, informándole que por falta de factura electrónica la DIAN no tiene competencia para pronunciarse sobre costos fiscales.',
            is_correct: false,
            justification: 'Es incorrecta. El artículo 684 del Estatuto Tributario otorga amplias facultades de fiscalización e investigación tributaria a la DIAN para verificar la exactitud de las declaraciones; el rechazo de costos no genera archivo, sino la continuación del procedimiento de determinación oficial.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Arts. 684, 702 y 703; Ley 1437 de 2011, Art. 29 C.P.',
        explanation_summary: 'El Requerimiento Especial es el acto previo y obligatorio de trámite donde la DIAN cuantifica glosas y garantiza el derecho de defensa del contribuyente antes de la Liquidación Oficial de Revisión.'
      },
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'Durante la ejecución de una inspección tributaria practicada a una persona jurídica, los funcionarios de la DIAN solicitan la exhibición de los libros de contabilidad físicos y auxiliares magnéticos. El representante legal manifiesta que los libros no están registrados ni actualizados desde hace seis meses y se niega a permitir el acceso al software contable. El Analista III acompaña la diligencia de práctica de pruebas conforme a las funciones de su empleo.',
        stem: 'Frente a la negativa del contribuyente de exhibir la contabilidad en la diligencia, la acción técnica que debe realizar el funcionario es:',
        options: [
          {
            key: 'A',
            text: 'levantar un acta pormenorizada de inspección en la que se deje constancia expresa de la no exhibición de los libros y de la manifestación de las partes, para dar inicio al procedimiento de sanción por libros de contabilidad.',
            is_correct: true,
            justification: 'Es correcta. Según los artículos 654, 655 y 779 del Estatuto Tributario, la no exhibición de los libros de contabilidad cuando la administración lo exija constituye causal de sanción. La diligencia debe constar en un acta de inspección tributaria firmada por los intervinientes, la cual constituye plena prueba para proferir el respectivo pliego de cargos.'
          },
          {
            key: 'B',
            text: 'suspender de inmediato toda la investigación tributaria de manera definitiva al no existir información contable disponible para verificar.',
            is_correct: false,
            justification: 'Es incorrecta. La falta de contabilidad no suspende definitivamente la potestad investigativa de la DIAN; por el contrario, faculta a la entidad para aplicar sanciones y determinar la obligación mediante estimación directa, presunciones legales o aforo conforme a los artículos 756 y subsiguientes del E.T.'
          },
          {
            key: 'C',
            text: 'ordenar el cierre inmediato del establecimiento comercial en el mismo acto de la diligencia sin trámite previo.',
            is_correct: false,
            justification: 'Es incorrecta. La clausura o sanción de clausura del establecimiento (Art. 657 E.T.) requiere un procedimiento previo con pliego de cargos y oportunidad de descargos (Art. 657 Parágrafo y Art. 684 E.T.); no puede aplicarse de plano por vía de hecho durante una inspección.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Arts. 654, 655, 779 y 782.',
        explanation_summary: 'La no exhibición de contabilidad debe constar en acta de inspección para soportar el pliego de cargos por irregularidades en la contabilidad conforme al debido proceso.'
      },
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Un contribuyente omiso en la presentación de la declaración del impuesto sobre las ventas (IVA) del bimestre 3 es notificado de un Emplazamiento para Declarar. La ley otorga un término perentorio para responder presentando la declaración correspondiente con la respectiva sanción por extemporaneidad. Transcurrido el término legal de un (1) mes sin que el contribuyente presente la declaración, el caso pasa al Analista III para apoyar la fase de liquidación.',
        stem: 'Al vencer el plazo del emplazamiento para declarar sin respuesta del contribuyente, la actuación procedente que debe proyectarse es:',
        options: [
          {
            key: 'A',
            text: 'el Auto de Liquidación Provisional o Resolución de Liquidación Oficial de Aforo, previa formulación del requerimiento o pliego cuando corresponda conforme al procedimiento de aforo.',
            is_correct: true,
            justification: 'Es correcta. El artículo 715 y 717 del Estatuto Tributario estipulan que vencido el término de un mes sin que se haya presentado la declaración, la administración podrá determinar los tributos mediante Liquidación de Aforo dentro de los cinco años siguientes al vencimiento del plazo para declarar.'
          },
          {
            key: 'B',
            text: 'un nuevo Emplazamiento para Declarar concediéndole un plazo indefinido adicional para no vulnerar el principio de confianza legítima.',
            is_correct: false,
            justification: 'Es incorrecta. Los términos tributarios son de estricto cumplimiento legal; la norma no contempla la reiteración indefinida de emplazamientos para declarar, pues ello vulneraría la firmeza y la eficacia de la gestión de cobro estatal.'
          },
          {
            key: 'C',
            text: 'remitir directamente el expediente a cobro coactivo sin necesidad de emitir ningún acto oficial de aforo.',
            is_correct: false,
            justification: 'Es incorrecta. El cobro coactivo exige la existencia de un título ejecutivo claro, expreso y exigible (Art. 828 E.T.). Si el contribuyente no declaró, la deuda no existe formalmente hasta tanto no quede ejecutoriada la Liquidación Oficial de Aforo.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario, Arts. 715, 716 y 717.',
        explanation_summary: 'Vencido el término del emplazamiento para declarar sin cumplimiento por el obligado, la DIAN procede a determinar oficialmente la obligación mediante Liquidación de Aforo.'
      },
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'básico',
        case_text: 'Un contribuyente notificado del Requerimiento Especial presenta respuesta en el término de tres (3) meses, aceptando parcialmente las glosas planteadas por la DIAN y corrigiendo su declaración de renta, cancelando el mayor impuesto y la sanción por inexactitud reducida a la cuarta parte. El expediente es asignado al Analista III para verificar la exactitud de la liquidación de la sanción reducida.',
        stem: 'Con base en el artículo 709 del Estatuto Tributario, la sanción por inexactitud reducida que procede con ocasión de la respuesta al requerimiento especial equivale al:',
        options: [
          {
            key: 'A',
            text: 'veinticinco por ciento (25%) de la sanción inicialmente planteada por la administración.',
            is_correct: false,
            justification: 'Es incorrecta. El artículo 709 del Estatuto Tributario (modificado por la Ley 1819 de 2016) señala que la sanción por inexactitud se reducirá a la cuarta parte (25%) de la sanción generada en la corrección o al 50% de la sanción fijada en la norma general, según los supuestos de hecho expresos del E.T.'
          },
          {
            key: 'B',
            text: 'cuarta parte de la sanción que resulte aplicable, siempre que el contribuyente corrija su declaración, liquide el mayor impuesto y pague o acuerde el pago.',
            is_correct: true,
            justification: 'Es correcta. El artículo 709 del Estatuto Tributario establece expresamente que si con ocasión de la respuesta al requerimiento especial el contribuyente acepta total o parcialmente los hechos, la sanción de inexactitud se reducirá a la cuarta parte de la que resulte aplicable en relación con los hechos aceptados.'
          },
          {
            key: 'C',
            text: 'cero por ciento (0%), exonerando totalmente al contribuyente de cualquier tipo de penalidad pecuniaria.',
            is_correct: false,
            justification: 'Es incorrecta. La aceptación con respuesta al requerimiento no extingue la sanción; otorga el beneficio de reducción por allanamiento oportuno, pero no exoneración total.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Estatuto Tributario Nacional, Art. 709.',
        explanation_summary: 'El artículo 709 del E.T. consagra la reducción de la sanción de inexactitud a la cuarta parte cuando el contribuyente acepta los hechos dentro del término de respuesta al requerimiento especial.'
      },

      // --- EJE: Fiscalización Aduanera y Destrucción de Mercancías ---
      {
        thematic_axis: 'Fiscalización Aduanera y Destrucción de Mercancías',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En un depósito aduanero habilitado se encuentra un lote de productos perecederos de importación que sufrieron avería total por ruptura de la cadena de frío, encontrándose en estado de descomposición biológica y sin aptitud para el consumo humano ni comercialización. El importador y la autoridad ambiental solicitan la destrucción inmediata de la mercancía. Conforme a las funciones esenciales del empleo (Función 4 del MERF), se designa al Analista III para intervenir en la diligencia.',
        stem: 'En el procedimiento aduanero de destrucción de mercancía averiada, la obligación técnica fundamental del Analista III consiste en:',
        options: [
          {
            key: 'A',
            text: 'intervenir en la diligencia, verificar que el procedimiento se realice con las medidas ambientales aplicables y levantar el acta de destrucción que identifique la mercancía, cantidades, causas y método de inutilización, garantizando la trazabilidad de lo actuado.',
            is_correct: true,
            justification: 'Es correcta. De conformidad con el Decreto 1165 de 2019 (Arts. 189 y subsiguientes) y la Resolución 046 de 2019 de la DIAN, en la destrucción de mercancía averiada debe estar presente la autoridad aduanera levantando un acta formal donde conste la pérdida total de valor comercial y el destino final, lo que extingue legalmente la obligación aduanera adeudada.'
          },
          {
            key: 'B',
            text: 'entregar la mercancía descompuesta a fundaciones de caridad para evitar pérdidas económicas al importador.',
            is_correct: false,
            justification: 'Es incorrecta. Mercancía averiada que represente riesgo biológico o sanitario para la salud pública no puede donarse bajo ninguna circunstancia conforme a las normas aduaneras e higiénicas nacionales.'
          },
          {
            key: 'C',
            text: 'proferir de manera directa un pliego de cargos sancionatorio al depósito por el solo hecho fortuito de la descomposición sin realizar inspección.',
            is_correct: false,
            justification: 'Es incorrecta. La destrucción de mercancía averiada o impropia es un mecanismo legal de disposición y extinción de la obligación aduanera cuando median causas justificadas; no procede sanción automática sin verificar incumplimientos culpables del régimen aduanero.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto 1165 de 2019, Arts. 189, 190; Manual MERF Analista III Función 4.',
        explanation_summary: 'La diligencia de destrucción de mercancías averiadas exige la presencia del servidor público aduanero, verificación técnica in situ y levantamiento del acta oficial que ampara la extinción de la obligación aduanera.'
      },
      {
        thematic_axis: 'Fiscalización Aduanera y Destrucción de Mercancías',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En una acción de control aduanero posterior en una bodega comercial, se identifican mercancías extranjeras consistentes en confecciones y calzado de procedencia asiática. Al requerir al tenedor las declaraciones de importación o facturas que acrediten la legal introducción al territorio aduanero nacional (TAN), este manifiesta que los documentos se encuentran en la sede principal en otra ciudad y no aporta ningún soporte físico o electrónico durante la diligencia.',
        stem: 'Frente a la no demostración de la legal introducción y permanencia de las mercancías durante la acción de control, la medida cautelar que corresponde adoptar es:',
        options: [
          {
            key: 'A',
            text: 'la aprehensión de las mercancías mediante acta en la que se describan los bienes y se otorgue el término legal al interesado para presentar el Documento de Objeción a la Aprehensión.',
            is_correct: true,
            justification: 'Es correcta. De conformidad con el Decreto 920 de 2023 (nuevo régimen sancionatorio y de decomiso de mercancías) y el Decreto 1165 de 2019, la no presentación de los documentos que amparen la legal introducción o permanencia de mercancías extranjeras da lugar a la medida cautelar de aprehensión mediante acta formal, abriendo la etapa probatoria para el interesado.'
          },
          {
            key: 'B',
            text: 'el decomiso directo e inmediato en el mismo momento de la visita sin permitir término de contradicción o descargos.',
            is_correct: false,
            justification: 'Es incorrecta. El decomiso es el acto administrativo definitivo que transfiere la propiedad de la mercancía a favor de la Nación. No puede decretarse de plano en la diligencia sin adelantar previamente el procedimiento administrativo con aprehensión y término para objeciones.'
          },
          {
            key: 'C',
            text: 'permitir la movilización de la mercancía a otro lugar bajo promesa verbal del comerciante de enviarlos por correo después.',
            is_correct: false,
            justification: 'Es incorrecta. El servidor aduanero tiene el deber legal de asegurar las mercancías objeto de sospecha de contrabando para evitar su ocultamiento o comercialización irregular.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto 920 de 2023, Arts. 69, 70, 71; Decreto 1165 de 2019, Art. 647.',
        explanation_summary: 'La aprehensión es la medida cautelar provisional que procede al momento de hallar mercancías sin soportes legales en el TAN, respetando el derecho al debido proceso previo al decomiso.'
      },
      {
        thematic_axis: 'Fiscalización Aduanera y Destrucción de Mercancías',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'Un importador clasificado como Usuario Aduanero de Trámite Simplificado presentó una declaración anticipada obligatoria fuera del término previsto por la normatividad aduanera. La división de fiscalización aduanera debe adelantar la investigación correspondiente y el Analista III apoya la revisión preliminar de los insumos y la proyección del acto correspondiente.',
        stem: 'En el régimen sancionatorio aduanero (Decreto 920 de 2023), la presentación extemporánea de la declaración anticipada obligatoria constituye una infracción aduanera de carácter:',
        options: [
          {
            key: 'A',
            text: 'administrativo sancionable con multa pecuniaria según la gravedad y el tipo de usuario, requiriendo la expedición del requerimiento especial aduanero.',
            is_correct: true,
            justification: 'Es correcta. El Decreto 920 de 2023 clasifica las infracciones aduaneras en gravísimas, graves y leves, sancionables con multas, cancelación o suspensión. La extemporaneidad en la declaración anticipada es una falta sujeta a sanción pecuniaria formal mediante Requerimiento Especial Aduanero.'
          },
          {
            key: 'B',
            text: 'penal con arresto obligatorio inmediato del representante legal sin trámite administrativo.',
            is_correct: false,
            justification: 'Es incorrecta. La extemporaneidad en trámites aduaneros ordinarios es una infracción de índole administrativa sujeta a la competencia de la DIAN, y no un delito penal de contrabando per se a menos que concurran elementos típicos previstos en el Código Penal.'
          },
          {
            key: 'C',
            text: 'inexistente, dado que la DIAN no fiscaliza los términos de las declaraciones de importación.',
            is_correct: false,
            justification: 'Es incorrecta. El control aduanero abarca expresamente la verificación del cumplimiento estricto de los plazos de presentación de declaraciones conforme al artículo 175 del Decreto 1165 de 2019.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto 920 de 2023, Régimen Sancionatorio Aduanero; Decreto 1165 de 2019.',
        explanation_summary: 'Las faltas aduaneras en términos de presentación de declaraciones corresponden a infracciones administrativas investigadas mediante el Requerimiento Especial Aduanero.'
      },

      // --- EJE: Régimen Cambiario y Detección de Lavado de Activos ---
      {
        thematic_axis: 'Régimen Cambiario y Detección de Lavado de Activos (UIAF / ROS)',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'En desarrollo de una auditoría cambiaria, se examinan las operaciones de comercio exterior de una empresa importadora. Se detecta que pagó exportaciones e importaciones de bienes mediante transferencias en cuentas bancarias no registradas en el exterior, sin canalizar las divisas a través del mercado cambiario (Intermediarios del Mercado Cambiario - IMC o cuentas de compensación debidamente registradas ante el Banco de la República).',
        stem: 'Atendiendo a la normativa cambiaria colombiana (Circular Externa DCIN-83 y Decreto Ley 2245 de 2011), al Analista III le corresponde identificar que la conducta investigada constituye:',
        options: [
          {
            key: 'A',
            text: 'una infracción al régimen cambiario por indebida canalización de divisas de operaciones obligatoriamente canalizables, susceptible de pliego de cargos y sanción pecuniaria por parte de la DIAN.',
            is_correct: true,
            justification: 'Es correcta. El pago de importaciones y exportaciones de bienes hace parte del mercado cambiario obligatorio (Art. 41 Resolución Externa 1 de 2018 Banco de la República). Su pago por fuera del mecanismo autorizado constituye infracción cambiaria sancionada por la DIAN bajo el Decreto Ley 2245 de 2011.'
          },
          {
            key: 'B',
            text: 'una operación mercantil enteramente libre y desregulada que no genera ninguna consecuencia administrativa ni fiscal.',
            is_correct: false,
            justification: 'Es incorrecta. El comercio exterior de bienes es una operación obligatoriamente canalizable sujeta al estricto control cambiario ejercido por la DIAN en materia tributaria, aduanera y de divisas.'
          },
          {
            key: 'C',
            text: 'una falta disciplinaria exclusiva de la Junta Directiva del Banco de la República que excluye toda competencia de la DIAN.',
            is_correct: false,
            justification: 'Es incorrecta. El Decreto Ley 2245 de 2011 y el Decreto Ley 927 de 2023 establecen con claridad meridiana que la DIAN es la autoridad competente para investigar y sancionar las infracciones al régimen cambiario en materia de comercio exterior y operaciones conexas.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto Ley 2245 de 2011; Resolución Externa 1 de 2018 BanRep; Circular Reglamentaria Externa DCIN-83.',
        explanation_summary: 'Las operaciones de importación de bienes deben canalizarse a través de los IMC o cuentas de compensación; su inobservancia genera proceso sancionatorio cambiario a cargo de la DIAN.'
      },
      {
        thematic_axis: 'Régimen Cambiario y Detección de Lavado de Activos (UIAF / ROS)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Durante la recopilación de información preliminar de fiscalización (Función 2 del empleo), el Analista III evidencia que una sociedad recién constituida, sin empleados registrados ni capacidad operativa conocida, realizó transacciones financieras internacionales fraccionadas en montos atípicos que no guardan relación con su objeto social ni con las declaraciones tributarias radicadas. El caso presenta señales de alerta vinculadas a posible lavado de activos.',
        stem: 'Conforme a los procedimientos institucionales de la DIAN y la función esencial 1 del empleo, la actuación que debe realizarse internamente es:',
        options: [
          {
            key: 'A',
            text: 'elaborar el informe técnico reservado para remitir el Reporte de Operaciones Sospechosas (ROS) a través del enlace institucional competente hacia la Unidad de Información y Análisis Financiero (UIAF).',
            is_correct: true,
            justification: 'Es correcta. De acuerdo con la Ley 526 de 1999, el Estatuto Orgánico del Sistema Financiero y el manual del empleo del Analista III (Función 1), cuando se identifiquen operaciones no usuales o injustificadas con señales de lavado de activos, debe gestionarse de forma reservada el Reporte de Operación Sospechosa (ROS) ante la UIAF.'
          },
          {
            key: 'B',
            text: 'comunicar al contribuyente por llamada telefónica informal que ha sido detectado como sospechoso para pedirle explicaciones previas.',
            is_correct: false,
            justification: 'Es incorrecta. Las leyes sobre prevención de lavado de activos (SARLAFT/UIAF) prohíben expresamente alertar al sujeto reportado ("tipping off"), dado que ello frustraría las labores de inteligencia e investigación criminal.'
          },
          {
            key: 'C',
            text: 'ignorar el hallazgo argumentando que la DIAN solamente se ocupa de la aritmética de los impuestos y no del lavado de activos.',
            is_correct: false,
            justification: 'Es incorrecta. El propósito principal del cargo Analista III (Ficha FT-TAH-1824) incluye taxativamente la "detección de lavado de activos y financiación del terrorismo" en coordinación con los órganos de control.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 526 de 1999; Ley 1121 de 2006; Manual Específico de Funciones DIAN Ficha AT-FL-2011 Función 1.',
        explanation_summary: 'El reporte de operaciones sospechosas (ROS) es una obligación funcional que debe cursarse con estricta reserva legal hacia la UIAF.'
      },

      // --- EJE: Procedimiento Administrativo General (CPACA Ley 1437) ---
      {
        thematic_axis: 'Procedimiento Administrativo General (CPACA Ley 1437 de 2011)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Un ciudadano radica a través del portal de la DIAN un derecho de petición en modalidad de consulta técnica solicitando conocer el procedimiento para acogerse a un beneficio tributario. El documento fue radicado el 2 de octubre. El funcionario encargado de la correspondencia consulta al Analista III cuál es el plazo legal que tiene la entidad para responder dicha consulta.',
        stem: 'De conformidad con el Código de Procedimiento Administrativo y de lo Contencioso Administrativo (Ley 1437 de 2011, Art. 14, sustituido por Ley 1755 de 2015), el término legal para resolver peticiones mediante las cuales se formula una consulta es de:',
        options: [
          {
            key: 'A',
            text: 'treinta (30) días siguientes a su recepción.',
            is_correct: true,
            justification: 'Es correcta. El artículo 14 de la Ley 1437 de 2011 (sustituido por la Ley 1755 de 2015) establece expresamente que las peticiones mediante las cuales se formula una consulta a las autoridades en relación con las materias a su cargo deberán resolverse dentro de los treinta (30) días siguientes a su recepción.'
          },
          {
            key: 'B',
            text: 'quince (15) días hábiles en todos los casos de manera uniforme.',
            is_correct: false,
            justification: 'Es incorrecta. El término de 15 días es la regla general para peticiones de interés general o particular, pero las consultas técnicas gozan del término especial de 30 días.'
          },
          {
            key: 'C',
            text: 'diez (10) días hábiles improrrogables.',
            is_correct: false,
            justification: 'Es incorrecta. El término de diez (10) días aplica para peticiones de información y de documentos (Art. 14 num. 1 Ley 1755 de 2015).'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 1437 de 2011, Art. 14 (sustituido por Ley 1755 de 2015, Art. 1).',
        explanation_summary: 'Las peticiones de consulta ante las autoridades tienen un plazo legal de 30 días hábiles para su debida respuesta técnica.'
      },
      {
        thematic_axis: 'Procedimiento Administrativo General (CPACA Ley 1437 de 2011)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'La DIAN profiere un acto administrativo de carácter sancionatorio. El administrado interpone recurso de reposición en debida forma. Al cabo de dos meses de radicado el recurso, el contribuyente acude a la oficina para indagar si el acto ya quedó en firme o si puede demandar ante la jurisdicción contenciosa.',
        stem: 'En el procedimiento administrativo colombiano (Ley 1437 de 2011, Art. 87), un acto administrativo queda en firme cuando:',
        options: [
          {
            key: 'A',
            text: 'contra él no procedan recursos, o vencido el término para interponerlos no se hayan ejercido, o cuando los recursos interpuestos se hayan decidido formalmente.',
            is_correct: true,
            justification: 'Es correcta. El artículo 87 del CPACA enumera taxativamente los casos de firmeza de los actos administrativos: 1. Cuando contra ellos no proceda ningún recurso; 2. Cuando venzan los plazos para interponerlos sin haberlos interpuesto; 3. Al día siguiente a la notificación de la decisión sobre los recursos; 4. Cuando se desistan, etc.'
          },
          {
            key: 'B',
            text: 'se proyecta la minuta interna por el funcionario sustanciador antes de su firma y notificación.',
            is_correct: false,
            justification: 'Es incorrecta. La proyección interna es un simple acto preparatorio sin eficacia jurídica ni efectos vinculantes para las partes.'
          },
          {
            key: 'C',
            text: 'solamente transcurridos 10 años desde su expedición por prescripción judicial.',
            is_correct: false,
            justification: 'Es incorrecta. La firmeza del acto administrativo ocurre en sede gubernativa una vez agotados los recursos ordinarios o precluidos los términos legales.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 1437 de 2011 (CPACA), Art. 87.',
        explanation_summary: 'La firmeza de los actos administrativos se consolida cuando se agotan o fenecen las oportunidades legales de impugnación en sede administrativa.'
      },

      // --- EJE: Conceptos de Evasión, Elusión, Contrabando y Ley de Transparencia ---
      {
        thematic_axis: 'Conceptos de Evasión, Elusión, Contrabando y Ley de Transparencia (Ley 1712)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En una capacitación interna del área de fiscalización, se discute la diferencia técnico-jurídica entre los fenómenos de evasión fiscal y elusión fiscal para estructurar correctamente los pliegos de cargos en investigaciones tributarias. Se pide al Analista III clarificar el concepto dogmático de elusión fiscal.',
        stem: 'Desde la perspectiva doctrinal y del ordenamiento tributario colombiano, la elusión fiscal se caracteriza por:',
        options: [
          {
            key: 'A',
            text: 'la utilización de vacíos, artificios o formas jurídicas atípicas e impropias con el propósito primordial de reducir, eliminar o diferir la carga tributaria sin violar de manera directa y frontal la letra formal de la ley, pero desnaturalizando su espíritu (abuso en materia tributaria).',
            is_correct: true,
            justification: 'Es correcta. El artículo 869 del Estatuto Tributario define la elusión/abuso tributario como la configuración de operaciones artificiosas sin propósito económico o comercial legítimo que buscan una ventaja tributaria. A diferencia de la evasión (que es la violación directa y flagrante de la ley, ej. ocultar ingresos o falsificar facturas), la elusión manipula las formas legales.'
          },
          {
            key: 'B',
            text: 'la falsificación dolosa de facturas y la ocultación física de mercancías extranjeras en puertos y aeropuertos.',
            is_correct: false,
            justification: 'Es incorrecta. Ocultar mercancías o falsificar facturas es evasión tributaria y contrabando, conductas ilegales de comisión directa y tipificadas como delitos penales.'
          },
          {
            key: 'C',
            text: 'el pago voluntario y anticipado de impuestos para obtener los descuentos de pronto pago establecidos por ley.',
            is_correct: false,
            justification: 'Es incorrecta. El pago anticipado con beneficio legal es economía de opción o incentivo tributario plenamente legítimo tutelado por el legislador.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Arts. 869, 869-1 y 869-2; Jurisprudencia Consejo de Estado.',
        explanation_summary: 'La elusión fiscal implica el uso artificioso de figuras legales para disminuir la tributación sin justificación económica real, enmarcado en el abuso tributario.'
      },
      {
        thematic_axis: 'Conceptos de Evasión, Elusión, Contrabando y Ley de Transparencia (Ley 1712)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Un periodista solicita a la DIAN mediante derecho de petición que se le remita copia íntegra de las declaraciones de renta de los últimos 5 años de un grupo de empresarios reconocidos para realizar una publicación mediática. El funcionario a cargo consulta el principio de reserva tributaria y la Ley 1712 de 2014 (Ley de Transparencia y del Derecho de Acceso a la Información Pública Nacional).',
        stem: 'Frente a dicha solicitud de información confidencial, la entidad pública debe:',
        options: [
          {
            key: 'A',
            text: 'negar la entrega de las declaraciones de renta invocando expresamente la excepción legal de información reservada tutelada por el artículo 583 del Estatuto Tributario y el artículo 19 de la Ley 1712 de 2014.',
            is_correct: true,
            justification: 'Es correcta. El artículo 583 del E.T. consagra la reserva de las declaraciones tributarias, y el artículo 19 de la Ley 1712 de 2014 señala que el derecho de acceso a la información pública no procede frente a información exceptuada por daño a los derechos a la privacidad, intimidad o amparada por reserva tributaria expresa.'
          },
          {
            key: 'B',
            text: 'entregar toda la información de inmediato aplicando el principio de máxima publicidad sin considerar la reserva tributaria.',
            is_correct: false,
            justification: 'Es incorrecta. El principio de máxima publicidad no es absoluto; está sujeto a los límites constitucionales y legales de reserva de datos personales, comerciales y tributarios.'
          },
          {
            key: 'C',
            text: 'subir los archivos a la página web institucional en datos abiertos para consulta pública de todos los ciudadanos.',
            is_correct: false,
            justification: 'Es incorrecta. La divulgación de declaraciones tributarias amparadas por reserva legal viola el secreto fiscal y genera responsabilidad disciplinaria y penal (violación de secretos o reserva documental).'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 1712 de 2014, Arts. 18 y 19; Estatuto Tributario, Art. 583.',
        explanation_summary: 'Las declaraciones tributarias se encuentran amparadas por reserva legal expresa (Art. 583 E.T.), prevaleciendo como excepción al principio general de acceso a la información pública.'
      },

      // --- EJE: Gestión Documental y Modelo MIPG ---
      {
        thematic_axis: 'Gestión Documental y Modelo MIPG',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En el subproceso de fiscalización y liquidación se reciben múltiples expedientes híbridos (documentos electrónicos y anexos físicos) procedentes de denuncias de contrabando. Como parte de la función 6 del empleo ("Realizar la gestión documental relacionada con trámites, solicitudes técnicas, denuncias y reportes..."), el Analista III debe garantizar la conformación y trazabilidad de los expedientes.',
        stem: 'Conforme a la Ley General de Archivos (Ley 594 de 2000) y las directrices del Archivo General de la Nación (AGN), la organización técnica de un expediente administrativo exige:',
        options: [
          {
            key: 'A',
            text: 'la foliación consecutiva y cronológica de las actuaciones, el respeto al principio de procedencia y orden original, y la adecuada vinculación en el índice electrónico del sistema de gestión documental.',
            is_correct: true,
            justification: 'Es correcta. La Ley 594 de 2000 y el Acuerdo 002 de 2014 del AGN disponen que los expedientes deben organizarse respetando el principio de orden original y procedencia, con foliación exacta (o índice electrónico con hash de integridad en expedientes electrónicos) para preservar la cadena de custodia y el debido proceso probatorio.'
          },
          {
            key: 'B',
            text: 'mezclar los documentos de distintos investigados en una sola carpeta general ordenada alfabéticamente por el nombre del funcionario sustanciador.',
            is_correct: false,
            justification: 'Es incorrecta. Mezclar actuaciones de distintos sujetos viola el principio de procedencia y orden original de la archivística pública y rompe la reserva del sumario tributario.'
          },
          {
            key: 'C',
            text: 'eliminar todos los documentos físicos apenas se escanean sin contar con concepto del comité institucional de gestión y desempeño ni tablas de retención.',
            is_correct: false,
            justification: 'Es incorrecta. La eliminación de documentos públicos está reglada por las Tablas de Retención Documental (TRD) y las directrices de conservación total o microfilmación certificada; no puede realizarse arbitrariamente.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 594 de 2000 (Ley General de Archivos); Acuerdo AGN 002 de 2014; MIPG Dimensión Gestión con Valores.',
        explanation_summary: 'La conformación del expediente administrativo debe acatar los principios de orden original, procedencia y foliación cronológica para salvaguardar la validez probatoria.'
      },
      {
        thematic_axis: 'Gestión Documental y Modelo MIPG',
        question_type: 'situacional',
        difficulty: 'básico',
        case_text: 'La DIAN realiza la autoevaluación anual de su desempeño institucional en el marco del Modelo Integrado de Planeación y Gestión (MIPG). Al equipo de trabajo técnico se le solicita identificar en cuál de las 7 dimensiones del MIPG se articulan las directrices sobre control interno, gestión del riesgo y líneas de defensa.',
        stem: 'En la estructura del MIPG (Decreto 1499 de 2017), el control interno y la administración del riesgo corresponden a la dimensión denominada:',
        options: [
          {
            key: 'A',
            text: 'Control Interno (Séptima Dimensión del MIPG - Modelo Estándar de Control Interno MECI).',
            is_correct: true,
            justification: 'Es correcta. La séptima dimensión del MIPG es el Control Interno, que opera de manera transversal mediante la actualización del Modelo Estándar de Control Interno (MECI) a través del esquema de líneas de defensa para asegurar el cumplimiento de objetivos institucionales.'
          },
          {
            key: 'B',
            text: 'Talento Humano exclusivamente.',
            is_correct: false,
            justification: 'Es incorrecta. Talento Humano es la primera dimensión (el corazón del modelo), enfocada en el ingreso, desarrollo y retiro del personal, no en las líneas de control interno.'
          },
          {
            key: 'C',
            text: 'Gestión del Conocimiento y la Innovación.',
            is_correct: false,
            justification: 'Es incorrecta. La dimensión de Gestión del Conocimiento se enfoca en la retención del saber institucional y la innovación, mientras que el control del riesgo se ubica en Control Interno.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto 1499 de 2017; Manual Operativo del MIPG versión 4, DAFP.',
        explanation_summary: 'El Control Interno es la 7ª dimensión transversal del MIPG que estructura las políticas del MECI y la administración del riesgo en las entidades públicas.'
      },

      // --- EJE: Competencias Comportamentales (Likert) ---
      {
        thematic_axis: 'Competencias Comportamentales (Nivel Técnico)',
        question_type: 'likert',
        difficulty: 'básico',
        case_text: 'Evaluación de Competencia: Comportamiento Ético (Nivel 4). En situaciones laborales donde se presentan presiones por parte de usuarios o terceros para agilizar trámites contraviniendo el turno riguroso de radicación.',
        stem: 'Rechazo de manera inmediata cualquier sugerencia o dádiva que busque alterar el orden legal de atención o priorizar expedientes sin justificación normativa.',
        options: [
          { key: 'A', text: 'Totalmente en desacuerdo', is_correct: false, justification: 'Nivel bajo de alineación ética.' },
          { key: 'B', text: 'En desacuerdo', is_correct: false, justification: 'Inconsistente con el principio de probidad pública.' },
          { key: 'C', text: 'De acuerdo', is_correct: false, justification: 'Alineación favorable estándar.' },
          { key: 'D', text: 'Totalmente de acuerdo', is_correct: true, justification: 'Demuestra el nivel máximo esperado de integridad y apego al principio de legalidad e imparcialidad (Nivel 4).' }
        ],
        correct_option: 'D',
        legal_basis: 'Manual de Funciones DIAN; Código de Integridad DIAN.',
        explanation_summary: 'Mide la firmeza y apego incorruptible a las normas y principios rectores de la administración pública.'
      },
      {
        thematic_axis: 'Competencias Comportamentales (Nivel Técnico)',
        question_type: 'likert',
        difficulty: 'básico',
        case_text: 'Evaluación de Competencia: Adaptabilidad y Trabajo en Equipo (Nivel 2). Frente a la implementación obligatoria de una nueva plataforma tecnológica para la trazabilidad digital de expedientes en el área de fiscalización.',
        stem: 'Asumo con disposición constructiva la transición hacia nuevos sistemas digitales y colaboro activamente con mis compañeros para solventar las dificultades operativas de la herramienta.',
        options: [
          { key: 'A', text: 'Totalmente en desacuerdo', is_correct: false, justification: 'Resistencia al cambio organizacional.' },
          { key: 'B', text: 'En desacuerdo', is_correct: false, justification: 'Baja colaboración con el equipo de trabajo.' },
          { key: 'C', text: 'De acuerdo', is_correct: false, justification: 'Aceptable disposición.' },
          { key: 'D', text: 'Totalmente de acuerdo', is_correct: true, justification: 'Evidencia capacidad de adaptación al cambio tecnológico y sinergia colaborativa en el equipo (Nivel 2).' }
        ],
        correct_option: 'D',
        legal_basis: 'Decreto 815 de 2018; Manual de Competencias Comportamentales Sector Público.',
        explanation_summary: 'Refleja proactividad, aprendizaje continuo y espíritu de equipo frente a la modernización tecnológica.'
      },

      // --- EJE: Código de Integridad y Ética Pública DIAN ---
      {
        thematic_axis: 'Código de Integridad y Ética Pública DIAN',
        question_type: 'likert',
        difficulty: 'básico',
        case_text: 'Evaluación del Valor: Diligencia y Servicio al Ciudadano. Cuando un usuario externo acude con inquietudes sobre una citación de fiscalización.',
        stem: 'Atiendo con respeto, claridad y orientación pedagógica a los ciudadanos, facilitando el entendimiento de sus deberes y derechos sin usar tecnicismos innecesarios.',
        options: [
          { key: 'A', text: 'Totalmente en desacuerdo', is_correct: false, justification: 'Contrario a la vocación de servicio público.' },
          { key: 'B', text: 'En desacuerdo', is_correct: false, justification: 'Falta de empatía institucional.' },
          { key: 'C', text: 'De acuerdo', is_correct: false, justification: 'Nivel regular de servicio.' },
          { key: 'D', text: 'Totalmente de acuerdo', is_correct: true, justification: 'Conducta plenamente alineada con el valor de la Diligencia y la vocación de servicio del Código de Integridad DIAN.' }
        ],
        correct_option: 'D',
        legal_basis: 'Código de Integridad de la DIAN (Resolución DIAN 059 de 2017).',
        explanation_summary: 'La diligencia y el respeto al ciudadano representan pilares fundamentales del servidor de la DIAN.'
      },
      {
        thematic_axis: 'Código de Integridad y Ética Pública DIAN',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'Un servidor público de la DIAN en ejercicio de sus funciones de fiscalización descubre que la empresa de su hermano político (cuñado) está siendo objeto de una auditoría por omisión de ingresos en su misma seccional. El superior jerárquico desconoce dicho vínculo de parentesco.',
        stem: 'De conformidad con el Código de Integridad, la Ley 1952 de 2019 (Código General Disciplinario) y el CPACA, la obligación legal del servidor público es:',
        options: [
          {
            key: 'A',
            text: 'declararse inmediatamente impedido por escrito ante su superior jerárquico manifestando la causal de conflicto de interés por parentesco de afinidad, absteniéndose de conocer o intervenir en el expediente.',
            is_correct: true,
            justification: 'Es correcta. El artículo 11 y 12 del CPACA (Ley 1437 de 2011) y la Ley 1952 de 2019 establecen la obligación estricta de todo servidor público de declararse impedido cuando concurra conflicto de interés, parentesco dentro del segundo grado de afinidad o razones que afecten su imparcialidad.'
          },
          {
            key: 'B',
            text: 'continuar con el caso en secreto para garantizar que no le cobren impuestos excesivos a su familiar.',
            is_correct: false,
            justification: 'Es incorrecta. Actuar en beneficio de familiares configura una falta disciplinaria gravísima y los delitos de concusión, prevaricato o interés indebido en la celebración de contratos/actuaciones oficiales.'
          },
          {
            key: 'C',
            text: 'archivar el expediente de inmediato argumentando que no tiene tiempo disponible en su jornada.',
            is_correct: false,
            justification: 'Es incorrecta. Archivar indebidamente una actuación por interés personal constituye un delito contra la administración pública y flagrante violación del régimen ético.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 1437 de 2011, Arts. 11 y 12; Ley 1952 de 2019 (Código General Disciplinario), Art. 40.',
        explanation_summary: 'El conflicto de intereses exige la manifestación inmediata del impedimento para salvaguardar la moralidad y la imparcialidad administrativa.'
      }
    ];

    // Más preguntas situacionales especializadas para enriquecer el banco con temas clave de Analista III
    const additionalSituational = [
      {
        thematic_axis: 'Determinación y Control Tributario (Fiscalización y Liquidación)',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'Un contribuyente es objeto de investigación tributaria por el impuesto sobre la renta del año gravable 2024. La DIAN le notifica un Emplazamiento para Corregir señalándole una presunta inexactitud en la deducción de salarios por no acreditar el pago de aportes parafiscales. El contribuyente decide no corregir su declaración dentro del mes siguiente a la notificación del emplazamiento.',
        stem: 'Al no responder afirmativamente el contribuyente al emplazamiento para corregir, la consecuencia legal procesal que sigue es que:',
        options: [
          {
            key: 'A',
            text: 'la DIAN puede proferir el Requerimiento Especial, y de mantenerse la inexactitud en la liquidación oficial, la sanción de inexactitud no se beneficiará de las tarifas reducidas previas.',
            is_correct: true,
            justification: 'Es correcta. De acuerdo con el artículo 685 y 703 del Estatuto Tributario, el emplazamiento para corregir no es obligatorio para proferir el requerimiento especial, pero si se formula y no se corrige, la administración continuará con el requerimiento especial aplicando las sanciones plenas del régimen de inexactitud.'
          },
          {
            key: 'B',
            text: 'se entiende perdonada la inconsistencia y precluye toda oportunidad de fiscalización para la administración.',
            is_correct: false,
            justification: 'Es incorrecta. El silencio del contribuyente frente al emplazamiento para corregir de ninguna manera extingue la facultad de fiscalización ni perdona la obligación tributaria insatisfecha.'
          },
          {
            key: 'C',
            text: 'el contribuyente pierde automáticamente el derecho a nombrar apoderado judicial en cualquier etapa procesal.',
            is_correct: false,
            justification: 'Es incorrecta. El derecho de postulación y representación judicial está garantizado constitucionalmente y no se pierde por omitir una corrección voluntaria.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario Nacional, Arts. 685, 703 y 709.',
        explanation_summary: 'El emplazamiento para corregir otorga la oportunidad de enmendar; de no hacerlo, la DIAN prosigue con el requerimiento especial bajo el régimen sancionatorio ordinario.'
      },
      {
        thematic_axis: 'Fiscalización Aduanera y Destrucción de Mercancías',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'En una diligencia de inspección previa aduanera solicitada por el declarante antes de la presentación de la declaración de importación (Art. 52 Decreto 1165 de 2019), el importador descubre que la mercancía recibida en el depósito aduanero difiere en marca y modelo respecto a lo consignado en los documentos de transporte.',
        stem: 'Frente a esta discrepancia hallada durante la inspección previa, el procedimiento aduanero legal que debe seguir el usuario con apoyo técnico de la autoridad es:',
        options: [
          {
            key: 'A',
            text: 'declarar las mercancías encontradas en la diligencia de inspección previa aportando los documentos de soporte correspondientes o reembarcarlas en los plazos previstos por la norma, sin que haya lugar a sanción o aprehensión por el solo hecho de la inspección previa.',
            is_correct: true,
            justification: 'Es correcta. El artículo 52 del Decreto 1165 de 2019 establece que la inspección previa tiene como objetivo verificar la descripción, cantidad, peso y estado de las mercancías. Las diferencias halladas durante esta inspección no dan lugar a decomiso si se subsanan mediante la declaración correcta o el reembarque dentro de los términos legales.'
          },
          {
            key: 'B',
            text: 'destruir inmediatamente la mercancía sin levantar acta y cobrarle una multa de 500 salarios mínimos al transportador.',
            is_correct: false,
            justification: 'Es incorrecta. La inspección previa es un derecho del importador para prevenir errores; no faculta la destrucción de plano de mercancías útiles y comerciales.'
          },
          {
            key: 'C',
            text: 'ocultar el hallazgo y declarar datos ficticios para evitar demoras en el despacho de aduana.',
            is_correct: false,
            justification: 'Es incorrecta. Declarar información contraria a la realidad en la declaración aduanera constituye infracción aduanera y causal de aprehensión por inexactitud.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Decreto 1165 de 2019, Art. 52 (Inspección previa de las mercancías).',
        explanation_summary: 'La inspección previa aduanera permite verificar mercancías antes de radicar la declaración y evita sanciones siempre que se declare lo verificado conforme al Art. 52.'
      },
      {
        thematic_axis: 'Régimen Cambiario y Detección de Lavado de Activos (UIAF / ROS)',
        question_type: 'situacional',
        difficulty: 'avanzado',
        case_text: 'Una empresa importadora compensa deudas con su proveedor en el exterior cruzando saldos de facturas de exportación sin registrar una cuenta de compensación ante el Banco de la República. El Analista III recibe el requerimiento de información cambiaria para proyectar el pliego de cargos.',
        stem: 'De acuerdo con el régimen cambiario colombiano, la figura de compensación de deudas por operaciones de comercio exterior:',
        options: [
          {
            key: 'A',
            text: 'está prohibida como regla general a menos que se realice a través de cuentas de compensación debidamente registradas y reportadas conforme a los mecanismos del Banco de la República.',
            is_correct: true,
            justification: 'Es correcta. El régimen cambiario colombiano prohíbe la extinción de obligaciones cambiarias mediante compensación directa privada entre particulares si no se utiliza el mecanismo legal de cuentas de compensación registradas con informe mensual de movimientos (Circular DCIN-83).'
          },
          {
            key: 'B',
            text: 'es totalmente libre entre particulares y extingue automáticamente las obligaciones sin informar al Banco de la República.',
            is_correct: false,
            justification: 'Es incorrecta. Las divisas de comercio exterior son de obligatoria canalización; la compensación privada sin canalización viola el régimen cambiario.'
          },
          {
            key: 'C',
            text: 'solo es válida si se paga en efectivo en billetes extranjeros entregados físicamente en la frontera.',
            is_correct: false,
            justification: 'Es incorrecta. El pago en efectivo en frontera por operaciones de comercio exterior ordinarias es contrario a las normas de canalización cambiaria y genera riesgo de lavado de activos.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Resolución Externa 1 de 2018 BanRep, Art. 55; Decreto Ley 2245 de 2011.',
        explanation_summary: 'La compensación de deudas de comercio exterior requiere el uso estricto de cuentas de compensación registradas para garantizar la trazabilidad macroeconómica.'
      },
      {
        thematic_axis: 'Procedimiento Administrativo General (CPACA Ley 1437 de 2011)',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'La DIAN expidió una resolución sancionatoria en materia aduanera y remitió la citación para notificación personal por correo electrónico a la dirección registrada en el RUT. El administrado no comparece ni abre el correo en los cinco días siguientes.',
        stem: 'Según el artículo 69 del CPACA y las normas especiales de notificación electrónica tributaria (Art. 566-1 E.T.), la notificación del acto administrativo:',
        options: [
          {
            key: 'A',
            text: 'se entenderá surtida para todos los efectos legales en los términos previstos por la norma especial (a los cinco días hábiles siguientes al acuse o envío del correo electrónico), garantizando la eficacia del acto.',
            is_correct: true,
            justification: 'Es correcta. El artículo 566-1 del Estatuto Tributario y el CPACA prevén que la notificación electrónica surte plenos efectos transcurrido el término de cinco (5) días hábiles siguientes a la entrega o depósito del correo en la bandeja del destinatario registrado.'
          },
          {
            key: 'B',
            text: 'queda anulada y el funcionario debe viajar personalmente al domicilio del investigado para leerle el texto en voz alta.',
            is_correct: false,
            justification: 'Es incorrecta. La notificación electrónica es un medio principal y vinculante expresamente avalado por la ley procesal y la Corte Constitucional.'
          },
          {
            key: 'C',
            text: 'se considera inexistente y se debe esperar indefinidamente a que el ciudadano decida comunicarse voluntariamente.',
            is_correct: false,
            justification: 'Es incorrecta. El procedimiento administrativo está regido por el principio de celeridad y eficacia; los plazos de notificación ficta o por aviso surten plenos efectos legales.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estatuto Tributario, Art. 566-1; Ley 1437 de 2011, Arts. 56 y 69.',
        explanation_summary: 'La notificación electrónica al correo registrado en el RUT surte plenos efectos legales en el plazo fijado por el Art. 566-1 del E.T.'
      },
      {
        thematic_axis: 'Gestión Documental y Modelo MIPG',
        question_type: 'situacional',
        difficulty: 'medio',
        case_text: 'El Analista III debe proyectar un acto administrativo técnico de trámite dentro de un proceso de investigación de contrabando (Función 7 del empleo). El documento contiene datos sensibles de terceros y soportes contables clasificados. El funcionario debe cargar el documento en el sistema de gestión documental de la DIAN.',
        stem: 'Para cumplir con los estándares de seguridad de la información y la gestión documental institucional, el servidor debe:',
        options: [
          {
            key: 'A',
            text: 'clasificar el documento con el nivel de seguridad correspondiente según las Tablas de Retención Documental y la política de seguridad digital, asegurando su trazabilidad mediante el radicado oficial y restringiendo accesos no autorizados.',
            is_correct: true,
            justification: 'Es correcta. Conforme a la Ley 594 de 2000, la Ley 1712 de 2014 y las políticas de seguridad de la información de la DIAN bajo el MIPG, los actos con información reservada o sensible deben tener trazabilidad estricta y control de acceso por perfiles de usuario.'
          },
          {
            key: 'B',
            text: 'guardar el documento en su correo personal particular para revisarlo desde su teléfono durante el fin de semana sin radicación oficial.',
            is_correct: false,
            justification: 'Es incorrecta. Enviar información institucional reservada a correos personales constituye una falta disciplinaria y violación a la política de seguridad de la información y reserva fiscal.'
          },
          {
            key: 'C',
            text: 'dejar copias impresas en los pasillos de la entidad para que cualquier persona que pase pueda revisarlas libremente.',
            is_correct: false,
            justification: 'Es incorrecta. Viola abiertamente los principios de custodia documental, reserva legal y confidencialidad de las actuaciones públicas.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 594 de 2000; Ley 1712 de 2014; Política de Seguridad Digital de la DIAN.',
        explanation_summary: 'La custodia y radicación en el sistema oficial con niveles de clasificación protege la reserva y garantiza la validez jurídica de la prueba.'
      }
    ];

    const allQuestions = [...questionsData, ...additionalSituational];

    for (const q of allQuestions) {
      const axisId = axisMap[q.thematic_axis];
      await db.query(
        `INSERT INTO questions (
          job_profile_id, thematic_axis_id, question_type, difficulty,
          case_text, stem, options, correct_option, legal_basis, explanation_summary
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)`,
        [
          analista3Id,
          axisId,
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

    await db.query('COMMIT');
    console.log(`--- Seeding finalizado con éxito: ${allQuestions.length} preguntas registradas para Analista III ---`);
  } catch (err) {
    await db.query('ROLLBACK');
    console.error('Error durante seeding:', err);
    process.exit(1);
  } finally {
    process.exit(0);
  }
}

seed();
