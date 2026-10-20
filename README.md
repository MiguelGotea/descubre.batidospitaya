# 🥤 Descubre Batidos Pitaya — Sistema de Activación y Descuento por QR

Plataforma web para registro de participantes mediante código QR físico ubicado en el punto de activación (Kiosko Multicentro Las Américas, Managua).

---

## 🔒 Arquitectura de Seguridad (5 Capas)

1. **Capa 1 — Validación de Llave de Acceso (`k`)**:
   - URL del QR: `https://descubre.batidospitaya.com/?k=bP9x2Qm7vN4kRj3sT8wLf6Yh`
   - Si no incluye la llave o es incorrecta, se oculta el botón "Continuar" y se muestra un mensaje informativo invitando a escanear el QR físico.
2. **Capa 2 — Geolocalización por Haversine**:
   - Valida que el participante se encuentre dentro de un radio de **300 metros** del punto físico:
     - **Latitud**: `12.139557`
     - **Longitud**: `-86.228827`
3. **Capa 3 — Fingerprint del Dispositivo**:
   - Combina Canvas rendering, WebGL info, resolución de pantalla, User-Agent y timezone en un hash SHA-256 único.
   - Bloquea más de un registro por dispositivo físico.
4. **Capa 4 — Token de Sesión Temporal**:
   - Una vez validadas las Capas 1, 2 y 3, el servidor emite un token de sesión seguro con vigencia de **30 minutos**.
5. **Capa 5 — Cédula Única y Cupón Alfanumérico**:
   - Normaliza la cédula nicaragüense y valida que no haya sido registrada previamente.
   - Genera un código único alfanumérico en mayúsculas de 6 dígitos (ej: `PIT8K2`).
   - Bloqueo en PC: Diseñado exclusivamente para celulares (pantallas mayores a 800px sin touch son bloqueadas mostrando un QR para abrirlo en el móvil).

---

## 🗄️ Base de Datos

Las tablas operan sobre la base de datos `u839374897_erp`:

1. `descubre_sesiones_qr`: Registra tokens temporales y coordenadas validadas.
2. `descubre_registros`: Registra a los participantes, cédulas, datos forenses del dispositivo y los códigos únicos de descuento.

---

## 🚀 Despliegue Continuo (GitHub Actions)

El flujo `.github/workflows/deploy-descubre.yml` se dispara automáticamente en cada `push` a la rama `main`.

### Secrets requeridos en GitHub:
En el repositorio `https://github.com/MiguelGotea/descubre.batidospitaya` ir a **Settings > Secrets and variables > Actions** y configurar:

| Secret | Descripción | Valor Ejemplo |
|---|---|---|
| `HOSTINGER_HOST` | Host o IP del servidor Hostinger | `195.35.xxx.xxx` o `batidospitaya.com` |
| `HOSTINGER_USER` | Usuario SSH de Hostinger | `u839374897` |
| `HOSTINGER_SSH_KEY` | Llave privada SSH configurada en Hostinger | `-----BEGIN OPENSSH PRIVATE KEY----- ...` |
| `HOSTINGER_PATH_DESCUBRE` | Ruta absoluta del dominio en Hostinger | `/home/u839374897/domains/descubre.batidospitaya.com/public_html` |

---

## 🌐 Comportamiento de `.htaccess`

Actualmente el archivo `.htaccess` contiene una regla activa de redirección temporal:

```apache
RewriteEngine On
RewriteRule ^.*$ https://www.instagram.com/batidospitaya/? [R=302,L]
```

- **Mientras esta línea exista y no esté comentada**, cualquier persona que entre a `descubre.batidospitaya.com` será redirigida a la cuenta de Instagram.
- **Para hacer pública la aplicación web**, simplemente comenta la línea anteponiendo un `#`:
  ```apache
  # RewriteRule ^.*$ https://www.instagram.com/batidospitaya/? [R=302,L]
  ```
