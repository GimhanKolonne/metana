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
        try {
            \Log::info('Starting Google Sheets initialization');
            $this->client = new Google_Client();
            $this->client->setApplicationName('Job Application Processor');
            $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
            $this->client->setAccessType('offline');
            
            \Log::info('Project ID: ' . env('GOOGLE_PROJECT_ID'));
            \Log::info('Client Email: ' . env('GOOGLE_CLIENT_EMAIL'));
            // Don't log sensitive info like private keys
            
            $privateKey = str_replace('\n', "\n", env('GOOGLE_PRIVATE_KEY'));
            
            $this->client->setAuthConfig([
                'type' => 'service_account',
                'project_id' => env('GOOGLE_PROJECT_ID'),
                'private_key_id' => env('GOOGLE_PRIVATE_KEY_ID'),
                'private_key' => $privateKey,
                'client_email' => env('GOOGLE_CLIENT_EMAIL'),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => env('GOOGLE_CLIENT_X509_CERT_URL'),
            ]);
            
            \Log::info('Auth config set successfully');
            $this->service = new Google_Service_Sheets($this->client);
            $this->spreadsheetId = env('GOOGLE_SHEET_ID');
            \Log::info('Google Sheets service initialized');
        } catch (\Exception $e) {
            \Log::error('Error initializing Google Sheets: ' . $e->getMessage());
            throw $e;
        }
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
