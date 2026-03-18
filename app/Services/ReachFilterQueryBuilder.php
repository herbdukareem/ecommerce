<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Build product queries with multiple filters and return facets.
 */
class ReachFilterQueryBuilder
{
    protected Builder $query;
    protected array $filters = [];

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Apply filters from request.
     */
    public function apply(Request $request): self
    {
        $this->filters = [
            'q' => $request->input('q'),
            'category_id' => $request->input('category_id'),
            'vendor_id' => $request->input('vendor_id'),
            'price_min' => $request->input('price_min'),
            'price_max' => $request->input('price_max'),
            'rating_min' => $request->input('rating_min'),
            'in_stock' => $request->boolean('in_stock'),
            'attributes' => (array) $request->input('attributes', []),
        ];

        if (!empty($this->filters['q'])) {
            $term = (string) $this->filters['q'];
            $this->query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                    ->orWhere('description', 'like', '%' . $term . '%');
            });
        }

        if (!empty($this->filters['category_id'])) {
            $categoryId = (int) $this->filters['category_id'];
            $this->query->whereHas('categories', function (Builder $q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        if (!empty($this->filters['vendor_id'])) {
            $this->query->where('vendor_id', (int) $this->filters['vendor_id']);
        }

        if ($this->filters['price_min'] !== null) {
            $this->query->where('base_price', '>=', (float) $this->filters['price_min']);
        }

        if ($this->filters['price_max'] !== null) {
            $this->query->where('base_price', '<=', (float) $this->filters['price_max']);
        }

        if (!empty($this->filters['rating_min'])) {
            $ratingMin = (float) $this->filters['rating_min'];
            $this->query->whereHas('reviews', function (Builder $q) use ($ratingMin) {
                $q->where('is_approved', true)
                    ->groupBy('product_id')
                    ->havingRaw('AVG(rating) >= ?', [$ratingMin]);
            });
        }

        if ($this->filters['in_stock']) {
            $this->query->whereHas('skus.stocks', function (Builder $q) {
                $q->whereRaw('on_hand - reserved > 0');
            });
        }

        foreach ($this->filters['attributes'] as $attribute => $values) {
            $valueList = is_array($values) ? $values : explode(',', (string) $values);
            $valueList = array_values(array_filter(array_map('trim', $valueList)));

            if (count($valueList) === 0) {
                continue;
            }

            $this->query->whereHas('skus.attributeValues', function (Builder $q) use ($attribute, $valueList) {
                $q->whereHas('attribute', function (Builder $aq) use ($attribute) {
                    $aq->where('name', $attribute);
                })->whereIn('value', $valueList);
            });
        }

        return $this;
    }

    /**
     * Get facet counts for filter UI.
     */
    public function facets(): array
    {
        $cacheKey = 'reach_facets:' . md5(json_encode($this->filters));

        return Cache::remember($cacheKey, 300, function (): array {
            $baseQuery = clone $this->query;
            $table = $baseQuery->getModel()->getTable();

            $price = (clone $baseQuery)
                ->selectRaw('MIN(' . $table . '.base_price) as min_price, MAX(' . $table . '.base_price) as max_price')
                ->first();

            $categoryFacet = (clone $baseQuery)
                ->join('category_product', 'category_product.product_id', '=', $table . '.id')
                ->join('categories', 'categories.id', '=', 'category_product.category_id')
                ->groupBy('categories.id', 'categories.name')
                ->selectRaw('categories.id, categories.name, COUNT(DISTINCT ' . $table . '.id) as product_count')
                ->orderByDesc('product_count')
                ->get();

            $attributeFacet = (clone $baseQuery)
                ->join('skus', 'skus.product_id', '=', $table . '.id')
                ->join('sku_attribute_value', 'sku_attribute_value.sku_id', '=', 'skus.id')
                ->join('attribute_values', 'attribute_values.id', '=', 'sku_attribute_value.attribute_value_id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->groupBy('attributes.name', 'attribute_values.value')
                ->selectRaw('attributes.name as attribute_name, attribute_values.value, COUNT(DISTINCT ' . $table . '.id) as product_count')
                ->orderBy('attributes.name')
                ->orderByDesc('product_count')
                ->get()
                ->groupBy('attribute_name')
                ->map(function ($rows) {
                    return $rows->map(function ($row) {
                        return [
                            'value' => $row->value,
                            'count' => (int) $row->product_count,
                        ];
                    })->values();
                });

            $ratingFacet = (clone $baseQuery)
                ->leftJoin('reviews', function ($join) use ($table) {
                    $join->on('reviews.product_id', '=', $table . '.id')
                        ->where('reviews.is_approved', '=', 1);
                })
                ->groupBy($table . '.id')
                ->selectRaw('COALESCE(AVG(reviews.rating), 0) as avg_rating')
                ->get()
                ->groupBy(function ($row) {
                    return (int) floor((float) $row->avg_rating);
                })
                ->map(fn ($rows) => $rows->count())
                ->sortKeysDesc()
                ->toArray();

            $inStockCount = (clone $baseQuery)
                ->whereExists(function ($q) use ($table) {
                    $q->select(DB::raw(1))
                        ->from('skus')
                        ->join('stocks', 'stocks.sku_id', '=', 'skus.id')
                        ->whereColumn('skus.product_id', $table . '.id')
                        ->whereRaw('stocks.on_hand - stocks.reserved > 0');
                })
                ->distinct($table . '.id')
                ->count($table . '.id');

            return [
                'price_range' => [
                    'min' => (float) ($price?->min_price ?? 0),
                    'max' => (float) ($price?->max_price ?? 0),
                ],
                'categories' => $categoryFacet,
                'attributes' => $attributeFacet,
                'ratings' => $ratingFacet,
                'availability' => [
                    'in_stock' => $inStockCount,
                ],
            ];
        });
    }

    public function get()
    {
        return $this->query->get();
    }
}