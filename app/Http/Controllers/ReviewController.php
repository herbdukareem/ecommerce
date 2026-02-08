<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Manage product reviews and ratings
 */
class ReviewController extends Controller
{
    /**
     * Get reviews for a product
     */
    public function index(Request $request, $productId)
    {
        $query = Review::with(['user', 'images'])
            ->where('product_id', $productId)
            ->where('is_approved', true);

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'recent');
        match($sortBy) {
            'helpful' => $query->withCount(['votes as helpful_votes' => function($q) {
                $q->where('is_helpful', true);
            }])->orderByDesc('helpful_votes'),
            'rating_high' => $query->orderByDesc('rating'),
            'rating_low' => $query->orderBy('rating'),
            default => $query->latest(),
        };

        $reviews = $query->paginate(10);

        // Get rating summary
        $summary = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            ')
            ->first();

        return response()->json([
            'reviews' => $reviews,
            'summary' => $summary,
        ]);
    }

    /**
     * Create a new review
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
            'images.*' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();

        // Check if product exists
        $product = Product::findOrFail($productId);

        // Check if user already reviewed this product
        if (Review::where('product_id', $productId)->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You have already reviewed this product',
            ], 422);
        }

        // Check if user purchased this product
        $verifiedPurchase = Order::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->whereHas('items.sku', function($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();

        $review = Review::create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'verified_purchase' => $verifiedPurchase,
            'is_approved' => true, // Auto-approve, or set to false for moderation
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $review->images()->create([
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review->load('images'),
        ], 201);
    }

    /**
     * Update a review
     */
    public function update(Request $request, $reviewId)
    {
        $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $review = Review::where('id', $reviewId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $review->update($request->only(['rating', 'title', 'comment']));

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review,
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, $reviewId)
    {
        $user = $request->user();
        $review = Review::where('id', $reviewId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Delete associated images
        foreach ($review->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ]);
    }

    /**
     * Vote on a review (helpful/not helpful)
     */
    public function vote(Request $request, $reviewId)
    {
        $request->validate([
            'is_helpful' => 'required|boolean',
        ]);

        $user = $request->user();

        // Update or create vote
        ReviewVote::updateOrCreate(
            [
                'review_id' => $reviewId,
                'user_id' => $user->id,
            ],
            [
                'is_helpful' => $request->is_helpful,
            ]
        );

        return response()->json([
            'message' => 'Vote recorded successfully',
        ]);
    }

    /**
     * Admin: Get all reviews for moderation
     */
    public function adminIndex(Request $request)
    {
        $query = Review::with(['user', 'product']);

        if ($request->filled('status')) {
            $isApproved = $request->status === 'approved';
            $query->where('is_approved', $isApproved);
        }

        $reviews = $query->latest()->paginate(20);

        return response()->json($reviews);
    }

    /**
     * Admin: Approve/reject a review
     */
    public function adminUpdateStatus(Request $request, $reviewId)
    {
        $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $review = Review::findOrFail($reviewId);
        $review->update(['is_approved' => $request->is_approved]);

        return response()->json([
            'message' => 'Review status updated successfully',
            'review' => $review,
        ]);
    }
}


