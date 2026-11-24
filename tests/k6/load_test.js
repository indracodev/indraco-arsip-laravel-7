// DMS PT Indraco - k6 load test (Phase 1, bench routes; needs BENCH_ROUTES=true).
//
//   k6 run -e MODE=mixed30 tests/k6/load_test.js
//   MODE: quick | office | stress | ping | mixed30 | slow   (TARGET_URL default http://127.0.0.1:8000)
//
// quick/office/stress follow PROJECT_RULES_LAN_FRONTEND_OPS.md §8.2; mixed30 = 30 VUs, 60 s,
// ~85% read / 15% write; slow = mixed30 plus 3 VUs that keep a 3 s request running.
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Counter } from 'k6/metrics';

const BASE = (__ENV.TARGET_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const MODE = __ENV.MODE || 'office';

const failed = new Rate('failed_requests_rate');
const locked = new Counter('database_locked_errors');

const sla = {
    'http_req_duration{name:ping}': ['avg<2', 'p(95)<10'],
    'http_req_duration{name:read}': ['p(95)<350'],
};

const profiles = {
    quick: { scenarios: { users: { executor: 'constant-vus', vus: 2, duration: '6s', exec: 'office' } },
        thresholds: { http_req_duration: ['p(95)<1500'], failed_requests_rate: ['rate<0.05'] } },
    office: { scenarios: { users: { executor: 'ramping-vus', exec: 'office', startVUs: 0,
        stages: [{ duration: '3s', target: 3 }, { duration: '8s', target: 5 }, { duration: '3s', target: 0 }] } },
        thresholds: { http_req_duration: ['p(95)<1500'], failed_requests_rate: ['rate<0.05'] } },
    stress: { scenarios: { users: { executor: 'ramping-vus', exec: 'office', startVUs: 0,
        stages: [{ duration: '5s', target: 6 }, { duration: '15s', target: 12 }, { duration: '5s', target: 0 }] } },
        thresholds: { http_req_duration: ['p(95)<2500'], failed_requests_rate: ['rate<0.10'] } },
    ping: { scenarios: { users: { executor: 'constant-vus', vus: 10, duration: '20s', exec: 'pingOnly' } },
        thresholds: { 'http_req_duration{name:ping}': ['avg<2', 'p(95)<10'] } },
    mixed30: { scenarios: { users: { executor: 'constant-vus', vus: 30, duration: '60s', exec: 'mixed' } },
        thresholds: { http_req_duration: ['p(95)<500'], failed_requests_rate: ['rate==0'], database_locked_errors: ['count==0'] } },
    slow: { scenarios: {
        users: { executor: 'constant-vus', vus: 27, duration: '60s', exec: 'mixed' },
        heavy: { executor: 'constant-vus', vus: 3, duration: '60s', exec: 'slowOnly' } },
        thresholds: { 'http_req_duration{name:read}': ['p(95)<350'], failed_requests_rate: ['rate==0'] } },
};

export const options = Object.assign({ summaryTrendStats: ['avg', 'med', 'p(95)', 'p(99)', 'max'] }, profiles[MODE]);
if (!options.scenarios) throw new Error(`Unknown MODE ${MODE}`);
if (MODE !== 'ping') options.thresholds['http_req_duration{name:read}'] ??= sla['http_req_duration{name:read}'];
// Always-true thresholds so the summary shows per-endpoint latency.
for (const n of ['ping', 'read', 'write', 'slow']) options.thresholds[`http_req_duration{name:${n}}`] ??= ['max>=0'];

function record(res, name) {
    const ok = check(res, { [`${name} 2xx`]: (r) => r.status >= 200 && r.status < 300 });
    failed.add(!ok);
    if (res.body && String(res.body).includes('database is locked')) locked.add(1);
}

const get = (path, name) => record(http.get(BASE + path, { tags: { name } }), name);
const post = (path, name) => record(http.post(BASE + path, null, { tags: { name } }), name);

export function setup() {
    const r = http.post(`${BASE}/bench/setup`);
    if (r.status !== 200) throw new Error(`bench routes off? /bench/setup -> ${r.status}`);
}

// Real-office pattern: heartbeat, catalog page, sometimes a save, human think time.
export function office() {
    get('/api/health/ping', 'ping');
    get('/bench/read', 'read');
    if (Math.random() < 0.15) post('/bench/write', 'write');
    sleep(0.5 + Math.random());
}

export function mixed() {
    if (Math.random() < 0.15) post('/bench/write', 'write');
    else get('/bench/read', 'read');
    if (Math.random() < 0.1) get('/api/health/ping', 'ping');
    sleep(0.2 + Math.random() * 0.6); // busy user: 0.2-0.8 s between clicks
}

export function pingOnly() {
    get('/api/health/ping', 'ping');
    sleep(0.2);
}

export function slowOnly() {
    get('/bench/slow', 'slow');
}
