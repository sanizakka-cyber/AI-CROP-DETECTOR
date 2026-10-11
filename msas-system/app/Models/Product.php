<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id', 'seller_type', 'name', 'sku', 'category', 'subcategory', 'brand', 'manufacturer',
        'description', 'usage_instructions', 'dosage_instructions', 'storage_requirements',
        'unit', 'cost_price', 'selling_price', 'quantity_in_stock', 'low_stock_threshold',
        'expiry_date', 'image', 'tags', 'status', 'is_approved', 'is_featured',
        'rating', 'rating_count',
    ];

    protected $casts = [
        'tags'            => 'array',
        'cost_price'      => 'float',
        'selling_price'   => 'float',
        'rating'          => 'float',
        'is_approved'     => 'boolean',
        'is_featured'     => 'boolean',
        'expiry_date'     => 'date',
    ];

    protected $appends = ['stock_status', 'image_url'];

    // ── Relationships ───────────────────────────────────────────────────────────

    public function dealer()
    {
        return $this->belongsTo(User::class, 'dealer_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ── Accessors ───────────────────────────────────────────────────────────────

    public function getStockStatusAttribute(): string
    {
        if ($this->quantity_in_stock <= 0) return 'out_of_stock';
        if ($this->quantity_in_stock <= $this->low_stock_threshold) return 'low_stock';
        return 'in_stock';
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        return str_starts_with($this->image, 'http') ? $this->image : asset('storage/' . $this->image);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_approved', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity_in_stock', '>', 0);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('brand', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%");
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Atomic, guarded decrement -- the plain decrement() this replaced had
     * no WHERE guard and no row lock: stock availability was checked in
     * PHP before an unconditional decrement, so two concurrent checkouts
     * on the last unit(s) of a product could both pass the check and both
     * decrement, driving quantity_in_stock negative (overselling). The
     * WHERE clause means the UPDATE itself only succeeds if enough stock
     * is still actually there at the moment it runs, not at the moment it
     * was checked earlier in the request.
     *
     * @return bool false if insufficient stock remained (caller must not
     *              treat the order item as fulfilled in that case).
     */
    public function decrementStock(int $qty): bool
    {
        $affected = static::where('id', $this->id)
            ->where('quantity_in_stock', '>=', $qty)
            ->decrement('quantity_in_stock', $qty);

        if ($affected > 0) {
            $this->quantity_in_stock -= $qty;
        }

        return $affected > 0;
    }

    public function syncRating(): void
    {
        $avg = $this->reviews()->avg('rating');
        $cnt = $this->reviews()->count();
        $this->update(['rating' => round($avg ?? 0, 2), 'rating_count' => $cnt]);
    }

    public static function categories(): array
    {
        return [
            'Livestock Feed'        => ['Starter Feed','Grower Feed','Finisher Feed','Layer Mash','Broiler Feed','Dairy Feed','Beef Feed','Fish Feed','Rabbit Feed'],
            'Veterinary Medicines'  => ['Antibiotics','Dewormers','Antiparasitic Drugs','Antiseptics','Vitamins','Pain Relievers','Anti-inflammatory Drugs','Immune Boosters'],
            'Vaccines'              => ['Poultry Vaccines','Cattle Vaccines','Goat Vaccines','Sheep Vaccines','Fish Vaccines'],
            'Animal Health'         => ['Tick Control','Fly Repellents','Wound Care','Disinfectants','Hoof Care','Skin Treatments'],
            'Veterinary Equipment'  => ['Syringes','Needles','Thermometers','Gloves','Weighing Scales','Ear Tags'],
            'Crop Protection'       => ['Herbicides','Fungicides','Insecticides','Pesticides','Bio-pesticides'],
            'Fertilizers'           => ['Organic Fertilizers','NPK Fertilizers','Urea','Compost','Foliar Fertilizers'],
            'Seeds'                 => ['Maize','Rice','Millet','Sorghum','Soybean','Groundnut','Vegetable Seeds'],
            'Farm Equipment'        => ['Sprayers','Water Pumps','Irrigation Equipment','Hand Tools','Protective Equipment'],
        ];
    }
}
