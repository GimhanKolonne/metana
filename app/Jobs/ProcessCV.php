<?php

namespace App\Jobs;

use App\Models\JobApplication;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

class ProcessCV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobApplication;

    public function __construct(JobApplication $jobApplication)
    {
        $this->jobApplication = $jobApplication;
    }

    public function handle()
    {
        // retrieve the cv file
        $cvPath = $this->jobApplication->cv_path;
        $cvContents = Storage::disk('s3')->get($cvPath);
        
        // save the file temp
        $tempPath = storage_path('app/temp_' . basename($cvPath));
        file_put_contents($tempPath, $cvContents);
        
        // extract text from cv according to the file extension
        $fileExtension = pathinfo($cvPath, PATHINFO_EXTENSION);
        $cvText = '';
        
        if ($fileExtension === 'pdf') {
            // Parse PDF
            $parser = new Parser();
            $pdf = $parser->parseFile($tempPath);
            $cvText = $pdf->getText();
        } elseif ($fileExtension === 'docx') {
            // Parse DOCX
            $phpWord = IOFactory::load($tempPath);
            $text = [];
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text[] = $element->getText();
                    }
                }
            }
            
            $cvText = implode(' ', $text);
        }
        
       
        unlink($tempPath);
        
        //  extract different sections from the cv 
        $extractedData = $this->extractDataFromCV($cvText);
        
        // update the job application with extracted data
        $this->jobApplication->update([
            'personal_info' => $extractedData['personal_info'],
            'education' => $extractedData['education'],
            'qualifications' => $extractedData['qualifications'],
            'projects' => $extractedData['projects'],
            'cv_processed' => true,
            'processed_timestamp' => now(),
        ]);
        
        // save it to google sheets
        $this->saveToGoogleSheets($extractedData);

        //  send webhook notification
        $this->sendWebhookNotification();
        
        // queue the emailk to be sent the next day
        SendFollowUpEmail::dispatch($this->jobApplication)
            ->delay(now()->addDay());
    }
    
    protected function extractDataFromCV($cvText)
    {
        $cvText = preg_replace('/\s+/', ' ', $cvText);
        $cvText = str_replace(["\r\n", "\r"], "\n", $cvText);
        
        
        $educationPatterns = [
            '/education(?:.*?)(?=skills|projects|qualifications|experience|contact|$)/is',
            '/academic(?:.*?)(?=skills|projects|qualifications|experience|contact|$)/is'
        ];
        
        $qualificationsPatterns = [
            '/skills\s*(?:&|and)?\s*(?:language)?(?:.*?)(?=education|projects|experience|contact|$)/is',
            '/qualifications(?:.*?)(?=education|projects|skills|experience|contact|$)/is',
            '/technical\s*skills(?:.*?)(?=education|projects|qualifications|experience|contact|$)/is'
        ];
        
        $projectsPatterns = [
            '/projects(?:.*?)(?=education|qualifications|skills|experience|contact|$)/is'
        ];
        
        $namePattern = '/^([A-Z\s]+)$/m'; 
        $emailPattern = '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i';
        $phonePattern = '/(\+?\d{1,4}[-\s]?\d{3,10}[-\s]?\d{3,10})/m';
        $linkedinPattern = '/(www\.linkedin\.com\/in\/[a-zA-Z0-9_-]+)/i';
        $addressPattern = '/(\d+\/\d+|\d+)\s[A-Za-z\s]+,\s[A-Za-z\s]+/m';
        
        
        $personalInfo = [
            'name' => $this->jobApplication->name,
            'email' => $this->jobApplication->email,
            'phone' => $this->jobApplication->phone,
            'linkedin' => '',
            'address' => ''
        ];
        
        // to extract name
        preg_match($namePattern, $cvText, $nameMatches);
        if (!empty($nameMatches[1])) {
            $personalInfo['name'] = trim($nameMatches[1]);
        }
        
        // to extract email
        preg_match($emailPattern, $cvText, $emailMatches);
        if (!empty($emailMatches[1])) {
            $personalInfo['email'] = trim($emailMatches[1]);
        }
        
        //to extract phone no
        preg_match($phonePattern, $cvText, $phoneMatches);
        if (!empty($phoneMatches[1])) {
            $personalInfo['phone'] = trim($phoneMatches[1]);
        }
        
    
        // extract sections
        $education = $this->extractSectionWithPatterns($cvText, $educationPatterns);
        $qualifications = $this->extractSectionWithPatterns($cvText, $qualificationsPatterns);
        $projects = $this->extractSectionWithPatterns($cvText, $projectsPatterns);
        
        // to process the extracted sections
        $educationItems = $this->processEducationSection($education);
        $qualificationItems = $this->processQualificationsSection($qualifications);
        $projectItems = $this->processProjectsSection($projects);
        
        return [
            'personal_info' => $personalInfo,
            'education' => $educationItems,
            'qualifications' => $qualificationItems,
            'projects' => $projectItems
        ];
    }
    
    protected function extractSectionWithPatterns($text, $patterns)
    {
        foreach ($patterns as $pattern) {
            preg_match($pattern, $text, $matches);
            if (!empty($matches[0])) {
                return trim($matches[0]);
            }
        }
        return '';
    }
    
    protected function processEducationSection($educationText)
    {
        $result = [];
        if (empty($educationText)) {
            return $result;
        }
        
        // eemove the section header
        $educationText = preg_replace('/(education|academic).+?\n/i', '', $educationText, 1);
        
        // split by year patterns or institution names
        $pattern = '/\b(20\d{2}|19\d{2})\s*(-|–|to)\s*(20\d{2}|19\d{2}|present)\b|\b[A-Z][a-zA-Z\s]+University|School|College|Institute\b/i';
        
        preg_match_all($pattern, $educationText, $matches, PREG_OFFSET_CAPTURE);
        
        if (empty($matches[0])) {
            $lines = explode("\n", $educationText);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strlen($line) > 10 && !preg_match('/^(education|academic)/i', $line)) {
                    $result[] = $line;
                }
            }
        } else {
            // process each education entry
            for ($i = 0; $i < count($matches[0]); $i++) {
                $start = $matches[0][$i][1];
                $end = ($i < count($matches[0]) - 1) ? $matches[0][$i + 1][1] : strlen($educationText);
                
                $entry = substr($educationText, $start, $end - $start);
                $entry = trim($entry);
                
                if (!empty($entry)) {
                    $result[] = $entry;
                }
            }
        }
        
        return $result;
    }
    
    protected function processQualificationsSection($qualificationsText)
    {
        $result = [];
        if (empty($qualificationsText)) {
            return $result;
        }
        
        // to remove the section header
        $qualificationsText = preg_replace('/(skills|qualifications|technical\s*skills).+?\n/i', '', $qualificationsText, 1);
        
        $lines = preg_split('/•|\n|,/', $qualificationsText);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 2) {
                $result[] = $line;
            }
        }
        
        return $result;
    }
    
    protected function processProjectsSection($projectsText)
    {
        $result = [];
        if (empty($projectsText)) {
            return $result;
        }
        
        // to remove the section header
        $projectsText = preg_replace('/projects.+?\n/i', '', $projectsText, 1);
        
        
        preg_match_all('/\n([A-Z][a-zA-Z\s]+(?:Website|System|Application|App|Platform))\n/m', $projectsText, $titleMatches);
        
        if (!empty($titleMatches[1])) {
            // to process each project by title
            foreach ($titleMatches[1] as $index => $title) {
                $start = strpos($projectsText, $title);
                $end = ($index < count($titleMatches[1]) - 1) 
                    ? strpos($projectsText, $titleMatches[1][$index + 1]) 
                    : strlen($projectsText);
                
                $projectDesc = substr($projectsText, $start, $end - $start);
                $result[] = trim($projectDesc);
            }
        } else {
            $paragraphs = preg_split('/\n{2,}/', $projectsText);
            
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (strlen($paragraph) > 15 && !preg_match('/^projects/i', $paragraph)) {
                    $result[] = $paragraph;
                }
            }
        }
        
        return $result;
    }
    
    protected function saveToGoogleSheets($extractedData)
    {
        try {
            $sheetsService = new \App\Services\GoogleSheetsService();
            
            // prepare the data to add to Google Sheets
            $values = [
                [
                    $this->jobApplication->name,
                    $this->jobApplication->email,
                    $this->jobApplication->phone,
                    $this->jobApplication->cv_public_link,
                    json_encode($extractedData['education']),
                    json_encode($extractedData['qualifications']),
                    json_encode($extractedData['projects']),
                    $this->jobApplication->processed_timestamp
                ]
            ];
            
            // add the data to Google Sheet
            $sheetsService->append('A1:H1', $values);
            
            \Log::info('Data successfully saved to Google Sheets');
        } catch (\Exception $e) {
            \Log::error('Error saving to Google Sheets: ' . $e->getMessage());
        }
    }
    
    protected function sendWebhookNotification()
    {
        // data payload
        $payload = [
            'cv_data' => [
                'personal_info' => $this->jobApplication->personal_info,
                'education' => $this->jobApplication->education,
                'qualifications' => $this->jobApplication->qualifications,
                'projects' => $this->jobApplication->projects,
                'cv_public_link' => $this->jobApplication->cv_public_link
            ],
            'metadata' => [
                'applicant_name' => $this->jobApplication->name,
                'email' => $this->jobApplication->email,
                'status' => $this->jobApplication->status, 
                'cv_processed' => $this->jobApplication->cv_processed,
                'processed_timestamp' => $this->jobApplication->processed_timestamp->toIso8601String()
            ]
        ];

        \Log::info('Webhook Payload:', $payload);

        try {
            // send the http request
            $response = Http::withHeaders([
                'X-Candidate-Email' => env('CANDIDATE_EMAIL', 'email@example.com') 
            ])->post('https://rnd-assignment.automations-3d6.workers.dev/', $payload);

            \Log::info('Webhook response:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // checking for successful status code
            if ($response->successful()) {
                \Log::info('Webhook notification sent successfully');
            } else {
                \Log::error('Failed to send webhook notification', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending webhook notification: ' . $e->getMessage());
        }
    }
}