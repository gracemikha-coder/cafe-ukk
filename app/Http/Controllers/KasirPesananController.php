<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KasirPesananController extends Controller
{

    public function index(Request $request)
{
    $status = $request->query('status');
    $daftarStatus = ['Baru', 'Diproses', 'Selesai', 'Batal'];

    $pesanans = Pesanan::with('user')
        ->when(
            in_array($status, $daftarStatus),
            fn ($q) => $q->where('status', $status),
            fn ($q) => $q->whereIn('status', ['Baru', 'Diproses'])   // bawaan: Baru dan Diproses
        )
        ->orderByDesc('id')
        ->get();

    return view('kasir.pesanan.index', compact('pesanans', 'status', 'daftarStatus'));
}

    public function create()
    {
        $menus = Menu::with(['kategori', 'resepItems.bahan'])->orderBy('nama')->get();
        return view('kasir.pesanan.create', compact('menus'));
    }

    public function store(Request $request, PesananService $service)
    {
        // nomor meja & metode bayar (NTF-06, NTF-07)
        Validator::make($request->all(), [
            'nomor_meja'   => 'required|integer|between:1,10',
            'metode_bayar' => 'required|in:Tunai,QRIS',
        ], [
            'nomor_meja.required'   => 'Nomor meja harus antara 1 dan 10.',
            'nomor_meja.integer'    => 'Nomor meja harus antara 1 dan 10.',
            'nomor_meja.between'    => 'Nomor meja harus antara 1 dan 10.',
            'metode_bayar.required' => 'Pilih metode pembayaran: Tunai atau QRIS.',
            'metode_bayar.in'       => 'Pilih metode pembayaran: Tunai atau QRIS.',
        ])->validate();

        // kumpulkan item: hanya menu yang kolom jumlahnya diisi
        $items = [];
        foreach ((array) $request->input('jumlah', []) as $menuId => $jumlah) {
            if ($jumlah === null || $jumlah === '') {
                continue;
            }
            $items[] = ['menu_id' => $menuId, 'jumlah' => $jumlah];
        }

        // jika ada yang gagal (stok kurang, menu nonaktif, dll.) service melempar
        // ValidationException, otomatis kembali ke form dengan pesan dan isian lama
        $pesanan = $service->buat($request->user(), [
            'nomor_meja'   => (int) $request->nomor_meja,
            'metode_bayar' => $request->metode_bayar,
            'items'        => $items,
        ]);

        return redirect('/kasir/pesanan/' . $pesanan->id)->with('sukses', 'Pesanan berhasil dibuat');
    }

    public function show(Pesanan $pesanan)
    {
        $pesanan->load('items', 'user');
        return view('kasir.pesanan.struk', compact('pesanan'));
    }

        public function ubahStatus(Request $request, Pesanan $pesanan, PesananService $service)
    {
        $service->ubahStatus($pesanan, (string) $request->input('status'));
        return back()->with('sukses', 'Status pesanan #' . $pesanan->id . ' diperbarui');
    }

    public function batalkan(Pesanan $pesanan, PesananService $service)
    {
        $service->batalkan($pesanan);
        return back()->with('sukses', 'Pesanan #' . $pesanan->id . ' dibatalkan, stok bahan dikembalikan');
    }
}