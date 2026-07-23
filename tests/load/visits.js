import http from 'k6/http';
import { check } from 'k6';

export const options = {
    scenarios: {
        api_readiness: { executor: 'constant-vus', vus: 50, duration: '2m' },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        'http_req_duration{endpoint:employees}': ['p(95)<500'],
    },
};

const baseUrl = __ENV.BASE_URL;
const apiKey = __ENV.API_CLIENT_KEY;

export default function () {
    const response = http.get(`${baseUrl}/api/v1/employees`, {
        headers: { Accept: 'application/json', 'X-API-Key': apiKey },
        tags: { endpoint: 'employees' },
    });
    check(response, { 'status 200': (result) => result.status === 200 });
}
