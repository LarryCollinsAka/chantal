<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Codewithkyrian\Transformers\Transformers;
use Illuminate\Support\Facades\Log;

use function Codewithkyrian\Transformers\Pipelines\remote_pipeline;

class LlmService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1/responses';
    protected $defaultModel = 'gpt-4.1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
    }

    public function generate($prompt, $model = null, $options = [])
    {
        $model = $model ?: $this->defaultModel;
        // Only accept string prompts for this endpoint!
        if (is_array($prompt)) {
            // Convert chat array to a single string (simple join)
            $prompt = collect($prompt)->pluck('content')->implode("\n");
        }

        $payload = array_merge([
            'model' => $model,
            'input' => $prompt,
        ], $options);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['text'] ?? null;
            } else {
                Log::error('OpenAI API error', ['body' => $response->body()]);
                return 'Sorry, the LLM could not answer right now.';
            }
        } catch (\Throwable $e) {
            Log::error('OpenAI Exception', ['error' => $e->getMessage()]);
            return 'Sorry, the LLM failed: ' . $e->getMessage();
        }
    }
}