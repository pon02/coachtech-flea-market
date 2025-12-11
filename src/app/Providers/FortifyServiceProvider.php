<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::registerView(function() {
            return view('auth.register');
        });

        Fortify::loginView(function() {
            return view('auth.login');
        });

        // カスタムログインバリデーション
        Fortify::authenticateUsing(function (Request $request) {
            try {
                // LoginRequestでバリデーション実行
                $loginRequest = app(LoginRequest::class);
                $loginRequest->merge($request->all());
                $validated = $loginRequest->validated();

                $user = User::where('email', $validated['email'])->first();

                if ($user && Hash::check($validated['password'], $user->password)) {
                    return $user;
                }

                return null;
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw $e;
            }
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verification');
        });

        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                return $request->wantsJson()
                    ? response()->json(['two_factor' => false])
                    : redirect()->route('verification.notice');
            }
        });

        $this->app->instance(VerifyEmailResponse::class, new class implements VerifyEmailResponse {
            public function toResponse($request)
            {
                return $request->wantsJson()
                    ? response()->json(['status' => 'Email verified successfully'])
                    : redirect()->route('mypage.profile')->with('status', 'メール認証が完了しました。プロフィールを設定してください。');
            }
        });

        RateLimiter::for('login', function(Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(5)->by($email . $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
