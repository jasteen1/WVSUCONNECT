#!/usr/bin/env bash
# Sync this repo into XAMPP htdocs (no --delete: keeps htdocs-only files like db_config.local.php).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
DEST="${WVSU_HTDOCS:-/Applications/XAMPP/xamppfiles/htdocs/WVSUCONNECT}"
mkdir -p "$DEST"
rsync -av \
  --exclude '.git/' \
  --exclude '.githooks/' \
  --exclude '.cursor/' \
  --exclude '*.code-workspace' \
  --exclude 'sync-to-htdocs.sh' \
  "$ROOT/" "$DEST/"
echo "Synced: $ROOT  →  $DEST"
