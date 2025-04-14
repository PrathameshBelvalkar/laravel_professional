<?php

namespace App\Models\Flipbook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectionShare extends Model
{
  use HasFactory;
  use softDeletes;
  protected $table = 'collection_shares';
  protected $fillable = [
    'user_id',
    'collection_id',
    'shared_permission',
    'visibility',
    'is_shared',
    'is_deleted',
    'shared_with',
    'share_message',
  ];

  public function collectionFlipbook()
  {
    return $this->belongsTo(FlipbookCollection::class, 'collection_id', 'id');
  }
}
