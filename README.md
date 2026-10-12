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
- `services/README.md`: guía de integración de endpoints futuros.

## Alcance de esta entrega

El acceso, registro, edición del perfil y alta de investigaciones usan PostgreSQL. La ficha de usuario del encabezado muestra la sesión activa y permite abrir las pantallas de acceso o el perfil. El perfil permite editar los datos de registro y guardar generación, habilidades, intereses e imagen JPG/PNG de hasta 1 MB. El formulario de subida registra proyectos, autores, áreas, palabras clave, historial y un PDF de hasta 20 MB en una transacción. Las fotos y los PDF se almacenan bajo `uploads/`; Apache necesita permiso de escritura y los PDF no son accesibles directamente por HTTP.

“Mis artículos” consulta en PostgreSQL los proyectos creados por la cuenta o donde participa como autor. El catálogo de proyectos y los demás módulos de exploración todavía usan datos de demostración. Para habilitar el formulario de subida, registra departamentos y áreas temáticas en PostgreSQL. No existen correos enviados, no se generan resúmenes por IA/PLN, no hay descarga de PDFs ni evaluación docente implementadas.
