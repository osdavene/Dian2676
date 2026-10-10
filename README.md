# 🏛️ Simulador y Plataforma de Preparación Concurso DIAN 2676
## Especializado en: Analista III (Fiscalización y Liquidación - Cód. 203 Grado 03)

Aplicación web integral diseñada con base en las directrices oficiales de la **Comisión Nacional del Servicio Civil (CNSC)** y la **Fundación Universitaria del Área Andina (FUAA)** (Contrato 489 de 2026 / Acuerdo No. 21 de 2025).

---

## 🎯 Características y Reglas Oficiales Implementadas

1. **Estructura Oficial de Pruebas Escritas**:
   - **Competencias Funcionales**: De carácter **ELIMINATORIO**. Puntaje mínimo aprobatorio: **70.00 / 100 puntos** (escala con parte entera y dos decimales truncados).
   - **Formato de Ítem PJS (Prueba de Juicio Situacional)**: 
     - 1 Caso hipotético en el contexto laboral de la DIAN.
     - 1 Enunciado de decisión/acción del servidor público.
     - 3 Opciones de respuesta (A, B, C) con una única correcta y justificaciones normativas detalladas para cada alternativa.
   - **Competencias Comportamentales e Integridad**: Formato de autoinforme con escala Likert de 4 niveles (*Totalmente en desacuerdo* a *Totalmente de acuerdo*).

2. **Perfil Especializado de Analista III**:
   - **Código de Ficha**: `AT-FL-2011` (Versión 5, FT-TAH-1824).
   - **Nivel Jerárquico**: Nivel Técnico • Grado 03.
   - **Proceso Misional**: Cumplimiento de obligaciones tributarias, aduaneras y cambiarias.
   - **Subproceso**: Fiscalización y Liquidación.
   - **Funciones y Ejes Evaluados**:
     - *Determinación y Control Tributario* (Estatuto Tributario: Arts. 684, 703, 709, 710, 715, 779, etc.).
     - *Fiscalización Aduanera y Destrucción de Mercancías* (Decreto 1165 de 2019, Decreto 920 de 2023, aprehensión y decomiso).
     - *Régimen Cambiario y Lavado de Activos* (Decreto Ley 2245 de 2011, Circular DCIN-83 BanRep, ROS ante la UIAF).
     - *Procedimiento Administrativo General* (CPACA Ley 1437 de 2011).
     - *Evasión, Elusión, Contrabando y Ley de Transparencia* (Ley 1712 de 2014).
     - *Gestión Documental y Modelo MIPG* (Ley 594 de 2000, 7 dimensiones del MIPG).
     - *Competencias Comportamentales e Integridad* (Código de Integridad DIAN).

3. **Modos de Estudio Disponibles**:
   - ⏱️ **Simulacro Oficial**: Prueba cronometrada (5 horas oficiales o express) con navegación por cuadernillo y simulación de la **Hoja de Respuestas Óptica Oficial**.
   - 🎯 **Práctica por Eje Temático**: Entrenamiento por áreas específicas con **retroalimentación inmediata**, mostrando la clave correcta, la justificación de cada opción y la cita legal.
   - 🔁 **Modo Repasar Fallos**: Filtra y carga automáticamente las preguntas que el usuario ha contestado de forma incorrecta para fijar el aprendizaje activo.
   - 📚 **Banco de Preguntas & Creador**: Explorador de preguntas en PostgreSQL con opción de crear nuevas preguntas PJS, exportar e importar en JSON.
   - 📊 **Analítica y Estadísticas**: Tablero con tasa de aprobación, promedio oficial, efectividad por eje temático y diagnóstico de temas a reforzar.
   - 👥 **Escalabilidad Multigrupo**: Selector y administrador de perfiles listo para incorporar **Analista II**, **Gestor I**, **Inspectores**, etc.

---

## 🗄️ Arquitectura Tecnológica y Base de Datos

- **Base de Datos**: PostgreSQL 18 (`dian2676_db`).
  - Tablas: `job_profiles`, `thematic_axes`, `questions`, `exam_sessions`, `session_answers`.
- **Backend**: Node.js + Express + `pg` (Pool de conexiones).
- **Frontend**: React + Vite + Tailwind CSS + Lucide Icons + Canvas-Confetti.

---

## 🚀 Cómo Iniciar la Aplicación

### Opción 1: Con el Lanzador Rápido (Windows)
Haz doble clic sobre el archivo:
```
iniciar_simulador.bat
```

### Opción 2: Desde la Terminal
```bash
# Iniciar servidor (sirve API y la interfaz web en puerto 3001)
npm start
```
Luego abre tu navegador en:
```
http://localhost:3001
```

---

## 🛠️ Comandos de Administración

- **Volver a poblar la base de datos inicial**:
  ```bash
  npm run seed
  ```
- **Agregar más preguntas especializadas**:
  ```bash
  npm run seed:more
  ```
- **Recompilar el cliente (en caso de modificaciones al código frontend)**:
  ```bash
  npm run build
  ```
