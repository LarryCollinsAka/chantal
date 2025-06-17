<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class OpenAiTestController extends Controller
{
   public function test()
    {
        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'), // key in .env
                'Content-Type'  => 'application/json',
            ]
        ]);

        $response = $client->post('chat/completions', [
            'json' => [
                'model' => 'gpt-4o-mini',
                'store' => true,
                'messages' => [
                    ['role' => 'user', 'content' => 'Who is the president of Cameroon?'],
                ],
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return response($data['choices'][0]['message']['content'] ?? 'No response');
    }
}
