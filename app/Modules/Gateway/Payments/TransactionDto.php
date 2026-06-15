<?php

namespace App\Modules\Gateway\Payments;

class TransactionDto
{
    public function __construct(
        public readonly string $providerOrderId,
        public readonly string $redirectUrl,
        public readonly string $status = 'pending',
    ) {
    }
}
