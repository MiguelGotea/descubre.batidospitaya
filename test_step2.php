<?php
$paramK = 'bP9x2Qm7vN4kRj3sT8wLf6Yh';
$hasValidKey = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Preview Step 2</title>
    <link rel="icon" type="image/png" href="img/icon12.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/formulario.css?v=<?php echo time(); ?>">
</head>
<body>
    <main class="app-viewport">
        <!-- Paso 1 (inactivo, tal como queda tras switchStep: sin la clase active) -->
        <section id="step1" class="step-screen screen-initial">
            <img src="img/logo_pitaya.png" alt="Logo" class="initial-logo-top">
            <div class="initial-footer-wrapper">
                <img src="img/pie_pagina.png" alt="Pie" class="img-pie-pagina">
            </div>
        </section>

        <!-- Paso 2 (activo) -->
        <section id="step2" class="step-screen screen-form active">
            <div class="form-content-wrapper">
                <img src="img/logo_pitaya.png" alt="Logo Batidos Pitaya" class="form-logo-top">
                <img src="img/texto_superior_formulario.png" alt="¡Llévate tu 10% de descuento!" class="form-texto-superior">
                
                <form id="registrationForm" class="form-interactive" autocomplete="off">
                    <div class="form-inputs-group">
                        <div class="form-input-card">
                            <span class="form-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Nombre completo" required maxlength="100">
                        </div>

                        <div class="form-input-card">
                            <span class="form-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                            </span>
                            <input type="tel" id="numero_contacto" name="numero_contacto" placeholder="Número de celular" required maxlength="8" inputmode="numeric">
                        </div>

                        <div class="form-input-card">
                            <span class="form-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="3.5"></rect>
                                    <circle cx="8" cy="10" r="2.5"></circle>
                                    <path d="M5.5 16a2.5 2.5 0 0 1 5 0"></path>
                                    <line x1="14" y1="9" x2="18" y2="9"></line>
                                    <line x1="14" y1="13" x2="18" y2="13"></line>
                                </svg>
                            </span>
                            <input type="text" id="numero_cedula" name="numero_cedula" placeholder="Número de cédula" required maxlength="16">
                        </div>
                    </div>

                    <div class="form-btn-slot">
                        <button type="submit" id="btnSubmitForm" class="btn-obtener-img" aria-label="Obtener Descuento">
                            <img src="img/boton_formulario.png" alt="Obtener Descuento" class="img-btn-obtener">
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
