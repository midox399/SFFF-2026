<?php
/**
 * Payment provider abstraction. Everything gateway-specific lives here so
 * swapping providers, or going from stub to live, touches only this file.
 *
 * ============================================================
 * TO WIRE UP THE REAL PROVIDER (Attijari Bank Tunisie e-commerce):
 * ============================================================
 * Attijari's online payment product is a merchant/TPE (terminal de paiement
 * électronique) e-commerce account. As of writing, no merchant account has
 * been opened yet — ask your Attijari business relationship manager for the
 * "solution de paiement en ligne" / e-commerce onboarding. Their gateway is
 * commonly a white-labeled Monetico/SPS-style hosted payment page, but get
 * this confirmed from Attijari's own integration kit once the account is
 * opened — do not assume the field names below, verify them against the
 * actual PDF/kit they hand you.
 *
 * What to get from Attijari before touching this file:
 *   - Site/merchant ID (numéro de site or TPE ID)
 *   - A secret key for computing/verifying the HMAC/MAC signature
 *   - The hosted payment page URL (sandbox + production)
 *   - Their required POST field names for amount, currency, order
 *     reference, return URL, and notification (IPN) URL
 *   - Their notification callback field names and the exact signature
 *     algorithm (commonly HMAC-SHA1 or SHA-256 over a specific field
 *     concatenation — this varies by processor, confirm exactly)
 *
 * 1. Store the site ID + secret key in config/database.php (or a new
 *    gitignored config/payment.php) — never hardcode keys here.
 * 2. Replace the body of payment_provider_init_checkout() below to build
 *    the redirect (or POST-and-redirect form) to Attijari's hosted payment
 *    page, passing:
 *      - amount: $reservation['amount_due'] (already in TND, already
 *        server-computed — never trust a client-sent amount)
 *      - a success/return URL: .../ticket.php?ref=<reference> — this is the
 *        printable ticket page the customer lands on after paying
 *      - a webhook/notification URL, e.g. .../payment-webhook.php
 *      - an order/reference id: $reservation['reference']
 *    Return the checkout URL (or render the auto-submit form) as their kit
 *    specifies.
 * 3. Replace payment_provider_verify_webhook() with their real MAC/signature
 *    verification (see api/payment-webhook.php) — this replaces, not adds
 *    to, the interim PAYMENT_WEBHOOK_SECRET check currently in place below.
 * ============================================================
 */

declare(strict_types=1);

/**
 * Starts a checkout session and returns the URL to redirect the browser
 * to. STUB IMPLEMENTATION — see file header for what a real one needs.
 */
function payment_provider_init_checkout(PDO $pdo, array $reservation): string
{
    // --- STUB: no live gateway configured yet ---
    // Points at a local page that simulates a successful payment so the
    // rest of the flow (webhook -> payment_status='paid' -> admin sees it)
    // can be tested today. Delete payment-stub-checkout.php once a real
    // provider is wired up here.
    return 'payment-stub-checkout.php?ref=' . urlencode($reservation['reference']);
}

/**
 * Verifies an incoming webhook actually came from the payment provider
 * (not a spoofed request). STUB — always returns true locally so the
 * webhook endpoint is testable; a real implementation MUST check the
 * provider's signature header/secret before trusting the payload.
 */
function payment_provider_verify_webhook(array $headers, string $rawBody): bool
{
    // --- INTERIM: shared-secret check ---
    // Until a real provider is wired up, require a secret only our own
    // stub/server knows, sent as a header, so a random visitor can't POST a
    // fake "paid" event. Replace with real signature verification (HMAC over
    // $rawBody using the provider's signing key) once a real gateway exists
    // — that check should look at the request body/signature, not this
    // shared secret.
    if (!defined('PAYMENT_WEBHOOK_SECRET')) {
        return false;
    }

    $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
    $provided = (string)($normalizedHeaders['x-webhook-secret'] ?? '');

    return $provided !== '' && hash_equals(PAYMENT_WEBHOOK_SECRET, $provided);
}
