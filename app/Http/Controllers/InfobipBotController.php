<?php

namespace App\Http\Controllers;

use App\Services\LlmService;
use App\Services\QdrantService;
use App\Services\WebSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InfobipBotController extends Controller
{
    public function handle(Request $request)
    {
        // Log the entire payload for debugging
        Log::info('Infobip Answers Payload', $request->all());

        // Try to extract a message from various payload keys
        $message = $request->input('message')
            ?? $request->input('text')
            ?? $request->input('userMessage')
            ?? $request->query('message')
            ?? null;

        if (!$message) {
            return response()->json([
                "actions" => [
                    [
                        "action" => "send-message",
                        "messages" => [
                            [ "text" => "No message found in payload." ]
                        ]
                    ]
                ]
            ]);
        }

        $mode = $this->detectMode($message);

        // Compose prompt depending on mode
        if ($mode === 'rag') {
            $embedding = $this->getEmbedding($message);
            $docs = app(QdrantService::class)->search($embedding);
            $context = implode("\n", $docs);
            $prompt = [
                ['role' => 'system', 'content' => "You are a helpful assistant. Use the following context to answer the user's question:\n" . $context],
                ['role' => 'user', 'content' => $message],
            ];
            $reply = app(LlmService::class)->generate($prompt, 'gpt-4.1');
        } elseif ($mode === 'web') {
            $searchResults = app(WebSearchService::class)->search($message);
            $prompt = [
                ['role' => 'system', 'content' => "You are a helpful assistant. Use the following web search results to answer the user's question:\n" . $searchResults],
                ['role' => 'user', 'content' => $message],
            ];
            $reply = app(LlmService::class)->generate($prompt, 'gpt-3.5-turbo');
        } else {
            // Default LLM mode, no extra context
            $prompt = [
                ['role' => 'system', 'content' => "You are a helpful assistant."],
                ['role' => 'user', 'content' => $message],
            ];
            $reply = app(LlmService::class)->generate($prompt, 'gpt-3.5-turbo');
        }

        if (!$reply) {
            $reply = "I'm sorry, I couldn't generate a response.";
        }

        return response()->json([
            "actions" => [
                [
                    "action" => "send-message",
                    "messages" => [
                        [ "text" => $reply ]
                    ]
                ]
            ]
        ]);
    }

    protected function detectMode($message)
    {
        if (str_starts_with($message, 'search:')) return 'web';
        if (str_starts_with($message, 'kb:')) return 'rag';
        return 'llm';
    }

    protected function getEmbedding($message)
    {
        $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.openai.com/v1/']);
        $response = $client->post('embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'input' => $message,
                'model' => 'text-embedding-ada-002'
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        return $data['data'][0]['embedding'];
    }
}