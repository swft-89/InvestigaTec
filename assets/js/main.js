import {service} from './services.js';
import {initDashboard} from './pages/dashboard.js';
import {initCatalog} from './pages/catalog.js';
import {initMyArticles} from './pages/my-articles.js';
import {initUpload} from './pages/upload.js';
import {initProfile} from './pages/profile.js';
import {initSummaries} from './pages/summaries.js';
import {initNotifications} from './pages/notifications.js';
import {initAccount} from './pages/account.js';
export const $=s=>document.querySelector(s);
export const escapeHtml=s=>String(s??'').replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
export function toast(text){let el=$('#toast');el.textContent=text;el.classList.add('show');setTimeout(()=>el.classList.remove('show'),3500)}
export const own=a=>a.owner===service.getUser().email;
export const accessible=a=>(a.status==='Aprobado'&&a.visibility==='Público')||own(a);
export function articleCard(a){return `<article class="article"><div class="article-meta"><span class="label">${escapeHtml(a.area)}</span><span>${escapeHtml(a.date)}</span></div><h3>${escapeHtml(a.title)}</h3><p>${escapeHtml(a.description)}</p><div class="article-foot">${escapeHtml(a.author)} · ${escapeHtml(a.status)}</div></article>`}
const routes={inicio:initDashboard,explorar:initCatalog,'mis-articulos':initMyArticles,subir:initUpload,perfil:initProfile,resumenes:initSummaries,notificaciones:initNotifications,acceso:initAccount};
const page=document.body.dataset.page;routes[page]?.();$('#account-status').textContent=service.getUser().name;
$('#menu-toggle')?.addEventListener('click',()=>$('#sidebar').classList.toggle('open'));
