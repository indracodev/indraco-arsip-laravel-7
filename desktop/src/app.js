/**
 * DMS PT INDRACO - Workstation Controller
 * Clean, enterprise-grade connection manager without raw IP exposure.
 */

const DEFAULT_SERVER_URL = "http://192.168.2.180:8000";
const STORAGE_KEY = "indraco_dms_server_url";
const STORAGE_LABEL_KEY = "indraco_dms_server_label";
const CURRENT_VERSION = "1.0.0";
const GITHUB_REPO = "indracodev/indraco-arsip-laravel-7";

// State references
let currentServerUrl = localStorage.getItem(STORAGE_KEY) || DEFAULT_SERVER_URL;
let retryTimer = null;
let countdownSec = 5;

// DOM Elements
const stateConnecting = document.getElementById("stateConnecting");
const stateOffline = document.getElementById("stateOffline");
const connectingText = document.getElementById("connectingText");
const activeRouteBadge = document.getElementById("activeRouteBadge");
const offlineRouteBadge = document.getElementById("offlineRouteBadge");
const countdownSecSpan = document.getElementById("countdownSec");
const btnRetryNow = document.getElementById("btnRetryNow");
const btnOpenSettings = document.getElementById("btnOpenSettings");
const btnCloseModal = document.getElementById("btnCloseModal");
const settingsModal = document.getElementById("settingsModal");
const inputServerUrl = document.getElementById("inputServerUrl");
const btnSaveSettings = document.getElementById("btnSaveSettings");
const btnTestPing = document.getElementById("btnTestPing");
const pingTestResult = document.getElementById("pingTestResult");
const btnCheckUpdate = document.getElementById("btnCheckUpdate");
const updateStatusText = document.getElementById("updateStatusText");

/**
 * Get human-friendly label for server endpoint
 */
function getRouteLabel(url) {
  const savedLabel = localStorage.getItem(STORAGE_LABEL_KEY);
  if (savedLabel && url === currentServerUrl) return savedLabel;

  if (url.includes("192.168.2.180")) return "Server Pusat Kantor";
  if (url.includes("192.168.3.199")) return "Stasiun Kerja Ini";
  if (url.includes("127.0.0.1") || url.includes("localhost")) return "Pengujian Mandiri (Lokal)";
  return "Jalur Server Terdaftar";
}

// Initialize UI
function init() {
  updateBadges();
  inputServerUrl.value = currentServerUrl;

  // Bind Events
  btnRetryNow.addEventListener("click", () => {
    stopRetryCountdown();
    connectToServer();
  });

  btnOpenSettings.addEventListener("click", openSettingsModal);
  btnCloseModal.addEventListener("click", closeSettingsModal);
  btnSaveSettings.addEventListener("click", saveSettings);
  btnTestPing.addEventListener("click", runPingTest);
  btnCheckUpdate.addEventListener("click", checkGitHubRelease);

  // Quick preset buttons
  document.querySelectorAll(".btn-preset").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".btn-preset").forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      inputServerUrl.value = btn.getAttribute("data-url");
      runPingTest();
    });
  });

  // Start initial connection check or immediately enter disconnected reconnect state
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get("offline") === "1") {
    showOfflineState(true);
  } else {
    connectToServer();
  }
}

function updateBadges() {
  const label = getRouteLabel(currentServerUrl);
  activeRouteBadge.textContent = "Jalur: " + label;
  offlineRouteBadge.textContent = "Jalur: " + label;
}

/**
 * Ping the DMS Server Health Endpoint
 */
async function pingServer(targetUrl, timeoutMs = 3500) {
  const cleanUrl = targetUrl.replace(/\/+$/, "");
  const pingEndpoint = `${cleanUrl}/api/health/ping`;
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

  const startTime = performance.now();
  try {
    const response = await fetch(pingEndpoint, {
      method: "GET",
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
      signal: controller.signal,
    });
    clearTimeout(timeoutId);

    const latency = Math.round(performance.now() - startTime);
    if (response.ok) {
      const data = await response.json();
      return { success: true, latency, data };
    }
    return { success: false, error: `Layanan tidak merespons (Status: ${response.status})` };
  } catch (err) {
    clearTimeout(timeoutId);
    return { success: false, error: "Jalur belum terhubung / offline" };
  }
}

/**
 * Attempt to connect to the server
 */
async function connectToServer() {
  showConnectingState();

  const result = await pingServer(currentServerUrl);
  if (result.success) {
    connectingText.textContent = `Terhubung! Membuka antarmuka...`;
    activeRouteBadge.style.borderColor = "var(--emerald-subtle-border)";
    activeRouteBadge.style.color = "var(--emerald-text)";
    activeRouteBadge.style.backgroundColor = "var(--emerald-subtle-bg)";

    // Save local shell URL
    sessionStorage.setItem("indraco_local_shell", window.location.href);

    // Short pause for smooth visual transition
    setTimeout(() => {
      try {
        const dest = new URL(currentServerUrl);
        dest.searchParams.set("dms_client", "desktop");
        window.location.replace(dest.toString());
      } catch (e) {
        window.location.replace(currentServerUrl);
      }
    }, 350);
  } else {
    showOfflineState(false);
  }
}

function showConnectingState() {
  stateOffline.classList.remove("active");
  stateConnecting.classList.add("active");
  connectingText.textContent = "Menyambungkan ke Pusat Arsip...";
  updateBadges();
  activeRouteBadge.style.borderColor = "";
  activeRouteBadge.style.color = "";
  activeRouteBadge.style.backgroundColor = "";
}

function showOfflineState(isDisconnected = false) {
  stateConnecting.classList.remove("active");
  stateOffline.classList.add("active");
  updateBadges();

  const reasonEl = document.getElementById("offlineErrorReason");
  const label = getRouteLabel(currentServerUrl);
  if (isDisconnected) {
    reasonEl.textContent = `Koneksi ke ${label} terputus (server mati atau kabel jaringan terlepas). Sistem sedang mencoba menghubungkan kembali secara berkala...`;
  } else {
    reasonEl.textContent = `Sistem belum mendeteksi layanan arsip aktif pada ${label}. Pastikan komputer terhubung ke jaringan kantor atau server lokal workstation sudah berjalan.`;
  }

  startRetryCountdown();
}

function startRetryCountdown() {
  stopRetryCountdown();
  countdownSec = 5;
  countdownSecSpan.textContent = countdownSec;

  retryTimer = setInterval(() => {
    countdownSec--;
    countdownSecSpan.textContent = countdownSec;
    if (countdownSec <= 0) {
      stopRetryCountdown();
      connectToServer();
    }
  }, 1000);
}

function stopRetryCountdown() {
  if (retryTimer) {
    clearInterval(retryTimer);
    retryTimer = null;
  }
}

/**
 * Settings Modal
 */
function openSettingsModal() {
  stopRetryCountdown();
  inputServerUrl.value = currentServerUrl;
  pingTestResult.textContent = "";
  pingTestResult.className = "ping-result";

  // Mark active preset button
  document.querySelectorAll(".btn-preset").forEach((btn) => {
    if (btn.getAttribute("data-url") === currentServerUrl) {
      btn.classList.add("active");
    } else {
      btn.classList.remove("active");
    }
  });

  settingsModal.classList.add("show");
}

function closeSettingsModal() {
  settingsModal.classList.remove("show");
  if (stateOffline.classList.contains("active")) {
    startRetryCountdown();
  }
}

async function runPingTest() {
  const url = inputServerUrl.value.trim();
  if (!url) return;

  pingTestResult.textContent = "Menguji jalur sambungan...";
  pingTestResult.className = "ping-result";

  const result = await pingServer(url, 3000);
  if (result.success) {
    pingTestResult.textContent = `✓ Jalur aktif & siap digunakan! (${result.latency}ms)`;
    pingTestResult.className = "ping-result ping-ok";
  } else {
    pingTestResult.textContent = `✗ Jalur tidak merespons (server belum aktif)`;
    pingTestResult.className = "ping-result ping-fail";
  }
}

function saveSettings() {
  let url = inputServerUrl.value.trim();
  if (!url) return;

  if (!/^https?:\/\//i.test(url)) {
    url = "http://" + url;
  }

  // Ensure port 8000 is used if omitted
  try {
    const parsed = new URL(url);
    if (!parsed.port && parsed.hostname !== 'localhost' && !url.includes(':80')) {
      parsed.port = "8000";
      url = parsed.origin;
    }
  } catch (e) {}

  currentServerUrl = url;
  localStorage.setItem(STORAGE_KEY, currentServerUrl);

  // Check if a preset matches
  let matchedLabel = "";
  document.querySelectorAll(".btn-preset").forEach((btn) => {
    if (btn.getAttribute("data-url") === currentServerUrl) {
      matchedLabel = btn.getAttribute("data-label");
    }
  });
  if (matchedLabel) {
    localStorage.setItem(STORAGE_LABEL_KEY, matchedLabel);
  } else {
    localStorage.removeItem(STORAGE_LABEL_KEY);
  }

  closeSettingsModal();
  connectToServer();
}

/**
 * GitHub Release Update Checker
 */
async function checkGitHubRelease() {
  updateStatusText.textContent = "Memeriksa versi rilis terbaru...";
  try {
    const res = await fetch(`https://api.github.com/repos/${GITHUB_REPO}/releases/latest`, {
      headers: { Accept: "application/vnd.github.v3+json" },
    });

    if (res.status === 404) {
      updateStatusText.textContent = "Versi aplikasi Anda sudah yang terbaru.";
      return;
    }

    if (!res.ok) {
      updateStatusText.textContent = "Tidak dapat memeriksa pembaruan saat ini.";
      return;
    }

    const release = await res.json();
    const latestVersion = (release.tag_name || "").replace(/^v/i, "");

    if (latestVersion && latestVersion !== CURRENT_VERSION) {
      updateStatusText.innerHTML = `Versi baru (${release.tag_name}) tersedia. <a href="${release.html_url}" target="_blank" style="color:#2563eb;text-decoration:underline;font-weight:600;">Unduh Pembaruan</a>`;
    } else {
      updateStatusText.textContent = `Aplikasi sudah pada versi terbaru (v${CURRENT_VERSION}).`;
    }
  } catch (err) {
    updateStatusText.textContent = "Jaringan internet tidak terjangkau.";
  }
}

document.addEventListener("DOMContentLoaded", init);
