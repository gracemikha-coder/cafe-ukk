const DEFAULT_API_URL = 'http://127.0.0.1:8000/api';

// Menerima penulisan apa saja, lalu menghasilkan alamat yang benar:
//   192.168.1.7:8000            -> http://192.168.1.7:8000/api
//   http://192.168.1.7:8000     -> http://192.168.1.7:8000/api
//   http://192.168.1.7:8000/api -> http://192.168.1.7:8000/api
//   http://192.168.1.7:8000/api/ -> http://192.168.1.7:8000/api
export function normalisasiUrl(url) {
  let u = String(url || '').trim();
  if (!u) return DEFAULT_API_URL;
  if (!/^https?:\/\//i.test(u)) u = 'http://' + u;
  u = u.replace(/\/+$/, '');
  if (!/\/api$/i.test(u)) u += '/api';
  return u;
}

export function getApiUrl() {
  return normalisasiUrl(localStorage.getItem('api_url') || DEFAULT_API_URL);
}

export function setApiUrl(url) {
  if (String(url || '').trim()) localStorage.setItem('api_url', normalisasiUrl(url));
}

export async function requestAPI(endpoint, data, method = 'POST', token = null) {
  let response;

  try {
    response = await fetch(`${getApiUrl()}/${endpoint}`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      ...(data ? { body: JSON.stringify(data) } : {}),
    });
  } catch (e) {
    const err = new Error(`Tidak dapat terhubung ke server (${getApiUrl()}). Periksa alamat server dan koneksi.`);
    err.status = 0;
    throw err;
  }

  let result = {};
  try { result = await response.json(); } catch (e) { /* respons kosong */ }

  if (!response.ok) {
    const err = new Error(
      Object.values(result.errors || {}).flat().join('\n') ||
      result.message ||
      'Terjadi kesalahan.'
    );
    err.status = response.status;
    err.data = result;
    throw err;
  }

  return result;
}