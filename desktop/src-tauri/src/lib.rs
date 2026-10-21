use std::net::{TcpStream, ToSocketAddrs};
use std::time::Duration;
use tauri::Manager;
use tauri::menu::{Menu, MenuItem};
use tauri_plugin_updater::UpdaterExt;

fn is_server_alive(host: &str, port: u16) -> bool {
    let addr_str = format!("{}:{}", host, port);
    if let Ok(mut addrs) = addr_str.to_socket_addrs() {
        if let Some(addr) = addrs.next() {
            return TcpStream::connect_timeout(&addr, Duration::from_millis(3500)).is_ok();
        }
    }
    false
}

#[cfg(target_os = "windows")]
fn confirm_exit() -> bool {
    use std::ffi::OsStr;
    use std::os::windows::ffi::OsStrExt;

    #[link(name = "user32")]
    extern "system" {
        fn MessageBoxW(
            hwnd: *mut std::ffi::c_void,
            lpText: *const u16,
            lpCaption: *const u16,
            uType: u32,
        ) -> i32;
    }

    let text: Vec<u16> = OsStr::new(
        "Apakah Anda yakin ingin menutup aplikasi DMS PT Indraco?\n\nPastikan data formulir yang sedang Anda isi sudah disimpan agar tidak hilang.",
    )
    .encode_wide()
    .chain(Some(0))
    .collect();

    let caption: Vec<u16> = OsStr::new("Konfirmasi Keluar - DMS PT INDRACO")
        .encode_wide()
        .chain(Some(0))
        .collect();

    // MB_YESNO (0x04) | MB_ICONQUESTION (0x20) | MB_DEFBUTTON2 (0x100) -> Default is NO to prevent accidental close
    const MB_YESNO: u32 = 0x00000004;
    const MB_ICONQUESTION: u32 = 0x00000020;
    const MB_DEFBUTTON2: u32 = 0x00000100;
    const IDYES: i32 = 6;

    let result = unsafe {
        MessageBoxW(
            std::ptr::null_mut(),
            text.as_ptr(),
            caption.as_ptr(),
            MB_YESNO | MB_ICONQUESTION | MB_DEFBUTTON2,
        )
    };

    result == IDYES
}

#[tauri::command]
fn get_app_version() -> String {
    env!("CARGO_PKG_VERSION").to_string()
}

#[cfg(target_os = "windows")]
fn execute_fresh_reset(app: &tauri::AppHandle) {
    use std::process::Command;
    use std::os::windows::process::CommandExt;

    if let Ok(current_exe) = std::env::current_exe() {
        let exe_path = current_exe.to_string_lossy().to_string();
        let local_app_data = std::env::var("LOCALAPPDATA").unwrap_or_default();
        let app_data = std::env::var("APPDATA").unwrap_or_default();
        let temp_dir = std::env::var("TEMP").unwrap_or_default();

        let local_target = format!("{}\\com.indraco.dms.client", local_app_data);
        let roaming_target = format!("{}\\com.indraco.dms.client", app_data);
        let temp_target = format!("{}\\com.indraco.dms.client", temp_dir);

        // Bulletproof Windows reset via PowerShell detached process:
        // 1. Wait for parent process to exit
        // 2. Kill any lingering webview processes locking the profile
        // 3. Completely delete %LOCALAPPDATA%\com.indraco.dms.client
        // 4. Relaunch fresh exe
        let ps_script = format!(
            "Start-Sleep -Seconds 2; \
             Get-Process -Name '*webview*', '*DMS-Indraco*' -ErrorAction SilentlyContinue | Where-Object {{ $_.Path -like '*com.indraco.dms.client*' -or $_.Path -like '*EdgeWebView*' -or $_.Name -like '*DMS-Indraco*' }} | Stop-Process -Force -ErrorAction SilentlyContinue; \
             Start-Sleep -Milliseconds 600; \
             if (Test-Path -LiteralPath '{local}') {{ Remove-Item -LiteralPath '{local}' -Recurse -Force -ErrorAction SilentlyContinue }}; \
             if (Test-Path -LiteralPath '{roam}') {{ Remove-Item -LiteralPath '{roam}' -Recurse -Force -ErrorAction SilentlyContinue }}; \
             if (Test-Path -LiteralPath '{temp}') {{ Remove-Item -LiteralPath '{temp}' -Recurse -Force -ErrorAction SilentlyContinue }}; \
             Start-Sleep -Milliseconds 400; \
             Start-Process -FilePath '{exe}'",
            local = local_target.replace('\'', "''"),
            roam = roaming_target.replace('\'', "''"),
            temp = temp_target.replace('\'', "''"),
            exe = exe_path.replace('\'', "''"),
        );

        const CREATE_NO_WINDOW: u32 = 0x08000000;
        let _ = Command::new("powershell")
            .args(["-NoProfile", "-ExecutionPolicy", "Bypass", "-Command", &ps_script])
            .creation_flags(CREATE_NO_WINDOW)
            .spawn();

        app.exit(0);
    }
}

#[tauri::command]
fn reset_app_cache_and_restart(app: tauri::AppHandle) -> Result<String, String> {
    #[cfg(target_os = "windows")]
    {
        execute_fresh_reset(&app);
        Ok("Aplikasi dibersihkan seperti baru di-install dan mulai ulang...".into())
    }
    #[cfg(not(target_os = "windows"))]
    {
        app.exit(0);
        Ok("Pembersihan selesai.".into())
    }
}

#[cfg(target_os = "windows")]
fn confirm_and_reset_cache(app: &tauri::AppHandle) {
    use std::ffi::OsStr;
    use std::os::windows::ffi::OsStrExt;

    #[link(name = "user32")]
    extern "system" {
        fn MessageBoxW(
            hwnd: *mut std::ffi::c_void,
            lpText: *const u16,
            lpCaption: *const u16,
            uType: u32,
        ) -> i32;
    }

    let text: Vec<u16> = OsStr::new(
        "Reset Total Aplikasi DMS PT Indraco (Fresh Installation)?\n\nSeluruh data cache, cookies, local storage, dan sesi kerja WebView2 akan dihapus secara permanen sehingga aplikasi kembali bersih seperti baru pertama kali di-install.\n\nAplikasi akan tertutup, menghapus seluruh data lokal, dan membuka kembali secara otomatis.\n\nLanjutkan proses reset?",
    )
    .encode_wide()
    .chain(Some(0))
    .collect();

    let caption: Vec<u16> = OsStr::new("Reset App (Fresh Install) - DMS PT INDRACO")
        .encode_wide()
        .chain(Some(0))
        .collect();

    const MB_YESNO: u32 = 0x00000004;
    const MB_ICONWARNING: u32 = 0x00000030;
    const MB_DEFBUTTON2: u32 = 0x00000100;
    const IDYES: i32 = 6;

    let result = unsafe {
        MessageBoxW(
            std::ptr::null_mut(),
            text.as_ptr(),
            caption.as_ptr(),
            MB_YESNO | MB_ICONWARNING | MB_DEFBUTTON2,
        )
    };

    if result == IDYES {
        execute_fresh_reset(app);
    }
}

#[cfg(target_os = "windows")]
fn check_and_apply_update(app: tauri::AppHandle) {
    use std::ffi::OsStr;
    use std::os::windows::ffi::OsStrExt;

    #[link(name = "user32")]
    extern "system" {
        fn MessageBoxW(
            hwnd: *mut std::ffi::c_void,
            lpText: *const u16,
            lpCaption: *const u16,
            uType: u32,
        ) -> i32;
    }

    fn show_msg(title: &str, message: &str, icon_flag: u32) {
        let text: Vec<u16> = OsStr::new(message).encode_wide().chain(Some(0)).collect();
        let caption: Vec<u16> = OsStr::new(title).encode_wide().chain(Some(0)).collect();
        unsafe {
            MessageBoxW(std::ptr::null_mut(), text.as_ptr(), caption.as_ptr(), icon_flag);
        }
    }

    tauri::async_runtime::spawn(async move {
        match app.updater() {
            Ok(updater) => {
                match updater.check().await {
                    Ok(Some(update)) => {
                        let new_ver = update.version.clone();
                        let prompt = format!(
                            "Versi baru (v{}) telah tersedia!\n\nAplikasi akan langsung mengunduh dan memasang pembaruan otomatis sekarang.\n\nLanjutkan pembaruan?",
                            new_ver
                        );
                        let text: Vec<u16> = OsStr::new(&prompt).encode_wide().chain(Some(0)).collect();
                        let caption: Vec<u16> = OsStr::new("Pembaruan Tersedia - DMS PT INDRACO").encode_wide().chain(Some(0)).collect();
                        const MB_YESNO: u32 = 0x00000004;
                        const MB_ICONINFORMATION: u32 = 0x00000040;
                        const IDYES: i32 = 6;
                        let res = unsafe {
                            MessageBoxW(std::ptr::null_mut(), text.as_ptr(), caption.as_ptr(), MB_YESNO | MB_ICONINFORMATION)
                        };

                        if res == IDYES {
                            match update.download_and_install(|_downloaded, _total| {}, || {}).await {
                                Ok(_) => {
                                    show_msg(
                                        "Pembaruan Selesai",
                                        "Pembaruan berhasil dipasang! Aplikasi akan dimulai ulang sekarang.",
                                        0x00000040,
                                    );
                                    app.restart();
                                }
                                Err(e) => {
                                    let err_msg = format!("Gagal memasang pembaruan otomatis: {}\n\nPastikan izin sistem mengizinkan instalasi.", e);
                                    show_msg("Gagal Memperbarui", &err_msg, 0x00000010);
                                }
                            }
                        }
                    }
                    Ok(None) => {
                        show_msg(
                            "Aplikasi Sudah Terbaru",
                            "Aplikasi DMS PT Indraco sudah pada versi terbaru (v1.0.0).\nTidak ada pembaruan yang diperlukan.",
                            0x00000040,
                        );
                    }
                    Err(e) => {
                        let err_text = format!(
                            "Tidak ada pembaruan baru atau server pembaruan belum dapat dijangkau:\n{}\n\n(Aplikasi tetap dapat digunakan secara normal)",
                            e
                        );
                        show_msg("Pemeriksaan Pembaruan", &err_text, 0x00000030);
                    }
                }
            }
            Err(e) => {
                let err_text = format!("Layanan pembaruan belum siap: {}", e);
                show_msg("Error", &err_text, 0x00000010);
            }
        }
    });
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .invoke_handler(tauri::generate_handler![
            get_app_version,
            reset_app_cache_and_restart
        ])
        .on_menu_event(|app, event| {
            if event.id() == "reset_cache" {
                #[cfg(target_os = "windows")]
                confirm_and_reset_cache(app);
            } else if event.id() == "check_update" {
                #[cfg(target_os = "windows")]
                check_and_apply_update(app.clone());
            }
        })
        .on_window_event(|_window, event| {
            if let tauri::WindowEvent::CloseRequested { api, .. } = event {
                #[cfg(target_os = "windows")]
                {
                    if !confirm_exit() {
                        api.prevent_close();
                    }
                }
            }
        })
        .setup(|app| {
            let window = app.get_webview_window("main").unwrap();

            // Setup native window title bar menu (Update & Reset side-by-side)
            let update_item = MenuItem::with_id(app, "check_update", "🔄 Periksa Update", true, None::<&str>)?;
            let reset_item = MenuItem::with_id(app, "reset_cache", "🗑️ Reset App (Fresh Install)", true, None::<&str>)?;
            let win_menu = Menu::with_items(app, &[&update_item, &reset_item])?;
            let _ = window.set_menu(win_menu);
            let _ = window.with_webview(|webview| {
                #[cfg(target_os = "windows")]
                unsafe {
                    let controller = webview.controller();
                    let core = controller.CoreWebView2().unwrap();
                    let settings = core.Settings().unwrap();
                    let _ = settings.SetIsBuiltInErrorPageEnabled(false);
                }
            });

            let win_clone = window.clone();
            std::thread::spawn(move || {
                let mut consecutive_failures = 0;
                loop {
                    std::thread::sleep(Duration::from_millis(6000));
                    if let Ok(current_url) = win_clone.url() {
                        let host = current_url.host_str().unwrap_or("");
                        let scheme = current_url.scheme();

                        // Only monitor when connected to external DMS server (not local tauri shell)
                        if (scheme == "http" || scheme == "https") && host != "tauri.localhost" && !host.is_empty() {
                            let port = current_url.port_or_known_default().unwrap_or(80);
                            if !is_server_alive(host, port) {
                                consecutive_failures += 1;
                                if consecutive_failures >= 4 {
                                    // Server is unreachable! Return to the initial reconnect page with return_path
                                    consecutive_failures = 0;
                                    let mut reconnect_str = String::from("http://tauri.localhost/index.html?offline=1");
                                    let path = current_url.path();
                                    if !path.is_empty() && path != "/" && path != "/login" && path != "/logout" {
                                        let full_path = if let Some(q) = current_url.query() {
                                            format!("{}?{}", path, q)
                                        } else {
                                            path.to_string()
                                        };
                                        reconnect_str.push_str("&return_path=");
                                        for b in full_path.bytes() {
                                            if b.is_ascii_alphanumeric() || b == b'-' || b == b'_' || b == b'.' || b == b'~' || b == b'/' {
                                                reconnect_str.push(b as char);
                                            } else {
                                                reconnect_str.push_str(&format!("%{:02X}", b));
                                            }
                                        }
                                    }
                                    let reconnect_url = tauri::Url::parse(&reconnect_str)
                                        .unwrap_or_else(|_| tauri::Url::parse("http://tauri.localhost/index.html?offline=1").unwrap());
                                    let _ = win_clone.navigate(reconnect_url);
                                }
                            } else {
                                consecutive_failures = 0;
                            }
                        } else {
                            consecutive_failures = 0;
                        }
                    }
                }
            });

            Ok(())
        })
        .plugin(tauri_plugin_updater::Builder::new().build())
        .plugin(tauri_plugin_process::init())
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}

