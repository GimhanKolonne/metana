<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class ProcessSheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $spreadsheetId;
    protected $privateKey;
    protected $projectId;
    protected $clientEmail;
    protected $privateKeyId;
    protected $clientId;
    protected $clientCertUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, $spreadsheetId, $privateKey, $projectId, $clientEmail, $privateKeyId, $clientId, $clientCertUrl)
    {
        $this->data = $data;
        $this->spreadsheetId = $spreadsheetId;
        $this->privateKey = $privateKey;
        $this->projectId = $projectId;
        $this->clientEmail = $clientEmail;
        $this->privateKeyId = $privateKeyId;
        $this->clientId = $clientId;
        $this->clientCertUrl = $clientCertUrl;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            \Log::info('Starting Google Sheets processing in queue job');
            
            // Create a new Google client in the job context
            $client = new Google_Client();
            $client->setApplicationName('Job Application Processor');
            $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');
            
            // Fix newlines in private key
            $privateKey = str_replace('\n', "\n", $this->privateKey);
            
            // Configure with the passed credentials
            $client->setAuthConfig([
                'type' => 'service_account',
                'project_id' => $this->projectId,
                'private_key_id' => $this->privateKeyId,
                'private_key' => $privateKey,
                'client_email' => $this->clientEmail,
                'client_id' => $this->clientId,
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => $this->clientCertUrl,
            ]);
            
            \Log::info('Auth config set in job');
            
            // Create the sheet service
            $service = new Google_Service_Sheets($client);
            
            // Create the value range with our data
            $body = new Google_Service_Sheets_ValueRange([
                'values' => [$this->data]
            ]);
            
            $params = [
                'valueInputOption' => 'RAW'
            ];
            
            // Append to the sheet
            $result = $service->spreadsheets_values->append(
                $this->spreadsheetId,
                'A1:H1',
                $body,
                $params
            );
            
            \Log::info('Data successfully saved to Google Sheets from queue job');
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error in queue job saving to Google Sheets: ' . $e->getMessage());
            throw $e;
        }
    }
}