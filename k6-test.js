import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metric to track error rate
export let errorRate = new Rate('errors');

export let options = {
    stages: [
        { duration: '10s', target: 50 },   // Ramp up to 50 users over 10s
        { duration: '10s', target: 200 },  // Ramp up to 200 users over 10s
        { duration: '10s', target: 400 },  // Ramp up to 400 users over 10s
        { duration: '20s', target: 800 },  // Ramp up to 800 users over 20s
        { duration: '10s', target: 0 },    // Ramp down to 0 users
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
        errors: ['rate<0.01'],            // Error rate should be less than 1%
    },
};

// Use the seeded account numbers from AccountSeeder
const sourceAccounts = [
    'IR123456789012345678901234',
    'IR987654321098765432109876',
    'IR667654321098765432109876',
    'IR227654321098765432109876'
];

function getRandomSourceAccount() {
    return sourceAccounts[Math.floor(Math.random() * sourceAccounts.length)];
}

function generateRandomSheba() {
    // Generate dummy IR numbers for destination (not in our system)
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
    let url = 'http://localhost:8090/api/sheba';

    // Use a real source account and random destination
    let payload = JSON.stringify({
        price: Math.floor(Math.random() * 10000) + 1000,  // random amount 1000-10999
        fromShebaNumber: getRandomSourceAccount(),
        toShebaNumber: generateRandomSheba(),
        note: 'Load test transfer',
    });

    let params = {
        headers: {
            'Content-Type': 'application/json',
            'X-Idempotency-Key': generateIdempotencyKey(),
        },
    };

    let res = http.post(url, payload, params);

    // Check for async response structure
    let success = check(res, {
        'status is 201': (r) => r.status === 201,
        'response has request': (r) => {
            try {
                const json = r.json();
                return json.request && json.request.id !== undefined && json.request.id !== null;
            } catch (e) {
                return false;
            }
        },
    });

    // If request failed, log the response for debugging
    if (!success && __VU === 1 && __ITER < 5) {
        console.log(`Failed response: ${res.status} - ${res.body}`);
    }

    errorRate.add(!success);

    // Sleep random short time to simulate think time between requests (10-100ms)
    sleep(Math.random() * 0.09 + 0.01);
}
