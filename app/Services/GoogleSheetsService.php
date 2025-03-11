<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class GoogleSheetsService
{
    protected $client;
    protected $service;
    protected $spreadsheetId;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Job Application Processor');
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');

        // Authenticate using environment variables
        $this->client->setAuthConfig([
            'type' => 'service_account',
            'project_id' => env('GOOGLE_PROJECT_ID'),
            'private_key_id' => env('GOOGLE_PRIVATE_KEY_ID'),
            'private_key' => env('GOOGLE_PRIVATE_KEY'),
            'client_email' => env('GOOGLE_CLIENT_EMAIL'),
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'auth_uri' => env('GOOGLE_AUTH_URI'),
            'token_uri' => env('GOOGLE_TOKEN_URI'),
            'auth_provider_x509_cert_url' => env('GOOGLE_AUTH_PROVIDER_X509_CERT_URL'),
            'client_x509_cert_url' => env('GOOGLE_CLIENT_X509_CERT_URL'),
        ]);

        $this->service = new Google_Service_Sheets($this->client);
        $this->spreadsheetId = env('GOOGLE_SHEET_ID');
    }

    public function append($range, $values)
    {
        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        $result = $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );

        return $result;
    }
}
