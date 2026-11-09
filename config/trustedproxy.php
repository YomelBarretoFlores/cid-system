<?php

declare(strict_types=1);

return [
    // Unset or empty trusts no proxy. "*" trusts the proxy that calls the app; a comma list trusts those addresses or ranges (Illuminate TrustProxies).
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
