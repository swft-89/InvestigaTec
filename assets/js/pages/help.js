import {service} from '../services.js';

export function initHelp() {
  const form = document.querySelector('#help-form');
  const message = document.querySelector('#help-message');
  if (!form || !message) return;

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    if (!submit) return;

    submit.disabled = true;
    message.textContent = 'Enviando tu solicitud...';
    try {
      const data = Object.fromEntries(new FormData(form));
      const result = await service.sendSupportRequest(data);
      form.reset();
      message.textContent = result.message;
    } catch (error) {
      message.textContent = error.message || 'No se pudo enviar la solicitud. Inténtalo nuevamente.';
    } finally {
      submit.disabled = false;
    }
  });
}
