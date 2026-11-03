import {service} from '../services.js';
import {limitMultiSelect, toast} from '../shared.js';
import {createArticleSummary, extractPdfText} from './pdf-summary.js';

let pdfjsLibPromise;

function loadPdfJs() {
  pdfjsLibPromise ??= import('../../../node_modules/pdfjs-dist/build/pdf.mjs').then(pdfjsLib => {
    pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
      '../../../node_modules/pdfjs-dist/build/pdf.worker.min.mjs',
      import.meta.url
    ).href;
    return pdfjsLib;
  });
  return pdfjsLibPromise;
}

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
  let generatedSummary = '';
  let extractionRequest = 0;
  limitMultiSelect(areas, 3, exceeded => {
    showMessage(areasLimitMessage, exceeded ? 'Solo puedes seleccionar hasta 3 áreas temáticas.' : '');
  });

  pdfInput.addEventListener('change', async () => {
    const pdf = pdfInput.files[0];
    const requestId = ++extractionRequest;
    showMessage(summaryStatus, '');
    if (!pdf) return;
    if (!pdf.name.toLowerCase().endsWith('.pdf') || (pdf.type && pdf.type !== 'application/pdf')) {
      showMessage(summaryStatus, 'Selecciona un archivo PDF válido para extraer el resumen.');
      return;
    }
    if (pdf.size > 20 * 1024 * 1024) {
      showMessage(summaryStatus, 'El PDF no puede superar 20 MB.');
      return;
    }

    showMessage(summaryStatus, 'Leyendo el documento en este navegador...');
    try {
      const pdfjsLib = await loadPdfJs();
      const documentTask = pdfjsLib.getDocument({
        data: new Uint8Array(await pdf.arrayBuffer()),
        cMapUrl: new URL('../../../node_modules/pdfjs-dist/cmaps/', import.meta.url).href,
        cMapPacked: true,
        standardFontDataUrl: new URL('../../../node_modules/pdfjs-dist/standard_fonts/', import.meta.url).href,
        isEvalSupported: false,
        enableXfa: false,
      });
      const document = await documentTask.promise;
      const text = await extractPdfText(document);
      await document.destroy();
      if (requestId !== extractionRequest) return;
      const result = createArticleSummary(text);
      if (!result.summary) {
        showMessage(summaryStatus, 'No se pudo extraer texto del PDF. Si es un documento escaneado, escribe el resumen manualmente.');
        return;
      }
      if (summaryInput.value.trim() && summaryInput.value !== generatedSummary) {
        showMessage(summaryStatus, 'Se encontró texto en el PDF, pero conservé el resumen que ya habías escrito. Puedes copiarlo o editarlo manualmente.');
        return;
      }
      summaryInput.value = result.summary;
      generatedSummary = result.summary;
      summaryInput.dispatchEvent(new Event('input', {bubbles: true}));
      showMessage(
        summaryStatus,
        result.source === 'abstract'
          ? 'Se extrajo el apartado Resumen/Abstract. Revísalo y edítalo si lo necesitas.'
          : 'No se encontró un apartado Resumen/Abstract; se generó una propuesta extractiva. Revísala y edítala si lo necesitas.'
      );
    } catch (error) {
      if (requestId !== extractionRequest) return;
      console.error('No se pudo leer el PDF para generar el resumen.', error);
      showMessage(summaryStatus, 'No se pudo leer este PDF. Puedes escribir el resumen manualmente y continuar.');
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
      generatedSummary = '';
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
