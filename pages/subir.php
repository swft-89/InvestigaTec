<div class="page-head">
  <p class="eyebrow">Nuevo depósito</p>
  <h1>Subir una investigación</h1>
  <p class="subtitle">
    Registra los datos del proyecto y adjunta un PDF de máximo 20 MB.
    Los campos marcados con * son obligatorios.
  </p>
</div>

<?php if (!current_user()): ?>
  <section class="panel form-panel">
    <p>Debes iniciar sesión para registrar una investigación.</p>
    <a class="button primary" href="?page=login">Iniciar sesión</a>
  </section>
<?php else: ?>
<form id="upload-form" class="panel form-panel" enctype="multipart/form-data">
  <h2>Información del proyecto</h2>

  <div class="form-grid">

    <!-- ===== proyectos.titulo ===== -->
    <label class="wide">
      Título *
      <input
        type="text"
        name="titulo"
        maxlength="300"
        required
        placeholder="Título completo del proyecto de investigación">
    </label>

    <!-- ===== proyectos.resumen ===== -->
    <label class="wide">
      Resumen *
      <textarea
        name="resumen"
        rows="5"
        maxlength="20000"
        required
        placeholder="Descripción breve del proyecto (objetivos, metodología, resultados esperados)"></textarea>
    </label>

    <!-- ===== proyectos.id_departamento ===== -->
    <label>
      Departamento *
      <select name="id_departamento" required>
        <option value="">Selecciona un departamento</option>
        <option value="">Cargando departamentos...</option>
      </select>
    </label>

    <!-- ===== áreas temáticas (N:M → proyectos_areas) ===== -->
    <label>
      Áreas temáticas *
      <select name="id_areas[]" multiple required size="5">
        <option value="" disabled>Cargando áreas...</option>
      </select>
      <span class="hint">Usa Ctrl (o Cmd) para seleccionar varias.</span>
    </label>

    <!-- ===== palabras clave (N:M → proyectos_palabras_clave) ===== -->
    <label class="wide">
      Palabras clave *
      <input
        type="text"
        name="palabras_clave"
        maxlength="3000"
        required
        placeholder="Separa con comas: inteligencia artificial, educación, machine learning">
      <span class="hint">Se crearán automáticamente las que no existan en el catálogo.</span>
    </label>

    <!-- ===== autores (N:M → proyectos_autores) ===== -->
    <label class="wide">
      Autores *
      <input
        type="text"
        name="autores"
        maxlength="8000"
        required
        placeholder="Correos de los autores separados por comas: juan@it.com, ana@it.com">
      <span class="hint">
        Deben ser usuarios registrados. El primer correo se toma como autor principal.
      </span>
    </label>

    <!-- ===== asesor (proyectos_autores con tipo ASESOR) ===== -->
    <label>
      Docente asesor
      <input
        type="email"
        name="asesor_correo"
        placeholder="docente@institucion.edu.mx">
      <span class="hint">Opcional. Debe ser un usuario con tipo DOCENTE.</span>
    </label>

    <!-- ===== proyectos.fecha_publicacion ===== -->
    <label>
      Fecha de publicación
      <input type="date" name="fecha_publicacion">
      <span class="hint">Opcional. Si se deja vacía, el proyecto queda como BORRADOR.</span>
    </label>

    <!-- ===== proyectos.estado ===== -->
    <label>
      Estado inicial *
      <select name="estado" required>
        <option value="BORRADOR" selected>Borrador</option>
        <option value="EN_REVISION">En revisión</option>
      </select>
      <span class="hint">
        Solo puedes iniciar en BORRADOR o EN_REVISION.
        Los demás estados los asigna el flujo de revisión.
      </span>
    </label>

    <!-- ===== archivos + ext_version_proyecto ===== -->
    <label class="wide">
      Archivo PDF *
      <input
        type="file"
        name="pdf"
        accept="application/pdf,.pdf"
        required
        maxlength="20971520">
      <span class="hint">Máximo 20 MB. Solo PDF.</span>
    </label>

  </div>

  <div class="form-actions">
    <button class="button primary" type="submit">Registrar proyecto</button>
    <a class="button secondary" href="?page=mis-articulos">Cancelar</a>
  </div>

  <p class="notice">
    El proyecto se guardará en el estado seleccionado y quedará vinculado a tu cuenta.
  </p>
  <p id="upload-message" role="alert" aria-live="polite"></p>
</form>
<?php endif; ?>