<?php include_once 'includes/templates/header.php'; ?>

<section class="seccion-invitacion">
    <span class="inv-fondo-txt" aria-hidden="true">CUMBRE</span>
    <div class="inv-contenido">
      <span class="inv-tag">— Cumbre Departamental 2026 —</span>
      <h2 class="inv-titulo">La Iglesia de Dios de la Profecía<br><span>te invita</span></h2>
      <p class="inv-texto">
        La Iglesia de Dios de la Profecía en Oruro invita a toda la juventud de las distintas iglesias a ser parte de la <strong>Cumbre Departamental de Jóvenes</strong>. Será un tiempo especial de unidad, adoración y crecimiento en la presencia de Dios, donde juntos responderemos al llamado de ser testigos de Cristo en nuestra generación.
      </p>
      <div class="inv-datos">
        <div class="inv-dato">
          <i class="fa-solid fa-calendar-days"></i>
          <span class="inv-dato-lbl">Fecha</span>
          <span class="inv-dato-val">6 de Noviembre, 2026</span>
        </div>
        <div class="inv-dato">
          <i class="fa-solid fa-location-dot"></i>
          <span class="inv-dato-lbl">Lugar</span>
          <span class="inv-dato-val">Ex Terminal de Oruro</span>
        </div>
        <div class="inv-dato">
          <i class="fa-solid fa-dove"></i>
          <span class="inv-dato-lbl">Lema</span>
          <span class="inv-dato-val">"Llamados a testificar"</span>
        </div>
      </div>
      <a href="tipo_inscripciones.php" class="inv-btn">
        <i class="fa-solid fa-arrow-right"></i> Inscríbete ahora
      </a>
    </div>
  </section>

  <section class="programa">
    <div class="contenido-programa">
      <div class="contenedor">
        <div class="programa-evento">

          <h2>Programa de la Cumbre</h2>

          <!-- MENU -->
          <nav class="menu-programa">
            <a href="#viernes"><i class="fa-solid fa-music"></i>Viernes</a>
            <a href="#sabado"><i class="fa-solid fa-cross"></i>Sabado</a>
          </nav>

          <?php 
          if(!isset($conn)) require_once('includes/funciones/bd_conexion.php');

          $sql = "SELECT e.*, 
                         t.titulo        AS tema,
                         ex.nombre       AS expositor_nombre,
                         ex.apellido     AS expositor_apellido,
                         ex.rango        AS expositor_rango,
                         m.nombre        AS moderador_nombre,
                         m.apellido      AS moderador_apellido
                  FROM eventos e
                  LEFT JOIN temas        t  ON e.id_tema      = t.id_tema
                  LEFT JOIN expositores  ex ON e.id_expositor = ex.id_expositor
                  LEFT JOIN moderadores  m  ON e.id_moderador = m.id_moderador
                  ORDER BY e.id_dia, e.hora_inicio";

          $resultado = $conn->query($sql);

          $eventos = [ 1 => [], 2 => [] ];
          while($row = $resultado->fetch_assoc()){
            $eventos[$row['id_dia']][] = $row;
          }
          ?>

          <!-- VIERNES -->
          <div id="viernes" class="info-curso">
            <?php foreach($eventos[1] as $evento): ?>
              <div class="detalle-evento">

                <div class="evento-header">
                  <span class="evento-tipo"><?php echo htmlspecialchars($evento['tipo_evento']); ?></span>
                  <span class="evento-hora">
                    <i class="fa-solid fa-clock"></i>
                    <?php echo substr($evento['hora_inicio'],0,5); ?> — <?php echo substr($evento['hora_fin'],0,5); ?>
                  </span>
                </div>

                <?php if (!empty($evento['tema'])): ?>
                  <p class="evento-tema">
                    <i class="fa-solid fa-book-open"></i>
                    <?php echo htmlspecialchars($evento['tema']); ?>
                  </p>
                <?php endif; ?>

                <?php if (!empty($evento['expositor_nombre'])): ?>
                  <p class="evento-expositor">
                    <i class="fa-solid fa-user-tie"></i>
                    <span class="exp-rango"><?php echo htmlspecialchars($evento['expositor_rango']); ?></span>
                    <?php echo htmlspecialchars($evento['expositor_nombre'].' '.$evento['expositor_apellido']); ?>
                  </p>
                <?php endif; ?>

                <?php if (!empty($evento['moderador_nombre'])): ?>
                  <p class="evento-moderador">
                    <i class="fa-solid fa-microphone"></i>
                    Moderador. <?php echo htmlspecialchars($evento['moderador_nombre'].' '.$evento['moderador_apellido']); ?>
                  </p>
                <?php endif; ?>

              </div>
            <?php endforeach; ?>
            <a href="calendario.php" class="button">Ver calendario completo</a>
          </div>

          <!-- SABADO -->
          <div id="sabado" class="info-curso">
            <?php foreach($eventos[2] as $evento): ?>
              <div class="detalle-evento">
                <div class="evento-header">
                  <span class="evento-tipo"><?php echo htmlspecialchars($evento['tipo_evento']); ?></span>
                  <span class="evento-hora">
                    <i class="fa-solid fa-clock"></i>
                    <?php echo substr($evento['hora_inicio'],0,5); ?> — <?php echo substr($evento['hora_fin'],0,5); ?>
                  </span>
                </div>
                <?php if (!empty($evento['tema'])): ?>
                  <p class="evento-tema"><i class="fa-solid fa-book-open"></i><?php echo htmlspecialchars($evento['tema']); ?></p>
                <?php endif; ?>
                <?php if (!empty($evento['expositor_nombre'])): ?>
                  <p class="evento-expositor">
                    <i class="fa-solid fa-user-tie"></i>
                    <span class="exp-rango"><?php echo htmlspecialchars($evento['expositor_rango']); ?></span>
                    <?php echo htmlspecialchars($evento['expositor_nombre'].' '.$evento['expositor_apellido']); ?>
                  </p>
                <?php endif; ?>
                <?php if (!empty($evento['moderador_nombre'])): ?>
                  <p class="evento-moderador">
                    <i class="fa-solid fa-microphone"></i>
                    Moderador. <?php echo htmlspecialchars($evento['moderador_nombre'].' '.$evento['moderador_apellido']); ?>
                  </p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <a href="calendario.php" class="button">Ver calendario completo</a>
          </div>


        </div>
      </div>
    </div>
  </section>


  <section class="invitados seccion">
    <span class="com-tag">— Expositores —</span>
    <h2>Nuestros Invitados</h2>
    <?php
      require_once('includes/funciones/bd_conexion.php');
      $res_exp = $conn->query("SELECT nombre, apellido, rango, imagen FROM expositores ORDER BY apellido");
    ?>
    <ul class="lista-invitados">
      <?php while($exp = $res_exp->fetch_assoc()): ?>
        <li>
          <a href="invitados.php" class="invitado">
            <div class="invitado-foto">
              <?php if(!empty($exp['imagen'])): ?>
                <img src="img/<?php echo htmlspecialchars($exp['imagen']); ?>"
                     alt="<?php echo htmlspecialchars($exp['nombre'] . ' ' . $exp['apellido']); ?>">
              <?php else: ?>
                <span class="invitado-inicial"><?php echo htmlspecialchars(mb_substr($exp['nombre'], 0, 1)); ?></span>
              <?php endif; ?>
            </div>
            <div class="invitado-info">
              <span class="invitado-rango"><?php echo htmlspecialchars($exp['rango']); ?></span>
              <p class="invitado-nombre"><?php echo htmlspecialchars($exp['nombre'] . ' ' . $exp['apellido']); ?></p>
            </div>
          </a>
        </li>
      <?php endwhile; ?>
    </ul>
  </section>


  <!--precios-->
  <section class="precios seccion seccion-fondo-azul">
    <h2>Precios</h2>

    <div class="aviso-inscripcion">
      <i class="fa-solid fa-circle-info"></i>
      Puedes inscribirte <strong>personalmente con QR</strong> o si es en 
      <strong>efectivo</strong> acercate a tu lider local.
      Para mas informacion presiona el boton 
      <a href="tipo_inscripciones.php" class="link-inscripcion">Inscripciones</a> en la barra.
    </div>

    <div class="contenedor">
      <?php
        /* paquetes con descuento activo desde la BD */
        $res_paq = $conn->query("
            SELECT p.id, p.nombre, p.precio, p.cupo_total, p.cupos_disponibles,
                   d.nombre        AS desc_nombre,
                   d.porcentaje    AS desc_porcentaje,
                   d.fecha_fin,
                   ROUND(p.precio - (p.precio * COALESCE(d.porcentaje,0) / 100), 0) AS precio_final
            FROM paquetes p
           LEFT JOIN paquete_descuentos pd ON p.id = pd.paquete_id
            LEFT JOIN descuentos d ON pd.descuento_id = d.id AND d.activo = 1
            ORDER BY p.precio
        ");

        /* beneficios que se muestran en todos los paquetes */
        $beneficios = [
            'Acceso a todas las conferencias',
            'Material de la cumbre',
        ];
      ?>

      <ul class="lista-precios">
        <?php while($paq = $res_paq->fetch_assoc()):
          $con_desc   = !empty($paq['desc_porcentaje']);
          $pf         = $con_desc ? $paq['precio_final'] : $paq['precio'];
          $agotado    = $paq['cupos_disponibles'] <= 0;
          $porc_cupos = $paq['cupo_total'] > 0
                        ? round(($paq['cupos_disponibles'] / $paq['cupo_total']) * 100)
                        : 0;
          $color_cupo = $porc_cupos > 50 ? 'cupo-alto' : ($porc_cupos > 20 ? 'cupo-medio' : 'cupo-bajo');
          $bens       = $beneficios;
          /* el paquete con alimento incluye el almuerzo */
          if(stripos($paq['nombre'], 'aliment') !== false) $bens[] = 'Almuerzo en el Hotel';
        ?>
          <li>
            <div class="tabla-precio <?php echo $agotado ? 'agotado' : ''; ?>">

              <!-- nombre del paquete -->
              <h3><?php echo htmlspecialchars($paq['nombre']); ?></h3>

              <!-- precio con o sin descuento -->
              <?php if($con_desc): ?>
                <p class="precio-original">Bs. <?php echo number_format($paq['precio'],0); ?></p>
                <p class="numero">Bs. <?php echo number_format($pf,2); ?></p>
                <div class="badge-descuento">
                  <i class="fa-solid fa-tag"></i>
                  <?php echo htmlspecialchars($paq['desc_nombre']); ?> —
                  <?php echo $paq['desc_porcentaje']; ?>% OFF
                </div>
                <p class="fecha-descuento">
                  <i class="fa-solid fa-calendar"></i>
                  Promocion hasta: <?php echo date('d/m/Y', strtotime($paq['fecha_fin'])); ?>
                </p>
              <?php else: ?>
                <p class="numero">Bs. <?php echo number_format($pf,0); ?></p>
              <?php endif; ?>

              <!-- cupos disponibles resaltados -->
              <div class="barra-cupos <?php echo $color_cupo; ?>">
                <div class="barra-cupos-relleno" style="width:<?php echo $porc_cupos; ?>%"></div>
              </div>
              <p class="texto-cupos <?php echo $color_cupo; ?>">
                <i class="fa-solid fa-users"></i>
                <?php if($agotado): ?>
                  <strong>AGOTADO</strong>
                <?php else: ?>
                  <strong><?php echo $paq['cupos_disponibles']; ?></strong> cupos disponibles
                  de <?php echo $paq['cupo_total']; ?>
                <?php endif; ?>
              </p>

              <!-- beneficios -->
              <ul>
                <?php foreach($bens as $ben): ?>
                  <li><?php echo $ben; ?></li>
                <?php endforeach; ?>
              </ul>

              <!-- boton -->
              <?php if($agotado): ?>
                <a href="#" class="button hollow" style="opacity:.5;pointer-events:none;">Agotado</a>
              <?php else: ?>
                <a href="tipo_inscripciones.php" class="button hollow">Inscribirme</a>
              <?php endif; ?>

            </div>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </section>

  <?php
  /* cargar ubicaciones activas */
  if(!isset($conn)) require_once('includes/funciones/bd_conexion.php');
  $ubicaciones_mapa = [];
  $res_ubic = $conn->query("SELECT * FROM ubicaciones WHERE activo=1 ORDER BY orden ASC");
  if($res_ubic) while($u = $res_ubic->fetch_assoc()) $ubicaciones_mapa[] = $u;

  $ubicacion_evento = null;
  foreach($ubicaciones_mapa as $u){
    if($u['tipo'] === 'evento'){ $ubicacion_evento = $u; break; }
  }
  ?>
  <section class="seccion-mapa">
    <h2>Ubicación del Evento</h2>

    <?php if(!$ubicacion_evento): ?>
      <p class="mapa-sin-ubicacion">
        <i class="fa-solid fa-map-location-dot"></i>
        Aún no se configuró la ubicación del evento.
      </p>

    <?php else: ?>

      <p class="texto-mapa">
        <i class="fa-solid fa-location-dot"></i>
        Haz click en el mapa para abrir en Google Maps
      </p>
      <div class="contenedor-mapa">
        <a href="<?php echo htmlspecialchars($ubicacion_evento['link_maps']); ?>" target="_blank" class="link-mapa">
          <?php if(!empty($ubicacion_evento['embed_url'])): ?>
            <iframe src="<?php echo htmlspecialchars($ubicacion_evento['embed_url']); ?>"
                    width="100%" height="100%" style="border:0;pointer-events:none;"
                    allowfullscreen="" loading="lazy"></iframe>
          <?php else: ?>
            <div class="mapa-sin-embed">
              <i class="fa-solid fa-map-location-dot"></i>
              <p><?php echo htmlspecialchars($ubicacion_evento['nombre']); ?></p>
            </div>
          <?php endif; ?>
          <div class="overlay-mapa">
            <i class="fa-solid fa-location-dot"></i>
            <p><?php echo htmlspecialchars($ubicacion_evento['nombre']); ?> — Click para abrir en Google Maps</p>
          </div>
        </a>
      </div>

    <?php endif; ?>
  </section>


  <!-- ══ COMISIONES ══ -->
  <section class="seccion-comisiones">
    <div class="contenedor">
      <span class="com-tag">— Organización —</span>
      <h2 class="com-titulo">Comisiones de la Cumbre</h2>

      <?php
        if(!isset($conn)) require_once('includes/funciones/bd_conexion.php');
        $res_com = $conn->query("
          SELECT c.id, c.nombre, c.icono,
                 GROUP_CONCAT(e.nombre ORDER BY e.id SEPARATOR '||') AS nombres,
                 GROUP_CONCAT(e.celular ORDER BY e.id SEPARATOR '||') AS celulares
          FROM comisiones c
          LEFT JOIN comision_encargados e ON e.comision_id = c.id
          WHERE c.activo = 1
          GROUP BY c.id
          ORDER BY c.orden
        ");
      ?>

      <div class="com-grid">
        <?php while($com = $res_com->fetch_assoc()): ?>
          <div class="com-card">
            <div class="com-card-header">
              <div class="com-icono">
                <i class="<?php echo htmlspecialchars($com['icono']); ?>"></i>
              </div>
              <h3 class="com-nombre"><?php echo htmlspecialchars($com['nombre']); ?></h3>
            </div>

            <?php if($com['nombres']): ?>
              <ul class="com-encargados">
                <?php
                  $nombres   = explode('||', $com['nombres']);
                  $celulares = explode('||', $com['celulares']);
                  foreach($nombres as $k => $nom):
                    $cel = $celulares[$k] ?? '';
                    $wa  = 'https://wa.me/591' . preg_replace('/[^0-9]/', '', $cel);
                ?>
                  <li class="com-encargado">
                    <div class="com-enc-info">
                      <i class="fa-solid fa-user"></i>
                      <span><?php echo htmlspecialchars($nom); ?></span>
                    </div>
                    <?php if($cel): ?>
                      <a href="<?php echo $wa; ?>" target="_blank" class="com-wa-btn" title="WhatsApp">
                        <i class="fa-brands fa-whatsapp"></i>
                        <?php echo htmlspecialchars($cel); ?>
                      </a>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="com-sin-enc">Por definir</p>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>
  
<?php include_once 'includes/templates/footer.php'; ?>