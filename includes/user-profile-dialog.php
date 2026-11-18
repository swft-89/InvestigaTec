<dialog id="user-profile-dialog" class="user-profile-dialog" aria-labelledby="user-profile-title">
  <div class="section-heading">
    <h2 id="user-profile-title">Perfil de usuario</h2>
    <button class="button secondary" type="button" data-close-user-profile>Cerrar</button>
  </div>
  <p id="user-profile-message" role="status" aria-live="polite"></p>
  <div id="user-profile-content" hidden>
    <div class="user-profile-heading">
      <img id="user-profile-avatar" alt="" hidden>
      <span id="user-profile-avatar-placeholder" class="user-profile-avatar-placeholder" aria-hidden="true"></span>
      <div>
        <h3 id="user-profile-name"></h3>
        <p id="user-profile-subtitle" class="muted"></p>
      </div>
    </div>
    <dl id="user-profile-details" class="user-profile-details"></dl>
    <section class="user-profile-projects">
      <h3>Artículos en los que participa</h3>
      <div id="user-profile-project-list"></div>
    </section>
  </div>
</dialog>
