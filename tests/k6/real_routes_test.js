// DMS PT Indraco - Fase 6 k6 load test against real routes (read-only + ping).
// Run: "C:\Program Files\k6\k6.exe" run tests/k6/real_routes_test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Counter } from 'k6/metrics';

const BASE = (__ENV.TARGET_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const failed = new Rate('failed_requests_rate');
const locked = new Counter('database_locked_errors');

export const options = {
    noCookiesReset: true,
    summaryTrendStats: ['avg', 'med', 'p(95)', 'p(99)', 'max'],
    scenarios: {
        ping: {
            executor: 'constant-vus',
            vus: 5,
            duration: '10s',
            exec: 'pingLoop',
        },
        office: {
            executor: 'ramping-vus',
            exec: 'officeLoop',
            startVUs: 0,
            stages: [
                { duration: '3s', target: 5 },
                { duration: '10s', target: 5 },
                { duration: '2s', target: 0 },
            ],
        },
    },
    thresholds: {
        'http_req_duration{name:ping}': ['avg<5', 'p(95)<15'],
        'http_req_duration{name:blade}': ['p(95)<350'],
        'http_req_duration{name:api}': ['p(95)<150'],
        failed_requests_rate: ['rate==0'],
        database_locked_errors: ['count==0'],
    },
};

function record(res, name) {
    const ok = check(res, { [`${name} 200`]: (r) => r.status === 200 });
    failed.add(!ok);
    if (res.body && String(res.body).includes('database is locked')) {
        locked.add(1);
    }
}

let loggedIn = false;
function ensureLogin() {
    if (loggedIn) return;
    const loginPage = http.get(`${BASE}/login`);
    const csrf = loginPage.html().find('input[name="_token"]').attr('value');
    const res = http.post(`${BASE}/login`, {
        _token: csrf,
        email: 'admin@indraco.com',
        password: 'password',
    });
    if (!check(res, { 'login ok': (r) => r.status === 200 && !r.url.endsWith('/login') })) {
        throw new Error(`Login failed: status=${res.status} url=${res.url}`);
    }
    loggedIn = true;
}

export function pingLoop() {
    record(http.get(`${BASE}/api/health/ping`, { tags: { name: 'ping' } }), 'ping');
    sleep(0.2);
}

export function officeLoop() {
    ensureLogin();
    record(http.get(`${BASE}/`, { tags: { name: 'blade' } }), 'blade');
    record(http.get(`${BASE}/archives`, { tags: { name: 'blade' } }), 'blade');
    record(http.get(`${BASE}/borrowings`, { tags: { name: 'blade' } }), 'blade');
    record(http.get(`${BASE}/destructions`, { tags: { name: 'blade' } }), 'blade');
    record(http.get(`${BASE}/api/realtime/check?initial=1`, { tags: { name: 'api' } }), 'api');
    sleep(0.5);
}
