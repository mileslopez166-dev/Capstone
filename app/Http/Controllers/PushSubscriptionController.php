<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')), 503);

        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', Rule::in(['aesgcm', 'aes128gcm'])],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['content_encoding'] ?? 'aes128gcm',
        );

        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
        ]);

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['subscribed' => false]);
    }
}
