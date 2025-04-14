<?php

namespace App\Models\Flipbook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlipbookCollection extends Model
{
  use HasFactory, softDeletes;
  protected $fillable = [
    'user_id',
    'collection_name',
  ];
  public function collectionShares()
  {
    return $this->hasMany(CollectionShare::class, 'collection_id', 'id');
  }
  public function thumbnailURL()
  {
    return getFileTemporaryURL($this->attributes['thumbnail']);
  }
}
