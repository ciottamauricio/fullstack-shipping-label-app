<?php

namespace App\Contracts;

/**
 * Interface for shipping label providers.
 *
 * Implementations can use EasyPost, FedEx, UPS, etc.
 * This abstraction allows easy provider switching without changing business logic.
 */
interface ShippingProviderInterface
{
    /**
     * Create a shipping label for the given address and package information.
     *
     * @param  array{
     *   from_name: string, from_company?: string,
     *   from_street1: string, from_street2?: string,
     *   from_city: string, from_state: string, from_zip: string,
     *   to_name: string, to_company?: string,
     *   to_street1: string, to_street2?: string,
     *   to_city: string, to_state: string, to_zip: string,
     *   weight: float, length: float, width: float, height: float
     * } $data Validated address and package data
     *
     * @return array{
     *   shipment_id: string,
     *   tracking_code?: string,
     *   carrier: string,
     *   service: string,
     *   label_url: string,
     *   label_file_type: string,
     *   rate: float,
     *   status: string
     * } Label details
     *
     * @throws \RuntimeException on API failure
     */
    public function createLabel(array $data): array;
}
