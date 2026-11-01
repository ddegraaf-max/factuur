<?php

namespace Tests\Support;

use App\Models\DemoRequest;
use App\Support\Brand;

/** De demo start sinds 1.86.0 alleen met een goedgekeurde link; tests maken er zo een. */
trait StartsDemo
{
    protected function approvedDemoToken(string $email = 'bezoeker@example.com'): string
    {
        return DemoRequest::create([
            'brand' => Brand::key(),
            'email' => $email,
            'token' => DemoRequest::newToken(),
            'review_token' => DemoRequest::newToken(),
            'status' => DemoRequest::APPROVED,
            'approved_at' => now(),
            'expires_at' => now()->addDays(DemoRequest::VALID_DAYS),
        ])->token;
    }

    /** De POST-url die een demo bouwt en inlogt. */
    protected function demoStartUrl(): string
    {
        return '/demo/start/'.$this->approvedDemoToken();
    }
}
