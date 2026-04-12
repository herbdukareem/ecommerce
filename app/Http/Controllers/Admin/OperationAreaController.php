<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OperationArea;
use Illuminate\Http\Request;

class OperationAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationArea::query()->with('city:id,name');

        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->input('city_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $areas = $query
            ->orderByRaw('COALESCE(sort_order, 9999) asc')
            ->orderBy('name')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($areas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'city_id' => 'required|integer|exists:operation_cities,id',
            'name' => 'required|string|max:120',
            'delivery_fee' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $exists = OperationArea::query()
            ->where('city_id', $data['city_id'])
            ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Area name already exists in this city.',
            ], 422);
        }

        $area = OperationArea::create($data);

        return response()->json([
            'message' => 'Area created successfully.',
            'area' => $area,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $area = OperationArea::findOrFail($id);

        $data = $request->validate([
            'city_id' => 'sometimes|integer|exists:operation_cities,id',
            'name' => 'sometimes|string|max:120',
            'delivery_fee' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $cityId = $data['city_id'] ?? $area->city_id;
        $name = $data['name'] ?? $area->name;

        $exists = OperationArea::query()
            ->where('city_id', $cityId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $area->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Area name already exists in this city.',
            ], 422);
        }

        $area->update($data);

        return response()->json([
            'message' => 'Area updated successfully.',
            'area' => $area->fresh('city:id,name'),
        ]);
    }

    public function destroy(int $id)
    {
        $area = OperationArea::findOrFail($id);
        $area->delete();

        return response()->json([
            'message' => 'Area deleted successfully.',
        ]);
    }
}
