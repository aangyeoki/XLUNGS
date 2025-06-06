<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\XRayDiagnosis;
use Carbon\Carbon;

class XRayController extends Controller
{
    private $googleCredentials;

    public function __construct()
    {
        $this->middleware('auth');
        $this->googleCredentials = json_decode(file_get_contents(storage_path('app/credentials/krisna-project-459916-9a4283f559c7.json')), true);
    }

    public function index()
    {
        $diagnoses = XRayDiagnosis::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact('diagnoses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'xray_image' => 'required|image|mimes:jpeg,png,jpg|max:10240'
        ]);

        try {
            $imagePath = $request->file('xray_image')->store('xray_images', 'public');
            $fullImagePath = storage_path('app/public/' . $imagePath);

            $aiResult = $this->analyzeXRayWithVertexAI($fullImagePath);

            if (isset($aiResult['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $aiResult['error'],
                    'error_type' => $aiResult['error_type'] ?? 'AI_ERROR'
                ], 503);
            }

            $diagnosis = XRayDiagnosis::create([
                'user_id' => Auth::id(),
                'nama' => $request->nama,
                'image_path' => $imagePath,
                'ai_result' => json_encode($aiResult),
                'diagnosis' => $aiResult['predicted_disease'],
                'confidence' => $aiResult['confidence'],
                'explanation' => $aiResult['explanation'] ?? null,
                'created_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diagnosa berhasil disimpan',
                'data' => $diagnosis,
                'ai_result' => $aiResult
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getAccessToken()
    {
        try {
            $now = time();
            $exp = $now + 3600;

            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT'
            ];

            $payload = [
                'iss' => $this->googleCredentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $exp,
                'iat' => $now
            ];

            $headerEncoded = $this->base64UrlEncode(json_encode($header));
            $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

            $data = $headerEncoded . '.' . $payloadEncoded;
            $signature = '';

            $privateKey = openssl_pkey_get_private($this->googleCredentials['private_key']);
            openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $signatureEncoded = $this->base64UrlEncode($signature);

            $jwt = $data . '.' . $signatureEncoded;

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                return $tokenData['access_token'];
            } else {
                throw new \Exception('Failed to get access token: ' . $response->body());
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate access token: ' . $e->getMessage());
        }
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function analyzeXRayWithVertexAI($imagePath)
    {
        try {
            $accessToken = $this->getAccessToken();
            $imageContent = file_get_contents($imagePath);
            $base64Image = base64_encode($imageContent);

            $url = 'https://us-central1-aiplatform.googleapis.com/v1/projects/krisna-project-459916/locations/us-central1/publishers/google/models/gemini-2.0-flash-001:streamGenerateContent';

            $payload = [
                "contents" => [
                    [
                        "role" => "user",
                        "parts" => [
                            [
                                "inlineData" => [
                                    "mimeType" => "image/jpeg",
                                    "data" => $base64Image
                                ]
                            ],
                            [
                                "text" => "Analisis X-Ray dada ini dan berikan diagnosa untuk kemungkinan penyakit paru-paru. " .
                                    "Berikan penilaian untuk: Normal, Pneumonia, COVID-19, Tuberculosis, dan Fibrosis. " .
                                    "Format response:\n" .
                                    "Diagnosa Utama: [nama penyakit dengan confidence tertinggi]\n" .
                                    "Confidence: [persentase confidence 0-100]\n" .
                                    "Detail Analisis:\n" .
                                    "- Normal: [persentase]%\n" .
                                    "- Pneumonia: [persentase]%\n" .
                                    "- COVID-19: [persentase]%\n" .
                                    "- Tuberculosis: [persentase]%\n" .
                                    "- Fibrosis: [persentase]%\n" .
                                    "Penjelasan: [jelaskan temuan pada X-Ray yang mendukung diagnosa]"
                            ]
                        ]
                    ]
                ],
                "generationConfig" => [
                    "temperature" => 0.1,
                    "maxOutputTokens" => 3000,
                    "topP" => 0.8,
                    "stopSequences" => []
                ],
                "safetySettings" => [
                    ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "OFF"],
                    ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "OFF"],
                    ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "OFF"],
                    ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "OFF"]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(120)
                ->post($url, $payload);

            if (!$response->successful()) {
                return $this->handleApiError($response);
            }

            $responseBody = $response->body();


            if (strpos($responseBody, '"error":') !== false) {
                return $this->handleApiErrorFromResponse($responseBody);
            }

            $responseText = $this->parseStreamingResponse($responseBody);


            if (empty($responseText)) {
                return [
                    'error' => 'AI tidak memberikan respons yang valid. Silakan coba lagi.',
                    'error_type' => 'EMPTY_RESPONSE',
                    'debug_info' => 'Response body: ' . substr($responseBody, 0, 500)
                ];
            }

            return $this->parseGeminiResponse($responseText);
        } catch (\Exception $e) {

            if (strpos($e->getMessage(), 'timeout') !== false) {
                return [
                    'error' => 'Layanan AI membutuhkan waktu terlalu lama untuk merespons. Silakan coba lagi.',
                    'error_type' => 'TIMEOUT_ERROR'
                ];
            } elseif (strpos($e->getMessage(), 'Connection') !== false || strpos($e->getMessage(), 'cURL') !== false) {
                return [
                    'error' => 'Tidak dapat terhubung ke layanan AI. Periksa koneksi internet Anda.',
                    'error_type' => 'CONNECTION_ERROR'
                ];
            } else {
                return [
                    'error' => 'Terjadi kesalahan teknis saat menganalisis X-Ray: ' . $e->getMessage(),
                    'error_type' => 'TECHNICAL_ERROR'
                ];
            }
        }
    }

    private function handleApiError($response)
    {
        $statusCode = $response->status();
        $errorBody = $response->body();

        switch ($statusCode) {
            case 401:
                return [
                    'error' => 'Sistem AI sedang mengalami masalah otentikasi. Token telah diperbarui, silakan coba lagi.',
                    'error_type' => 'AUTHENTICATION_ERROR'
                ];
            case 403:
                return [
                    'error' => 'Akses ke layanan AI ditolak. Periksa izin atau quota API.',
                    'error_type' => 'PERMISSION_ERROR'
                ];
            case 429:
                return [
                    'error' => 'Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.',
                    'error_type' => 'RATE_LIMIT_ERROR'
                ];
            default:
                if ($statusCode >= 500) {
                    return [
                        'error' => 'Server AI sedang mengalami gangguan. Silakan coba lagi nanti.',
                        'error_type' => 'SERVER_ERROR'
                    ];
                } else {
                    return [
                        'error' => 'Terjadi kesalahan saat menghubungi layanan AI. Kode error: ' . $statusCode,
                        'error_type' => 'API_ERROR'
                    ];
                }
        }
    }

    private function handleApiErrorFromResponse($fullContent)
    {
        $errorData = json_decode($fullContent, true);
        if (isset($errorData['error'])) {
            $errorCode = $errorData['error']['code'] ?? 'unknown';
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            $errorStatus = $errorData['error']['status'] ?? 'UNKNOWN';

            if ($errorStatus === 'UNAUTHENTICATED' || $errorCode == 401) {
                return [
                    'error' => 'Token autentikasi AI telah kedaluwarsa. Silakan coba lagi.',
                    'error_type' => 'TOKEN_EXPIRED'
                ];
            } else {
                return [
                    'error' => 'Layanan AI mengalami masalah: ' . $errorMessage,
                    'error_type' => 'API_ERROR',
                    'technical_details' => "Code: {$errorCode}, Status: {$errorStatus}"
                ];
            }
        }

        return [
            'error' => 'Response error yang tidak dikenal',
            'error_type' => 'UNKNOWN_ERROR'
        ];
    }

    private function parseStreamingResponse($fullContent)
    {
        $responseText = '';

        $lines = explode("\n", $fullContent);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (strpos($line, 'data: ') === 0) {
                $line = substr($line, 6);
            }

            if (!$this->isValidJson($line)) {
                continue;
            }

            $json = json_decode($line, true);

            if ($json && isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                $responseText .= $json['candidates'][0]['content']['parts'][0]['text'];
            }
        }

        if (empty($responseText)) {

            $jsonObjects = preg_split('/(?<=})\s*,\s*(?=\{)/', $fullContent);

            foreach ($jsonObjects as $jsonStr) {
                $jsonStr = trim($jsonStr);
                if (empty($jsonStr)) continue;

                if (!$this->isValidJson($jsonStr)) {
                    continue;
                }

                $json = json_decode($jsonStr, true);
                if ($json && isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    $responseText .= $json['candidates'][0]['content']['parts'][0]['text'];
                }
            }
        }

        if (empty($responseText)) {
            preg_match_all('/"text":\s*"([^"]*(?:\\.[^"]*)*)"/', $fullContent, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $textMatch) {
                    $decodedText = json_decode('"' . $textMatch . '"');
                    if ($decodedText !== null) {
                        $responseText .= $decodedText;
                    }
                }
            }
        }

        if (empty($responseText)) {

            preg_match_all('/\{[^}]*"text"[^}]*\}/', $fullContent, $matches);

            foreach ($matches[0] as $jsonMatch) {
                $json = json_decode($jsonMatch, true);
                if ($json && isset($json['text'])) {
                    $responseText .= $json['text'];
                }
            }
        }
        return $responseText;
    }

    private function isValidJson($string)
    {
        if (empty($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function parseGeminiResponse($responseText)
    {

        if (empty($responseText)) {
            return [
                'error' => 'AI tidak memberikan hasil analisis. Silakan coba upload gambar X-Ray yang berbeda.',
                'error_type' => 'EMPTY_AI_RESPONSE'
            ];
        }

        $predictedDisease = 'Unknown';
        $confidence = 0;
        $allPredictions = [];
        $explanation = '';

        if (preg_match('/Diagnosa Utama[:\s\*]*([^\n]+)/i', $responseText, $match)) {
            $predictedDisease = trim(strip_tags($match[1]));
        }

        if (preg_match('/Confidence[:\s\*]*([0-9]+)/i', $responseText, $match)) {
            $confidence = (int)$match[1];
        }


        $diseases = ['Normal', 'Pneumonia', 'COVID-19', 'Tuberculosis', 'Fibrosis'];
        foreach ($diseases as $disease) {
            if (preg_match('/' . $disease . '[:\s\*]*([0-9]+)%/i', $responseText, $match)) {
                $allPredictions[$disease] = (int)$match[1];
            }
        }

        if (preg_match('/Penjelasan[:\s\*]*(.+)/is', $responseText, $match)) {
            $rawExplanation = trim($match[1]);

            $cleanExplanation = str_replace('*', '', $rawExplanation);
            $cleanExplanation = preg_replace('/\*\*Penting:\*\*.*$/is', '', $cleanExplanation);

            $cleanExplanation = preg_replace('/\s+/', ' ', $cleanExplanation);
            $cleanExplanation = trim($cleanExplanation);
            $cleanExplanation = preg_replace('/\n\s*([A-Z][^:]+:)/', "\n• $1", $cleanExplanation);

            $explanation = $cleanExplanation;
        }
        return [
            'predicted_disease' => $predictedDisease,
            'confidence' => $confidence,
            'all_predictions' => $allPredictions,
            'explanation' => $explanation,
            'analysis_timestamp' => now()->toIso8601String(),
            'raw_response' => $responseText
        ];
    }
}
