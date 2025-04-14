<?php

namespace App\Models\Flipbook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlipbookCategories extends Model
{
    use HasFactory;
    protected $table = 'flipbook_categories';
    protected $fillable = [
        'label',
        'value'
    ];
}
