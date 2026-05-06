<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AIController extends Controller
{
    /**
     * Gửi tin nhắn cho AI Trợ lý
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        $sessionId = $request->input('session_id', Str::uuid()->toString());
        $message = $request->input('message');

        $aiService = new AIService();
        $reply = $aiService->chat($message, $sessionId, $user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'reply' => $reply,
                'session_id' => $sessionId,
            ]
        ]);
    }

    /**
     * Lấy lịch sử chat của session
     */
    public function history(Request $request): JsonResponse
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $aiService = new AIService();
        $history = $aiService->getHistory($sessionId);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Xóa lịch sử chat
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        if ($sessionId) {
            $aiService = new AIService();
            $aiService->clearHistory($sessionId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa lịch sử chat.'
        ]);
    }
}
