<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductAttribute extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
    ];

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariation::class, 'product_variation_attributes')
            ->withPivot('value')
            ->withTimestamps();
    }
}
