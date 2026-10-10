# Contract: points d'extension des layers existants

Trois layers existants reçoivent un point d'extension générique. Aucun ne mentionne la vente : ils exposent un contrat et un registre, que sales remplit depuis `SalesServiceProvider::boot()`. Sans enregistrement, leur comportement est strictement celui d'aujourd'hui.

> **Modification d'un layer existant** : chaque section ci-dessous touche un layer d'une feature précédente. Arbitrage de la coordination : toutes sont réalisées dans la branche 007 (Phase 2 de `tasks.md`), E1 et E2 après la remise à jour sur les corrections de 001 et 003.

## E1. booking : gardes de création de réservation

```php
namespace Functional\Booking\Contracts;

interface ReservationRequestGuard
{
    /** @throws \Functional\Fleet\Exceptions\RefusalException */
    public function ensureCanReserve(Machine $lockedMachine, CarbonImmutable $startDate, CarbonImmutable $endDate): void;

    /** @param Builder<Machine> $machines */
    public function excludeUnavailable(Builder $machines, CarbonImmutable $startDate, CarbonImmutable $endDate): void;
}
```

```php
namespace Functional\Booking\Extensions;

final class ReservationRequestGuards   // singleton, même forme que ReservationTransitionGuards
{
    /** @param class-string<ReservationRequestGuard> $guardClass */
    public function register(string $guardClass): void;

    /** @return list<ReservationRequestGuard> */
    public function all(): array;
}
```

Points d'appel (booking) :
- `CreateReservation::reserve()` : après `MachineEligibility::ensureReservableUntil()`, sous le verrou `machines`, avant la recherche de chevauchement : `foreach ($guards->all() as $guard) $guard->ensureCanReserve($lockedMachine, $startDate, $endDate);`
- `AvailableMachinesQuery` : chaque garde reçoit le builder des machines pour la période recherchée.

Rempli par sales : `ReservedSaleReservationGuard` refuse si une vente `reserved` de la machine a `planned_handover_date <= $endDate` (`MachineReservedForSaleException::until(Sale $sale)`, clé `sales::refusals.machine_reserved_for_sale`, texte : « Machine vendue sous réserve, remise prévue le JJ/MM/AAAA ») ; dans `excludeUnavailable`, il ajoute `whereNotExists` sur ces ventes.

## E2. fleet : gardes de retrait

```php
namespace Functional\Fleet\Extensions;

final class MachineRetirementGuards   // singleton
{
    /** @param class-string<MachineRetirementGuard> $guardClass */
    public function register(string $guardClass): void;

    /** @return list<MachineRetirementGuard> */
    public function all(): array;
}
```

Changements :
- `RetireMachine` reçoit `MachineRetirementGuards` au lieu de `MachineRetirementGuard` et appelle `ensureCanRetire()` sur chaque garde, sous le verrou existant.
- `FleetServiceProvider` : `singleton(MachineRetirementGuards::class)` ; suppression du `bindIf(MachineRetirementGuard::class, UnrestrictedRetirement::class)` et de `Guards/UnrestrictedRetirement.php`. Le contrat `MachineRetirementGuard` est conservé.
- `BookingServiceProvider` : `$this->app->make(MachineRetirementGuards::class)->register(ActiveReservationsRetirementGuard::class)` dans `boot()`, à la place du `bind()` dans `register()`.
- Les tests de retrait existants (001) restent verts sans modification de comportement.

Rempli par sales : `OpenSaleRetirementGuard` refuse si la machine a une vente `listed` ou `reserved` (`MachineHasOpenSaleException::for(Sale $sale)`, classe `final` de sales qui étend `RefusalException`, clé `sales::refusals.machine_has_open_sale`).

La remise d'une vente (`HandOverSale`) passe la vente à `sold` **avant** d'appeler `RetireMachine` dans la même transaction : le garde de sales ne voit plus de vente ouverte, le garde de booking contrôle toujours les réservations, et un refus annule toute la transaction.

## E3. fleet : badges de machine

```php
namespace Functional\Fleet\Contracts;

interface MachineBadgeProvider
{
    /**
     * @param  list<int>  $machineIds
     * @return array<int, list<MachineBadge>>  indexé par machine_id
     */
    public function badgesFor(array $machineIds): array;

    /**
     * Événements temps réel qui doivent rafraîchir les badges (noms d'écouteurs Livewire, ex. « echo-private:<canal>,.<événement> »).
     *
     * @return list<string>
     */
    public function refreshListeners(): array;
}
```

```php
namespace Functional\Fleet\ValueObjects;

final readonly class MachineBadge
{
    public function __construct(public string $label, public string $color, public ?string $url = null) {}
}
```

```php
namespace Functional\Fleet\Extensions;

final class MachineBadges   // singleton
{
    /** @param class-string<MachineBadgeProvider> $providerClass */
    public function register(string $providerClass): void;

    /** @param list<int> $machineIds @return array<int, list<MachineBadge>> */
    public function forMachines(array $machineIds): array;   // fusionne les fournisseurs, une requête par fournisseur

    /** @return list<string> */
    public function refreshListeners(): array;               // union des refreshListeners() des fournisseurs
}
```

Affichage : `fleet::livewire.machine-index`, `booking::livewire.planning`, `booking::livewire.availability-search` affichent les badges à côté de la référence (`flux:badge`, lien si `url`). Les trois composants ajoutent `MachineBadges::refreshListeners()` à leurs écouteurs via `getListeners()` (FR-025 : un badge change sans rechargement).

Rempli par sales : `SaleMachineBadges` : « En vente » (bleu) pour `listed`, « Vendue sous réserve — remise le JJ/MM » (ambre) pour `reserved`, lien vers la vente ; `refreshListeners()` = `['echo-private:sales,.sale.changed']`.

## E4. billing : sources de transmission

```php
namespace Functional\Billing\Contracts;

interface BillableSource
{
    public function type(): BillableLineType;

    public function line(Transmission $transmission): BillableLine;

    public function subject(Transmission $transmission): TransmissionSubject;
}
```

```php
namespace Functional\Billing\ValueObjects;

final readonly class TransmissionSubject
{
    public function __construct(
        public string $label,              // ex. « Vente NAC-0042 »
        public int $customerId,
        public string $customerName,
        public string $url,                // écran d'origine
        public Model $historySubject,      // modèle qui reçoit l'activité « billing »
    ) {}
}
```

```php
namespace Functional\Billing\Extensions;

final class BillableSources   // singleton
{
    /** @param class-string<BillableSource> $sourceClass */
    public function register(string $sourceClass): void;

    /** @throws UnknownBillableSourceException  final, étend RefusalException, factory forType() */
    public function for(BillableLineType $type): BillableSource;
}
```

```php
namespace Functional\Billing\Actions;

final class QueueSourceTransmission
{
    /** Idempotent : renvoie la transmission existante pour (type, id). Envoi après commit. */
    public function handle(BillableLineType $type, int $sourceId): Transmission;
}
```

Changements dans billing :
- migration additive sur `transmissions` (voir data-model) ;
- `BillableLineType::UsedMachineSale = 'used_machine_sale'` ;
- `BillableLine` : `reservationRef` et `bookingAgency` nullables ; nouveaux champs `sourceRef` et `saleDate` (nullables), présents dans `toArray()` et donc dans l'export (`Exports/ExportLineFormatter`) ; le montant reste `amountExclTax: ?Money` (forme de `bdef4c6`) ;
- `MakeBillableLine` : si `source_type` est renseigné, délègue à `BillableSources::for($type)->line()` ;
- `BillingHistory::record()` accepte un `Model` ; `TransmissionLifecycle` rattache l'activité à la réservation, ou au `historySubject` de la source ;
- écran `Transmissions` : pour une transmission de source, affiche `subject()` (libellé, lien, client, saisie de l'identifiant client) au lieu de la réservation ; chargement par lot (pas de requête dans une boucle) ;
- `ReservationBillingSection` et `BillingStatement` inchangés (ils filtrent par réservation).

Rempli par sales : `SaleBillableSource` (type `UsedMachineSale`) : voir `billing-sale-line.md`.
