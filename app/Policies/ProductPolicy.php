<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Product $product): bool
    {
        return $product->vendor_id === $user->id;
    }

    public function update(User $user, Product $product): bool
    {
        return $product->vendor_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $product->vendor_id === $user->id;
    }
}
