# Integración prevista

`assets/js/services.js` es la capa actual de datos de demostración. Cuando se implemente el backend, sustituirla por llamadas a endpoints PHP autenticados.

Endpoints sugeridos: `POST /api/auth/login`, `POST /api/auth/register`, `POST /api/auth/recover`, `GET/PUT /api/profile`, `GET/POST /api/articles`, `GET/PATCH/DELETE /api/articles/{id}`, `GET /api/articles/{id}/download`, `GET /api/summaries`, `GET /api/notifications`.

**Seguridad imprescindible:** validación en servidor, sesiones seguras, `password_hash`/`password_verify`, prepared statements PDO, CSRF, autorización por propietario, validación MIME PDF, límite 20 MB, almacenamiento fuera del webroot, tokens de recuperación con vencimiento, control de versiones y envío real de correo. **Ninguno de estos endpoints está implementado todavía.**
