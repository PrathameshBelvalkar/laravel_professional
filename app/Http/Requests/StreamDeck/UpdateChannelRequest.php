<?php

namespace App\Http\Requests\StreamDeck;
use App\Rules\ExclusiveLogo;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class UpdateChannelRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "channel_id" => ['required', 'numeric',Rule::exists('channels', 'id')],
            "channel_name" => ['nullable', 'string', 'min:3', 'max:255'],
            "channel_type" => ['nullable', Rule::in(['0', '1', '2'])],
            "linear_channel_type" => ['nullable', Rule::in(['0', '1', '2'])],
            "schedule_duration" => ['nullable', Rule::in(['0', '1'])],
            "start_time" => ['nullable', 'date_format:H:i'],
            "logo_position" => ['nullable', Rule::in(['0', '1'])],
            "logo_on_off" => ['nullable', Rule::in(['0', '1'])],
            "main_color" => ['nullable', 'string'],
            "channel_embedded" => ['nullable', Rule::in(['0', '1'])],
            "add_tag_url" => ['nullable', 'string'],
            "no_of_adds_in_hour" => ['nullable', Rule::in(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'])],
            "Seconds_per_add_break" => ['nullable', Rule::in(['15', '30', '45', '60', '90', '120'])],
            "logo" => ['nullable', 'file', 'mimes:jpg,png', 'max:10240', new ExclusiveLogo],
            "logo_link" => ['nullable', 'string', new ExclusiveLogo],
        ];
    }
    public function messages()
    {
        return [
            'channel_name.string' => 'Channel name should be a string.',
            'channel_name.min' => 'Channel name must be at least :min characters.',
            'channel_name.max' => 'Channel name must not exceed :max characters.',
            'channel_type.in' => 'Channel type should be either linear, fast or on demand.',
            'linear_channel_type.in' => 'Linear type should be either scheduled or looped.',
            'schedule_duration.in' => 'Channel type should be either linear, fast or on demand.',
            'start_time' => 'Start time format should be HH:MM.',
            'logo_position.in' => 'Logo position should either be right or left.',
            'logo_on_off.in' => 'Logo should either on or off.',
            'main_color.string' => 'Main color should be a string.',
            'channel_embedded.in' => 'Channel embedded should be anywhere or the only domains you choose.',
            'add_tag_url.string' => 'Tags url should be a string',
            'no_of_adds_in_hour' => 'The adds per hour  should be only :values',
            'Seconds_per_add_break' => 'The Seconds per add duration  should be only :values',
            'logo.file' => 'Logo should be a file',
            'logo.mimes' => 'The logo extension should be only :values.',
            'logo_link.string' => 'The logo link should be a string.',
        ];
    }
}
