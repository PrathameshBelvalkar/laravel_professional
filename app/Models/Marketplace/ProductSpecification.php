<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSpecification extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];
}
