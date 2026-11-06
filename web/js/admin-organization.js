(() => {
  'use strict';
  document.querySelectorAll('[data-org-structure]').forEach(form => {
    const kind=form.querySelector('[data-org-kind]'),parent=form.querySelector('[data-org-parent]');
    function update(){const required={university:[],faculty:['university'],department:['university','faculty'],unit:['university','faculty']}[kind.value]||[];
      [...parent.options].forEach(option=>{const allowed=!option.value || required.includes(option.dataset.kind);option.hidden=!allowed;option.disabled=!allowed;});
      if(parent.selectedOptions[0]?.disabled || !required.length)parent.value='';parent.required=!!required.length;
    }
    kind.addEventListener('change',update);update();
  });
  document.querySelectorAll('[data-org-assignment]').forEach(group => {
    const canonical=group.querySelector('[name=org_id]');
    const levels=['university','faculty','department'];
    const unitSelect=group.querySelector('[data-org-level="unit"]');
    const selects=levels.map(kind=>group.querySelector(`[data-org-level="${kind}"]`));
    const rows=[...canonical.options].filter(o=>o.value).map(o=>({id:o.value,parent:o.dataset.parent,kind:o.dataset.kind,label:o.textContent}));
    const byId=new Map(rows.map(row=>[row.id,row]));
    function sync(){const chain={};let row=byId.get(canonical.value);const seen=new Set();while(row && !seen.has(row.id)){seen.add(row.id);chain[row.kind]=row.id;row=byId.get(row.parent);}
      if(unitSelect){unitSelect.replaceChildren(new Option('Yo‘q',''));rows.filter(r=>r.kind==='unit' && (r.parent===chain.university || r.parent===chain.faculty)).forEach(r=>unitSelect.add(new Option(r.label.split(' → ').pop(),r.id)));unitSelect.value=chain.unit||'';unitSelect.disabled=!chain.university;}
      selects.forEach((select,index)=>{const kind=levels[index],parent=index?chain[levels[index-1]]:null;
        select.replaceChildren(new Option('Yo‘q',''));rows.filter(r=>r.kind===kind && (!index || r.parent===parent || (index===2 && !chain.faculty && r.parent===chain.university))).forEach(r=>select.add(new Option(r.label.split(' → ').pop(),r.id)));
        select.value=chain[kind]||'';select.disabled=index===2?!chain.university:(index>0 && !parent);
      });
    }
    selects.forEach((select,index)=>select.addEventListener('change',()=>{canonical.value=select.value || (index?selects[index-1].value:'');sync();}));
    unitSelect?.addEventListener('change',()=>{canonical.value=unitSelect.value || selects[2].value || selects[1].value || selects[0].value;sync();});
    canonical.addEventListener('change',sync);sync();
    group.querySelector('[data-org-cascade]').hidden=false;
  });
})();
