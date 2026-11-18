<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRaffleEntryRequest extends FormRequest
{
    public const DUPLICATE_TICKET_MESSAGE = 'This ticket number has already been entered into the draw.';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the input so the same ticket typed differently is still caught as a duplicate.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $idNumber = $this->input('id_number');
        $ticketNumber = $this->input('ticket_number');

        $this->merge([
            'name' => is_string($name) ? Str::squish($name) : $name,
            'id_number' => is_string($idNumber) ? Str::upper(Str::remove(' ', $idNumber)) : $idNumber,
            'ticket_number' => is_string($ticketNumber) ? Str::upper(Str::remove(' ', $ticketNumber)) : $ticketNumber,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'id_number' => ['required', 'string', 'alpha_num:ascii', 'max:20'],
            'ticket_number' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('raffle_entries', 'ticket_number'),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_number.alpha_num' => 'The ID number may only contain letters and numbers.',
            'ticket_number.regex' => 'The ticket number may only contain letters, numbers and dashes.',
            'ticket_number.unique' => self::DUPLICATE_TICKET_MESSAGE,
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'id_number' => 'ID number',
            'ticket_number' => 'ticket number',
        ];
    }
}
