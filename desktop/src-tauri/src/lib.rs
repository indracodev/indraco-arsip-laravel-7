use std::net::{TcpStream, ToSocketAddrs};
use std::time::Duration;
use tauri::Manager;

fn is_server_alive(host: &str, port: u16) -> bool {
    let addr_str = format!("{}:{}", host, port);
    if let Ok(mut addrs) = addr_str.to_socket_addrs() {
        if let Some(addr) = addrs.next() {
            return TcpStream::connect_timeout(&addr, Duration::from_millis(1500)).is_ok();
        }
    }
    false
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .setup(|app| {
            let window = app.get_webview_window("main").unwrap();
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
                    std::thread::sleep(Duration::from_millis(2000));
                    if let Ok(current_url) = win_clone.url() {
                        let host = current_url.host_str().unwrap_or("");
                        let scheme = current_url.scheme();

                        // Only monitor when connected to external DMS server (not local tauri shell)
                        if (scheme == "http" || scheme == "https") && host != "tauri.localhost" && !host.is_empty() {
                            let port = current_url.port_or_known_default().unwrap_or(80);
                            if !is_server_alive(host, port) {
                                consecutive_failures += 1;
                                if consecutive_failures >= 2 {
                                    // Server is unreachable! Return to the initial reconnect page
                                    consecutive_failures = 0;
                                    let reconnect_url = tauri::Url::parse("http://tauri.localhost/index.html?offline=1")
                                        .unwrap_or_else(|_| tauri::Url::parse("http://tauri.localhost").unwrap());
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
