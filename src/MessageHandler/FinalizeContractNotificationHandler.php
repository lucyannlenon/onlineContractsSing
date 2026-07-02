<?php

namespace App\MessageHandler;

use App\Message\FinalizeContractNotification;
use App\Repository\ContractsRepository;
use App\Services\GeneratePdfContract;
use App\Services\NotificationContractServer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Fast path for the finish notification. Every step acts on the same persisted
 * state used by the periodic scheduler (signature link, contract "notified"),
 * so it is fully idempotent: whichever runs first wins and the other no-ops.
 * If anything here fails, the periodic tasks (GenerateContractTask /
 * NotificationFinishContractTask) still complete the work — the user never
 * perceives the failure.
 */
#[AsMessageHandler]
readonly class FinalizeContractNotificationHandler
{
    public function __construct(
        private ContractsRepository        $contracts,
        private GeneratePdfContract        $pdf,
        private NotificationContractServer $notifier,
        private LoggerInterface            $logger
    ) {
    }

    public function __invoke(FinalizeContractNotification $message): void
    {
        $contract = $this->contracts->find($message->contractId);

        if ($contract === null) {
            $this->logger->warning('Finalize handler: contract not found', [
                'contract_id' => $message->contractId,
            ]);
            return;
        }

        if ($contract->isNotified()) {
            // Already handled (by a previous run or the periodic fallback).
            return;
        }

        // Generate any still-pending PDFs for this contract (only link === null).
        $this->pdf->executeForContract($contract);

        // notify() re-checks that every signature has a link, sends the webhook
        // and flips "notified" on success; it swallows transient webhook errors,
        // which the periodic fallback then retries.
        $this->notifier->notify($contract);
    }
}