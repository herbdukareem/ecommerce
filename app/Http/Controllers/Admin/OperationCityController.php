<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OperationCity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OperationCityController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationCity::query()->withCount('areas');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $cities = $query
            ->orderByRaw('COALESCE(sort_order, 9999) asc')
            ->orderBy('name')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($cities);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:60|unique:operation_cities,code',
            'status' => 'sometimes|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['code'] = $data['code'] ?? Str::slug($data['name']);

        $city = OperationCity::create($data);

        return response()->json([
            'message' => 'City created successfully.',
            'city' => $city,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $city = OperationCity::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'code' => 'sometimes|string|max:60|unique:operation_cities,code,' . $city->id,
            'status' => 'sometimes|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $city->update($data);

        return response()->json([
            'message' => 'City updated successfully.',
            'city' => $city->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        $city = OperationCity::findOrFail($id);
        $city->delete();

        return response()->json([
            'message' => 'City deleted successfully.',
        ]);
    }
}
