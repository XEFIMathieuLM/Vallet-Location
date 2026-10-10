# Contrat : points d'extension de `booking` utilisés par `accounts`

## Existants (livrés par la 001)

| Point d'extension | Usage par `accounts` |
|-------------------|----------------------|
| `ReservationTransitionGuards` + `ReservationTransitionGuard` | `PurchaseOrderDepartureGuard::beforeDeparture` lève `MissingPurchaseOrderException` (G3) ; `beforeReturn` sans effet |
| `ReservationDetailSections` | composant `accounts.purchase-order-section`, position 15 (G4) |
| readiness par section (contrat de la 001) | la section se prononce pour `ReservationTransition::Departure` (G4) |
| `RefusalException` (fleet) | toutes les exceptions de refus de `accounts` en héritent : message technique anglais, clé de traduction `accounts::refusals.*`, factories nommées ; testées avec `AssertsRefusals` |

## Nouveau : `CustomerBadges` (G5)

Même forme que `MachineBadges` de la 007 dans `fleet`, pour que les deux registres se lisent de la même façon.

```php
namespace Functional\Booking\Contracts;

interface CustomerBadgeProvider
{
    /**
     * @param  list<int>  $customerIds
     * @return array<int, list<CustomerBadge>>  indexé par customer_id
     */
    public function badgesFor(array $customerIds): array;

    /**
     * Événements temps réel qui doivent rafraîchir les badges (noms d'écouteurs Livewire).
     *
     * @return list<string>
     */
    public function refreshListeners(): array;
}
```

```php
namespace Functional\Booking\ValueObjects;

final readonly class CustomerBadge
{
    public function __construct(public string $label, public string $color, public ?string $url = null, public ?string $description = null) {}
}
```

```php
namespace Functional\Booking\Extensions;

final class CustomerBadges   // singleton
{
    /** @param class-string<CustomerBadgeProvider> $providerClass */
    public function register(string $providerClass): void;

    /** @param list<int> $customerIds @return array<int, list<CustomerBadge>> */
    public function forCustomers(array $customerIds): array;   // fusionne les fournisseurs, une requête par fournisseur

    /** @return list<string> */
    public function refreshListeners(): array;                 // union des refreshListeners() des fournisseurs
}
```

- `description` est affichée pour le client sélectionné ; `label` dans la liste (`flux:badge` de couleur `color`, lien si `url`).
- `CreateReservationForm` appelle `forCustomers` une seule fois sur la liste de clients affichée (au plus 20) et pour le client sélectionné. Sans fournisseur, rien n'est affiché.
- `accounts` enregistre `KeyAccountBadgeProvider` : « Grand compte », description « Tarif négocié appliqué par la facturation — bon de commande exigé avant la sortie », lien vers `/grands-comptes` ; une requête `whereIn` sur `key_accounts` ; `refreshListeners()` vide (la liste de clients se recharge à chaque recherche).

## Livré par la 004 : garde sur le changement de type du client (G6)

```php
interface CustomerChangeGuard
{
    /**
     * @throws RefusalException
     */
    public function beforeTypeChange(Customer $customer, ?CustomerType $newType): void;
}
```

- Registre `CustomerChangeGuards`, livré par la 004 et appelé par la méthode de qualification du type de `Functional\Booking\Actions\UpdateCustomer`, dans sa transaction et après verrouillage du client.
- `accounts` enregistre `KeyAccountTypeGuard` : refuse (`KeyAccountMustStayProfessionalException`) tout nouveau type autre que professionnel pour un client grand compte (FR-003).
- Noms définitifs de l'enum de type et de la méthode repris de la 004 au rebase.

## Événement `CustomerChanged` (convention de la coordination)

`Functional\Booking\Events\CustomerChanged` (`ShouldDispatchAfterCommit`), introduit avec `UpdateCustomer`. `DesignateKeyAccount` et `RevokeKeyAccount` l'émettent après commit ; elles n'écrivent pas `customers` et ne passent donc pas par `UpdateCustomer` (research G2).

- `CustomerChanged` signifie « quelque chose a changé pour ce client », pas « la ligne `customers` a été écrite » : il peut être émis sans aucune écriture de `customers` (désignation ou retrait d'un grand compte).
- Tout écouteur de `CustomerChanged` (dont celui de la 005 sur l'e-mail) DOIT vérifier lui-même ce qui a changé avant d'agir, et ne rien faire si le champ qui l'intéresse est inchangé.
- `UpdateCustomer` reste la seule écriture de la table `customers` ; `booking` n'a ni colonne ni garde de désignation (décision de la coordination, 2026-10-10).
