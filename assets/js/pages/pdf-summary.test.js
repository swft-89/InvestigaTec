import test from 'node:test';
import assert from 'node:assert/strict';
import {createArticleSummary, extractPdfText} from './pdf-summary.js';

test('extracts Spanish resumen until the next section heading', () => {
  const text = [
    'RESUMEN',
    'Este estudio analiza el aprendizaje colaborativo mediante una investigación aplicada con estudiantes universitarios. Se compararon estrategias y se identificaron resultados relevantes para mejorar la enseñanza.',
    'Palabras clave: educación, aprendizaje',
    'INTRODUCCIÓN',
    'Este contenido no pertenece al resumen.',
  ].join('\n');

  const result = createArticleSummary(text);
  assert.equal(result.source, 'abstract');
  assert.match(result.summary, /aprendizaje colaborativo/u);
  assert.doesNotMatch(result.summary, /Palabras clave|INTRODUCCIÓN/u);
});

test('extracts English abstract after an inline heading', () => {
  const result = createArticleSummary([
    'ABSTRACT: This research studies sustainable energy systems in urban communities and evaluates their long-term impact.',
    'Keywords: renewable energy',
  ].join('\n'));
  assert.equal(result.source, 'abstract');
  assert.match(result.summary, /sustainable energy systems/u);
  assert.doesNotMatch(result.summary, /Keywords/u);
});

test('creates an extractive summary when the PDF has no abstract heading', () => {
  const text = [
    'La investigación analiza métodos de aprendizaje automático para clasificar documentos científicos en español.',
    'El estudio utiliza un conjunto de datos de publicaciones académicas y compara varios modelos de clasificación.',
    'Los resultados muestran que los modelos entrenados con suficientes ejemplos mejoran la precisión de búsqueda.',
    'La propuesta facilita el acceso a información de investigación para estudiantes y docentes.',
  ].join(' ');

  const result = createArticleSummary(text);
  assert.equal(result.source, 'generated');
  assert.ok(result.summary.length > 0);
  assert.ok(result.summary.length < text.length);
});

test('returns an empty result for scanned PDFs without an extractable text layer', () => {
  assert.deepEqual(createArticleSummary(' \n\t '), {summary: '', source: 'empty'});
});

test('extracts text and line breaks from PDF.js page content', async () => {
  const pdf = {
    numPages: 2,
    async getPage(number) {
      return {
        async getTextContent() {
          return {
            items: number === 1
              ? [{str: 'Resumen', hasEOL: true}, {str: 'Texto del documento', hasEOL: true}]
              : [{str: 'Fin', hasEOL: false}],
          };
        },
      };
    },
  };
  assert.equal(await extractPdfText(pdf), 'Resumen\nTexto del documento\nFin');
});
