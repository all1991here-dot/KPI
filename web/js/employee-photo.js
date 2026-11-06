(() => {
  document.querySelectorAll('[data-employee-photo]').forEach(image => {
    const fallback=image.nextElementSibling;
    const unavailable=()=>{image.hidden=true;if(fallback)fallback.hidden=false;};
    image.addEventListener('error',unavailable);
    if(image.complete && !image.naturalWidth)unavailable();
  });
})();
