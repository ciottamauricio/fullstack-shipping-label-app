<?php

namespace App\Actions;

use App\Contracts\ShippingProviderInterface;
use App\Models\User;
use RuntimeException;

class CreateShippingLabelAction
{
    public function __construct(private readonly ShippingProviderInterface $shippingProvider) {}

    public function execute(User $user, array $data): \App\Models\ShippingLabel
    {
        // Call the shipping provider (EasyPost, FedEx, UPS, etc.)
        $labelData = $this->shippingProvider->createLabel($data);

        // Save the label to the database
        return $user->shippingLabels()->create([
            ...$data,
            ...$labelData,
        ]);
    }
}
