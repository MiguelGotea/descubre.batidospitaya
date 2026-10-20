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
    <title>Descubre Batidos Pitaya — 10% Descuento de Apertura</title>
    <link rel="icon" type="image/png" href="img/icon12.png">
    
    <!-- Fuentes Google: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
</head>
<body>

    <!-- ========================================================
         BLOQUEO PARA FORMATO PC (SOLO FORMATO CELULAR)
         ======================================================== -->
    <div id="pcBlocker" class="pc-blocker-overlay">
        <div class="pc-blocker-card">
            <img src="img/logo.png" alt="Batidos Pitaya" class="pc-blocker-logo">
            <div class="pc-blocker-icon">📱</div>
            <h2 class="pc-blocker-title">¡Solo para Celulares!</h2>
            <p class="pc-blocker-text">
                Esta activación de descuento de apertura está diseñada exclusivamente para participar desde tu celular en nuestro Kiosko.
            </p>
            <div class="pc-blocker-qr-box">
                <img id="pcQrImg" src="" alt="QR Móvil">
                <span>Escaneá con tu celular para continuar</span>
            </div>
        </div>
    </div>

    <!-- ========================================================
         VIEWPORT PRINCIPAL MÓVIL
         ======================================================== -->
    <main class="app-viewport">
        
        <!-- ==========================================
             PASO 1: PANTALLA INICIAL
             ========================================== -->
        <section id="step1" class="step-screen screen-initial active">
            <!-- Logo Pitaya superior flotante (subido más arriba) -->
            <img src="img/logo_pitaya.png" alt="Logo Batidos Pitaya" class="initial-logo-top">
            
            <!-- Pie de página independiente: Segmento verde con texto y botón -->
            <div class="initial-footer-wrapper">
                <img src="img/pie_pagina.png" alt="Pie verde" class="img-pie-pagina">
                
                <div class="pie-pagina-content">
                    <img src="img/texto_inferior.png" alt="Obtené 10% de descuento en tu batido el día de la apertura" class="img-texto-inferior">
                    
                    <?php if ($hasValidKey): ?>
                        <!-- Botón Continuar interactivo con animación latente -->
                        <button type="button" id="btnContinuar" class="btn-continuar-img" aria-label="Continuar">
                            <img src="img/boton_continuar.png" alt="Continuar" class="img-btn-continuar">
                        </button>
                    <?php else: ?>
                        <!-- Mensaje si no tiene llave -->
                        <div class="no-key-warning-card">
                            <div class="qr-badge-icon">📲</div>
                            <h4>¿Querés tu 10% de descuento?</h4>
                            <p>Para participar debés <strong>escanear el código QR</strong> en la lona de nuestro punto de activación en el Kiosko de Multicentro Las Américas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ==========================================
             PASO 2: FORMULARIO DE REGISTRO
             ========================================== -->
        <section id="step2" class="step-screen screen-form">
            <img src="img/pagina_formulario.jpeg" alt="Formulario de Descuento" class="screen-form-bg">
            
            <form id="registrationForm" class="form-overlay-wrapper" autocomplete="off">
                <div class="form-inputs-slot">
                    <!-- 1. Nombre completo -->
                    <div class="input-row">
                        <input type="text" id="nombre_completo" name="nombre_completo" 
                               placeholder="Nombre completo" required maxlength="100">
                    </div>
                    <!-- 2. Número de celular -->
                    <div class="input-row">
                        <input type="tel" id="numero_contacto" name="numero_contacto" 
                               placeholder="Número de celular" required maxlength="8" inputmode="numeric">
                    </div>
                    <!-- 3. Número de cédula -->
                    <div class="input-row">
                        <input type="text" id="numero_cedula" name="numero_cedula" 
                               placeholder="Número de cédula" required maxlength="16">
                    </div>
                </div>

                <!-- Botón Obtener Descuento -->
                <div class="btn-submit-slot">
                    <button type="submit" id="btnSubmitForm" class="btn-obtener-descuento">
                        OBTENER DESCUENTO <span class="arrow-icon">➔</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- ==========================================
             PASO 3: PANTALLA FINAL (CUPÓN)
             ========================================== -->
        <section id="step3" class="step-screen screen-final">
            <img src="img/pagina_final.jpeg" alt="Tu Descuento Pitaya" class="screen-final-bg">
            
            <!-- Inyección del código de 6 dígitos sobre el recuadro blanco -->
            <div class="final-code-slot">
                <span id="couponDisplayCode" class="coupon-display-code">------</span>
            </div>

            <!-- Botón para copiar código al portapapeles -->
            <div class="final-action-footer">
                <button type="button" id="btnCopyCode" class="btn-copy-code">
                    📋 Copiar Código
                </button>
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
    
    <script src="js/fingerprint.js?v=<?php echo filemtime(__DIR__ . '/js/fingerprint.js'); ?>"></script>
    <script src="js/app.js?v=<?php echo filemtime(__DIR__ . '/js/app.js'); ?>"></script>
</body>
</html>
