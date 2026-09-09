<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    public function rules(): array
    {
        /** @var Application $application */
        $application = $this->route('application');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('applications', 'slug')->ignore($application->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'base_url' => ['required', 'string', 'url', 'max:255'],
            'has_financial_report' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
