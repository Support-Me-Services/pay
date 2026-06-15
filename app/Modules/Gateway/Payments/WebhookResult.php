<?php

namespace App\Modules\Gateway\Payments;

class WebhookResult
{
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';

    public function __construct(
        public readonly bool $valid,
        public readonly ?string $transactionId = null, // uuid transakcji bramki (extOrderId)
        public readonly string $status = self::STATUS_IGNORED,
    ) {
    }
}
