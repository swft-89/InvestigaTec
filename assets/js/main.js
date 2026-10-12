import {initDashboard} from './pages/dashboard.js';
import {initCatalog} from './pages/catalog.js';
import {initMyArticles} from './pages/my-articles.js';
import {initUpload} from './pages/upload.js';
import {initSummaries} from './pages/summaries.js';
import {initNotifications} from './pages/notifications.js';
import {initAccount} from './pages/account.js';
import {$} from './shared.js';
const routes={inicio:initDashboard,explorar:initCatalog,'mis-articulos':initMyArticles,subir:initUpload,resumenes:initSummaries,notificaciones:initNotifications};
const page=document.body.dataset.page;
if(page==='perfil'){
  import(`./pages/profile.js?v=${window.INVESTIGATEC_PROFILE_ASSET_VERSION}`)
    .then(({initProfile})=>initProfile())
    .catch(error=>{
      console.error('No se pudo cargar el perfil.',error);
      const output=$('#profile-error');
      if(output)output.textContent='No se pudo cargar el perfil. Recarga la página e inténtalo nuevamente.';
    });
}else routes[page]?.();
initAccount();
const sidebar=$('#sidebar');
const menuToggle=$('#menu-toggle');
const sidebarBackdrop=$('#sidebar-backdrop');
const closeSidebar=()=>{
  sidebar?.classList.remove('open');
  document.body.classList.remove('sidebar-open');
  menuToggle?.setAttribute('aria-expanded','false');
  if(sidebarBackdrop)sidebarBackdrop.hidden=true;
};
menuToggle?.addEventListener('click',()=>{
  const open=!sidebar?.classList.contains('open');
  sidebar?.classList.toggle('open',open);
  document.body.classList.toggle('sidebar-open',open);
  menuToggle.setAttribute('aria-expanded',String(open));
  if(sidebarBackdrop)sidebarBackdrop.hidden=!open;
});
$('#sidebar-close')?.addEventListener('click',closeSidebar);
sidebarBackdrop?.addEventListener('click',closeSidebar);
sidebar?.querySelectorAll('a').forEach(link=>link.addEventListener('click',closeSidebar));
document.addEventListener('keydown',event=>{
  if(event.key==='Escape'&&sidebar?.classList.contains('open')){
    closeSidebar();
    menuToggle?.focus();
  }
});
