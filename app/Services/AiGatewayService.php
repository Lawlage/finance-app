<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AiGatewayAuthException;
use App\Exceptions\AiGatewayException;
use App\Exceptions\AiGatewayValidationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AiGatewayService
{
    private readonly string $baseUrl;

    private readonly string $apiKey;

    public function __construct()
    {
        /** @var string $url */
        $url = config('services.ai_gateway.url');
        /** @var string $key */
        $key = config('services.ai_gateway.key');

        $this->baseUrl = rtrim($url, '/');
        $this->apiKey = $key;
    }

    /**
     * Categorize a batch of transactions via the AI gateway.
     *
     * @param  array<int, array{id: int, description: string, amount: float}>  $transactions
     * @param  array<int, string>  $categories
     * @return array<int, array{id: int, category: string}>
     */
    public function categorize(array $transactions, array $categories): array
    {
        $response = $this->post('/categorize', [
            'transactions' => $transactions,
            'categories' => $categories,
        ]);

        /** @var array<int, array{id: int, category: string}> */
        return $response->json('results');
    }

    /**
     * Analyze spending data and return budget recommendations.
     *
     * @param  array<int, array{category: string, total: float}>  $summary
     * @return array{recommendations: string, model: string}
     */
    public function analyze(string $periodStart, string $periodEnd, array $summary): array
    {
        $response = $this->post('/analyze', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'summary' => $summary,
        ]);

        /** @var array{recommendations: string, model: string} */
        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function post(string $endpoint, array $data): Response
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->post($this->baseUrl.$endpoint, $data);

        if ($response->status() === 401) {
            throw new AiGatewayAuthException('AI Gateway authentication failed: invalid API key');
        }

        if ($response->status() === 422) {
            throw new AiGatewayValidationException(
                'AI Gateway validation error: '.$response->body()
            );
        }

        if ($response->failed()) {
            throw new AiGatewayException(
                'AI Gateway request failed with status '.$response->status().': '.$response->body()
            );
        }

        return $response;
    }
}
