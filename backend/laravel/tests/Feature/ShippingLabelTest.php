<?php

namespace Tests\Feature;

use App\Contracts\ShippingProviderInterface;
use App\Models\ShippingLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingLabelTest extends TestCase
{
    use RefreshDatabase;

    /** Fake EasyPost response returned by the mocked service. */
    private array $easyPostResult = [
        'easypost_shipment_id' => 'shp_test_abc123',
        'tracking_code'        => '9400111899223397711668',
        'carrier'              => 'USPS',
        'service'              => 'Priority',
        'label_url'            => 'https://easypost-files.s3.amazonaws.com/files/postage_label/test.pdf',
        'label_file_type'      => 'PDF',
        'rate'                 => 7.50,
        'status'               => 'purchased',
    ];

    /** A fully valid label creation payload. */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }

    /** Mock the shipping provider interface to return $this->easyPostResult. */
    private function mockEasyPost(): void
    {
        $this->mock(ShippingProviderInterface::class)
             ->shouldReceive('createLabel')
             ->once()
             ->andReturn($this->easyPostResult);
    }

    // -------------------------------------------------------------------------
    // POST /api/labels  — create
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_create_a_label(): void
    {
        $this->mockEasyPost();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->postJson('/api/labels', $this->validPayload());

        $response->assertCreated()
                 ->assertJsonPath('carrier', 'USPS')
                 ->assertJsonPath('service', 'Priority')
                 ->assertJsonPath('tracking_code', '9400111899223397711668')
                 ->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('shipping_labels', [
            'user_id'              => $user->id,
            'easypost_shipment_id' => 'shp_test_abc123',
        ]);
    }

    public function test_create_label_passes_correct_data_to_easypost(): void
    {
        $payload = $this->validPayload();

        $this->mock(ShippingProviderInterface::class)
             ->shouldReceive('createLabel')
             ->once()
             ->withArgs(function (array $data) use ($payload) {
                 return $data['from_city'] === $payload['from_city']
                     && $data['to_state']  === $payload['to_state']
                     && $data['weight']    === $payload['weight'];
             })
             ->andReturn($this->easyPostResult);

        $this->actingAs(User::factory()->create())
             ->postJson('/api/labels', $payload)
             ->assertCreated();
    }

    public function test_create_label_returns_422_when_easypost_fails(): void
    {
        $this->mock(ShippingProviderInterface::class)
             ->shouldReceive('createLabel')
             ->once()
             ->andThrow(new \RuntimeException('EasyPost error: Invalid address.'));

        $this->actingAs(User::factory()->create())
             ->postJson('/api/labels', $this->validPayload())
             ->assertUnprocessable()
             ->assertJsonPath('message', 'EasyPost error: Invalid address.');
    }

    public function test_create_label_rejects_non_us_state(): void
    {
        $this->actingAs(User::factory()->create())
             ->postJson('/api/labels', $this->validPayload(['from_state' => 'ON'])) // Ontario, Canada
             ->assertUnprocessable()
             ->assertJsonValidationErrors('from_state');
    }

    public function test_create_label_rejects_invalid_zip_format(): void
    {
        $this->actingAs(User::factory()->create())
             ->postJson('/api/labels', $this->validPayload(['to_zip' => 'ABCDE']))
             ->assertUnprocessable()
             ->assertJsonValidationErrors('to_zip');
    }

    public function test_create_label_requires_all_address_fields(): void
    {
        $this->actingAs(User::factory()->create())
             ->postJson('/api/labels', [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors([
                 'from_name', 'from_street1', 'from_city', 'from_state', 'from_zip',
                 'to_name',   'to_street1',   'to_city',   'to_state',   'to_zip',
                 'weight', 'length', 'width', 'height',
             ]);
    }

    public function test_create_label_rejects_zero_or_negative_dimensions(): void
    {
        $this->actingAs(User::factory()->create())
             ->postJson('/api/labels', $this->validPayload(['weight' => 0, 'height' => -1]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['weight', 'height']);
    }

    public function test_create_label_requires_authentication(): void
    {
        $this->postJson('/api/labels', $this->validPayload())
             ->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/labels  — index
    // -------------------------------------------------------------------------

    public function test_user_can_list_their_labels(): void
    {
        $user = User::factory()->create();
        ShippingLabel::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->getJson('/api/labels')
             ->assertOk()
             ->assertJsonCount(3);
    }

    public function test_user_only_sees_their_own_labels(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        ShippingLabel::factory()->count(2)->create(['user_id' => $alice->id]);
        ShippingLabel::factory()->count(5)->create(['user_id' => $bob->id]);

        $this->actingAs($alice)
             ->getJson('/api/labels')
             ->assertOk()
             ->assertJsonCount(2);
    }

    public function test_labels_index_requires_authentication(): void
    {
        $this->getJson('/api/labels')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/labels/{id}  — show
    // -------------------------------------------------------------------------

    public function test_user_can_view_their_own_label(): void
    {
        $user  = User::factory()->create();
        $label = ShippingLabel::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->getJson("/api/labels/{$label->id}")
             ->assertOk()
             ->assertJsonPath('id', $label->id)
             ->assertJsonPath('tracking_code', $label->tracking_code);
    }

    public function test_user_cannot_view_another_users_label(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();
        $label = ShippingLabel::factory()->create(['user_id' => $bob->id]);

        $this->actingAs($alice)
             ->getJson("/api/labels/{$label->id}")
             ->assertForbidden();
    }

    public function test_show_returns_404_for_nonexistent_label(): void
    {
        $this->actingAs(User::factory()->create())
             ->getJson('/api/labels/99999')
             ->assertNotFound();
    }

    public function test_show_requires_authentication(): void
    {
        $label = ShippingLabel::factory()->create();

        $this->getJson("/api/labels/{$label->id}")->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/labels/{id}/download  — download
    // -------------------------------------------------------------------------

    public function test_download_redirects_to_label_url(): void
    {
        $user  = User::factory()->create();
        $label = ShippingLabel::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->get("/api/labels/{$label->id}/download")
             ->assertRedirect($label->label_url);
    }

    public function test_download_is_forbidden_for_another_users_label(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();
        $label = ShippingLabel::factory()->create(['user_id' => $bob->id]);

        $this->actingAs($alice)
             ->get("/api/labels/{$label->id}/download")
             ->assertForbidden();
    }

    public function test_download_requires_authentication(): void
    {
        $label = ShippingLabel::factory()->create();

        $this->get("/api/labels/{$label->id}/download")->assertUnauthorized();
    }
}
