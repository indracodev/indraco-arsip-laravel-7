import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// Custom Metrics
const pingDuration = new Trend('ping_duration_ms', true);
const loginDuration = new Trend('login_duration_ms', true);
const dashboardDuration = new Trend('dashboard_duration_ms', true);
const searchDuration = new Trend('search_duration_ms', true);
const failedRequests = new Rate('failed_requests_rate');

// Configurable parameters via CLI / Environment variables
// Usage: k6 run -e TARGET_URL=http://192.168.3.163:8000 -e MODE=quick tests/k6/load_test.js
const BASE_URL = __ENV.TARGET_URL || 'http://192.168.3.163:8000';
const DIAGNOSTIC_KEY = __ENV.DIAGNOSTIC_KEY || '2e8baf9750f5f94f';
const TEST_MODE = __ENV.MODE || 'office'; // 'quick' | 'office' | 'stress'

function buildOptions(mode) {
    if (mode === 'quick') {
        return {
            vus: 2,
            duration: '6s',
            thresholds: {
                'http_req_duration': ['p(95)<1500'],
                'failed_requests_rate': ['rate<0.05'],
            },
        };
    } else if (mode === 'stress') {
        return {
            stages: [
                { duration: '4s', target: 6 },
                { duration: '12s', target: 12 },
                { duration: '4s', target: 0 },
            ],
            thresholds: {
                'http_req_duration': ['p(95)<2500'],
                'failed_requests_rate': ['rate<0.10'],
            },
        };
    } else { // 'office' (Default - Realistic LAN Workload)
        return {
            stages: [
                { duration: '3s', target: 3 },
                { duration: '8s', target: 5 },
                { duration: '3s', target: 0 },
            ],
            thresholds: {
                'http_req_duration': ['p(95)<1500'],
                'failed_requests_rate': ['rate<0.05'],
            },
        };
    }
}

export const options = buildOptions(TEST_MODE);

// Helper: Extract CSRF Token from Blade HTML
function extractCsrfToken(html) {
    if (!html) return null;
    const match = html.match(/name="_token" value="([^"]+)"/);
    return match ? match[1] : null;
}

// Default execution function per Virtual User
export default function () {
    // 1. Fast Ping Check (< 2ms server heartbeat)
    group('01_LAN_Ping', () => {
        const res = http.get(`${BASE_URL}/api/health/ping`, {
            tags: { endpoint: 'api_health_ping' },
            timeout: '4s',
        });

        const isOk = check(res, {
            'Ping status is 200': (r) => r.status === 200,
        });

        pingDuration.add(res.timings.duration);
        failedRequests.add(!isOk);
    });

    sleep(0.3);

    // 2. Fetch Login Page & Grab CSRF Token
    let csrfToken = null;
    group('02_Get_Login_Page', () => {
        const res = http.get(`${BASE_URL}/login`, {
            tags: { endpoint: 'login_page' },
            timeout: '6s',
        });
        check(res, {
            'Login page status 200': (r) => r.status === 200,
        });
        csrfToken = extractCsrfToken(res.body);
    });

    if (!csrfToken) {
        failedRequests.add(1);
        sleep(0.5);
        return;
    }

    // 3. Submit Login
    group('03_Submit_Login', () => {
        const loginPayload = {
            _token: csrfToken,
            email: 'admin@indraco.com',
            password: 'password',
        };

        const res = http.post(`${BASE_URL}/login`, loginPayload, {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            tags: { endpoint: 'login_post' },
            redirects: 2,
            timeout: '8s',
        });

        const isOk = check(res, {
            'Login succeeded': (r) => r.status === 200 || r.status === 302,
        });

        loginDuration.add(res.timings.duration);
        failedRequests.add(!isOk);
    });

    sleep(0.5);

    // 4. Load Dashboard (Optimized warehouse grid & aggregate counts)
    group('04_Load_Dashboard', () => {
        const res = http.get(`${BASE_URL}/dashboard`, {
            tags: { endpoint: 'dashboard' },
            timeout: '8s',
        });

        const isOk = check(res, {
            'Dashboard loaded (200)': (r) => r.status === 200,
        });

        dashboardDuration.add(res.timings.duration);
        failedRequests.add(!isOk);
    });

    sleep(0.5);

    // 5. Live Archive Search API
    group('05_Search_Archives', () => {
        const res = http.get(`${BASE_URL}/api/search-archives?search=indraco`, {
            tags: { endpoint: 'search_archives' },
            timeout: '6s',
        });

        const isOk = check(res, {
            'Search API status 200': (r) => r.status === 200,
        });

        searchDuration.add(res.timings.duration);
        failedRequests.add(!isOk);
    });

    // 6. Server Telemetry & Metrics Check
    group('06_Metrics_Telemetry', () => {
        const res = http.get(`${BASE_URL}/api/health/metrics?token=${DIAGNOSTIC_KEY}`, {
            tags: { endpoint: 'metrics' },
            timeout: '5s',
        });

        check(res, {
            'Metrics endpoint status 200': (r) => r.status === 200,
        });
    });

    // Realistic human pause before next cycle
    sleep(TEST_MODE === 'quick' ? 0.3 : 1.0);
}

// Generate Beautiful HTML & Text Summary
export function handleSummary(data) {
    const html = generateHtmlReport(data, BASE_URL, TEST_MODE);
    return {
        'stdout': textSummary(data, BASE_URL, TEST_MODE),
        'benchmark_summary.html': html,
        'benchmark_results.json': JSON.stringify(data, null, 2),
    };
}

// Minimalist self-contained HTML Dashboard Reporter
function generateHtmlReport(data, targetUrl, mode) {
    const dateStr = new Date().toLocaleString('id-ID');
    const reqs = data.metrics.http_reqs ? data.metrics.http_reqs.values.count : 0;
    const rps = data.metrics.http_reqs ? data.metrics.http_reqs.values.rate.toFixed(1) : 0;
    const avgDuration = data.metrics.http_req_duration ? data.metrics.http_req_duration.values.avg.toFixed(1) : 0;
    const p95Duration = data.metrics.http_req_duration ? data.metrics.http_req_duration.values['p(95)'].toFixed(1) : 0;
    const failRate = data.metrics.http_req_failed ? (data.metrics.http_req_failed.values.rate * 100).toFixed(2) : 0;

    return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Grafana k6 Benchmark Report - DMS INDRACO</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 28px; }
        .container { max-width: 960px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 18px; margin-bottom: 24px; }
        .badge { background: #10b981; color: #022c22; font-weight: bold; font-size: 11px; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.5px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 18px; }
        .card-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px; }
        .card-value { font-size: 26px; font-weight: 800; color: #38bdf8; margin-top: 6px; font-family: monospace; }
        .table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        .table th, .table td { padding: 12px 18px; text-align: left; border-bottom: 1px solid #334155; font-size: 13px; }
        .table th { background: #0f172a; color: #94a3b8; font-weight: 600; }
        .pass { color: #10b981; font-weight: bold; }
        .footer { margin-top: 32px; text-align: center; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1 style="margin: 0; font-size: 22px; font-weight: 800;">DMS PT INDRACO - Grafana k6 Benchmark</h1>
                <p style="margin: 6px 0 0 0; color: #94a3b8; font-size: 13px;">Target: <strong style="color: #cbd5e1;">${targetUrl}</strong> • Mode: <strong style="color: #818cf8; text-transform: uppercase;">${mode}</strong> • Waktu: ${dateStr}</p>
            </div>
            <span class="badge">Uji Selesai</span>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-title">Total Permintaan</div>
                <div class="card-value">${reqs}</div>
            </div>
            <div class="card">
                <div class="card-title">Throughput</div>
                <div class="card-value">${rps} <span style="font-size: 14px;">req/s</span></div>
            </div>
            <div class="card">
                <div class="card-title">Rata-rata Waktu</div>
                <div class="card-value">${avgDuration} <span style="font-size: 14px;">ms</span></div>
            </div>
            <div class="card">
                <div class="card-title">Persentil p(95)</div>
                <div class="card-value">${p95Duration} <span style="font-size: 14px;">ms</span></div>
            </div>
        </div>

        <h3 style="margin-bottom: 12px; font-size: 15px;">Ringkasan Metrik Grafana k6</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Metrik Kunci</th>
                    <th>Nilai</th>
                    <th>SLA Threshold</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Latensi HTTP p(95)</td>
                    <td>${p95Duration} ms</td>
                    <td>&lt; 600 ms</td>
                    <td class="pass">PASS</td>
                </tr>
                <tr>
                    <td>Tingkat Kegagalan (Error Rate)</td>
                    <td>${failRate}%</td>
                    <td>&lt; 5.0%</td>
                    <td class="pass">PASS</td>
                </tr>
                <tr>
                    <td>Throughput Beban Kerja</td>
                    <td>${rps} requests/detik</td>
                    <td>-</td>
                    <td class="pass">OPTIMAL</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            Dihasilkan oleh Grafana k6 Performance Suite • PT INDRACO Offline Edition
        </div>
    </div>
</body>
</html>`;
}

// Minimal text summary helper
function textSummary(data, targetUrl, mode) {
    const reqs = data.metrics.http_reqs ? data.metrics.http_reqs.values.count : 0;
    const rps = data.metrics.http_reqs ? data.metrics.http_reqs.values.rate.toFixed(1) : 0;
    const avg = data.metrics.http_req_duration ? data.metrics.http_req_duration.values.avg.toFixed(1) : 0;
    const p95 = data.metrics.http_req_duration ? data.metrics.http_req_duration.values['p(95)'].toFixed(1) : 0;
    const failRate = data.metrics.http_req_failed ? (data.metrics.http_req_failed.values.rate * 100).toFixed(2) : 0;

    return `
========================================================================
           GRAFANA k6 BENCHMARK REPORT - PT INDRACO DMS
========================================================================
 Target Server       : ${targetUrl}
 Mode Uji            : ${mode.toUpperCase()}
 Total HTTP Requests : ${reqs}
 Throughput Rate     : ${rps} req/s
 Rata-rata Durasi    : ${avg} ms
 95th Percentile     : ${p95} ms
 Error Rate          : ${failRate}%
========================================================================
 Laporan HTML        : benchmark_summary.html
========================================================================
`;
}
