// Punto único de acceso a datos: reemplazar estas funciones por fetch a endpoints PHP.
// No se guardan contraseñas ni bytes PDF en localStorage.
import {initialArticles} from './demo-data.js';
const get=(key,fallback)=>{try{return JSON.parse(localStorage.getItem(key))??fallback}catch{return fallback}};
const set=(key,value)=>localStorage.setItem(key,JSON.stringify(value));
export const service={
 getUser:()=>get('investigatec_user',{name:'Estudiante de prueba',email:'demo@itcj.edu.mx',major:'Ingeniería en Sistemas Computacionales'}),
 saveUser:user=>set('investigatec_user',user),
 getArticles:()=>get('investigatec_articles',initialArticles),
 saveArticles:articles=>set('investigatec_articles',articles),
 getNotices:()=>get('investigatec_notices',[{text:'Un artículo está en revisión.',date:'2026-10-02',read:false}]),
 saveNotices:notices=>set('investigatec_notices',notices),
 reset:()=>['investigatec_user','investigatec_articles','investigatec_notices'].forEach(k=>localStorage.removeItem(k))
};
