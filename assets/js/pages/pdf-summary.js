const sectionHeading = /^\s*(?:\d+(?:\.\d+)*[.)]?\s*)?(?:palabras\s+clave|keywords?|key\s+words|index\s+terms|introducci[oó]n|introduction|objetivos?|objectives?|metodolog[ií]a|methodology|materiales?\s+y\s+m[eé]todos|materials?\s+and\s+methods|resultados?|results?|discusi[oó]n|discussion|conclusiones?|conclusions?|referencias|references)\b/i;

function words(text) {
  return text.match(/[\p{L}\p{N}]{3,}/gu) ?? [];
}

function cleanText(text) {
  return text
    .replace(/\u00ad/gu, '')
    .replace(/-\s*\n\s*/gu, '')
    .replace(/[ \t]*\n[ \t]*/gu, '\n')
    .replace(/[ \t]{2,}/gu, ' ')
    .replace(/\n{3,}/gu, '\n\n')
    .trim();
}

function trimAbstract(text) {
  const lines = cleanText(text).split(/\n+/u);
  const body = [];
  for (const line of lines) {
    if (sectionHeading.test(line.trim())) break;
    const trimmed = line.trim();
    if (trimmed) body.push(trimmed);
    if (words(body.join(' ')).length >= 280) break;
  }
  return body.join(' ').replace(/\s+/gu, ' ').trim();
}

function extractAbstract(text) {
  const lines = cleanText(text).split(/\n/u);
  for (let index = 0; index < lines.length; index++) {
    const match = lines[index].match(/^[ \t]*(?:resumen|abstract)\b[ \t]*(?::|[.\-–—][ \t]*)?(.*)$/iu);
    if (!match) continue;
    const content = [match[1]];
    for (let next = index + 1; next < lines.length; next++) {
      const line = lines[next].trim();
      if (sectionHeading.test(line)) break;
      if (/^\s*(?:resumen|abstract)\s*$/iu.test(line)) break;
      if (line) content.push(line);
    }
    const abstract = trimAbstract(content.join('\n'));
    if (words(abstract).length >= 8) return abstract;
  }
  return '';
}

export function extractArticleSummary(text) {
  const normalizedText = cleanText(text);
  if (!normalizedText) return {summary: '', source: 'empty'};
  const abstract = extractAbstract(normalizedText);
  if (abstract) return {summary: abstract, source: 'abstract'};
  return {summary: '', source: 'empty'};
}

export async function extractPdfText(pdfDocument) {
  const pages = [];
  for (let pageNumber = 1; pageNumber <= pdfDocument.numPages; pageNumber++) {
    const page = await pdfDocument.getPage(pageNumber);
    const content = await page.getTextContent();
    let pageText = '';
    for (const item of content.items) {
      if (!('str' in item)) continue;
      pageText += item.str;
      pageText += item.hasEOL ? '\n' : ' ';
    }
    pages.push(pageText.trimEnd());
  }
  return pages.join('\n');
}
