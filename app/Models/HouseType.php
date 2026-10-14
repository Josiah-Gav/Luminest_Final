<?php

namespace App\Models;

use Database\Factories\HouseTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseType extends Model
{
    /** @use HasFactory<HouseTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'category',
        'short_description',
        'description',
        'badge',
        'image_path',
        'bedrooms',
        'bathrooms',
        'carports',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'carports' => 'integer',
            'price' => 'integer',
        ];
    }

    /** @return HasMany<House, $this> */
    public function houses(): HasMany
    {
        return $this->hasMany(House::class);
    }
}
