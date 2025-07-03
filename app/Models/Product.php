<?php

namespace App\Models;

use App\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The Product model.
 */
class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'sku',
        'type',
        'price',
        'is_active',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function isSimple(): bool
    {
        return $this->type === ProductType::SIMPLE;
    }

    public function isVariable(): bool
    {
        return $this->type === ProductType::VARIABLE;
    }
}
