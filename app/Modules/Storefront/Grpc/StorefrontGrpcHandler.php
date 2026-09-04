<?php

namespace App\Modules\Storefront\Grpc;

use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Models\ShopItem;
use App\Modules\Storefront\Services\GatewayClient;
use Pay\Storefront\V1\ResolveRedirectTargetRequest;
use Pay\Storefront\V1\ResolveRedirectTargetRequest\TargetType;
use Pay\Storefront\V1\ResolveRedirectTargetResponse;
use Pay\Storefront\V1\StorefrontServiceInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;

/**
 * Faza 5: gateway-svc jako jedyny właściciel wiedzy o slugach/handle'ach/
 * statusie aktywności sklepu — konsument: api-gateway, przy budowie
 * przekierowania po rozwiązaniu kodu inicjalizacji (core-svc, InitCodeService).
 *
 * WYŁĄCZNIE dodanie: nowa metoda na serwerze gRPC, który i tak nie jest
 * dziś uruchamiany automatycznie w produkcji (patrz grpc-worker.php) — zero
 * wpływu na istniejący ruch HTTP obsługiwany przez app/Modules/Init/**,
 * które w tym kroku pozostaje bez zmian.
 */
class StorefrontGrpcHandler implements StorefrontServiceInterface
{
    public function ResolveRedirectTarget(ContextInterface $ctx, ResolveRedirectTargetRequest $in): ResolveRedirectTargetResponse
    {
        $urlPath = null;
        $active = false;

        if ($in->getTargetType() === TargetType::SHOP_ITEM) {
            $shopItem = ShopItem::find($in->getTargetId());
            if ($shopItem !== null) {
                $urlPath = '/?produkt=' . urlencode($shopItem->slug);
                $active = (bool) $shopItem->active;
            }
        } elseif ($in->getTargetType() === TargetType::ORGANIZATION) {
            $organization = Organization::find($in->getTargetId());
            if ($organization !== null) {
                $urlPath = '/people/' . urlencode($organization->handle);
                $active = true;
            }
        }

        $found = $urlPath !== null;

        // Ten handler jest wołany WYŁĄCZNIE z publicznego przepływu skanu
        // (api-gateway -> core-svc.Resolve -> tu) — każde znalezione i
        // aktywne trafienie to realny skan, bezpiecznie zgłaszamy zdarzenie
        // tak samo jak dzisiejszy InitController::show(). Synchronicznie,
        // NIE dispatchAfterResponse — ten worker gRPC nigdy nie wywołuje
        // hooka terminate() z HTTP Kernela, na którym opiera się
        // dispatchAfterResponse (patrz claude/marcin, sekcja Faza 5).
        if ($found && $active) {
            app(GatewayClient::class)->sendEvent('tag_open', $in->getSourceUuid());
        }

        return new ResolveRedirectTargetResponse([
            'found' => $found,
            'active' => $active,
            'url_path' => $urlPath ?? '',
        ]);
    }
}
