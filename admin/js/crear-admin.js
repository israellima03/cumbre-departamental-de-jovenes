/**
 * crear-admin.js
 * Lógica del formulario "Crear Nuevo Administrador"
 * - Validaciones en tiempo real
 * - Filtros en cascada: Distrito → Iglesia → Ministerio
 * - Medidor de fuerza de contraseña
 * - Preview dinámico del nuevo usuario
 * - Submit con fetch a admin_api.php
 */

(function () {
  'use strict';

  /* ========================================================
     INIT
     ======================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    initRoles();
    initPassword();
    initCascada();
    initPreview();
    initValidacionesEnTiempoReal();
    //initSubmit();
  });

  /* ========================================================
     ROLES — checkboxes personalizados
     ======================================================== */
  function initRoles() {
    document.querySelectorAll('.ca-rol-checkbox').forEach(function (cb) {
      cb.addEventListener('change', function () {
        const item = this.closest('.ca-rol-item');
        if (this.checked) {
          item.classList.add('rol-activo');
        } else {
          item.classList.remove('rol-activo');
        }
        limpiarError('err-roles');
        actualizarPreview();
      });
    });
  }

  function getRolesSeleccionados() {
    const seleccionados = [];
    document.querySelectorAll('.ca-rol-checkbox:checked').forEach(function (cb) {
      const item = cb.closest('.ca-rol-item');
      const nombre = item.querySelector('.ca-rol-nombre');
      seleccionados.push({
        id:     cb.value,
        nombre: nombre ? nombre.textContent.trim() : ''
      });
    });
    return seleccionados;
  }

  /* ========================================================
     CONTRASEÑA — mostrar/ocultar y fuerza
     ======================================================== */
  function initPassword() {
    // Mostrar/ocultar contraseña
    togglePass('btn-ver-pass',  'password',  'icono-pass');
    togglePass('btn-ver-pass2', 'password2', 'icono-pass2');

    // Medidor de fuerza
    const passInput = document.getElementById('password');
    if (passInput) {
      passInput.addEventListener('input', function () {
        medirFuerza(this.value);
        actualizarPreview();
      });
    }

    // Confirmación de contraseña
    const pass2 = document.getElementById('password2');
    if (pass2) {
      pass2.addEventListener('input', function () {
        validarConfirmacion();
      });
    }
  }

  function togglePass(btnId, inputId, iconoId) {
    const btn   = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    const icono = document.getElementById(iconoId);
    if (!btn || !input) return;

    btn.addEventListener('click', function () {
      const esPass = input.type === 'password';
      input.type = esPass ? 'text' : 'password';
      if (icono) {
        icono.className = esPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
      }
    });
  }

  function medirFuerza(pass) {
    const fill = document.getElementById('strength-fill');
    const lbl  = document.getElementById('strength-lbl');
    if (!fill || !lbl) return;

    if (!pass) {
      fill.className = 'ca-strength-fill';
      fill.style.width = '0';
      lbl.textContent = '';
      lbl.className = 'ca-strength-lbl';
      return;
    }

    let puntos = 0;
    if (pass.length >= 8)  puntos++;
    if (/[A-Z]/.test(pass)) puntos++;
    if (/[0-9]/.test(pass)) puntos++;
    if (/[^A-Za-z0-9]/.test(pass)) puntos++;

    if (puntos <= 1) {
      fill.className = 'ca-strength-fill debil';
      lbl.textContent = 'Débil';
      lbl.className = 'ca-strength-lbl debil';
    } else if (puntos <= 3) {
      fill.className = 'ca-strength-fill media';
      lbl.textContent = 'Media';
      lbl.className = 'ca-strength-lbl media';
    } else {
      fill.className = 'ca-strength-fill fuerte';
      lbl.textContent = 'Fuerte';
      lbl.className = 'ca-strength-lbl fuerte';
    }
  }

  function validarConfirmacion() {
    const p1 = val('password');
    const p2 = val('password2');
    const input2 = document.getElementById('password2');

    if (!p2) { limpiarError('err-password2'); return true; }

    if (p1 !== p2) {
      mostrarError('err-password2', 'Las contraseñas no coinciden');
      if (input2) input2.classList.add('input-error');
      return false;
    } else {
      limpiarError('err-password2');
      if (input2) { input2.classList.remove('input-error'); input2.classList.add('input-ok'); }
      return true;
    }
  }

  /* ========================================================
     CASCADA: Distrito → Iglesia
     ======================================================== */
  function initCascada() {
    const selDistrito = document.getElementById('distrito_id');
    const selIglesia  = document.getElementById('iglesia_id');

    if (!selDistrito || !selIglesia) return;

    // Guardar las opciones originales
    const todasIglesias = Array.from(selIglesia.options).slice(1); // sin el "Seleccionar"

    selDistrito.addEventListener('change', function () {
      const distId = this.value;

      // Filtrar iglesias por distrito
      selIglesia.innerHTML = '<option value="">Seleccionar Iglesia</option>';
      todasIglesias.forEach(function (opt) {
        if (!distId || opt.dataset.distrito === distId) {
          selIglesia.appendChild(opt.cloneNode(true));
        }
      });
    });
  }

  /* ========================================================
     PREVIEW EN TIEMPO REAL
     ======================================================== */
  function initPreview() {
    ['nombre','usuario'].forEach(function (id) {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', actualizarPreview);
    });
  }

  function actualizarPreview() {
    const nombre  = val('nombre');
    const usuario = val('usuario');
    const roles   = getRolesSeleccionados();

    const preview = document.getElementById('ca-preview');
    if (!preview) return;

    // Mostrar preview solo si hay algo que mostrar
    if (!nombre && !usuario && roles.length === 0) {
      preview.style.display = 'none';
      return;
    }

    preview.style.display = 'block';

    // Avatar inicial
    const avatar = document.getElementById('prev-avatar');
    if (avatar) avatar.textContent = nombre ? nombre.charAt(0).toUpperCase() : '?';

    // Nombre
    const prevNombre = document.getElementById('prev-nombre');
    if (prevNombre) prevNombre.textContent = nombre || 'Nuevo Usuario';

    // Usuario
    const prevUsuario = document.getElementById('prev-usuario');
    if (prevUsuario) prevUsuario.textContent = usuario ? '@' + usuario : '@usuario';

    // Roles
    const prevRoles = document.getElementById('prev-roles');
    if (prevRoles) {
      if (roles.length === 0) {
        prevRoles.innerHTML = '<span style="font-size:.78em;color:var(--ca-lt);">Sin roles asignados aún</span>';
      } else {
        prevRoles.innerHTML = roles.map(function (r) {
          return '<span class="ca-prev-rol-tag"><i class="fa-solid fa-circle-check"></i>' + r.nombre + '</span>';
        }).join('');
      }
    }
  }

  /* ========================================================
     VALIDACIONES EN TIEMPO REAL
     ======================================================== */
  function initValidacionesEnTiempoReal() {
    // Nombre
    const nombre = document.getElementById('nombre');
    if (nombre) {
      nombre.addEventListener('input', function () {
        if (this.value.trim().length >= 3) {
          limpiarError('err-nombre');
          this.classList.remove('input-error'); this.classList.add('input-ok');
        }
      });
    }

    // Usuario — validar disponibilidad con debounce
    const usuario = document.getElementById('usuario');
    if (usuario) {
      let timer;
      usuario.addEventListener('input', function () {
        const v = this.value.trim();
        clearTimeout(timer);
        const hint = document.getElementById('hint-usuario');

        if (!v) {
          limpiarError('err-usuario');
          if (hint) hint.textContent = '';
          this.classList.remove('input-ok', 'input-error');
          return;
        }

        if (!/^[a-zA-Z0-9_\.]+$/.test(v)) {
          mostrarError('err-usuario', 'Solo letras, números, _ y .');
          this.classList.add('input-error'); this.classList.remove('input-ok');
          return;
        }

        // Verificar disponibilidad después de 600ms
        timer = setTimeout(function () {
          verificarUsuario(v, usuario, hint);
        }, 600);
      });
    }

    // Password
    const pass = document.getElementById('password');
    if (pass) {
      pass.addEventListener('input', function () {
        if (this.value.length >= 8) {
          limpiarError('err-password');
          this.classList.remove('input-error');
        }
      });
    }
  }

  function verificarUsuario(usuario, inputEl, hintEl) {
    if (hintEl) hintEl.textContent = 'Verificando...';

    fetch('api_admins.php?accion=verificar_usuario&usuario=' + encodeURIComponent(usuario))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.disponible) {
          limpiarError('err-usuario');
          if (hintEl) { hintEl.textContent = '✓ Usuario disponible'; hintEl.style.color = 'var(--ca-verde)'; }
          if (inputEl) { inputEl.classList.add('input-ok'); inputEl.classList.remove('input-error'); }
        } else {
          mostrarError('err-usuario', 'Este nombre de usuario ya está en uso');
          if (hintEl) { hintEl.textContent = ''; }
          if (inputEl) { inputEl.classList.add('input-error'); inputEl.classList.remove('input-ok'); }
        }
      })
      .catch(function () {
        // Si la API no responde, no bloquear al usuario
        if (hintEl) hintEl.textContent = '';
      });
  }

  /* ========================================================
     SUBMIT
     ======================================================== */
  /*function initSubmit() {
    const form = document.getElementById('form-crear-admin');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!validarTodo()) return;

      const btn = document.getElementById('btn-crear');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creando usuario...';

      const fd = new FormData(form);
      fd.append('accion', 'crear_admin');

      fetch('../admin_api.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Crear Usuario';

          if (data.ok) {
            mostrarToast('Usuario creado correctamente', 'exito');
            setTimeout(function () {
              window.location.href = 'administradores.php';
            }, 1800);
          } else {
            mostrarToast('Error: ' + (data.msg || 'Intenta de nuevo'), 'error');
          }
        })
        .catch(function () {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Crear Usuario';
          mostrarToast('Error de conexión. Intenta de nuevo.', 'error');
        });
    });
  }*/

  /* ========================================================
     VALIDACIONES COMPLETAS
     ======================================================== */
  function validarTodo() {
    let ok = true;

    // Nombre
    const nombre = val('nombre');
    if (nombre.length < 3) {
      mostrarError('err-nombre', 'El nombre es obligatorio (mínimo 3 caracteres)');
      marcarError('nombre');
      ok = false;
    }

    // Usuario
    const usuario = val('usuario');
    if (!usuario) {
      mostrarError('err-usuario', 'El nombre de usuario es obligatorio');
      marcarError('usuario');
      ok = false;
    } else if (!/^[a-zA-Z0-9_\.]+$/.test(usuario)) {
      mostrarError('err-usuario', 'Solo letras, números, _ y .');
      marcarError('usuario');
      ok = false;
    }

    // Contraseña
    const pass = val('password');
    if (pass.length < 8) {
      mostrarError('err-password', 'La contraseña debe tener al menos 8 caracteres');
      marcarError('password');
      ok = false;
    }

    // Confirmar contraseña
    if (!validarConfirmacion()) ok = false;

    // Roles
    const roles = getRolesSeleccionados();
    if (roles.length === 0) {
      mostrarError('err-roles', 'Debes asignar al menos un rol');
      ok = false;
    }

    // Scroll al primer error
    if (!ok) {
      const primerErr = document.querySelector('.ca-error.visible');
      if (primerErr) primerErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    return ok;
  }

  /* ========================================================
     HELPERS
     ======================================================== */
  function val(id) {
    const el = document.getElementById(id);
    return el ? el.value.trim() : '';
  }

  function mostrarError(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.classList.add('visible'); }
  }

  function limpiarError(id) {
    const el = document.getElementById(id);
    if (el) { el.textContent = ''; el.classList.remove('visible'); }
  }

  function marcarError(inputId) {
    const el = document.getElementById(inputId);
    if (el) { el.classList.add('input-error'); el.classList.remove('input-ok'); }
  }

  function mostrarToast(msg, tipo) {
    const toast = document.getElementById('ca-toast');
    if (!toast) return;

    const icono = tipo === 'exito'
      ? '<i class="fa-solid fa-circle-check"></i>'
      : tipo === 'error'
        ? '<i class="fa-solid fa-circle-xmark"></i>'
        : '<i class="fa-solid fa-circle-info"></i>';

    toast.innerHTML = icono + ' ' + msg;
    toast.className = 'ca-toast show ' + (tipo || '');

    setTimeout(function () { toast.classList.remove('show'); }, 3500);
  }
  /* ============================================================
     ENVIO DEL FORMULARIO CREAR ADMIN
     ============================================================ */
  var formCrear = document.getElementById('form-crear-admin');
  if(formCrear){
      formCrear.addEventListener('submit', function(e){
          e.preventDefault();
  
          /* validaciones basicas en cliente */
          var ok = true;

          function mostrarErr(id, msg){
              var el = document.getElementById(id);
              if(el){ el.textContent = msg; el.classList.add('visible'); }
              ok = false;
          }
          function limpiarErr(id){
              var el = document.getElementById(id);
              if(el){ el.textContent = ''; el.classList.remove('visible'); }
          }

          limpiarErr('err-nombre');
          limpiarErr('err-usuario');
          limpiarErr('err-password');
          limpiarErr('err-password2');
          limpiarErr('err-roles');

          var nombre    = document.getElementById('nombre').value.trim();
          var usuario   = document.getElementById('usuario').value.trim();
          var password  = document.getElementById('password').value;
          var password2 = document.getElementById('password2').value;
          var roles     = document.querySelectorAll('input[name="roles[]"]:checked');

          if(!nombre)              mostrarErr('err-nombre',    'El nombre es obligatorio');
          if(!usuario)             mostrarErr('err-usuario',   'El usuario es obligatorio');
          if(password.length < 8)  mostrarErr('err-password',  'Minimo 8 caracteres');
          if(password !== password2) mostrarErr('err-password2','Las contrasenas no coinciden');
          if(roles.length === 0)   mostrarErr('err-roles',     'Selecciona al menos un rol');

          if(!ok) return;

          /* deshabilitar boton mientras envia */
          var btnCrear = document.getElementById('btn-crear');
          btnCrear.disabled = true;
          btnCrear.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

          /* armar FormData con todos los campos */
          var fd = new FormData(formCrear);
          fd.append('agregar-admin', '1');

          fetch('insertar-admin.php', { method: 'POST', body: fd })
          .then(function(r){ return r.json(); })
          .then(function(data){
              if(data.ok){
                  /* mostrar toast de exito */
                  mostrarToastCrear(data.msg, 'exito');

                  /* limpiar formulario */
                  formCrear.reset();
                  document.querySelectorAll('.ca-rol-item').forEach(function(el){
                      el.classList.remove('rol-activo');
                  });
                  document.getElementById('ca-preview').style.display = 'none';

                  /* redirigir a administradores despues de 2 segundos */
                  setTimeout(function(){
                      window.location.href = 'crear-admin.php';
                  }, 3000);

              } else {
                  mostrarToastCrear(data.msg, 'error');
                  btnCrear.disabled = false;
                  btnCrear.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Crear Usuario';
              }
          })
          .catch(function(){
              mostrarToastCrear('Error de conexion. Intenta de nuevo.', 'error');
              btnCrear.disabled = false;
              btnCrear.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Crear Usuario';
          });
      });
  }

  /* toast especifico para crear-admin */
  function mostrarToastCrear(msg, tipo){
      var el = document.getElementById('ca-toast');
      if(!el) return;

      var icono = tipo === 'exito'
          ? '<i class="fa-solid fa-circle-check"></i>'
          : '<i class="fa-solid fa-triangle-exclamation"></i>';

      el.innerHTML = icono + ' ' + msg;
      el.className = 'ca-toast show' + (tipo === 'exito' ? ' exito' : ' error');

      setTimeout(function(){
          el.classList.remove('show');
      }, 3500);
  }


})();