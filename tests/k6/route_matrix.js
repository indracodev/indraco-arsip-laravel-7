// DMS PT Indraco - Fase 6 performance matrix: every main read route at 1 / 10 / 30 / 50 VUs.
// READ-ONLY (GET only): safe against the live server that runs on a copy of real data.
//
//   k6 run -e TARGET_URL=http://127.0.0.1:8000 -e ARCHIVE_ID=117 -e OUT=result.json tests/k6/route_matrix.js
//   LEVELS=1,10,30   (default 1,10,30,50)   HOLD=25 (seconds per level)
//   MODE=human  -> realistic users instead of back-to-back requests (see run() below)
//
// Login happens once per role in setup() (3 sessions in total, no login per VU: avoids the
// login throttle and leaves the browser sessions alone). Every VU replays one role's route
// list without think time, so "N VUs" = N requests in flight at once (much harsher than N
// people clicking). The summary prints p50/p95/p99, req/s and failures per level and route.
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const BASE = (__ENV.TARGET_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const ARCHIVE = __ENV.ARCHIVE_ID || '117';
const LEVELS = (__ENV.LEVELS || '1,10,30,50').split(',').map(Number);
const HOLD = parseInt(__ENV.HOLD || '25', 10);
const PASSWORD = __ENV.PASSWORD || 'password'; // UserSeeder::DEFAULT_PASSWORD
const ROLES = { admin: 'admin@indraco.com', gudang: 'gudang@indraco.com', fin: 'fin@indraco.com' };

const ok = new Rate('route_ok');

// PIC Departemen has no dashboard: "/" redirects them to /archives (DashboardController).
const common = {
    archives: '/archives',
    archives_search: '/archives?search=box',
    archives_filter: '/archives?status=in_warehouse&sort=box_number&direction=asc&per_page=50',
    archive_show: `/archives/${ARCHIVE}`,
    borrowings: '/borrowings',
    destr_h30: '/destructions?tab=h30',
    destr_ext: '/destructions?tab=extensions',
    destr_bap: '/destructions?tab=destructions',
    poll: '/api/realtime/check?since_seq=0',
    poll_initial: '/api/realtime/check?initial=1',
    file_preview: `/files/archive/${ARCHIVE}/scan_input_form`,
    ping: '/api/health/ping',
};
const warehouse = { quick_search: '/api/warehouse/layout-search?q=IND' };
const routesFor = {
    admin: { dashboard_admin: '/', ...common, ...warehouse },
    gudang: { dashboard_gudang: '/', ...common, ...warehouse },
    fin: common,
};
const ALL = [...new Set(Object.values(routesFor).flatMap((r) => Object.keys(r)))];

const scenarios = {};
const thresholds = {};
let start = 0;
for (const n of LEVELS) {
    scenarios[`vu${n}`] = { executor: 'constant-vus', vus: n, duration: `${HOLD}s`, startTime: `${start}s`, gracefulStop: '3s', exec: 'run' };
    start += HOLD + 4;
    for (const name of ALL) {
        // Dummy thresholds: they only make k6 keep a sub-metric per level + route for the summary.
        thresholds[`http_req_duration{scenario:vu${n},name:${name}}`] = ['max>=0'];
        thresholds[`route_ok{scenario:vu${n},name:${name}}`] = ['rate>=0'];
    }
}

export const options = {
    scenarios,
    thresholds,
    summaryTrendStats: ['med', 'p(95)', 'p(99)', 'max', 'count'],
    discardResponseBodies: false,
};

function login(email) {
    const jar = new http.CookieJar();
    const page = http.get(`${BASE}/login`, { jar });
    const token = page.html().find('input[name="_token"]').attr('value');
    const res = http.post(`${BASE}/login`, { _token: token, email, password: PASSWORD }, { jar });
    if (res.status !== 200 || res.url.endsWith('/login')) {
        throw new Error(`login ${email} failed: ${res.status} ${res.url}`);
    }
    const cookies = {};
    for (const [k, v] of Object.entries(jar.cookiesForURL(`${BASE}/`))) {
        cookies[k] = { value: v[0], replace: true };
    }
    return cookies;
}

export function setup() {
    const sessions = {};
    for (const [role, email] of Object.entries(ROLES)) sessions[role] = login(email);
    return sessions;
}

function hit(sessions, role, name, path) {
    const res = http.get(`${BASE}${path}`, { cookies: sessions[role], redirects: 0, tags: { name, role } });
    const good = check(res, { 'status 200': (r) => r.status === 200 });
    ok.add(good, { name });
    if (!good && __ITER < 2) console.warn(`${role} ${name} -> ${res.status} ${res.headers.Location || ''}`);
}

// MODE=human: one VU = one person with the app open. Like the browser: LAN ping every 5 s (lan.js),
// notification poll every POLL_S s ± 2 s after a random first delay (notifications.js, default 15;
// -e POLL_S=5 = before P3) and on average one page every 20 s.
const HUMAN = __ENV.MODE === 'human';
const POLL_MS = parseFloat(__ENV.POLL_S || '15') * 1000;
let nextPoll = null; // per VU
const PAGES = Object.fromEntries(Object.entries(routesFor).map(([role, r]) => [role,
    Object.entries(r).filter(([n]) => !['poll', 'poll_initial', 'ping'].includes(n))]));

export function run(sessions) {
    const roles = Object.keys(ROLES);
    const role = roles[(__VU - 1) % roles.length];
    if (HUMAN) {
        const now = Date.now();
        if (nextPoll === null) nextPoll = now + Math.random() * POLL_MS;
        if (now >= nextPoll) {
            hit(sessions, role, 'poll', common.poll);
            nextPoll = now + POLL_MS - 2000 + Math.random() * 4000;
        }
        hit(sessions, role, 'ping', common.ping);
        if (Math.random() < 0.25) {
            const [name, path] = PAGES[role][Math.floor(Math.random() * PAGES[role].length)];
            hit(sessions, role, name, path);
        }
        sleep(4.5 + Math.random());
        return;
    }
    for (const [name, path] of Object.entries(routesFor[role])) hit(sessions, role, name, path);
    sleep(0.01);
}

export function handleSummary(data) {
    const rows = [];
    for (const n of LEVELS) {
        for (const name of ALL) {
            const d = data.metrics[`http_req_duration{scenario:vu${n},name:${name}}`];
            const r = data.metrics[`route_ok{scenario:vu${n},name:${name}}`];
            if (!d || !d.values.count) continue;
            rows.push({
                vus: n, route: name, count: d.values.count,
                rps: +(d.values.count / HOLD).toFixed(1),
                p50: +d.values.med.toFixed(2), p95: +d.values['p(95)'].toFixed(2),
                p99: +d.values['p(99)'].toFixed(2), max: +d.values.max.toFixed(1),
                err_pct: r ? +((1 - r.values.rate) * 100).toFixed(2) : null,
            });
        }
    }
    const table = ['| VU | route | n | req/s | p50 | p95 | p99 | max | err % |', '|---|---|---|---|---|---|---|---|---|']
        .concat(rows.map((x) => `| ${x.vus} | ${x.route} | ${x.count} | ${x.rps} | ${x.p50} | ${x.p95} | ${x.p99} | ${x.max} | ${x.err_pct} |`))
        .join('\n');
    const total = LEVELS.map((n) => {
        const all = rows.filter((x) => x.vus === n);
        return `vu${n}: ${all.reduce((s, x) => s + x.rps, 0).toFixed(1)} req/s total`;
    }).join(', ');
    const out = { stdout: `${table}\n\n${total}\n` };
    if (__ENV.OUT) {
        out[__ENV.OUT] = JSON.stringify({ base: BASE, rows }, null, 1);
        out[__ENV.OUT.replace(/\.json$/, '.md')] = `${table}\n\n${total}\n`;
    }
    return out;
}
