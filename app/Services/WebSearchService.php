<?php

namespace App\Services;

use GuzzleHttp\Client;

class WebSearchService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.bing.microsoft.com/v7.0/']);
        $this->apiKey = env('BING_SEARCH_KEY');
    }

    public function search($query)
    {
        $response = $this->client->get('search', [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
            ],
            'query' => [
                'q' => $query,
                'count' => 3,
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        return collect($data['webPages']['value'] ?? [])
            ->pluck('snippet')
            ->implode("\n");
    }
}