#!/bin/bash
# ============================================================
# SCRIPT DE MIGRACIONES DE BASE DE DATOS
# ============================================================
# Este script ejecuta migraciones SQL en servidores remotos
# a través de SSH.
#
# Uso: ./run-migrations.sh --host <host> --port <port> --user <user> \
#      --db-name <db> --db-user <dbuser> --db-pass <dbpass>
# ============================================================

set -e  # Salir inmediatamente si un comando falla

# Colores para output
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
else
    RED=''
    GREEN=''
    YELLOW=''
    BLUE=''
    CYAN=''
    NC=''
fi

# Funciones de logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_migration() {
    echo -e "${CYAN}[MIGRATION]${NC} $1"
}

# Función para mostrar uso
show_usage() {
    echo "Uso: $0 --host <host> --port <port> --user <user> --db-name <db> --db-user <dbuser> --db-pass <dbpass>"
    echo ""
    echo "Parámetros:"
    echo "  --host      Hostname o IP del servidor"
    echo "  --port      Puerto SSH (típicamente 2222 para cPanel)"
    echo "  --user      Usuario SSH/cPanel"
    echo "  --db-name   Nombre de la base de datos"
    echo "  --db-user   Usuario MySQL"
    echo "  --db-pass   Contraseña MySQL"
    echo ""
}

# Parsear argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        --host)
            HOST="$2"
            shift 2
            ;;
        --port)
            PORT="$2"
            shift 2
            ;;
        --user)
            USER="$2"
            shift 2
            ;;
        --db-name)
            DB_NAME="$2"
            shift 2
            ;;
        --db-user)
            DB_USER="$2"
            shift 2
            ;;
        --db-pass)
            DB_PASS="$2"
            shift 2
            ;;
        --help)
            show_usage
            exit 0
            ;;
        *)
            log_error "Argumento desconocido: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Validar parámetros requeridos
if [ -z "$HOST" ] || [ -z "$PORT" ] || [ -z "$USER" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    log_error "Faltan parámetros requeridos"
    show_usage
    exit 1
fi

# Directorio de migraciones
MIGRATIONS_DIR="scripts/migrations"

# Verificar que existe el directorio de migraciones
if [ ! -d "$MIGRATIONS_DIR" ]; then
    log_error "El directorio de migraciones no existe: $MIGRATIONS_DIR"
    exit 1
fi

# Contar migraciones disponibles
MIGRATION_FILES=($(find "$MIGRATIONS_DIR" -name "*.sql" -type f | sort))
TOTAL_MIGRATIONS=${#MIGRATION_FILES[@]}

if [ $TOTAL_MIGRATIONS -eq 0 ]; then
    log_warning "No se encontraron archivos de migración en: $MIGRATIONS_DIR"
    exit 0
fi

# Mostrar información (sin datos sensibles)
log_info "Iniciando migraciones de base de datos..."
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_info "Servidor: $HOST:$PORT"
log_info "Base de datos: $DB_NAME"
log_info "Migraciones encontradas: $TOTAL_MIGRATIONS"
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Probar conexión SSH
log_info "Verificando conexión SSH..."
if ! ssh -o ConnectTimeout=10 -o BatchMode=yes -p "$PORT" "$USER@$HOST" "echo 'OK'" > /dev/null 2>&1; then
    log_error "No se pudo establecer conexión SSH con $HOST:$PORT"
    exit 1
fi
log_success "Conexión SSH establecida"

# Probar conexión a MySQL
log_info "Verificando conexión a MySQL..."
if ! ssh -p "$PORT" "$USER@$HOST" "mysql -u'$DB_USER' -p'$DB_PASS' -e 'SELECT 1' '$DB_NAME'" > /dev/null 2>&1; then
    log_error "No se pudo conectar a MySQL con las credenciales proporcionadas"
    exit 1
fi
log_success "Conexión a MySQL establecida"

# Crear tabla de control de migraciones si no existe
log_info "Verificando tabla de control de migraciones..."
ssh -p "$PORT" "$USER@$HOST" "mysql -u'$DB_USER' -p'$DB_PASS' '$DB_NAME'" << 'EOF'
CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checksum VARCHAR(64) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
EOF
log_success "Tabla de control verificada"

# Ejecutar cada migración
EXECUTED=0
SKIPPED=0
FAILED=0

for MIGRATION_FILE in "${MIGRATION_FILES[@]}"; do
    MIGRATION_NAME=$(basename "$MIGRATION_FILE")
    
    log_migration "Procesando: $MIGRATION_NAME"
    
    # Verificar si ya fue ejecutada
    ALREADY_EXECUTED=$(ssh -p "$PORT" "$USER@$HOST" "mysql -u'$DB_USER' -p'$DB_PASS' -N -e \"SELECT COUNT(*) FROM schema_migrations WHERE migration = '$MIGRATION_NAME'\" '$DB_NAME'" 2>/dev/null)
    
    if [ "$ALREADY_EXECUTED" == "1" ]; then
        log_warning "  ⏭️  Ya ejecutada, omitiendo..."
        ((SKIPPED++))
        continue
    fi
    
    # Leer contenido de la migración
    MIGRATION_CONTENT=$(cat "$MIGRATION_FILE")
    
    # Calcular checksum para verificación
    CHECKSUM=$(echo "$MIGRATION_CONTENT" | sha256sum | cut -d' ' -f1)
    
    # Ejecutar migración
    log_info "  ▶️  Ejecutando migración..."
    
    if echo "$MIGRATION_CONTENT" | ssh -p "$PORT" "$USER@$HOST" "mysql -u'$DB_USER' -p'$DB_PASS' '$DB_NAME'" 2>&1; then
        # Registrar migración exitosa
        ssh -p "$PORT" "$USER@$HOST" "mysql -u'$DB_USER' -p'$DB_PASS' '$DB_NAME' -e \"INSERT INTO schema_migrations (migration, checksum) VALUES ('$MIGRATION_NAME', '$CHECKSUM')\""
        log_success "  ✅ Migración ejecutada exitosamente"
        ((EXECUTED++))
    else
        log_error "  ❌ Error al ejecutar la migración"
        ((FAILED++))
        # Continuar con las demás migraciones (fail-fast: false)
    fi
done

# Resumen final
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_info "RESUMEN DE MIGRACIONES"
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_info "Total procesadas: $TOTAL_MIGRATIONS"
log_success "Ejecutadas: $EXECUTED"
log_warning "Omitidas (ya ejecutadas): $SKIPPED"

if [ $FAILED -gt 0 ]; then
    log_error "Fallidas: $FAILED"
    log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 1
else
    log_success "TODAS LAS MIGRACIONES COMPLETADAS"
    log_info "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
    log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    exit 0
fi
