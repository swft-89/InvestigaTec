# Integración prevista

`assets/js/services.js` contiene la integración PHP autenticada para registro, inicio y cierre de sesión, y perfil. Los otros módulos aún usan datos de demostración.

Endpoints disponibles: `POST /api/login.php`, `POST /api/register.php`, `POST /api/logout.php`, `GET/POST /api/profile.php`, `GET /api/carreras.php`, `GET /api/projects.php` (catálogos de departamentos y áreas), `POST /api/projects.php` (alta transaccional con PDF) y `GET /api/my-projects.php` (proyectos creados por el usuario o donde figura como autor). Los cambios de perfil y las altas de proyectos aceptan `multipart/form-data` y requieren CSRF y sesión.

Los endpoints restantes sugeridos son: `POST /api/auth/recover`, `GET/POST /api/articles`, `GET/PATCH/DELETE /api/articles/{id}`, `GET /api/articles/{id}/download`, `GET /api/summaries` y `GET /api/notifications`.

El esquema PostgreSQL debe incluir `ext_estudiante_perfil` y las tablas de proyectos (definidas en `database/schema.sql`). Los PDF se almacenan bajo `uploads/proyectos/`, con acceso HTTP directo denegado por `.htaccess`; Apache necesita permiso de escritura. Las fotos de perfil se almacenan bajo `uploads/perfiles/`. La capa restante todavía necesita validación de propietario, tokens de recuperación con vencimiento, control de versiones y envío real de correo.
