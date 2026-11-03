const stopWords = new Set((
  'a al algo algunas algunos ante antes como con contra cual cuando de del desde donde durante e el ella ellas ellos en entre era esa esas ese eso esos esta estaba estas este esto estos fue ha hasta hay la las le les lo los mas más me mi mis mucha muchas mucho muchos muy ni no nos o otra otras otro otros para pero por porque que quien se sin sobre son su sus también tanto te tiene toda todas todo todos tras un una unas uno unos y ya ' +
  'a an and are as at be been by for from has have in into is it its of on or that the their this to was were will with'
).split(/\s+/u));

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

function summarizeText(text) {
  const sentences = cleanText(text)
    .replace(/\n+/gu, ' ')
    .split(/(?<=[.!?])\s+(?=[\p{Lu}\d¿¡])/u)
    .map(sentence => sentence.trim())
    .filter(sentence => words(sentence).length >= 7);
  if (!sentences.length) return '';

  const frequencies = new Map();
  for (const sentence of sentences) {
    for (const token of words(sentence.toLocaleLowerCase())) {
      if (!stopWords.has(token)) frequencies.set(token, (frequencies.get(token) ?? 0) + 1);
    }
  }
  const ranked = sentences.map((sentence, index) => {
    const tokens = words(sentence.toLocaleLowerCase()).filter(token => !stopWords.has(token));
    const score = tokens.reduce((total, token) => total + (frequencies.get(token) ?? 0), 0) /
      Math.sqrt(Math.max(tokens.length, 1)) / (1 + index * 0.12);
    return {sentence, index, score};
  }).filter(item => words(item.sentence).length <= 70)
    .sort((left, right) => right.score - left.score || left.index - right.index);

  const selected = [];
  let length = 0;
  for (const item of ranked) {
    if (selected.length >= 3 || length + item.sentence.length > 1400) continue;
    selected.push(item);
    length += item.sentence.length;
  }
  return selected.sort((left, right) => left.index - right.index)
    .map(item => item.sentence)
    .join(' ')
    .trim();
}

export function createArticleSummary(text) {
  const normalizedText = cleanText(text);
  if (!normalizedText) return {summary: '', source: 'empty'};
  const abstract = extractAbstract(normalizedText);
  if (abstract) return {summary: abstract, source: 'abstract'};
  const summary = summarizeText(normalizedText);
  return {summary, source: summary ? 'generated' : 'empty'};
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
