import {service} from '../services.js';
import {escapeHtml, toast} from '../shared.js';

export async function initNotifications() {
  const target = document.querySelector('#notifications-list');
  const message = document.querySelector('#notifications-message');
  const search = document.querySelector('#notification-search');
  const filter = document.querySelector('#notification-filter');
  const limitButtons = [...document.querySelectorAll('[data-notification-limit]')];
  if (!target || !message || !search || !filter || !limitButtons.length) return;
  let selectedLimit = '5';
  let requestId = 0;
  let searchTimer;

  async function load() {
    const currentRequestId = ++requestId;
    message.textContent = 'Cargando notificaciones...';
    try {
      const {notifications} = await service.getNotices({
        limit: selectedLimit,
        search: search.value.trim(),
        status: filter.value,
      });
      if (currentRequestId !== requestId) return;
      target.innerHTML = notifications.length
        ? notifications.map(notification => {
          const date = new Date(notification.fecha_envio);
          const formattedDate = Number.isNaN(date.getTime())
            ? notification.fecha_envio
            : new Intl.DateTimeFormat('es-MX', {dateStyle: 'medium', timeStyle: 'short'}).format(date);
          return `<article class="note-row">
            <h3>${escapeHtml(notification.mensaje)}${notification.leida ? '' : ' · Nuevo'}</h3>
            <p>${escapeHtml(formattedDate)}</p>
            ${notification.titulo ? `<a href="?page=articulo&amp;id=${encodeURIComponent(notification.id_proyecto)}">Ver artículo: ${escapeHtml(notification.titulo)}</a>` : ''}
            <button class="text-button notification-read-button" type="button"
              data-notification-id="${Number(notification.id_notificacion)}"
              ${notification.leida ? 'disabled' : ''}>
              ${notification.leida ? 'Leída' : 'Marcar como leída'}
            </button>
          </article>`;
        }).join('')
        : '<p>No hay notificaciones que coincidan con la búsqueda y el filtro.</p>';
      message.textContent = '';
    } catch (error) {
      if (currentRequestId !== requestId) return;
      message.textContent = error.message;
    }
  }

  target.addEventListener('click', async event => {
    if (!(event.target instanceof Element)) return;
    const button = event.target.closest('.notification-read-button');
    if (!button || button.disabled) return;
    button.disabled = true;
    try {
      await service.markNoticeRead(Number(button.dataset.notificationId));
      toast('Notificación marcada como leída.');
      await load();
      document.dispatchEvent(new Event('notifications:updated'));
    } catch (error) {
      message.textContent = error.message;
      button.disabled = false;
    }
  });

  for (const button of limitButtons) {
    button.addEventListener('click', () => {
      selectedLimit = button.dataset.notificationLimit;
      for (const limitButton of limitButtons) {
        limitButton.setAttribute('aria-pressed', String(limitButton === button));
      }
      load();
    });
  }
  search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(load, 250);
  });
  filter.addEventListener('change', load);

  await load();
}
