import {service} from './services.js';

export const $=selector=>document.querySelector(selector);
export const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
export function toast(text){const element=$('#toast');if(!element)return;element.textContent=text;element.classList.add('show');setTimeout(()=>element.classList.remove('show'),3500)}
export const own=article=>article.owner===service.getUser().email;
export const accessible=article=>(article.status==='Aprobado'&&article.visibility==='Público')||own(article);
export function limitMultiSelect(select,max,onLimit){
  let previous=[...select.selectedOptions].map(option=>option.value).slice(0,max);
  select.addEventListener('change',()=>{
    const selected=[...select.selectedOptions].map(option=>option.value);
    if(selected.length>max){
      const allowed=new Set(previous.filter(value=>selected.includes(value)).slice(0,max));
      for(const value of selected){
        if(allowed.size>=max)break;
        allowed.add(value);
      }
      for(const option of select.options)option.selected=allowed.has(option.value);
      onLimit(true);
    }else{
      onLimit(false);
    }
    previous=[...select.selectedOptions].map(option=>option.value);
  });
}
export function articleCard(article){
  const publicArticle=article.id_proyecto!==undefined;
  const title=article.titulo??article.title;
  const area=article.areas??article.area;
  const date=article.fecha??article.date;
  const description=article.resumen??article.description;
  const author=article.autores??article.author;
  const detailLink=publicArticle?`<a href="?page=detalle-articulo&amp;id=${encodeURIComponent(article.id_proyecto)}">${escapeHtml(title)}</a>`:escapeHtml(title);
  return `<article class="article"><div class="article-meta"><span>${escapeHtml(date)}</span></div><h3>${detailLink}</h3><p class="article-summary">${escapeHtml(description)}</p><div class="article-areas"><span class="label">${escapeHtml(area)}</span></div><div class="article-foot">${escapeHtml(author)} · ${publicArticle?'Publicado':escapeHtml(article.status)}</div></article>`
}
