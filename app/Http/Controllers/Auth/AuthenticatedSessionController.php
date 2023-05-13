<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use DeviceDetector\DeviceDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function index()
    {
        if ($user = Auth::user()) {
            $errors = $code = $messages = $action = null;

            return view('web-user', compact('user', 'errors', 'code', 'action'));
        }

        return view('login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $dev = new DeviceDetector($request->userAgent());
        $device = $dev->getBrandName() ? ($dev->getBrandName().$dev->getDeviceName()) : $request->userAgent();

        $user = $request->user();

        if (! $request->ajax()) {
            return response()->redirectToRoute('web.user');
        }

        return (new UserResource($user))->additional([
            'message' => 'Login was successfull',
            'status' => 'success',
            'status_code' => 200,
            'token' => $user->createToken($device)->plainTextToken,
        ])->response()->setStatusCode(200);
    }

    public function getTokens(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->get();

        $data = $tokens->map(function ($token) use ($request) {
            $dev = new DeviceDetector($token->name);
            $dev->parse();
            $os = $dev->getOs();
            $name = $os['name'] ?? 'Unknown Device';
            $version = $os['version'] ?? '0.00';
            $platform = in_array($dev->getBrandName(), ['Apple', 'Microsoft'])
                ? $dev->getBrandName()
                : (in_array($dev->getOs('name'), ['Android', 'Ubuntu', 'Windows'])
                    ? $dev->getOs('name')
                    : ($dev->getClient('type') === 'browser'
                        ? $dev->getClient('family')
                        : $dev->getBrandName()
                    )
                );

            return (object) [
                'id' => $token->id,
                'name' => collect([$dev->getBrandName(), $name, "(v{$version})"])->implode(' '),
                'platform' => $platform,
                'platform_id' => str($platform)->slug('-')->toString(),
                'current' => $token->id === $request->user()->currentAccessToken()->id,
                'last_used' => $token->last_used_at?->diffInHours() > 24
                    ? $token->last_used_at->format('d M Y')
                    : $token->last_used_at?->diffForHumans(),
            ];
        });

        return $this->buildResponse([
            'message' => 'Tokens retrieved successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $data,
        ]);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        if (! $request->isXmlHttpRequest()) {
            session()->flush();

            return response()->redirectToRoute('web.login');
        }

        return $this->buildResponse([
            'message' => 'You have been successfully logged out',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }

    /**
     * Destroy all selected authenticated sessions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroyTokens(Request $request)
    {
        $request->validate([
            'token_ids' => 'required|array',
        ], [
            'token_ids.required' => __('Please select at least one device to logout'),
        ]);

        $tokens = $request->user()->tokens()
            ->whereIn('id', $request->token_ids)
            ->whereNot('id', $request->user()->currentAccessToken()->id)
            ->get();

        $names = [];

        if ($tokens->count() > 0) {
            $names = $tokens->pluck('name')->map(function ($name) {
                $dev = new DeviceDetector($name);
                $dev->parse();
                $os = $dev->getOs();
                $osName = $os['name'] ?? 'Unknown Device';
                $osVersion = $os['version'] ?? '0.00';

                return collect([$dev->getBrandName(), $osName, "(v{$osVersion})"])->implode(' ');
            })->implode(', ');

            $tokens->each->delete();
        } else {
            return $this->buildResponse([
                'message' => __('You are no longer logged in on any of the selected devices'),
                'status' => 'error',
                'status_code' => 422,
            ]);
        }

        return $this->buildResponse([
            'message' => __('You have been successfully logged out of :0', [$names]),
            'status' => 'success',
            'status_code' => 200,
        ]);
    }
}