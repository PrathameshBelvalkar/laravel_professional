<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicNews extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = ['title', 'news_text', 'api_id', 'summary', "url", "author", "publish_date", "video", "image", "source_country", "language", "catgory"];
    protected static function booted(): void
    {
        static::retrieved(function ($news) {
            $news->increment("read_count");
        });
    }
}
