<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\BillingTransactionModel;
use App\Models\UserModel;

/**
 * BillingController
 *
 * Handles Google Play purchase verification for premium subscriptions.
 * Logs every purchase to billing_transactions table for admin review.
 *
 * Endpoints:
 *   POST /api/billing/google-play/verify → Verifies a purchase token and activates premium
 *
 * Product ID mapping:
 *   velixa_premium_yearly   → premium_until = now + 1 year
 *   velixa_premium_lifetime → premium_until = now + 10 years
 */
class BillingController extends BaseApiController
{
    private UserModel $userModel;
    private BillingTransactionModel $billingTransactionModel;

    /** Known product IDs and their premium durations */
    private const PRODUCT_DURATIONS = [
        'velixa_premium_yearly'   => '+1 year',
        'velixa_premium_lifetime' => '+10 years',
    ];

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->billingTransactionModel = new BillingTransactionModel();
    }

    /**
     * POST /api/billing/google-play/verify
     *
     * Body (JSON):
     *   {
     *     "user_id": 1,
     *     "purchase_token": "abc123...",
     *     "product_id": "velixa_premium_yearly"
     *   }
     *
     * Response 200:
     *   {
     *     "success": true,
     *     "message": "Premium geactiveerd.",
     *     "data": {
     *       "user_id": 1,
     *       "email": "user@example.com",
     *       "premium": true,
     *       "premium_until": "2027-06-05 12:00:00"
     *     }
     *   }
     */
    public function verifyPurchase()
    {
        // Authenticate user via JWT token
        $userId = $this->getAuthUserId();

        $body = $this->getJsonBody(['purchase_token', 'product_id']);
        if ($body === false) {
            return $this->error('Ongeldige aanvraag.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'purchase_token' => 'required|max_length[1024]',
            'product_id'     => 'required|max_length[255]',
        ])) {
            return $this->error('Validatie mislukt.', 422, $this->getValidationErrors());
        }

        $purchaseToken = $this->sanitizeText((string) $body['purchase_token'], 1024);
        $productId     = $this->sanitizeText((string) $body['product_id'], 255);

        // In production, verify the purchase token with Google Play Developer API here.
        // For testing with static test cards (e.g. always_approved), we accept the token
        // directly. See: https://developer.android.com/google/play/billing/test
        //
        // Example production flow:
        //   $googlePlayValidator = new GooglePlayPurchaseValidator();
        //   if (! $googlePlayValidator->verify($productId, $purchaseToken)) {
        //       return $this->error('Purchase token invalid.', 400);
        //   }

        $duration = self::PRODUCT_DURATIONS[$productId] ?? null;
        if ($duration === null) {
            log_message('warning', 'BillingController: unknown product_id={product_id} user_id={user_id}', [
                'product_id' => $productId,
                'user_id'    => $userId,
            ]);
            return $this->error('Onbekend product.', 400);
        }

        $user = $this->userModel->find($userId);
        if (! $user) {
            return $this->error('Gebruiker niet gevonden.', 404);
        }

        // Activate premium with appropriate duration
        $this->userModel->activatePremium($userId, $duration);

        // Log the transaction in billing_transactions table
        $this->billingTransactionModel->logTransaction([
            'user_id'          => $userId,
            'product_id'       => $productId,
            'purchase_token'   => $purchaseToken,
            'plan_type'        => $this->billingTransactionModel->planTypeFromProductId($productId),
            'amount'           => $body['amount'] ?? null,
            'currency'         => $body['currency'] ?? null,
            'google_order_id'  => $body['google_order_id'] ?? null,
            'premium_duration' => $duration,
            'raw_response'     => $body,
        ]);

        // Refresh user data after update
        $user = $this->userModel->find($userId);

        log_message('info', 'BillingController: premium activated user_id={user_id} product_id={product_id} duration={duration}', [
            'user_id'    => $userId,
            'product_id' => $productId,
            'duration'   => $duration,
        ]);

        return $this->ok([
            'user_id'       => (int) $user['id'],
            'email'         => $user['email'],
            'role'          => $user['role'] ?? 'user',
            'premium'       => true,
            'premium_until' => $user['premium_until'],
        ], 'Premium geactiveerd.');
    }
}