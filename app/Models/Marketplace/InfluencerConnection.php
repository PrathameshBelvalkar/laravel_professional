<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfluencerConnection extends Model
{
  use HasFactory;
  protected $fillable = ['user_id', 'status'];
}
