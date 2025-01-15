import http from 'k6/http';
import { sleep } from 'k6';

export const options = {
  stages: [
    { duration: '10s', target: 5 },  
    { duration: '1m', target: 20 }, 
    { duration: '10s', target: 0 }, 
  ],
};

export default function () {

  const url = 'http://127.0.0.1:8000/api/create/tenant';


  const params = {
    headers: {
      'Content-Type': 'application/json',
    },
  };

  const payload = JSON.stringify({
    name: 'Norvin Crujido',
    email: `test${Math.floor(Math.random() * 10000)}@test.com`, 
    password: 'password',
  });

  const response = http.post(url, payload, params);

  console.log(`Response status: ${response.status}`);
  sleep(1);
}
