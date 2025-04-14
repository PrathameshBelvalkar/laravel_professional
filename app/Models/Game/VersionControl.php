<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VersionControl extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'version_controls';
}
