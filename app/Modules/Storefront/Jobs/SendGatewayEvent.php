<?php

namespace App\Modules\Storefront\Jobs;

use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Asynchroniczna wysyłka eventu do bramki — dispatchAfterResponse(),
 * żeby nie opóźniać redirectu klienta (bez potrzeby workera kolejki).
 */
class SendGatewayEvent
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly ?string $tagUid = null,
    ) {
    }

    public function handle(GatewayClient $gateway): void
    {
        $gateway->sendEvent($this->type, $this->tagUid);
    }
}
