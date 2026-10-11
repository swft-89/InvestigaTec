import {service} from './services.js';

export const $=selector=>document.querySelector(selector);
export const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
export function toast(text){const element=$('#toast');if(!element)return;element.textContent=text;element.classList.add('show');setTimeout(()=>element.classList.remove('show'),3500)}
export const own=article=>article.owner===service.getUser().email;
export const accessible=article=>(article.status==='Aprobado'&&article.visibility==='Público')||own(article);
export function articleCard(article){return `<article class="article"><div class="article-meta"><span class="label">${escapeHtml(article.area)}</span><span>${escapeHtml(article.date)}</span></div><h3>${escapeHtml(article.title)}</h3><p>${escapeHtml(article.description)}</p><div class="article-foot">${escapeHtml(article.author)} · ${escapeHtml(article.status)}</div></article>`}
