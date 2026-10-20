/**
 * Lógica de la Aplicación Móvil - descubre.batidospitaya.com
 */

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
    const pcQrImg = document.getElementById('pcQrImg');
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
    const btnCopyCode = document.getElementById('btnCopyCode');

    const inputNombre = document.getElementById('nombre_completo');
    const inputContacto = document.getElementById('numero_contacto');
    const inputCedula = document.getElementById('numero_cedula');

    // 1. RESTRICCIÓN PARA PC (SOLO FORMATO CELULAR)
    function verificarDispositivoMovil() {
        const isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isWideScreen = window.innerWidth > 800;

        // Si es pantalla de escritorio amplia y no es un dispositivo móvil con pantalla táctil
        if (isWideScreen && (!isMobileUA || navigator.maxTouchPoints === 0)) {
            if (pcBlocker) {
                pcBlocker.style.display = 'flex';
                // Generar QR para escanear en celular
                const currentUrl = encodeURIComponent(window.location.href);
                if (pcQrImg) {
                    pcQrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${currentUrl}`;
                }
            }
            return false;
        } else {
            if (pcBlocker) pcBlocker.style.display = 'none';
            return true;
        }
    }

    verificarDispositivoMovil();
    window.addEventListener('resize', verificarDispositivoMovil);

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
        setTimeout(() => {
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
                                    data.message,
                                    '🎁'
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
                    showToast('🎉 ¡Descuento obtenido con éxito!');
                } else {
                    showErrorModal('No se pudo registrar', data.message || 'Ocurrió un error al procesar tu solicitud.', '⚠️');
                }
            } catch (err) {
                showErrorModal('Error de Conexión', 'No se pudo comunicar con el servidor. Revisa tu conexión a internet.', '📶');
            }
        });
    }

    // 6. PASO 3: COPIAR CÓDIGO
    if (btnCopyCode) {
        btnCopyCode.addEventListener('click', () => {
            const code = couponDisplayCode ? couponDisplayCode.textContent.trim() : '';
            if (code && navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    showToast('¡Código ' + code + ' copiado al portapapeles!');
                }).catch(() => {
                    showToast('Código: ' + code);
                });
            } else if (code) {
                showToast('Código: ' + code);
            }
        });
    }
});
