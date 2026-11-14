<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Cb\RecruitmentController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationReviewController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->awaitingRegistration()) {
            return redirect()->route('dashboard');
        }

        return view('portal.review', ['registration' => $request->user()->registration]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->awaitingRegistration(), 403);

        return app(RecruitmentController::class)->replace($request, $request->user()->registration, true);
    }

    public function document(Request $request, string $document): StreamedResponse
    {
        abort_unless($request->user()->awaitingRegistration(), 403);

        return app(RecruitmentController::class)->document($request->user()->registration, $document);
    }
}
