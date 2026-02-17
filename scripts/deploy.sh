#!/bin/bash
# ============================================================
# SCRIPT DE DEPLOYMENT PARA SERVIDORES CPANEL
# ============================================================
# Este script sincroniza archivos del repositorio a servidores
# cPanel usando rsync sobre SSH.
#
# Uso: ./deploy.sh --host <host> --port <port> --user <user> --path <path>
# ============================================================

set -e  # Salir inmediatamente si un comando falla

# Colores para output (solo si el terminal lo soporta)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    NC='\033[0m' # Sin color
else
    RED=''
    GREEN=''
    YELLOW=''
    BLUE=''
    NC=''
fi

# Función para mostrar mensajes
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

# Función para mostrar uso
show_usage() {
    echo "Uso: $0 --host <host> --port <port> --user <user> --path <path>"
    echo ""
    echo "Parámetros:"
    echo "  --host    Hostname o IP del servidor"
    echo "  --port    Puerto SSH (típicamente 2222 para cPanel)"
    echo "  --user    Usuario SSH/cPanel"
    echo "  --path    Ruta remota de deployment (ej: /home/user/public_html)"
    echo ""
    echo "Ejemplo:"
    echo "  $0 --host servidor.com --port 2222 --user usuario --path /home/usuario/public_html"
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
        --path)
            REMOTE_PATH="$2"
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
if [ -z "$HOST" ] || [ -z "$PORT" ] || [ -z "$USER" ] || [ -z "$REMOTE_PATH" ]; then
    log_error "Faltan parámetros requeridos"
    show_usage
    exit 1
fi

# Mostrar información del deployment (sin datos sensibles)
log_info "Iniciando deployment..."
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_info "Destino: $HOST:$PORT"
log_info "Ruta remota: $REMOTE_PATH"
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Archivos y directorios a EXCLUIR de la sincronización
EXCLUDES=(
    ".git"
    ".git/"
    ".gitignore"
    ".gitattributes"
    ".github/"
    "node_modules/"
    "vendor/"
    ".env"
    ".env.*"
    "config/servers.json"
    ".DS_Store"
    "Thumbs.db"
    "*.log"
    "storage/logs/*"
    "storage/framework/cache/*"
    "storage/framework/sessions/*"
    "storage/framework/views/*"
    "bootstrap/cache/*"
    ".phpunit.result.cache"
    "phpunit.xml"
    "tests/"
    "*.pem"
    "*.key"
    "id_rsa*"
    "composer.phar"
    "composer.lock"
    "package-lock.json"
    "webpack.mix.js"
    "mintty.exe.stackdump"
    "README.md"
    ".editorconfig"
    "scripts/"
)

# Construir argumentos de exclusión para rsync
RSYNC_EXCLUDES=""
for exclude in "${EXCLUDES[@]}"; do
    RSYNC_EXCLUDES="$RSYNC_EXCLUDES --exclude='$exclude'"
done

# Probar conexión SSH antes de sincronizar
log_info "Verificando conexión SSH..."
if ! ssh -o ConnectTimeout=10 -o BatchMode=yes -p "$PORT" "$USER@$HOST" "echo 'Conexión OK'" > /dev/null 2>&1; then
    log_error "No se pudo establecer conexión SSH con $HOST:$PORT"
    log_error "Verifique que la clave SSH está configurada correctamente"
    exit 1
fi
log_success "Conexión SSH establecida"

# Verificar que la ruta remota existe
log_info "Verificando ruta remota..."
if ! ssh -p "$PORT" "$USER@$HOST" "[ -d '$REMOTE_PATH' ]"; then
    log_error "La ruta remota no existe: $REMOTE_PATH"
    exit 1
fi
log_success "Ruta remota verificada"

# Ejecutar rsync
log_info "Sincronizando archivos..."

# Construir comando rsync
RSYNC_CMD="rsync -avz --delete --progress \
    -e 'ssh -p $PORT -o StrictHostKeyChecking=no' \
    $RSYNC_EXCLUDES \
    ./ $USER@$HOST:$REMOTE_PATH/"

# Ejecutar rsync (usando eval para expandir las comillas correctamente)
if eval $RSYNC_CMD; then
    log_success "Archivos sincronizados correctamente"
else
    log_error "Error durante la sincronización"
    exit 1
fi

# Establecer permisos correctos en archivos PHP (opcional pero recomendado)
log_info "Ajustando permisos de archivos..."
ssh -p "$PORT" "$USER@$HOST" "
    cd '$REMOTE_PATH' && \
    find . -type f -name '*.php' -exec chmod 644 {} \; 2>/dev/null || true && \
    find . -type d -exec chmod 755 {} \; 2>/dev/null || true
" || log_warning "No se pudieron ajustar todos los permisos (algunos archivos pueden requerir permisos específicos)"

# Limpiar caché de Laravel si existe artisan
log_info "Limpiando caché de Laravel..."
ssh -p "$PORT" "$USER@$HOST" "
    cd '$REMOTE_PATH' && \
    if [ -f artisan ]; then
        php artisan cache:clear 2>/dev/null || true
        php artisan config:clear 2>/dev/null || true
        php artisan view:clear 2>/dev/null || true
        php artisan route:clear 2>/dev/null || true
        echo 'Caché de Laravel limpiado'
    fi
" || log_warning "No se pudo limpiar la caché de Laravel"

# Resumen final
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_success "DEPLOYMENT COMPLETADO EXITOSAMENTE"
log_info "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
log_info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit 0
