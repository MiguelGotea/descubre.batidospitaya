# 📋 Especificación Técnica — descubre.batidospitaya.com
> Proyecto: Registro por QR en lona física  
> Dominio: `descubre.batidospitaya.com`  
> Fecha: Octubre 2026

---

## 1. Concepto General

Se imprime un **QR fijo en una lona física**. Cualquier persona que escanee ese QR con su celular accede a un formulario de registro. El sistema debe:

- **Permitir** el acceso a quien escanea el QR en el lugar físico
- **Bloquear** el acceso directo al dominio sin la llave del QR
- **Bloquear** que el mismo dispositivo se registre más de una vez
- **Bloquear** que alguien se registre desde una ubicación geográfica lejana al punto de la lona
- **Bloquear** el doble registro de la misma cédula en la base de datos

---

## 2. Llave del QR (Access Key)

### URL completa del QR

```
https://descubre.batidospitaya.com/?k=bP9x2Qm7vN4kRj3sT8wLf6Yh
```

### Características de la llave `bP9x2Qm7vN4kRj3sT8wLf6Yh`

| Propiedad | Valor |
|---|---|
| Longitud | 24 caracteres |
| Composición | Mayúsculas + minúsculas + números entrelazados |
| Patrón | Sin patrón reconocible (sin palabras, sin secuencias) |
| Caracteres URL-safe | Sí (sin `#`, `?`, `&`, `%`, espacios) |
| Dificultad de transcripción | Alta — mezcla de `b/B`, `9/q`, `v/V`, `f/F` sin separadores |

> **¿Por qué dificulta compartirlo por chat?**  
> 24 caracteres de case-sensitive mezclados (`bP9x2Qm7vN4kRj3sT8wLf6Yh`) son prácticamente imposibles de dictar verbalmente o escribir de memoria. Si alguien intenta copiarla a mano y comete un error → acceso denegado.  
> Sin embargo, **copiar-pegar el link completo sí funciona**, por eso se combinan las demás capas.

---

## 3. Arquitectura de Seguridad por Capas

```
┌─────────────────────────────────────────────────────────┐
│                   ESCANEO DEL QR                        │
│         descubre.batidospitaya.com/?k=bP9x2Qm7...       │
└──────────────────────────┬──────────────────────────────┘
                           │
              ┌────────────▼────────────┐
              │   CAPA 1: Validar llave │
              │   ?k == KEY_SECRETA     │
              └────────────┬────────────┘
                    ❌ Falla → Pantalla "Acceso Restringido"
                           │ ✅ OK
              ┌────────────▼────────────────────┐
              │  CAPA 2: Geolocalización         │
              │  ¿Está dentro del radio?         │
              │  Radio: ~300 metros del punto    │
              └────────────┬────────────────────┘
                    ❌ Fuera → "No estás en el lugar del evento"
                           │ ✅ Dentro
              ┌────────────▼────────────────────┐
              │  CAPA 3: Fingerprint dispositivo │
              │  ¿Este dispositivo ya registró? │
              └────────────┬────────────────────┘
                    ❌ Repetido → "Tu dispositivo ya participó"
                           │ ✅ Nuevo
              ┌────────────▼────────────────────┐
              │  CAPA 4: Token de sesión         │
              │  Genera token único (30 min)     │
              │  Redirige a /formulario?s=TOKEN  │
              └────────────┬────────────────────┘
                           │
              ┌────────────▼────────────────────┐
              │  FORMULARIO DE REGISTRO          │
              └────────────┬────────────────────┘
                           │ (al enviar)
              ┌────────────▼────────────────────┐
              │  CAPA 5: Validar cédula única    │
              │  ¿Cédula ya existe en BD?        │
              └────────────┬────────────────────┘
                    ❌ Duplicada → "Cédula ya registrada"
                           │ ✅ Nueva
              ┌────────────▼────────────────────┐
              │  GUARDAR REGISTRO COMPLETO       │
              │  + Datos forenses del dispositivo│
              └─────────────────────────────────┘
```

---

## 4. Detalle de cada Capa

### Capa 1 — Llave QR

- El servidor PHP valida que `$_GET['k']` sea exactamente igual a la constante interna
- La constante **solo existe en el servidor**, nunca se expone en HTML ni JS
- Si la llave es incorrecta o ausente → mostrar página de acceso restringido

```php
// Ejemplo backend
define('QR_ACCESS_KEY', 'bP9x2Qm7vN4kRj3sT8wLf6Yh');

if (!isset($_GET['k']) || $_GET['k'] !== QR_ACCESS_KEY) {
    // Mostrar pantalla de acceso restringido
    die(renderRestrictedPage());
}
```

---

### Capa 2 — Geolocalización

- Al validar la llave, el frontend solicita permiso de geolocalización al usuario
- Si el usuario **niega** → acceso denegado (no puede continuar)
- Si el usuario **acepta** → se verifica que las coordenadas estén dentro del radio permitido
- Las coordenadas exactas del punto de la lona se definen en el servidor (no en el JS)
- El frontend envía `{lat, lng}` al servidor vía POST para validación

**Configuración del radio:**

```php
// Coordenadas del punto de la lona (definir antes de imprimir)
define('LONA_LAT', 12.XXXXXX);   // Completar con coordenadas reales
define('LONA_LNG', -86.XXXXXX);  // Completar con coordenadas reales
define('RADIO_METROS', 300);      // Radio permitido en metros
```

**Fórmula de distancia (Haversine):**
```php
function distanciaMetros($lat1, $lng1, $lat2, $lng2): float {
    $R = 6371000; // Radio tierra en metros
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
```

> **Limitación conocida:** La geolocalización del celular puede tener un margen de error de 20-100 metros en entornos urbanos. El radio de 300 metros lo compensa.  
> **Limitación de privacidad:** Si el usuario tiene desactivado el GPS, el navegador puede usar la IP para geolocalización (menos precisa). Se puede configurar si se exige GPS o se acepta IP-geo.

---

### Capa 3 — Fingerprint de Dispositivo

Se genera un identificador único del dispositivo combinando múltiples señales:

| Señal | Método | Nota |
|---|---|---|
| User-Agent | `navigator.userAgent` | Modelo + OS + navegador |
| Canvas fingerprint | Renderizado de texto en `<canvas>` | Único por GPU/driver |
| WebGL fingerprint | Info del renderizador gráfico | Muy estable |
| Idioma del sistema | `navigator.language` | Dato adicional |
| Resolución de pantalla | `screen.width x screen.height` | |
| Zona horaria | `Intl.DateTimeFormat` | |
| IP pública | Registrada en servidor | |

**El fingerprint se hashea en SHA256 antes de guardarse en BD.**

Si el hash del dispositivo ya existe en la tabla `activaciones_dispositivos` → mostrar pantalla "Tu dispositivo ya participó en esta activación".

> **Limitación conocida:** El fingerprint de canvas/WebGL puede cambiar si el usuario actualiza drivers o usa modo incógnito + diferentes navegadores. No es 100% infalible pero bloquea el 95% de los casos casuales.

---

### Capa 4 — Token de Sesión

Propósito: evitar que alguien que recibe el link `?k=LLAVE` lo pueda usar indefinidamente una vez conocido.

**Flujo:**
1. Servidor valida llave → genera token UUID único → lo guarda en tabla `sesiones_qr`
2. Token tiene expiración de **30 minutos**
3. Redirige al usuario a `/formulario?s=TOKEN`
4. El formulario solo es accesible con un `?s=TOKEN` válido y no expirado
5. Al enviar el formulario, el token se **marca como usado** (no se puede reutilizar)

```sql
CREATE TABLE sesiones_qr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    ip_origen VARCHAR(45),
    device_fingerprint VARCHAR(64),
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    expira_en DATETIME,
    usado TINYINT(1) DEFAULT 0,
    INDEX idx_token (token)
);
```

---

### Capa 5 — Validación de Cédula Única

- Al enviar el formulario, se verifica que la cédula no exista en la tabla `registros`
- Si existe → "Esta cédula ya fue registrada en la activación"
- La cédula se guarda normalizada (sin guiones, uppercase) para evitar duplicados por formato

---

## 5. Datos Forenses Guardados por Registro

Cada registro en BD almacena la siguiente información del dispositivo:

```sql
CREATE TABLE registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Datos del formulario
    nombre_completo VARCHAR(200) NOT NULL,
    numero_cedula VARCHAR(20) NOT NULL UNIQUE,
    numero_contacto VARCHAR(15),
    correo_electronico VARCHAR(150),
    
    -- Datos forenses del dispositivo
    ip_publica VARCHAR(45),
    user_agent TEXT,
    device_fingerprint VARCHAR(64),    -- Hash SHA256 de señales del dispositivo
    canvas_fingerprint VARCHAR(64),    -- Hash del canvas rendering
    
    -- Geolocalización
    geo_lat DECIMAL(10, 8),
    geo_lng DECIMAL(11, 8),
    geo_precision_metros INT,          -- Precisión reportada por el navegador
    geo_metodo ENUM('gps','ip','manual') DEFAULT 'gps',
    
    -- Sesión
    session_token VARCHAR(64),         -- Token de sesión usado
    
    -- Metadatos
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activacion_id INT DEFAULT 1,       -- Para futuras activaciones
    
    INDEX idx_cedula (numero_cedula),
    INDEX idx_fingerprint (device_fingerprint),
    INDEX idx_ip (ip_publica)
);
```

> **Nota sobre MAC address:** Los navegadores web modernos **no exponen la MAC address** por razones de privacidad (esto fue bloqueado desde HTML5). No es posible obtenerla desde JavaScript en el navegador. El fingerprint de canvas+WebGL+UA cubre ese rol de forma equivalente.

---

## 6. Estructura de Archivos del Proyecto

```
descubre.batidospitaya.com/
├── index.php               ← Entrada principal (valida llave + geo + fingerprint)
├── formulario.php          ← Formulario (valida token de sesión)
├── save_registration.php   ← Guarda registro (valida cédula + token)
├── config/
│   ├── keys.php            ← QR_ACCESS_KEY, coordenadas lona, radio (PRIVADO)
│   └── database.php        ← Conexión BD
├── core/
│   ├── session_manager.php ← Genera y valida tokens de sesión
│   ├── geo_validator.php   ← Valida geolocalización (Haversine)
│   ├── fingerprint.php     ← Lógica de fingerprint servidor
│   └── device_blocker.php  ← Verifica dispositivos ya registrados
├── css/
│   └── styles.css
├── js/
│   ├── fingerprint.js      ← Genera canvas/WebGL fingerprint en cliente
│   └── geo.js              ← Solicita y envía geolocalización
└── img/
```

---

## 7. Pantallas del Flujo

### 7.1 Pantalla de Acceso Restringido
Mostrada cuando alguien accede sin llave o con llave incorrecta.
> *"Para acceder a esta activación, escanea el código QR en el punto de activación."*

### 7.2 Pantalla de Permiso de Ubicación
Se muestra antes de pedir permisos al navegador.
> *"Necesitamos verificar que estás en el lugar de la activación. Por favor permite el acceso a tu ubicación."*

### 7.3 Pantalla de Fuera de Rango
> *"Parece que no estás en el punto de la activación. Este registro solo está disponible en [Nombre del local]."*

### 7.4 Pantalla de Dispositivo Ya Registrado
> *"Tu dispositivo ya fue registrado en esta activación. Cada participante puede registrarse una sola vez."*

### 7.5 Formulario de Registro
Campos mínimos (a definir en desarrollo):
- Nombre completo
- Número de cédula
- Número de contacto
- Correo electrónico (opcional)
- *(campos adicionales según el objetivo de la activación)*

### 7.6 Confirmación de Registro
> *"¡Registro exitoso! Gracias por participar."*

---

## 8. Resumen de Qué Bloquea Cada Capa

| Intento de acceso | C1 Llave | C2 Geo | C3 Device | C4 Token | C5 Cédula |
|---|:---:|:---:|:---:|:---:|:---:|
| URL directa sin llave | ❌ | — | — | — | — |
| Llave mal escrita | ❌ | — | — | — | — |
| Link compartido, usuario lejano | ✅ | ❌ | — | — | — |
| Link compartido, usuario cercano | ✅ | ✅ | ❌* | — | — |
| Token de sesión vencido (>30min) | ✅ | ✅ | ✅ | ❌ | — |
| Mismo celular, segundo intento | ✅ | ✅ | ❌ | — | — |
| Persona diferente, misma cédula | ✅ | ✅ | ✅ | ✅ | ❌ |
| Registro legítimo desde QR | ✅ | ✅ | ✅ | ✅ | ✅ |

> *Si el dispositivo es nuevo (no registrado aún) y está cerca, puede entrar. La cédula es el candado final.

---

## 9. Configuración Previa al Lanzamiento

Antes de imprimir la lona y lanzar el proyecto, definir:

- [ ] Coordenadas GPS exactas del punto donde va la lona
- [ ] Radio de geolocalización permitido (recomendado: 300 m)
- [ ] Nombre de la activación / campaña
- [ ] Campos exactos del formulario
- [ ] Base de datos destino (¿la del ERP o una nueva?)
- [ ] Fecha de inicio y fin de la activación (para desactivar el QR automáticamente)
- [ ] Mensaje de la pantalla de confirmación (¿sorteo, descuento, membresía?)
