# 🏛️ Simulador y Plataforma de Preparación Concurso DIAN 2676

Aplicación web integral diseñada con base en las directrices oficiales de la **Comisión Nacional del Servicio Civil (CNSC)** y la **Fundación Universitaria del Área Andina (FUAA)** (Contrato 489 de 2026 / Acuerdo No. 21 de 2025).

**Stack:** Laravel 13 · PHP 8.3/8.4 · PostgreSQL (`dian2676_db`) · Tailwind CSS · Alpine.js · Nginx · AWS CloudPanel & Docker

---

## 🎯 Características y Módulos Principales

| Módulo | Descripción |
|---|---|
| **Simulacro Oficial PJS** | Examen cronometrado con distribución oficial de preguntas, temporizador y hoja óptica interactiva |
| **Práctica por Ejes Temáticos** | Entrenamiento focalizado por temas con retroalimentación instantánea, citas legales y justificaciones |
| **Repaso de Fallos** | Detección automática y banco de preguntas erradas para refuerzo activo del aspirante |
| **Control de Roles y Cargos** | Aspirantes asignados rígidamente a su perfil (**Analista II**, **Analista III**, **Gestor I**, **Gestor III**) |
| **Panel de Superadministrador** | Control central (`/admin/dashboard`): gestión de usuarios, cambio de cargos, analítica y sesiones en vivo |
| **Copias de Seguridad y Restauración** | Generación de backups JSON totales y restauración en 1 clic vía web (`/admin/backup`) o CLI (`php artisan dian:restore-backup`) |
| **Diseño 100% Mobile-First** | Optimizado con ergonomía táctil para cualquier smartphone y tablet |
| **Banco de Preguntas** | 478 preguntas oficiales y 41 ejes temáticos clasificados por nivel técnico, profesional y transversales |

---

## 👥 Credenciales de Acceso Iniciales

- **Super Administrador**: `admin@dian.gov.co` / `admin12345`
- **Aspirante Demo**: `aspirante@dian.gov.co` / `password123`

---

## 🚀 Despliegue Automático en AWS (Similar a FacCol / facturaco)

El repositorio cuenta con integración continua y despliegue continuo (CI/CD) mediante **GitHub Actions** en `.github/workflows/deploy.yml`.

Cada `push` a la rama `main` ejecuta automáticamente:
1. Conexión segura SSH con el servidor AWS (`appleboy/ssh-action@v1.0.3`).
2. Descarga de últimos cambios (`git fetch origin main && git reset --hard origin/main`).
3. Instalación de dependencias de producción (`composer install --no-dev`).
4. Ejecución de migraciones automáticas (`php artisan migrate --force`).
5. Sincronización del banco oficial de 478 preguntas y ejes temáticos (`php artisan db:seed --class=DianSeeder --force`).
6. Optimización de cachés (`config:cache`, `route:cache`, `view:cache`).
7. Reactivación del sitio con 0 downtime (`php artisan up`).

### Configuración de Secretos en GitHub
En el repositorio de GitHub (`Settings` > `Secrets and variables` > `Actions`), agrega:

- `AWS_HOST`: Dirección IP pública de tu servidor AWS (EC2 / Lightsail).
- `AWS_USER`: Usuario SSH de tu servidor (ej. `ubuntu` o el usuario con permisos de despliegue).
- `AWS_SSH_KEY`: Contenido de tu clave privada SSH (`.pem` o `id_rsa`).
- `AWS_PORT`: (Opcional, por defecto `22`).
- `AWS_SITE_PATH`: (Opcional, ej. `/home/dian2676/htdocs/dian2676.com` en CloudPanel).

---

## 🛠️ Comandos de Desarrollo y Verificación

```bash
# Iniciar servidor local
php artisan serve

# Ejecutar suite de pruebas automáticas (26 pruebas, 868 aserciones)
php artisan test

# Restaurar última copia de seguridad de preguntas
php artisan dian:restore-backup --latest

# Re-poblar la base de datos con preguntas y ejes temáticos
php artisan db:seed --class=DianSeeder --force
```
