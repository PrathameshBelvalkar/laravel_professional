<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supportquestionanswers extends Model
{
    use HasFactory;
    protected $table= 'support_question_answers';

    use SoftDeletes; 
    protected $fillable = ['user_id', 'category_id', 'question', 'answer'];

}
