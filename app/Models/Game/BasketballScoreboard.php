<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BasketballScoreboard extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'basketball_scoreboards';

    protected $fillable = [
        'tournament_id',
        'team_one_id',
        'team_two_id',
        'team_one_score',
        'team_two_score',
        'team_one_log',
        'team_two_log',
    ];
}
