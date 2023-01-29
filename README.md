# Library cek mutasi akun qris.id [PHP]
## Instalasi
Silahkan menginstall Library ini menggunakan composer
````
composer require wahidin/mutasi-qris
````
## Penggunakan
Simple nya seperti ini
````
use Wahidin\Mutasi\Qris;
$qris = new Qris (
    'email@qris.id',
    'pw_qris_id',
    1
);

$hasil = $qris->mutasi();

print_r($hasil);
````
untuk lebih jelasnya silahkan cek contoh.php :)
## Hasil jika run contoh.php
````
{
    "status": true,
    "data": [
        {
            "id": 21374519,
            "timestamp": 1661261715,
            "tanggal": "2022-08-23 15:35:15",
            "nominal": 1,
            "status": "Success Paid",
            "inv_id": 71912754271,
            "tanggal_settlement": "",
            "asal_transaksi": "Shopeepay",
            "nama_costumer": "62895609616655",
            "rrn": ""
        },
        {
            "id": 20849963,
            "timestamp": 1660632288,
            "tanggal": "2022-08-16 08:44:48",
            "nominal": 1,
            "status": "Success Paid",
            "inv_id": 21065100729,
            "tanggal_settlement": "",
            "asal_transaksi": "BRI",
            "nama_costumer": "ZLY WAHYUNI",
            "rrn": ""
        }
    ]
}
````