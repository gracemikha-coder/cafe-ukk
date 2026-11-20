<?php 

namespace App\Http\Controllers;

use App\Services\ImporDataAwalService;
use Illuminate\Http\Request;
use Iluminate\Support\Facades\File;
use Iluminate\Support\Str;
use RuntimeException;
use Throwable;

class ImporCsvController extends Controller
{
    private const BERKAS = [
        'kategori' => 'master_kategori_menu.csv',
        'bahan' => 'bahan.csv',
        'menu' => 'menu.csv',
        'resep' => 'resep.csv'
    ];

    public function index()
    {
        return view('manajer.impor');
    }

    public function proses(Request $request, ImporDataAwalService $impor)
    {
        $aturan = [];
        $pesan = [];
        foreach(array_keys(self::BERKAS)as $kolom){
             $aturan[$kolom] = 'required|file|extensions:csv,txt';
            $pesan["{$kolom}.required"]   = "Berkas {$kolom} wajib dipilih.";
            $pesan["{$kolom}.file"]       = "Berkas {$kolom} tidak valid.";
            $pesan["{$kolom}.extensions"] = "Berkas {$kolom} harus berformat CSV.";
        }
        $request->validate($aturan, $pesan);

        $folder = stronge_path('app/impor-'. Str::random(12));
        File::ensureDirectoryExixts($folder);

        try{
            foreach(self::BERKAS as $kolom => $nama){
                $request->file($kolom)->move($folder, $nama);
            }

            $hasil = $impor->impor($folder);
        }catch(RuntimeException $e){
            File::deleteDirectory($folder);
            return back()->withErrors(['impor' => $e->getMessage()]);
        }catch (Throwable $e){
            File::deleteDirectory($folder);
            report($e);
            return back()->withErrors(['impor' => 'Impor gagal. Periksa isi dan format berkas CSV.']);
        }

        File::deleteDirectory($folder);

        return back()->with('hasil_impor', $hasil);
    }
}