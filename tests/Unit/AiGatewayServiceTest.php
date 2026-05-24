<?php

declare(strict_types=1);

use App\Exceptions\AiGatewayAuthException;
use App\Exceptions\AiGatewayException;
use App\Exceptions\AiGatewayValidationException;
use App\Services\AiGatewayService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'services.ai_gateway.url' => 'http://gateway.test',
        'services.ai_gateway.key' => 'test-key',
    ]);
});

it('sends categorize request with correct headers and payload', function (): void {
    Http::fake([
        'gateway.test/categorize' => Http::response([
            'results' => [
                ['id' => 1, 'category' => 'Groceries'],
            ],
        ]),
    ]);

    $service = new AiGatewayService;
    $result = $service->categorize(
        [['id' => 1, 'description' => 'Supermarket', 'amount' => -50.0]],
        ['Groceries', 'Dining'],
    );

    expect($result)->toBe([['id' => 1, 'category' => 'Groceries']]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://gateway.test/categorize'
        && $request->header('X-API-Key')[0] === 'test-key'
        && $request['transactions'][0]['id'] === 1
        && $request['categories'] === ['Groceries', 'Dining']);
});

it('sends analyze request and returns response', function (): void {
    Http::fake([
        'gateway.test/analyze' => Http::response([
            'recommendations' => 'Cut spending on dining.',
            'model' => 'llama3.3:70b',
        ]),
    ]);

    $service = new AiGatewayService;
    $result = $service->analyze('2026-01-01', '2026-01-31', [
        ['category' => 'Dining', 'total' => 200.0],
    ]);

    expect($result['recommendations'])->toBe('Cut spending on dining.');
    expect($result['model'])->toBe('llama3.3:70b');
});

it('throws auth exception on 401', function (): void {
    Http::fake([
        'gateway.test/categorize' => Http::response('Unauthorized', 401),
    ]);

    $service = new AiGatewayService;
    $service->categorize([], []);
})->throws(AiGatewayAuthException::class);

it('throws validation exception on 422', function (): void {
    Http::fake([
        'gateway.test/categorize' => Http::response('Invalid payload', 422),
    ]);

    $service = new AiGatewayService;
    $service->categorize([], []);
})->throws(AiGatewayValidationException::class);

it('throws general exception on server error', function (): void {
    Http::fake([
        'gateway.test/categorize' => Http::response('Internal error', 500),
    ]);

    $service = new AiGatewayService;
    $service->categorize([], []);
})->throws(AiGatewayException::class);
