import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metric to track error rate
export let errorRate = new Rate('errors');

export let options = {
    stages: [
        { duration: '10s', target: 50 },  // Ramp up to 50 users over 30s
        { duration: '10s', target: 200 },  // Stay at 100 users for 1m
        { duration: '10s', target: 400 }, // Ramp up to 200 users over 30s
        { duration: '20s', target: 800 },  // Stay at 200 users for 1m
        { duration: '10s', target: 0 },   // Ramp down to 0 users
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'],  // 95% of requests must complete below 500ms
        errors: ['rate<0.01'],              // Error rate should be less than 1%
    },
};

function generateRandomSheba() {
    // Generate dummy IR numbers (replace with realistic format if needed)
    let prefix = 'IR';
    let body = '';
    for (let i = 0; i < 24; i++) {
        body += Math.floor(Math.random() * 10).toString();
    }
    return prefix + body;
}

function generateIdempotencyKey() {
    // Generate UUID v4 (simple version)
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        let r = (Math.random() * 16) | 0,
            v = c == 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

export default function () {
    let url = 'http://localhost:8090/api/sheba';  // Change to your API URL
    let payload = JSON.stringify({
        price: Math.floor(Math.random() * 10000) + 1000,  // random amount 1000-10999
        fromShebaNumber: generateRandomSheba(),
        toShebaNumber: generateRandomSheba(),
        note: 'Load test transfer',
        idempotency_key: generateIdempotencyKey(),
    });

    let params = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    let res = http.post(url, payload, params);

    // Basic checks for response
    let success = check(res, {
        'status is 201': (r) => r.status === 201,
        'response has id': (r) => r.json('id') !== undefined && r.json('id') !== null,
    });

    errorRate.add(!success);

    // Sleep random short time to simulate think time between requests (10-100ms)
    sleep(Math.random() * 0.09 + 0.01);
}
