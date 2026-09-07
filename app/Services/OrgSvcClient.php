<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Klient REST do org-svc (przez api-gateway, prefiks /internal/v1/organizations —
 * patrz InternalOrganizationController.java), auth: X-Internal-Api-Key. Osobna
 * klasa od Modules\Storefront\Services\GatewayClient — to inny "Gateway"
 * (płatności PayU), nazwy celowo nie mają się mylić.
 *
 * org-svc jest źródłem prawdy dla ZAPISU organizacji (Faza migracji, patrz
 * plan). Lokalna tabela `organizations` w MySQL zostaje jako zsynchronizowane
 * lustro (nadal celem 7 kluczy obcych z innych tabel) — wywołujący musi po
 * każdym zapisie przez ten klient zaktualizować lokalny wiersz (patrz
 * OrganizationsController/UsersController).
 */
class OrgSvcClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.api_gateway_base_url');
        $this->apiKey = config('services.api_gateway_internal_key');
    }

    public function create(string $userId, string $name): array
    {
        return $this->send('post', '/internal/v1/organizations', [
            'userId' => $userId,
            'name' => $name,
        ]);
    }

    public function updateName(int $id, string $userId, string $name): array
    {
        return $this->send('put', "/internal/v1/organizations/{$id}/name", [
            'userId' => $userId,
            'name' => $name,
        ]);
    }

    /** @param string[]|null $sections null = wszystko widoczne */
    public function updateSections(int $id, string $userId, ?array $sections): array
    {
        return $this->send('put', "/internal/v1/organizations/{$id}/sections", [
            'userId' => $userId,
            'sections' => $sections,
        ]);
    }

    /** Wyłącznie admin (bramkowane w wywołującym) — przepina organizację na innego użytkownika. */
    public function updateOwner(int $id, string $newUserId): array
    {
        return $this->send('put', "/internal/v1/organizations/{$id}/owner", [
            'newUserId' => $newUserId,
        ]);
    }

    public function getOrganization(int $id): array
    {
        return $this->fetch('get', "/internal/v1/organizations/{$id}");
    }

    /** @return array<int, array> */
    public function listOrganizationsByOwner(string $userId): array
    {
        return $this->fetch('get', "/internal/v1/organizations?userId={$userId}");
    }

    /** Admin: WSZYSTKIE organizacje systemu. */
    public function listAllOrganizations(): array
    {
        return $this->fetch('get', '/internal/v1/organizations');
    }

    // --- Węzły "O nas" (Faza 2 migracji) ---

    public function createBeneficiaryNode(array $payload): array
    {
        return $this->send('post', '/internal/v1/beneficiary-nodes', $payload);
    }

    public function updateBeneficiaryNode(int $id, array $payload): array
    {
        return $this->send('put', "/internal/v1/beneficiary-nodes/{$id}", $payload);
    }

    /** @return array{deleted: bool, deletedImage: string} */
    public function deleteBeneficiaryNode(int $id, int $organizationId): array
    {
        return $this->fetch('delete', "/internal/v1/beneficiary-nodes/{$id}?organizationId={$organizationId}");
    }

    /** @return array<int, array> */
    public function listBeneficiaryNodes(int $organizationId, bool $activeOnly = false): array
    {
        $query = $activeOnly ? '&activeOnly=true' : '';

        return $this->fetch('get', "/internal/v1/beneficiary-nodes?organizationId={$organizationId}{$query}");
    }

    /** @param int[] $orderedIds */
    public function reorderBeneficiaryNodes(int $organizationId, array $orderedIds): void
    {
        $this->send('post', '/internal/v1/beneficiary-nodes/reorder', [
            'organizationId' => $organizationId,
            'orderedIds' => $orderedIds,
        ]);
    }

    // --- Produkty sklepu (Faza 3 migracji) ---

    public function createShopItem(array $payload): array
    {
        return $this->send('post', '/internal/v1/shop-items', $payload);
    }

    public function updateShopItem(int $id, array $payload): array
    {
        return $this->send('put', "/internal/v1/shop-items/{$id}", $payload);
    }

    public function deleteShopItem(int $id, int $organizationId): void
    {
        $response = $this->request()->delete($this->baseUrl . "/internal/v1/shop-items/{$id}?organizationId={$organizationId}");
        if (! $response->successful()) {
            throw new RuntimeException('org-svc niedostępny (HTTP ' . $response->status() . ')');
        }
    }

    public function toggleShopItem(int $id, int $organizationId): array
    {
        return $this->send('post', "/internal/v1/shop-items/{$id}/toggle?organizationId={$organizationId}", []);
    }

    public function getShopItem(int $id): array
    {
        return $this->fetch('get', "/internal/v1/shop-items/{$id}");
    }

    public function listShopItems(?int $organizationId = null, bool $activeOnly = false): array
    {
        $query = [];
        if ($organizationId !== null) {
            $query[] = "organizationId={$organizationId}";
        }
        if ($activeOnly) {
            $query[] = 'activeOnly=true';
        }
        $qs = $query ? '?' . implode('&', $query) : '';

        return $this->fetch('get', "/internal/v1/shop-items{$qs}");
    }

    // --- Stanowiska pracy (Faza 4 migracji) ---

    public function createJobPosition(array $payload): array
    {
        return $this->send('post', '/internal/v1/job-positions', $payload);
    }

    public function updateJobPosition(int $id, array $payload): array
    {
        return $this->send('put', "/internal/v1/job-positions/{$id}", $payload);
    }

    public function toggleJobPosition(int $id, int $organizationId): array
    {
        return $this->send('post', "/internal/v1/job-positions/{$id}/toggle?organizationId={$organizationId}", []);
    }

    public function deleteJobPosition(int $id, int $organizationId): void
    {
        $response = $this->request()->delete($this->baseUrl . "/internal/v1/job-positions/{$id}?organizationId={$organizationId}");
        if (! $response->successful()) {
            throw new RuntimeException('org-svc niedostępny (HTTP ' . $response->status() . ')');
        }
    }

    public function getJobPosition(int $id): array
    {
        return $this->fetch('get', "/internal/v1/job-positions/{$id}");
    }

    public function listJobPositions(int $organizationId, bool $activeOnly = false): array
    {
        $query = $activeOnly ? '&activeOnly=true' : '';

        return $this->fetch('get', "/internal/v1/job-positions?organizationId={$organizationId}{$query}");
    }

    // --- Aplikacje o pracę (Faza 4 migracji) ---

    public function createJobApplication(array $payload): array
    {
        return $this->send('post', '/internal/v1/job-applications', $payload);
    }

    public function getJobApplication(int $id): array
    {
        return $this->fetch('get', "/internal/v1/job-applications/{$id}");
    }

    public function listJobApplications(int $organizationId, ?int $jobPositionId = null, ?string $status = null, bool $activeFutureConsent = false): array
    {
        $query = "organizationId={$organizationId}";
        if ($jobPositionId !== null) {
            $query .= "&jobPositionId={$jobPositionId}";
        }
        if ($status !== null) {
            $query .= '&status=' . urlencode($status);
        }
        if ($activeFutureConsent) {
            $query .= '&activeFutureConsent=true';
        }

        return $this->fetch('get', "/internal/v1/job-applications?{$query}");
    }

    public function updateJobApplicationStatus(int $id, int $organizationId, string $status): array
    {
        return $this->send('put', "/internal/v1/job-applications/{$id}/status?organizationId={$organizationId}", ['status' => $status]);
    }

    public function markJobApplicationRead(int $id, int $organizationId): array
    {
        return $this->send('post', "/internal/v1/job-applications/{$id}/mark-read?organizationId={$organizationId}", []);
    }

    /** @return array{deleted: bool, deletedCvPath: string} */
    public function deleteJobApplication(int $id, int $organizationId): array
    {
        return $this->fetch('delete', "/internal/v1/job-applications/{$id}?organizationId={$organizationId}");
    }

    // --- Kody inicjalizacji kontaktu / tagi NFC/QR (Faza 5 migracji) ---
    // Uwaga: to jedyna domena, która żyje w core-svc, nie org-svc — ten sam
    // api-gateway, ten sam klucz, stąd współdzielony klient (patrz plan migracji).

    /** @param array{organizationId?: int, ownerUserId?: string} $owner dokładnie jeden klucz */
    public function createInitCode(array $owner, ?string $label, ?int $shopItemId, ?int $targetOrganizationId): array
    {
        return $this->send('post', '/internal/v1/init-codes', [
            'owner' => $owner,
            'label' => $label ?? '',
            'shopItemId' => $shopItemId,
            'targetOrganizationId' => $targetOrganizationId,
        ]);
    }

    public function updateInitCode(int $id, array $owner, ?string $label, ?int $shopItemId, ?int $targetOrganizationId): array
    {
        return $this->send('put', "/internal/v1/init-codes/{$id}", [
            'owner' => $owner,
            'label' => $label ?? '',
            'shopItemId' => $shopItemId,
            'targetOrganizationId' => $targetOrganizationId,
        ]);
    }

    public function toggleInitCode(int $id, array $owner): array
    {
        return $this->send('post', "/internal/v1/init-codes/{$id}/toggle", ['owner' => $owner]);
    }

    public function deleteInitCode(int $id, array $owner): void
    {
        $response = $this->request()->send('delete', $this->baseUrl . "/internal/v1/init-codes/{$id}", ['json' => ['owner' => $owner]]);
        if (! $response->successful()) {
            throw new RuntimeException('core-svc niedostępny (HTTP ' . $response->status() . ')');
        }
    }

    public function listInitCodes(?int $organizationId = null, ?string $ownerUserId = null): array
    {
        $query = [];
        if ($organizationId !== null) $query[] = "organizationId={$organizationId}";
        if ($ownerUserId !== null) $query[] = "ownerUserId={$ownerUserId}";
        $qs = $query ? '?' . implode('&', $query) : '';

        return $this->fetch('get', "/internal/v1/init-codes{$qs}");
    }

    /** @return array{found: bool, targetType: string, targetId: int, uuid: string} */
    public function resolveInitCode(string $uuid): array
    {
        return $this->fetch('get', '/internal/v1/init-codes/resolve/' . urlencode($uuid));
    }

    private function fetch(string $method, string $path): array
    {
        $response = $this->request()->{$method}($this->baseUrl . $path);

        if (! $response->successful()) {
            Log::error('org-svc: wywołanie nieudane', ['method' => $method, 'path' => $path, 'http' => $response->status()]);
            throw new RuntimeException('org-svc niedostępny (HTTP ' . $response->status() . ')');
        }

        return $response->json();
    }

    private function send(string $method, string $path, array $payload): array
    {
        $response = $this->request()->{$method}($this->baseUrl . $path, $payload);

        if (! $response->successful()) {
            Log::error('org-svc: wywołanie nieudane', [
                'method' => $method,
                'path' => $path,
                'http' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('org-svc niedostępny (HTTP ' . $response->status() . ')');
        }

        return $response->json();
    }

    private function request()
    {
        return Http::withHeaders(['X-Internal-Api-Key' => $this->apiKey])
            ->acceptJson()
            ->timeout(5);
    }
}
