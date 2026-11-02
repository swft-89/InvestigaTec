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

## Despliegue en Render con Docker

El contenedor usa PHP 8.3 con Apache y `pdo_pgsql`. En el plan gratuito el sistema de archivos de Render es efímero: las fotos y los PDF nuevos guardados localmente pueden perderse al reiniciar o volver a desplegar. Para conservar nuevas cargas sin un disco de pago, integra almacenamiento de objetos externo y guarda sus URL o identificadores; no uses Git como almacenamiento de cargas de usuarios.

1. Confirma que tienes la rama actualizada y súbela a GitHub:

   ```sh
   git switch b
   git pull origin b
   git push origin b
   ```

   `origin/b` es la referencia remota; al conectar Render, selecciona la rama `b`.
2. En Render crea una base **PostgreSQL** y un **Web Service** conectado al repositorio `swft-89/InvestigaTec`. Selecciona la rama `b`, el runtime **Docker** y deja el Dockerfile en la ruta raíz (`Dockerfile`). No necesitas comandos personalizados de build o inicio.
3. En las variables de entorno del Web Service configura `DATABASE_URL` con la URL interna de PostgreSQL de Render. Puedes vincularla desde el panel de Render para no copiar credenciales a GitHub. El contenedor escucha en el puerto definido por `PORT` (por defecto `10000`).
4. Inicializa la base una sola vez, conectándote con un cliente PostgreSQL y ejecutando, en este orden:
   - `database/schema.sql`
   - `database/migrations/20261018_article_status_notifications.sql`
   - `database/migrations/20261024_support_requests.sql`
   - `database/migrations/20261025_admin_moderation.sql`
   - `database/migrations/20261101_cloudinary_profile_photos.sql`
   - `database/migrations/20261102_brevo_email_and_password_recovery.sql`

   `database/seed_desarrollo.sql` contiene datos de ejemplo local: no lo uses como catálogo de producción. Carga los departamentos y carreras oficiales antes de habilitar registros. Provisiona también la cuenta administradora con el procedimiento descrito arriba.
5. Configura en Render `BREVO_API_KEY` y `BREVO_SENDER_EMAIL`, que debe ser un remitente verificado en Brevo. `BREVO_SENDER_NAME` es opcional. Para enlaces de recuperación, configura `APP_URL` con la URL pública HTTPS del servicio Render; si no se define, se usa `RENDER_EXTERNAL_URL`. Las notificaciones de cambios de estado, reportes y respuestas de soporte se envían a la vez que se guardan. Los enlaces de restablecimiento vencen en 45 minutos y se limita una solicitud por cuenta cada dos minutos.
6. Configura en Render las variables secretas `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY` y `CLOUDINARY_API_SECRET`. El proyecto usa el SDK PHP de Cloudinary, instalado con Composer durante la construcción de Docker (`composer install` para desarrollo local). Las fotos de perfil se guardan como imágenes seguras HTTPS y los PDF como recursos autenticados; sus descargas pasan por la sesión de la aplicación y usan enlaces firmados de corta duración. No expongas `CLOUDINARY_API_SECRET`, `BREVO_API_KEY` ni otras credenciales en GitHub o en el navegador.
7. Las cargas creadas después del despliegue se almacenan en Cloudinary. Los PDF locales ya existentes siguen usando su ruta anterior y no se migran automáticamente; asegúrate de que estén presentes en la imagen o vuelve a subirlos. Las fotos de perfil locales tampoco se migran automáticamente: hay que recuperarlas desde respaldo y volver a cargarlas desde cada perfil. No agregues fotos privadas ni cargas de usuarios al repositorio.
8. Despliega y revisa los logs del servicio. Cada push posterior a `b` iniciará un nuevo despliegue. Mantén `DATABASE_URL` y las credenciales de Cloudinary/Brevo como variables privadas, y respalda la base de datos según tu política de conservación.

Para comprobar la imagen localmente, si tienes Docker instalado:

```sh
docker build -t investigatec .
docker run --rm -p 8080:10000 -e PORT=10000 investigatec
```

Abre `http://localhost:8080`. Para probar funciones conectadas a PostgreSQL, configura también `DATABASE_URL` en el contenedor.
