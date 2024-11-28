<?php

namespace Wahidin\Mutasi;

use Symfony\Component\DomCrawler\Crawler;
use InvalidArgumentException;
use RuntimeException;

class QrisTransactionFetcher
{
    private const BASE_URL = "https://merchant.qris.interactive.co.id";
    private const MIN_LIMIT = 10;
    private const MAX_LIMIT = 300;
    private const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    private string $username;
    private string $password;
    private string $cookieFile;
    private string $url;
    private ?string $postData = null;
    private string $fromDate;
    private string $toDate;
    private int $limit;
    private ?int $filter;

    public function __construct(
        string $username, 
        string $password, 
        ?int $filter, 
        ?string $fromDate = null, 
        ?string $toDate = null, 
        int $limit = 20
    ) {
        $this->validateInputs($username, $password, $filter, $fromDate, $toDate, $limit);

        $today = date('Y-m-d');
        $this->username = $username;
        $this->password = $password;
        $this->cookieFile = md5($username . $password) . "_cookie.txt";

        $this->fromDate = $fromDate ?? $today;
        $this->toDate = $toDate ?? date('Y-m-d', strtotime($this->fromDate . ' +31 day'));
        $this->limit = $limit;
        $this->filter = $filter;
    }

    private function validateInputs(
        string $username, 
        string $password, 
        ?int $filter, 
        ?string $fromDate, 
        ?string $toDate, 
        int $limit
    ): void {
        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException("Username and password are required.");
        }

        if ($fromDate !== null && !preg_match(self::DATE_PATTERN, $fromDate)) {
            throw new InvalidArgumentException("Invalid from date format. Use YYYY-MM-DD.");
        }

        if ($toDate !== null && !preg_match(self::DATE_PATTERN, $toDate)) {
            throw new InvalidArgumentException("Invalid to date format. Use YYYY-MM-DD.");
        }

        if ($limit < self::MIN_LIMIT || $limit > self::MAX_LIMIT) {
            throw new InvalidArgumentException(
                "Limit must be between " . self::MIN_LIMIT . " and " . self::MAX_LIMIT
            );
        }

        if ($filter !== null && $filter < 0) {
            throw new InvalidArgumentException("Filter amount must be a non-negative integer.");
        }
    }

    public function fetchTransactions(): array
    {
        $maxLoginAttempts = 3;
        
        for ($attempt = 1; $attempt <= $maxLoginAttempts; $attempt++) {
            $this->url = self::BASE_URL . "/m/kontenr.php?idir=pages/historytrx.php";
            $this->postData = $this->prepareMultipartFilterData();

            $response = $this->sendRequest();
            
            if (str_contains($response, 'Logout')) {
                return $this->parseTransactions($response);
            }

            $this->login();
        }

        throw new RuntimeException("Failed to login after {$maxLoginAttempts} attempts");
    }

    private function login(): void
    {
        // Remove existing cookie file
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }

        // Fetch login page to get secret token
        $this->url = self::BASE_URL . "/m/login.php";
        $this->postData = null;
        $rawToken = $this->sendRequest();

        preg_match('/name="secret_token" value="(.*?)">/', $rawToken, $matches);
        $secretToken = $matches[1] ?? throw new RuntimeException("Could not extract secret token");

        // Perform login
        $this->url = self::BASE_URL . "/m/login.php?pgv=go";
        $this->postData = $this->prepareMultipartLoginData($secretToken);
        $loginResponse = $this->sendRequest();

        if (!str_contains($loginResponse, '/historytrx.php')) {
            throw new RuntimeException("Login failed. Please check your credentials.");
        }
    }

    private function sendRequest(): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile
        ]);

        if ($this->postData !== null) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $this->postData,
                CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data; boundary=---------------------------']
            ]);
        }

        $result = curl_exec($ch);
        
        if ($result === false) {
            throw new RuntimeException("cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    private function prepareMultipartFilterData(): string
    {
        $boundary = '---------------------------' . uniqid();
        $body = '';

        $fields = [
            'datexbegin' => $this->fromDate,
            'datexend' => $this->toDate,
            'limitasidata' => (string)$this->limit,
            'searchtxt' => (string)($this->filter ?? ''),
            'Filter' => 'Filter'
        ];

        foreach ($fields as $key => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
            $body .= $value . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";
        return $body;
    }

    private function prepareMultipartLoginData(string $secretToken): string
    {
        $boundary = '---------------------------' . uniqid();
        $body = '';

        $fields = [
            'secret_token' => $secretToken,
            'username' => $this->username,
            'password' => $this->password,
            'submitBtn' => ''
        ];

        foreach ($fields as $key => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
            $body .= $value . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";
        return $body;
    }

    private function parseTransactions(string $response): array
    {
        $dom = new Crawler($response);
        $history = $dom->filter("#history > tbody > tr")->each(function (Crawler $node) {
            return $node->filter("td")->each(fn(Crawler $node) => $node->text());
        });

        return array_filter(array_map(function($transaction) {
            if (count($transaction) < 9) {
                return null;
            }

            return [
                'id' => (int) $transaction[0],
                'timestamp' => strtotime($transaction[1]),
                'tanggal' => $transaction[1],
                'nominal' => (int) $transaction[2],
                'status' => trim($transaction[3]),
                'inv_id' => (int) $transaction[4],
                'tanggal_settlement' => $transaction[5],
                'asal_transaksi' => $transaction[6],
                'nama_costumer' => $transaction[7],
                'rrn' => $transaction[8],
            ];
        }, $history));
    }
}