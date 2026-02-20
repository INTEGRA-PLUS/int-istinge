<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

---

## 🚀 Sistema de Deployment Automatizado

Este repositorio incluye un sistema de deployment automatizado con **GitHub Actions** para desplegar código y migraciones de base de datos a **40 servidores cPanel**.

### Características

- ✅ **Deployment automático** en cada push a `master`
- ✅ **Ejecución manual** con opciones configurables
- ✅ **Deployment paralelo** (máximo 5 servidores simultáneos)
- ✅ **Tolerante a fallos** (un servidor fallido no detiene los demás)
- ✅ **Migraciones SQL** con control de versiones
- ✅ **Seguridad** - Credenciales en GitHub Secrets

---

## ⚠️ CONFIGURACIÓN INICIAL DE SEGURIDAD

> **ADVERTENCIA CRÍTICA**: El archivo `config/servers.json` contiene credenciales sensibles y **NUNCA** debe subirse a GitHub. Este archivo está en `.gitignore` para evitar fugas accidentales.

### Paso 1: Verificar .gitignore

Asegúrate de que `.gitignore` incluya las siguientes líneas:

```gitignore
# ARCHIVOS SENSIBLES - NUNCA SUBIR A GITHUB
config/servers.json
*.pem
*.key
id_rsa
id_rsa.pub
```

### Paso 2: Crear archivo de configuración local (SOLO PARA PRUEBAS)

```bash
# Copiar plantilla
cp config/servers.json.example config/servers.json

# Editar con tus datos reales (ESTE ARCHIVO NO SE SUBE A GITHUB)
nano config/servers.json
```

### Paso 3: Configurar GitHub Secrets

Ve a tu repositorio en GitHub → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

#### Secrets requeridos:

| Secret Name | Descripción |
|-------------|-------------|
| `SSH_PRIVATE_KEY` | Clave SSH privada para acceso a servidores |
| `SERVERS_CONFIG` | JSON completo de configuración de servidores |
| `DB_PASS_SERVER_0` | Contraseña MySQL del servidor 0 |
| `DB_PASS_SERVER_1` | Contraseña MySQL del servidor 1 |
| ... | ... |
| `DB_PASS_SERVER_39` | Contraseña MySQL del servidor 39 |

#### Configurar SSH_PRIVATE_KEY

```bash
# En tu máquina local, copiar la clave privada:
cat ~/.ssh/id_rsa

# Pegar el contenido completo (incluyendo -----BEGIN y -----END-----)
# como valor del secret SSH_PRIVATE_KEY
```

#### Configurar SERVERS_CONFIG

El valor debe ser el JSON completo de tus servidores. Ejemplo mínimo:

```json
{
  "servers": [
    {
      "name": "Server 1 - Cliente A",
      "host": "servidor1.com",
      "port": 2222,
      "user": "usuario1",
      "path": "/home/usuario1/public_html",
      "db_name": "usuario1_db",
      "db_user": "usuario1_dbuser",
      "enabled": true
    },
    {
      "name": "Server 2 - Cliente B",
      "host": "servidor2.com",
      "port": 2222,
      "user": "usuario2",
      "path": "/home/usuario2/public_html",
      "db_name": "usuario2_db",
      "db_user": "usuario2_dbuser",
      "enabled": true
    }
  ]
}
```

---

## 📁 Estructura del Sistema de Deployment

```
├── .github/
│   └── workflows/
│       └── deploy.yml          # Workflow de GitHub Actions
├── scripts/
│   ├── deploy.sh               # Script de sincronización con rsync
│   ├── run-migrations.sh       # Script de migraciones SQL
│   └── migrations/
│       └── 001_add_prorrateo_column.sql  # Migraciones SQL
├── config/
│   └── servers.json.example    # Plantilla (NO el archivo real)
└── .gitignore                  # Excluye archivos sensibles
```

---

## 🔧 Uso del Sistema

### Deployment Automático

El deployment se ejecuta automáticamente cuando:
- Haces push a la rama `master`

### Deployment Manual

1. Ve a GitHub → **Actions** → **Deploy to cPanel Servers**
2. Click en **Run workflow**
3. Configura las opciones:
   - **Desplegar código**: `true/false`
   - **Ejecutar migraciones**: `true/false`
   - **Servidores específicos**: `[0,1,5]` (índices) o vacío para todos

### Ejecución Local de Scripts

```bash
# Deploy a un servidor específico
./scripts/deploy.sh \
  --host servidor.com \
  --port 2222 \
  --user usuario_cpanel \
  --path /home/usuario/public_html

# Ejecutar migraciones
./scripts/run-migrations.sh \
  --host servidor.com \
  --port 2222 \
  --user usuario_cpanel \
  --db-name mi_database \
  --db-user mi_dbuser \
  --db-pass "mi_password"
```

---

## 📝 Agregar Nuevas Migraciones

1. Crea un nuevo archivo en `scripts/migrations/` con formato:
   ```
   NNN_descripcion_breve.sql
   ```
   Ejemplo: `002_add_status_column.sql`

2. Escribe SQL **idempotente** (que no falle si se ejecuta múltiples veces):

```sql
-- Verificar si la columna existe antes de agregarla
DELIMITER //
DROP PROCEDURE IF EXISTS my_migration//
CREATE PROCEDURE my_migration()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mi_tabla' 
        AND COLUMN_NAME = 'nueva_columna'
    ) THEN
        ALTER TABLE mi_tabla ADD COLUMN nueva_columna VARCHAR(255);
    END IF;
END//
DELIMITER ;
CALL my_migration();
DROP PROCEDURE IF EXISTS my_migration;
```

3. Commit y push a `master`
4. Ejecuta el workflow manualmente con "Ejecutar migraciones" habilitado

---

## 🔒 Verificación de Seguridad

Antes de hacer push, verifica que no estés subiendo archivos sensibles:

```bash
# Verificar que servers.json está ignorado
git status
# NO debe aparecer config/servers.json

# Verificar archivos que se subirán
git diff --cached --name-only
# NO debe incluir ningún archivo sensible

# Probar que .gitignore funciona
echo "test" > config/servers.json
git add config/servers.json
# Debería mostrar: "The following paths are ignored by one of your .gitignore files"
rm config/servers.json
```

---

## 🖥️ Información de Servidores

Todos los servidores cPanel tienen las siguientes características:

| Componente | Versión |
|------------|---------|
| cPanel | 132.0 (build 22) |
| Apache | 2.4.66 |
| MariaDB | 10.11.15 |
| Sistema | Linux x86_64 |
| Puerto SSH | 2222 |

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, yet powerful, providing tools needed for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of any modern web application framework, making it a breeze to get started learning the framework.

If you're not in the mood to read, [Laracasts](https://laracasts.com) contains over 1100 video tutorials on a range of topics including Laravel, modern PHP, unit testing, JavaScript, and more. Boost the skill level of yourself and your entire team by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for helping fund on-going Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell):

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[British Software Development](https://www.britishsoftware.co)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- [UserInsights](https://userinsights.com)
- [Fragrantica](https://www.fragrantica.com)
- [SOFTonSOFA](https://softonsofa.com/)
- [User10](https://user10.com)
- [Soumettre.fr](https://soumettre.fr/)
- [CodeBrisk](https://codebrisk.com)
- [1Forge](https://1forge.com)
- [TECPRESSO](https://tecpresso.co.jp/)
- [Runtime Converter](http://runtimeconverter.com/)
- [WebL'Agence](https://weblagence.com/)
- [Invoice Ninja](https://www.invoiceninja.com)
- [iMi digital](https://www.imi-digital.de/)
- [Earthlink](https://www.earthlink.ro/)
- [Steadfast Collective](https://steadfastcollective.com/)

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

