<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\AIController;
use Modules\AI\Http\Controllers\VoiceController;
use Modules\AI\Http\Controllers\WhatsAppController;

// ── WhatsApp webhook (no auth — verificato con HMAC) ─────────────────────────
Route::get('ai/whatsapp/webhook',  [WhatsAppController::class, 'verify'])->name('ai.whatsapp.verify');
Route::post('ai/whatsapp/webhook', [WhatsAppController::class, 'webhook'])->name('ai.whatsapp.webhook')
    ->withoutMiddleware(['auth:sanctum']);

// ── Twilio voice / SMS webhook (no auth — verificato con X-Twilio-Signature) ─
Route::post('ai/voice/inbound',    [VoiceController::class, 'inbound'])->name('ai.voice.inbound')
    ->withoutMiddleware(['auth:sanctum']);
Route::post('ai/sms/inbound',      [VoiceController::class, 'inboundSms'])->name('ai.sms.inbound')
    ->withoutMiddleware(['auth:sanctum']);

Route::middleware(['auth:sanctum'])->prefix('v1')->name('ai.')->group(function () {

    // ── AI Ticket Writer ──────────────────────────────────────────────────────
    Route::post('ai/draft-ticket',                  [AIController::class, 'draftTicket'])->name('draft-ticket');
    Route::post('ai/conversations/{conversation}/chat', [AIController::class, 'chat'])->name('chat');

    // ── WhatsApp outbound ─────────────────────────────────────────────────────
    Route::post('ai/whatsapp/send',                 [WhatsAppController::class, 'send'])->name('whatsapp.send');
    Route::post('ai/whatsapp/send-template',        [WhatsAppController::class, 'sendTemplate'])->name('whatsapp.send-template');
});
