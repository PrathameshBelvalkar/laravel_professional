<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AssignTechUserRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "ticket_id" => ['required', Rule::exists('support_tickets', 'ticket_unique_id')],
            "user_id" => [
                'required',
                "array",
                function ($attribute, $value, $fail) {
                    $userIds = $this->input('user_id');

                    foreach ($value as $id) {
                        if (!is_numeric($id)) {
                            $fail('All values in the user_id array must be numeric.');
                            return;
                        }
                    }
                    // Check if user_id is an array and all elements are numeric
                    // if (!is_array($userIds) || count($userIds) == 0 || !collect($userIds)->every('numeric')) {
                    //     $fail('The user_id field must be a non-empty array of numeric values.');
                    // }
        
                    // Check if all user_ids exist in the users table with role_id 2
                    $exists = \DB::table('users')
                        ->whereIn('id', $userIds)
                        ->where('role_id', 2)
                        ->count();

                    if ($exists != count($userIds)) {
                        $fail('One or more user_ids do not exist or do not have the required role.');
                    }
                },
            ],
            "action" => ['required', Rule::in(['assign', 'remove'])]
        ];
    }
}
