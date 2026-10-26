# InvestigaTec · Interfaz modular para Visual Studio Code

## Organización

- `index.php`: controlador de páginas (no concentra su contenido).
- `pages/`: pantallas independientes, con inicio de sesión y registro separados.
- `includes/`: encabezado, menú y arranque compartidos.
- `assets/css/styles.css`: estilo institucional azul marino, rojo y blanco, inspirado en el logo suministrado.
- `assets/js/pages/`: lógica JS específica de cada pantalla.
- `assets/js/services.js`: adaptador de datos sustituible por un backend.
- `assets/js/demo-data.js`: ejemplos ficticios.
- `config/app.php`: configuración de aplicación.
- `database/schema.sql`: esquema de PostgreSQL 16 para autenticación, estudiantes y perfiles.
- `database/migrations/20261024_support_requests.sql`: tabla e índices para solicitudes de ayuda, sugerencias y reportes.
- `database/migrations/20261025_admin_moderation.sql`: aprobación de cuentas, reportes académicos y respuestas desde administración.
- `services/README.md`: guía de integración de endpoints futuros.

## Alcance de esta entrega

El acceso, registro, edición del perfil y alta de investigaciones usan PostgreSQL. La ficha de usuario del encabezado muestra la sesión activa y permite abrir las pantallas de acceso o el perfil. El perfil permite editar los datos de registro y guardar generación, habilidades, intereses e imagen JPG/PNG de hasta 1 MB. El formulario de subida registra proyectos, autores, áreas, palabras clave, historial y un PDF de hasta 20 MB en una transacción. Las fotos y los PDF se almacenan bajo `uploads/`; Apache necesita permiso de escritura y los PDF no son accesibles directamente por HTTP.

“Mis artículos” consulta en PostgreSQL los proyectos creados por la cuenta o donde participa como autor. Cada título abre una ficha con sus datos y una vista previa PDF protegida por sesión y permisos; el creador y los autores pueden editar los metadatos y reemplazar el documento mientras esté en borrador o requiera cambios. Inicio y Explorar consultan los artículos reales aprobados y, al consultar estas pantallas, los publican automáticamente como `PUBLICADO`; la ficha pública permite consultar los datos y el resumen, y la descarga del PDF requiere iniciar sesión. Las estadísticas de Inicio usan PostgreSQL y solo aparecen para usuarios autenticados. Las notificaciones de cambio de estado se registran en PostgreSQL, aparecen en la página de notificaciones y se indican en el encabezado mientras estén sin leer; el correo se implementará posteriormente. No se generan resúmenes mediante IA generativa ni está implementada la evaluación docente.

La página Ayuda y soporte incluye preguntas frecuentes y un formulario para registrar solicitudes en `ext_ayuda_sugerencias`. Ejecuta `database/migrations/20261024_support_requests.sql` en PostgreSQL después de crear el esquema base; la tabla admite envíos de invitados y vincula los de usuarios autenticados.

El panel `?page=admin` solo se muestra a la cuenta configurada como administradora y sus endpoints comprueban también el correo `admin@investigatec.mx`, su marca administrativa, aprobación y estado activo. Después del esquema base, ejecuta las migraciones en este orden: `20261018_article_status_notifications.sql`, `20261024_support_requests.sql` y `20261025_admin_moderation.sql`. Genera un hash de contraseña localmente con PHP y provisiona la cuenta administradora manualmente; el script público de registro nunca asigna privilegios:

```sql
INSERT INTO usuarios
    (nombre, apellido_paterno, correo, password_hash, tipo_usuario,
     estado_cuenta, ext_aprobada, ext_administrador)
VALUES
    ('Administrador', 'InvestigaTec', 'admin@investigatec.mx',
     '<HASH_GENERADO_LOCALMENTE>', 'DOCENTE', 'ACTIVA', TRUE, TRUE)
ON CONFLICT (correo) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    estado_cuenta = 'ACTIVA',
    ext_aprobada = TRUE,
    ext_administrador = TRUE;
```

Para generar el valor de `password_hash`, ejecuta localmente `php -r '$p = readline("Nueva contraseña: "); echo password_hash($p, PASSWORD_DEFAULT), PHP_EOL;'` y reemplaza el marcador por el hash resultante.

Las cuentas nuevas quedan pendientes hasta su aprobación. La suspensión invalida la sesión activa y bloquea nuevos inicios de sesión. Los docentes tienen una sección de revisión donde pueden consultar artículos no publicados y enviar reportes; el administrador recibe esos reportes en su panel y en sus notificaciones. Las respuestas a usuarios registrados aparecen en sus notificaciones; las solicitudes de invitados quedan respondidas y almacenadas en el panel, pero requieren integrar correo para que el invitado reciba la respuesta.

Al seleccionar un PDF en el formulario de subida, PDF.js extrae su texto localmente en el navegador. El sistema busca una sección “Resumen” o “Abstract”; si no la encuentra, propone un resumen extractivo basado en las oraciones y términos más relevantes. El campo queda editable antes de enviar. Para instalar la dependencia del navegador ejecuta `npm ci` en la raíz del proyecto; los archivos del PDF no se envían para generar el resumen.
