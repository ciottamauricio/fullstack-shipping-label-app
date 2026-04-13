<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShippingLabel>
 */
class ShippingLabelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'              => User::factory(),
            'easypost_shipment_id' => 'shp_' . fake()->unique()->uuid(),
            'tracking_code'        => strtoupper(fake()->bothify('9400########')),
            'carrier'              => 'USPS',
            'service'              => fake()->randomElement(['First', 'Priority', 'Express', 'ParcelSelect']),
            'label_url'            => 'https://easypost-files.s3.us-east-1.amazonaws.com/files/postage_label/' . fake()->uuid() . '.pdf',
            'label_file_type'      => 'PDF',
            'rate'                 => fake()->randomFloat(2, 3, 30),

            'from_name'    => fake()->name(),
            'from_company' => null,
            'from_street1' => fake()->streetAddress(),
            'from_street2' => null,
            'from_city'    => fake()->city(),
            'from_state'   => 'CA',
            'from_zip'     => fake()->numerify('#####'),

            'to_name'    => fake()->name(),
            'to_company' => null,
            'to_street1' => fake()->streetAddress(),
            'to_street2' => null,
            'to_city'    => fake()->city(),
            'to_state'   => 'NY',
            'to_zip'     => fake()->numerify('#####'),

            'weight' => fake()->randomFloat(2, 1, 70),
            'length' => fake()->randomFloat(2, 1, 24),
            'width'  => fake()->randomFloat(2, 1, 24),
            'height' => fake()->randomFloat(2, 1, 12),

            'status' => 'purchased',
        ];
    }
}
