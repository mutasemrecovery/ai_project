<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailReplyJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailReplyWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = env('EMAIL_REPLY_WEBHOOK_SECRET');

        if ($secret && ! hash_equals($secret, (string) $request->header('X-Webhook-Secret'))) {
            abort(403);
        }

        $data = $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
            'reply_body' => 'required|string',
        ]);

        ProcessEmailReplyJob::dispatch((int) $data['lead_id'], $data['reply_body']);

        return response()->json(['queued' => true]);
    }
}
