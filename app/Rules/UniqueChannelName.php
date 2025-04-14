<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\StreamDeck\Channel; 

class UniqueChannelName implements Rule
{
    public function passes($attribute, $value)
    {
        return !Channel::where('channel_name', $value)->exists();
    }

    public function message()
    {
        return 'The :attribute has already been taken.';
    }
}

