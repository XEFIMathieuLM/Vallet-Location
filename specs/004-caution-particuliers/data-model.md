# Data Model: Caution des particuliers

Montants en centimes (entiers), lus et écrits en `Functional\Billing\Money\Money` via `MoneyCast` (003). Dates en heure de Paris. Aucune clé étrangère en cascade. Statuts en texte castés en enum PHP.

## Layer `booking` (enrichi par la feature 004)

### customers (colonne ajoutée)

| Colonne | Type | Règle |
|---------|------|-------|
| type | string, nullable | `CustomerType` : `individual` (particulier) / `professional` (professionnel) ; `null` = « à renseigner » (clients antérieurs à la mise en service) ; CHECK `type IN ('individual','professional')` |

Migration `functional/booking/database/migrations/2026_10_10_000080_add_type_to_customers_table.php`. Aucune reprise de données : les clients existants restent `null` (FR-002).

- `Customer` : cast `'type' => CustomerType::class` ; `LogsActivity` + `RecordsAuthorAgency`, `logOnly(['type'])->logOnlyDirty()` (historique du client, FR-021).
- `NewCustomer` (value object de `CreateReservation`) : champ `CustomerType $type` obligatoire (FR-001).

### Contrats ajoutés à `booking`

- `Functional\Booking\Enums\CustomerType` : `Individual = 'individual'`, `Professional = 'professional'`, `label()` traduit.
- `Functional\Booking\Actions\UpdateCustomer::qualify(Customer $customer, CustomerType $type, Authenticatable&AgencyMember $author): Customer` — transaction, `lockForUpdate()` sur le client, sans effet si le type est inchangé, appelle `CustomerChangeGuards` puis écrit, dispatch `CustomerChanged($customer, ['type'])`.
- `Functional\Booking\Events\CustomerChanged implements ShouldDispatchAfterCommit` : `__construct(public readonly Customer $customer, public readonly list<string> $changedAttributes)`.
- `Functional\Booking\Contracts\CustomerChangeGuard` : `beforeTypeChange(Customer $customer, CustomerType $newType): void` (`@throws RefusalException`).
- `Functional\Booking\Extensions\CustomerChangeGuards` : `register(class-string<CustomerChangeGuard>)`, `all(): list<CustomerChangeGuard>` ; singleton, vide par défaut.

Détail : [contracts/customer-contract.md](contracts/customer-contract.md).

## Layer `deposit` (nouveau)

### deposit_rates

| Colonne | Type | Règle |
|---------|------|-------|
| id | bigint | |
| machine_category_id | FK `machine_categories`, nullable | `null` = montant par défaut |
| amount_cents | integer | CHECK `amount_cents > 0` |
| updated_by | FK `users`, nullable | dernier auteur |
| timestamps | | |

- Index unique partiel `(machine_category_id) WHERE machine_category_id IS NOT NULL` ; index unique partiel `((1)) WHERE machine_category_id IS NULL` : un seul montant par défaut.
- La migration insère la ligne par défaut à `config('deposit.initial_default_amount_cents')` (150000).
- Montant exigé d'une réservation = montant de la catégorie de sa machine, sinon montant par défaut (`ResolveDepositAmount`).

### deposits

| Colonne | Type | Règle |
|---------|------|-------|
| id | bigint | |
| reservation_id | FK `reservations` | **unique** (une caution par réservation, FR-008) |
| amount_cents | integer | CHECK `> 0` ; figé à l'encaissement (FR-007, FR-018) |
| payment_method | string | `PaymentMethod` : `cheque`, `card_imprint`, `cash` |
| payment_reference | string, nullable | CHECK : non nul sauf si `payment_method = 'cash'` |
| status | string, indexé | `DepositStatus` (voir états) |
| collected_by | FK `users` | |
| collected_at | timestamp | |
| awaiting_since | timestamp, nullable | début de l'attente d'action (à restituer, à solder, bloquée) ; FR-019, FR-020 |
| retained_cents | integer, nullable | renseigné à la fin |
| refunded_cents | integer, nullable | renseigné à la fin |
| is_no_damage_confirmed | boolean, default false | confirmation « aucun dégât constaté » (FR-012) |
| closed_by | FK `users`, nullable | auteur de la restitution ou du solde |
| closed_at | timestamp, nullable | |
| timestamps | | |

CHECK de cohérence :
- `status IN ('refunded','settled')` ⇔ `closed_at`, `closed_by`, `retained_cents`, `refunded_cents` non nuls ;
- `retained_cents + refunded_cents = amount_cents` quand ils sont renseignés ;
- `status = 'refunded'` ⇒ `retained_cents = 0` ;
- `retained_cents >= 0`, `refunded_cents >= 0`.

### États de `Deposit` (pattern State, `src/States/`)

| De | Vers autorisés |
|----|----------------|
| Collected | ToRefund, BlockedByDamage |
| ToRefund | BlockedByDamage, Refunded |
| BlockedByDamage | ToSettle, ToRefund |
| ToSettle | BlockedByDamage, Settled |
| Refunded | — (final) |
| Settled | — (final) |

Toute autre transition lève `IllegalDepositTransitionException`. `awaiting_since` est posé à l'entrée dans `ToRefund`, `BlockedByDamage` ou `ToSettle`.

### Situation dérivée (sans ligne `deposits`)

`DepositSituation::for(Reservation)` renvoie un `DepositSituationKind` affiché par la section :

| Situation | Condition |
|-----------|-----------|
| NotRequired | client professionnel, sans caution |
| NotTracked | réservation en cours ou clôturée sans caution (sortie avant la mise en service), ou annulée sans caution |
| CustomerTypeMissing | client sans type, réservation confirmée |
| ToCollect | client particulier, réservation confirmée, sans caution |
| Deposit(status) | une caution existe : son état |

L'étape « départ » est prête pour la section quand la situation est `NotRequired`, `NotTracked` ou une caution existe.

### Configuration `functional/deposit/config/deposit.php`

| Clé | Valeur | Usage |
|-----|--------|-------|
| initial_default_amount_cents | 150000 | montant par défaut inséré par la migration (à confirmer) |
| overdue_after_days | 7 | mise en évidence dans la liste (FR-020) |
| timezone | `Europe/Paris` | ancienneté |

### Historique (`activity_log`, log `deposit`, sur la réservation)

`DepositHistoryEvent` : `collected` (montant, moyen, référence), `payment_corrected` (motif, ancien moyen/référence, nouveau), `blocked_by_damage`, `released` (vers à restituer / à solder), `refunded` (montant, confirmation), `settled` (retenu, restitué). Auteur (`causedBy`) et `author_agency_id` en propriété.

## Lectures d'autres layers (sans écriture)

| Source | Lecture |
|--------|---------|
| `booking` | `Reservation` (statut, client, machine), `ReservationChanged` |
| `fleet` | `MachineCategory` (via la machine) |
| `inspection` | `CountUnresolvedDamages::for()`, `DamageChanged` (`reservationId`, `unresolvedCount`) |
| `billing` | `damage_settlements` (`outcome = billed`, `amount_cents`) agrégé en SQL ; `Money`, `MoneyCast` |
