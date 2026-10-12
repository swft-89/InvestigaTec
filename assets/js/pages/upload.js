import {service} from '../services.js';
import {toast} from '../shared.js';

function showMessage(element, text) {
  element.textContent = text;
}

export function initUpload() {
  const form = document.querySelector('#upload-form');
  if (!form) return;
  const output = document.querySelector('#upload-message');
  const department = form.elements.id_departamento;
  const areas = form.querySelector('select[name="id_areas[]"]');

  service.getProjectOptions().then(({departamentos, areas_tematicas}) => {
    department.replaceChildren(new Option('Selecciona un departamento', ''));
    for (const item of departamentos) department.add(new Option(item.nombre, item.id_departamento));

    areas.replaceChildren();
    for (const item of areas_tematicas) areas.add(new Option(item.nombre, item.id_area));
    if (!departamentos.length || !areas_tematicas.length) {
      showMessage(output, 'No hay departamentos o áreas temáticas disponibles. Contacta al administrador.');
    }
  }).catch(error => showMessage(output, error.message));

  form.addEventListener('submit', async event => {
    event.preventDefault();
    showMessage(output, '');
    const pdf = form.elements.pdf.files[0];
    if (!pdf || !pdf.name.toLowerCase().endsWith('.pdf') || (pdf.type && pdf.type !== 'application/pdf')) {
      showMessage(output, 'Selecciona un archivo PDF válido.');
      return;
    }
    if (pdf.size > 20 * 1024 * 1024) {
      showMessage(output, 'El PDF no puede superar 20 MB.');
      return;
    }

    const button = form.querySelector('[type="submit"]');
    button.disabled = true;
    try {
      const result = await service.createProject(new FormData(form));
      form.reset();
      toast(`Proyecto registrado correctamente (folio ${result.id_proyecto}).`);
      showMessage(output, `Proyecto registrado correctamente. Folio: ${result.id_proyecto}.`);
    } catch (error) {
      showMessage(output, error.message);
    } finally {
      button.disabled = false;
    }
  });
}
