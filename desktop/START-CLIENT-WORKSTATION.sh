#!/usr/bin/env bash
# ==============================================================================
# PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
# Client Workstation Launcher for Linux (Ubuntu, Debian, Fedora, Arch, Mint)
# ==============================================================================

set -e

# Pindah ke direktori script ini berada
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$DIR"

# Target Server Default
SERVER_URL="http://192.168.2.180:8000"

# Cek apakah ada file server_url.txt
if [ -f "server_url.txt" ]; then
    SERVER_URL=$(tr -d '\r\n ' < "server_url.txt")
elif [ -f "desktop/server_url.txt" ]; then
    SERVER_URL=$(tr -d '\r\n ' < "desktop/server_url.txt")
fi

echo "==============================================================================="
echo "             PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)"
echo "                   Client Workstation Launcher (Linux)"
echo "==============================================================================="
echo ""
echo "[*] Target Server DMS : $SERVER_URL"
echo "[*] Memeriksa koneksi jaringan ke server..."

# Tes ping endpoint dengan curl
if curl -s --max-time 2 "$SERVER_URL/api/health/ping" >/dev/null 2>&1; then
    echo "[OK] Server DMS aktif dan siap digunakan!"
else
    echo "[PERHATIAN] Server pada $SERVER_URL belum merespons."
    echo "            Pastikan komputer terhubung ke jaringan LAN/WiFi kantor,"
    echo "            atau server utama telah dijalankan."
    echo ""
    echo "Pilihan:"
    echo "  [1] Coba hubungkan ulang (Retry)"
    echo "  [2] Ganti alamat server"
    echo "  [3] Tetap buka antarmuka"
    echo "  [4] Buat Shortcut Desktop Linux (.desktop)"
    echo "  [5] Keluar"
    read -rp "Pilihan [1/2/3/4/5]: " CHOICE
    case "$CHOICE" in
        1) exec "$0" ;;
        2)
            read -rp "Masukkan alamat server baru (contoh: http://127.0.0.1:8000): " NEW_URL
            if [ -n "$NEW_URL" ]; then
                echo "$NEW_URL" > "$DIR/server_url.txt"
                SERVER_URL="$NEW_URL"
            fi
            ;;
        4)
            DESKTOP_DIR="${XDG_DESKTOP_DIR:-$HOME/Desktop}"
            ICON_PATH="$DIR/public/images/icon-indraco-white-bg.png"
            if [ ! -f "$ICON_PATH" ]; then
                ICON_PATH="$DIR/desktop/src-tauri/icons/128x128.png"
            fi

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
            chmod +x "$DESKTOP_DIR/dms-indraco.desktop"
            mkdir -p "$HOME/.local/share/applications"
            cp "$DESKTOP_DIR/dms-indraco.desktop" "$HOME/.local/share/applications/"
            echo "[OK] Shortcut berhasil dibuat di Desktop dan Menu Aplikasi Linux!"
            echo ""
            read -rp "Tekan Enter untuk melanjutkan..."
            ;;
        5) exit 0 ;;
    esac
fi

echo ""
echo "[*] Membuka antarmuka DMS Indraco dalam Mode Standalone Desktop..."

# Deteksi browser Chromium/Chrome/Edge untuk Standalone App Mode (tanpa address bar)
for BROWSER in google-chrome google-chrome-stable chromium chromium-browser microsoft-edge-stable microsoft-edge brave-browser; do
    if command -v "$BROWSER" >/dev/null 2>&1; then
        echo "[OK] Menggunakan Engine: $BROWSER"
        "$BROWSER" --app="$SERVER_URL" --start-maximized >/dev/null 2>&1 &
        exit 0
    fi
done

# Fallback ke Firefox
if command -v firefox >/dev/null 2>&1; then
    echo "[OK] Menggunakan Engine: firefox"
    firefox --new-window "$SERVER_URL" >/dev/null 2>&1 &
    exit 0
fi

# Fallback ke default xdg-open
if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$SERVER_URL" >/dev/null 2>&1 &
    exit 0
fi

echo "[ERROR] Tidak ditemukan web browser yang didukung di sistem Linux ini."
exit 1
