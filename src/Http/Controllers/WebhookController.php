<?php

namespace Mralston\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mralston\Payment\Http\Requests\HometreeWebhookRequest;
use Mralston\Payment\Services\HometreeService;
use Mralston\Payment\Services\TandemService;
use Mralston\Payment\Services\HsbcService;

class WebhookController
{
    public function __construct(
        protected HometreeService $hometreeService,
        protected TandemService $tandemService,
        protected HsbcService $hsbcService,
    ) {}

    public function tandem(Request $request, string $uuid)
    {
        return $this->tandemService->handleWebhook($request, $uuid);
    }

    public function hsbc(Request $request, string $uuid)
    {
        return $this->hsbcService->handleWebhook($request, $uuid);
    }

    public function hometree(HometreeWebhookRequest $request)
    {
        Log::debug('Hometree webhook received');
        Log::debug('Headers:', $request->headers->all());
        Log::debug('Body:', $request->all());
        Log::debug($request->getContent());


        $this->hometreeService->handleWebhook(collect($request->validated()));

        return response()->json(['message' => 'success']);
    }
}
