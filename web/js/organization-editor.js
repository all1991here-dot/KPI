(() => {
  const type=document.querySelector('[data-org-editor-type]');
  const parent=document.querySelector('[data-org-editor-parent]');
  const university=document.querySelector('[data-university-picker]');
  const universityField=document.querySelector('[data-university-field]');
  if(!type || !parent)return;
  const filter=()=>{
    const kind=type.value;
    if(universityField)universityField.hidden=kind!=='department';
    [...parent.options].forEach(option=>{
      let allowed=!option.value || (kind==='faculty'?option.dataset.kind==='university':(['department','unit'].includes(kind) && ['university','faculty'].includes(option.dataset.kind)));
      if(kind==='university' && option.value)allowed=false;
      if(allowed && option.value && kind==='department' && university?.value)allowed=option.dataset.university===university.value;
      option.hidden=!allowed;option.disabled=!allowed;
    });
    if(parent.selectedOptions[0]?.disabled || kind==='university')parent.value='';
    parent.required=kind!=='university';
  };
  type.addEventListener('change',filter);university?.addEventListener('change',filter);
  parent.addEventListener('change',()=>{if(university && parent.value)university.value=parent.selectedOptions[0].dataset.university||'';filter();});
  filter();
})();
