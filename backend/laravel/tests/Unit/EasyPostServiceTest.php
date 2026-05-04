<?php

namespace Tests\Unit;

use App\Services\EasyPostService;
use EasyPost\EasyPostClient;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class EasyPostServiceTest extends TestCase
{
    private EasyPostClient&MockInterface $client;
    private EasyPostService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client  = \Mockery::mock(EasyPostClient::class);
        $this->service = new EasyPostService($this->client);
    }

    private function fakeShipment(): object
    {
        $rate = (object) [
            'carrier' => 'USPS',
            'service' => 'Priority',
            'rate'    => '7.50',
        ];

        $label = (object) ['label_url' => 'https://easypost-files.s3.amazonaws.com/files/postage_label/test.pdf'];

        $shipment = \Mockery::mock();
        $shipment->id            = 'shp_test_abc123';
        $shipment->tracking_code = '9400111899223397711668';
        $shipment->selected_rate = $rate;
        $shipment->postage_label = $label;
        $shipment->shouldReceive('lowestRate')
                 ->with(['USPS'])
                 ->andReturn($rate);

        return $shipment;
    }

    private function validData(): array
    {
        return [
            'from_name'    => 'Jane Doe',
            'from_street1' => '388 Townsend St',
            'from_city'    => 'San Francisco',
            'from_state'   => 'CA',
            'from_zip'     => '94107',
            'to_name'      => 'John Smith',
            'to_street1'   => '1600 Pennsylvania Ave NW',
            'to_city'      => 'Washington',
            'to_state'     => 'DC',
            'to_zip'       => '20500',
            'weight'       => 16,
            'length'       => 12,
            'width'        => 8,
            'height'       => 4,
        ];
    }

    public function test_create_label_returns_expected_fields(): void
    {
        $shipment = $this->fakeShipment();

        $shipmentResource = \Mockery::mock();
        $shipmentResource->shouldReceive('create')->once()->andReturn($shipment);
        $shipmentResource->shouldReceive('buy')->once()->with($shipment->id, \Mockery::any())->andReturn($shipment);

        $this->client->shipment = $shipmentResource;

        $result = $this->service->createLabel($this->validData());

        $this->assertSame('shp_test_abc123', $result['easypost_shipment_id']);
        $this->assertSame('USPS', $result['carrier']);
        $this->assertSame('Priority', $result['service']);
        $this->assertSame(7.50, $result['rate']);
        $this->assertSame('purchased', $result['status']);
        $this->assertArrayHasKey('label_url', $result);
        $this->assertArrayHasKey('tracking_code', $result);
    }

    public function test_create_label_sends_correct_addresses_to_client(): void
    {
        $data     = $this->validData();
        $shipment = $this->fakeShipment();

        $shipmentResource = \Mockery::mock();
        $shipmentResource->shouldReceive('create')
            ->once()
            ->withArgs(function (array $payload) use ($data) {
                return $payload['from_address']['city']  === $data['from_city']
                    && $payload['from_address']['state'] === $data['from_state']
                    && $payload['to_address']['city']    === $data['to_city']
                    && $payload['to_address']['country'] === 'US'
                    && $payload['parcel']['weight']      === $data['weight'];
            })
            ->andReturn($shipment);
        $shipmentResource->shouldReceive('buy')->once()->andReturn($shipment);

        $this->client->shipment = $shipmentResource;

        $this->service->createLabel($data);
    }

    public function test_create_label_throws_runtime_exception_on_easypost_failure(): void
    {
        $shipmentResource = \Mockery::mock();
        $shipmentResource->shouldReceive('create')
            ->once()
            ->andThrow(new \EasyPost\Exception\General\EasyPostException('Invalid address'));

        $this->client->shipment = $shipmentResource;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('EasyPost error: Invalid address');

        $this->service->createLabel($this->validData());
    }
}
