<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $name = $request->post("name");
        $password = $request->post("password");
        // 自定义登录流程
        $user = User::query()
            ->where("users.name", $name)
            ->where("customers.is_active", 1)
            ->leftJoin("customers","customers.id", "users.customer_id")
            ->select("password", "users.id as id")
            ->first();
        if ($user && Hash::check($password, $user->password)) {
            $token = auth()->login($user);
            return $this->success($this->formatToken($token));
        } else {
            return $this->success([], "登录失败");
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();
        return $this->success([]);
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return $this->success($this->formatToken(auth()->refresh()));
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     * @return array
     */
    protected function formatToken(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }
}
