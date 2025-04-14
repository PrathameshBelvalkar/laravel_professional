<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeamMatch extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'team_matches';

    protected $fillable = [
        'sport_id',
        'location',
        'date',
        'time'
    ];
}
