<?php

namespace App\Services;

use App\Contracts\ShippingProviderInterface;
use EasyPost\EasyPostClient;
use EasyPost\Exception\General\EasyPostException;
use RuntimeException;

class EasyPostService implements ShippingProviderInterface
{
    public function __construct(private readonly EasyPostClient $client) {}

    /**
     * Create a Shipment, buy the cheapest USPS rate, and return the
     * fields needed to populate the shipping_labels table.
     *
     * @param  array{
     *   from_name: string, from_company?: string,
     *   from_street1: string, from_street2?: string,
     *   from_city: string, from_state: string, from_zip: string,
     *   to_name: string, to_company?: string,
     *   to_street1: string, to_street2?: string,
     *   to_city: string, to_state: string, to_zip: string,
     *   weight: float, length: float, width: float, height: float
     * } $data Validated input from the request
     *
     * @throws RuntimeException on EasyPost API failure
     */
    public function createLabel(array $data): array
    {
        try {
            $labelFormat = strtoupper(config('easypost.label_format', 'PDF'));

            // 1. Create the shipment (EasyPost fetches all available rates)
            $shipment = $this->client->shipment->create([
                'from_address' => [
                    'name'    => $data['from_name'],
                    'company' => $data['from_company'] ?? null,
                    'street1' => $data['from_street1'],
                    'street2' => $data['from_street2'] ?? null,
                    'city'    => $data['from_city'],
                    'state'   => $data['from_state'],
                    'zip'     => $data['from_zip'],
                    'country' => 'US',
                ],
                'to_address' => [
                    'name'    => $data['to_name'],
                    'company' => $data['to_company'] ?? null,
                    'street1' => $data['to_street1'],
                    'street2' => $data['to_street2'] ?? null,
                    'city'    => $data['to_city'],
                    'state'   => $data['to_state'],
                    'zip'     => $data['to_zip'],
                    'country' => 'US',
                ],
                'parcel' => [
                    'weight' => $data['weight'],  // ounces
                    'length' => $data['length'],  // inches
                    'width'  => $data['width'],   // inches
                    'height' => $data['height'],  // inches
                ],
                'options' => [
                    'label_format' => $labelFormat,
                ],
            ]);

            // 2. Select cheapest USPS rate
            //    lowestRate() throws if no matching rate is found
            $rate = $shipment->lowestRate(['USPS']);

            // 3. Purchase the label
            $purchased = $this->client->shipment->buy($shipment->id, $rate);

            return [
                'easypost_shipment_id' => $purchased->id,
                'tracking_code'        => $purchased->tracking_code ?? null,
                'carrier'              => $purchased->selected_rate->carrier,
                'service'              => $purchased->selected_rate->service,
                'label_url'            => $purchased->postage_label->label_url,
                'label_file_type'      => $labelFormat,
                'rate'                 => (float) $purchased->selected_rate->rate,
                'status'               => 'purchased',
            ];
        } catch (EasyPostException $e) {
            throw new RuntimeException('EasyPost error: ' . $e->getMessage(), 0, $e);
        }
    }
}
