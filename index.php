<?php
require_once('QRIS.php');
use wahidin\mutasi\qris as wQris;

try {
    // inisiasi class
    $qris = new wQris (
        'email@gmail.com',  //* qris.id email
        'pass123',  //* qris.id password
        '2022-08-01',   // tanggal awal     | format tanggal Y-m-d | default (null) = hari ini
        null,   // tanggal akhir    | format tanggal Y-m-d | default (null) = hari ini
        null,    // limit data mutasi perhalaman, default 20 | min 10 max 300
        null,   // cari nominal transaksi, default (null)
    );


    // fetch data
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
}

header("Content-Type: application/json");
echo json_encode($result,JSON_PRETTY_PRINT);
