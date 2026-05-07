<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DispatchRider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class DispatchRiderController extends Controller
{
    public function index(Request $request)
    {
        $query = DispatchRider::query()
            ->with(['user:id,name,email,phone,status', 'deliveryPartner:id,name,company_name,status'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('availability_status')) {
            $query->where('availability_status', $request->string('availability_status'));
        }

        if ($request->filled('delivery_partner_id')) {
            $query->where('delivery_partner_id', $request->integer('delivery_partner_id'));
        }

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->whereHas('user', function ($userQuery) use ($term) {
                $userQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $rider = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
            ]);

            $user->assignRole(Role::findOrCreate('Dispatch Rider', 'sanctum'));

            return DispatchRider::create([
                'user_id' => $user->id,
                'delivery_partner_id' => $data['delivery_partner_id'] ?? null,
                'phone' => $data['phone'] ?? null,
                'profile_photo_path' => $request->file('profile_picture')?->store('dispatch-riders/profile', 'public'),
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'vehicle_plate_number' => $data['vehicle_plate_number'] ?? null,
                'vehicle_image_path' => $request->file('vehicle_image')?->store('dispatch-riders/vehicles', 'public'),
                'availability_status' => $data['availability_status'] ?? 'available',
                'status' => $data['status'] ?? 'active',
            ]);
        });

        return response()->json([
            'message' => 'Dispatch rider created successfully',
            'rider' => $rider->load(['user', 'deliveryPartner']),
        ], 201);
    }

    public function show(int $id)
    {
        return response()->json(
            DispatchRider::with(['user', 'deliveryPartner', 'assignments.order.user'])->findOrFail($id)
        );
    }

    public function update(Request $request, int $id)
    {
        $rider = DispatchRider::with('user')->findOrFail($id);
        $data = $this->validatePayload($request, $rider);

        DB::transaction(function () use ($rider, $data, $request) {
            $userPayload = collect($data)
                ->only(['name', 'email', 'phone', 'status'])
                ->filter(fn ($value) => $value !== null)
                ->all();

            if (!empty($data['password'])) {
                $userPayload['password'] = Hash::make($data['password']);
            }

            if ($userPayload) {
                $rider->user->update($userPayload);
            }

            $riderPayload = collect($data)
                ->only(['delivery_partner_id', 'phone', 'vehicle_type', 'vehicle_plate_number', 'availability_status', 'status'])
                ->all();

            if ($request->hasFile('profile_picture')) {
                if ($rider->profile_photo_path) {
                    Storage::disk('public')->delete($rider->profile_photo_path);
                }
                $riderPayload['profile_photo_path'] = $request->file('profile_picture')->store('dispatch-riders/profile', 'public');
            }

            if ($request->hasFile('vehicle_image')) {
                if ($rider->vehicle_image_path) {
                    Storage::disk('public')->delete($rider->vehicle_image_path);
                }
                $riderPayload['vehicle_image_path'] = $request->file('vehicle_image')->store('dispatch-riders/vehicles', 'public');
            }

            $rider->update($riderPayload);
        });

        return response()->json([
            'message' => 'Dispatch rider updated successfully',
            'rider' => $rider->fresh(['user', 'deliveryPartner']),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $rider = DispatchRider::findOrFail($id);
        $data = $request->validate([
            'status' => 'required|string|in:active,inactive',
        ]);

        $rider->update($data);
        $rider->user?->update($data);

        return response()->json([
            'message' => 'Dispatch rider status updated',
            'rider' => $rider->fresh(['user', 'deliveryPartner']),
        ]);
    }

    protected function validatePayload(Request $request, ?DispatchRider $rider = null): array
    {
        $isUpdate = $rider !== null;
        $required = $isUpdate ? 'sometimes' : 'required';
        $userId = $rider?->user_id;

        return $request->validate([
            'name' => "$required|string|max:255",
            'email' => [
                $required,
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => 'nullable|string|max:30',
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'delivery_partner_id' => 'nullable|exists:delivery_partners,id',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'vehicle_type' => 'nullable|string|in:motorbike,bicycle,tricycle,car,van,truck',
            'vehicle_plate_number' => 'nullable|string|max:80',
            'vehicle_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'availability_status' => 'sometimes|string|in:available,unavailable,on_delivery',
            'status' => 'sometimes|string|in:active,inactive',
        ]);
    }
}
