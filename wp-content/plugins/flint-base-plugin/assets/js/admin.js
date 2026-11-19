document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.flint-tabs a');
  const panels = document.querySelectorAll('.flint-panel');
  const show = (hash) => {
    const id = (hash || '#layouts').replace('#','');
    tabs.forEach(t => t.classList.toggle('nav-tab-active', t.getAttribute('href') === '#' + id));
    panels.forEach(p => p.classList.toggle('active', p.id === id));
  };
  tabs.forEach(tab => tab.addEventListener('click', e => { e.preventDefault(); history.replaceState(null,'',tab.hash); show(tab.hash); }));
  show(location.hash);

  document.querySelectorAll('[data-block-action]').forEach(btn => btn.addEventListener('click', () => {
    const panel = btn.closest('.flint-post-type-blocks') || document;
    panel.querySelectorAll('input[type="checkbox"][name*="[block_rules]"]').forEach(cb => cb.checked = btn.dataset.blockAction === 'all');
  }));

  document.querySelectorAll('[data-group-block-action]').forEach(btn => btn.addEventListener('click', () => {
    const group = btn.closest('.flint-block-group');
    if (!group) return;
    group.querySelectorAll('input[type="checkbox"][name*="[block_rules]"]').forEach(cb => cb.checked = btn.dataset.groupBlockAction === 'all');
  }));


  document.querySelectorAll('[data-block-post-type]').forEach(btn => btn.addEventListener('click', () => {
    const type = btn.dataset.blockPostType;
    document.querySelectorAll('[data-block-post-type]').forEach(b => b.classList.toggle('button-primary', b === btn));
    document.querySelectorAll('[data-block-post-type-panel]').forEach(panel => { panel.hidden = panel.dataset.blockPostTypePanel !== type; });
  }));

  document.querySelectorAll('[data-add-row]').forEach(btn => btn.addEventListener('click', () => {
    const type = btn.dataset.addRow;
    const tpl = document.querySelector(`template[data-template="${type}"]`);
    const rows = document.querySelector(`[data-rows="${type}"]`);
    if (!tpl || !rows) return;
    const index = Date.now().toString();
    rows.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__INDEX__', index));
  }));
  document.addEventListener('click', e => {
    if (e.target.matches('[data-remove-row]')) e.target.closest('.flint-row')?.remove();
  });
  document.addEventListener('input', e => {
    if (e.target.matches('.flint-color-input')) {
      const swatch = e.target.closest('.flint-row')?.querySelector('.flint-swatch');
      if (swatch && /^#[0-9a-f]{6}$/i.test(e.target.value)) swatch.style.background = e.target.value;
    }
  });
});

// Launch checklist: save each manual check immediately.
document.addEventListener('change', function(e){
  if(!e.target.matches('[data-launch-check]') || e.target.disabled) return;
  var item=e.target.closest('[data-launch-item]');
  var list=e.target.closest('.flint-launch-list');
  if(!item || !list) return;
  item.classList.add('is-saving');
  var body=new URLSearchParams();
  body.append('action','flint_launch_toggle'); body.append('nonce',list.dataset.launchNonce||'');
  body.append('key',item.dataset.launchItem||''); body.append('complete',e.target.checked?'1':'0');
  fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString(),credentials:'same-origin'})
    .then(function(r){return r.json();}).then(function(r){
      if(!r.success) throw new Error((r.data&&r.data.message)||'Unable to save');
      item.classList.toggle('is-complete',!!r.data.complete);
      var meta=item.querySelector('[data-launch-meta]');
      if(meta) meta.textContent=r.data.complete?'Completed '+r.data.timestamp+' by '+r.data.user:'';
      var link=item.querySelector('[data-launch-link]'); if(link) link.hidden=!!r.data.complete;
      var all=list.querySelectorAll('[data-launch-item]').length, done=list.querySelectorAll('[data-launch-item].is-complete').length;
      var doneEl=document.querySelector('[data-launch-done]'), prog=document.querySelector('[data-launch-progress]');
      if(doneEl) doneEl.textContent=done; if(prog){prog.max=all;prog.value=done;}
    }).catch(function(err){ e.target.checked=!e.target.checked; window.alert(err.message); })
    .finally(function(){item.classList.remove('is-saving');});
});

// Accessibility scanner.
document.addEventListener('click', function(e){
  if(!e.target.matches('[data-a11y-scan]')) return;
  var btn=e.target, status=document.querySelector('[data-a11y-status]'); btn.disabled=true; if(status)status.textContent='Scanning published URLs…';
  var body=new URLSearchParams(); body.append('action','flint_accessibility_scan'); body.append('nonce',window.flintBaseAdmin?.a11yNonce||'');
  fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString(),credentials:'same-origin'}).then(r=>r.json()).then(r=>{if(!r.success)throw new Error(r.data?.message||'Scan failed'); if(status)status.textContent=`${r.data.scanned} URLs scanned; ${r.data.count} issue groups found. Reloading report…`; location.hash='#accessibility'; location.reload();}).catch(err=>{if(status)status.textContent=err.message;}).finally(()=>btn.disabled=false);
});

// Launch source-folder rename action.
document.addEventListener('click', function(e){
 if(!e.target.matches('[data-launch-rename-source]'))return; var list=e.target.closest('.flint-launch-list'); if(!list)return;
 if(!confirm('Rename the active theme base-src folder using today’s date?'))return; var body=new URLSearchParams();body.append('action','flint_launch_rename_source');body.append('nonce',list.dataset.launchNonce||'');e.target.disabled=true;
 fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString(),credentials:'same-origin'}).then(r=>r.json()).then(r=>{if(!r.success)throw new Error(r.data?.message||'Rename failed');alert(r.data.message);}).catch(err=>alert(err.message)).finally(()=>e.target.disabled=false);
});

// Accessibility report filters + client-side pagination.
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-a11y-report]').forEach(function(report){
    const rows=Array.from(report.querySelectorAll('[data-a11y-row]'));
    const filters={}; report.querySelectorAll('[data-a11y-filter]').forEach(el=>filters[el.dataset.a11yFilter]=el);
    const per=report.querySelector('[data-a11y-per-page]'), count=report.querySelector('[data-a11y-count]'), pager=report.querySelector('[data-a11y-pagination]');
    let page=1;
    function matches(row){
      const q=(filters.search?.value||'').trim().toLowerCase();
      return (!q || row.dataset.search.includes(q)) && (!filters.wcag?.value || row.dataset.wcag===filters.wcag.value) && (!filters.issue?.value || row.dataset.issue===filters.issue.value) && (!filters.page?.value || row.dataset.page===filters.page.value);
    }
    function render(){
      const visible=rows.filter(matches), size=parseInt(per?.value||'25',10), pages=Math.max(1,Math.ceil(visible.length/size)); page=Math.min(page,pages);
      rows.forEach(r=>r.hidden=true); visible.slice((page-1)*size,page*size).forEach(r=>r.hidden=false);
      if(count) count.textContent=visible.length+' of '+rows.length+' issues';
      if(pager){ pager.innerHTML=''; if(pages>1){
        const prev=document.createElement('button'); prev.type='button'; prev.className='button'; prev.textContent='‹ Previous'; prev.disabled=page===1; prev.onclick=()=>{page--;render();}; pager.append(prev);
        const label=document.createElement('span'); label.className='paging-input'; label.textContent=' Page '+page+' of '+pages+' '; pager.append(label);
        const next=document.createElement('button'); next.type='button'; next.className='button'; next.textContent='Next ›'; next.disabled=page===pages; next.onclick=()=>{page++;render();}; pager.append(next);
      }}
    }
    Object.values(filters).forEach(el=>el.addEventListener(el.type==='search'?'input':'change',()=>{page=1;render();}));
    per?.addEventListener('change',()=>{page=1;render();});
    report.querySelector('[data-a11y-clear]')?.addEventListener('click',()=>{Object.values(filters).forEach(el=>el.value='');page=1;render();});
    render();
  });
});

// Security scanner + report filters/pagination.
document.addEventListener('click', function(e){
  if(!e.target.matches('[data-security-scan]')) return;
  var btn=e.target,status=document.querySelector('[data-security-status]'); btn.disabled=true; if(status)status.textContent='Scanning site, configuration, headers, endpoints, and local source…';
  var body=new URLSearchParams();body.append('action','flint_security_scan');body.append('nonce',window.flintBaseAdmin?.securityNonce||'');
  fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString(),credentials:'same-origin'}).then(r=>r.json()).then(r=>{if(!r.success)throw new Error(r.data?.message||'Scan failed');if(status)status.textContent=`${r.data.scanned} URLs scanned; ${r.data.count} warnings found. Reloading report…`;location.hash='#security';location.reload();}).catch(err=>{if(status)status.textContent=err.message;}).finally(()=>btn.disabled=false);
});
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('[data-security-report]').forEach(function(report){
 const rows=Array.from(report.querySelectorAll('[data-security-row]')),filters={};report.querySelectorAll('[data-security-filter]').forEach(el=>filters[el.dataset.securityFilter]=el);const per=report.querySelector('[data-security-per-page]'),count=report.querySelector('[data-security-count]'),pager=report.querySelector('[data-security-pagination]');let page=1;
 function matches(r){const q=(filters.search?.value||'').trim().toLowerCase();return(!q||r.dataset.search.includes(q))&&(!filters.severity?.value||r.dataset.severity===filters.severity.value)&&(!filters.issue?.value||r.dataset.issue===filters.issue.value);}
 function render(){const visible=rows.filter(matches),size=parseInt(per?.value||'25',10),pages=Math.max(1,Math.ceil(visible.length/size));page=Math.min(page,pages);rows.forEach(r=>r.hidden=true);visible.slice((page-1)*size,page*size).forEach(r=>r.hidden=false);if(count)count.textContent=visible.length+' of '+rows.length+' warnings';if(pager){pager.innerHTML='';if(pages>1){const prev=document.createElement('button');prev.type='button';prev.className='button';prev.textContent='‹ Previous';prev.disabled=page===1;prev.onclick=()=>{page--;render();};pager.append(prev);const label=document.createElement('span');label.className='paging-input';label.textContent=' Page '+page+' of '+pages+' ';pager.append(label);const next=document.createElement('button');next.type='button';next.className='button';next.textContent='Next ›';next.disabled=page===pages;next.onclick=()=>{page++;render();};pager.append(next);}}}
 Object.values(filters).forEach(el=>el.addEventListener(el.type==='search'?'input':'change',()=>{page=1;render();}));per?.addEventListener('change',()=>{page=1;render();});report.querySelector('[data-security-clear]')?.addEventListener('click',()=>{Object.values(filters).forEach(el=>el.value='');page=1;render();});render();
});});
