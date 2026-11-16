import {service} from '../services.js';
import {limitMultiSelect, toast} from '../shared.js';
import {extractArticleSummary} from './pdf-summary.js';
import {validatePdf} from './pdf-validation.js';

function showMessage(element, text) {
  element.textContent = text;
}

export function initUpload() {
  const form = document.querySelector('#upload-form');
  if (!form) return;
  const output = document.querySelector('#upload-message');
  const summaryStatus = document.querySelector('#summary-status');
  const department = form.elements.id_departamento;
  const areas = form.querySelector('select[name="id_areas[]"]');
  const areasLimitMessage = form.querySelector('#areas-limit-message');
  const pdfInput = form.elements.pdf;
  const summaryInput = form.elements.resumen;
  let extractedSummary = '';
  let extractionRequest = 0;
  let validatedPdf = null;
  limitMultiSelect(areas, 3, exceeded => {
    showMessage(areasLimitMessage, exceeded ? 'Solo puedes seleccionar hasta 3 áreas temáticas.' : '');
  });

  pdfInput.addEventListener('change', async () => {
    const pdf = pdfInput.files[0];
    const requestId = ++extractionRequest;
    validatedPdf = null;
    showMessage(summaryStatus, '');
    if (summaryInput.value === extractedSummary) {
      summaryInput.value = '';
      summaryInput.dispatchEvent(new Event('input', {bubbles: true}));
    }
    extractedSummary = '';
    if (!pdf) return;
    showMessage(summaryStatus, 'Verificando que el PDF esté completo y pueda leerse...');
    try {
      const text = await validatePdf(pdf);
      if (requestId !== extractionRequest) return;
      validatedPdf = pdf;
      const result = extractArticleSummary(text);
      if (!result.summary) {
        showMessage(
          summaryStatus,
          text.trim()
            ? 'No se encontró un apartado Resumen/Abstract en el PDF. Ingresa el resumen manualmente.'
            : 'No se pudo extraer texto del PDF. Ingresa el resumen manualmente.'
        );
        return;
      }
      if (summaryInput.value.trim() && summaryInput.value !== extractedSummary) {
        showMessage(summaryStatus, 'Se encontró texto en el PDF, pero conservé el resumen que ya habías escrito. Puedes copiarlo o editarlo manualmente.');
        return;
      }
      summaryInput.value = result.summary;
      extractedSummary = result.summary;
      summaryInput.dispatchEvent(new Event('input', {bubbles: true}));
      showMessage(summaryStatus, 'Se extrajo el apartado Resumen/Abstract del PDF. Revísalo y edítalo si lo necesitas.');
    } catch (error) {
      if (requestId !== extractionRequest) return;
      console.error('No se pudo leer el PDF para extraer el resumen.', error);
      showMessage(summaryStatus, error.message || 'El PDF está dañado o vacío. Selecciona otro documento.');
    }
  });

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
    if (!pdf) {
      showMessage(output, 'Selecciona un archivo PDF válido.');
      return;
    }
    if (validatedPdf !== pdf) {
      showMessage(output, 'El PDF no pudo validarse. Selecciona nuevamente un documento PDF legible y completo.');
      return;
    }

    const button = form.querySelector('[type="submit"]');
    button.disabled = true;
    try {
      const result = await service.createProject(new FormData(form));
      form.reset();
      extractedSummary = '';
      validatedPdf = null;
      showMessage(summaryStatus, '');
      toast(`Proyecto registrado correctamente (folio ${result.id_proyecto}).`);
      showMessage(output, `Proyecto registrado correctamente. Folio: ${result.id_proyecto}.`);
    } catch (error) {
      showMessage(output, error.message);
    } finally {
      button.disabled = false;
    }
  });
}
