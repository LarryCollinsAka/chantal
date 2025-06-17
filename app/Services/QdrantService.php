<?php

namespace App\Services;

use GuzzleHttp\Client;

class QdrantService
{
    protected $client;
    protected $collection;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => env('QDRANT_URL', 'http://localhost:6333')]);
        $this->collection = env('QDRANT_COLLECTION', 'docs');
    }

    public function search($embedding, $limit = 5)
    {
        $response = $this->client->post("/collections/{$this->collection}/points/search", [
            'json' => [
                'vector' => $embedding,
                'limit' => $limit,
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        return array_map(fn($point) => $point['payload']['text'], $data['result']);
    }
}