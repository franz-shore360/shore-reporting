<?php

namespace App\Http\Requests;

use App\Models\Import;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! $user->can('import-create')) {
            return false;
        }

        return match ($this->input('entity_type')) {
            Import::ENTITY_DEPARTMENT => $user->can('department-import'),
            Import::ENTITY_GL_ACCOUNT => $user->can('gl-account-import'),
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::in(Import::ENTITY_TYPES)],
            'file' => ['required', File::default()->extensions(['csv', 'txt', 'xlsx', 'xls'])->max(10240)],
        ];
    }
}
