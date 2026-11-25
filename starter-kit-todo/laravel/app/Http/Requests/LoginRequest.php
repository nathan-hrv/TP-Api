<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * EXEMPLE de FormRequest : valide le corps de POST /auth/login AVANT que le contrôleur ne soit appelé.
 * En cas d'échec, Laravel répond 422 avec le détail par champ ; le contrôleur n'est jamais exécuté.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // route publique
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }
}
