# LinguaPro — Proyecto completo (Frontend + Backend)

Plataforma de cursos online para una escuela de idiomas, según la
planeación y el documento base de LinguaPro.

```
linguapro/
  frontend/   Vue 3 + Vite + Pinia + Vue Router (ver frontend/README.md)
  backend/    Laravel 12 + Sanctum + MySQL       (ver backend/README.md)
```

## Orden de instalación recomendado

1. **Backend** — sigue `backend/README.md` paso a paso: crear el proyecto
   Laravel, copiar estos archivos, configurar MySQL, migrar y sembrar
   datos. Al final tendrás la API corriendo en `http://localhost:8000/api`.

2. **Frontend** — sigue `frontend/README.md`: `npm install`, copia
   `.env.example` a `.env` y ajusta `VITE_API_URL` a la URL de tu backend,
   luego `npm run dev`. Se abre en `http://localhost:5173`.

Si el backend aún no está listo, el frontend funciona igual en **modo
demo** con datos de ejemplo (ver la sección correspondiente en
`frontend/README.md`), así que puedes avanzar ambas partes en paralelo,
tal como sugiere el cronograma de una semana del documento de planeación.

## Usuarios de prueba

| Correo                 | Contraseña | Rol            |
|-------------------------|------------|-----------------|
| admin@linguapro.mx        | password   | Administrador   |
| laura@linguapro.mx         | password   | Profesor        |
| evelyn@linguapro.mx        | password   | Estudiante      |

(En modo demo del frontend sin backend, cualquier contraseña funciona.)
