<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('Vendor')) {
            return $order->items()->whereHas('sku.product', function ($query) use ($user) {
                $query->where('vendor_id', $user->id);
            })->exists();
        }

        return false;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->hasRole('Vendor') && $order->items()->whereHas('sku.product', function ($query) use ($user) {
            $query->where('vendor_id', $user->id);
        })->exists();
    }
}
