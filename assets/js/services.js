// Punto único de acceso a datos: reemplazar estas funciones por fetch a endpoints PHP.
// No se guardan contraseñas ni bytes PDF en localStorage.
import {initialArticles} from './demo-data.js';
const get=(key,fallback)=>{try{return JSON.parse(localStorage.getItem(key))??fallback}catch{return fallback}};
const set=(key,value)=>localStorage.setItem(key,JSON.stringify(value));
// API real de autenticación; el resto de servicios siguen en modo demostración.
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
async function apiRequest(url, payload) {
  const options = {credentials:'same-origin',headers:{'Accept':'application/json'}};
  if (payload !== undefined) {
    options.method='POST';
    options.headers['Content-Type']='application/json';
    options.headers['X-CSRF-Token']=csrfToken();
    options.body=JSON.stringify(payload);
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
 getUser:()=>window.INVESTIGATEC_AUTH_USER ? {...get('investigatec_profile_demo',{}),...window.INVESTIGATEC_AUTH_USER} : {name:'Invitado',email:'',major:''},
 saveUser:user=>set('investigatec_profile_demo',user), // Solo preferencias de perfil en demo; NO actualiza usuarios en PostgreSQL.
 getArticles:()=>get('investigatec_articles',initialArticles),
 saveArticles:articles=>set('investigatec_articles',articles),
 getNotices:()=>get('investigatec_notices',[{text:'Un artículo está en revisión.',date:'2026-10-02',read:false}]),
 saveNotices:notices=>set('investigatec_notices',notices),
 reset:()=>['investigatec_profile_demo','investigatec_articles','investigatec_notices'].forEach(k=>localStorage.removeItem(k))
};
