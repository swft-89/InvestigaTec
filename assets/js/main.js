import {initDashboard} from './pages/dashboard.js';
import {initCatalog} from './pages/catalog.js';
import {initMyArticles} from './pages/my-articles.js';
import {initArticle} from './pages/article.js';
import {initPublicArticle} from './pages/public-article.js';
import {initUpload} from './pages/upload.js';
import {initSummaries} from './pages/summaries.js';
import {initNotifications} from './pages/notifications.js';
import {initHelp} from './pages/help.js';
import {initAdmin} from './pages/admin.js';
import {initReviewProjects} from './pages/review-projects.js';
import {initAccount} from './pages/account.js';
import {$} from './shared.js';
import {service} from './services.js';
const routes={inicio:initDashboard,explorar:initCatalog,'mis-articulos':initMyArticles,articulo:initArticle,'detalle-articulo':initPublicArticle,subir:initUpload,resumenes:initSummaries,notificaciones:initNotifications,ayuda:initHelp,admin:initAdmin,revision:initReviewProjects};
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
const unreadNotificationsBadge=$('#unread-notifications-count');
async function refreshUnreadNotifications(){
  if(!unreadNotificationsBadge||!window.INVESTIGATEC_AUTH_USER)return;
  try{
    const {count}=await service.getUnreadNoticeCount();
    unreadNotificationsBadge.textContent=count>99?'99+':String(count);
    unreadNotificationsBadge.hidden=count===0;
    unreadNotificationsBadge.setAttribute('aria-label',`${count} notificaciones sin leer`);
  }catch(error){
    console.error('No se pudo consultar el contador de notificaciones.',error);
  }
}
document.addEventListener('notifications:updated',refreshUnreadNotifications);
refreshUnreadNotifications();
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
