<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $email = Str::lower($validated['email']);
        $source = $validated['source'] ?? 'footer';

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber && $subscriber->is_active) {
            return response()->json([
                'message' => 'You are already subscribed.',
                'subscriber' => $subscriber,
            ]);
        }

        if ($subscriber) {
            $subscriber->forceFill([
                'source' => $source,
                'is_active' => true,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'metadata' => $this->metadataFromRequest($request),
            ])->save();
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
                'source' => $source,
                'is_active' => true,
                'subscribed_at' => now(),
                'metadata' => $this->metadataFromRequest($request),
            ]);
        }

        return response()->json([
            'message' => 'Thanks for subscribing.',
            'subscriber' => $subscriber,
        ], Response::HTTP_CREATED);
    }

    protected function metadataFromRequest(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ];
    }
}
