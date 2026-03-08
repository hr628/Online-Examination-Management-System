#!/usr/bin/env bash
# ============================================================
# complete_fix.sh – Clean restart for the OEMS Docker stack
# ============================================================
# Usage:  bash complete_fix.sh
# ============================================================

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Bcrypt hash of the plain-text demo password "password".
# Verified: password_verify('password', DEMO_HASH) === true
DEMO_HASH='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'

info()    { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Helper: check if a TCP port is in use (tries lsof, then ss, then netstat)
port_in_use() {
  local port="$1"
  if command -v lsof &>/dev/null; then
    lsof -iTCP:"$port" -sTCP:LISTEN -t &>/dev/null 2>&1
  elif command -v ss &>/dev/null; then
    ss -tlnp 2>/dev/null | grep -q ":${port} "
  elif command -v netstat &>/dev/null; then
    netstat -tlnp 2>/dev/null | grep -q ":${port} "
  else
    return 1  # cannot determine; assume free
  fi
}

# ── 1. Port conflict check ───────────────────────────────────
info "Checking for port conflicts…"
for PORT in 8080 8081 3306; do
  if port_in_use "$PORT"; then
    warn "Port $PORT is already in use. Attempting to stop conflicting container…"
    docker ps --filter "publish=$PORT" -q | xargs -r docker stop || true
  fi
done

# ── 2. Tear down existing stack ──────────────────────────────
info "Stopping and removing existing containers + volumes…"
docker compose down -v --remove-orphans 2>/dev/null || true

# ── 3. Remove any stale images that may cache bad config ─────
info "Rebuilding web image (no cache)…"
docker compose build --no-cache web

# ── 4. Start the stack ───────────────────────────────────────
info "Starting all services…"
docker compose up -d

# ── 5. Wait for MySQL to be healthy ─────────────────────────
info "Waiting for MySQL to become healthy (up to 120 s)…"
ELAPSED=0
until docker inspect --format='{{.State.Health.Status}}' exam_system_db 2>/dev/null | grep -q "healthy"; do
  sleep 5
  ELAPSED=$((ELAPSED + 5))
  if [ "$ELAPSED" -ge 120 ]; then
    error "MySQL did not become healthy within 120 s."
    docker logs exam_system_db --tail 40
    exit 1
  fi
  info "  …still waiting (${ELAPSED}s elapsed)"
done
info "MySQL is healthy."

# ── 6. Verify database initialisation ───────────────────────
info "Verifying database schema…"
TABLE_COUNT=$(docker exec exam_system_db \
  mysql -uroot -prootpass123 oems_db \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='oems_db';" \
  --skip-column-names 2>/dev/null | tail -1 || echo "0")

if [ "$TABLE_COUNT" -lt 5 ]; then
  warn "Only $TABLE_COUNT table(s) found. Re-importing SQL files…"
  for SQL_FILE in "$SCRIPT_DIR"/sql/[0-9][0-9]_*.sql; do
    info "  Importing $(basename "$SQL_FILE")…"
    docker exec -i exam_system_db \
      mysql -uroot -prootpass123 oems_db < "$SQL_FILE"
  done
else
  info "Database OK – $TABLE_COUNT tables present."
fi

# ── 7. Reset all demo passwords to "password" ───────────────
info "Resetting all demo user passwords to 'password'…"
docker exec exam_system_db \
  mysql -uroot -prootpass123 oems_db \
  -e "UPDATE users SET password_hash='${DEMO_HASH}' WHERE id > 0;" \
  2>/dev/null && info "Passwords updated." || warn "Could not update passwords (table may not exist yet)."

# ── 8. Show running containers ───────────────────────────────
echo ""
info "Running containers:"
docker compose ps

# ── 9. Access information ────────────────────────────────────
echo ""
echo -e "${GREEN}============================================================${NC}"
echo -e "${GREEN}  OEMS Docker stack is ready!${NC}"
echo -e "${GREEN}============================================================${NC}"
echo ""
echo "  Application : http://localhost:8080"
echo "  phpMyAdmin  : http://localhost:8081"
echo ""
echo "  Application login:"
echo "    Username : admin"
echo "    Password : password"
echo ""
echo "  phpMyAdmin login:"
echo "    Username : root"
echo "    Password : rootpass123"
echo ""
echo "  DB connection (from PHP):"
echo "    Host     : db"
echo "    User     : root"
echo "    Password : rootpass123"
echo "    Database : oems_db"
echo -e "${GREEN}============================================================${NC}"
