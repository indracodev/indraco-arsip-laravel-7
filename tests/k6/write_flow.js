// DMS PT Indraco - WRITE load test: the whole archive life cycle under concurrency.
// WRITES TEST DATA. Only against a server whose database has a snapshot (UJI-BEBAN-1-SNAPSHOT.bat)
// that is restored afterwards (UJI-BEBAN-3-RESTORE.bat). Runbook: .agents/context/runbook-uji-beban-server.md
//
//   k6 run -e TARGET_URL=http://192.168.2.180:8000 -e LEVELS=5,10,20 -e HOLD=60 -e OUT=write-PC1.json tests/k6/write_flow.js
//   RUN_ID=<id>      label of every record ("LOADTEST-<RUN_ID>-..."; default random, printed at start/end)
//   PICS=fin,hrd,hcs,dm,mkt   PIC Departemen accounts (<code>@indraco.com), one per VU in turn
//   GUDANG=gudang@indraco.com   PASSWORD=password (UserSeeder::DEFAULT_PASSWORD)   BOXES=2 (boxes per pengajuan)
//
// One iteration of one VU (= one PIC + one PIC Gudang, each with its own login session):
//   PIC submits a pengajuan of BOXES boxes (each 2 document lines + scan PDF 50-200 KB)
//   -> gudang verifies every box (box number counter under contention) -> check-in to a free rack
//   -> box 1: PIC requests penarikan, gudang dispatches (or rejects, 1 in 3)
//   -> box 2: PIC requests perpanjangan, gudang approves; then PIC proposes pemusnahan, gudang
//      approves and completes it with a BAP number + certificate PDF.
// Step time = the POST alone (redirects: 0); it is OK only when the page it redirects to shows a success toast.
// Two tester PCs at once: each gets its own RUN_ID; `php artisan dms:loadtest-verify` (no id) checks both.
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE = (__ENV.TARGET_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const LEVELS = (__ENV.LEVELS || '5,10,20').split(',').map(Number);
const HOLD = parseInt(__ENV.HOLD || '45', 10);
const PASSWORD = __ENV.PASSWORD || 'password';
const PIC_PASSWORD = __ENV.PIC_PASSWORD || PASSWORD;
const GUDANG = __ENV.GUDANG || 'gudang@indraco.com';
const PICS = (__ENV.PICS || 'fin,hrd,hcs,dm,mkt').split(',').map((p) => (p.includes('@') ? p : `${p}@indraco.com`));
const BOXES = Math.max(2, parseInt(__ENV.BOXES || '2', 10));

const STEPS = ['login', 'submit', 'find', 'verify', 'checkin', 'borrow_request', 'borrow_dispatch', 'borrow_reject',
    'extend_request', 'extend_approve', 'destroy_propose', 'destroy_approve', 'destroy_complete'];
const trend = Object.fromEntries(STEPS.map((s) => [s, new Trend(`w_${s}`, true)]));
const stepOk = new Rate('w_step_ok');
const created = new Counter('w_created');
const http5xx = new Counter('w_http_5xx');
const http419 = new Counter('w_http_419');

const scenarios = {};
const thresholds = { w_http_5xx: ['count<1'], w_http_419: ['count<5'] };
let start = 0;
for (const n of LEVELS) {
    scenarios[`vu${n}`] = { executor: 'constant-vus', vus: n, duration: `${HOLD}s`, startTime: `${start}s`, gracefulStop: '60s', exec: 'run' };
    start += HOLD + 62;
    for (const s of STEPS) {
        thresholds[`w_${s}{scenario:vu${n}}`] = ['max>=0'];
        thresholds[`w_step_ok{scenario:vu${n},step:${s}}`] = ['rate>=0'];
    }
}
export const options = { scenarios, thresholds, summaryTrendStats: ['med', 'p(95)', 'max', 'count'], setupTimeout: '120s' };

// ---------- helpers ----------
function today() { return new Date().toISOString().slice(0, 10); }

/** A valid PDF (one page, Helvetica text) padded to `kb` KB with more text lines. */
function makePdf(label, kb) {
    const lines = [];
    let y = 800;
    let i = 0;
    let stream = '';
    while (stream.length < kb * 1024) {
        lines.push(`BT /F1 7 Tf 30 ${y} Td (${label} baris ${i} - dokumen uji beban, aman dihapus) Tj ET`);
        y = y <= 40 ? 800 : y - 9;
        i++;
        if (lines.length >= 200) { stream += lines.join('\n') + '\n'; lines.length = 0; }
    }
    stream += lines.join('\n');
    const objs = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        `<< /Length ${stream.length} >>\nstream\n${stream}\nendstream`,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];
    let pdf = '%PDF-1.4\n';
    const offsets = [];
    objs.forEach((o, k) => { offsets.push(pdf.length); pdf += `${k + 1} 0 obj\n${o}\nendobj\n`; });
    const xref = pdf.length;
    pdf += `xref\n0 ${objs.length + 1}\n0000000000 65535 f \n`;
    offsets.forEach((o) => { pdf += `${String(o).padStart(10, '0')} 00000 n \n`; });
    pdf += `trailer\n<< /Size ${objs.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF\n`;
    return pdf;
}

function track(res, step) {
    if (res.status >= 500) http5xx.add(1);
    if (res.status === 419) http419.add(1);
    trend[step].add(res.timings.duration);
}

function token(res) {
    const m = /name="_token" value="([^"]+)"/.exec(res.body || '') || /name="csrf-token" content="([^"]+)"/.exec(res.body || '');
    return m ? m[1] : '';
}

function toast(res) {
    const m = /class="toast toast--(\w+)"[\s\S]*?class="toast__text">([^<]*)</.exec(res.body || '');
    return m ? { tone: m[1], text: m[2].trim() } : { tone: '', text: '' };
}

function get(jar, path, step) {
    const res = http.get(`${BASE}${path}`, { jar, tags: { name: step, step } });
    if (res.status >= 500) http5xx.add(1);
    return res;
}

/** POST a form (multipart when a file is in it) and judge it by the success toast after the redirect. */
function post(jar, path, body, step, referer) {
    const sent = http.post(`${BASE}${path}`, body, { jar, redirects: 0, headers: { Referer: `${BASE}${referer}` }, tags: { name: step, step } });
    track(sent, step); // the write itself; the page after the redirect is not part of the step time
    const res = sent.status === 302 ? get(jar, sent.headers.Location.replace(/^https?:\/\/[^/]+/, ''), `${step}_page`) : sent;
    const t = toast(res);
    const good = check(res, { [`${step} ok`]: (r) => r.status === 200 && t.tone === 'success' });
    stepOk.add(good, { step });
    if (!good && __ITER < 3) console.warn(`[VU ${__VU}] ${step} -> ${res.status} ${t.tone}: ${t.text.slice(0, 160)}`);
    return good ? t.text || 'ok' : null;
}

function login(email, password) {
    const jar = new http.CookieJar();
    const page = http.get(`${BASE}/login`, { jar, tags: { name: 'login', step: 'login' } });
    const res = http.post(`${BASE}/login`, { _token: token(page), email, password }, { jar, tags: { name: 'login', step: 'login' } });
    track(res, 'login');
    stepOk.add(res.status === 200 && !res.url.endsWith('/login'), { step: 'login' });
    if (res.status !== 200 || res.url.endsWith('/login')) throw new Error(`login ${email} gagal: ${res.status} ${res.url}`);
    return jar;
}

function pdfFile(name) { return http.file(state.pdf, `${name}.pdf`, 'application/pdf'); }

// ---------- setup / per VU state ----------
export function setup() {
    const runId = __ENV.RUN_ID || `r${Date.now().toString(36)}`;
    // Fail fast on wrong credentials / host before any VU starts (sessions are not reused).
    login(GUDANG, PASSWORD);
    for (const pic of PICS) login(pic, PIC_PASSWORD);
    console.log(`RUN_ID=${runId}  (verifikasi: php artisan dms:loadtest-verify ${runId})`);
    return { runId };
}

const state = { pic: null, gudang: null, pdf: null, n: 0 };

export function run(data) {
    if (!state.pic) {
        const email = PICS[(__VU - 1) % PICS.length];
        state.pic = login(email, PIC_PASSWORD);
        state.gudang = login(GUDANG, PASSWORD);
        state.code = email.split('@')[0].toUpperCase();
        state.pdf = makePdf(`LOADTEST-${data.runId}`, 50 + Math.floor(Math.random() * 150));
    }
    state.n++;
    const label = `LOADTEST-${data.runId}-${state.code}-v${__VU}i${state.n}`;
    const ids = submitAndStore(label);
    if (ids.length >= 2) {
        withdraw(ids[0], label);
        extendThenDestroy(ids[1], label);
    }
    sleep(0.2);
}

function submitAndStore(label) {
    const { pic, gudang } = state;
    const form = get(pic, '/archives/create', 'submit');
    const body = { _token: token(form), submit_action: 'submit', tgl_penyerahan: today(), scan_input_form: pdfFile(label) };
    for (let b = 0; b < BOXES; b++) {
        body[`boxes[${b}][title]`] = `${label}-b${b + 1}`;
        body[`boxes[${b}][physical_condition]`] = 'Baik';
        body[`boxes[${b}][sub_department_id]`] = 'inti';
        for (let i = 0; i < 2; i++) {
            body[`boxes[${b}][items][${i}][document_name]`] = `Dokumen uji ${i + 1}`;
            body[`boxes[${b}][items][${i}][period_start]`] = '2026-01';
            body[`boxes[${b}][items][${i}][period_end]`] = '2026-06';
            body[`boxes[${b}][items][${i}][notes]`] = `${label} butir ${i + 1}`;
        }
    }
    if (!post(pic, '/archives', body, 'submit', '/archives/create')) return [];
    created.add(BOXES, { kind: 'box' });

    const list = get(gudang, `/archives?search=${encodeURIComponent(`${label}-b`)}`, 'find');
    trend.find.add(list.timings.duration);
    const ids = [...new Set([...(list.body || '').matchAll(/\/archives\/(\d+)"/g)].map((m) => m[1]))];
    const found = check(ids, { 'find all boxes': (x) => x.length === BOXES });
    stepOk.add(found, { step: 'find' });
    if (!found) console.warn(`[VU ${__VU}] find ${label}: ${ids.length}/${BOXES}`);

    const stored = [];
    for (const id of ids.sort((a, b) => a - b)) {
        const page = get(gudang, `/archives/${id}`, 'verify');
        if (!post(gudang, `/archives/${id}/verify`, { _token: token(page), action: 'approve' }, 'verify', `/archives/${id}`)) continue;
        const booked = get(gudang, `/archives/${id}`, 'checkin');
        const racks = [...(booked.body || '').matchAll(/<option value="(\d+)"\s*>/g)].map((m) => m[1]);
        if (!racks.length) { console.warn(`[VU ${__VU}] no free rack for archive ${id}`); continue; }
        const rack = racks[Math.floor(Math.random() * racks.length)];
        if (post(gudang, `/archives/${id}/checkin`, { _token: token(booked), warehouse_location_id: rack, notes: label }, 'checkin', `/archives/${id}`)) stored.push(id);
    }
    return stored;
}

function withdraw(id, label) {
    const { pic, gudang } = state;
    const form = get(pic, '/borrowings/create', 'borrow_request');
    const ok = post(pic, '/borrowings', { _token: token(form), 'archive_ids[]': id, purpose: `${label} penarikan`, approval_file: pdfFile(`${label}-tarik`) },
        'borrow_request', '/borrowings/create');
    if (!ok) return;
    created.add(1, { kind: 'borrowing' });
    const list = get(gudang, `/borrowings?search=${encodeURIComponent(`${label} penarikan`)}`, 'borrow_dispatch');
    const m = /\/borrowings\/(\d+)\/dispatch/.exec(list.body || '');
    if (!m) { console.warn(`[VU ${__VU}] borrowing of ${id} not found`); return; }
    if (Math.random() < 1 / 3) {
        post(gudang, `/borrowings/${m[1]}/reject`, { _token: token(list), reason: `${label} ditolak uji` }, 'borrow_reject', '/borrowings');
    } else {
        post(gudang, `/borrowings/${m[1]}/dispatch`, { _token: token(list), confirm_file_checked: '1' }, 'borrow_dispatch', '/borrowings');
    }
}

function extendThenDestroy(id, label) {
    const { pic, gudang } = state;
    const extForm = get(pic, `/destructions/extend/${id}`, 'extend_request');
    if (!post(pic, '/destructions/extend', { _token: token(extForm), 'archive_ids[]': id, additional_months: '12', extension_reason: `${label} perpanjangan`, scan_extension_form: pdfFile(`${label}-ext`) },
        'extend_request', `/destructions/extend/${id}`)) return;
    created.add(1, { kind: 'extension' });
    const q = encodeURIComponent(`${label}-b`); // the box title; box order != id order
    const extList = get(gudang, `/destructions?tab=extensions&q=${q}`, 'extend_approve');
    const ext = /\/destructions\/extensions\/(\d+)\/approve/.exec(extList.body || '');
    if (!ext || !post(gudang, `/destructions/extensions/${ext[1]}/approve`, { _token: token(extList) }, 'extend_approve', '/destructions?tab=extensions')) return;

    const propose = get(pic, `/destructions/propose/${id}`, 'destroy_propose');
    if (!post(pic, '/destructions/propose', { _token: token(propose), 'archive_ids[]': id, destruction_date: today(), method: 'Penghancuran (Shredding)', notes: `${label} pemusnahan`, approval_file: pdfFile(`${label}-musnah`) },
        'destroy_propose', `/destructions/propose/${id}`)) return;
    created.add(1, { kind: 'destruction' });
    const list = get(gudang, `/destructions?tab=destructions&q=${q}`, 'destroy_approve');
    const log = /\/destructions\/(\d+)\/approve/.exec(list.body || '');
    if (!log || !post(gudang, `/destructions/${log[1]}/approve`, { _token: token(list) }, 'destroy_approve', '/destructions?tab=destructions')) return;
    const again = get(gudang, `/destructions?tab=destructions&q=${q}`, 'destroy_complete');
    post(gudang, `/destructions/${log[1]}/complete`, { _token: token(again), bap_number: `BAP/${label}`, destruction_date: today(), notes: label, certificate_file: pdfFile(`${label}-bap`) },
        'destroy_complete', '/destructions?tab=destructions');
}

// ---------- summary ----------
export function handleSummary(data) {
    const rows = [];
    for (const n of LEVELS) {
        for (const s of STEPS) {
            const d = data.metrics[`w_${s}{scenario:vu${n}}`];
            const r = data.metrics[`w_step_ok{scenario:vu${n},step:${s}}`];
            if (!d || !d.values.count) continue;
            rows.push({ vus: n, step: s, count: d.values.count, p50: +d.values.med.toFixed(1), p95: +d.values['p(95)'].toFixed(1), max: +d.values.max.toFixed(0),
                ok_pct: r && r.values.rate !== undefined ? +(r.values.rate * 100).toFixed(1) : null });
        }
    }
    const m = (k) => (data.metrics[k] ? data.metrics[k].values.count : 0);
    const table = ['| VU | langkah | n | p50 ms | p95 ms | max ms | sukses % |', '|---|---|---|---|---|---|---|']
        .concat(rows.map((x) => `| ${x.vus} | ${x.step} | ${x.count} | ${x.p50} | ${x.p95} | ${x.max} | ${x.ok_pct ?? '-'} |`)).join('\n');
    const runId = data.setup_data ? data.setup_data.runId : __ENV.RUN_ID;
    const totals = `RUN_ID=${runId}  dibuat (box+log): ${m('w_created')}  HTTP 5xx: ${m('w_http_5xx')}  HTTP 419: ${m('w_http_419')}  ` +
        `request total: ${m('http_reqs')}  gagal cek: ${data.metrics.checks ? data.metrics.checks.values.fails : 0}`;
    const text = `${table}\n\n${totals}\n`;
    const out = { stdout: text };
    if (__ENV.OUT) {
        out[__ENV.OUT] = JSON.stringify({ base: BASE, runId, rows, created: m('w_created'), http5xx: m('w_http_5xx'), http419: m('w_http_419') }, null, 1);
        out[__ENV.OUT.replace(/\.json$/, '.md')] = text;
    }
    return out;
}
