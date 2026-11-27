<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Intentos fallidos permitidos desde un mismo equipo (con cualquier correo)
     * antes de bloquearlo. Es holgado porque en la cantera varios usan la misma red.
     */
    public const INTENTOS_POR_EQUIPO = 20;

    public const MINUTOS_BLOQUEO_EQUIPO = 15;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $inactivo = false;

        $autenticado = Auth::attemptWhen(
            $this->only('email', 'password'),
            function (User $user) use (&$inactivo): bool {
                $inactivo = ! $user->activo;

                return $user->activo;
            },
            $this->boolean('remember'),
        );

        if (! $autenticado) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->throttleKeyEquipo(), self::MINUTOS_BLOQUEO_EQUIPO * 60);

            throw ValidationException::withMessages([
                'email' => $inactivo ? trans('auth.inactive') : trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $porCorreo = RateLimiter::tooManyAttempts($this->throttleKey(), 5);
        $porEquipo = RateLimiter::tooManyAttempts($this->throttleKeyEquipo(), self::INTENTOS_POR_EQUIPO);

        if (! $porCorreo && ! $porEquipo) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($porEquipo ? $this->throttleKeyEquipo() : $this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Clave del límite por equipo (IP): frena a quien prueba muchos correos distintos.
     */
    public function throttleKeyEquipo(): string
    {
        return 'login-equipo|'.$this->ip();
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
