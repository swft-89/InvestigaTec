// Punto único de acceso a datos: reemplazar estas funciones por fetch a endpoints PHP.
// No se guardan contraseñas ni bytes PDF en localStorage.
import {initialArticles} from './demo-data.js';
const get=(key,fallback)=>{try{return JSON.parse(localStorage.getItem(key))??fallback}catch{return fallback}};
const set=(key,value)=>localStorage.setItem(key,JSON.stringify(value));
// Autenticación, proyectos y perfil usan la API PHP; otros módulos conservan datos de demostración.
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
async function apiRequest(url, payload) {
  const options = {credentials:'same-origin',headers:{'Accept':'application/json'}};
  if (payload !== undefined) {
    options.method='POST';
    options.headers['X-CSRF-Token']=csrfToken();
    if (payload instanceof FormData) options.body=payload;
    else {
      options.headers['Content-Type']='application/json';
      options.body=JSON.stringify(payload);
    }
  }
  const response=await fetch(url,options);
  let result;
  try { result=await response.json(); }
  catch { throw new Error('Respuesta inválida del servidor. Revisa Apache/PHP.'); }
  if (!response.ok) throw new Error(result.error || 'No se pudo completar la operación.');
  return result;
}
export const service={
 fetchCareers:()=>apiRequest('api/carreras.php'),
 registerStudent:data=>apiRequest('api/register.php',data),
 login:data=>apiRequest('api/login.php',data),
 logout:()=>apiRequest('api/logout.php',{}),
 getProfile:()=>apiRequest('api/profile.php'),
 saveProfile:data=>apiRequest('api/profile.php',data),
 getProjectOptions:()=>apiRequest('api/projects.php'),
 createProject:data=>apiRequest('api/projects.php',data),
 getMyProjects:()=>apiRequest('api/my-projects.php'),
 getProject:id=>apiRequest(`api/project.php?id=${encodeURIComponent(id)}`),
 updateProject:(id,data)=>apiRequest(`api/project.php?id=${encodeURIComponent(id)}`,data),
 getProjectFileUrl:id=>`api/project-file.php?id=${encodeURIComponent(id)}`,
 getPublicProjects:()=>apiRequest('api/public-projects.php'),
 getPublicProject:id=>apiRequest(`api/public-project.php?id=${encodeURIComponent(id)}`),
 getPublicProjectDownloadUrl:id=>`api/project-download.php?id=${encodeURIComponent(id)}`,
 getPublicProjectPreviewUrl:id=>`api/project-download.php?id=${encodeURIComponent(id)}&preview=1`,
 getUser:()=>window.INVESTIGATEC_AUTH_USER ? {...get('investigatec_profile_demo',{}),...window.INVESTIGATEC_AUTH_USER} : {name:'Invitado',email:'',major:''},
 saveUser:user=>set('investigatec_profile_demo',user), // Solo preferencias de perfil en demo; NO actualiza usuarios en PostgreSQL.
 getArticles:()=>get('investigatec_articles',initialArticles),
 saveArticles:articles=>set('investigatec_articles',articles),
 getNotices:()=>apiRequest('api/notifications.php'),
 getUnreadNoticeCount:()=>apiRequest('api/notifications.php?unread_count=1'),
 markNoticesRead:()=>apiRequest('api/notifications.php',{action:'mark_read'}),
 sendSupportRequest:data=>apiRequest('api/support.php',data),
 getAdminDashboard:()=>apiRequest('api/admin.php'),
 adminAction:data=>apiRequest('api/admin.php',data),
 reportProject:data=>apiRequest('api/project-report.php',data),
 getReviewProjects:()=>apiRequest('api/review-projects.php'),
 reset:()=>['investigatec_profile_demo','investigatec_articles','investigatec_notices'].forEach(k=>localStorage.removeItem(k))
};
