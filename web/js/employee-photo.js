(() => {
  document.querySelectorAll('[data-employee-photo]').forEach(image => {
    const fallback=image.nextElementSibling;
    let triedRemote=false;
    const unavailable=()=>{if(!triedRemote && image.dataset.photoFallback){triedRemote=true;image.src=image.dataset.photoFallback;return;}image.hidden=true;if(fallback)fallback.hidden=false;};
    image.addEventListener('error',unavailable);
    image.addEventListener('load',()=>{image.hidden=false;if(fallback)fallback.hidden=true;});
    if(image.complete && !image.naturalWidth)unavailable();
  });
  document.querySelectorAll('[data-photo-check]').forEach(button => {
    button.addEventListener('click',async()=>{
      const result=button.parentElement.querySelector('[data-photo-result]');
      button.disabled=true;result.textContent='Rasm tekshirilmoqda…';
      try{
        const response=await fetch(button.dataset.photoCheck,{credentials:'same-origin',headers:{Accept:'application/json'}});
        if(!response.ok)throw new Error();
        const data=await response.json();result.textContent=data.message;
        if(data.ok){const image=document.querySelector('[data-employee-photo]');if(image){image.src=data.url+(data.url.includes('?')?'&':'?')+'v='+Date.now();}}
      }catch{result.textContent='Tekshirish bajarilmadi. Sahifani yangilab qayta urinib ko‘ring.';}
      finally{button.disabled=false;}
    });
  });
})();
