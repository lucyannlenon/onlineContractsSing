<?php

namespace App\Message;

/**
 * Dispatched the moment a contract is fully signed, so the PDF generation and
 * the finish notification happen within seconds instead of waiting for the
 * periodic scheduler. The periodic tasks remain as an idempotent fallback.
 */
readonly class FinalizeContractNotification
{
    public function __construct(
        public int $contractId
    ) {
    }
}