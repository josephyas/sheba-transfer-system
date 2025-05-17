import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 100,
    duration: '30s',
    thresholds: {
        http_req_duration: ['p(95)<500'],
        http_reqs: ['rate>1000'],
    },
};

export default function () {
    // Choose distinct source and destination accounts
    const fromIndex = Math.floor(Math.random() * 4);
    // Ensure toIndex is different from fromIndex
    const toIndex = (fromIndex + 1 + Math.floor(Math.random() * 3)) % 4;

    const shebaNumbers = [
        'IR123456789012345678901234',
        'IR987654321098765432109876',
        'IR667654321098765432109876',
        'IR227654321098765432109876'
    ];

    // Add randomness to price to reduce duplicate transfers
    const price = 1000 + Math.floor(Math.random() * 9000);

    // Add timestamp to note to ensure uniqueness
    const note = `Performance test ${Date.now()}-${Math.random()}`;

    const payload = {
        price: price,
        fromShebaNumber: shebaNumbers[fromIndex],
        ToShebaNumber: shebaNumbers[toIndex],
        note: note
    };

    const res = http.post('http://localhost:8090/api/sheba', JSON.stringify(payload), {
        headers: { 'Content-Type': 'application/json' },
    });

    // Check status and log errors for debugging
    check(res, {
        'is status 201': (r) => r.status === 201,
    });

    if (res.status !== 201) {
        console.log(`Error: ${res.status} - ${res.body.substring(0, 100)}`);
    }

    sleep(0.05); // Slightly longer sleep to reduce concurrency issues
}
