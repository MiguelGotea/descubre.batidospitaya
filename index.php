<?php
require_once __DIR__ . '/core/config.php';

// Verificación de Llave (Capa 1)
$paramK = trim($_GET['k'] ?? '');
$hasValidKey = (!empty($paramK) && $paramK === QR_ACCESS_KEY);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Descubre Batidos Pitaya</title>
    <link rel="icon" type="image/png" href="img/icon12.png">

    <!-- Fuentes Google: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
    <link rel="stylesheet" href="css/formulario.css?v=<?php echo filemtime(__DIR__ . '/css/formulario.css'); ?>">
    <link rel="stylesheet" href="css/final.css?v=<?php echo filemtime(__DIR__ . '/css/final.css'); ?>">
    <link rel="stylesheet" href="css/bloqueo.css?v=<?php echo filemtime(__DIR__ . '/css/bloqueo.css'); ?>">
</head>

<body>

    <!-- ========================================================
         BLOQUEO PARA FORMATO PC (SOLO FORMATO CELULAR)
         ======================================================== -->
    <div id="pcBlocker" class="pc-blocker-overlay">
        <div class="pc-blocker-card">
            <img src="img/logo_pitaya.png" alt="Batidos Pitaya" class="pc-blocker-logo">
            <h2 class="pc-blocker-title">¡Solo para Celulares!</h2>
            <p class="pc-blocker-text">
                Esta activación de descuento de apertura está diseñada exclusivamente para participar desde tu celular
                en nuestro Kiosko.
            </p>
            <div class="pc-blocker-badge">
                <span class="pc-blocker-badge-icon">📍</span>
                <span>Visítanos en nuestro Kiosko en <strong>Multicentro Las Américas</strong> para escanear el código y
                    obtener tu descuento.</span>
            </div>
        </div>
    </div>

    <!-- ========================================================
         VIEWPORT PRINCIPAL MÓVIL
         ======================================================== -->
    <main class="app-viewport">

        <?php if ($hasValidKey): ?>
            <!-- ==========================================
             PASO 1: PANTALLA INICIAL (ACCESO CON LLAVE QR)
             ========================================== -->
            <section id="step1" class="step-screen screen-initial active">
                <!-- Logo Pitaya superior flotante (subido más arriba) -->
                <img src="img/logo_pitaya.png" alt="Logo Batidos Pitaya" class="initial-logo-top">

                <!-- Pie de página independiente: Segmento verde con texto y botón -->
                <div class="initial-footer-wrapper">
                    <img src="img/pie_pagina.png" alt="Pie verde" class="img-pie-pagina">

                    <div class="pie-pagina-content">
                        <img src="img/texto_inferior.png" alt="Obtené 10% de descuento en tu batido el día de la apertura"
                            class="img-texto-inferior">

                        <!-- Botón Continuar interactivo con animación latente -->
                        <button type="button" id="btnContinuar" class="btn-continuar-img" aria-label="Continuar">
                            <img src="img/boton_continuar.png" alt="Continuar" class="img-btn-continuar">
                        </button>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- ==========================================
             PANTALLA DE BLOQUEO (SIN ESCANEAR QR)
             ========================================== -->
            <section class="screen-no-key active">
                <div class="no-key-content-wrapper">
                    <!-- Logo Pitaya superior flotante -->
                    <img src="img/logo_pitaya.png" alt="Logo Batidos Pitaya" class="no-key-logo-top">

                    <!-- Texto gráfico de la dinámica: Para participar en la dinámica solo es necesario escanear el código QR -->
                    <img src="img/texto_bloqueo_qr.png"
                        alt="¡Para participar en la dinámica! Solo es necesario escanear el código QR"
                        class="img-texto-bloqueo">
                </div>
            </section>
        <?php endif; ?>

        <!-- ==========================================
             PASO 2: FORMULARIO DE REGISTRO
             ========================================== -->
        <section id="step2" class="step-screen screen-form">
            <div class="form-content-wrapper">
                <!-- Logo Pitaya superior flotante -->
                <img src="img/logo_pitaya.png" alt="Logo Batidos Pitaya" class="form-logo-top">

                <!-- Texto superior: ¡Llévate tu 10% de descuento! -->
                <img src="img/texto_superior_formulario.png"
                    alt="¡Llévate tu 10% de descuento! Completá tus datos y obtené tu descuento para el día de la apertura."
                    class="form-texto-superior">

                <!-- Formulario interactivo -->
                <form id="registrationForm" class="form-interactive" autocomplete="off">
                    <div class="form-inputs-group">
                        <!-- 1. Nombre completo -->
                        <div class="form-input-card">
                            <span class="form-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Nombre completo"
                                required maxlength="100">
                        </div>

                        <!-- 2. Número de celular -->
                        <div class="form-input-card">
                            <span class="form-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                    </path>
                                </svg>
                            </span>
                            <input type="tel" id="numero_contacto" name="numero_contacto"
                                placeholder="Número de celular" required maxlength="8" inputmode="numeric">
                        </div>

                        <!-- 3. Número de cédula -->
                        <div class="form-input-card">
                            <span class="form-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="3.5"></rect>
                                    <circle cx="8" cy="10" r="2.5"></circle>
                                    <path d="M5.5 16a2.5 2.5 0 0 1 5 0"></path>
                                    <line x1="14" y1="9" x2="18" y2="9"></line>
                                    <line x1="14" y1="13" x2="18" y2="13"></line>
                                </svg>
                            </span>
                            <input type="text" id="numero_cedula" name="numero_cedula" placeholder="Número de cédula"
                                required maxlength="16">
                        </div>
                    </div>

                    <!-- Botón Obtener Descuento interactivo con animación latente -->
                    <div class="form-btn-slot">
                        <button type="submit" id="btnSubmitForm" class="btn-obtener-img" aria-label="Obtener Descuento">
                            <img src="img/boton_formulario.png" alt="Obtener Descuento" class="img-btn-obtener">
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ==========================================
             PASO 3: PANTALLA FINAL (CUPÓN)
             ========================================== -->
        <section id="step3" class="step-screen screen-final">
            <div class="final-content-wrapper">
                <!-- Logo Pitaya superior flotante -->
                <img src="img/logo_pitaya.png" alt="Logo Batidos Pitaya" class="final-logo-top">

                <!-- Ticket con código insertado -->
                <div class="ticket-wrapper">
                    <img src="img/ticket_cupon.png" alt="Cupón 10% Descuento" class="img-ticket">
                    <div class="ticket-code-badge">
                        <span id="couponDisplayCode" class="coupon-display-code">------</span>
                    </div>
                </div>

                <!-- Botón interactivo palpitante para tomar captura -->
                <div class="final-btn-slot">
                    <button type="button" id="btnCapturaCupon" class="btn-captura-img"
                        aria-label="Tomar captura del cupón">
                        <img src="img/boton_captura.png"
                            alt="Haz una captura y preséntala en nuestro Kiosko de Batidos Pitaya Multicentro Las Américas"
                            class="img-btn-captura">
                    </button>
                </div>
            </div>
        </section>

    </main>

    <!-- ========================================================
         MODAL GENERAL DE PROCESO / MENSAJES / ERRORES
         ======================================================== -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-content-card">
            <div id="modalSpinner" class="spinner"></div>
            <div id="modalIcon" class="modal-icon" style="display: none;"></div>
            <h3 id="modalTitle" class="modal-title">Cargando...</h3>
            <p id="modalMessage" class="modal-message">Por favor espera un momento.</p>
            <button type="button" id="modalBtn" class="modal-btn" style="display: none;">Entendido</button>
        </div>
    </div>

    <!-- Toast de Notificaciones Rápidas -->
    <div id="toastMsg" class="toast-msg"></div>

    <!-- Configuración Global Frontend -->
    <script>
        window.APP_CONFIG = {
            hasKey: <?php echo $hasValidKey ? 'true' : 'false'; ?>,
            k: <?php echo json_encode($paramK); ?>,
            token: ''
        };
    </script>

    <script src="js/app.js?v=<?php echo filemtime(__DIR__ . '/js/app.js'); ?>"></script>
</body>

</html>