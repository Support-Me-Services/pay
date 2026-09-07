<?php

namespace App\Modules\Storefront\Models;

use App\Services\OrgSvcClient;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Organizacja — byt nad kontem (User). Jedno konto może zarządzać wieloma
 * organizacjami; każda ma własne „O nas"/Zbiórki/Praca/Aplikacje/Baza
 * kandydatów oraz samoobsługową widoczność tych 5 sekcji (enabled_sections).
 *
 * Faza 6 migracji: dane żyją WYŁĄCZNIE w org-svc — to już nie model Eloquent
 * (żadnej lokalnej tabeli), tylko zwykła klasa PHP z tym samym publicznym API
 * (id/name/handle/canSee()/find()/rootOrganization()), żeby reszta kodu
 * (dziesiątki miejsc czytających organizację) nie musiała się zmieniać.
 */
class Organization
{
    /** Klucze sekcji sterowanych widocznością — mirror User::SECTIONS. */
    public const SECTIONS = [
        'beneficiaries' => 'O nas',
        'shop-items' => 'Zbiórki',
        'positions' => 'Praca',
        'applications' => 'Aplikacje / Baza kandydatów',
        'init-codes' => 'Tagi NFC / Kody QR',
    ];

    public int $id;
    public string $user_id;
    public string $name;
    public string $handle;
    public ?string $logo;
    public ?array $enabled_sections;

    private function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->user_id = $data['userId'];
        $this->name = $data['name'];
        $this->handle = $data['handle'];
        $this->logo = $data['logo'] ?? null;
        $this->enabled_sections = $data['enabledSections'] ?? null;
    }

    /** Owija surową odpowiedź org-svc (id, userId, name, handle, logo, enabledSections). */
    public static function fromOrgSvc(array $orgSvcResponse): self
    {
        return new self($orgSvcResponse);
    }

    public static function find(int $id): ?self
    {
        try {
            return self::fromOrgSvc(app(OrgSvcClient::class)->getOrganization($id));
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function findOrFail(int $id): self
    {
        return self::find($id) ?? throw new ModelNotFoundException("Organization {$id} not found");
    }

    public static function findByHandle(string $handle): ?self
    {
        // org-svc nie ma dziś RPC "po handle" — pobieramy wszystkie i filtrujemy
        // (te same listy i tak trzeba trzymać w pamięci gdzie indziej; liczba
        // organizacji w tym systemie jest mała, patrz plan migracji).
        foreach (app(OrgSvcClient::class)->listAllOrganizations() as $o) {
            if ($o['handle'] === $handle) {
                return self::fromOrgSvc($o);
            }
        }

        return null;
    }

    public static function findByHandleOrFail(string $handle): self
    {
        return self::findByHandle($handle) ?? throw new ModelNotFoundException("Organization handle={$handle} not found");
    }

    /** Wszystkie organizacje systemu, posortowane po nazwie (dropdown mecenasa/admina). */
    public static function allOrderedByName(): array
    {
        $items = app(OrgSvcClient::class)->listAllOrganizations();
        usort($items, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return array_map(fn (array $o) => self::fromOrgSvc($o), $items);
    }

    /** Organizacje danego konta, posortowane po nazwie (org-svc sortuje po swojej stronie). */
    public static function byOwnerOrderedByName(string $userId): array
    {
        return array_map(fn (array $o) => self::fromOrgSvc($o), app(OrgSvcClient::class)->listOrganizationsByOwner($userId));
    }

    /**
     * Czy TA organizacja ma widoczną daną sekcję. Self-service — bez
     * nadpisania is_admin (to platformowa flaga na User, nie na Organization).
     * `enabled_sections === null` oznacza „wszystko widoczne" (domyślne).
     */
    public function canSee(string $section): bool
    {
        return $this->enabled_sections === null || in_array($section, $this->enabled_sections, true);
    }

    /**
     * Domyślna organizacja na globalnych, wspólnych stronach publicznych
     * (/, /beneficiaries, /praca) — sprzed rejestracji istniało tylko jedno
     * konto/jedna organizacja, więc te strony zostają przypięte do niej.
     *
     * Faza 6 migracji: najstarsza (najmniejsze ID) organizacja w org-svc —
     * wcześniej pytaliśmy najpierw `User::rootOwner()` (MySQL) o e-mail
     * konta i szukaliśmy JEGO organizacji; to nie tylko zbędny round-trip
     * do bazy, której już nie potrzebujemy do niczego innego na tych
     * stronach, ale i wierniejszy oryginalnej intencji komentarza wyżej —
     * "pierwsza organizacja jaka kiedykolwiek powstała" wprost, bez
     * pośredniczenia przez konkretny, zahardkodowany e-mail administratora.
     */
    public static function rootOrganization(): ?self
    {
        return self::allOrderedByIdAsc()[0] ?? null;
    }

    private static function allOrderedByIdAsc(): array
    {
        $items = app(OrgSvcClient::class)->listAllOrganizations();
        usort($items, fn ($a, $b) => $a['id'] <=> $b['id']);

        return array_map(fn (array $o) => self::fromOrgSvc($o), $items);
    }
}
