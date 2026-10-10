import os
import sys
import re
import json

sys.stdout.reconfigure(encoding='utf-8')

DOCS_DIR = r'c:\Proyectos\Dian2676\Doc\Descargas_Online'
OUT_JSON = r'c:\Proyectos\Dian2676\server\extracted_questions.json'

def parse_integridad():
    file_path = os.path.join(DOCS_DIR, 'simulacro_doc_3.txt')
    with open(file_path, 'r', encoding='utf-8') as f:
        text = f.read()

    pattern = r'Pregunta\s+(\d+)\s+Caso:\s*(.*?)\s*Enunciado:\s*(.*?)\s*A\.\s*(.*?)\s*B\.\s*(.*?)\s*C\.\s*(.*?)\s*D\.\s*(.*?)\s*Respuesta correcta:\s*([A-D])\s*Justificación:\s*(.*?)(?=(?:Pregunta\s+\d+|Tabla de Puntuación|$))'
    matches = list(re.finditer(pattern, text, re.DOTALL))
    
    questions = []
    for m in matches:
        num = int(m.group(1))
        caso = m.group(2).strip()
        enunciado = m.group(3).strip()
        opt_a = m.group(4).strip()
        opt_b = m.group(5).strip()
        opt_c = m.group(6).strip()
        opt_d = m.group(7).strip()
        correct_key = m.group(8).strip()
        justificacion = m.group(9).strip()
        
        options = [
            {'key': 'A', 'text': opt_a, 'is_correct': correct_key == 'A', 'justification': justificacion if correct_key == 'A' else 'Opción incorrecta según los principios éticos y deberes del Código de Integridad.'},
            {'key': 'B', 'text': opt_b, 'is_correct': correct_key == 'B', 'justification': justificacion if correct_key == 'B' else 'Opción incorrecta según los principios éticos y deberes del Código de Integridad.'},
            {'key': 'C', 'text': opt_c, 'is_correct': correct_key == 'C', 'justification': justificacion if correct_key == 'C' else 'Opción incorrecta según los principios éticos y deberes del Código de Integridad.'},
            {'key': 'D', 'text': opt_d, 'is_correct': correct_key == 'D', 'justification': justificacion if correct_key == 'D' else 'Opción incorrecta según los principios éticos y deberes del Código de Integridad.'}
        ]
        
        legal_basis = 'Código de Integridad DIAN / Estatuto Anticorrupción (Ley 1474 de 2011) / Ley 1952 de 2019'
        if '(' in justificacion and ')' in justificacion:
            parts = justificacion.split(')')
            legal_basis = parts[0].replace('Valor “', '').replace('Valor "', '').strip() + ')'
            
        questions.append({
            'axis_name': 'Código de Integridad y Ética Pública DIAN',
            'axis_category': 'integridad',
            'question_type': 'situacional',
            'difficulty': 'medio',
            'case_text': caso,
            'stem': f'{num}. {enunciado}',
            'options': options,
            'correct_option': correct_key,
            'legal_basis': legal_basis,
            'explanation_summary': justificacion
        })
        
    print(f'✅ Integridad parseada: {len(questions)} preguntas')
    return questions


def parse_comportamentales():
    file_path = os.path.join(DOCS_DIR, 'simulacro_doc_1.txt')
    with open(file_path, 'r', encoding='utf-8') as f:
        text = f.read()

    pattern = r'Pregunta\s+(\d+)\s+Caso:\s*(.*?)\s*Enunciado:\s*(.*?)\s*A\.\s*(.*?)\s*B\.\s*(.*?)\s*C\.\s*(.*?)\s*D\.\s*(.*?)\s*Respuesta correcta:\s*([A-D])\s*Justificación:\s*(.*?)(?=(?:Pregunta\s+\d+|Tabla de Puntuación|$))'
    matches = list(re.finditer(pattern, text, re.DOTALL))
    
    questions = []
    for m in matches:
        num = int(m.group(1))
        caso = m.group(2).strip()
        enunciado = m.group(3).strip()
        opt_a = m.group(4).strip()
        opt_b = m.group(5).strip()
        opt_c = m.group(6).strip()
        opt_d = m.group(7).strip()
        correct_key = m.group(8).strip()
        justificacion = m.group(9).strip()
        
        options = [
            {'key': 'A', 'text': opt_a, 'is_correct': correct_key == 'A', 'justification': justificacion if correct_key == 'A' else 'Comportamiento no alineado con el nivel óptimo de la competencia esperada en la DIAN.'},
            {'key': 'B', 'text': opt_b, 'is_correct': correct_key == 'B', 'justification': justificacion if correct_key == 'B' else 'Comportamiento no alineado con el nivel óptimo de la competencia esperada en la DIAN.'},
            {'key': 'C', 'text': opt_c, 'is_correct': correct_key == 'C', 'justification': justificacion if correct_key == 'C' else 'Comportamiento no alineado con el nivel óptimo de la competencia esperada en la DIAN.'},
            {'key': 'D', 'text': opt_d, 'is_correct': correct_key == 'D', 'justification': justificacion if correct_key == 'D' else 'Comportamiento no alineado con el nivel óptimo de la competencia esperada en la DIAN.'}
        ]
        
        legal_basis = 'Diccionario de Competencias Laborales DIAN - Decreto 491 de 2020 / Decreto Ley 071 de 2020'
        
        questions.append({
            'axis_name': 'Competencias Comportamentales (Nivel Técnico)',
            'axis_category': 'comportamental',
            'question_type': 'situacional',
            'difficulty': 'medio',
            'case_text': caso,
            'stem': f'{num}. {enunciado}',
            'options': options,
            'correct_option': correct_key,
            'legal_basis': legal_basis,
            'explanation_summary': justificacion
        })
        
    print(f'✅ Comportamentales parseadas: {len(questions)} preguntas')
    return questions


def parse_generalidades():
    file_path = os.path.join(DOCS_DIR, 'simulacro_doc_2.txt')
    with open(file_path, 'r', encoding='utf-8') as f:
        text = f.read()

    chunks = re.split(r'________________+', text)
    
    # ----------------------------------------------------
    # BLOQUE 2: FUNCIÓN PÚBLICA, CARRERA Y RÉGIMEN DISCIPLINARIO (20 preguntas)
    # ----------------------------------------------------
    answers_bloque2 = {}
    just_bloque2 = {}
    ans_lines = [l.strip() for l in chunks[2].splitlines() if l.strip()]
    idx = 0
    while idx < len(ans_lines):
        line = ans_lines[idx]
        if line.isdigit() and 1 <= int(line) <= 20:
            q_num = int(line)
            if idx + 2 < len(ans_lines):
                ans_letter = ans_lines[idx+1]
                ans_just = ans_lines[idx+2]
                answers_bloque2[q_num] = ans_letter
                just_bloque2[q_num] = ans_just
                idx += 3
                continue
        idx += 1
        
    q_matches_b2 = list(re.finditer(r'(\d+)\.\s*(.*?)\s*\*\s*A\.\s*(.*?)\s*\*\s*B\.\s*(.*?)\s*\*\s*C\.\s*(.*?)\s*\*\s*D\.\s*(.*?)(?=(?:\d+\.|\Z))', chunks[1], re.DOTALL))
    questions_b2 = []
    for m in q_matches_b2:
        num = int(m.group(1))
        enunciado = m.group(2).strip()
        opt_a = m.group(3).strip()
        opt_b = m.group(4).strip()
        opt_c = m.group(5).strip()
        opt_d = m.group(6).strip()
        
        correct_key = answers_bloque2.get(num, 'B')
        just = just_bloque2.get(num, 'Ley 909 de 2004 / Decreto 1083 de 2015.')
        
        options = [
            {'key': 'A', 'text': opt_a, 'is_correct': correct_key == 'A', 'justification': just if correct_key == 'A' else 'Incorrecta a la luz de las normas de carrera y función pública.'},
            {'key': 'B', 'text': opt_b, 'is_correct': correct_key == 'B', 'justification': just if correct_key == 'B' else 'Incorrecta a la luz de las normas de carrera y función pública.'},
            {'key': 'C', 'text': opt_c, 'is_correct': correct_key == 'C', 'justification': just if correct_key == 'C' else 'Incorrecta a la luz de las normas de carrera y función pública.'},
            {'key': 'D', 'text': opt_d, 'is_correct': correct_key == 'D', 'justification': just if correct_key == 'D' else 'Incorrecta a la luz de las normas de carrera y función pública.'}
        ]
        
        questions_b2.append({
            'axis_name': 'Carrera Administrativa y Función Pública',
            'axis_category': 'funcional_basica',
            'question_type': 'situacional',
            'difficulty': 'medio',
            'case_text': 'Situación administrativa en el ejercicio de la función pública y el régimen de carrera en entidades del Estado colombiano.',
            'stem': f'{num}. {enunciado}',
            'options': options,
            'correct_option': correct_key,
            'legal_basis': 'Ley 909 de 2004 / Decreto 1083 de 2015 / Código General Disciplinario (Ley 1952 de 2019)',
            'explanation_summary': just
        })
    print(f'✅ Bloque Carrera parseado: {len(questions_b2)} preguntas')

    # ----------------------------------------------------
    # BLOQUE 3: GESTIÓN DOCUMENTAL Y LEY GENERAL DE ARCHIVOS (20 preguntas)
    # ----------------------------------------------------
    answers_bloque3 = {}
    just_bloque3 = {}
    ans_lines_b3 = [l.strip() for l in chunks[4].splitlines() if l.strip()]
    idx = 0
    while idx < len(ans_lines_b3):
        line = ans_lines_b3[idx]
        if line.isdigit() and 1 <= int(line) <= 20:
            q_num = int(line)
            if idx + 2 < len(ans_lines_b3):
                ans_letter = ans_lines_b3[idx+1]
                ans_just = ans_lines_b3[idx+2]
                answers_bloque3[q_num] = ans_letter
                just_bloque3[q_num] = ans_just
                idx += 3
                continue
        idx += 1

    q_matches_b3 = list(re.finditer(r'(\d+)\.\s*(.*?)\s*\*\s*A\.\s*(.*?)\s*\*\s*B\.\s*(.*?)\s*\*\s*C\.\s*(.*?)\s*\*\s*D\.\s*(.*?)(?=(?:\d+\.|\Z))', chunks[3], re.DOTALL))
    questions_b3 = []
    for m in q_matches_b3:
        num = int(m.group(1))
        enunciado = m.group(2).strip()
        opt_a = m.group(3).strip()
        opt_b = m.group(4).strip()
        opt_c = m.group(5).strip()
        opt_d = m.group(6).strip()
        
        correct_key = answers_bloque3.get(num, 'B')
        just = just_bloque3.get(num, 'Ley 594 de 2000 / Acuerdos del Archivo General de la Nación.')
        
        options = [
            {'key': 'A', 'text': opt_a, 'is_correct': correct_key == 'A', 'justification': just if correct_key == 'A' else 'Incorrecta técnica o normativamente según directrices AGN.'},
            {'key': 'B', 'text': opt_b, 'is_correct': correct_key == 'B', 'justification': just if correct_key == 'B' else 'Incorrecta técnica o normativamente según directrices AGN.'},
            {'key': 'C', 'text': opt_c, 'is_correct': correct_key == 'C', 'justification': just if correct_key == 'C' else 'Incorrecta técnica o normativamente según directrices AGN.'},
            {'key': 'D', 'text': opt_d, 'is_correct': correct_key == 'D', 'justification': just if correct_key == 'D' else 'Incorrecta técnica o normativamente según directrices AGN.'}
        ]
        
        questions_b3.append({
            'axis_name': 'Gestión Documental y Modelo MIPG',
            'axis_category': 'funcional_basica',
            'question_type': 'situacional',
            'difficulty': 'medio',
            'case_text': 'Gestión del ciclo vital del documento, organización de expedientes, foliación y transferencias documentales en la administración pública.',
            'stem': f'{num}. {enunciado}',
            'options': options,
            'correct_option': correct_key,
            'legal_basis': 'Ley 594 de 2000 (Ley General de Archivos) / Decretos y Reglamentación AGN',
            'explanation_summary': just
        })
    print(f'✅ Bloque Gestión Documental parseado: {len(questions_b3)} preguntas')

    # ----------------------------------------------------
    # BLOQUE 4: INTEGRIDAD Y ÉTICA PÚBLICA (20 preguntas)
    # ----------------------------------------------------
    answers_bloque4 = {}
    vals_bloque4 = {}
    ans_lines_b4 = [l.strip() for l in chunks[8].splitlines() if l.strip()]
    idx = 0
    while idx < len(ans_lines_b4):
        line = ans_lines_b4[idx]
        if line.isdigit() and 1 <= int(line) <= 20:
            q_num = int(line)
            if idx + 3 < len(ans_lines_b4):
                ans_letter = ans_lines_b4[idx+2]
                ans_val = ans_lines_b4[idx+3]
                answers_bloque4[q_num] = ans_letter
                vals_bloque4[q_num] = ans_val
                idx += 4
                continue
        idx += 1
        
    text_b4 = chunks[5] + '\n' + chunks[6] + '\n' + chunks[7]
    q_matches_b4 = list(re.finditer(r'(\d+)\.\s*(.*?)\s*\*\s*A\.\s*(.*?)\s*\*\s*B\.\s*(.*?)\s*\*\s*C\.\s*(.*?)\s*\*\s*D\.\s*(.*?)(?=(?:\d+\.|\Z))', text_b4, re.DOTALL))
    questions_b4 = []
    for m in q_matches_b4:
        num = int(m.group(1))
        enunciado = m.group(2).strip()
        opt_a = m.group(3).strip()
        opt_b = m.group(4).strip()
        opt_c = m.group(5).strip()
        opt_d = m.group(6).strip()
        
        correct_key = answers_bloque4.get(num, 'C')
        val = vals_bloque4.get(num, 'Código de Integridad y Ética Pública DIAN.')
        just = f'Valor evaluado: {val}. Conducta recta conforme a los principios de probidad y transparencia en la DIAN.'
        
        options = [
            {'key': 'A', 'text': opt_a, 'is_correct': correct_key == 'A', 'justification': just if correct_key == 'A' else 'Conducta que compromete la ética o la imparcialidad pública.'},
            {'key': 'B', 'text': opt_b, 'is_correct': correct_key == 'B', 'justification': just if correct_key == 'B' else 'Conducta que compromete la ética o la imparcialidad pública.'},
            {'key': 'C', 'text': opt_c, 'is_correct': correct_key == 'C', 'justification': just if correct_key == 'C' else 'Conducta que compromete la ética o la imparcialidad pública.'},
            {'key': 'D', 'text': opt_d, 'is_correct': correct_key == 'D', 'justification': just if correct_key == 'D' else 'Conducta que compromete la ética o la imparcialidad pública.'}
        ]
        
        questions_b4.append({
            'axis_name': 'Código de Integridad y Ética Pública DIAN',
            'axis_category': 'integridad',
            'question_type': 'situacional',
            'difficulty': 'avanzado',
            'case_text': 'Dilema ético en la función pública tributaria y aduanera, prevención de conflictos de interés y anticorrupción.',
            'stem': f'{num}. {enunciado}',
            'options': options,
            'correct_option': correct_key,
            'legal_basis': f'Código de Integridad DIAN / {val}',
            'explanation_summary': just
        })
    print(f'✅ Bloque Ética Experto parseado: {len(questions_b4)} preguntas')

    # ----------------------------------------------------
    # BLOQUE 1: CONSTITUCIÓN POLÍTICA Y DERECHOS FUNDAMENTALES (20 preguntas)
    # ----------------------------------------------------
    const_keys = {
        1: ('B', 'Art. 24 Ley 1755 / Ley 1437: La negativa debe motivarse por escrito citando la norma que ampara la reserva legal y los recursos procedentes.'),
        2: ('B', 'Art. 37 Constitución Política: El derecho a la manifestación pacífica está garantizado, priorizando el diálogo antes de medidas de fuerza.'),
        3: ('B', 'Art. 13 Constitución Política: Todas las personas nacen libres e iguales ante la ley y recibirán la misma protección y trato sin discriminación.'),
        4: ('B', 'Principio de Reserva y Debido Proceso en Contratación: La información preliminar en licitaciones está sujeta a reserva hasta su publicación oficial.'),
        5: ('B', 'Art. 330 Constitución Política y Convenio 169 OIT: Obligatoriedad de la consulta previa a comunidades étnicas ante decisiones que las afecten.'),
        6: ('B', 'Art. 6 y 122 Constitución Política: Ningún servidor público puede cumplir órdenes abiertamente contrarias a la ley o que dispongan de fondos públicos indebidamente.'),
        7: ('B', 'Decreto 2591 de 1991: Los términos de la acción de tutela son improrrogables y perentorios, corriendo de inmediato sin dilación.'),
        8: ('B', 'Art. 79 Constitución Política: Deber del Estado y de sus servidores de proteger la diversidad e integridad del medio ambiente.'),
        9: ('B', 'Art. 23 Constitución Política y Ley 1755 de 2015: El derecho de petición no exige solemnidad de abogado ni pago alguno.'),
        10: ('B', 'Art. 258 Constitución Política: El voto es un derecho y un deber ciudadano, y es secreto para garantizar la libertad democrática.'),
        11: ('B', 'Art. 20 y 29 Constitución Política: Se garantiza la libertad de expresar y difundir su pensamiento y opiniones, y el debido proceso.'),
        12: ('B', 'Art. 13 y 53 Constitución Política: Principio fundamental de igualdad de oportunidades y remuneración proporcional sin discriminación de género.'),
        13: ('B', 'Art. 209 Constitución Política: La función administrativa está al servicio de los intereses generales bajo los principios de moralidad e imparcialidad.'),
        14: ('B', 'Ley 581 de 2000 (Ley de Cuotas): Reglamenta la adecuada y efectiva participación de la mujer en los niveles decisorios de la administración.'),
        15: ('B', 'Art. 2 Constitución Política: Las autoridades están instituidas para proteger a todas las personas en su vida, honra y bienes.'),
        16: ('B', 'Art. 15 Constitución Política: Derecho al hábeas data, rectificación y actualización de información recogida en bancos de datos públicos.'),
        17: ('B', 'Art. 29 Constitución Política: El debido proceso se aplicará a toda clase de actuaciones judiciales y administrativas.'),
        18: ('B', 'Art. 10 Constitución Política: Respeto y garantía de la lengua oficial y dialectos de grupos étnicos en su territorio.'),
        19: ('C', 'Art. 123 Constitución Política: Los servidores públicos están al servicio del Estado y de la comunidad; ejercerán sus funciones en la forma prevista en la Constitución y la ley.'),
        20: ('B', 'Art. 209 Constitución Política: La función administrativa se desarrolla con fundamento en los principios de igualdad, moralidad, eficacia, economía, celeridad e imparcialidad.')
    }
    
    q_matches_b1 = list(re.finditer(r'(\d+)\.\s*(.*?)\s*\*\s*A\.\s*(.*?)\s*\*\s*B\.\s*(.*?)\s*\*\s*C\.\s*(.*?)\s*\*\s*D\.\s*(.*?)(?=(?:\d+\.|\Z|Pregunta|FUNCIÓN PÚBLICA))', chunks[0], re.DOTALL))
    questions_b1 = []
    for m in q_matches_b1:
        num = int(m.group(1))
        enunciado = m.group(2).strip()
        opt_a = m.group(3).strip()
        opt_b = m.group(4).strip()
        opt_c = m.group(5).strip()
        opt_d = m.group(6).strip()
        
        correct_key, just = const_keys.get(num, ('B', 'Constitución Política de Colombia de 1991.'))
        
        options = [
            {'key': 'A', 'text': opt_a, 'is_correct': correct_key == 'A', 'justification': just if correct_key == 'A' else 'Incompatible con la norma constitucional aplicable.'},
            {'key': 'B', 'text': opt_b, 'is_correct': correct_key == 'B', 'justification': just if correct_key == 'B' else 'Incompatible con la norma constitucional aplicable.'},
            {'key': 'C', 'text': opt_c, 'is_correct': correct_key == 'C', 'justification': just if correct_key == 'C' else 'Incompatible con la norma constitucional aplicable.'},
            {'key': 'D', 'text': opt_d, 'is_correct': correct_key == 'D', 'justification': just if correct_key == 'D' else 'Incompatible con la norma constitucional aplicable.'}
        ]
        
        questions_b1.append({
            'axis_name': 'Constitución Política y Derechos Fundamentales',
            'axis_category': 'funcional_basica',
            'question_type': 'situacional',
            'difficulty': 'medio',
            'case_text': 'Aplicación de principios constitucionales, derechos fundamentales y deberes del servidor público en actuaciones administrativas del Estado colombiano.',
            'stem': f'{num}. {enunciado}',
            'options': options,
            'correct_option': correct_key,
            'legal_basis': 'Constitución Política de Colombia de 1991',
            'explanation_summary': just
        })
    print(f'✅ Bloque Constitución parseado: {len(questions_b1)} preguntas')

    all_general = questions_b1 + questions_b2 + questions_b3 + questions_b4
    return all_general

if __name__ == '__main__':
    q_int = parse_integridad()
    q_comp = parse_comportamentales()
    q_gen = parse_generalidades()
    
    all_qs = q_int + q_comp + q_gen
    print(f'\nGuardando {len(all_qs)} preguntas en {OUT_JSON}...')
    with open(OUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(all_qs, f, ensure_ascii=False, indent=2)
    print('✨ Archivo JSON generado exitosamente.')
