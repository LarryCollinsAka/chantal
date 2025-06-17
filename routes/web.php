<?php

use App\Http\Controllers\InfobipBotController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::post('/infobip/webhook', [InfobipBotController::class, 'handle']);


Route::get('/test-openai', [\App\Http\Controllers\OpenAiTestController::class, 'test']);
Route::post('/test-answers', [\App\Http\Controllers\InfobipBotController::class, 'handle']);

use Illuminate\Support\Facades\Log;

Route::get('/llm-test', function () {
    $prompt = [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'What is the capital of France?'],
    ];

    try {
        $response = app(\App\Services\LlmService::class)->generate($prompt);
        Log::info('LLM Response:', ['response' => $response]);
        return response($response ?? 'LLM returned nothing');
    } catch (\Throwable $e) {
        Log::error('LLM Exception:', ['error' => $e->getMessage()]);
        return response('LLM Error: ' . $e->getMessage(), 500);
    }
});

Route::get('/test-bot', [\App\Http\Controllers\InfobipBotController::class, 'handle']);

require __DIR__.'/auth.php';
