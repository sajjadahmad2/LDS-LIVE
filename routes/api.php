<?php

use Illuminate\Http\Request;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('webhook/{campaign_id}', [WebhookController::class, 'handleWebhookUrl'])->name('api.webhook.lead');
Route::middleware([])->get('agent/consent', [WebhookController::class, 'getAgentConsent'])->name('agent.consent');
Route::post('webhook/contact', [WebhookController::class, 'Contactwebhook'])->name('api.webhook.content');
Route::middleware([])->get('agent/carriertypes', [WebhookController::class, 'getAgentCarrierTypes'])->name('agent.carrier.type');

