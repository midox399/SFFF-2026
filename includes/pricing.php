<?php
/**
 * Single source of truth for passport prices, server-side. Must be kept in
 * sync with the `numericPrice` values in index.html's ticketsList arrays
 * (FR/EN/AR) — those are display-only; this is what's actually charged.
 * Never trust a price sent by the client.
 */

declare(strict_types=1);

const PASSPORT_PRICES_TND = [
    'standard' => 25.00,
    'pro' => 120.00,
    'diplomatique' => 230.00,
    'vip' => 350.00,
];

function passport_unit_price(string $slug): ?float
{
    return PASSPORT_PRICES_TND[$slug] ?? null;
}
