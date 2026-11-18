import assert from 'node:assert/strict';
import test from 'node:test';
import {validatePdf} from './pdf-validation.js';

test('rejects empty PDF files before parsing', async () => {
  await assert.rejects(
    validatePdf({size: 0, name: 'empty.pdf', type: 'application/pdf'}),
    /PDF está vacío/
  );
});

test('rejects damaged PDF files instead of allowing the upload', async () => {
  const contents = new TextEncoder().encode('%PDF-1.7\nbroken file');
  const file = {
    size: contents.length,
    name: 'damaged.pdf',
    type: 'application/pdf',
    arrayBuffer: async () => contents.buffer,
  };

  await assert.rejects(validatePdf(file));
});
