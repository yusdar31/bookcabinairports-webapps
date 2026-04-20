import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.1/index.js';

/*
 * Bookcabin Load Test — k6
 *
 * Install: brew install k6 / choco install k6
 * Run:     k6 run scripts/k6-load-test.js
 * Cloud:   k6 cloud run scripts/k6-load-test.js
 */

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const errorRate = new Rate('errors');
const apiDuration = new Trend('api_duration', true);

export const options = {
    stages: [
        { duration: '30s', target: 10 },  // Ramp up
        { duration: '1m',  target: 20 },  // Steady load
        { duration: '30s', target: 50 },  // Spike test
        { duration: '1m',  target: 20 },  // Recover
        { duration: '15s', target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'],   // 95% requests < 500ms
        http_req_failed:   ['rate<0.05'],   // Error rate < 5%
        errors:            ['rate<0.05'],
    },
};

export default function () {
    group('Health Check', () => {
        const res = http.get(`${BASE_URL}/api/health`);
        check(res, { 'health ok': (r) => r.status === 200 });
        errorRate.add(res.status !== 200);
        apiDuration.add(res.timings.duration);
    });

    group('Login', () => {
        const res = http.post(`${BASE_URL}/api/login`, JSON.stringify({
            email: 'kasir@bookcabin.test',
            password: 'password',
        }), { headers: { 'Content-Type': 'application/json' } });

        check(res, { 'login ok': (r) => r.status === 200 });
        errorRate.add(res.status !== 200);
    });

    group('Room Availability', () => {
        const checkIn = new Date(Date.now() + 86400000).toISOString().slice(0, 16);
        const checkOut = new Date(Date.now() + 2 * 86400000).toISOString().slice(0, 16);
        const res = http.get(`${BASE_URL}/api/rooms/availability?check_in=${checkIn}&check_out=${checkOut}`);
        check(res, { 'rooms loaded': (r) => r.status === 200 || r.status === 401 });
    });

    group('POS Transaction', () => {
        const res = http.post(`${BASE_URL}/api/transactions`, JSON.stringify({
            outlet_id: 1,
            items: [{ menu_id: 1, quantity: 2 }],
            payment_method: 'cash',
        }), { headers: { 'Content-Type': 'application/json' } });

        check(res, { 'transaction ok': (r) => r.status === 201 || r.status === 401 });
        apiDuration.add(res.timings.duration);
    });

    sleep(1);
}

export function handleSummary(data) {
    return {
        stdout: textSummary(data, { indent: '→', enableColors: true }),
    };
}
