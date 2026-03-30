<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\CheckoutSession;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Payment;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle incoming Stripe webhooks
     * 
     * @see https://stripe.com/docs/webhooks
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        // Verify webhook signature if secret is configured
        if ($webhookSecret) {
            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            } catch (SignatureVerificationException $e) {
                Log::error('Stripe webhook signature verification failed', [
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            } catch (\Exception $e) {
                Log::error('Stripe webhook error', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Webhook error'], 400);
            }
        } else {
            // No signature verification (development mode)
            $event = json_decode($payload, false);
            Log::warning('Stripe webhook signature verification disabled - not recommended for production');
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        // Route event to appropriate handler
        $result = match ($event->type) {
            // Payment Intent events
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
            'payment_intent.canceled' => $this->handlePaymentIntentCanceled($event->data->object),
            
            // Charge events
            'charge.succeeded' => $this->handleChargeSucceeded($event->data->object),
            'charge.failed' => $this->handleChargeFailed($event->data->object),
            'charge.refunded' => $this->handleChargeRefunded($event->data->object),
            'charge.dispute.created' => $this->handleDisputeCreated($event->data->object),
            'charge.dispute.closed' => $this->handleDisputeClosed($event->data->object),
            
            // Payout events (important for cash flow tracking)
            'payout.created' => $this->handlePayoutCreated($event->data->object),
            'payout.paid' => $this->handlePayoutPaid($event->data->object),
            'payout.failed' => $this->handlePayoutFailed($event->data->object),
            
            // Transfer events (Connect - application fees)
            'transfer.created' => $this->handleTransferCreated($event->data->object),
            'application_fee.created' => $this->handleApplicationFeeCreated($event->data->object),
            
            // Account events (if using Connect)
            'account.updated' => $this->handleAccountUpdated($event->data->object),

            // Checkout Session events (payment links)
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
            'checkout.session.expired' => $this->handleCheckoutSessionExpired($event->data->object),

            default => $this->handleUnknownEvent($event),
        };

        return response()->json(['received' => true, 'handled' => $result]);
    }

    /**
     * Payment Intent succeeded - main payment confirmation
     */
    private function handlePaymentIntentSucceeded(object $paymentIntent): bool
    {
        Log::info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100,
            'currency' => $paymentIntent->currency,
        ]);

        // Find or create transaction
        $transaction = BankTransaction::where('external_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'completed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'webhook_confirmed_at' => now()->toIso8601String(),
                    'stripe_status' => $paymentIntent->status,
                ]),
            ]);
            return true;
        }

        // If transaction doesn't exist, it might be a new payment
        // The daily sync should pick it up, but we can optionally create it here
        Log::info('Payment intent not found locally, will be picked up by sync', [
            'payment_intent_id' => $paymentIntent->id,
        ]);

        return true;
    }

    /**
     * Payment Intent failed
     */
    private function handlePaymentIntentFailed(object $paymentIntent): bool
    {
        Log::warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
        ]);

        // Update local transaction if exists
        $transaction = BankTransaction::where('external_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'failed',
                'notes' => 'Payment failed: ' . ($paymentIntent->last_payment_error->message ?? 'Unknown error'),
            ]);
        }

        return true;
    }

    /**
     * Payment Intent canceled
     */
    private function handlePaymentIntentCanceled(object $paymentIntent): bool
    {
        Log::info('Payment intent canceled', [
            'payment_intent_id' => $paymentIntent->id,
            'cancellation_reason' => $paymentIntent->cancellation_reason ?? 'Unknown',
        ]);

        $transaction = BankTransaction::where('external_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'canceled',
                'notes' => 'Canceled: ' . ($paymentIntent->cancellation_reason ?? 'Unknown'),
            ]);
        }

        return true;
    }

    /**
     * Charge succeeded
     */
    private function handleChargeSucceeded(object $charge): bool
    {
        Log::info('Charge succeeded', [
            'charge_id' => $charge->id,
            'amount' => $charge->amount / 100,
            'payment_intent' => $charge->payment_intent ?? null,
        ]);

        // Update or create the transaction
        $transaction = BankTransaction::where('external_id', $charge->id)
            ->orWhere('external_id', $charge->payment_intent)
            ->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'completed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'charge_id' => $charge->id,
                    'receipt_url' => $charge->receipt_url,
                ]),
            ]);
        }

        return true;
    }

    /**
     * Charge failed
     */
    private function handleChargeFailed(object $charge): bool
    {
        Log::warning('Charge failed', [
            'charge_id' => $charge->id,
            'failure_code' => $charge->failure_code,
            'failure_message' => $charge->failure_message,
        ]);

        return true;
    }

    /**
     * Charge refunded - CRITICAL for tracking refunds
     */
    private function handleChargeRefunded(object $charge): bool
    {
        $refundAmount = $charge->amount_refunded / 100;
        $isFullRefund = $charge->refunded;

        Log::info('Charge refunded', [
            'charge_id' => $charge->id,
            'original_amount' => $charge->amount / 100,
            'refund_amount' => $refundAmount,
            'is_full_refund' => $isFullRefund,
        ]);

        // Find original transaction
        $originalTransaction = BankTransaction::where('external_id', $charge->id)
            ->orWhere('external_id', $charge->payment_intent)
            ->first();

        if (!$originalTransaction) {
            Log::warning('Original transaction not found for refund', ['charge_id' => $charge->id]);
            return true;
        }

        // Create refund transaction if not exists
        $refundExternalId = $charge->id . '_refund';
        
        $existingRefund = BankTransaction::where('external_id', $refundExternalId)->first();
        
        if (!$existingRefund) {
            BankTransaction::create([
                'bank_account_id' => $originalTransaction->bank_account_id,
                'external_id' => $refundExternalId,
                'transaction_date' => now(),
                'value_date' => now(),
                'type' => 'refund',
                'amount' => -$refundAmount, // Negative for refund
                'currency' => strtoupper($charge->currency),
                'description' => 'Rimborso Stripe: ' . ($charge->description ?? $charge->id),
                'beneficiary' => $originalTransaction->beneficiary,
                'source' => 'stripe',
                'status' => 'completed',
                'is_reconciled' => false,
                'metadata' => [
                    'original_charge_id' => $charge->id,
                    'original_transaction_id' => $originalTransaction->id,
                    'is_full_refund' => $isFullRefund,
                    'refund_created_at' => now()->toIso8601String(),
                ],
            ]);

            Log::info('Refund transaction created', [
                'original_transaction_id' => $originalTransaction->id,
                'refund_amount' => $refundAmount,
            ]);
        }

        // Update original transaction
        $originalTransaction->update([
            'metadata' => array_merge($originalTransaction->metadata ?? [], [
                'refunded' => true,
                'refund_amount' => $refundAmount,
                'is_full_refund' => $isFullRefund,
            ]),
        ]);

        return true;
    }

    /**
     * Dispute created - IMPORTANT for chargeback tracking
     */
    private function handleDisputeCreated(object $dispute): bool
    {
        Log::error('Stripe dispute created (chargeback)', [
            'dispute_id' => $dispute->id,
            'charge_id' => $dispute->charge,
            'amount' => $dispute->amount / 100,
            'reason' => $dispute->reason,
            'status' => $dispute->status,
        ]);

        // Find original transaction
        $transaction = BankTransaction::where('external_id', $dispute->charge)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'disputed',
                'notes' => 'DISPUTE: ' . $dispute->reason,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'dispute_id' => $dispute->id,
                    'dispute_reason' => $dispute->reason,
                    'dispute_status' => $dispute->status,
                    'dispute_amount' => $dispute->amount / 100,
                    'dispute_created_at' => now()->toIso8601String(),
                ]),
            ]);

            // TODO: Send notification to admin about dispute
            Log::critical('Dispute requires immediate attention', [
                'transaction_id' => $transaction->id,
                'dispute_id' => $dispute->id,
            ]);
        }

        return true;
    }

    /**
     * Dispute closed
     */
    private function handleDisputeClosed(object $dispute): bool
    {
        Log::info('Dispute closed', [
            'dispute_id' => $dispute->id,
            'status' => $dispute->status, // won, lost, withdrawn
        ]);

        $transaction = BankTransaction::where('external_id', $dispute->charge)->first();

        if ($transaction) {
            $status = match ($dispute->status) {
                'won' => 'completed',
                'lost' => 'chargeback_lost',
                default => 'completed',
            };

            $transaction->update([
                'status' => $status,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'dispute_outcome' => $dispute->status,
                    'dispute_closed_at' => now()->toIso8601String(),
                ]),
            ]);
        }

        return true;
    }

    /**
     * Payout created - Track money moving to bank
     */
    private function handlePayoutCreated(object $payout): bool
    {
        Log::info('Payout created', [
            'payout_id' => $payout->id,
            'amount' => $payout->amount / 100,
            'arrival_date' => date('Y-m-d', $payout->arrival_date),
            'status' => $payout->status,
        ]);

        // Track payout for cash flow monitoring
        // This is money leaving Stripe to your bank account

        return true;
    }

    /**
     * Payout paid - Money arrived at bank
     */
    private function handlePayoutPaid(object $payout): bool
    {
        Log::info('Payout paid', [
            'payout_id' => $payout->id,
            'amount' => $payout->amount / 100,
            'arrival_date' => date('Y-m-d', $payout->arrival_date),
        ]);

        // Update payout tracking
        // This confirms money has arrived at the bank account

        return true;
    }

    /**
     * Payout failed
     */
    private function handlePayoutFailed(object $payout): bool
    {
        Log::error('Payout failed', [
            'payout_id' => $payout->id,
            'amount' => $payout->amount / 100,
            'failure_code' => $payout->failure_code,
            'failure_message' => $payout->failure_message,
        ]);

        // Alert admin about failed payout
        // This is critical as it affects cash flow

        return true;
    }

    /**
     * Transfer created (Connect)
     */
    private function handleTransferCreated(object $transfer): bool
    {
        Log::info('Transfer created', [
            'transfer_id' => $transfer->id,
            'amount' => $transfer->amount / 100,
            'destination' => $transfer->destination,
        ]);

        return true;
    }

    /**
     * Application fee created (Connect - your commission)
     */
    private function handleApplicationFeeCreated(object $fee): bool
    {
        Log::info('Application fee created', [
            'fee_id' => $fee->id,
            'amount' => $fee->amount / 100,
            'charge' => $fee->charge,
        ]);

        // This is OPPLA's commission on partner transactions
        // Important for commission invoicing

        return true;
    }

    /**
     * Connected account updated
     */
    private function handleAccountUpdated(object $account): bool
    {
        Log::info('Connected account updated', [
            'account_id' => $account->id,
            'payouts_enabled' => $account->payouts_enabled ?? null,
            'charges_enabled' => $account->charges_enabled ?? null,
        ]);

        // Update local client record if linked to Stripe Connect account
        $client = Client::where('stripe_account_id', $account->id)->first();

        if ($client) {
            $client->update([
                'stripe_payouts_enabled' => $account->payouts_enabled ?? false,
                'stripe_charges_enabled' => $account->charges_enabled ?? false,
            ]);
        }

        return true;
    }

    /**
     * Checkout session completed - pagamento ricevuto tramite link
     */
    private function handleCheckoutSessionCompleted(object $session): bool
    {
        Log::info('Checkout session completed', [
            'session_id' => $session->id,
            'amount_total' => ($session->amount_total ?? 0) / 100,
            'customer_email' => $session->customer_details->email ?? null,
            'payment_intent' => $session->payment_intent ?? null,
        ]);

        $checkoutSession = CheckoutSession::where('stripe_session_id', $session->id)->first();

        if ($checkoutSession) {
            $checkoutSession->update([
                'status' => 'complete',
                'payment_intent_id' => $session->payment_intent ?? null,
                'customer_email' => $session->customer_details->email ?? $checkoutSession->customer_email,
                'completed_at' => now(),
            ]);

            Log::info('Checkout session aggiornata a complete', [
                'id' => $checkoutSession->id,
                'amount' => $checkoutSession->amount,
            ]);
        } else {
            Log::warning('Checkout session non trovata nel DB', [
                'stripe_session_id' => $session->id,
            ]);
        }

        return true;
    }

    /**
     * Checkout session expired - link scaduto senza pagamento
     */
    private function handleCheckoutSessionExpired(object $session): bool
    {
        Log::info('Checkout session expired', [
            'session_id' => $session->id,
        ]);

        $checkoutSession = CheckoutSession::where('stripe_session_id', $session->id)->first();

        if ($checkoutSession) {
            $checkoutSession->update([
                'status' => 'expired',
            ]);
        }

        return true;
    }

    /**
     * Handle unknown/unhandled events
     */
    private function handleUnknownEvent(object $event): bool
    {
        Log::debug('Unhandled Stripe webhook event', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        return false;
    }
}
