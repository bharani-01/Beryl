<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpLocation extends Model
{
    protected $table = 'ip_locations';

    protected $primaryKey = 'ip';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_private' => 'boolean',
    ];
}
