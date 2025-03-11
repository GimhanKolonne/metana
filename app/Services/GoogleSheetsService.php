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
    
    // Check if the credentials are stored as a base64 encoded string in the .env file
    $googleCredentials = env('GOOGLE_CREDENTIALS_JSON');
    
    if ($googleCredentials) {
        // Decode base64-encoded credentials if set in the environment
        $decodedCredentials = base64_decode($googleCredentials);
        $this->client->setAuthConfig(json_decode($decodedCredentials, true));
    } else {
        // If no base64 string is found, use the default credentials file path
        if (file_exists(storage_path('app/google-credentials.json'))) {
            $this->client->setAuthConfig(storage_path('app/google-credentials.json'));
        } else {
            throw new \Exception('Google credentials file not found.');
        }
    }

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