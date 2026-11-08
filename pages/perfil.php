<div class="page-head"><p class="eyebrow">Cuenta</p><h1>Perfil del estudiante</h1><p class="subtitle">Edita tus datos de registro y completa tu perfil académico.</p></div>
<form class="panel form-panel registration-form" id="profile-form">
  <h2>Información del estudiante</h2>
  <div class="profile-photo">
    <img id="profile-photo-preview" alt="Foto de perfil actual" hidden>
    <label>Foto de perfil (JPG o PNG, máximo 1 MB)<input name="portrait" type="file" accept="image/jpeg,image/png"></label>
  </div>
  <div class="form-grid">
    <label>Nombre *<input name="nombre" maxlength="100" required></label>
    <label>Apellido paterno *<input name="apellido_paterno" maxlength="100" required></label>
    <label>Apellido materno (opcional)<input name="apellido_materno" maxlength="100"></label>
    <label>Correo electrónico *<input name="correo" type="email" maxlength="255" required></label>
    <label>Número de control *<input name="num_control" minlength="4" maxlength="20" required></label>
    <label>Carrera *<select name="id_carrera" required><option value="">Cargando carreras...</option></select></label>
    <label>Semestre (opcional)<input name="semestre" type="number" min="1" max="20"></label>
    <label>Generación (opcional)<input name="generacion" maxlength="20" placeholder="Ej. 2022-2026"></label>
    <label class="wide">Habilidades (opcional)<textarea name="habilidad" maxlength="2000" rows="3" placeholder="Ej. Python, análisis de datos"></textarea></label>
    <label class="wide">Intereses de investigación (opcional)<textarea name="intereses" maxlength="2000" rows="3" placeholder="Ej. aprendizaje automático"></textarea></label>
  </div>
  <div class="form-actions"><button type="submit" class="button primary">Guardar cambios</button></div>
  <p id="profile-error" role="alert" aria-live="polite"></p>
</form>