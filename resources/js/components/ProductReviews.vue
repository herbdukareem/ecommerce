<template>
  <div class="mt-8">
    <h2 class="text-2xl font-bold mb-6">Customer Reviews</h2>

    <!-- Rating Summary -->
    <div v-if="summary" class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="text-center">
          <div class="text-5xl font-bold text-gray-900">{{ summary.average_rating?.toFixed(1) || '0.0' }}</div>
          <div class="flex justify-center my-2">
            <StarRating :rating="summary.average_rating" :size="'large'" />
          </div>
          <p class="text-gray-600">{{ summary.total_reviews }} reviews</p>
        </div>
        <div class="space-y-2">
          <RatingBar :stars="5" :count="summary.five_star" :total="summary.total_reviews" />
          <RatingBar :stars="4" :count="summary.four_star" :total="summary.total_reviews" />
          <RatingBar :stars="3" :count="summary.three_star" :total="summary.total_reviews" />
          <RatingBar :stars="2" :count="summary.two_star" :total="summary.total_reviews" />
          <RatingBar :stars="1" :count="summary.one_star" :total="summary.total_reviews" />
        </div>
      </div>
    </div>

    <!-- Write Review Button -->
    <div class="mb-6">
      <button
        @click="showReviewForm = !showReviewForm"
        class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700"
      >
        Write a Review
      </button>
    </div>

    <!-- Review Form -->
    <div v-if="showReviewForm" class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold mb-4">Write Your Review</h3>
      <form @submit.prevent="submitReview">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
          <StarRating :rating="newReview.rating" :editable="true" @update:rating="newReview.rating = $event" />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
          <input
            v-model="newReview.title"
            type="text"
            class="w-full border border-gray-300 rounded-lg px-4 py-2"
            placeholder="Summarize your experience"
          />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Review</label>
          <textarea
            v-model="newReview.comment"
            rows="4"
            class="w-full border border-gray-300 rounded-lg px-4 py-2"
            placeholder="Share your thoughts about this product"
          ></textarea>
        </div>
        <div class="flex space-x-3">
          <button
            type="submit"
            class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700"
            :disabled="submitting"
          >
            {{ submitting ? 'Submitting...' : 'Submit Review' }}
          </button>
          <button
            type="button"
            @click="showReviewForm = false"
            class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Reviews List -->
    <div class="space-y-4">
      <div
        v-for="review in reviews"
        :key="review.id"
        class="bg-white rounded-lg shadow p-6"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <div class="flex items-center space-x-2">
              <span class="font-semibold">{{ review.user.name }}</span>
              <span v-if="review.verified_purchase" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                Verified Purchase
              </span>
            </div>
            <StarRating :rating="review.rating" :size="'small'" />
          </div>
          <span class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</span>
        </div>
        <h4 v-if="review.title" class="font-semibold mb-2">{{ review.title }}</h4>
        <p class="text-gray-700 mb-4">{{ review.comment }}</p>
        <div class="flex items-center space-x-4 text-sm">
          <button
            @click="voteReview(review.id, true)"
            class="text-gray-600 hover:text-indigo-600"
          >
            👍 Helpful ({{ review.helpful_count }})
          </button>
          <button
            @click="voteReview(review.id, false)"
            class="text-gray-600 hover:text-indigo-600"
          >
            👎 Not Helpful ({{ review.not_helpful_count }})
          </button>
        </div>
      </div>
    </div>

    <div v-if="!reviews.length && !loading" class="text-center text-gray-500 py-8">
      No reviews yet. Be the first to review this product!
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import StarRating from './StarRating.vue';
import RatingBar from './RatingBar.vue';

const props = defineProps({
  productId: {
    type: Number,
    required: true,
  },
});

const reviews = ref([]);
const summary = ref(null);
const showReviewForm = ref(false);
const loading = ref(false);
const submitting = ref(false);

const newReview = ref({
  rating: 5,
  title: '',
  comment: '',
});

const loadReviews = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get(`/api/products/${props.productId}/reviews`);
    reviews.value = data.reviews.data;
    summary.value = data.summary;
  } catch (error) {
    console.error('Failed to load reviews:', error);
  } finally {
    loading.value = false;
  }
};

const submitReview = async () => {
  if (!newReview.value.rating) {
    alert('Please select a rating');
    return;
  }

  submitting.value = true;
  try {
    await axios.post(`/api/products/${props.productId}/reviews`, newReview.value);
    alert('Review submitted successfully!');
    showReviewForm.value = false;
    newReview.value = { rating: 5, title: '', comment: '' };
    loadReviews();
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to submit review');
  } finally {
    submitting.value = false;
  }
};

const voteReview = async (reviewId, isHelpful) => {
  try {
    await axios.post(`/api/reviews/${reviewId}/vote`, { is_helpful: isHelpful });
    loadReviews();
  } catch (error) {
    console.error('Failed to vote:', error);
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

onMounted(() => {
  loadReviews();
});


