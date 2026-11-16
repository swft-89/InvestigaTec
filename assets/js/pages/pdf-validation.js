import {extractPdfText} from './pdf-summary.js';

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

export async function validatePdf(file) {
  if (!file || file.size === 0) throw new Error('El PDF está vacío. Selecciona un documento con contenido.');
  if (file.size > 20 * 1024 * 1024) throw new Error('El PDF no puede superar 20 MB.');
  if (!file.name.toLowerCase().endsWith('.pdf') || (file.type && file.type !== 'application/pdf')) {
    throw new Error('Selecciona un archivo PDF válido.');
  }

  const pdfjsLib = await loadPdfJs();
  const documentTask = pdfjsLib.getDocument({
    data: new Uint8Array(await file.arrayBuffer()),
    cMapUrl: new URL('../../../node_modules/pdfjs-dist/cmaps/', import.meta.url).href,
    cMapPacked: true,
    standardFontDataUrl: new URL('../../../node_modules/pdfjs-dist/standard_fonts/', import.meta.url).href,
    isEvalSupported: false,
    enableXfa: false,
  });
  const document = await documentTask.promise;
  try {
    if (document.numPages < 1) throw new Error('El PDF no contiene páginas.');
    const text = await extractPdfText(document);
    return text;
  } finally {
    await document.destroy();
  }
}
