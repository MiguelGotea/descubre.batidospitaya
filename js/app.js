/**
 * Generador de Firma de Dispositivo integrado (anti-bloqueo)
 * Combina Canvas, WebGL, señales del sistema y genera un hash SHA-256
 */
async function sha256(str) {
    try {
        if (window.crypto && crypto.subtle && window.TextEncoder) {
            const buffer = new TextEncoder().encode(str);
            const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        }
    } catch (e) {
        // Fallback silencioso si crypto.subtle falla o está en HTTP no seguro
    }
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = ((hash << 5) - hash) + str.charCodeAt(i);
        hash |= 0;
    }
    return Math.abs(hash).toString(16).padStart(16, '0') + Math.abs(hash * 31).toString(16).padStart(16, '0');
}

function getCanvasFingerprint() {
    try {
        const canvas = document.createElement('canvas');
        canvas.width = 200;
        canvas.height = 50;
        const ctx = canvas.getContext('2d');
        if (!ctx) return 'canvas_unsupported';

        ctx.textBaseline = 'top';
        ctx.font = "14px 'Arial', sans-serif";
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);

        ctx.fillStyle = '#069';
        ctx.fillText('Pitaya-Descubre-2026', 2, 15);
        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
        ctx.fillText('✨Batidos Pitaya Nicaragua🥤', 4, 17);

        return canvas.toDataURL();
    } catch (e) {
        return 'canvas_error';
    }
}

function getWebGLFingerprint() {
    try {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) return 'webgl_unsupported';

        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
        if (!debugInfo) return 'webgl_no_debug_info';

        const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) || '';
        const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || '';
        return `${vendor}~~~${renderer}`;
    } catch (e) {
        return 'webgl_error';
    }
}

async function getDeviceFingerprint() {
    try {
        const canvasData = getCanvasFingerprint();
        const canvasHash = await sha256(canvasData);
        const webglData = getWebGLFingerprint();

        const signals = [
            navigator.userAgent || '',
            navigator.language || '',
            (window.screen ? window.screen.width + 'x' + window.screen.height : ''),
            (window.screen ? window.screen.colorDepth : ''),
            new Date().getTimezoneOffset(),
            (window.Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone || '' : ''),
            navigator.hardwareConcurrency || 0,
            navigator.maxTouchPoints || 0,
            webglData,
            canvasHash
        ].join('###');

        const deviceHash = await sha256(signals);

        return {
            deviceFingerprint: deviceHash,
            canvasFingerprint: canvasHash
        };
    } catch (err) {
        return {
            deviceFingerprint: 'client_' + Date.now().toString(16),
            canvasFingerprint: 'fallback'
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Configuración inyectada desde PHP
    const config = window.APP_CONFIG || {
        hasKey: false,
        k: '',
        token: ''
    };

    let sessionToken = config.token || '';
    let cachedFingerprint = null;

    // Elementos del DOM
    const pcBlocker = document.getElementById('pcBlocker');
    const modalOverlay = document.getElementById('modalOverlay');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalIcon = document.getElementById('modalIcon');
    const modalBtn = document.getElementById('modalBtn');
    const modalSpinner = document.getElementById('modalSpinner');
    const toast = document.getElementById('toastMsg');

    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');

    const btnContinuar = document.getElementById('btnContinuar');
    const registrationForm = document.getElementById('registrationForm');
    const couponDisplayCode = document.getElementById('couponDisplayCode');
    const btnCapturaCupon = document.getElementById('btnCapturaCupon');

    const inputNombre = document.getElementById('nombre_completo');
    const inputContacto = document.getElementById('numero_contacto');
    const inputCedula = document.getElementById('numero_cedula');

    // 1. RESTRICCIÓN PARA PC (SOLO FORMATO CELULAR)
    /**
     * Detección multicapa de dispositivos móviles (Smartphones, Tablets, Plegables)
     * Cubre todo el panorama de dispositivos móviles:
     * - Android (Chrome, Samsung Internet, Firefox, Opera, Edge, Brave, etc.)
     * - iOS (iPhone, iPad con Safari, Chrome, Firefox)
     * - Dispositivos en modo "Sitio para computadoras" / "Desktop site" (como en Android Chrome)
     * - Teléfonos plegables abiertos o cerrados (Galaxy Fold, Pixel Fold, etc.)
     * - Dispositivos Huawei / Honor (HarmonyOS, OpenHarmony, Petal Browser)
     * - WebViews y navegadores in-app (WhatsApp, Instagram, Facebook, TikTok, Google Lens)
     * - Bloquea con absoluta precisión computadoras de escritorio y laptops reales (PC/Mac)
     */
    function esDispositivoMovil() {
        const ua = (navigator.userAgent || navigator.vendor || window.opera || '').toLowerCase();
        const platform = (navigator.platform || '').toLowerCase();

        // 1. Detección exhaustiva por User-Agent
        const mobileKeywords = [
            'android', 'iphone', 'ipad', 'ipod', 'mobile', 'tablet',
            'phone', 'harmonyos', 'openharmony', 'huawei', 'honor',
            'samsungbrowser', 'ucbrowser', 'miuibrowser', 'xiaomi',
            'redmi', 'oppo', 'vivo', 'realme', 'motorola', 'oneplus',
            'crios', 'fxios', 'edgios', 'edga', 'opera mobi', 'opera mini',
            'blackberry', 'iemobile', 'webos', 'silk', 'kindle',
            'line', 'instagram', 'fban', 'fbav', 'whatsapp', 'tiktok', 'wv'
        ];
        const hasMobileUA = mobileKeywords.some(keyword => ua.includes(keyword));

        // 2. Client Hints API moderna (Chromium: Chrome, Samsung Internet, Edge)
        const isClientHintMobile = Boolean(navigator.userAgentData && navigator.userAgentData.mobile);

        // 3. Detección de iPadOS y iOS simulando Mac de escritorio (Apple Safari en iPad / iPhone)
        // Desde iPadOS 13, Apple reporta platform='MacIntel' y UA='Macintosh', pero con touchPoints > 1
        const isAppleMobile = (platform.includes('macintel') || ua.includes('macintosh')) &&
            Boolean(navigator.maxTouchPoints && navigator.maxTouchPoints > 1);

        // 4. Capacidades táctiles del hardware (Hardware Touch Screen)
        const maxTouch = navigator.maxTouchPoints || navigator.msMaxTouchPoints || 0;
        const hasTouchSupport = maxTouch > 0 || ('ontouchstart' in window) || (typeof TouchEvent !== 'undefined');

        // 5. CSS Media Queries de interacción física (Nivel 4)
        const isCoarsePointer = window.matchMedia ? (
            window.matchMedia('(pointer: coarse)').matches ||
            window.matchMedia('(any-pointer: coarse)').matches
        ) : false;

        const hasNoHover = window.matchMedia ? window.matchMedia('(hover: none)').matches : false;
        const hasFineHover = window.matchMedia ? (
            window.matchMedia('(hover: hover)').matches &&
            window.matchMedia('(pointer: fine)').matches
        ) : false;

        // 6. Dimensiones físicas y proporciones de la pantalla del hardware
        const screenW = window.screen ? window.screen.width : window.innerWidth;
        const screenH = window.screen ? window.screen.height : window.innerHeight;
        const minScreenDim = Math.min(screenW, screenH);

        // 7. Sensores y APIs exclusivas de dispositivos móviles
        const hasOrientationAPI = typeof window.orientation !== 'undefined' || Boolean(window.screen && window.screen.orientation);
        const dpr = window.devicePixelRatio || 1;

        // 8. Detección especial: Celular con "Sitio para computadoras" / "Desktop site" activado
        // En este modo, Chrome Android envía UA 'X11; Linux x86_64' y simula innerWidth = 980px.
        // Pero conserva: pantalla táctil (5-10 puntos), pointer coarse, pantalla física móvil (<= 600px),
        // alta densidad de píxeles (DPR >= 1.75), o sensores de orientación móvil.
        const isDesktopModeOnMobile = hasTouchSupport && (
            isCoarsePointer ||
            hasNoHover ||
            minScreenDim <= 600 ||
            dpr >= 1.75
        );

        // --- EVALUACIÓN DE DECISIÓN ---

        // Si tiene UA móvil explícito o Client Hint móvil: es móvil
        if (hasMobileUA || isClientHintMobile) {
            return true;
        }

        // Si es dispositivo Apple táctil (iPad / iPhone en modo desktop): es móvil
        if (isAppleMobile) {
            return true;
        }

        // Si es un celular con "Sitio para computadoras": es móvil
        if (isDesktopModeOnMobile) {
            return true;
        }

        // Si la dimensión menor de pantalla física es de formato móvil/tablet y tiene touch: es móvil
        if (minScreenDim <= 850 && hasTouchSupport) {
            return true;
        }

        // Si el puntero principal es táctil y no tiene ratón de escritorio con hover fino: es móvil
        if (isCoarsePointer && !hasFineHover) {
            return true;
        }

        // Si no cumple ninguna señal móvil y es un dispositivo de pantalla ancha con ratón: es PC/Laptop
        return false;
    }

    function verificarDispositivoMovil() {
        const esMovil = esDispositivoMovil();

        if (!esMovil) {
            if (pcBlocker) {
                pcBlocker.style.display = 'flex';
            }
            return false;
        } else {
            if (pcBlocker) {
                pcBlocker.style.display = 'none';
            }
            document.body.classList.add('is-mobile-device');
            return true;
        }
    }

    verificarDispositivoMovil();
    window.addEventListener('resize', verificarDispositivoMovil);
    window.addEventListener('orientationchange', verificarDispositivoMovil);

    // 2. MODAL HELPERS
    function showLoadingModal(title, msg) {
        modalIcon.style.display = 'none';
        modalSpinner.style.display = 'block';
        modalTitle.textContent = title;
        modalMessage.textContent = msg;
        modalBtn.style.display = 'none';
        modalOverlay.classList.add('active');
    }

    function showErrorModal(title, msg, icon = '⚠️') {
        modalSpinner.style.display = 'none';
        modalIcon.style.display = 'block';
        modalIcon.textContent = icon;
        modalTitle.textContent = title;
        modalMessage.textContent = msg;
        modalBtn.style.display = 'block';
        modalBtn.textContent = 'Entendido';
        modalBtn.onclick = () => {
            modalOverlay.classList.remove('active');
        };
        modalOverlay.classList.add('active');
    }

    function hideModal() {
        modalOverlay.classList.remove('active');
    }

    function showToast(msg) {
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function switchStep(fromStep, toStep) {
        fromStep.classList.remove('active');
        fromStep.style.display = 'none';
        setTimeout(() => {
            toStep.style.display = 'flex';
            toStep.classList.add('active');
            if (toStep.id !== 'step1') {
                document.body.style.overflow = 'auto';
                const viewport = document.querySelector('.app-viewport');
                if (viewport) {
                    viewport.style.overflow = 'auto';
                    viewport.style.height = 'auto';
                    viewport.style.minHeight = '100dvh';
                }
            } else {
                document.body.style.overflow = 'hidden';
                const viewport = document.querySelector('.app-viewport');
                if (viewport) {
                    viewport.style.overflow = 'hidden';
                    viewport.style.height = '100dvh';
                }
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 200);
    }

    // 3. PASO 1: CLICK EN CONTINUAR -> VALIDACIÓN GEOLOCALIZACIÓN Y DISPOSITIVO
    if (btnContinuar) {
        btnContinuar.addEventListener('click', async (e) => {
            e.preventDefault();

            if (!config.hasKey || !config.k) {
                showErrorModal('Acceso Restringido', 'Para participar debés escanear el código QR en nuestro punto de activación.', '🔒');
                return;
            }

            showLoadingModal('Verificando ubicación', 'Por favor permite el acceso a tu ubicación cuando el navegador lo solicite...');

            // Obtener fingerprint
            try {
                if (!cachedFingerprint) {
                    cachedFingerprint = await getDeviceFingerprint();
                }
            } catch (err) {
                console.error("Error fingerprint:", err);
                cachedFingerprint = { deviceFingerprint: '', canvasFingerprint: '' };
            }

            // Solicitar geolocalización
            if (!navigator.geolocation) {
                showErrorModal('GPS no disponible', 'Tu navegador o dispositivo no soporta geolocalización.', '📍');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    showLoadingModal('Comprobando activación', 'Validando cercanía al Kiosko de Batidos Pitaya...');

                    try {
                        const formData = new FormData();
                        formData.append('k', config.k);
                        formData.append('lat', lat);
                        formData.append('lng', lng);
                        formData.append('accuracy', accuracy);
                        formData.append('fingerprint', cachedFingerprint.deviceFingerprint);

                        const res = await fetch('validate_access.php', {
                            method: 'POST',
                            body: formData
                        });

                        const text = await res.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (parseErr) {
                            console.error("Respuesta no válida del servidor:", text);
                            showErrorModal('Error del Servidor', 'El servidor retornó: ' + (text.substring(0, 150) || 'Error ' + res.status), '⚠️');
                            return;
                        }

                        if (data.success && data.token) {
                            sessionToken = data.token;
                            hideModal();
                            // Pasar a Paso 2 (Formulario)
                            switchStep(step1, step2);
                        } else {
                            if (data.error_type === 'OUT_OF_RANGE') {
                                showErrorModal(
                                    'Fuera del Punto de Activación',
                                    data.message || 'Debes estar cerca del Kiosko de Batidos Pitaya en Multicentro Las Américas para participar.',
                                    '📍'
                                );
                            } else if (data.error_type === 'DEVICE_ALREADY_REGISTERED') {
                                showErrorModal(
                                    'Dispositivo Ya Registrado',
                                    data.message
                                );
                            } else {
                                showErrorModal('Atención', data.message || 'No se pudo validar el acceso.', '⚠️');
                            }
                        }
                    } catch (netErr) {
                        showErrorModal('Error de Conexión', 'Hubo un problema al conectar con el servidor. Por favor intenta de nuevo.', '📶');
                    }
                },
                (geoErr) => {
                    let msg = 'Es necesario permitir el acceso a la ubicación en tu navegador para validar que estás en el punto de activación.';
                    if (geoErr.code === geoErr.PERMISSION_DENIED) {
                        msg = 'Permiso de ubicación denegado. Por favor actívalo en los ajustes de tu navegador para obtener tu descuento.';
                    } else if (geoErr.code === geoErr.POSITION_UNAVAILABLE) {
                        msg = 'No se pudo determinar tu ubicación actual. Asegúrate de tener el GPS activado.';
                    } else if (geoErr.code === geoErr.TIMEOUT) {
                        msg = 'El tiempo de espera para obtener la ubicación expiró. Intenta de nuevo.';
                    }
                    showErrorModal('Ubicación requerida', msg, '📍');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0
                }
            );
        });
    }

    // 4. MÁSCARAS Y FORMATEO DE INPUTS
    if (inputContacto) {
        inputContacto.addEventListener('input', (e) => {
            // Solo dígitos, máximo 8
            let val = e.target.value.replace(/\D/g, '');
            if (val.length > 8) val = val.substring(0, 8);
            e.target.value = val;
        });
    }

    if (inputCedula) {
        inputCedula.addEventListener('input', (e) => {
            let val = e.target.value.toUpperCase().replace(/[^0-9A-Z]/g, '');
            // Formatear estilo 001-000000-0000X
            let formatted = '';
            if (val.length > 0) formatted += val.substring(0, 3);
            if (val.length > 3) formatted += '-' + val.substring(3, 9);
            if (val.length > 9) formatted += '-' + val.substring(9, 14);
            e.target.value = formatted;
        });
    }

    if (inputNombre) {
        inputNombre.addEventListener('blur', (e) => {
            // Capitalizar palabras
            let words = e.target.value.trim().toLowerCase().split(/\s+/);
            e.target.value = words.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        });
    }

    // 5. PASO 2: ENVÍO DEL FORMULARIO Y OBTENCIÓN DE CÓDIGO
    if (registrationForm) {
        registrationForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nombre = inputNombre.value.trim();
            const contacto = inputContacto.value.trim();
            const cedula = inputCedula.value.trim();

            if (!nombre || !contacto || !cedula) {
                showErrorModal('Campos requeridos', 'Por favor completa todos los campos del formulario.', '📝');
                return;
            }

            const palabras = nombre.split(/\s+/);
            if (palabras.length < 2) {
                showErrorModal('Nombre Incompleto', 'Por favor ingresa tu nombre y apellido.', '👤');
                return;
            }

            if (contacto.length !== 8) {
                showErrorModal('Celular Inválido', 'El número de celular debe contener 8 dígitos.', '📱');
                return;
            }

            const cedulaLimpia = cedula.replace(/[^0-9A-Z]/gi, '');
            if (cedulaLimpia.length < 14) {
                showErrorModal('Cédula Inválida', 'Por favor ingresa una cédula nicaragüense válida.', '🪪');
                return;
            }

            showLoadingModal('Generando descuento', 'Validando tus datos y creando tu cupón de apertura...');

            try {
                if (!cachedFingerprint) {
                    cachedFingerprint = await getDeviceFingerprint();
                }

                const formData = new FormData();
                formData.append('session_token', sessionToken);
                formData.append('nombre_completo', nombre);
                formData.append('numero_contacto', contacto);
                formData.append('numero_cedula', cedula);
                formData.append('device_fingerprint', cachedFingerprint.deviceFingerprint);
                formData.append('canvas_fingerprint', cachedFingerprint.canvasFingerprint);

                const res = await fetch('save_registration.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    console.error("Respuesta no válida del servidor:", text);
                    showErrorModal('Error del Servidor', 'El servidor retornó: ' + (text.substring(0, 150) || 'Error ' + res.status), '⚠️');
                    return;
                }

                if (data.success && data.codigo) {
                    hideModal();
                    // Inyectar código en la pantalla final
                    if (couponDisplayCode) {
                        couponDisplayCode.textContent = data.codigo;
                    }
                    // Pasar al Paso 3 (Pantalla final)
                    switchStep(step2, step3);
                    showToast('¡Descuento obtenido con éxito!');
                } else {
                    showErrorModal('No se pudo registrar', data.message || 'Ocurrió un error al procesar tu solicitud.', '⚠️');
                }
            } catch (err) {
                showErrorModal('Error de Conexión', 'No se pudo comunicar con el servidor. Revisa tu conexión a internet.', '📶');
            }
        });
    }

    // 6. PASO 3: BOTÓN AMARILLO PALPITANTE - GENERAR IMAGEN DEL TICKET / COMPARTIR
    if (btnCapturaCupon) {
        btnCapturaCupon.addEventListener('click', async () => {
            const code = couponDisplayCode ? couponDisplayCode.textContent.trim() : '';
            if (!code || code === '------') {
                showToast('Generando cupón...');
                return;
            }

            showLoadingModal('Guardando Ticket', 'Preparando la imagen de tu ticket con el código...');

            try {
                // Asegurar que la tipografía esté lista en el navegador
                if (document.fonts && document.fonts.ready) {
                    await document.fonts.ready;
                }

                const baseImg = new Image();
                baseImg.crossOrigin = 'anonymous';

                await new Promise((resolve, reject) => {
                    baseImg.onload = resolve;
                    baseImg.onerror = reject;
                    baseImg.src = 'img/ticket_cupon.png';
                });

                // Dimensiones HD basadas en la resolución del nuevo ticket (675x607 x scale 2)
                const origWidth = baseImg.naturalWidth || 675;
                const origHeight = baseImg.naturalHeight || 607;
                const scale = 2;
                const canvas = document.createElement('canvas');
                canvas.width = origWidth * scale;   // 1350
                canvas.height = origHeight * scale;  // 1214
                const ctx = canvas.getContext('2d');

                // Dibujar el ticket base
                ctx.drawImage(baseImg, 0, 0, canvas.width, canvas.height);

                // Posición exacta del centro de la cápsula clara en ticket_2.png (675x607)
                // Centro horizontal = 50% (337.5px), Centro cápsula Y = 538px (88.63%)
                const centerX = canvas.width / 2;
                const centerY = Math.round(canvas.height * (538 / 607));

                ctx.fillStyle = '#0A3D36';
                ctx.font = '900 68px "Outfit", sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                const spacedCode = code.split('').join('  ');
                ctx.fillText(spacedCode, centerX, centerY);

                hideModal();

                // Convertir a Blob para compartir, copiar o descargar
                canvas.toBlob(async (blob) => {
                    if (!blob) {
                        showToast('Código: ' + code);
                        return;
                    }

                    const fileName = `ticket_pitaya_${code}.png`;
                    const file = new File([blob], fileName, { type: 'image/png' });

                    // 1. Intentar copiar imagen al portapapeles si el navegador lo permite
                    let imageCopied = false;
                    if (navigator.clipboard && window.ClipboardItem) {
                        try {
                            await navigator.clipboard.write([
                                new ClipboardItem({ 'image/png': blob })
                            ]);
                            imageCopied = true;
                        } catch (clipErr) {
                            // Silencioso: algunos navegadores restringen ClipboardItem para imágenes
                        }
                    }

                    // 2. Web Share API para guardar directamente en Fotos/Galería en móviles
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({
                                files: [file],
                                title: 'Ticket Cupón Batidos Pitaya',
                                text: `¡Mi 10% de descuento en Batidos Pitaya! Código: ${code}`
                            });
                            showToast(imageCopied ? '🎟️ ¡Ticket copiado y listo en tus fotos!' : '🎟️ ¡Ticket listo en tus fotos!');
                            return;
                        } catch (shareErr) {
                            if (shareErr.name === 'AbortError') return;
                        }
                    }

                    // 3. Descarga directa del ticket como respaldo confiable
                    const downloadUrl = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(() => URL.revokeObjectURL(downloadUrl), 4000);

                    // 4. Copiar código de texto al portapapeles si la imagen no se pudo copiar
                    if (navigator.clipboard && !imageCopied) {
                        navigator.clipboard.writeText(code).catch(() => { });
                    }

                    showToast(imageCopied ? '🎟️ ¡Ticket descargado y copiado!' : '🎟️ ¡Ticket guardado en tu galería!');
                }, 'image/png');

            } catch (err) {
                hideModal();
                console.error("Error al generar ticket:", err);
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(code).catch(() => { });
                }
                showToast('¡Código: ' + code + ' copiado al portapapeles!');
            }
        });
    }
});
