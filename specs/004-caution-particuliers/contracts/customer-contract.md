# Contrat : écriture et type du client (`booking`)

Point d'entrée unique pour modifier un client existant, partagé par les features 004, 005 et 006. La feature implémentée la première crée `UpdateCustomer`, les suivantes y ajoutent leur méthode. Noms arrêtés avec la coordination des features le 2026-10-10.

## Enum

```php
namespace Functional\Booking\Enums;

enum CustomerType: string implements HasLabel
{
    case Individual = 'individual';
    case Professional = 'professional';
}
```

Colonne `customers.type` nullable : `null` = « à renseigner ». Le type « professionnel » ne porte aucune règle des grands comptes (spec FR-003).

## Création

`NewCustomer(string $name, ?string $phone, ?string $email, CustomerType $type)` : le type est obligatoire à la création d'un client depuis `CreateReservationForm` (FR-001).

## Action

```php
namespace Functional\Booking\Actions;

final class UpdateCustomer
{
    public function qualify(Customer $customer, CustomerType $type, Authenticatable&AgencyMember $author): Customer;
    // 005 : changeEmail(...)
}
```

`qualify()` :
1. ouvre une transaction et verrouille le client (`lockForUpdate()`) ;
2. ne fait rien si le type est déjà `$type` (aucun événement) ;
3. appelle `beforeTypeChange($lockedCustomer, $type)` de chaque garde de `CustomerChangeGuards` ; un refus (`RefusalException`) annule tout ;
4. écrit le type ; l'historique du client (`LogsActivity`, `RecordsAuthorAgency`) trace l'ancien et le nouveau type et l'auteur ;
5. après le commit, `CustomerChanged($customer, ['type'])`.

## Événement

```php
namespace Functional\Booking\Events;

final class CustomerChanged implements ShouldDispatchAfterCommit
{
    /** @param list<string> $changedAttributes */
    public function __construct(public readonly Customer $customer, public readonly array $changedAttributes) {}
}
```

Chaque écouteur vérifie l'attribut qui l'intéresse : `in_array('email', $event->changedAttributes, true)`.

## Garde de changement de type

```php
namespace Functional\Booking\Contracts;

interface CustomerChangeGuard
{
    /** @throws RefusalException */
    public function beforeTypeChange(Customer $customer, CustomerType $newType): void;
}
```

```php
namespace Functional\Booking\Extensions;

final class CustomerChangeGuards
{
    /** @param class-string<CustomerChangeGuard> $guardClass */
    public function register(string $guardClass): void;

    /** @return list<CustomerChangeGuard> */
    public function all(): array;
}
```

Singleton lié dans `BookingServiceProvider`, vide par défaut. L'ancien type se lit sur `$customer->type` (éventuellement `null`). Exemple 006 : `app(CustomerChangeGuards::class)->register(KeyAccountTypeChangeGuard::class)` dans son provider.
