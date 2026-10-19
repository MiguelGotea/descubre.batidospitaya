/**
 * Fingerprint Generator para Dispositivos Móviles
 * Combina Canvas, WebGL, señales del sistema y genera un hash SHA-256
 */

async function sha256(str) {
    const buffer = new TextEncoder().encode(str);
    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
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
    const canvasData = getCanvasFingerprint();
    const canvasHash = await sha256(canvasData);
    const webglData = getWebGLFingerprint();
    
    const signals = [
        navigator.userAgent || '',
        navigator.language || '',
        screen.width + 'x' + screen.height,
        screen.colorDepth || '',
        new Date().getTimezoneOffset(),
        Intl.DateTimeFormat().resolvedOptions().timeZone || '',
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
}
