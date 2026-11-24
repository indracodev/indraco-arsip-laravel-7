# Performance baseline

## Fase 1 (2026-11-24) — fondasi, route bench, salinan DB asli
Laptop 16 core (lebih cepat per core dari AMD A9; perkirakan 2–4× lebih lambat di server asli). CPU dibatasi dengan affinity. Skrip `tests/k6/load_test.js`.

| Core × worker | office p50/p95 ms | mixed30 (30 VU) p50/p95 ms, req/s, error | slow (3 req 3 dtk): baca p95, gagal | RAM private idle→puncak MB |
|---|---|---|---|---|
| 2 × 2 | 7,7 / 30,8 | 11,5 / 47,6, 62,9, 0 | timeout 30 dtk, 18 gagal | 101 → 107 |
| 2 × 3 | 8,2 / 34,0 | 11,9 / 39,3, 63,5, 0 | ±21 dtk, 4 gagal | 120 → 126 |
| **2 × 4** | 7,6 / 31,5 | 12,5 / 37,4, 62,2, 0 | **113,8, 0** | 140 → 149 |
| 1 × 2 | 8,5 / 36,3 | 14,3 / 55,0, 62,6, 0 | timeout, 38 gagal | 100 → 105 |
| 1 × 3 | 8,3 / 21,5 | 14,5 / 55,4, 62,8, 0 | 26 gagal | 120 → 126 |
| **1 × 4** | 7,7 / 28,0 | 14,4 / 46,6, 63,0, 0 | **79,5, 0** | 139 → 149 |
| 16 × 6 | 11,5 / 33,7 | 14,6 / 33,1, 62,9, 0 | 40,8, 0 | — |
| `artisan serve` (L7 cara lama) | 6,6 / 25,0 | 10,2 / 35,9, 63,3, 0 | **semua request ±9 dtk** | — |

- Ping (Caddy): 0,3–0,7 ms rata-rata, p95 ≤ 1,7 ms (1 core 30 VU: p95 ±18 ms).
- HTTPS vs HTTP (2×4, mixed30): p95 34,1 vs 28,7 ms; 0 error.
- 0 `database is locked` di semua skenario.
- START→SIAP (PHP membalas, semua worker hangat): 1,6–3,0 dtk (16 core), 2,1–2,8 dtk (2 core). Koordinator: 1,5 dtk.
- Idle: CPU 0 ms/20 dtk; 6 worker ≈ 137 MB private + 25 MB loop cmd + Caddy ≈ 60 MB.
- **Wajib diulang di PC server asli** sebelum cutover.
