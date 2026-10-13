<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AnalyzeRequest;
use App\Services\ConversationAiService;
use App\Services\UsageMeter;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AiController extends Controller
{
    public function analyze(
        AnalyzeRequest $request,
        ConversationAiService $ai,
        UsageMeter $usage,
    ): JsonResponse {
        abort_if(
            strlen($request->getContent()) > 250000,
            413,
            'This analysis chunk is too large.',
        );
        $body = $request->validated();
        $reservation = $usage->reserve(
            $request,
            ($body['purpose'] ?? '') === 'freelance' ? 'freelance' : 'chat',
            $body['mode'],
            count($body['messages']),
        );
        $success = false;
        try {
            $result = $ai->analyze($body);
            $success = true;

            return response()->json($result);
        } catch (\Throwable $error) {
            return response()->json(
                [
                    'error' => get_class($error) === RuntimeException::class
                            ? $error->getMessage()
                            : 'Invalid request or AI response structure.',
                ],
                422,
            );
        } finally {
            $usage->finish($reservation, $success);
        }
    }
}
