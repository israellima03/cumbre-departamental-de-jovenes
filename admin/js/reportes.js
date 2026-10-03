/* ============================================================
   REPORTES.JS — sumas correctas, editar/eliminar gastos
   ============================================================ */
(function(){
'use strict';

var _elimAccion   = null;
var _elimId       = null;
var _elimCallback = null;
var _vistaEconomia = 'iglesia';

document.addEventListener('DOMContentLoaded', function(){
  initTabs();
  initCerrarModales();
  initBotones();
  initEconomiaFiltros();

  /* debounce búsqueda */
  var fn = document.getElementById('rfiltro-nombre');
  if(fn){ var t; fn.addEventListener('input',function(){ clearTimeout(t); t=setTimeout(cargarTablaInscritos,350); }); }

  /* cargar tab inicial */
  cargarInscritos();
});

/* ══════════════════════════════════════
   TABS
══════════════════════════════════════ */
function initTabs(){
  document.querySelectorAll('.rep-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.rep-tab').forEach(function(b){ b.classList.remove('active'); });
      document.querySelectorAll('.rep-panel').forEach(function(p){ p.classList.remove('active'); });
      this.classList.add('active');
      var tab = this.dataset.tab;
      document.getElementById('panel-'+tab).classList.add('active');
      if(tab==='inscritos') cargarInscritos();
      if(tab==='entregas')  cargarEntregas();
      if(tab==='economia')  cargarEconomia();
      if(tab==='gastos')    cargarGastos();
    });
  });
}

/* ══════════════════════════════════════
   INSCRITOS
══════════════════════════════════════ */
function cargarInscritos(){
  fetch('api_reportes.php?accion=stats_inscritos')
  .then(r=>r.json()).then(function(d){
    set('rs-total',      d.total);
    set('rs-confirmados',d.confirmados);
    set('rs-pendientes', d.pendientes);
    set('rs-recaudado',  'Bs. '+fmt(d.recaudado));
  });

  /* por iglesia */
  api('por_iglesia','tb-iglesia',function(rows){
    return rows.map(function(r){
      return tr([r.iglesia, r.total, r.confirmados]);
    }).join('')+totalRow(rows,'total',3);
  });

  /* por distrito */
  api('por_distrito','tb-distrito',function(rows){
    return rows.map(function(r){
      return tr([r.distrito, r.total, r.confirmados]);
    }).join('')+totalRow(rows,'total',3);
  });

  /* por paquete */
  api('por_paquete','tb-paquete',function(rows){
    var suma = rows.reduce(function(s,r){ return s+r.recaudado; },0);
    return rows.map(function(r){
      return tr([r.paquete, r.total, 'Bs. '+fmt(r.recaudado)]);
    }).join('')+'<tr style="font-weight:700;background:var(--bg);"><td colspan="2">Total</td><td>Bs. '+fmt(suma)+'</td></tr>';
  });

  /* por método */
  api('por_metodo','tb-metodo',function(rows){
    var suma = rows.reduce(function(s,r){ return s+r.recaudado; },0);
    return rows.map(function(r){
      return tr([r.metodo, r.total, 'Bs. '+fmt(r.recaudado)]);
    }).join('')+'<tr style="font-weight:700;background:var(--bg);"><td colspan="2">Total</td><td>Bs. '+fmt(suma)+'</td></tr>';
  });

  cargarTablaInscritos();
}

window.cargarTablaInscritos = function(){
  var estado = val('rfiltro-estado');
  var pago   = val('rfiltro-pago');
  var nombre = (document.getElementById('rfiltro-nombre')||{}).value||'';
  var url    = 'api_reportes.php?accion=tabla_inscritos';
  if(estado) url+='&estado='+estado;
  if(pago)   url+='&pago='+pago;

  fetch(url).then(r=>r.json()).then(function(data){
    var lista = data.inscritos||[];
    if(nombre) lista=lista.filter(function(i){ return (i.nombre+' '+i.apellido).toLowerCase().includes(nombre.toLowerCase()); });

    var lbl = document.getElementById('rep-inscritos-lbl');
    if(lbl) lbl.textContent = lista.length+' registros';

    /* suma total recaudado filtrado */
    var sumaFilt = lista.reduce(function(s,i){ return s + (i.estado_pago==='confirmado' ? i.precio_final : 0); },0);
    var pie = document.getElementById('total-inscritos-pie');
    if(pie) pie.textContent = 'Total recaudado (filtrado): Bs. '+fmt(sumaFilt);

    var tb = document.getElementById('tb-inscritos-rep');
    if(!lista.length){ tb.innerHTML='<tr><td colspan="11" class="tabla-vacia">Sin resultados</td></tr>'; return; }
    tb.innerHTML = lista.map(function(i,idx){
      var est = i.estado_pago==='confirmado'
        ? '<span class="badge badge-confirmado">Confirmado</span>'
        : '<span class="badge badge-pendiente">Pendiente</span>';
      var met = i.metodo_pago==='efectivo'
        ? '<span class="metodo-efectivo"><i class="fa-solid fa-money-bill"></i> Efectivo</span>'
        : '<span class="metodo-qr"><i class="fa-solid fa-qrcode"></i> QR</span>';
      return '<tr>'+
        '<td>'+(idx+1)+'</td>'+
        '<td style="font-weight:600;white-space:nowrap;">'+esc(i.nombre)+' '+esc(i.apellido)+'</td>'+
        '<td>'+esc(i.celular)+'</td>'+
        '<td style="font-size:12px;">'+esc(i.iglesia)+'</td>'+
        '<td class="col-hide-sm" style="font-size:12px;">'+esc(i.distrito)+'</td>'+
        '<td style="font-size:12px;">'+esc(i.paquete)+'</td>'+
        '<td>'+met+'</td>'+
        '<td>'+est+'</td>'+
        '<td class="col-hide-sm" style="font-weight:600;">Bs. '+fmt(i.precio_final)+'</td>'+
        '<td class="col-hide-md" style="font-size:11px;">'+esc(i.registrado_por)+'</td>'+
        '<td class="col-hide-md" style="font-size:11px;">'+esc(i.confirmo_por)+'</td>'+
      '</tr>';
    }).join('');
  });
};

/* ══════════════════════════════════════
   ENTREGAS
══════════════════════════════════════ */
function cargarEntregas(){
  fetch('api_reportes.php?accion=stats_entregas')
  .then(r=>r.json()).then(function(d){
    set('re-mat-entregados',  d.mat_entregados);
    set('re-mat-pendientes',  d.mat_pendientes);
  });

  cargarTablaEntregas();
}

window.cargarTablaEntregas = function(){
  var estado = val('rfiltro-entrega-estado');
  var url    = 'api_reportes.php?accion=tabla_entregas';
  if(estado) url+='&estado='+estado;

  fetch(url).then(r=>r.json()).then(function(data){
    var tb    = document.getElementById('tb-entregas-rep');
    var lista = data.entregas||[];
    if(!lista.length){ tb.innerHTML='<tr><td colspan="6" class="tabla-vacia">Sin resultados</td></tr>'; return; }
    tb.innerHTML = lista.map(function(e,i){
      var est = e.entregado=='1'
        ? '<span class="badge badge-entregado">Entregado</span>'
        : '<span class="badge badge-pend-ent">Pendiente</span>';
      return '<tr>'+
        '<td>'+(i+1)+'</td>'+
        '<td style="font-weight:600;font-size:12px;">'+esc(e.inscrito)+'</td>'+
        '<td style="font-size:12px;">'+esc(e.item)+'</td>'+
        '<td><span style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;">'+esc(e.tipo_item)+'</span></td>'+
        '<td>'+est+'</td>'+
        '<td class="col-hide-sm" style="font-size:11px;color:var(--txt-soft);">'+esc(e.entregado_por)+'</td>'+
      '</tr>';
    }).join('');
  });
};

/* ══════════════════════════════════════
   ECONOMÍA — vista dinámica
══════════════════════════════════════ */
function initEconomiaFiltros(){
  document.querySelectorAll('.ec-filtro-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.ec-filtro-btn').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
      _vistaEconomia = this.dataset.vista;
      cargarVistaEconomia();
    });
  });
}

function cargarEconomia(){
  fetch('api_reportes.php?accion=stats_economia')
  .then(r=>r.json()).then(function(d){
    set('ec-ingresos',  'Bs. '+fmt(d.ingresos));
    set('ec-ofrendas',  'Bs. '+fmt(d.ofrendas));
    set('ec-gastos',    'Bs. '+fmt(d.gastos));
  });
  cargarVistaEconomia();
}

function cargarVistaEconomia(){
  var vista = _vistaEconomia;
  var accionMap = {
    iglesia:  'rec_por_iglesia',
    distrito: 'rec_por_distrito',
    metodo:   'rec_por_metodo'
  };
  var titulosMap = {
    iglesia:  '<i class="fa-solid fa-church"></i> Recaudado por Iglesia',
    distrito: '<i class="fa-solid fa-map"></i> Recaudado por Distrito',
    metodo:   '<i class="fa-solid fa-credit-card"></i> Recaudado por Método de Pago'
  };
  var headMap = {
    iglesia:  '<tr><th>Iglesia</th><th>Inscritos</th><th>Recaudado (Bs.)</th></tr>',
    distrito: '<tr><th>Distrito</th><th>Inscritos</th><th>Recaudado (Bs.)</th></tr>',
    metodo:   '<tr><th>Método</th><th>Total</th><th>Recaudado (Bs.)</th></tr>'
  };

  var tit = document.getElementById('ec-tabla-titulo');
  var hed = document.getElementById('ec-thead');
  if(tit) tit.innerHTML = titulosMap[vista]||'';
  if(hed) hed.innerHTML = headMap[vista]||'';

  var tb = document.getElementById('tb-economia-dyn');
  tb.innerHTML = '<tr><td colspan="4" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>';

  fetch('api_reportes.php?accion='+accionMap[vista])
  .then(r=>r.json()).then(function(data){
    var rows = data.datos||[];
    if(!rows.length){ tb.innerHTML='<tr><td colspan="4" class="tabla-vacia">Sin datos</td></tr>'; return; }

    var suma = 0;
    var html = '';

    var campoMonto = 'recaudado';
    var campoNum   = (vista==='metodo') ? 'total' : 'inscritos';
    var campoNom   = vista;
    suma = rows.reduce(function(s,r){ return s+r[campoMonto]; },0);
    html = rows.map(function(r){
      return '<tr>'+
        '<td style="font-weight:600;">'+esc(r[campoNom]||r.metodo||'—')+'</td>'+
        '<td>'+r[campoNum]+'</td>'+
        '<td style="font-weight:700;color:var(--green);">Bs. '+fmt(r[campoMonto])+'</td>'+
      '</tr>';
    }).join('');
    html += '<tr style="font-weight:700;background:var(--bg);"><td colspan="2">Total</td><td>Bs. '+fmt(suma)+'</td></tr>';

    tb.innerHTML = html;
    var pie = document.getElementById('ec-suma-pie');
    if(pie) pie.textContent = 'Bs. '+fmt(suma);
  });
}

/* ══════════════════════════════════════
   GASTOS Y OFRENDAS
══════════════════════════════════════ */
function cargarGastos(){
  /* gastos */
  fetch('api_reportes.php?accion=listar_gastos')
  .then(r=>r.json()).then(function(data){
    var lista = data.gastos||[];
    var tb    = document.getElementById('tb-gastos');
    var lbl   = document.getElementById('gastos-lbl');
    var suma  = lista.reduce(function(s,g){ return s+g.monto; },0);
    if(lbl) lbl.textContent = lista.length+' registros';
    set('total-gastos-pie','Bs. '+fmt(suma));
    if(!lista.length){ tb.innerHTML='<tr><td colspan="6" class="tabla-vacia">Sin gastos</td></tr>'; return; }
    tb.innerHTML = lista.map(function(g,i){
      var acc = PUEDE_EDITAR_REP
        ? '<td style="white-space:nowrap;">'+
            '<button class="btn-accion btn-ver" onclick="abrirModalGasto(\''+encodeURIComponent(JSON.stringify(g))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
            '<button class="btn-accion" style="color:#dc2626;" onclick="eliminar(\'eliminar_gasto\','+g.id+',\'¿Eliminar gasto?\',cargarGastos)"><i class="fa-solid fa-trash"></i></button>'+
          '</td>'
        : '';
      return '<tr>'+
        '<td>'+(i+1)+'</td>'+
        '<td style="font-size:13px;white-space:normal;">'+esc(g.motivo)+'</td>'+
        '<td style="font-size:12px;">'+esc(g.responsable)+'</td>'+
        '<td style="font-weight:700;color:var(--accent);">Bs. '+fmt(g.monto)+'</td>'+
        '<td class="col-hide-sm" style="font-size:12px;">'+esc(g.fecha||'')+'</td>'+
        acc+
      '</tr>';
    }).join('');
  });

  /* ofrendas */
  fetch('api_reportes.php?accion=listar_ofrendas')
  .then(r=>r.json()).then(function(data){
    var lista = data.ofrendas||[];
    var tb    = document.getElementById('tb-ofrendas');
    var lbl   = document.getElementById('ofrendas-lbl');
    var suma  = lista.reduce(function(s,o){ return s+o.monto; },0);
    if(lbl) lbl.textContent = lista.length+' registros';
    set('total-ofrendas-pie','Bs. '+fmt(suma));
    if(!lista.length){ tb.innerHTML='<tr><td colspan="6" class="tabla-vacia">Sin ofrendas</td></tr>'; return; }
    tb.innerHTML = lista.map(function(o,i){
      var acc = PUEDE_EDITAR_REP
        ? '<td style="white-space:nowrap;">'+
            '<button class="btn-accion btn-ver" onclick="abrirModalOfrenda(\''+encodeURIComponent(JSON.stringify(o))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
            '<button class="btn-accion" style="color:#dc2626;" onclick="eliminar(\'eliminar_ofrenda\','+o.id+',\'¿Eliminar ofrenda?\',cargarGastos)"><i class="fa-solid fa-trash"></i></button>'+
          '</td>'
        : '';
      return '<tr>'+
        '<td>'+(i+1)+'</td>'+
        '<td style="font-weight:600;font-size:13px;">'+esc(o.de_parte_de)+'</td>'+
        '<td style="font-size:12px;">'+esc(o.recibido_nom)+'</td>'+
        '<td style="font-weight:700;color:var(--green);">Bs. '+fmt(o.monto)+'</td>'+
        '<td class="col-hide-sm" style="font-size:12px;">'+esc(o.fecha||'')+'</td>'+
        acc+
      '</tr>';
    }).join('');
  });
}
window.cargarGastos = cargarGastos;

/* ══════════════════════════════════════
   MODALES GASTO / OFRENDA
══════════════════════════════════════ */
window.abrirModalGasto = function(json){
  var g = typeof json === 'string' ? JSON.parse(decodeURIComponent(json)) : (json || {});
  document.getElementById('gasto-id').value          = g.id          || '';
  document.getElementById('gasto-motivo').value      = g.motivo      || '';
  document.getElementById('gasto-monto').value       = g.monto       || '';
  document.getElementById('gasto-fecha').value       = g.fecha       || hoy();
  document.getElementById('gasto-responsable').value = g.responsable || '';
  document.getElementById('modal-gasto-titulo').innerHTML =
    '<i class="fa-solid fa-receipt"></i> '+(g.id?'Editar':'Nuevo')+' Gasto';
  abrirModalRep('modal-gasto');
};

window.abrirModalOfrenda = function(json){
  var o = typeof json === 'string' ? JSON.parse(decodeURIComponent(json)) : (json || {});
  document.getElementById('ofrenda-id').value    = o.id          || '';
  document.getElementById('ofrenda-de').value    = o.de_parte_de || '';
  document.getElementById('ofrenda-monto').value = o.monto       || '';
  document.getElementById('ofrenda-fecha').value = o.fecha       || hoy();
  document.getElementById('ofrenda-notas').value = o.notas       || '';
  document.getElementById('modal-ofrenda-titulo').innerHTML =
    '<i class="fa-solid fa-hand-holding-heart"></i> '+(o.id?'Editar':'Nueva')+' Ofrenda';
  abrirModalRep('modal-ofrenda');
};
/* ══════════════════════════════════════
   ELIMINAR
══════════════════════════════════════ */
window.eliminar = function(accion, id, msg, callback){
  _elimAccion=accion; _elimId=id; _elimCallback=callback;
  var txt=document.getElementById('eliminar-rep-txt');
  if(txt) txt.textContent=msg;
  abrirModalRep('modal-eliminar-rep');
};

/* ══════════════════════════════════════
   BOTONES
══════════════════════════════════════ */
function initBotones(){
  boton('btn-guardar-gasto', function(){
    enviar('guardar_gasto',{
      id: val('gasto-id'), motivo: val('gasto-motivo'),
      monto: val('gasto-monto'), fecha: val('gasto-fecha'),
      responsable: val('gasto-responsable')
    },'modal-gasto', cargarGastos);
  });

  boton('btn-guardar-ofrenda', function(){
    enviar('guardar_ofrenda',{
      id: val('ofrenda-id'), de_parte_de: val('ofrenda-de'),
      monto: val('ofrenda-monto'), fecha: val('ofrenda-fecha'),
      notas: val('ofrenda-notas')
    },'modal-ofrenda', cargarGastos);
  });

  boton('btn-confirmar-eliminar-rep', function(){
    if(!_elimAccion||!_elimId) return;
    var btn = document.getElementById('btn-confirmar-eliminar-rep');
    btn.disabled=true;
    var fd=new FormData(); fd.append('accion',_elimAccion); fd.append('id',_elimId);
    fetch('api_reportes.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(function(data){
      btn.disabled=false;
      if(data.ok){ toast(data.msg,'ok'); cerrarModalRep('modal-eliminar-rep'); if(_elimCallback) _elimCallback(); }
      else toast(data.msg,'error');
    }).catch(function(){ btn.disabled=false; toast('Error de conexion','error'); });
  });
}

/* ══════════════════════════════════════
   HELPERS
══════════════════════════════════════ */
function api(accion, tbId, renderFn){
  fetch('api_reportes.php?accion='+accion)
  .then(r=>r.json()).then(function(data){
    var tb=document.getElementById(tbId); if(!tb) return;
    var rows=data.datos||[];
    if(!rows.length){ tb.innerHTML='<tr><td colspan="5" class="tabla-vacia">Sin datos</td></tr>'; return; }
    tb.innerHTML = renderFn(rows);
  });
}
function totalRow(rows, campo, cols){
  var suma = rows.reduce(function(s,r){ return s+parseInt(r[campo]||0); },0);
  return '<tr style="font-weight:700;background:var(--bg);">'+
    '<td>Total</td><td colspan="'+(cols-1)+'">'+suma+'</td></tr>';
}
function enviar(accion, datos, modalId, callback){
  var fd=new FormData(); fd.append('accion',accion);
  Object.keys(datos).forEach(function(k){ fd.append(k,datos[k]); });
  fetch('api_reportes.php',{method:'POST',body:fd})
  .then(r=>r.json()).then(function(data){
    if(data.ok){ toast(data.msg,'ok'); cerrarModalRep(modalId); if(callback) callback(); }
    else toast(data.msg,'error');
  }).catch(function(){ toast('Error de conexion','error'); });
}
function boton(id, fn){
  var el=document.getElementById(id); if(el) el.addEventListener('click',fn);
}
function initCerrarModales(){
  document.querySelectorAll('.modal-overlay').forEach(function(el){
    el.addEventListener('click',function(e){ if(e.target===this) this.style.display='none'; });
  });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape') document.querySelectorAll('.modal-overlay').forEach(function(el){ el.style.display='none'; });
  });
}
function abrirModalRep(id){ var el=document.getElementById(id); if(el) el.style.display='grid'; }
window.cerrarModalRep = function(id){ var el=document.getElementById(id); if(el) el.style.display='none'; };
function set(id,v){ var el=document.getElementById(id); if(el) el.textContent=v; }
function val(id){ var el=document.getElementById(id); return el?el.value.trim():''; }
function fmt(n){ return parseFloat(n||0).toFixed(2); }
function hoy(){ return new Date().toISOString().substring(0,10); }
function encDat(obj){ return '\''+encodeURIComponent(JSON.stringify(obj))+'\''; }
function tr(cols){ return '<tr>'+cols.map(function(c){ return '<td>'+esc(String(c??'—'))+'</td>'; }).join('')+'</tr>'; }
function toast(msg,tipo){
  var el=document.getElementById('toast-reportes'); if(!el) return;
  el.textContent=msg; el.className='toast show'+(tipo?' toast-'+tipo:'');
  clearTimeout(toast._t); toast._t=setTimeout(function(){ el.classList.remove('show'); },3500);
}
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

})();