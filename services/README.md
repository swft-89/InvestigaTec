# Integración prevista

`assets/js/services.js` contiene la integración PHP autenticada para registro, inicio y cierre de sesión, perfil, proyectos y notificaciones. Otros módulos aún usan datos de demostración.

Endpoints disponibles: `POST /api/login.php`, `POST /api/register.php`, `POST /api/logout.php`, `GET/POST /api/profile.php`, `GET /api/carreras.php`, `GET /api/projects.php` (catálogos de departamentos y áreas), `POST /api/projects.php` (alta transaccional con PDF), `GET /api/my-projects.php` (proyectos creados por el usuario o donde figura como autor) y `GET/POST /api/notifications.php` (notificaciones de la sesión actual). Los cambios de perfil, las altas y las operaciones de notificación requieren sesión y CSRF.

Para habilitar las notificaciones dentro del sitio, ejecuta `database/migrations/20261018_article_status_notifications.sql` y después `database/migrations/20261101_brevo_email_and_password_recovery.sql` en PostgreSQL. El trigger inserta una notificación sin leer para cada autor cuando cambia el estado del proyecto; la segunda migración habilita su despacho por correo. La página muestra cinco avisos recientes por defecto y permite buscar, filtrar, elegir 5/10/15/todos y marcar cada aviso individualmente como leído. Configura `BREVO_API_KEY` y `BREVO_SENDER_EMAIL` para habilitar el envío; todos los correos incluyen un aviso para no responder.

Los endpoints restantes sugeridos son: `POST /api/auth/recover`, `GET/POST /api/articles`, `GET/PATCH/DELETE /api/articles/{id}` y `GET /api/summaries`.

El esquema PostgreSQL debe incluir `ext_estudiante_perfil`, `ext_notificacion` y las tablas de proyectos (definidas en `database/schema.sql`). Los PDF se almacenan bajo `uploads/proyectos/`, con acceso HTTP directo denegado por `.htaccess`; Apache necesita permiso de escritura. Las fotos de perfil se almacenan bajo `uploads/perfiles/`. Los tokens de recuperación con vencimiento y la evaluación docente aún no están implementados.

La extracción de texto y propuesta de resumen PDF se ejecuta en el navegador con PDF.js. Ejecuta `npm ci` en la raíz del repositorio al preparar el entorno local; la imagen Docker copia `pdfjs-dist` a `node_modules/` para que Apache sirva los módulos `.mjs` en producción. El `.htaccess` raíz asigna el MIME de JavaScript requerido por esos módulos.
