# LinguaPro — Backend (Laravel + MySQL)

API REST construida según:

- `Documento Base LinguaPro` (requisitos funcionales RF-01 a RF-12, arquitectura de 3 capas, modelo de base de datos)
- El frontend Vue.js ya entregado (`linguapro-frontend`), que consume esta API vía `VITE_API_URL`

Stack: **Laravel 12 + Sanctum (autenticación por token) + MySQL**.

> **Nota:** este paquete contiene el código de la aplicación (modelos, controladores,
> migraciones, rutas, seeders). No incluye el esqueleto base de Laravel ni las
> dependencias de Composer, porque este entorno no tiene acceso a Packagist para
> instalarlas. Instala Laravel normalmente en tu máquina y copia estos archivos
> encima — son exactamente los mismos pasos que seguirías en el Día 1 del
> cronograma del documento de planeación.

## 1. Crear el proyecto base

```bash
composer create-project laravel/laravel backend "^12.0"
cd backend
```

## 2. Instalar Sanctum (autenticación por token para la API)

```bash
php artisan install:api
```

Este comando instala Laravel Sanctum, publica su migración
(`personal_access_tokens`) y agrega el enrutamiento de `api.php` a
`bootstrap/app.php`. A continuación **sobrescribe** ese `bootstrap/app.php`
con el de este paquete (ya incluye el alias de middleware `role` y CORS).

## 3. Copiar los archivos de este paquete

Copia manteniendo la misma ruta relativa dentro de tu proyecto `backend/`:

```
app/Models/*.php                          → app/Models/
app/Http/Middleware/EnsureRole.php        → app/Http/Middleware/
app/Http/Controllers/Api/**               → app/Http/Controllers/Api/
database/migrations/2024_01_01_*.php      → database/migrations/
database/seeders/*.php                    → database/seeders/
routes/api.php                            → routes/  (sobrescribe)
bootstrap/app.php                         → bootstrap/ (sobrescribe)
config/cors.php                           → config/
```

## 4. Configurar la base de datos MySQL

Edita `.env` (creado automáticamente por `create-project`):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=linguapro
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173
```

Crea la base de datos vacía en MySQL:

```sql
CREATE DATABASE linguapro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 5. Migrar y poblar datos de ejemplo

```bash
php artisan migrate
php artisan db:seed
```

Esto crea las tablas de la sección 5.1 del documento base (`users` con
columna `role`, `courses`, `lessons`, `enrollments`, `progress`,
`live_classes`) y usuarios/cursos de ejemplo:

| Correo                  | Contraseña | Rol           |
|--------------------------|------------|----------------|
| admin@linguapro.mx        | password   | Administrador  |
| laura@linguapro.mx        | password   | Profesor       |
| evelyn@linguapro.mx       | password   | Estudiante     |

## 6. Levantar el servidor

```bash
php artisan serve
```

La API queda disponible en `http://localhost:8000/api`. Apunta el frontend
a esa URL en su `.env` (`VITE_API_URL=http://localhost:8000/api`) y todo
el flujo del frontend (login, cursos, inscripción, progreso, clases en
vivo, paneles de profesor y administrador) empezará a usar datos reales
en lugar del modo demo.

## Endpoints principales

Reflejan la tabla de rutas y los requisitos funcionales del documento base:

| Método | Ruta                          | Rol requerido | Requisito |
|--------|-------------------------------|---------------|-----------|
| POST   | /api/register                  | Público        | RF-01 |
| POST   | /api/login                      | Público        | RF-02 |
| POST   | /api/logout                     | Autenticado    | RF-02 |
| GET    | /api/courses                    | Autenticado    | RF-06 |
| GET    | /api/courses/{id}                | Autenticado    | RF-06 |
| GET    | /api/courses/{id}/lessons         | Autenticado    | RF-08 |
| GET    | /api/enrollments/me               | Autenticado    | RF-07 |
| POST   | /api/enrollments                  | Autenticado    | RF-07 |
| GET    | /api/progress/me                  | Autenticado    | RF-09 |
| POST   | /api/progress                     | Autenticado    | RF-09 |
| GET    | /api/live-classes                 | Autenticado    | RF-10 |
| GET    | /api/teacher/students              | Profesor       | — (panel del profesor) |
| GET/POST/PUT/DELETE | /api/admin/courses    | Administrador  | RF-04 |
| POST/PUT/DELETE      | /api/admin/lessons    | Administrador  | RF-05 |
| GET/PUT/DELETE        | /api/admin/users      | Administrador  | RF-12 |
| POST/PUT/DELETE       | /api/admin/live-classes| Administrador | RF-11 |

Todas las rutas privadas usan el header `Authorization: Bearer {token}`
devuelto por `/api/login` o `/api/register` — exactamente el formato que
ya espera `src/services/api.js` en el frontend.

## Reglas de integridad aplicadas (sección 5.4)

- `email` único en `users`.
- `enrollments` tiene índice único (`user_id`, `course_id`) para evitar
  inscripciones duplicadas.
- `progress` tiene índice único (`user_id`, `lesson_id`).
- Claves foráneas con `constrained()` aseguran que lecciones, inscripciones,
  progreso y clases en vivo siempre referencien un curso/usuario existente.

## Nota sobre el rol "profesor"

El documento base no incluye un campo que asocie un curso a un profesor
específico. Para soportar el "Panel del Profesor" ya construido en el
frontend, se añadió una columna `teacher_id` (nullable) en `courses`. Es
la única adición fuera del modelo de datos original; todo lo demás sigue
la sección 5.1 tal cual.

## Próximos pasos sugeridos

- Añadir Form Requests dedicados si la validación crece.
- Registrar `enrollments` como requisito antes de servir `lessons` (hoy
  cualquier autenticado puede ver lecciones de cualquier curso; para
  producción conviene comprobar la inscripción activa).
- Agregar pruebas automatizadas (Pest/PHPUnit) para los criterios de
  aceptación de la sección 2.3.
