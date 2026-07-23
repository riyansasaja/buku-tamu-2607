import http from 'k6/http';
import encoding from 'k6/encoding';
import { check } from 'k6';

export const options = {
    scenarios: { visit_creation: { executor: 'constant-vus', vus: 50, duration: '2m' } },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        'http_req_duration{endpoint:create_visit}': ['p(95)<2000'],
    },
};

const png = encoding.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', 'std');

export default function () {
    const unique = `${__VU}-${__ITER}-${Date.now()}`;
    const response = http.post(`${__ENV.BASE_URL}/api/v1/visits`, {
        guest_name: 'Tamu Load Test',
        address: 'Alamat sintetis staging',
        employee_id: __ENV.EMPLOYEE_ID,
        guest_whatsapp: __ENV.TEST_GUEST_WHATSAPP,
        visit_purpose: 'Pengujian performa staging',
        photo: http.file(png, 'test.png', 'image/png'),
    }, {
        headers: { Accept: 'application/json', 'X-API-Key': __ENV.API_CLIENT_KEY, 'Idempotency-Key': `load-${unique}` },
        tags: { endpoint: 'create_visit' },
    });
    check(response, { 'status 201': (result) => result.status === 201 });
}
