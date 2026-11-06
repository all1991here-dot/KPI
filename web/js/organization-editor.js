(() => {
  document.querySelectorAll('[data-organization-parent-picker]').forEach(group => {
    const university=group.querySelector('[data-university-picker]');
    const faculty=group.querySelector('[data-faculty-picker]');
    const filter=()=>{
      [...faculty.options].forEach(option=>{const allowed=!option.value || !university.value || option.dataset.university===university.value;option.hidden=!allowed;option.disabled=!allowed;});
      if(faculty.selectedOptions[0]?.disabled)faculty.value='';
    };
    university.addEventListener('change',filter);
    faculty.addEventListener('change',()=>{if(faculty.value)university.value=faculty.selectedOptions[0].dataset.university||'';filter();});
    filter();
  });
})();
