<?php

namespace App\Http\Requests\calendar;


use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class CalendarEventAddRequest extends RequestWrapper
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
            "event_title" => ['nullable'],
            "start_date_time" => [
                'required',
                'date_format:Y-m-d H:i:s',
            ],
            "end_date_time" => [
                'required',
                'date_format:Y-m-d H:i:s',
            ],
            "event_description" => ['nullable', 'string'],
            "category" => ['nullable', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'reminder' => ['nullable', 'json'],
            'visibility' => [Rule::in(['1', '2', '3'])],
            "event_attachment" => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,png', 'max:10240'],
            "location" => ['nullable', 'max:500'],
        ];
    }
    public function messages()
    {
        return [
            'event_title.required' => 'Event title  field is required.',
            'event_title.min' => 'Event title must be at least :min characters.',
            'event_title.max' => 'Event title must not exceed :max characters.',
            'start_date_time.required' => 'Start date is required',
            'start_date_time.date_format' => 'The start date and time should be in the format: Y-m-d H:i:s.',
            'start_date_time.after_or_equal' => 'Start time is greater than or equal to current time.',
            'end_date_time.required' => 'end date is required',
            'end_date_time.date_format' => 'The end date and time should be in the format: Y-m-d H:i:s.',
            'end_date_time.after_or_equal' => 'The end date should be equal or greater than start date.',
            'event_description.required' => 'Event description field is required.',
            'event_description.max' => 'Event description :max characters.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status field should be either "active" or "inactive".',
            'reminder.in' => 'The reminder field should be json format.',
            'visibiliy.in' => 'The visibiliy field should be either "private", "Followers only" or "Public".',
            'event_attachment.file' => 'The attcahmnet should be a file.',
            'event_attachment.mimes' => 'The file extension should be only :mimes.',
            'event_attachment.max' => 'The File size cannot exceed :max size.',
        ];
    }
}
