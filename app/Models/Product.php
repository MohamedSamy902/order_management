<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'status',
    ];

    public $translatable = [
        'name',
        'description',
    ];

    public function images()
    {
        return $this->hasMany(ImageProduct::class);
    }
}
