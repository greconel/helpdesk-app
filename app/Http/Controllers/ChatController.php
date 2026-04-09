<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function ask(Request $request, ChatbotService $chatbot): JsonResponse
    {
        // Valideer de vraag
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        // Haal bestaand sessie token op of maak een nieuw aan
        $sessionToken = $request->session()->get('chat_token');

        if (!$sessionToken) {
            $sessionToken = Str::uuid()->toString();
            $request->session()->put('chat_token', $sessionToken);
        }

        // Vraag beantwoorden via de chatbot service
        $answer = $chatbot->answer(
            $request->question,
            $sessionToken
        );

        return response()->json([
            'answer' => $answer,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        // Verwijder het sessie token zodat 
        // een nieuw gesprek start
        $request->session()->forget('chat_token');

        return response()->json([
            'message' => 'Gesprek gereset.',
        ]);
    }
}