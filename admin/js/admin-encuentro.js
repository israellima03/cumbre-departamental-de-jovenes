/* ============================================================
   ADMIN-ENCUENTRO.JS — Solo lógica del dashboard
   ============================================================ */
(function(){
'use strict';

var todosInscritos     = [];
var inscritosFiltrados = [];
var paginaTabla        = 1;
var porPagina          = 15;
var inscritoCredencial = null;

/* ══════════════════════════════════════
   HELPERS GLOBALES (definidos primero)
══════════════════════════════════════ */
function setText(id,v){ var el=document.getElementById(id); if(el) el.textContent=v; }
function val(id){ var el=document.getElementById(id); return el?el.value.trim():''; }
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function toast(msg,tipo){
  var el=document.getElementById('toast-dashboard'); if(!el) return;
  el.textContent=msg;
  el.className='toast show'+(tipo?' toast-'+tipo:'');
  clearTimeout(toast._t);
  toast._t=setTimeout(function(){ el.classList.remove('show'); },3500);
}
/* ══════════════════════════════════════
   INIT
══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function(){

  ['modal-inscripcion-overlay','modal-editar-overlay','modal-entrega-overlay'].forEach(function(id){
    var el=document.getElementById(id);
    if(el) el.addEventListener('click',function(e){ if(e.target===this) this.style.display='none'; });
  });

  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
      ['modal-inscripcion-overlay','modal-editar-overlay','modal-entrega-overlay'].forEach(function(id){
        var el=document.getElementById(id); if(el) el.style.display='none';
      });
    }
  });

  cargarStats();
  cargarInscritos();
  initFiltros();
  initNuevaInscripcion();
  initEditarInscrito();
  initCredencial();
  initExportarPDF();
  initModalEntrega();
});

/* ══════════════════════════════════════
   STATS
══════════════════════════════════════ */
function cargarStats(){
  fetch('api_dashboard.php?accion=stats')
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(!data.ok) return;
    animarNum('stat-total',data.total);
    animarNum('stat-pendientes',data.pendientes);
    animarNum('stat-confirmados',data.confirmados);
    animarNum('stat-cupos',data.cupos_disp);
    setText('stat-hoy','+'+data.hoy+' hoy');
    setText('stat-conf-sub',data.confirmados+' pagos verificados');
    var pct=data.cupos_total>0?Math.round(((data.cupos_total-data.cupos_disp)/data.cupos_total)*100):0;
    var bar=document.getElementById('stat-cupos-bar');
    var pctEl=document.getElementById('stat-cupos-pct');
    if(bar) setTimeout(function(){ bar.style.width=pct+'%'; },300);
    if(pctEl) pctEl.textContent=pct+'% ocupado';
  }).catch(function(e){ console.error('stats:',e); });
}

/* ══════════════════════════════════════
   INSCRITOS
══════════════════════════════════════ */
function cargarInscritos(){
  var tbody=document.getElementById('tbody-inscritos');
  if(tbody) tbody.innerHTML='<tr><td colspan="13" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>';
  fetch('api_dashboard.php?accion=listar_inscritos')
  .then(function(r){ return r.json(); })
  .then(function(data){
    todosInscritos=data.inscritos||[];
    inscritosFiltrados=todosInscritos.slice();
    renderTabla();
  }).catch(function(e){ console.error('inscritos:',e); });
}

function renderTabla(){
  var tbody=document.getElementById('tbody-inscritos');
  var lbl=document.getElementById('total-lbl');
  if(!tbody) return;

  if(!inscritosFiltrados.length){
    tbody.innerHTML='<tr><td colspan="13" class="tabla-vacia"><i class="fa-solid fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;color:var(--border);"></i>Sin resultados</td></tr>';
    if(lbl) lbl.textContent='0 registros';
    var pag=document.getElementById('paginacion'); if(pag) pag.innerHTML='';
    return;
  }

  if(lbl) lbl.textContent=inscritosFiltrados.length+' registros';
  var inicio=(paginaTabla-1)*porPagina;
  var pagina=inscritosFiltrados.slice(inicio,inicio+porPagina);

  tbody.innerHTML=pagina.map(function(ins,i){
    var badge=ins.estado_pago==='confirmado'
      ?'<span class="badge badge-confirmado"><i class="fa-solid fa-circle-check"></i> Confirmado</span>'
      :'<span class="badge badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>';
    var metodo=ins.metodo_pago==='efectivo'
      ?'<span class="metodo-efectivo"><i class="fa-solid fa-money-bill"></i> Efectivo</span>'
      :'<span class="metodo-qr"><i class="fa-solid fa-qrcode"></i> QR</span>';
    var fecha=ins.fecha_pago?ins.fecha_pago.substring(0,10):'—';

    var matEnt='<span class="ent-na">—</span>';
    if(ins.material_entregado!==null && ins.material_entregado!==undefined){
      if(String(ins.material_entregado)==='1'){
        var tituloM='Entregado por: '+(ins.material_entregado_por||'—');
        matEnt=PUEDE_ENTREGAR
          ?'<button class="badge-ent badge-ent-si" onclick="abrirEntrega(\'material\','+ins.inscripcion_id+',0)" title="'+tituloM+'"><i class="fa-solid fa-check"></i> Sí</button>'
          :'<span class="badge-ent badge-ent-si" title="'+tituloM+'"><i class="fa-solid fa-check"></i> Sí</span>';
      } else {
        matEnt=PUEDE_ENTREGAR
          ?'<button class="badge-ent badge-ent-no" onclick="abrirEntrega(\'material\','+ins.inscripcion_id+',1)"><i class="fa-solid fa-xmark"></i> No</button>'
          :'<span class="badge-ent badge-ent-no"><i class="fa-solid fa-xmark"></i> No</span>';
      }
    }

    var acciones='';
    if(typeof PUEDE_GESTIONAR!=='undefined'&&PUEDE_GESTIONAR){
      var insJson=encodeURIComponent(JSON.stringify(ins));
      acciones+='<button class="btn-accion btn-ver" onclick="abrirEditar(\''+insJson+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> ';
    }
    if(ins.estado_pago==='confirmado'){
      acciones+='<button class="btn-accion btn-credencial" onclick="descargarCredencial('+ins.id+')" title="Credencial PDF"><i class="fa-solid fa-id-card"></i></button>';
    }
    if(typeof ES_ADMIN!=='undefined'&&ES_ADMIN){
      acciones+=' <button class="btn-accion btn-eliminar" onclick="eliminarInscrito('+ins.id+',\''+esc(ins.nombre+' '+ins.apellido)+'\')" title="Eliminar inscrito"><i class="fa-solid fa-trash"></i></button>';
    }
    return '<tr>'+
      '<td>'+(inicio+i+1)+'</td>'+
      '<td><div class="participante-cell">'+
        '<div class="participante-avatar">'+(ins.nombre[0]||'').toUpperCase()+(ins.apellido[0]||'').toUpperCase()+'</div>'+
        '<div><div class="participante-nombre">'+esc(ins.nombre)+' '+esc(ins.apellido)+'</div>'+
        '<div class="participante-tipo">'+esc(ins.carnet)+'</div></div>'+
      '</div></td>'+
      '<td>'+esc(ins.celular||'—')+'</td>'+
      '<td>'+esc(ins.iglesia||'—')+'</td>'+
      '<td>'+esc(ins.distrito||'—')+'</td>'+
      '<td>'+esc(ins.paquete||'—')+'</td>'+
      '<td>'+metodo+'</td>'+
      '<td>'+badge+'</td>'+
      '<td class="col-entrega">'+matEnt+'</td>'+
      '<td>'+fecha+'</td>'+
      '<td class="col-usuario">'+esc(ins.registro_por||'—')+'</td>'+
      '<td class="col-usuario">'+esc(ins.confirmo_por||'—')+'</td>'+
      '<td class="col-acciones">'+acciones+'</td>'+
    '</tr>';
  }).join('');

  renderPaginacion();
}

function renderPaginacion(){
  var el=document.getElementById('paginacion'); if(!el) return;
  var totalPags=Math.ceil(inscritosFiltrados.length/porPagina);
  if(totalPags<=1){ el.innerHTML=''; return; }
  var html='<button class="pag-btn" '+(paginaTabla<=1?'disabled':'')+' onclick="cambiarPag('+(paginaTabla-1)+')"><i class="fa-solid fa-chevron-left"></i></button>';
  var ini=Math.max(1,paginaTabla-2), fin=Math.min(totalPags,paginaTabla+2);
  if(ini>1) html+='<button class="pag-btn" onclick="cambiarPag(1)">1</button>'+(ini>2?'<span style="color:var(--txt-xsoft);padding:0 4px;">…</span>':'');
  for(var i=ini;i<=fin;i++) html+='<button class="pag-btn '+(i===paginaTabla?'active':'')+'" onclick="cambiarPag('+i+')">'+i+'</button>';
  if(fin<totalPags) html+=(fin<totalPags-1?'<span style="color:var(--txt-xsoft);padding:0 4px;">…</span>':'')+'<button class="pag-btn" onclick="cambiarPag('+totalPags+')">'+totalPags+'</button>';
  html+='<button class="pag-btn" '+(paginaTabla>=totalPags?'disabled':'')+' onclick="cambiarPag('+(paginaTabla+1)+')"><i class="fa-solid fa-chevron-right"></i></button>';
  el.innerHTML=html;
}

window.cambiarPag=function(p){
  var totalPags=Math.ceil(inscritosFiltrados.length/porPagina);
  if(p<1||p>totalPags) return;
  paginaTabla=p; renderTabla();
  var card=document.querySelector('.card');
  if(card) card.scrollIntoView({behavior:'smooth',block:'start'});
};

/* ══════════════════════════════════════
   FILTROS
══════════════════════════════════════ */
function initFiltros(){
  ['filtro-carnet','filtro-iglesia','filtro-distrito','filtro-pago','filtro-estado'].forEach(function(id){
    var el=document.getElementById(id);
    if(el){ el.addEventListener('input',aplicarFiltros); el.addEventListener('change',aplicarFiltros); }
  });
}

function aplicarFiltros(){
  var q=val('filtro-carnet'),igl=val('filtro-iglesia'),dist=val('filtro-distrito'),pago=val('filtro-pago'),est=val('filtro-estado');
  inscritosFiltrados=todosInscritos.filter(function(ins){
    return (!q||(ins.nombre+' '+ins.apellido+' '+ins.carnet).toLowerCase().includes(q.toLowerCase()))
      &&(!igl||String(ins.iglesia_id)===igl)
      &&(!dist||String(ins.distrito_id)===dist)
      &&(!pago||ins.metodo_pago===pago)
      &&(!est||ins.estado_pago===est);
  });
  paginaTabla=1; renderTabla();
}

/* ══════════════════════════════════════
   MODAL ENTREGA
══════════════════════════════════════ */
var _entregaTipo=null, _entregaIns=null, _entregaVal=null;

function initModalEntrega(){
  var btnConf=document.getElementById('btn-confirmar-entrega');
  if(btnConf) btnConf.addEventListener('click',function(){
    var fd=new FormData();
    fd.append('accion','marcar_material_entregado');
    fd.append('inscripcion_id',_entregaIns);
    fd.append('entregado',_entregaVal);
    btnConf.disabled=true;
    fetch('api_dashboard.php',{method:'POST',body:fd})
    .then(function(r){ return r.json(); })
    .then(function(data){
      btnConf.disabled=false;
      if(data.ok){ toast(data.msg,'ok'); document.getElementById('modal-entrega-overlay').style.display='none'; cargarInscritos(); }
      else toast(data.msg,'error');
    }).catch(function(){ toast('Error de conexion','error'); btnConf.disabled=false; });
  });
}

window.abrirEntrega=function(tipo,inscripcionId,nuevoVal){
  _entregaTipo=tipo; _entregaIns=inscripcionId; _entregaVal=nuevoVal;
  var detalle=document.getElementById('entrega-detalle');
  var txt=document.getElementById('entrega-txt');
  var accion=nuevoVal===1?'marcar como ENTREGADO':'desmarcar (NO entregado)';
  txt.textContent='¿Confirmas '+accion+' el material/regalo de este inscrito?';
  detalle.innerHTML='<p style="text-align:center;color:var(--txt-xsoft);font-size:12px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</p>';
  document.getElementById('modal-entrega-overlay').style.display='grid';
  fetch('api_dashboard.php?accion=detalle_entrega&inscripcion_id='+inscripcionId+'&tipo='+tipo)
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(!data.ok||!data.items||!data.items.length){ detalle.innerHTML=''; return; }
    var html='<div style="background:var(--bg);border-radius:8px;padding:12px;margin-bottom:4px;">'+
      '<p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--txt-xsoft);margin-bottom:8px;">'+
        'Material a entregar:'+
      '</p>'+
      data.items.map(function(it){
        var gen=it.genero==='mujer'?'<i class="fa-solid fa-venus" style="color:#e879a0;"></i> Mujer'
          :it.genero==='unisex'?'<i class="fa-solid fa-circle" style="color:#8b5cf6;"></i> Unisex'
          :it.genero?'<i class="fa-solid fa-mars" style="color:#5C625B;"></i> Hombre':'';
        return '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">'+
          '<span style="font-weight:600;font-size:13px;">'+esc(it.nombre)+'</span>'+
          '<span style="font-size:12px;color:var(--txt-soft);">x'+it.cantidad+(it.talla?' | <strong>'+esc(it.talla)+'</strong>':'')+(it.genero?' '+gen:'')+'</span>'+
        '</div>';
      }).join('')+'</div>';
    detalle.innerHTML=html;
  }).catch(function(){ detalle.innerHTML=''; });
};

/* ══════════════════════════════════════
   NUEVA INSCRIPCION EFECTIVO
══════════════════════════════════════ */
function initNuevaInscripcion(){
  var btnAbrir=document.getElementById('btn-nueva-inscripcion');
  if(btnAbrir) btnAbrir.addEventListener('click',function(){
    resetModalInscripcion();
    document.getElementById('modal-inscripcion-overlay').style.display='grid';
  });

  var elIgl=document.getElementById('ni-iglesia');
  if(elIgl) elIgl.addEventListener('change',function(){
    var opt=this.options[this.selectedIndex];
    document.getElementById('ni-distrito-id').value=opt.dataset.distritoId||'';
    document.getElementById('ni-distrito-nombre').value=opt.dataset.distrito||'Sin distrito';
  });

  var btnRes=document.getElementById('btn-ni-resumen');
  if(btnRes) btnRes.addEventListener('click',function(){
    if(!validarFormInscripcion()) return;
    mostrarResumenInscripcion();
  });

  var btnConf=document.getElementById('btn-ni-confirmar');
  if(btnConf) btnConf.addEventListener('click',confirmarInscripcionEfectivo);
}

function resetModalInscripcion(){
  document.getElementById('paso-datos').style.display='block';
  document.getElementById('paso-resumen').style.display='none';
  document.getElementById('btn-ni-resumen').style.display='inline-flex';
  document.getElementById('btn-ni-confirmar').style.display='none';
  document.getElementById('btn-ni-volver').style.display='none';
  ['ni-nombre','ni-apellido','ni-carnet','ni-celular','ni-edad','ni-distrito-nombre','ni-distrito-id'].forEach(function(id){
    var el=document.getElementById(id); if(el) el.value='';
  });
  var nt=document.getElementById('ni-tipo'); if(nt) nt.value='';
  var ni=document.getElementById('ni-iglesia'); if(ni) ni.value='';
  document.querySelectorAll('input[name="ni-paquete"]').forEach(function(r){ r.checked=false; });
}


function validarFormInscripcion(){
  var ok = true;
  var mensajes = [];

  /* limpiar bordes anteriores */
  ['ni-nombre','ni-apellido','ni-carnet','ni-edad','ni-celular'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.style.borderColor = '';
  });

  function marcarError(id){
    var el = document.getElementById(id);
    if(el) el.style.borderColor = '#dc2626';
  }

  /* ── NOMBRE ── */
  var nombre = val('ni-nombre');
  if(!nombre){
    marcarError('ni-nombre'); mensajes.push('El nombre es obligatorio'); ok = false;
  } else if(!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(nombre)){
    marcarError('ni-nombre'); mensajes.push('El nombre solo puede contener letras'); ok = false;
  } else if(nombre.length < 2){
    marcarError('ni-nombre'); mensajes.push('El nombre debe tener al menos 2 letras'); ok = false;
  } else if(nombre.length > 50){
    marcarError('ni-nombre'); mensajes.push('El nombre no puede superar 50 caracteres'); ok = false;
  }

  /* ── APELLIDO ── */
  var apellido = val('ni-apellido');
  if(!apellido){
    marcarError('ni-apellido'); mensajes.push('El apellido es obligatorio'); ok = false;
  } else if(!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(apellido)){
    marcarError('ni-apellido'); mensajes.push('El apellido solo puede contener letras'); ok = false;
  } else if(apellido.length < 2){
    marcarError('ni-apellido'); mensajes.push('El apellido debe tener al menos 2 letras'); ok = false;
  } else if(apellido.length > 60){
    marcarError('ni-apellido'); mensajes.push('El apellido no puede superar 60 caracteres'); ok = false;
  }

  /* ── CARNET ── */
  var carnet = val('ni-carnet');
  if(!carnet){
    marcarError('ni-carnet'); mensajes.push('El carnet es obligatorio'); ok = false;
  } else if(!/^\d{6,8}(-\d{1,2}[A-Za-z]?)?[A-Za-z]?$/.test(carnet)){
    marcarError('ni-carnet'); mensajes.push('Carnet inválido. Ej: 1234567 o 1234567-1A'); ok = false;
  }

  /* ── CELULAR ── */
  var celular = val('ni-celular');
  if(!celular){
    marcarError('ni-celular'); mensajes.push('El celular es obligatorio'); ok = false;
  } else if(!/^\d{8}$/.test(celular)){
    marcarError('ni-celular'); mensajes.push('El celular debe tener exactamente 8 dígitos'); ok = false;
  } else if(!/^[67]/.test(celular)){
    marcarError('ni-celular'); mensajes.push('El celular debe empezar con 6 o 7'); ok = false;
  }

  /* ── EDAD ── */
  var edad = parseInt(val('ni-edad'), 10);
  if(!val('ni-edad')){
    marcarError('ni-edad'); mensajes.push('La edad es obligatoria'); ok = false;
  } else if(isNaN(edad) || edad < 5 || edad > 100){
    marcarError('ni-edad'); mensajes.push('Ingresa una edad válida (5 a 100)'); ok = false;
  }

  /* ── TIPO ── */
  if(!val('ni-tipo')){
    mensajes.push('Selecciona el tipo de inscrito'); ok = false;
  }

  /* ── IGLESIA ── */
  if(!val('ni-iglesia')){
    mensajes.push('Selecciona la iglesia'); ok = false;
  }

  /* ── PAQUETE ── */
  if(!document.querySelector('input[name="ni-paquete"]:checked')){
    mensajes.push('Selecciona un paquete'); ok = false;
  }

  if(!ok){
    toast(mensajes[0], 'error');
    /* mostrar todos si hay más de uno con un pequeño delay */
    if(mensajes.length > 1){
      setTimeout(function(){
        toast(mensajes.length + ' campos requieren atención', 'warn');
      }, 600);
    }
  }

  return ok;
}


function mostrarResumenInscripcion(){
  var radio=document.querySelector('input[name="ni-paquete"]:checked');
  var precioPaq=parseFloat(radio.dataset.precio)||0, total=precioPaq;
  var iglSel=document.getElementById('ni-iglesia');
  var iglNombre=iglSel&&iglSel.selectedIndex>=0?iglSel.options[iglSel.selectedIndex].text:'—';
  var html='<div style="font-size:13px;">'+
    fila('Nombre',esc(val('ni-nombre')+' '+val('ni-apellido')))+
    fila('Carnet',esc(val('ni-carnet')))+
    fila('Edad',esc(val('ni-edad'))+' años')+
    fila('Celular',esc(val('ni-celular')))+
    fila('Iglesia',esc(iglNombre))+
    fila('Paquete',esc(radio.dataset.nombre)+' — Bs. '+precioPaq.toFixed(2))+
    '<div style="background:#2C2668;color:#fff;border-radius:8px;padding:12px 16px;display:flex;justify-content:space-between;margin-top:12px;">'+
    '<span style="font-weight:700;">TOTAL A PAGAR</span>'+
    '<strong style="font-size:18px;">Bs. '+total.toFixed(2)+'</strong></div></div>';
  document.getElementById('resumen-contenido').innerHTML=html;
  document.getElementById('paso-datos').style.display='none';
  document.getElementById('paso-resumen').style.display='block';
  document.getElementById('btn-ni-resumen').style.display='none';
  document.getElementById('btn-ni-confirmar').style.display='inline-flex';
  document.getElementById('btn-ni-volver').style.display='inline-flex';
}

function fila(l,v){
  return '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;">'+
    '<span style="color:#666;font-weight:600;">'+l+'</span><span>'+v+'</span></div>';
}

window.volverDatos=function(){
  document.getElementById('paso-datos').style.display='block';
  document.getElementById('paso-resumen').style.display='none';
  document.getElementById('btn-ni-resumen').style.display='inline-flex';
  document.getElementById('btn-ni-confirmar').style.display='none';
  document.getElementById('btn-ni-volver').style.display='none';
};

function confirmarInscripcionEfectivo(){
  var radio=document.querySelector('input[name="ni-paquete"]:checked');
  var fd=new FormData();
  fd.append('accion','registrar_efectivo');
  fd.append('nombre',val('ni-nombre')); fd.append('apellido',val('ni-apellido'));
  fd.append('carnet',val('ni-carnet'));
  fd.append('edad',val('ni-edad')); fd.append('celular',val('ni-celular'));
  fd.append('tipo_inscrito_id',val('ni-tipo')); fd.append('iglesia_id',val('ni-iglesia'));
  fd.append('distrito_id',val('ni-distrito-id')); fd.append('paquete_id',radio.value);
  var btnConf=document.getElementById('btn-ni-confirmar');
  btnConf.disabled=true; btnConf.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';
  fetch('api_dashboard.php',{method:'POST',body:fd})
  .then(function(r){ return r.json(); })
  .then(function(data){
    btnConf.disabled=false; btnConf.innerHTML='<i class="fa-solid fa-check"></i> Registrar Inscripción';
    if(data.ok){
      window.cerrarModalInscripcion();
      toast('Inscripción registrada. Queda pendiente hasta que tesorería confirme el pago.','ok');
      cargarInscritos(); cargarStats();
    } else toast(data.msg,'error');
  }).catch(function(){ toast('Error de conexion','error'); btnConf.disabled=false; });
}

window.cerrarModalInscripcion=function(){
  document.getElementById('modal-inscripcion-overlay').style.display='none';
};

/* ══════════════════════════════════════
   EDITAR INSCRITO
══════════════════════════════════════ */
function initEditarInscrito(){
  var btnG=document.getElementById('btn-guardar-editar');
  if(btnG) btnG.addEventListener('click',function(){
    var fd=new FormData();
    fd.append('accion','editar_inscrito');
    fd.append('id',val('edit-inscrito-id'));
    fd.append('nombre',val('edit-nombre')); fd.append('apellido',val('edit-apellido'));
    fd.append('carnet',val('edit-carnet'));
    fd.append('edad',val('edit-edad')); fd.append('celular',val('edit-celular'));
    fetch('api_dashboard.php',{method:'POST',body:fd})
    .then(function(r){ return r.json(); })
    .then(function(data){
      if(data.ok){ toast('Inscrito actualizado','ok'); window.cerrarModalEditar(); cargarInscritos(); }
      else toast(data.msg,'error');
    });
  });
}

window.abrirEditar=function(json){
  var ins=JSON.parse(decodeURIComponent(json));
  document.getElementById('edit-inscrito-id').value=ins.id;
  document.getElementById('edit-nombre').value=ins.nombre;
  document.getElementById('edit-apellido').value=ins.apellido;
  document.getElementById('edit-carnet').value=ins.carnet;
  document.getElementById('edit-edad').value=ins.edad||'';
  document.getElementById('edit-celular').value=ins.celular||'';
  document.getElementById('modal-editar-overlay').style.display='grid';
};

window.cerrarModalEditar=function(){
  document.getElementById('modal-editar-overlay').style.display='none';
};

/* ══════════════════════════════════════
   CREDENCIAL
══════════════════════════════════════ */
function initCredencial(){
  var inp=document.getElementById('buscar-credencial');
  var btn=document.getElementById('btn-descargar-credencial');
  var timer;
  if(btn) btn.disabled=true;
  inscritoCredencial=null;
  if(inp) inp.addEventListener('input',function(){
    clearTimeout(timer);
    var q=this.value.trim();
    inscritoCredencial=null;
    if(btn) btn.disabled=true;
    var res=document.getElementById('credencial-resultados');
    if(q.length<2){ if(res) res.innerHTML=''; return; }
    timer=setTimeout(function(){
      fetch('api_dashboard.php?accion=buscar_inscrito&q='+encodeURIComponent(q))
      .then(function(r){ return r.json(); })
      .then(function(data){
        if(!res) return;
        if(!data.inscritos||!data.inscritos.length){
          res.innerHTML='<p style="color:var(--txt-xsoft);font-size:12px;margin-top:4px;">Sin resultados</p>';
          return;
        }
        res.innerHTML=data.inscritos.map(function(i){
          return '<div class="cred-resultado" onclick="seleccionarCredencial('+i.id+',\''+esc(i.nombre+' '+i.apellido)+'\')">'+
            '<i class="fa-solid fa-user" style="color:var(--accent);margin-right:6px;"></i>'+
            esc(i.nombre)+' '+esc(i.apellido)+' — <small style="color:var(--txt-xsoft);">'+esc(i.carnet)+'</small></div>';
        }).join('');
      }).catch(function(){});
    },350);
  });
  if(btn) btn.addEventListener('click',function(){
    if(!inscritoCredencial){ toast('Selecciona un inscrito primero','warn'); return; }
    descargarCredencial(inscritoCredencial);
  });
}

window.seleccionarCredencial=function(id,nombre){
  inscritoCredencial=id;
  document.getElementById('buscar-credencial').value=nombre;
  document.getElementById('credencial-resultados').innerHTML='';
  var btn=document.getElementById('btn-descargar-credencial');
  if(btn) btn.disabled=false;
};

window.descargarCredencial=function(inscritoId){
  var btn=document.getElementById('btn-descargar-credencial');
  if(btn){ btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Generando...'; }
  var fd=new FormData();
  fd.append('accion','descargar_credencial');
  fd.append('inscrito_id',inscritoId);
  fetch('api_dashboard.php',{method:'POST',body:fd})
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(btn){ btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-download"></i> <span class="btn-txt-label"> Descargar Credencial PDF</span>'; }
    if(data.ok&&data.credencial){
      window.open('../credenciales/'+data.credencial,'_blank');
      toast('Credencial generada','ok');
    } else toast(data.msg||'Error al generar credencial','error');
  }).catch(function(){
    if(btn){ btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-download"></i> <span class="btn-txt-label"> Descargar Credencial PDF</span>'; }
    toast('Error de conexion','error');
  });
};

/* ══════════════════════════════════════
   EXPORTAR PDF
══════════════════════════════════════ */
/* ══════════════════════════════════════
   EXPORTAR PDF
══════════════════════════════════════ */
function initExportarPDF(){
  var btn = document.getElementById('btn-exportar-pdf-tabla');
  if(!btn) return;
  btn.addEventListener('click', function(){
    if(!inscritosFiltrados.length){ toast('No hay datos para exportar','warn'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando...';

    function cargarScript(src, cb){
      var s = document.createElement('script');
      s.src = src;
      s.onload = cb;
      s.onerror = function(){
        toast('Error al cargar librería PDF','error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> <span class="btn-txt-label">Exportar PDF</span>';
      };
      document.head.appendChild(s);
    }

    function intentarGenerar(){
      if(window.jspdf && window.jspdf.jsPDF && window.jspdf.jsPDF.API && window.jspdf.jsPDF.API.autoTable){
        generarPDFTabla(btn);
      } else if(window.jspdf && window.jspdf.jsPDF){
        /* jsPDF cargó pero falta autoTable */
        cargarScript(
          'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js',
          function(){ setTimeout(function(){ generarPDFTabla(btn); }, 100); }
        );
      } else {
        /* cargar jsPDF primero */
        cargarScript(
          'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
          function(){
            cargarScript(
              'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js',
              function(){ setTimeout(function(){ generarPDFTabla(btn); }, 100); }
            );
          }
        );
      }
    }
    intentarGenerar();
  });
}

function generarPDFTabla(btn){
  try {
    var doc = new window.jspdf.jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });

    /* ── cabecera ── */
    doc.setFillColor(44, 38, 104);
    doc.rect(0, 0, 297, 18, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(11);
    doc.setFont('helvetica','bold');
    doc.text('Inscritos — Cumbre Departamental de Jóvenes 2026', 10, 7);
    doc.setFontSize(8);
    doc.setFont('helvetica','normal');
    doc.text('IDDP Oruro · Oruro, noviembre 2026', 10, 13);
    var fecha = new Date().toLocaleString('es-BO');
    doc.text('Generado: ' + fecha + '   Total: ' + inscritosFiltrados.length + ' registros', 287, 13, { align:'right' });
    doc.setTextColor(0, 0, 0);

    /* ── filas ── */
    var body = inscritosFiltrados.map(function(ins, i){
      var matEnt = '—';
      if(ins.material_entregado !== null && ins.material_entregado !== undefined){
        matEnt = String(ins.material_entregado) === '1' ? '✓' : '✗';
      }
      return [
        i + 1,
        (ins.nombre || '') + ' ' + (ins.apellido || ''),
        ins.carnet   || '—',
        ins.celular  || '—',
        ins.iglesia  || '—',
        ins.distrito || '—',
        ins.paquete  || '—',
        ins.metodo_pago  || '—',
        ins.estado_pago  || '—',
        matEnt,
        ins.fecha_pago ? ins.fecha_pago.substring(0,10) : '—'
      ];
    });

    /* ── tabla ── */
    doc.autoTable({
      startY: 22,
      head: [[
        '#','Nombre Completo','Carnet','Celular',
        'Iglesia','Distrito','Paquete',
        'Método','Estado','Mat.','Fecha'
      ]],
      body: body,
      styles: {
        fontSize: 7.5,
        cellPadding: 2.5,
        overflow: 'linebreak',
        valign: 'middle'
      },
      headStyles: {
        fillColor: [44, 38, 104],
        textColor: 255,
        fontStyle: 'bold',
        fontSize: 8
      },
      alternateRowStyles: {
        fillColor: [244, 242, 240]
      },
      columnStyles: {
        0:  { cellWidth: 8,  halign:'center' },   /* # */
        1:  { cellWidth: 38 },                     /* Nombre */
        2:  { cellWidth: 18 },                     /* Carnet */
        3:  { cellWidth: 18 },                     /* Celular */
        4:  { cellWidth: 28 },                     /* Iglesia */
        5:  { cellWidth: 22 },                     /* Distrito */
        6:  { cellWidth: 30 },                     /* Paquete */
        7:  { cellWidth: 16, halign:'center' },    /* Método */
        8:  { cellWidth: 20, halign:'center' },    /* Estado */
        9:  { cellWidth: 12, halign:'center' },    /* Mat */
        10: { cellWidth: 22, halign:'center' }     /* Fecha */
      },
      didDrawCell: function(data){
        /* colorear ✓ en verde y ✗ en rojo */
        if(data.section === 'body' && data.column.index === 9){
          var val = String(data.cell.raw);
          if(val === '✓') data.cell.styles.textColor = [6, 95, 70];
          if(val === '✗') data.cell.styles.textColor = [220, 0, 43];
        }
        /* colorear estado */
        if(data.section === 'body' && data.column.index === 8){
          var est = String(data.cell.raw);
          if(est === 'confirmado') data.cell.styles.textColor = [6, 95, 70];
          if(est === 'pendiente')  data.cell.styles.textColor = [146, 64, 14];
        }
      },
      margin: { top: 22, left: 7, right: 7, bottom: 10 },
      tableWidth: 'auto'
    });

    /* ── pie de página ── */
    var totalPags = doc.internal.getNumberOfPages();
    for(var p = 1; p <= totalPags; p++){
      doc.setPage(p);
      doc.setFontSize(7);
      doc.setTextColor(150);
      doc.text(
        'Sistema de inscripciones — IDDP Oruro · Lima Technology   |   Pág. ' + p + '/' + totalPags,
        148.5, 205, { align:'center' }
      );
    }

    var nombreArchivo = 'inscritos_' + new Date().toISOString().substring(0,10) + '.pdf';
    doc.save(nombreArchivo);

    toast('PDF descargado correctamente','ok');
  } catch(e){
    console.error('PDF error:', e);
    toast('Error al generar PDF: ' + e.message, 'error');
  } finally {
    if(btn){
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> <span class="btn-txt-label">Exportar PDF</span>';
    }
  }
}
/* ══════════════════════════════════════
   ANIMACIONES
══════════════════════════════════════ */
function animarNum(id,objetivo){
  var el=document.getElementById(id); if(!el) return;
  var t0=null,dur=700;
  function step(ts){
    if(!t0) t0=ts;
    var p=Math.min((ts-t0)/dur,1);
    el.textContent=Math.round(p*objetivo);
    if(p<1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

window.eliminarInscrito = function(inscritoId, nombre){
  if(!confirm('⚠ ELIMINAR INSCRITO\n\n¿Estás seguro de eliminar a "'+nombre+'" del sistema?\n\nEsto eliminará:\n• Su inscripción\n• Sus regalos\n• Recuperará los cupos\n\nEsta acción NO se puede deshacer.')) return;
  if(!confirm('Segunda confirmación requerida.\n\n¿Confirmas eliminar permanentemente a "'+nombre+'"?')) return;

  var fd = new FormData();
  fd.append('accion', 'eliminar_inscrito');
  fd.append('inscrito_id', inscritoId);

  fetch('api_dashboard.php', {method:'POST', body:fd})
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.ok){
      toast(data.msg, 'ok');
      cargarInscritos();
      cargarStats();
    } else {
      toast(data.msg || 'Error al eliminar', 'error');
    }
  })
  .catch(function(){
    toast('Error de conexion', 'error');
  });
};

})();