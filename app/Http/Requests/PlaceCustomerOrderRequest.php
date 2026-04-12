<?php

namespace App\Http\Requests;

use App\Models\DispatchTimeSlot;
use App\Models\OperationArea;
use App\Models\OperationCity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlaceCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'city_id' => 'required|integer|exists:operation_cities,id',
            'area_id' => 'required|integer|exists:operation_areas,id',
            'dispatch_time_slot_id' => 'required|integer|exists:dispatch_time_slots,id',
            'payment_mode' => 'required|string|in:cash,transfer,bank_deposit,pos,online_payment,other,card,bank_transfer,wallet',
            'payment_reference' => 'nullable|string|max:120',
            'order_note' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $cityId = (int) $this->input('city_id');
            $areaId = (int) $this->input('area_id');
            $slotId = (int) $this->input('dispatch_time_slot_id');

            $city = OperationCity::query()->find($cityId);
            if (!$city || $city->status !== 'active') {
                $validator->errors()->add('city_id', 'Selected city must be active.');
            }

            $area = OperationArea::query()->find($areaId);
            if (!$area || $area->status !== 'active') {
                $validator->errors()->add('area_id', 'Selected area must be active.');
            }

            if ($area && $area->city_id !== $cityId) {
                $validator->errors()->add('area_id', 'Selected area does not belong to selected city.');
            }

            $slot = DispatchTimeSlot::query()->find($slotId);
            if (!$slot || $slot->status !== 'active') {
                $validator->errors()->add('dispatch_time_slot_id', 'Selected dispatch slot must be active.');
            }
        });
    }
}
