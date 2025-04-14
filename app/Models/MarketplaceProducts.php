<?php

namespace App\Models;

use App\Models\Marketplace\ProductSpecification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Marketplace\MarketplaceStore;

class MarketplaceProducts extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'marketplace_products';

  protected $dates = ['deleted_at'];
  public function specifications()
  {
    return $this->hasMany(ProductSpecification::class, 'product_id');
  }
  public function store()
  {
    return $this->belongsTo(MarketplaceStore::class, 'store_id', 'id');
  }
}
