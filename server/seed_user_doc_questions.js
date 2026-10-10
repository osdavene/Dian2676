const db = require('./db');

async function seedDocQuestions() {
  console.log('--- Incorporando preguntas reales de LA/FT desde C:\\Proyectos\\Dian2676\\Doc ---');

  try {
    const jobRes = await db.query("SELECT id FROM job_profiles WHERE code = 'AT-FL-2011'");
    if (jobRes.rows.length === 0) {
      console.error('Perfil Analista III no encontrado');
      return;
    }
    const analista3Id = jobRes.rows[0].id;

    // Buscar o verificar el eje temático de Lavado de Activos y Régimen Cambiario
    let axisRes = await db.query(
      "SELECT id FROM thematic_axes WHERE job_profile_id = $1 AND name LIKE '%Lavado de Activos%'",
      [analista3Id]
    );
    let axisId = axisRes.rows[0]?.id;

    if (!axisId) {
      const allAxes = await db.query('SELECT id, name FROM thematic_axes WHERE job_profile_id = $1 LIMIT 1', [analista3Id]);
      axisId = allAxes.rows[0].id;
    }

    const docQuestions = [
      // ================= CASO 1 =================
      {
        case_text: 'Un funcionario que ejerce labores de inspección y vigilancia en una entidad pública recibe un informe sobre una empresa del sector real que ha comenzado a realizar transacciones financieras inusuales. El documento detalla que los recursos de la empresa son depositados en cuentas bancarias para luego ser transferidos rápidamente a otras instituciones financieras, buscando ocultar su rastro documentado y el verdadero origen de los fondos. Adicionalmente, el servidor público debe participar en una mesa de trabajo interinstitucional para mejorar el cruce de información dentro del modelo de gestión adoptado por el Estado, el cual busca una mayor efectividad en la prevención de estas conductas ilícitas de orden económico.',
        stem: '1. Frente a la etapa delictiva descrita en el informe donde los fondos se transfieren entre instituciones para disimular su fuente, el funcionario identifica que esto corresponde a:',
        options: [
          {
            key: 'A',
            text: 'la fase de ocultamiento en la que se aplican capas de transacciones financieras complejas.',
            is_correct: true,
            justification: 'Es correcta. En el ciclo de lavado de activos (Colocación, Ocultamiento o Estratificación, e Integración), una vez los dineros ya fueron ingresados a cuentas bancarias, su transferencia sucesiva y veloz entre entidades financieras para desvanecer el rastro documental y camuflar su procedencia ilícita configura técnicamente la fase de ocultamiento (layering).'
          },
          {
            key: 'B',
            text: 'la etapa de colocación inicial mediante la introducción de capitales ilegales en la economía.',
            is_correct: false,
            justification: 'Es incorrecta. La colocación ocurre en el primer momento en que el efectivo de origen criminal entra físicamente al sistema financiero o comercial, mientras que en este caso los fondos ya depositados se están triangulando.'
          },
          {
            key: 'C',
            text: 'la consolidación de la integración de recursos regresándolos al mercado formal con fines lícitos.',
            is_correct: false,
            justification: 'Es incorrecta. La integración es la etapa final en la que los activos aparentemente limpios se incorporan definitivamente en la economía mediante compra de inmuebles, empresas o inversiones legales.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Estándares Internacionales GAFI (Recomendaciones contra LA/FT); Circular Externa UIAF; Código Penal Colombiano Art. 323.',
        explanation_summary: 'El fraccionamiento y triangulación entre bancos para borrar el rastro de fondos ya ingresados corresponde a la fase de ocultamiento o estratificación.'
      },
      {
        case_text: 'Un funcionario que ejerce labores de inspección y vigilancia en una entidad pública recibe un informe sobre una empresa del sector real que ha comenzado a realizar transacciones financieras inusuales. El documento detalla que los recursos de la empresa son depositados en cuentas bancarias para luego ser transferidos rápidamente a otras instituciones financieras, buscando ocultar su rastro documentado y el verdadero origen de los fondos. Adicionalmente, el servidor público debe participar en una mesa de trabajo interinstitucional para mejorar el cruce de información dentro del modelo de gestión adoptado por el Estado, el cual busca una mayor efectividad en la prevención de estas conductas ilícitas de orden económico.',
        stem: '2. En relación con la mesa de trabajo y el modelo de gestión para la inteligencia económica, el servidor debe tener en cuenta que este enfoque busca fundamentalmente:',
        options: [
          {
            key: 'A',
            text: 'operar de manera sistémica, amplia y bidireccional integrando nuevas fuentes de información y sectores.',
            is_correct: true,
            justification: 'Es correcta. El modelo de gestión de inteligencia económica estatal frente al LA/FT exige una visión integral y transversal que articule al sector financiero, al sector real (comercio exterior, aduanas, bienes raíces) y a las autoridades de inspección y control (DIAN, UIAF, superintendencias), generando flujos de información bidireccionales y colaborativos.'
          },
          {
            key: 'B',
            text: 'limitar la prevención al sector financiero mediante controles rígidos sobre transacciones de bajo valor.',
            is_correct: false,
            justification: 'Es incorrecta. Limitar el control exclusivamente al sector financiero crea vulnerabilidades críticas en el sector real y el comercio exterior, contraviniendo el principio de enfoque basado en riesgos.'
          },
          {
            key: 'C',
            text: 'garantizar la condena penal expedita mediante la recolección exclusiva de pruebas para la fiscalía.',
            is_correct: false,
            justification: 'Es incorrecta. El propósito de la inteligencia económica y administrativa no es asumir la función jurisdiccional o penal de la Fiscalía, sino prevenir, detectar y disuadir el fenómeno delictivo mediante análisis estratégico y reportes oportunos.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 526 de 1999 (Estatuto Orgánico UIAF); Decreto Ley 927 de 2023 (Estructura DIAN).',
        explanation_summary: 'La inteligencia económica contra el LA/FT se fundamenta en un modelo sistémico, amplio y bidireccional con múltiples sectores de la economía.'
      },
      {
        case_text: 'Un funcionario que ejerce labores de inspección y vigilancia en una entidad pública recibe un informe sobre una empresa del sector real que ha comenzado a realizar transacciones financieras inusuales. El documento detalla que los recursos de la empresa son depositados en cuentas bancarias para luego ser transferidos rápidamente a otras instituciones financieras, buscando ocultar su rastro documentado y el verdadero origen de los fondos. Adicionalmente, el servidor público debe participar en una mesa de trabajo interinstitucional para mejorar el cruce de información dentro del modelo de gestión adoptado por el Estado, el cual busca una mayor efectividad en la prevención de estas conductas ilícitas de orden económico.',
        stem: '3. Para comprender el contexto de la economía criminal y sus efectos perjudiciales sobre el Estado, el funcionario debe considerar que estas prácticas ilícitas generan principalmente:',
        options: [
          {
            key: 'A',
            text: 'el aumento desproporcionado de las exportaciones formales y una mayor rentabilidad del mercado nacional.',
            is_correct: false,
            justification: 'Es incorrecta. La economía criminal no impulsa la competitividad exportadora formal; fomenta el contrabando y la informalidad comercial.'
          },
          {
            key: 'B',
            text: 'una distorsión en la inversión, el ahorro y un incremento artificial en los precios.',
            is_correct: true,
            justification: 'Es correcta. Los flujos de dineros ilícitos distorsionan gravemente los mercados económicos: inflan artificialmente los precios de bienes raíces y productos de consumo, generan competencia desleal contra las empresas formales cumplidoras y desincentivan el ahorro productivo de la sociedad.'
          },
          {
            key: 'C',
            text: 'la estabilidad temporal de las tasas de interés y el fortalecimiento de la recaudación.',
            is_correct: false,
            justification: 'Es incorrecta. El lavado de activos desestabiliza el recaudo tributario legítimo y genera volatilidad monetaria y financiera en los mercados.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Doctrina Económica del Banco de la República; Estudios Económicos de la UIAF y Naciones Unidas (UNODC).',
        explanation_summary: 'El lavado de activos genera distorsión en el ahorro y la inversión, además de un incremento artificial de precios que perjudica al mercado formal.'
      },

      // ================= CASO 2 =================
      {
        case_text: 'En el marco de sus funciones administrativas, un servidor público recibe el reporte interno de una compañía que comercializa bienes de alto valor. La empresa señala que varios clientes de estratos bajos, sin solvencia económica aparente, han adquirido productos costosos pagando con dinero en efectivo de forma fraccionada. Esta situación atípica ha superado los parámetros de normalidad del sector. El funcionario debe orientar a la compañía sobre la correcta tipificación de esta actividad y el procedimiento adecuado para informar al organismo de inteligencia correspondiente, garantizando el sigilo de la información y la protección del tejido empresarial frente al crimen organizado.',
        stem: '4. Al clasificar el comportamiento de los compradores que realizan transacciones atípicas que no pueden ser razonablemente justificadas, el servidor determina que se trata de:',
        options: [
          {
            key: 'A',
            text: 'una operación sospechosa que amerita ser informada de manera inmediata al organismo de inteligencia.',
            is_correct: true,
            justification: 'Es correcta. Una operación inusual que no encuentra justificación económica, financiera o comercial razonable adquiere formalmente el carácter de Operación Sospechosa, debiendo ser reportada de inmediato a la UIAF mediante el Reporte de Operaciones Sospechosas (ROS).'
          },
          {
            key: 'B',
            text: 'una simple operación inusual que debe archivarse tras solicitar una explicación verbal al usuario.',
            is_correct: false,
            justification: 'Es incorrecta. Si la operación atípica no puede ser razonablemente justificada, no puede ser archivada por la empresa; omitir el reporte configura incumplimiento grave de las normas antilavado.'
          },
          {
            key: 'C',
            text: 'un acto delictivo comprobado que exige la detención preventiva de los implicados en ventanilla.',
            is_correct: false,
            justification: 'Es incorrecta. La entidad comercial o los funcionarios administrativos no tienen atribuciones judiciales de captura en flagrancia por la sola sospecha financiera; su deber legal es reportar a la UIAF.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 526 de 1999, Art. 10; Estatuto Orgánico del Sistema Financiero Art. 102; Manual Analista III Función 1.',
        explanation_summary: 'Las operaciones inusuales no justificadas razonablemente se clasifican como operaciones sospechosas y deben remitirse a la UIAF.'
      },
      {
        case_text: 'En el marco de sus funciones administrativas, un servidor público recibe el reporte interno de una compañía que comercializa bienes de alto valor. La empresa señala que varios clientes de estratos bajos, sin solvencia económica aparente, han adquirido productos costosos pagando con dinero en efectivo de forma fraccionada. Esta situación atípica ha superado los parámetros de normalidad del sector. El funcionario debe orientar a la compañía sobre la correcta tipificación de esta actividad y el procedimiento adecuado para informar al organismo de inteligencia correspondiente, garantizando el sigilo de la información y la protección del tejido empresarial frente al crimen organizado.',
        stem: '5. Frente a la preocupación de la empresa por las posibles implicaciones legales de enviar este reporte a la autoridad competente, el funcionario aclara que la notificación:',
        options: [
          {
            key: 'A',
            text: 'mantiene estricta reserva y no constituye una denuncia penal ni genera responsabilidad para la entidad.',
            is_correct: true,
            justification: 'Es correcta. De conformidad con el artículo 11 de la Ley 526 de 1999 y el artículo 102 del EOSF, el Reporte de Operación Sospechosa (ROS) tiene reserva legal absoluta y su remisión no constituye denuncia penal ni genera responsabilidad civil, comercial o penal para la persona natural o jurídica reportante ni para sus directivos o empleados.'
          },
          {
            key: 'B',
            text: 'inicia automáticamente un proceso sancionatorio público contra el cliente por presunto enriquecimiento financiero ilícito.',
            is_correct: false,
            justification: 'Es incorrecta. El ROS no es público ni da apertura automática a sanciones; es insumo reservado de inteligencia financiera para análisis estratégico de la UIAF.'
          },
          {
            key: 'C',
            text: 'requiere pruebas contundentes del delito para evitar demandas por difamación comercial contra la empresa.',
            is_correct: false,
            justification: 'Es incorrecta. Para emitir un ROS no se requiere plena prueba del delito fuente (la sospecha fundada y objetiva es suficiente); además, la ley protege a la entidad frente a demandas por difamación.'
          }
        ],
        correct_option: 'A',
        legal_basis: 'Ley 526 de 1999, Art. 11 (Reserva Legal y Exoneración de Responsabilidad); Art. 102 EOSF.',
        explanation_summary: 'El ROS está cobijado por reserva legal y no genera responsabilidad civil o penal para quien reporta.'
      },
      {
        case_text: 'En el marco de sus funciones administrativas, un servidor público recibe el reporte interno de una compañía que comercializa bienes de alto valor. La empresa señala que varios clientes de estratos bajos, sin solvencia económica aparente, han adquirido productos costosos pagando con dinero en efectivo de forma fraccionada. Esta situación atípica ha superado los parámetros de normalidad del sector. El funcionario debe orientar a la compañía sobre la correcta tipificación de esta actividad y el procedimiento adecuado para informar al organismo de inteligencia correspondiente, garantizando el sigilo de la información y la protección del tejido empresarial frente al crimen organizado.',
        stem: '6. Para fortalecer los controles de la compañía a partir de las situaciones atípicas presentadas, el servidor público le recomienda implementar un catálogo de señales de alerta que sirva para:',
        options: [
          {
            key: 'A',
            text: 'reportar al supervisor a todos los clientes nuevos que operen exclusivamente mediante dinero en efectivo.',
            is_correct: false,
            justification: 'Es incorrecta. El uso de efectivo en sí mismo no implica reporte automático obligatorio de todo cliente sin análisis contextual de su actividad.'
          },
          {
            key: 'B',
            text: 'bloquear de inmediato cualquier transacción comercial que exceda los promedios transaccionales de la región.',
            is_correct: false,
            justification: 'Es incorrecta. Bloquear operaciones de plano sin análisis vulnera la libertad económica y las obligaciones contractuales mercantiles.'
          },
          {
            key: 'C',
            text: 'llamar la atención sobre un cliente o hecho que requiere un análisis detallado y riguroso.',
            is_correct: true,
            justification: 'Es correcta. Una señal de alerta es un indicador o detonante preventivo que advierte al personal sobre hechos o comportamientos atípicos que ameritan una debida diligencia intensificada o un análisis riguroso de la operación, antes de calificarla o descartarla.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Guías de Señales de Alerta de la UIAF; Circular Básica Jurídica Superintendencia de Sociedades (Capítulo X SAGRILAFT).',
        explanation_summary: 'Las señales de alerta son indicadores diseñados para activar el análisis detallado y riguroso de una operación atípica.'
      },

      // ================= CASO 3 =================
      {
        case_text: 'Un servidor público asesora a una corporación del sector real que busca implementar buenas prácticas corporativas para blindarse contra la penetración de dineros ilícitos. La entidad teme que, al establecer vínculos con proveedores o clientes internacionales dudosos, termine siendo utilizada sin saberlo para dar apariencia legal a activos criminales. Durante las mesas de trabajo, el funcionario evalúa los posibles riesgos institucionales a los que se expondría la compañía si no cuenta con un sistema robusto de conocimiento del mercado y advierte sobre las repercusiones negativas que esto traería para su estabilidad operativa, prestigio institucional y viabilidad jurídica a largo plazo.',
        stem: '7. En caso de que la corporación decida omitir los controles preventivos y se vea involucrada en una investigación judicial, el funcionario señala que el impacto recaería directamente en el riesgo:',
        options: [
          {
            key: 'A',
            text: 'soberano debido a la disminución del recaudo tributario esperado por las autoridades competentes del central.',
            is_correct: false,
            justification: 'Es incorrecta. El riesgo soberano corresponde a la capacidad de pago del Estado en los mercados de deuda externa, no al riesgo de una corporación privada.'
          },
          {
            key: 'B',
            text: 'legal y de contagio al someter sus bienes a procesos como la extinción de dominio.',
            is_correct: true,
            justification: 'Es correcta. En los sistemas de administración de riesgos de LA/FT (SARLAFT/SAGRILAFT), los riesgos asociados directos de operar con dineros o contrapartes ilícitas son el riesgo legal (procesos penales, multas, medidas cautelares y extinción de dominio, Ley 1708 de 2014) y el riesgo de contagio (la ruina patrimonial propagada por el vínculo con terceros criminales).'
          },
          {
            key: 'C',
            text: 'crediticio al incrementarse las tasas de interés aplicadas a sus futuros proyectos de expansión productiva.',
            is_correct: false,
            justification: 'Es incorrecta. El riesgo crediticio es la probabilidad de impago de un deudor, lo cual es de naturaleza financiera ajena al núcleo del riesgo judicial por LA/FT.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Ley 1708 de 2014 (Código de Extinción de Dominio); SARLAFT / SAGRILAFT Riesgos Asociados.',
        explanation_summary: 'La vinculación en investigaciones judiciales por dineros ilícitos impacta directamente el riesgo legal y de contagio de la empresa.'
      },
      {
        case_text: 'Un servidor público asesora a una corporación del sector real que busca implementar buenas prácticas corporativas para blindarse contra la penetración de dineros ilícitos. La entidad teme que, al establecer vínculos con proveedores o clientes internacionales dudosos, termine siendo utilizada sin saberlo para dar apariencia legal a activos criminales. Durante las mesas de trabajo, el funcionario evalúa los posibles riesgos institucionales a los que se expondría la compañía si no cuenta con un sistema robusto de conocimiento del mercado y advierte sobre las repercusiones negativas que esto traería para su estabilidad operativa, prestigio institucional y viabilidad jurídica a largo plazo.',
        stem: '8. Para mitigar efectivamente estas vulnerabilidades, el servidor instruye a la corporación para que adopte como una buena práctica fundamental la implementación de mecanismos orientados a:',
        options: [
          {
            key: 'A',
            text: 'priorizar transacciones en divisas extranjeras sin registro para evitar demoras en los intercambios comerciales formales.',
            is_correct: false,
            justification: 'Es incorrecta. Omitir el registro formal de divisas es una infracción cambiaria grave y facilita el lavado de activos.'
          },
          {
            key: 'B',
            text: 'aplicar el conocimiento del cliente e indagar sobre sus antecedentes y experiencia en el sector.',
            is_correct: true,
            justification: 'Es correcta. La debida diligencia y el conocimiento del cliente (KYC - Know Your Customer) y de la contraparte, identificando al beneficiario final, la procedencia legal de sus recursos y su idoneidad en el sector comercial, es el pilar preventivo indispensable contra el lavado de activos.'
          },
          {
            key: 'C',
            text: 'delegar la validación de usuarios a las autoridades aduaneras que operan en las zonas francas.',
            is_correct: false,
            justification: 'Es incorrecta. El deber de debida diligencia de conocimiento del cliente no es delegable a las autoridades aduaneras; es una obligación jurídica y corporativa directa de la entidad.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Recomendación 10 del GAFI; SAGRILAFT Circular 100-000016 Superintendencia de Sociedades.',
        explanation_summary: 'El conocimiento del cliente e indagación de antecedentes y beneficiarios finales es la medida central para mitigar riesgos de LA/FT.'
      },
      {
        case_text: 'Un servidor público asesora a una corporación del sector real que busca implementar buenas prácticas corporativas para blindarse contra la penetración de dineros ilícitos. La entidad teme que, al establecer vínculos con proveedores o clientes internacionales dudosos, termine siendo utilizada sin saberlo para dar apariencia legal a activos criminales. Durante las mesas de trabajo, el funcionario evalúa los posibles riesgos institucionales a los que se expondría la compañía si no cuenta con un sistema robusto de conocimiento del mercado y advierte sobre las repercusiones negativas que esto traería para su estabilidad operativa, prestigio institucional y viabilidad jurídica a largo plazo.',
        stem: '9. Cuando el funcionario expone las consecuencias que surgen a partir de los rumores sobre la participación de la empresa en operaciones ilícitas, hace énfasis en que se materializa un riesgo:',
        options: [
          {
            key: 'A',
            text: 'sistémico que colapsa la infraestructura financiera del Estado por la fuga de divisas no reportadas.',
            is_correct: false,
            justification: 'Es incorrecta. El riesgo sistémico alude al derrumbe del sistema financiero en su conjunto por desequilibrios macro, no a los rumores sobre una compañía particular.'
          },
          {
            key: 'B',
            text: 'reputacional que dificulta entablar relaciones comerciales, provocando la pérdida de inversiones y futuros compradores estratégicos.',
            is_correct: true,
            justification: 'Es correcta. El riesgo reputacional se define como la posibilidad de pérdida económica, desprestigio público o exclusión de mercados comerciales y financieros como consecuencia de la mala imagen o vinculación pública con actividades ilícitas o de lavado de activos.'
          },
          {
            key: 'C',
            text: 'operativo generado por fallas tecnológicas que vulneran las bases de datos transaccionales de la entidad.',
            is_correct: false,
            justification: 'Es incorrecta. El riesgo operativo se vincula con fallas en procesos, personas, fraudes internos o sistemas informáticos, no con la opinión o descrédito público.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Normatividad SARLAFT/SAGRILAFT; Estándares de Gestión de Riesgo ISO 31000.',
        explanation_summary: 'El deterioro de imagen derivado de rumores de operaciones ilícitas materializa el riesgo reputacional.'
      },

      // ================= CASO 4 =================
      {
        case_text: 'Durante un ciclo de capacitación estratégica impartida por un servidor público a diferentes agremiaciones económicas, se expone un caso de estudio sobre las tácticas de fraccionamiento utilizadas por grupos criminales. El funcionario detalla cómo una red trasladó recursos desde el exterior dividiendo el dinero en pequeños montos remitidos a múltiples beneficiarios sin relación aparente, quienes cobraron los giros aportando información falsa. El propósito de esta sesión es que los asistentes comprendan la diferencia entre las alertas tempranas y los esquemas históricos ya documentados, facilitando así el diseño de políticas institucionales y la formulación de hipótesis preventivas dentro de sus propios sectores.',
        stem: '10. Al explicar el esquema de fraccionamiento expuesto en el caso de estudio, el servidor indica que el mecanismo de dividir altas sumas de dinero busca principalmente:',
        options: [
          {
            key: 'A',
            text: 'minimizar el pago de comisiones bancarias durante las transferencias electrónicas realizadas por medios digitales.',
            is_correct: false,
            justification: 'Es incorrecta. Al multiplicar las transferencias, las comisiones bancarias acumuladas suelen aumentar; el móvil criminal no es el ahorro bancario.'
          },
          {
            key: 'B',
            text: 'evadir los controles existentes tanto en el país de origen de los fondos como en su destino.',
            is_correct: true,
            justification: 'Es correcta. El fraccionamiento o pitufeo busca que cada una de las operaciones individuales se mantenga por debajo de los topes legales de reporte obligatorio (umbral de transacciones en efectivo o transferencias al exterior), evadiendo la detección automática de los sistemas de monitoreo y supervisión.'
          },
          {
            key: 'C',
            text: 'diversificar las inversiones en el mercado de valores para obtener mayores rendimientos a corto plazo.',
            is_correct: false,
            justification: 'Es incorrecta. La dispersión de giros a terceros falsos no es inversión en bolsa de valores; es una maniobra de estratificación ilícita.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Circular Externa UIAF; Tipologías Regionales GAFILAT sobre Fraccionamiento y Pitufeo.',
        explanation_summary: 'El fraccionamiento tiene como finalidad evadir los controles y umbrales de reporte obligatorio fijados por la regulación.'
      },
      {
        case_text: 'Durante un ciclo de capacitación estratégica impartida por un servidor público a diferentes agremiaciones económicas, se expone un caso de estudio sobre las tácticas de fraccionamiento utilizadas por grupos criminales. El funcionario detalla cómo una red trasladó recursos desde el exterior dividiendo el dinero en pequeños montos remitidos a múltiples beneficiarios sin relación aparente, quienes cobraron los giros aportando información falsa. El propósito de esta sesión es que los asistentes comprendan la diferencia entre las alertas tempranas y los esquemas históricos ya documentados, facilitando así el diseño de políticas institucionales y la formulación de hipótesis preventivas dentro de sus propios sectores.',
        stem: '11. Para diferenciar los conceptos analizados en la capacitación, el funcionario aclara que mientras las señales de alerta diagnostican hechos inusuales específicos, las tipologías funcionan como:',
        options: [
          {
            key: 'A',
            text: 'requerimientos regulatorios exclusivos para los clientes que manejan grandes volúmenes de dinero en efectivo diariamente.',
            is_correct: false,
            justification: 'Es incorrecta. Los requerimientos regulatorios son normas obligatorias, no tipologías explicativas.'
          },
          {
            key: 'B',
            text: 'pruebas documentales irrefutables que conducen al decomiso inmediato de bienes sin necesidad de un juicio.',
            is_correct: false,
            justification: 'Es incorrecta. Las tipologías son instrumentos doctrinarios y pedagógicos de análisis, no pruebas documentales procesales en juicios.'
          },
          {
            key: 'C',
            text: 'reportes de esquemas históricos que permiten formular hipótesis delictivas y diseñar mecanismos de prevención estructurales.',
            is_correct: true,
            justification: 'Es correcta. Las tipologías son descripciones sistematizadas de patrones, técnicas y métodos reiterados en el tiempo por las redes criminales para lavar activos o financiar el terrorismo; permiten a los analistas formular hipótesis delictivas y estructurar políticas preventivas.'
          }
        ],
        correct_option: 'C',
        legal_basis: 'Informes de Tipologías UIAF / GAFILAT / GAFI.',
        explanation_summary: 'Las tipologías corresponden a reportes de esquemas históricos documentados que sirven para estructurar la prevención institucional.'
      },
      {
        case_text: 'Durante un ciclo de capacitación estratégica impartida por un servidor público a diferentes agremiaciones económicas, se expone un caso de estudio sobre las tácticas de fraccionamiento utilizadas por grupos criminales. El funcionario detalla cómo una red trasladó recursos desde el exterior dividiendo el dinero en pequeños montos remitidos a múltiples beneficiarios sin relación aparente, quienes cobraron los giros aportando información falsa. El propósito de esta sesión es que los asistentes comprendan la diferencia entre las alertas tempranas y los esquemas históricos ya documentados, facilitando así el diseño de políticas institucionales y la formulación de hipótesis preventivas dentro de sus propios sectores.',
        stem: '12. En el desarrollo del análisis práctico sobre la modalidad de fraccionamiento, el servidor menciona que la utilización de múltiples beneficiarios para canalizar estas transacciones a menudo involucra a individuos denominados:',
        options: [
          {
            key: 'A',
            text: 'supervisores encubiertos encargados de validar la legalidad de los giros internacionales de manera aleatoria.',
            is_correct: false,
            justification: 'Es incorrecta. Los supervisores son funcionarios del Estado, no los receptores interpuestos en maniobras criminales.'
          },
          {
            key: 'B',
            text: 'pitufos que reciben una compensación por prestar sus datos para eludir la supervisión del sistema.',
            is_correct: true,
            justification: 'Es correcta. En las tipologías de LA/FT (técnica de pitufeo o smurfing), se denomina "pitufos" a las personas reclutadas que prestan sus datos personales o cuentas a cambio de una pequeña comisión para retirar o depositar giros de bajo monto evitando la supervisión.'
          },
          {
            key: 'C',
            text: 'acopiadores que coordinan las grandes inversiones bursátiles en paraísos fiscales mediante el uso de intermediarios.',
            is_correct: false,
            justification: 'Es incorrecta. Los acopiadores no son la figura técnica que cobra giros fraccionados minoritarios en ventanilla bancaria.'
          }
        ],
        correct_option: 'B',
        legal_basis: 'Tipología UIAF sobre Pitufeo / Fraccionamiento de Remesas Internacionales.',
        explanation_summary: 'En la modalidad de fraccionamiento, los intermediarios que prestan sus identidades a cambio de pago son conocidos técnicamente como pitufos.'
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
    console.log(`--- Se insertaron con éxito ${inserted} preguntas oficiales de LA/FT desde Doc. Total actual: ${totalCount.rows[0].total} ---`);

  } catch (err) {
    console.error('Error insertando preguntas desde Doc:', err);
  } finally {
    process.exit(0);
  }
}

seedDocQuestions();
