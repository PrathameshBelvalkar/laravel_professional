<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ExclusiveLogo implements Rule
{
    public function passes($attribute, $value)
    {
        $logo = request()->file('logo');
        $logoLink = request()->input('logo_link');

        return ($logo && !$logoLink) || (!$logo && $logoLink);
    }

    public function message()
    {
        return 'Either the logo or logo link should be filled, not both.';
    }
}

