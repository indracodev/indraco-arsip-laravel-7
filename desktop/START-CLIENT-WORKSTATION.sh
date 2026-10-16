#!/usr/bin/env bash
# ==============================================================================
# PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
# Client Workstation Launcher for Linux (Ubuntu, Debian, Fedora, Arch, Mint)
# Launches the Custom Matte Light Workstation UI in Frameless Standalone App Mode
# ==============================================================================

set -e

# Pindah ke direktori script ini berada
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$DIR"

UI_TARGET="file://$DIR/desktop/src/index.html"
ICON_PATH="$DIR/public/images/icon-indraco-white-bg.png"
if [ ! -f "$ICON_PATH" ]; then
    ICON_PATH="$DIR/desktop/src-tauri/icons/128x128.png"
fi

# Otomatis buat / perbarui shortcut desktop Linux jika belum ada
DESKTOP_DIR="${XDG_DESKTOP_DIR:-$HOME/Desktop}"
if [ -d "$DESKTOP_DIR" ] && [ ! -f "$DESKTOP_DIR/dms-indraco.desktop" ]; then
    cat <<EOF > "$DESKTOP_DIR/dms-indraco.desktop"
[Desktop Entry]
Version=1.0
Type=Application
Name=DMS PT Indraco
Comment=Sistem Manajemen Dokumen & Arsip Digital
Exec=$DIR/START-CLIENT-WORKSTATION.sh
Icon=$ICON_PATH
Terminal=false
Categories=Office;Database;
StartupWMClass=google-chrome
EOF
    chmod +x "$DESKTOP_DIR/dms-indraco.desktop" 2>/dev/null || true
    mkdir -p "$HOME/.local/share/applications" 2>/dev/null || true
    cp "$DESKTOP_DIR/dms-indraco.desktop" "$HOME/.local/share/applications/" 2>/dev/null || true
fi

# Buka UI Shell Klien dalam App Mode (Frameless / Tanpa Address Bar)
for BROWSER in google-chrome google-chrome-stable chromium chromium-browser microsoft-edge-stable microsoft-edge brave-browser; do
    if command -v "$BROWSER" >/dev/null 2>&1; then
        "$BROWSER" --app="$UI_TARGET" --start-maximized >/dev/null 2>&1 &
        exit 0
    fi
done

# Fallback ke Firefox
if command -v firefox >/dev/null 2>&1; then
    firefox --new-window "$UI_TARGET" >/dev/null 2>&1 &
    exit 0
fi

# Fallback ke default xdg-open
if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$UI_TARGET" >/dev/null 2>&1 &
    exit 0
fi

exit 1
