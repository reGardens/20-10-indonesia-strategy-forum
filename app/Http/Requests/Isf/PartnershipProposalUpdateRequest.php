<?php

namespace App\Http\Requests\Isf;

use App\Models\Isf\PartnershipProposal;
use Illuminate\Foundation\Http\FormRequest;

class PartnershipProposalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'pic_name' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:150'],
            'phone' => ['sometimes', 'required', 'string', 'min:8', 'max:50'],
            'partnership_type' => ['sometimes', 'required', 'string', 'in:'.implode(',', array_keys(PartnershipProposal::TYPES))],
            'message' => ['sometimes', 'required', 'string', 'min:5', 'max:10000'],
            'status' => ['sometimes', 'required', 'string', 'in:'.implode(',', array_keys(PartnershipProposal::STATUSES))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status kemitraan tidak valid.',
        ];
    }
}

