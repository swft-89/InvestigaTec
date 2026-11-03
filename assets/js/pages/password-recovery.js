import {service} from '../services.js';

function setMessage(id, text) {
  const element = document.getElementById(id);
  if (element) element.textContent = text;
}

export function initPasswordRecovery() {
  const requestForm = document.getElementById('recovery-request-form');
  const resetForm = document.getElementById('recovery-reset-form');
  const token = new URLSearchParams(location.hash.slice(1)).get('token');
  if (token) {
    requestForm.hidden = true;
    resetForm.hidden = false;
    history.replaceState(null, '', '?page=recuperar');
  }

  requestForm.addEventListener('submit', async event => {
    event.preventDefault();
    setMessage('recovery-request-error', '');
    setMessage('recovery-request-message', '');
    const button = requestForm.querySelector('[type=submit]');
    button.disabled = true;
    try {
      const {message} = await service.requestPasswordRecovery(requestForm.elements.email.value.trim());
      setMessage('recovery-request-message', message);
    } catch (error) {
      setMessage('recovery-request-error', error.message);
    } finally {
      button.disabled = false;
    }
  });

  resetForm.addEventListener('submit', async event => {
    event.preventDefault();
    setMessage('recovery-reset-error', '');
    setMessage('recovery-reset-message', '');
    const password = resetForm.elements.password.value;
    if (password !== resetForm.elements.confirmation.value) {
      setMessage('recovery-reset-error', 'Las contraseñas no coinciden.');
      return;
    }
    const button = resetForm.querySelector('[type=submit]');
    button.disabled = true;
    try {
      const {message} = await service.resetPassword(token, password);
      resetForm.reset();
      setMessage('recovery-reset-message', message);
    } catch (error) {
      setMessage('recovery-reset-error', error.message);
    } finally {
      button.disabled = false;
    }
  });
}
