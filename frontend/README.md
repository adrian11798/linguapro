# LinguaPro — Frontend (Vue.js)

Frontend de la plataforma de cursos online para una escuela de idiomas, construido según:

- `Planeación del Proyecto` (alcance, arquitectura de 3 capas, tecnologías)
- `Documento Base LinguaPro` (requisitos funcionales, rutas, modelo de datos)
- Mockups de diseño (paleta de colores, tipografías, pantallas)

Stack: **Vue 3 + Vite + Vue Router + Pinia + Axios**, listo para conectarse a la API REST de Laravel.

## Instalación

```bash
cd linguapro-frontend
npm install
cp .env.example .env   # ajusta VITE_API_URL a tu backend Laravel
npm run dev
```

Abre `http://localhost:5173`.

## Modo demo (sin backend)

Si la API de Laravel todavía no está disponible, **el frontend funciona igual**: cada
servicio (`src/services/*.js`) intenta llamar a la API real y, si falla, usa los datos
de ejemplo de `src/services/mockData.js`. Esto permite avanzar el frontend en paralelo
al backend, como sugiere el cronograma de una semana del documento de planeación.

Usuarios de prueba (cualquier contraseña funciona en modo demo):

| Correo                  | Rol          |
|--------------------------|--------------|
| evelyn@linguapro.mx       | Estudiante   |
| laura@linguapro.mx        | Profesor     |
| admin@linguapro.mx        | Administrador|

## Estructura del proyecto

Sigue la estructura sugerida en el "Documento Base LinguaPro" (sección 4.1):

```
src/
  components/     Componentes reutilizables (NavBar, CourseCard, ProgressBar)
  views/          Pantallas principales (estudiante, profesor, admin)
  router/         Rutas y guardas de navegación por rol
  services/       Consumo de la API REST + datos mock de respaldo
  store/          Estado global (Pinia) — sesión y usuario autenticado
  assets/styles/  Variables de marca (colores, tipografías) y estilos base
```

## Rutas implementadas

Correspondientes a la tabla de rutas del documento base:

| Ruta                  | Acceso         | Función                          |
|-----------------------|----------------|-----------------------------------|
| `/login`               | Público        | Inicio de sesión                  |
| `/register`             | Público        | Registro de estudiante            |
| `/dashboard`            | Estudiante     | Dashboard / resumen del día       |
| `/courses`              | Estudiante     | Explorar cursos disponibles       |
| `/my-courses`           | Estudiante     | Cursos inscritos                  |
| `/courses/:id`          | Estudiante     | Contenido y lecciones de un curso |
| `/progress`             | Estudiante     | Progreso del estudiante           |
| `/live-classes`         | Estudiante     | Agenda de clases en vivo          |
| `/teacher`               | Profesor       | Panel del profesor / alumnos      |
| `/admin/courses`         | Administrador  | Gestión de cursos                 |
| `/admin/lessons`         | Administrador  | Gestión de contenidos             |
| `/admin/users`           | Administrador  | Gestión de usuarios                |
| `/admin/live-classes`    | Administrador  | Gestión de clases en vivo          |

El control de acceso por rol se implementa en `src/router/index.js` (`router.beforeEach`).

## Identidad visual aplicada

Tomada del documento de diseño de LinguaPro:

- **Color principal / CTA:** `#0D233A` (Azul Medianoche Profundo)
- **Fondo general:** `#F4F5F7`
- **Tarjetas / formularios:** `#FFFFFF`
- **Bordes:** `#D1D5DB`
- **Acento (progreso):** `#3B82F6`
- **Encabezados:** Playfair Display · **Cuerpo:** Inter

## Conectar con el backend Laravel

En `src/services/api.js` se configura la instancia de Axios apuntando a
`VITE_API_URL`. Cuando el equipo de backend tenga listos los endpoints de
`backend/routes/api.php` (login, courses, lessons, enrollments, progress,
live-classes), el frontend los usará automáticamente sin cambios adicionales
en las vistas — solo actualiza `.env`.

## Próximos pasos sugeridos

- Reemplazar el reproductor de video de marcador de posición por un embed real
  (YouTube/Vimeo o el servicio de videoconferencia definido).
- Conectar `src/services/*.js` a los endpoints reales cuando el backend esté listo.
- Añadir validaciones de formulario más robustas y manejo de errores de la API.
- Sustituir las 3 propuestas de logo cuando el cliente las apruebe.
