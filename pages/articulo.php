<div class="page-head">
  <p class="eyebrow">Repositorio académico</p>
  <h1 id="article-title">Detalle del artículo</h1>
  <p class="subtitle">Consulta la información registrada y revisa el documento desde el repositorio.</p>
</div>
<p id="article-message" role="status" aria-live="polite">Cargando artículo...</p>
<section id="article-content" hidden>
  <div class="section-heading article-detail-heading">
    <a class="button secondary" href="?page=mis-articulos">Volver a mis artículos</a>
    <button class="button primary" id="edit-article" type="button" hidden>Editar artículo</button>
  </div>
  <section class="panel article-details" aria-labelledby="article-title">
    <div class="article-detail-status"><span id="article-status" class="status"></span></div>
    <div id="article-data" class="account-summary-details"></div>
  </section>
  <section class="panel report-panel" id="article-report-panel" hidden>
    <h2>Reportar artículo al administrador</h2>
    <p class="muted">Usa esta opción si detectas un problema que deba revisarse en este artículo.</p>
    <form id="article-report-form">
      <label>Motivo del reporte
        <textarea name="reason" rows="4" minlength="10" maxlength="3000" required placeholder="Describe el caso con suficiente detalle (10 a 3,000 caracteres)."></textarea>
      </label>
      <button class="button secondary" type="submit">Enviar reporte</button>
      <p id="article-report-message" role="status" aria-live="polite"></p>
    </form>
  </section>
  <section class="panel article-preview">
    <div class="section-heading"><h2>Vista previa del documento</h2><a id="article-open-file" class="button secondary" target="_blank" rel="noopener" hidden>Abrir PDF en otra pestaña</a></div>
    <p id="article-file-message" class="hint"></p>
    <iframe id="article-pdf" title="Vista previa del documento PDF" hidden></iframe>
  </section>
  <form id="article-form" class="panel form-panel registration-form" hidden>
    <h2>Editar información del artículo</h2>
    <div class="form-grid">
      <label class="wide">Título *<input name="titulo" maxlength="300" required></label>
      <label class="wide">Resumen *<textarea name="resumen" rows="5" maxlength="20000" required></textarea></label>
      <label>Departamento *<select name="id_departamento" required></select></label>
      <label>Áreas temáticas *<select name="id_areas[]" multiple required size="5"></select><span class="hint">Selecciona hasta 3 áreas. Usa Ctrl (o Cmd) para elegir varias.</span><span id="article-areas-limit-message" class="hint" role="status" aria-live="polite"></span></label>
      <label class="wide">Palabras clave *<input name="palabras_clave" maxlength="3000" required><span class="hint">Separa las palabras clave con comas.</span></label>
      <label>Fecha de publicación<input name="fecha_publicacion" type="date"></label>
      <label class="wide">Reemplazar PDF (opcional)<input name="pdf" type="file" accept="application/pdf,.pdf"><span class="hint">Máximo 20 MB. Si no seleccionas un archivo, se conserva el documento actual.</span></label>
    </div>
    <div class="form-actions">
      <button class="button primary" type="submit">Guardar cambios</button>
      <button class="button secondary" id="cancel-edit-article" type="button">Cancelar</button>
    </div>
    <p id="article-edit-message" role="alert" aria-live="polite"></p>
  </form>
</section>
