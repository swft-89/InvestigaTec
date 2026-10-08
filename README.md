# InvestigaTec · Interfaz modular para Visual Studio Code

## Ejecutar

1. Extrae el ZIP y abre la carpeta `InvestigaTec_Modular` en Visual Studio Code.
2. Requiere **PHP 8+** para cargar las plantillas `.php` (la extensión Live Server sola NO ejecuta PHP).
3. Abre una terminal en la raíz y ejecuta `php -S localhost:8000`.
4. En el navegador visita `http://localhost:8000`.

## Organización

- `index.php`: controlador de páginas (no concentra su contenido).
- `pages/`: ocho pantallas independientes.
- `includes/`: encabezado, menú y arranque compartidos.
- `assets/css/styles.css`: estilo institucional azul marino, rojo y blanco, inspirado en el logo suministrado.
- `assets/js/pages/`: lógica JS específica de cada pantalla.
- `assets/js/services.js`: adaptador de datos sustituible por un backend.
- `assets/js/demo-data.js`: ejemplos ficticios.
- `config/app.php`: configuración de aplicación.
- `database/schema.sql`: esquema **propuesto** de PostgreSQL 16, todavía no conectado.
- `services/README.md`: guía de integración de endpoints futuros.

## Alcance de esta entrega

Prototipo navegable: Inicio, exploración filtrada, mis artículos, subida de PDF (valida 20 MB pero no almacena el archivo), resúmenes de ejemplo, perfil, avisos y acceso demostrativo. Los datos se guardan en `localStorage` del navegador y son borrables; NO son una base de datos real.

El login no verifica credenciales, no existen correos enviados, no se generan resúmenes por IA/PLN, no hay descarga real de PDFs y no hay evaluación docente implementada. Los estados de muestra ilustran el flujo. No utilizar con datos personales o contraseñas reales hasta completar la capa de servidor y su seguridad.
