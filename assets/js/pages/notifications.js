import {service} from '../services.js';
import {escapeHtml, toast} from '../shared.js';

export async function initNotifications() {
  const target = document.querySelector('#notifications-list');
  const message = document.querySelector('#notifications-message');
  const markRead = document.querySelector('#mark-read');
  if (!target || !message || !markRead) return;

  async function load() {
    message.textContent = 'Cargando notificaciones...';
    try {
      const {notifications} = await service.getNotices();
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
          </article>`;
        }).join('')
        : '<p>No tienes notificaciones.</p>';
      message.textContent = '';
      markRead.disabled = !notifications.some(notification => !notification.leida);
    } catch (error) {
      message.textContent = error.message;
      markRead.disabled = true;
    }
  }

  markRead.addEventListener('click', async () => {
    markRead.disabled = true;
    try {
      await service.markNoticesRead();
      toast('Notificaciones marcadas como leídas.');
      await load();
      document.dispatchEvent(new Event('notifications:updated'));
    } catch (error) {
      message.textContent = error.message;
      markRead.disabled = false;
    }
  });

  await load();
}
