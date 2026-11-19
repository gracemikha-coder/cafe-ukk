// Alamat server bisa diatur dari layar login (disimpan di HP), tanpa mengubah kode.
// Bawaan 127.0.0.1 hanya berlaku di browser komputer. Di HP isi alamat IP komputer, misalnya http://192.168.1.5:8000
const ALAMAT_BAWAAN = 'http://127.0.0.1:8000';

export function ambilAlamatServer() {
  return localStorage.getItem('alamat_server') || ALAMAT_BAWAAN;
}

export function simpanAlamatServer(alamat) {
  let bersih = String(alamat || '').trim().replace(/\/+$/, '').replace(/\/api$/, '');

  if (!bersih) {
    localStorage.removeItem('alamat_server');
    return ALAMAT_BAWAAN;
  }

  if (!/^https?:\/\//i.test(bersih)) {
    bersih = 'http://' + bersih;
  }

  localStorage.setItem('alamat_server', bersih);
  return bersih;
}

export async function requestAPI(endpoint, data, method = 'POST', token = null) {
  let response;

  try {
    response = await fetch(`${ambilAlamatServer()}/api/${endpoint}`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      ...(data ? { body: JSON.stringify(data) } : {}),
    });
  } catch (e) {
    const error = new Error('Tidak dapat terhubung ke server. Periksa alamat server dan koneksi.');
    error.status = 0;
    throw error;
  }

  let result = {};
  try {
    result = await response.json();
  } catch (e) {
    result = {};
  }

  if (!response.ok) {
    const pesan =
      Object.values(result.errors || {}).flat().join('\n') ||
      result.message ||
      'Terjadi kesalahan.';

    const error = new Error(pesan);
    error.status = response.status;
    error.data = result;
    throw error;
  }

  return result;
}