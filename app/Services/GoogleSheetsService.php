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
    
        // Fix the private key format
        $privateKey = str_replace('\\n', "\n", env('GOOGLE_PRIVATE_KEY'));
        
        // Authenticate using environment variables
        $this->client->setAuthConfig([
            'type' => 'service_account',
            'project_id' => env('GOOGLE_PROJECT_ID'),
            'private_key_id' => env('GOOGLE_PRIVATE_KEY_ID'),
            'private_key' => $privateKey,
            'client_email' => env('GOOGLE_CLIENT_EMAIL'),
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth', // Default value
            'token_uri' => 'https://oauth2.googleapis.com/token', // Default value
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs', // Default value
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
