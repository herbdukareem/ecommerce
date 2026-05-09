<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeNewsletterAction($request, 'newsletter.view');

        $query = NewsletterSubscriber::query();

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where('email', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $subscribers = $query
            ->latest('subscribed_at')
            ->latest()
            ->paginate(min((int) $request->get('per_page', 20), 100));

        return response()->json([
            'data' => $subscribers->items(),
            'meta' => [
                'current_page' => $subscribers->currentPage(),
                'last_page' => $subscribers->lastPage(),
                'per_page' => $subscribers->perPage(),
                'total' => $subscribers->total(),
            ],
            'stats' => [
                'total' => NewsletterSubscriber::count(),
                'active' => NewsletterSubscriber::where('is_active', true)->count(),
                'inactive' => NewsletterSubscriber::where('is_active', false)->count(),
            ],
            'recent_campaigns' => NewsletterCampaign::query()
                ->with('sender:id,name,email')
                ->latest('sent_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, NewsletterSubscriber $subscriber)
    {
        $this->authorizeNewsletterAction($request, 'newsletter.manage');

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['is_active'];

        $subscriber->forceFill([
            'is_active' => $isActive,
            'subscribed_at' => $isActive ? ($subscriber->subscribed_at ?: now()) : $subscriber->subscribed_at,
            'unsubscribed_at' => $isActive ? null : now(),
        ])->save();

        return response()->json([
            'message' => $isActive ? 'Subscriber reactivated.' : 'Subscriber unsubscribed.',
            'subscriber' => $subscriber,
        ]);
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        $this->authorizeNewsletterAction(request(), 'newsletter.manage');

        $subscriber->delete();

        return response()->json([
            'message' => 'Subscriber deleted.',
        ]);
    }

    public function send(Request $request)
    {
        $this->authorizeNewsletterAction($request, 'newsletter.create');

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body_html' => ['required', 'string', 'min:10'],
        ]);

        $bodyHtml = $this->sanitizeHtml($validated['body_html']);
        $recipients = NewsletterSubscriber::query()
            ->where('is_active', true)
            ->pluck('email');

        if ($recipients->isEmpty()) {
            return response()->json([
                'message' => 'There are no active newsletter subscribers.',
            ], 422);
        }

        $campaign = NewsletterCampaign::create([
            'subject' => $validated['subject'],
            'body_html' => $bodyHtml,
            'status' => 'sent',
            'recipient_count' => $recipients->count(),
            'sent_by' => $request->user()?->id,
            'sent_at' => now(),
        ]);

        $recipients->chunk(50)->each(function ($emails) use ($validated, $bodyHtml) {
            foreach ($emails as $email) {
                Mail::html($bodyHtml, function ($message) use ($email, $validated) {
                    $message->to($email)->subject($validated['subject']);
                });
            }
        });

        return response()->json([
            'message' => "Newsletter sent to {$campaign->recipient_count} subscribers.",
            'campaign' => $campaign->load('sender:id,name,email'),
        ]);
    }

    public function export(): StreamedResponse
    {
        $this->authorizeNewsletterAction(request(), 'newsletter.view');

        $fileName = 'newsletter-subscribers-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Email', 'Status', 'Source', 'Subscribed At', 'Unsubscribed At']);

            NewsletterSubscriber::query()
                ->orderBy('email')
                ->chunk(500, function ($subscribers) use ($output) {
                    foreach ($subscribers as $subscriber) {
                        fputcsv($output, [
                            $subscriber->email,
                            $subscriber->is_active ? 'active' : 'inactive',
                            $subscriber->source,
                            optional($subscriber->subscribed_at)->toDateTimeString(),
                            optional($subscriber->unsubscribed_at)->toDateTimeString(),
                        ]);
                    }
                });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? '';
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? '';

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><blockquote><pre><code><hr>');
    }

    protected function authorizeNewsletterAction(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->hasAnyRole(['Super Admin', 'Admin'])
                || $user->can($permission)
            ),
            403,
            'You do not have permission to manage newsletters.'
        );
    }
}
