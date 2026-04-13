<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingLabel extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'easypost_shipment_id',
        'tracking_code',
        'carrier',
        'service',
        'label_url',
        'label_file_type',
        'rate',
        'from_name',
        'from_company',
        'from_street1',
        'from_street2',
        'from_city',
        'from_state',
        'from_zip',
        'to_name',
        'to_company',
        'to_street1',
        'to_street2',
        'to_city',
        'to_state',
        'to_zip',
        'weight',
        'length',
        'width',
        'height',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rate'   => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width'  => 'decimal:2',
            'height' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
