<?php

namespace App\Http\Requests\Projects;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'project_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:'.implode(',', [
                Project::STATUS_ACTIVE,
                Project::STATUS_COMPLETED,
                Project::STATUS_CANCELLED,
            ])],
            'link' => ['nullable', 'url', 'max:2000'],
        ];
    }
}
