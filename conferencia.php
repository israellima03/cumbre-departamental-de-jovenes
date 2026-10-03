<?php 
include_once 'includes/templates/header.php';
require_once('includes/funciones/bd_conexion.php');

/* ── CONFIGURACION DE PREGUNTAS ── */
$config_preguntas = ['activo' => 1, 'mensaje_inactivo' => 'Las preguntas estaran disponibles durante el evento.'];
$res_cfg = $conn->query("SELECT activo, mensaje_inactivo FROM config_preguntas LIMIT 1");
if($res_cfg && $r = $res_cfg->fetch_assoc()) $config_preguntas = $r;

/* ── CARGAR EVENTOS CON EXPOSITOR, TEMA Y MATERIALES ── */
$sql = "
    SELECT 
        e.id_evento, e.fecha, e.hora_inicio, e.hora_fin, e.tipo_evento,
        e.preguntas_activas,        /* ← AGREGA ESTA LINEA */
        d.nombre        AS nombre_dia,
        t.titulo        AS tema,
        ex.nombre       AS expositor_nombre,
        ex.apellido     AS expositor_apellido,
        ex.rango        AS expositor_rango,
        ex.descripcion  AS expositor_desc,
        ex.imagen       AS expositor_imagen
    FROM eventos e
    LEFT JOIN dias        d  ON e.id_dia       = d.id_dia
    LEFT JOIN temas       t  ON e.id_tema      = t.id_tema
    LEFT JOIN expositores ex ON e.id_expositor = ex.id_expositor
    WHERE e.id_expositor IS NOT NULL AND e.id_tema IS NOT NULL
    ORDER BY e.fecha, e.hora_inicio
";

$res    = $conn->query($sql);
$eventos_por_dia = [];

if($res){
    while($fila = $res->fetch_assoc()){
        /* materiales de este evento */
        $res_mat = $conn->prepare("SELECT * FROM materiales_evento WHERE id_evento = ? ORDER BY id");
        $res_mat->bind_param('i', $fila['id_evento']);
        $res_mat->execute();
        $fila['materiales'] = $res_mat->get_result()->fetch_all(MYSQLI_ASSOC);
        $res_mat->close();

        $eventos_por_dia[$fila['fecha']][] = $fila;
    }
}

/* nombres de dias en español */
$dias_semana = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
$meses       = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

/* iconos por tipo de material */
$iconos_mat = [
    'pdf'    => ['clase' => 'pdf',   'icono' => 'fa-file-pdf',        'accion' => 'Descargar'],
    'ppt'    => ['clase' => 'ppt',   'icono' => 'fa-file-powerpoint', 'accion' => 'Descargar'],
    'img'    => ['clase' => 'img',   'icono' => 'fa-image',           'accion' => 'Ver'],
    'zip'    => ['clase' => 'zip',   'icono' => 'fa-file-zipper',     'accion' => 'Descargar'],
    'video'  => ['clase' => 'video', 'icono' => 'fa-brands fa-youtube','accion' => 'Ver en linea'],
    'enlace' => ['clase' => 'video', 'icono' => 'fa-link',            'accion' => 'Abrir'],
];
?>

<section class="seccion contenedor">
  <h2>Material de Conferencias</h2>
  <p>Descarga o visualiza el material de cada conferencista</p>
</section>

<section class="seccion-conferencias contenedor">

  <?php if(empty($eventos_por_dia)): ?>
    <div style="text-align:center;padding:60px 20px;color:#999;">
      <i class="fa-solid fa-calendar-xmark" style="font-size:3em;display:block;margin-bottom:15px;"></i>
      <p>El material de conferencias estara disponible durante el evento.</p>
    </div>

  <?php else: ?>

    <?php foreach($eventos_por_dia as $fecha => $eventos): ?>
      <?php
        $ts      = strtotime($fecha);
        $num_dia = date('w', $ts);
        $num_mes = date('n', $ts);
        $titulo_dia = $dias_semana[$num_dia] . ' ' . date('d', $ts) . ' de ' . $meses[$num_mes];
      ?>

      <div class="dia-conferencia">
        <h3 class="titulo-dia">
          <i class="fa-solid fa-calendar-day"></i>
          <?php echo $titulo_dia; ?>
        </h3>

        <div class="lista-conferencias">
          <?php foreach($eventos as $ev): ?>
            <div class="tarjeta-conferencia">

              <!-- INFO EXPOSITOR -->
              <div class="info-conferencia">
                <div class="foto-expositor">

                  <?php if(!empty($ev['expositor_imagen'])): ?>

                    <?php
                    $imagen = str_replace('img/', '', $ev['expositor_imagen']);
                    ?>

                    <img src="img/<?php echo htmlspecialchars($imagen); ?>"
                         alt="<?php echo htmlspecialchars($ev['expositor_nombre']); ?>">

                  <?php else: ?>

                    <div style="width:80px;height:80px;border-radius:50%;background:#2C2668;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.8em;">
                      <i class="fa-solid fa-user"></i>
                    </div>

                  <?php endif; ?>

                </div>

                <div class="datos-conferencia">
                  <span class="hora-conf">
                    <i class="fa-solid fa-clock"></i>
                    <?php echo substr($ev['hora_inicio'],0,5); ?> — <?php echo substr($ev['hora_fin'],0,5); ?> hrs
                  </span>

                  <!-- TIPO DE EVENTO en vez de descripcion -->
                  <span class="tipo-evento-badge tipo-<?php echo strtolower($ev['tipo_evento']); ?>">
                    <?php echo htmlspecialchars($ev['tipo_evento']); ?>
                  </span>

                  <h4 class="tema-conf"><?php echo htmlspecialchars($ev['tema']); ?></h4>
                  <p class="expositor-conf">
                    <i class="fa-solid fa-user"></i>
                    <?php echo htmlspecialchars($ev['expositor_rango'] . ' ' . $ev['expositor_nombre'] . ' ' . $ev['expositor_apellido']); ?>
                  </p>
                </div>
              </div>

              <!-- MATERIALES -->
              <div class="materiales">
                <p class="titulo-materiales">
                  <i class="fa-solid fa-folder-open"></i>
                  Material disponible:
                </p>

                <?php if(empty($ev['materiales'])): ?>
                  <div class="sin-material">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <p>Material proximamente disponible</p>
                  </div>
                <?php else: ?>
                  <div class="lista-materiales">
                    <?php foreach($ev['materiales'] as $mat):
                      $meta   = $iconos_mat[$mat['tipo']] ?? $iconos_mat['enlace'];
                      $esDesc = in_array($mat['tipo'], ['pdf','ppt','zip','img']);
                    ?>
                      <?php
                       $url_mat = $mat['url'];
                        /* si no empieza con http es un archivo local en /material/ */
                        if(!empty($url_mat) && !str_starts_with($url_mat,'http')){
                          $url_mat = 'material/' . $url_mat;
                        }
                        /* verificar descarga_activa */
                        $descarga_ok = !isset($mat['descarga_activa']) || $mat['descarga_activa'] == '1';
                      ?>
                      <a href="<?php echo $descarga_ok ? htmlspecialchars($url_mat) : '#'; ?>"
                         class="material-item"
                         target="_blank"
                         <?php echo ($esDesc && $descarga_ok) ? 'download' : ''; ?>
                         <?php echo !$descarga_ok ? 'onclick="return false;" style="opacity:.5;cursor:not-allowed;"' : ''; ?>>
                        <div class="icono-material <?php echo $meta['clase']; ?>">
                          <i class="<?php echo $meta['icono']; ?>"></i>
                        </div>
                        <div class="info-material">
                          <span class="nombre-material"><?php echo htmlspecialchars($mat['nombre']); ?></span>
                          <span class="tipo-material">
                            <?php echo strtoupper($mat['tipo']); ?> —
                            <?php echo $meta['accion']; ?>
                            <?php if(!empty($mat['descripcion'])): ?>
                              · <?php echo htmlspecialchars($mat['descripcion']); ?>
                            <?php endif; ?>
                          </span>
                        </div>
                        <?php if(!$descarga_ok): ?>
                          <span style="font-size:10px;color:#999;white-space:nowrap;"><i class="fa-solid fa-lock"></i> Durante el evento</span>
                        <?php else: ?>
                          <i class="fa-solid <?php echo $esDesc ? 'fa-download' : 'fa-external-link'; ?> icono-accion"></i>
                        <?php endif; ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- SECCION PREGUNTAS -->
              <div class="seccion-preguntas" id="preguntas-<?php echo $ev['id_evento']; ?>">
                <div class="preguntas-header">
                  <i class="fa-solid fa-circle-question"></i>
                  <span>¿Tienes una pregunta para esta conferencia?</span>
                </div>

                <?php if($ev['preguntas_activas']): ?>
                  <form class="form-pregunta" data-evento="<?php echo $ev['id_evento']; ?>" novalidate>
                    <div class="pregunta-campos">
                      <textarea class="pregunta-texto"
                                placeholder="Escribe tu pregunta anonima aqui... *"
                                rows="3"
                                maxlength="500"
                                required></textarea>
                    </div>
                    <div class="pregunta-footer">
                      <span class="pregunta-contador">0 / 500</span>
                      <button type="submit" class="btn-enviar-pregunta">
                        <i class="fa-solid fa-paper-plane"></i> Enviar pregunta
                      </button>
                    </div>
                    <div class="pregunta-msg" style="display:none;"></div>
                  </form>
                <?php else: ?>
                  <div class="preguntas-cerradas">
                    <i class="fa-solid fa-lock"></i>
                    <p><?php echo htmlspecialchars($config_preguntas['mensaje_inactivo']); ?></p>
                  </div>
                <?php endif; ?>
              </div>
              <!-- BOTÓN VER PREGUNTAS APROBADAS -->
              <div class="ver-preguntas-wrap">
                <button class="btn-ver-preguntas" data-evento="<?php echo $ev['id_evento']; ?>"
                        data-tema="<?php echo htmlspecialchars($ev['tema']); ?>">
                  <i class="fa-solid fa-comments"></i> Ver Preguntas
                </button>
              </div>

            </div><!-- fin tarjeta-conferencia -->
          <?php endforeach; ?>
        </div>
      </div><!-- fin dia-conferencia -->
    <?php endforeach; ?>

  <?php endif; ?>
  <!-- MODAL PREGUNTAS APROBADAS -->
  <div id="modal-preguntas-overlay" style="display:none;position:fixed;inset:0;background:rgba(44,38,104,0.85);z-index:9999;place-items:center;padding:20px;">
    <div id="modal-preguntas" style="background:#fff;border-radius:16px;width:100%;max-width:600px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.4);max-height:90vh;display:flex;flex-direction:column;">
    
      <div style="background:linear-gradient(135deg,#2C2668,#5C625B);padding:20px 24px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
        <div>
          <h3 style="font-family:'Oswald',sans-serif;color:#fff;margin:0;font-size:1.1em;letter-spacing:1px;">
            <i class="fa-solid fa-comments"></i> Preguntas Aprobadas
          </h3>
          <p id="modal-pq-tema" style="color:rgba(255,255,255,0.7);font-size:0.82em;margin:4px 0 0;"></p>
        </div>
        <button onclick="cerrarModalPreguntas()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:6px;cursor:pointer;font-size:16px;">✕</button>
      </div>

      <!-- CONTENIDO PREGUNTA -->
      <div style="flex:1;overflow-y:auto;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 30px;min-height:200px;">
        <div id="modal-pq-loading" style="text-align:center;color:#999;">
          <i class="fa-solid fa-spinner fa-spin" style="font-size:2em;display:block;margin-bottom:10px;"></i>
          Cargando preguntas...
        </div>
        <div id="modal-pq-contenido" style="display:none;text-align:center;width:100%;">
          <div style="margin-bottom:16px;">
            <span id="modal-pq-num" style="font-family:'Oswald',sans-serif;font-size:0.8em;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;"></span>
          </div>
          <blockquote id="modal-pq-texto" style="font-family:'Montserrat',sans-serif;font-size:1.4em;color:#2C2668;line-height:1.6;margin:0 0 20px;font-style:italic;padding:0 10px;"></blockquote>
          <div style="height:3px;background:linear-gradient(90deg,#E8750A,#E8750A);border-radius:2px;width:60px;margin:0 auto;"></div>
        </div>
        <div id="modal-pq-vacio" style="display:none;text-align:center;color:#9ca3af;">
          <i class="fa-solid fa-comment-slash" style="font-size:3em;display:block;margin-bottom:12px;color:#e1e1e1;"></i>
          <p style="font-size:0.95em;">No hay preguntas aprobadas aún para esta conferencia.</p>
        </div>
      </div>

      <!-- NAVEGACIÓN -->
      <div id="modal-pq-nav" style="display:none;padding:16px 24px;border-top:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
        <button id="btn-pq-ant" onclick="navegarPregunta(-1)"
                style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#2C2668;color:#fff;border:none;border-radius:8px;font-family:'Oswald',sans-serif;font-size:0.9em;cursor:pointer;transition:all .2s;">
          <i class="fa-solid fa-chevron-left"></i> Anterior
        </button>
        <span id="modal-pq-progreso" style="font-size:0.85em;color:#9ca3af;font-family:'Oswald',sans-serif;letter-spacing:1px;"></span>
        <button id="btn-pq-sig" onclick="navegarPregunta(1)"
                style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#2C2668;color:#fff;border:none;border-radius:8px;font-family:'Oswald',sans-serif;font-size:0.9em;cursor:pointer;transition:all .2s;">
          Siguiente <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>

    </div>
  </div>

</section>
<?php $js_pagina = ['conferencia.js']; ?>
<?php include_once 'includes/templates/footer.php'; ?>