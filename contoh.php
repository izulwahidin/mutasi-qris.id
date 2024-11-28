<?php
require("vendor/autoload.php");
use Wahidin\Mutasi\QrisTransactionFetcher;

try {
    // Initialize the transaction fetcher
    $fetcher = new QrisTransactionFetcher(
        'izlwhd@gmail.com',     // Merchant portal username
        'izuladgj123',     // Merchant portal password
        1,              // Optional: Filter transactions above this amount
        '2023-01-01',        // Optional: Start date
        '2023-02-01',        // Optional: End date
        50                   // Optional: Limit number of transactions (default: 20)
    );

    // Fetch transactions
    $transactions = $fetcher->fetchTransactions();

    // Process transactions
    foreach ($transactions as $transaction) {
        echo "Transaction ID: " . $transaction['id'] . "\n";
        echo "Amount: Rp " . number_format($transaction['nominal']) . "\n";
        echo "Customer: " . $transaction['nama_costumer'] . "\n";
        echo "---\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}