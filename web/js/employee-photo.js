(() => {
  document.querySelectorAll('[data-employee-photo]').forEach(image => {
    const fallback=image.nextElementSibling;
    let triedRemote=false;
    const unavailable=()=>{if(!triedRemote && image.dataset.photoFallback){triedRemote=true;image.src=image.dataset.photoFallback;return;}image.hidden=true;if(fallback)fallback.hidden=false;};
    image.addEventListener('error',unavailable);
    if(image.complete && !image.naturalWidth)unavailable();
  });
})();
