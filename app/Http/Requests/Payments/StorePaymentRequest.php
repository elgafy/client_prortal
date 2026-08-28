<?php

namespace App\Http\Requests\Payments;

use App\Models\Payment;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    /**
     * The "no project" option is submitted as the string "none" by the form;
     * normalize it to null before validating.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('project_id') === 'none') {
            $this->merge(['project_id' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->where('client_id', $this->integer('client_id')),
                ),
            ],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'payment_date' => ['required', 'date'],
            'method' => ['required', 'string', 'in:'.implode(',', (array) Setting::get(Setting::PAYMENT_METHODS, []))],
            'received_from' => ['nullable', 'string', 'max:255'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'in:'.Payment::STATUS_ACTIVE.','.Payment::STATUS_VOID],
        ];
    }

    /**
     * A payment assigned to a project must be in the project's currency —
     * otherwise project balances would mix currencies (PRD §13, §34–35).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $projectId = $this->integer('project_id');

            if ($projectId === 0) {
                return;
            }

            $project = Project::find($projectId);

            if ($project && $project->currency !== $this->input('currency')) {
                $validator->errors()->add(
                    'currency',
                    __('The payment currency must match the project currency (:currency).', [
                        'currency' => $project->currency,
                    ]),
                );
            }
        });
    }
}
