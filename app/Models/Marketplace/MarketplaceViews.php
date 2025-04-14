<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceViews extends Model
{
  use HasFactory;
  protected $fillable = ['broadcast_id', 'view_count',];
}
