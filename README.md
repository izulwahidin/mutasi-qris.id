# QRIS Transaction Fetcher

## Overview

QRIS Transaction Fetcher is a PHP library that allows merchants to retrieve transaction history from the QRIS (Quick Response Code Indonesian Standard) merchant portal. This library simplifies the process of fetching transaction data through automated login and data extraction.

## Features

- 🔐 Secure login to QRIS merchant portal
- 📅 Flexible date range selection for transactions
- 🔢 Transaction amount filtering
- 🧩 Comprehensive error handling
- 📊 Parsed transaction details

## Requirements

- PHP 7.4+
- Symfony DomCrawler Component
- cURL Extension
- Composer

## Installation

Install the library using Composer:

```bash
composer require wahidin/mutasi
```

## Usage Example

```php
<?php
use Wahidin\Mutasi\QrisTransactionFetcher;

try {
    // Initialize the transaction fetcher
    $fetcher = new QrisTransactionFetcher(
        'your_username',     // Merchant portal username
        'your_password',     // Merchant portal password
        100000,              // Optional: Filter transactions above this amount
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
```

## Transaction Data Structure

Each transaction is an associative array with the following keys:

- `id`: Transaction internal ID
- `timestamp`: Unix timestamp of transaction
- `tanggal`: Transaction date
- `nominal`: Transaction amount
- `status`: Transaction status
- `inv_id`: Invoice ID
- `tanggal_settlement`: Settlement date
- `asal_transaksi`: Transaction origin
- `nama_costumer`: Customer name
- `rrn`: Reference number

## Error Handling

The library throws specific exceptions for various scenarios:

- `InvalidArgumentException`: For incorrect input parameters
- `RuntimeException`: For login failures or network issues

## Configuration Options

### Constructor Parameters

- `$username` (required): Merchant portal username
- `$password` (required): Merchant portal password
- `$filter` (optional): Minimum transaction amount filter
- `$fromDate` (optional): Start date for transaction history
- `$toDate` (optional): End date for transaction history
- `$limit` (optional): Maximum number of transactions to fetch (10-300)

## Limitations

- Requires active internet connection
- Depends on the current structure of the QRIS merchant portal
- Maximum of 300 transactions per request

## Security Notes

- Stores temporary cookies for authentication
- Recommends using environment variables for credentials
- Implements basic input validation
