<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\User;
use App\Services\PesananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PesananApiController extends Controller
{
    /** Riwayat pesanan milik pelanggan yang login. */
    public function index(Request $request): JsonResponse
    {
        $data = Pesanan::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($p) => [
                'id'         => $p->id,
                'nomor_meja' => $p->nomor_meja,
                'status'     => $p->status,
                'total'      => (float) $p->total,
                'waktu'      => $p->created_at->format('d/m/Y H:i'),
            ])->values();

        return response()->json(['data' => $data]);
    }

    /** Status dan struk satu pesanan. Hanya pesanan milik sendiri. */
    public function show(Request $request, int $id): JsonResponse
    {
        $pesanan = Pesanan::with('items')->find($id);

        if (!$pesanan) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }
        if ((int) $pesanan->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak membuka pesanan ini.'], 403);
        }

        return response()->json(['data' => $this->bentuk($pesanan)]);
    }

    public function store(Request $request, PesananService $service): JsonResponse
    {
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

        // rapikan item: [{menu_id, jumlah}, ...]
        $items = [];
        foreach ((array) $request->input('items', []) as $it) {
            if (!is_array($it)) {
                continue;
            }
            $items[] = [
                'menu_id' => $it['menu_id'] ?? null,
                'jumlah'  => $it['jumlah'] ?? null,
            ];
        }

        $user = $request->user();

        $pesanan = DB::transaction(function () use ($user, $service, $request, $items) {
            // kunci baris pelanggan supaya dua permintaan bersamaan tidak lolos keduanya
            User::whereKey($user->id)->lockForUpdate()->first();

            $masihBaru = Pesanan::where('user_id', $user->id)->where('status', 'Baru')->exists();
            if ($masihBaru) {
                throw ValidationException::withMessages([
                    'pesanan' => ['Anda masih memiliki pesanan berstatus Baru. Tunggu sampai pesanan diproses.'],
                ]);
            }

            return $service->buat($user, [
                'nomor_meja'   => (int) $request->input('nomor_meja'),
                'metode_bayar' => $request->input('metode_bayar'),
                'items'        => $items,
            ]);
        });

        return response()->json([
            'message' => 'Pesanan berhasil dibuat.',
            'data'    => $this->bentuk($pesanan->load('items')),
        ], 201);
    }

    private function bentuk(Pesanan $p): array
    {
        return [
            'id'           => $p->id,
            'waktu'        => $p->created_at->format('d/m/Y H:i'),
            'nomor_meja'   => $p->nomor_meja,
            'metode_bayar' => $p->metode_bayar,
            'status'       => $p->status,
            'items'        => $p->items->map(fn ($i) => [
                'nama'          => $i->nama_menu_saat_pesan,
                'harga_satuan'  => (float) $i->harga_satuan_saat_pesan,
                'jumlah'        => (int) $i->jumlah,
                'subtotal'      => (float) $i->subtotal_item,
            ])->values(),
            'subtotal'     => (float) $p->subtotal,
            'service'      => (float) $p->service,
            'pajak'        => (float) $p->pajak,
            'total'        => (float) $p->total,
        ];
    }
}