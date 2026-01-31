<?php

namespace App\Models;

use App\Traits\Model\HasImage;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Concerns\Package\HasTranslations;

class ImageProduct extends Model
{
    use HasImage, HasTranslations;

    protected $fillable = [
        'image',
        'product_id',
    ];

    public $translatable = [
        'image',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
