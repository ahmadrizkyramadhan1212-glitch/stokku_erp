<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Endpoint untuk Asisten AI "Falah" Warung Adijaya.
     * Meneruskan seluruh permintaan ke AiAssistantController terpadu berbasis Google Gemini AI asli.
     */
    public function sendMessage(Request $request, GeminiService $geminiService): JsonResponse
    {
        $aiController = app(AiAssistantController::class);
        return $aiController->chat($request, $geminiService);
    }
}
