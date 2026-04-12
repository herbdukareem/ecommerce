<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DispatchTimeSlot;
use Illuminate\Http\Request;

class DispatchTimeSlotController extends Controller
{
    public function index(Request $request)
    {
        $query = DispatchTimeSlot::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $slots = $query
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $slot = DispatchTimeSlot::create($data);

        return response()->json([
            'message' => 'Dispatch time slot created successfully.',
            'slot' => $slot,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $slot = DispatchTimeSlot::findOrFail($id);

        $data = $request->validate([
            'label' => 'sometimes|string|max:120',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if (isset($data['start_time'], $data['end_time']) && strtotime($data['start_time']) >= strtotime($data['end_time'])) {
            return response()->json([
                'message' => 'Start time must be before end time.',
            ], 422);
        }

        $data['updated_by'] = $request->user()?->id;
        $slot->update($data);

        return response()->json([
            'message' => 'Dispatch time slot updated successfully.',
            'slot' => $slot->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        $slot = DispatchTimeSlot::findOrFail($id);
        $slot->delete();

        return response()->json([
            'message' => 'Dispatch time slot deleted successfully.',
        ]);
    }
}
