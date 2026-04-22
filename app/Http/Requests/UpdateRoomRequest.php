<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'bedType' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'size' => ['nullable', 'string', 'max:255'],
            'pricePerNight' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'mainImage' => ['required_without:mainImageFile', 'nullable', 'string', 'max:2048'],
            'mainImageFile' => ['nullable', 'image', 'max:10240'],
            'images' => ['nullable', 'array'],
            'images.*' => ['required', 'string', 'max:2048'],
            'imagesFiles' => ['nullable', 'array'],
            'imagesFiles.*' => ['required', 'image', 'max:10240'],
            'amenities' => ['nullable', 'array'],
            'amenities.*.icon' => ['required', 'string', 'max:100'],
            'amenities.*.label' => ['required', 'string', 'max:255'],
            'kitchenAmenities' => ['nullable', 'array'],
            'kitchenAmenities.*' => ['required', 'string', 'max:255'],
            'bathroomAmenities' => ['nullable', 'array'],
            'bathroomAmenities.*' => ['required', 'string', 'max:255'],
            'otherAmenities' => ['nullable', 'array'],
            'otherAmenities.*' => ['required', 'string', 'max:255'],
            'policies' => ['nullable', 'array'],
            'policies.*' => ['required', 'string', 'max:255'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['required', 'string', 'max:255'],
            'isActive' => ['required', 'boolean'],
            'sortOrder' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $jsonFields = [
            'images',
            'amenities',
            'kitchenAmenities',
            'bathroomAmenities',
            'otherAmenities',
            'policies',
            'highlights',
        ];

        $payload = [];

        foreach ($jsonFields as $field) {
            $value = $this->input($field);

            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload[$field] = $decoded;
                }
            }
        }

        if ($this->has('capacity')) {
            $payload['capacity'] = (int) $this->input('capacity');
        }

        if ($this->has('pricePerNight')) {
            $payload['pricePerNight'] = (int) $this->input('pricePerNight');
        }

        if ($this->has('sortOrder') && $this->input('sortOrder') !== null && $this->input('sortOrder') !== '') {
            $payload['sortOrder'] = (int) $this->input('sortOrder');
        }

        if ($this->has('isActive')) {
            $payload['isActive'] = filter_var($this->input('isActive'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ?? $this->boolean('isActive');
        }

        $this->merge($payload);
    }
}
