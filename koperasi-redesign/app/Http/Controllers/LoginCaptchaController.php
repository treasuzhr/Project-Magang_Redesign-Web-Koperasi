<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginCaptchaController extends Controller
{
    public function issue(Request $request): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($index = 0; $index < 6; $index++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while ($code === $request->session()->get('captcha_answer'));
        $request->session()->put('captcha_answer', $code);

        return $code;
    }

    public function __invoke(Request $request): JsonResponse|Response
    {
        if (! $request->boolean('image')) {
            return response()->json(['code' => $this->issue($request)])->header('Cache-Control', 'no-store');
        }
        $code = $request->session()->get('captcha_answer');
        if (! is_string($code) || ! preg_match('/\A[A-HJ-NP-Z2-9]{6}\z/', $code)) {
            $code = $this->issue($request);
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="60" viewBox="0 0 180 60"><rect width="180" height="60" fill="#f5f7fa"/>';
        for ($index = 0; $index < 8; $index++) {
            $svg .= '<path d="M'.random_int(0, 180).' '.random_int(0, 60).' L'.random_int(0, 180).' '.random_int(0, 60).'" stroke="#b4bec8" opacity=".6"/>';
        }
        $colors = ['#27704c', '#2b69aa', '#a65c20', '#a3384a', '#238787', '#7855a0'];
        for ($index = 0; $index < 6; $index++) {
            $x = 12 + $index * 27;
            $angle = random_int(-15, 15);
            $svg .= '<text x="'.$x.'" y="40" font-family="sans-serif" font-size="28" font-weight="700" fill="'.$colors[$index].'" transform="rotate('.$angle.' '.$x.' 32)">'.$code[$index].'</text>';
        }

        return response($svg.'</svg>')->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-store')->header('X-Content-Type-Options', 'nosniff');
    }
}
