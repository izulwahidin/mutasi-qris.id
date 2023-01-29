<?php
require_once('vendor/autoload.php');
use Wahidin\Mutasi\Qris;

try {
    // inisiasi class
    $qris = new Qris (
        'email@qris.id',  //* qris.id email, wajib
        'pw_qris_id',  //* qris.id password, wajib
        1,   // nominal transaksi yang ingin di cari, wajib
        null,   // tanggal awal     | format tanggal Y-m-d | default (null) = hari ini
        null,   // tanggal akhir    | format tanggal Y-m-d | default (null) = 31 hari setelah tanggal awal
        null,    // limit data mutasi perhalaman, default 20 | min 10 max 300 data
    );


    // ambil data mutasi
    $mutasi = $qris->mutasi();

    $result = [
        'status' => true,
        'data' => $mutasi
    ];
} catch (Exception $e) {
    http_response_code(400);
    $result = [
        'status' => false,
        'message' => $e->getMessage()
    ];
} finally {
    header("Content-Type: application/json");
    echo json_encode($result,JSON_PRETTY_PRINT);
}
