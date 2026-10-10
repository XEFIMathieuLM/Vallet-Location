---

description: "Task list for feature 004-caution-particuliers"
---

# Tasks: Caution des particuliers

**Input**: Design documents from `specs/004-caution-particuliers/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/customer-contract.md), [quickstart.md](quickstart.md)

**Tests**: Inclus, comme pour les 001 à 003 : un test Feature PHPUnit par scénario d'acceptation, des tests Unit purs pour les calculs en mémoire (constitution 1.0.1). Dans chaque story, écrire les tests d'abord et vérifier qu'ils échouent.

**Organization**: Tâches groupées par user story ; chaque story est testable indépendamment une fois la phase 2 terminée.

## ⚠️ Prérequis : features 001, 002 et 003

Cette feature s'appuie sur :

- la 001 **avec ses commits `e8cba36`, `7423fc9` et `7122415`** : `RefusalException` abstraite, `ReservationTransition`, `ReservationDetailSections::register(string $livewireComponent, int $position, ReservationTransition ...$guardedTransitions)` et l'événement `reservation-transition-readiness` (`step`, `section`, `is_ready`), `ReservationTransitionGuards`, `CreateReservation::handle(Authenticatable&AgencyMember, Machine, Customer|NewCustomer, CarbonImmutable, CarbonImmutable)`, `NewCustomer`, `AgencyMember`, `RecordsAuthorAgency`, `CreatesUsers`, `AssertsRefusals`, `DisplaysRefusals`, `PermissionSeeder` (rôle `salarie` ← `Permission::all()`), composants `x-empty-state`, `x-loading-hint`, `x-section-heading` ;
- la 002 : `Damage`, `CountUnresolvedDamages::for(Reservation): int`, `DamageChanged(int $reservationId, int $unresolvedCount)` ;
- la 003 : `damage_settlements` (`outcome`, `amount_cents`), `DamageOutcome::Billed`, `Money`, `Currency`, `MoneyCast`, `SettleDamage` / `BillDamage` / `WaiveDamage` (pour les tests).

Avant T001 :

1. la coordination donne son feu vert ;
2. la branche `004-caution-particuliers` est mise à jour par-dessus la 003 corrigée, qui contient les commits 001 ci-dessus (vérifier `git merge-base --is-ancestor 7423fc9 HEAD` et `git merge-base --is-ancestor 7122415 HEAD`) ;
3. la base est recréée (`php artisan migrate:fresh --seed`, la migration `transmissions` de la 003 ayant été modifiée sur place) ;
4. `docker compose exec -u sail laravel.test php artisan test` passe sur la branche mise à jour.

Si une signature listée ci-dessus diffère sur la branche mise à jour, s'arrêter et corriger [plan.md](plan.md) avant de coder.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US6)

## Conventions pour toutes les tâches

Celles de la 001 s'appliquent sans changement (CLAUDE.md, constitution 1.0.1) : commandes via `docker compose exec -u sail laravel.test …`, code en anglais, textes dans `functional/deposit/resources/lang/fr/*.php` (et `functional/booking/resources/lang/fr/*.php` pour le client), statuts en `string` castés en enum, pas de cascade, pas d'observer, pas de `try/catch` (`rescue()` + relance), pas de commentaire, factories avec `faker()`, contrôles par permission, fichiers < 200 lignes, méthodes < 40 lignes, agrégats en base. En plus :

- `deposit` dépend de `billing`, `inspection`, `booking` et `fleet` ; **aucun fichier de ces layers n'importe une classe de `deposit`**, et aucun de leurs modèles ne déclare de relation vers `deposits` ou `deposit_rates`.
- Les seules modifications hors de `functional/deposit/` sont celles de `booking` listées en phase 2 et en US2 (client), et l'enregistrement du layer et le menu (T001, T004, T065), et `CLAUDE.md` (T069).
- Montants en `Money` (centimes), jamais en flottant ; colonnes `*_cents` en entier.
- Refus : sous-classes `final` de `Functional\Fleet\Exceptions\RefusalException` à factories nommées ; testés avec `assertRefused()`.
- Acteurs : `Authenticatable&AgencyMember`, jamais `App\Models\User` dans un layer ; utilisateurs de test via `CreatesUsers`.
- Les tests manipulent l'horloge avec `$this->travelTo()`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Layer `deposit`, configuration, permissions.

- [ ] T001 Créer le layer OSDD `functional/deposit/` avec `osdd:*` (`composer.json` `"type": "layer"` déclarant `functional/billing`, `functional/inspection`, `functional/booking`, `functional/fleet` et les mêmes packages que `functional/billing/composer.json`, PSR-4 `Functional\\Deposit\\` → `src/` + namespaces des seeders et factories, `extra.laravel.providers` = `Functional\\Deposit\\Providers\\DepositServiceProvider`) ; dossiers `src/`, `config/`, `database/{migrations,factories,seeders}/`, `resources/{views,lang/fr}/`, `routes/`, `tests/{Feature,Unit}/` ; déclarer `"functional/deposit": "*"` dans le `require` du `composer.json` racine et `Functional\\Deposit\\Tests\\` dans son `autoload-dev`, mettre à jour `composer.lock` ; ajouter `functional/deposit/{src,database,routes,config}` à `phpstan.neon` et `functional/deposit/src` à la `<source>` de `phpunit.xml`
- [ ] T002 [P] Créer `functional/deposit/config/deposit.php` : `initial_default_amount_cents` = 150000, `overdue_after_days` = 7, `timezone` = `Europe/Paris` ; le charger par `mergeConfigFrom` dans `functional/deposit/src/Providers/DepositServiceProvider.php` (qui étend `Xefi\LaravelOSDD\LayerServiceProvider` ; migrations, traductions `deposit`, vues `deposit`, routes `web` et `commands` par `withRouting`)
- [ ] T003 [P] Créer l'enum `DepositPermission` dans `functional/deposit/src/Access/DepositPermission.php` : `ManageDeposits = 'deposits.manage'`, `ManageDepositRates = 'deposit_rates.manage'`
- [ ] T004 Créer `functional/deposit/database/seeders/DepositPermissionSeeder.php` (même forme que `BookingPermissionSeeder` : `forgetCachedPermissions()` puis `Permission::findOrCreate()` pour chaque cas de `DepositPermission`, sans toucher aux rôles) ; l'appeler dans `database/seeders/DatabaseSeeder.php` et dans `Tests\TestCase::seedPermissions()` (`tests/TestCase.php`) **avant** `PermissionSeeder` (depends on T003)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Type de client et contrat d'écriture dans `booking` ; tables, modèles, états, montant et historique de la caution. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

### `booking` : type de client et contrat partagé ([contracts/customer-contract.md](contracts/customer-contract.md))

- [ ] T005 [P] Créer l'enum `CustomerType` dans `functional/booking/src/Enums/CustomerType.php` : `Individual = 'individual'`, `Professional = 'professional'`, implémente `Functional\Fleet\Contracts\HasLabel` (`label()` → `booking::customers.types.{value}`) ; libellés « Particulier », « Professionnel » et « À renseigner » dans `functional/booking/resources/lang/fr/customers.php`
- [ ] T006 Créer la migration `functional/booking/database/migrations/2026_10_10_000080_add_type_to_customers_table.php` : colonne `type` « string, nullable » ; CHECK `type IN ('individual','professional')` ; aucune reprise de données (les clients existants restent `null`)
- [ ] T007 Mettre à jour `functional/booking/src/Models/Customer.php` : `type` dans `Fillable`, cast `'type' => CustomerType::class`, `LogsActivity` + `RecordsAuthorAgency` avec `getActivitylogOptions()` = `LogOptions::defaults()->useLogName('booking')->logOnly(['type'])->logOnlyDirty()->dontSubmitEmptyLogs()` ; dans `functional/booking/database/factories/CustomerFactory.php`, type `individual` par défaut et états `individual()`, `professional()`, `untyped()` (type `null`) (depends on T005, T006)
- [ ] T008 [P] Créer le contrat `functional/booking/src/Contracts/CustomerChangeGuard.php` (`/** @throws RefusalException */ public function beforeTypeChange(Customer $customer, CustomerType $newType): void;`) et le registre `functional/booking/src/Extensions/CustomerChangeGuards.php` (`final`, calqué sur `ReservationTransitionGuards` : `register(class-string<CustomerChangeGuard> $guardClass): void`, `all(): list<CustomerChangeGuard>` construits par `app()`) ; le lier en singleton dans `functional/booking/src/Providers/BookingServiceProvider.php`, vide par défaut ; ajouter sa réinitialisation à `functional/booking/tests/Concerns/WithoutTransitionExtensions.php`
- [ ] T009 [P] Créer l'événement `functional/booking/src/Events/CustomerChanged.php` : `final`, `implements ShouldDispatchAfterCommit`, `use Dispatchable, SerializesModels`, `__construct(public readonly Customer $customer, public readonly array $changedAttributes)` avec `@param list<string> $changedAttributes`
- [ ] T010 Écrire les tests `functional/booking/tests/Feature/UpdateCustomerQualifyTest.php` : qualification d'un client sans type → type écrit, `CustomerChanged` émis avec `['type']` après commit (`Event::fake([CustomerChanged::class])`), activité `booking` avec ancien `null` et nouveau `individual` et `author_agency_id` ; type inchangé → aucun événement, aucune activité ; garde enregistré qui refuse (double `functional/booking/tests/Doubles/RefusingCustomerChangeGuard.php`) → `assertRefused`, type inchangé en base, aucun événement ; le garde reçoit le client verrouillé avec l'ancien type et le nouveau type ; aucun garde enregistré par défaut (`app(CustomerChangeGuards::class)->all() === []`) (depends on T007, T008, T009)
- [ ] T011 Créer `functional/booking/src/Actions/UpdateCustomer.php` (`final`) avec `qualify(Customer $customer, CustomerType $type, Authenticatable&AgencyMember $author): Customer` : `DB::transaction`, `Customer::query()->lockForUpdate()->findOrFail()`, retour sans effet si le type est déjà `$type`, appel de `beforeTypeChange()` de chaque garde de `CustomerChangeGuards`, écriture du type, puis `CustomerChanged::dispatch($customer, ['type'])` ; faire passer T010 (depends on T010)

### `deposit` : tables et modèles

- [ ] T012 [P] Créer les enums dans `functional/deposit/src/Enums/` : `DepositStatus` (`collected`, `to_refund`, `blocked_by_damage`, `to_settle`, `refunded`, `settled`, avec `isFinal()` vrai pour `refunded` et `settled`), `PaymentMethod` (`cheque`, `card_imprint`, `cash`, avec `requiresReference()` faux pour `cash`), `DepositSituationKind` (`not_required`, `not_tracked`, `customer_type_missing`, `to_collect`, `tracked`), `DepositHistoryEvent` (`collected`, `payment_corrected`, `blocked_by_damage`, `released`, `refunded`, `settled`, avec `description(array $details)` traduite) ; tous `HasLabel`, libellés dans `functional/deposit/resources/lang/fr/enums.php` et `history.php`
- [ ] T013 [P] Créer la migration `functional/deposit/database/migrations/2026_10_10_000090_create_deposit_rates_table.php` : `machine_category_id` « FK `machine_categories`, nullable » (`->constrained()` sans cascade), `amount_cents` « integer, CHECK `amount_cents > 0` », `updated_by` « FK `users`, nullable », timestamps ; index unique partiel `(machine_category_id) WHERE machine_category_id IS NOT NULL` et index unique partiel `((1)) WHERE machine_category_id IS NULL` ; insérer la ligne par défaut (`machine_category_id` null) à `config('deposit.initial_default_amount_cents')`
- [ ] T014 [P] Créer la migration `functional/deposit/database/migrations/2026_10_10_000091_create_deposits_table.php` selon [data-model.md](data-model.md#deposits) : `reservation_id` « unique » (`->constrained()` sans cascade), `amount_cents` CHECK `> 0`, `payment_method` string, `payment_reference` string nullable, `status` string indexé défaut `collected`, `collected_by` → users, `collected_at`, `awaiting_since` nullable (indexé), `retained_cents` et `refunded_cents` nullable, `is_no_damage_confirmed` booléen défaut false, `closed_by` → users nullable, `closed_at` nullable, timestamps ; CHECK : `payment_reference` non nul sauf si `payment_method = 'cash'` ; `status IN ('refunded','settled')` ⇔ `closed_at`, `closed_by`, `retained_cents`, `refunded_cents` non nuls ; `retained_cents + refunded_cents = amount_cents` quand renseignés ; `status = 'refunded'` ⇒ `retained_cents = 0` ; `retained_cents >= 0` et `refunded_cents >= 0`
- [ ] T015 [P] Créer le modèle `functional/deposit/src/Models/DepositRate.php` (`HasControl`, `HasFactory` ; cast `amount` → `MoneyCast::class.':amount_cents'` ; relation `category()` → `MachineCategory`) et `functional/deposit/database/factories/DepositRateFactory.php` (états `forCategory()`, `default()`) (depends on T013)
- [ ] T016 Créer le modèle `functional/deposit/src/Models/Deposit.php` (`HasControl`, `HasFactory` ; casts `status` → `DepositStatus`, `payment_method` → `PaymentMethod`, `amount` → `MoneyCast:amount_cents`, `retained` → `MoneyCast:retained_cents`, `refunded` → `MoneyCast:refunded_cents`, dates en `immutable_datetime` ; relations `reservation()`, `collector()`, `closer()` ; `state(): DepositState`) et `functional/deposit/database/factories/DepositFactory.php` (un état par statut, cohérent avec les CHECK) (depends on T012, T014)

### `deposit` : cycle de vie, montant, situation, historique

- [ ] T017 [P] Écrire les tests Unit purs `functional/deposit/tests/Unit/DepositStatesTest.php` : pour chaque état, les transitions autorisées du tableau de [data-model.md](data-model.md#états-de-deposit-pattern-state-srcstates) réussissent et toutes les autres lèvent `IllegalDepositTransitionException` ; `Refunded` et `Settled` n'acceptent aucune transition
- [ ] T018 Créer `functional/deposit/src/States/DepositState.php` (abstraite : `toRefund()`, `blockByDamage()`, `toSettle()`, `refund()`, `settle()` qui lèvent `IllegalDepositTransitionException` par défaut, et `status(): DepositStatus`) et une classe `final` par état (`Collected`, `ToRefund`, `BlockedByDamage`, `ToSettle`, `Refunded`, `Settled`) qui redéfinit seulement ses transitions autorisées ; `functional/deposit/src/Exceptions/IllegalDepositTransitionException.php` ; faire passer T017 (depends on T012, T017)
- [ ] T019 [P] Créer `functional/deposit/src/Exceptions/DepositRefusedException.php` (`final`, étend `RefusalException`) avec les factories nommées utilisées par les stories : `customerTypeMissing()`, `notCollected(Money $expected)`, `reservationNotConfirmed()`, `customerNotIndividual()`, `alreadyCollected()`, `referenceRequired(PaymentMethod $method)`, `reasonRequired()`, `noDamageNotConfirmed()`, `damagesToSettle(int $unresolvedCount)` (pluriel), `notRefundable(DepositStatus $status)`, `notSettleable(DepositStatus $status)`, `alreadyClosed()`, `invalidAmount()` ; messages techniques en anglais ASCII, messages utilisateur dans `functional/deposit/resources/lang/fr/refusals.php`
- [ ] T020 Écrire les tests `functional/deposit/tests/Feature/ResolveDepositAmountTest.php` : catégorie sans montant propre → montant par défaut ; catégorie avec montant propre → ce montant ; la migration a créé un seul montant par défaut à 1 500,00 € ; une deuxième ligne par défaut ou un deuxième montant pour la même catégorie est refusé par la base (US6 scénarios 1 et 2) (depends on T015)
- [ ] T021 Créer `functional/deposit/src/Actions/ResolveDepositAmount.php` : `forCategory(MachineCategory $category): Money` et `forReservation(Reservation $reservation): Money` (catégorie de la machine de la réservation, sinon montant par défaut) en une requête ; faire passer T020 (depends on T020)
- [ ] T022 Écrire les tests `functional/deposit/tests/Feature/DepositSituationTest.php` : client professionnel sans caution → `NotRequired` ; client sans type et réservation confirmée → `CustomerTypeMissing` ; particulier, confirmée, sans caution → `ToCollect` avec le montant attendu ; réservation en cours ou clôturée sans caution → `NotTracked` ; annulée sans caution → `NotTracked` ; caution existante → `Tracked` avec son état ; `isDepartureReady()` vrai pour `NotRequired`, `NotTracked` et `Tracked`, faux sinon (depends on T016, T021)
- [ ] T023 Créer `functional/deposit/src/Queries/DepositSituation.php` (`for(Reservation): DepositSituationResult`) et le value object `functional/deposit/src/ValueObjects/DepositSituationResult.php` (`kind`, `?Deposit $deposit`, `?Money $expectedAmount`, `isDepartureReady(): bool`) ; faire passer T022 (depends on T022)
- [ ] T024 [P] Créer `functional/deposit/src/History/DepositHistory.php` : `record(Reservation $reservation, DepositHistoryEvent $event, Authenticatable&AgencyMember $author, array $details = []): void` → `activity('deposit')->performedOn($reservation)->causedBy($author)->event($event->value)->withProperties([...$details, 'author_agency_id' => $author->agencyId()])->log($event->description($details))`, et `recordSystem(Reservation, DepositHistoryEvent, array $details = [])` sans auteur pour les transitions automatiques (depends on T012)
- [ ] T025 [P] Créer l'événement `functional/deposit/src/Events/DepositChanged.php` (`final`, `ShouldBroadcast`, `ShouldDispatchAfterCommit`, `__construct(public readonly int $reservationId)`, `PrivateChannel('fleet')`, `broadcastAs()` = `deposit.changed`, charge utile `{reservation_id}`) ; émis par chaque action qui modifie une caution (encaissement, correction, synchronisation, restitution, solde)
- [ ] T026 [P] Créer les contrôles `functional/deposit/src/Access/Controls/DepositControl.php` (`deposits.manage`) et `DepositRateControl.php` (`deposit_rates.manage`) sur le modèle de `ReservationControl` (`GlobalPerimeter`) ; les enregistrer dans `DepositServiceProvider` (depends on T003, T015, T016)

**Checkpoint** : le type de client et le contrat `UpdateCustomer` existent ; les tables, modèles, états, montant, situation et historique de la caution sont testés.

---

## Phase 3: User Story 1 - Bloquer la sortie tant que la caution d'un particulier n'est pas encaissée (Priority: P1) 🎯 MVP

**Goal**: La sortie d'un particulier est refusée sans caution encaissée ; la section « Caution » permet l'encaissement et signale la disponibilité de la sortie.

**Independent Test**: Réservation confirmée d'un particulier : sortie refusée « caution non encaissée » ; encaissement ; sortie acceptée.

### Tests for User Story 1 ⚠️

- [ ] T027 [P] [US1] Test `functional/deposit/tests/Feature/DepartureRequiresDepositTest.php` : scénario 2 (sortie refusée, `assertRefused(DepositRefusedException::class, '1 500,00')` avec le montant attendu) ; scénario 4 (après encaissement, sortie acceptée, photos de départ simulées comme dans les tests de la 003) ; scénario 5 (client professionnel : sortie acceptée sans caution) ; client sans type : refus « type de client à renseigner » (FR-005) ; réservation déjà en cours à la mise en service : aucune caution exigée au retour
- [ ] T028 [P] [US1] Test `functional/deposit/tests/Feature/CollectDepositTest.php` : scénario 3 (caution encaissée : montant de la catégorie, moyen, référence, auteur, agence, date ; activité `deposit` `collected`) ; montant figé (modifier ensuite le montant de la catégorie ne change pas la caution) ; refus : réservation en cours, clôturée ou annulée (`reservationNotConfirmed`), client professionnel ou sans type (`customerNotIndividual`), caution déjà encaissée (`alreadyCollected`), référence manquante pour un chèque ou une empreinte (`referenceRequired`), espèces acceptées sans référence ; scénario 6 (deux encaissements concurrents : le second est refusé, une seule ligne ; simuler la course par deux appels successifs et vérifier l'index unique en insérant une seconde ligne directement)
- [ ] T029 [P] [US1] Test Livewire `functional/deposit/tests/Feature/ReservationDepositSectionTest.php` : scénario 1 (« à encaisser » et montant attendu) ; scénario 5 (« non requise (client professionnel) ») ; la section émet `reservation-transition-readiness` avec `step: 'departure'`, `section: 'deposit.reservation-section'`, `is_ready: false` au mount sans caution, puis `true` après l'encaissement (`assertDispatched`) ; la section est enregistrée gardienne de `ReservationTransition::Departure` (`app(ReservationDetailSections::class)->isGuardedBy('deposit.reservation-section', ReservationTransition::Departure)`) ; refus affiché par `@error('refusal')` ; utilisateur sans `deposits.manage` : situation visible, aucune action, disponibilité toujours émise

### Implementation for User Story 1

- [ ] T030 [US1] Créer `functional/deposit/src/Actions/CollectDeposit.php` : `handle(Reservation $reservation, PaymentMethod $method, ?string $reference, Authenticatable&AgencyMember $author): Deposit` — `DB::transaction`, `Reservation::query()->lockForUpdate()->findOrFail()`, contrôles et refus de T028 dans cet ordre (réservation confirmée, client particulier, aucune caution, référence), montant = `ResolveDepositAmount::forReservation()`, création `status = collected`, `DepositHistory::record(... Collected ...)` ; après la transaction, `DepositChanged::dispatch($deposit->reservation_id)` pour rafraîchir les autres postes (depends on T021, T024, T019, T025)
- [ ] T031 [US1] Créer le garde `functional/deposit/src/Guards/DepositCollectedGuard.php` (`implements ReservationTransitionGuard`) : `beforeDeparture()` lève `customerTypeMissing()` si `$reservation->customer->type === null`, `notCollected($expectedAmount)` si particulier sans ligne `deposits` (lecture `Deposit::query()->whereBelongsTo($reservation)->exists()` dans la transaction de `DepartReservation`) ; `beforeReturn()` vide ; l'enregistrer dans `ReservationTransitionGuards` depuis `DepositServiceProvider::boot()` (depends on T023)
- [ ] T032 [US1] Créer le composant `functional/deposit/src/Livewire/ReservationDepositSection.php` (`DisplaysRefusals`, `#[Locked] public Reservation $reservation`) : propriété calculée `situation` (`DepositSituation`), action `collect()` (validation : `method` dans `PaymentMethod`, `reference` requise selon `requiresReference()`, `Gate::authorize(DepositPermission::ManageDeposits->value)`, `CollectDeposit`, `Flux::modal('collect-deposit')->close()`, `Flux::toast`) ; `mount()` et chaque action réussie appellent `reportReadiness()` = `$this->dispatch('reservation-transition-readiness', step: ReservationTransition::Departure->value, section: self::NAME, is_ready: $this->situation->isDepartureReady())` avec `public const NAME = 'deposit.reservation-section'` ; écoute `echo-private:fleet,.reservation.changed` et `echo-private:fleet,.deposit.changed` (filtrés sur la réservation) pour se rafraîchir et réémettre la disponibilité (depends on T030)
- [ ] T033 [US1] Créer la vue `functional/deposit/resources/views/livewire/reservation-deposit-section.blade.php` : `<section class="space-y-4">`, `<x-section-heading>` « Caution », `@error('refusal')` en `flux:callout` danger, badge d'état (`flux:badge`), montant formaté (`Money::format()` + « € »), bouton « Encaisser la caution » ouvrant `flux:modal name="collect-deposit"` (moyen en `flux:radio.group`, référence en `flux:input`, `x-loading-hint`), détails de l'encaissement une fois fait ; textes dans `functional/deposit/resources/lang/fr/section.php` (depends on T032)
- [ ] T034 [US1] Dans `DepositServiceProvider::boot()` : `Livewire::component(ReservationDepositSection::NAME, ReservationDepositSection::class)` et `app(ReservationDetailSections::class)->register(ReservationDepositSection::NAME, 30, ReservationTransition::Departure)` ; faire passer T027, T028, T029 (depends on T031, T033)

**Checkpoint** : US1 livrable seule (MVP) : aucune sortie de particulier sans caution.

---

## Phase 4: User Story 2 - Qualifier chaque client comme particulier ou professionnel (Priority: P1)

**Goal**: Type obligatoire à la création d'un client ; qualification des clients existants depuis la section « Caution ».

**Independent Test**: Créer un client sans type : refus. Client existant sans type : sortie refusée, qualification depuis la section, sortie possible selon le type.

### Tests for User Story 2 ⚠️

- [ ] T035 [P] [US2] Test `functional/booking/tests/Feature/CreateReservationCustomerTypeTest.php` : scénario 1 (formulaire `CreateReservationForm` avec nouveau client sans type → erreur de validation sur `newCustomerType`, aucune réservation) ; nouveau client « particulier » → `customers.type = individual` ; client existant choisi sans type → réservation créée, type inchangé (`null`) ; `CreateReservation::handle()` avec `NewCustomer` porte le type
- [ ] T036 [P] [US2] Test `functional/deposit/tests/Feature/QualifyCustomerFromSectionTest.php` : scénario 2 (section « type de client à renseigner » avec choix particulier / professionnel) ; scénario 3 (sortie refusée tant que non qualifié, couvert aussi par T027) ; scénario 4 (qualifié « particulier » → section « à encaisser », sortie refusée sans caution) ; qualifié « professionnel » → « non requise », `is_ready: true` émis ; scénario 5 (particulier avec caution encaissée requalifié « professionnel » → caution toujours suivie « encaissée ») ; requalification professionnel → particulier d'un client à réservation confirmée → caution exigée ; refus d'un garde `CustomerChangeGuard` affiché dans la section

### Implementation for User Story 2

- [ ] T037 [US2] Ajouter `public CustomerType $type` à `functional/booking/src/Data/NewCustomer.php` et le persister dans `CreateReservation::persistedCustomer()` (`functional/booking/src/Actions/CreateReservation.php`) ; mettre à jour les appels existants de `NewCustomer` dans les tests de la 001 (`functional/booking/tests/`) et la factory si besoin
- [ ] T038 [US2] Dans `functional/booking/src/Livewire/CreateReservationForm.php` et sa vue : champ `newCustomerType` (`flux:radio.group` « Particulier » / « Professionnel », sans valeur par défaut), règle `required` + `Rule::enum(CustomerType::class)` quand `isNewCustomer`, libellé d'attribut traduit ; afficher le type (ou « À renseigner ») dans la liste des clients existants ; faire passer T035 (depends on T037)
- [ ] T039 [US2] Ajouter à `ReservationDepositSection` l'action `qualify(string $type)` : `Gate::authorize(DepositPermission::ManageDeposits->value)`, `app(UpdateCustomer::class)->qualify($this->reservation->customer, CustomerType::from($type), auth()->user())`, toast, `reportReadiness()` ; dans la vue, bloc « Type de client à renseigner » avec deux boutons et, pour un client déjà qualifié, le type affiché avec une action « Modifier le type » dans un `flux:modal` ; faire passer T036 (depends on T011, T034)

**Checkpoint** : US1 + US2 couvrent la règle de la fiche pour tous les clients, existants ou nouveaux.

---

## Phase 5: User Story 3 - Restituer ou retenir la caution au retour (Priority: P1)

**Goal**: Après le retour, la caution passe à restituer ou bloquée ; restitution avec confirmation « aucun dégât » ; solde par retenue calculée.

**Independent Test**: Clôture sans dégât → à restituer → restituée avec confirmation. Dégât refacturé 450 € sur une caution de 1 500 € → à solder, 450 / 1 050 → soldée.

### Tests for User Story 3 ⚠️

- [ ] T040 [P] [US3] Tests Unit purs `functional/deposit/tests/Unit/DepositRetentionTest.php` : 1 500 € et 450 € refacturés → retenue 450 €, restitution 1 050 € ; 1 500 € et 2 000 € → 1 500 € et 0 € ; 1 500 € et 0 € → 0 € et 1 500 € ; retenue + restitution = caution dans tous les cas
- [ ] T041 [P] [US3] Test `functional/deposit/tests/Feature/BilledDamagesTotalTest.php` : somme en base des `damage_settlements` `billed` des dégâts de la réservation (deux dégâts refacturés 450 € et 300 €, un non refacturé → 750 €) ; dégâts d'une autre réservation ignorés ; aucun règlement → 0
- [ ] T042 [P] [US3] Test `functional/deposit/tests/Feature/SyncDepositStatusTest.php` : scénario 1 (clôture sans dégât à traiter → `to_refund`, `awaiting_since` posé) ; clôture avec dégât à traiter → `blocked_by_damage` ; dégât signalé après clôture sur une caution `to_refund` → `blocked_by_damage` ; tous les dégâts réglés avec au moins un refacturé → `to_settle` ; scénario 7 (seul dégât non refacturé → `to_refund`) ; nouveau dégât sur `to_settle` → `blocked_by_damage` ; idempotence (deux appels → un seul changement, une seule activité) ; caution finale jamais modifiée ; déclenché par `ReservationChanged` (retour réel via `ReturnReservation`) et par `DamageChanged` (signalement réel via `ReportDamage`, règlement via `BillDamage` / `WaiveDamage`)
- [ ] T043 [P] [US3] Test `functional/deposit/tests/Feature/RefundDepositTest.php` : scénario 2 (restitution avec confirmation → `refunded`, `refunded_cents = amount_cents`, `retained_cents = 0`, `is_no_damage_confirmed = true`, auteur, agence, date ; activité `refunded` avec la confirmation) ; scénario 8 (sans confirmation → `noDamageNotConfirmed`) ; scénario 3 (dégât à traiter → `damagesToSettle`) ; réservation non clôturée et non annulée → `notRefundable` ; caution déjà restituée → `alreadyClosed` ; état en retard (listener manqué) : `RefundDeposit` resynchronise d'abord et refuse si un dégât est à traiter
- [ ] T044 [P] [US3] Test `functional/deposit/tests/Feature/SettleDepositTest.php` : scénarios 4 et 5 (à solder → soldée avec 450 € retenus et 1 050 € restitués, recalculés à la validation ; aucun montant saisi) ; scénario 6 (2 000 € refacturés → 1 500 € retenus, 0 € restitué) ; solde d'une caution non `to_settle` → `notSettleable`
- [ ] T045 [P] [US3] Test `functional/deposit/tests/Feature/CorrectDepositPaymentTest.php` (FR-010) : correction du moyen et de la référence avant et après la sortie, motif obligatoire (`reasonRequired`), activité `payment_corrected` avec motif, ancien et nouveau moyen et référence ; montant inchangé ; correction refusée sur une caution restituée ou soldée (`alreadyClosed`)
- [ ] T046 [P] [US3] Test `functional/deposit/tests/Feature/ReconcileDepositsTest.php` : une caution `collected` d'une réservation clôturée sans dégât (listener court-circuité) passe `to_refund` après `artisan deposit:reconcile` ; les cautions finales ne sont pas lues ; la commande est planifiée toutes les 5 minutes sans chevauchement (`Schedule` inspecté)
- [ ] T047 [P] [US3] Test Livewire `functional/deposit/tests/Feature/ReservationDepositSectionRefundTest.php` : affichage « à restituer » avec case de confirmation ; « bloquée par un dégât » avec la liste des dégâts à traiter ; « à solder » avec retenue et restitution non modifiables ; restitution et solde depuis la section ; correction dans un `flux:modal` avec motif

### Implementation for User Story 3

- [ ] T048 [P] [US3] Créer `functional/deposit/src/ValueObjects/DepositRetention.php` (`final readonly`) : `static fromBilledTotal(Money $deposit, Money $billedTotal): self`, propriétés `Money $retained`, `Money $refunded` ; faire passer T040 (depends on T040)
- [ ] T049 [P] [US3] Créer `functional/deposit/src/Queries/BilledDamagesTotal.php` : `for(Reservation $reservation): Money` par une seule requête `SUM(damage_settlements.amount_cents)` jointe à `damages` sur `reservation_id`, `outcome = DamageOutcome::Billed->value` ; faire passer T041 (depends on T041)
- [ ] T050 [US3] Créer `functional/deposit/src/Actions/SyncDepositStatus.php` : `for(Reservation $reservation): void` — sans caution ou caution finale : rien ; sinon transaction, `Deposit::lockForUpdate()`, calcul de l'état dû (réservation `closed` ou `cancelled`, `CountUnresolvedDamages::for()`, existence d'un règlement `billed`), transition par `state()` seulement si l'état change, `awaiting_since = now()` à l'entrée dans un état d'attente, `DepositHistory::recordSystem()` (`blocked_by_damage` ou `released`, activité sans `causedBy`), `DepositChanged` (depends on T018, T024, T049)
- [ ] T051 [US3] Créer les listeners `functional/deposit/src/Listeners/SyncDepositOnReservationChanged.php` (statut `closed` ou `cancelled` uniquement) et `SyncDepositOnDamageChanged.php` (`Reservation::find($event->reservationId)`) appelant `SyncDepositStatus` ; les enregistrer par `Event::listen` dans `DepositServiceProvider` ; faire passer T042 (depends on T050)
- [ ] T052 [US3] Créer la commande `functional/deposit/src/Console/ReconcileDeposits.php` (`deposit:reconcile`) : parcourt par lots (`chunkById`) les cautions non finales dont la réservation est `closed` ou `cancelled`, appelle `SyncDepositStatus` ; la planifier dans `functional/deposit/routes/console.php` toutes les 5 minutes `->withoutOverlapping()` ; faire passer T046 (depends on T050)
- [ ] T053 [US3] Créer `functional/deposit/src/Actions/RefundDeposit.php` : `handle(Deposit $deposit, bool $isNoDamageConfirmed, Authenticatable&AgencyMember $author): Deposit` — `SyncDepositStatus` d'abord, puis transaction avec verrou : état `to_refund` exigé (sinon `damagesToSettle` si `blocked_by_damage`, `alreadyClosed` si final, `notRefundable` sinon), confirmation exigée si la réservation est `closed`, `state()->refund()`, `refunded_cents = amount_cents`, `retained_cents = 0`, `closed_by`, `closed_at`, `is_no_damage_confirmed`, `DepositHistory` `refunded` ; faire passer T043 (depends on T050)
- [ ] T054 [US3] Créer `functional/deposit/src/Actions/SettleDeposit.php` : `handle(Deposit $deposit, Authenticatable&AgencyMember $author): Deposit` — `SyncDepositStatus`, transaction avec verrou, état `to_settle` exigé, `DepositRetention::fromBilledTotal($deposit->amount, BilledDamagesTotal::for())`, `state()->settle()`, montants, `closed_by`, `closed_at`, `DepositHistory` `settled` (retenu, restitué) ; faire passer T044 (depends on T048, T050)
- [ ] T055 [US3] Créer `functional/deposit/src/Actions/CorrectDepositPayment.php` : `handle(Deposit $deposit, PaymentMethod $method, ?string $reference, string $reason, Authenticatable&AgencyMember $author): Deposit` — transaction avec verrou, refus si final, motif non vide après `trim`, référence selon le moyen, `DepositHistory` `payment_corrected` (motif, ancien et nouveau moyen et référence) ; faire passer T045 (depends on T024)
- [ ] T056 [US3] Ajouter à `ReservationDepositSection` et sa vue les actions `refund()` (case `isNoDamageConfirmed`, affichée seulement si la réservation est clôturée), `settle()` (retenue et restitution affichées en lecture seule), `correctPayment()` (`flux:modal name="correct-deposit"` avec moyen, référence, motif) et l'affichage des états à restituer, bloquée (liste des dégâts à traiter lue dans `inspection`), à solder, restituée, soldée ; extraire les blocs de vue en partiels si la vue dépasse 200 lignes ; faire passer T047 (depends on T053, T054, T055)

**Checkpoint** : US1 à US3 couvrent la garantie complète : encaissée avant le départ, rendue ou retenue au retour.

---

## Phase 6: User Story 4 - Rendre la caution d'une réservation annulée (Priority: P2)

**Goal**: L'annulation d'une réservation dont la caution est encaissée la fait passer à restituer.

**Independent Test**: Encaisser, annuler : « à restituer » ; restitution sans confirmation de dégât.

- [ ] T057 [P] [US4] Test `functional/deposit/tests/Feature/CancelledReservationDepositTest.php` : scénario 1 (annulation acceptée, caution `to_refund`, `awaiting_since` posé) ; scénario 2 (sans caution : rien à restituer, situation `NotTracked`) ; restitution sans confirmation acceptée pour une réservation annulée ; encaissement refusé sur une réservation annulée
- [ ] T058 [US4] Vérifier que `SyncDepositOnReservationChanged` traite `cancelled` et que `RefundDeposit` n'exige pas la confirmation pour une réservation annulée ; ajuster T050/T053 si T057 échoue (depends on T057, T051, T053)

---

## Phase 7: User Story 5 - Suivre les cautions à restituer depuis toutes les agences (Priority: P2)

**Goal**: Liste des cautions en attente d'action, filtrable par agence, avec ancienneté et retard.

**Independent Test**: Trois cautions (à restituer depuis 10 jours, à solder, bloquée) listées avec leur état et leur ancienneté ; la première en évidence.

- [ ] T059 [P] [US5] Test `functional/deposit/tests/Feature/PendingDepositsListTest.php` : scénario 1 (cautions `to_refund`, `to_settle`, `blocked_by_damage` de plusieurs agences listées avec réservation, client, agence de rattachement, montant, état, `awaiting_since` ; `collected`, `refunded`, `settled` absentes) ; filtre par agence et par état ; scénario 2 (`travelTo` + 8 jours : caution à restituer ou à solder en évidence ; bloquée par un dégât non mise en évidence) ; accès refusé sans `deposits.manage` ; une seule requête pour la liste (pas de requête par ligne)
- [ ] T060 [US5] Créer `functional/deposit/src/Queries/PendingDeposits.php` (`query(?int $agencyId, ?DepositStatus $status): Builder` avec `with(['reservation.customer', 'reservation.machine.agency'])`, tri par `awaiting_since`) et `isOverdue(Deposit): bool` (`overdue_after_days`, heure de Paris) (depends on T059)
- [ ] T061 [US5] Créer `functional/deposit/src/Livewire/PendingDepositsList.php` et sa vue `functional/deposit/resources/views/livewire/pending-deposits-list.blade.php` (`x-page-heading`, filtres agence et état, tableau Flux, `x-empty-state` sans résultat, badge « En retard », lien vers la réservation, mise à jour sur `echo-private:fleet,.deposit.changed`) ; route `Route::livewire('cautions', PendingDepositsList::class)->name('deposit.pending.index')` dans `functional/deposit/routes/web.php` sous `['auth', 'verified', 'can:'.DepositPermission::ManageDeposits->value]` ; faire passer T059 (depends on T060)

---

## Phase 8: User Story 6 - Paramétrer le montant de la caution (Priority: P3)

**Goal**: Montant par défaut et montant par catégorie modifiables ; une caution encaissée garde son montant.

**Independent Test**: 3 000 € pour « nacelle » : une réservation de nacelle exige 3 000 €, une mini-pelle le montant par défaut ; une caution encaissée garde le sien.

- [ ] T062 [P] [US6] Test `functional/deposit/tests/Feature/DepositRatesTest.php` : scénarios 3 et 4 (montant de catégorie modifié : nouvelle caution au nouveau montant, caution encaissée inchangée ; idem pour le montant par défaut) ; scénario 5 (montant vide, nul, négatif ou mal formé refusé : `invalidAmount` ou validation `Money::INPUT_PATTERN`) ; scénario 6 (retrait du montant d'une catégorie → montant par défaut) ; le montant par défaut ne peut pas être retiré ; `updated_by` renseigné ; accès refusé sans `deposit_rates.manage`
- [ ] T063 [US6] Créer `functional/deposit/src/Actions/SetDepositRate.php` (`handle(?MachineCategory $category, Money $amount, Authenticatable&AgencyMember $author): DepositRate`, `updateOrCreate` sous transaction, refus si montant non positif) et `RemoveDepositRate.php` (`handle(MachineCategory $category): void`, sans effet sur le montant par défaut) (depends on T062)
- [ ] T064 [US6] Créer `functional/deposit/src/Livewire/DepositRatesIndex.php` (montant par défaut modifiable + liste des catégories avec montant effectif et mention « par défaut » ou « propre ») et `DepositRateForm.php` (`#[Locked] public MachineCategory $category`, saisie `Money::fromInput`, retrait) avec leurs vues ; routes `cautions/montants` (`deposit.rates.index`) et `cautions/montants/{category}` (`deposit.rates.edit`) sous `can:`.`DepositPermission::ManageDepositRates->value` ; faire passer T062 (depends on T063)

---

## Phase 9: Polish & Cross-Cutting Concerns

- [ ] T065 [P] Ajouter l'entrée de menu « Cautions » (sous-entrées « En attente » et « Montants », chacune affichée selon sa permission) dans `resources/views/layouts/app/sidebar.blade.php`, textes dans `lang/fr`
- [ ] T066 [P] Ajouter à `functional/deposit/database/seeders/` un seeder de démonstration (`DepositDemoSeeder`) appelé par `DatabaseSeeder` : montants pour deux catégories, quelques cautions dans chaque état via les factories
- [ ] T067 [P] Vérifier l'absence de dépendance inverse : `grep -rn "Functional\\\\Deposit" functional/{fleet,booking,inspection,billing}` ne renvoie rien
- [ ] T068 Lancer `vendor/bin/pint --dirty` puis `vendor/bin/pint --test`, `vendor/bin/phpstan clear-result-cache` puis `vendor/bin/phpstan analyse` (zéro erreur) et `php artisan test` (suite complète verte) ; vérifier fichiers < 200 lignes et méthodes < 40 lignes dans `functional/deposit/src` et les fichiers modifiés de `functional/booking/src`
- [ ] T069 Mettre à jour `CLAUDE.md` (section Architecture) : sens des dépendances `deposit → billing → inspection → booking → fleet`, point d'extension `Extensions/CustomerChangeGuards` de `booking`, `DepositPermissionSeeder`
- [ ] T070 Dérouler [quickstart.md](quickstart.md) dans le navigateur (scénarios 1 à 7) et corriger les écarts

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)** : après les prérequis (feu vert, branche à jour, base recréée).
- **Foundational (Phase 2)** : après la phase 1 ; bloque toutes les stories.
- **US1 (Phase 3)** : après la phase 2. MVP.
- **US2 (Phase 4)** : après US1 (la qualification passe par la section de T032–T034) ; T035, T037, T038 (formulaire de réservation) peuvent démarrer dès la phase 2.
- **US3 (Phase 5)** : après US1 (une caution encaissée est nécessaire) ; indépendante d'US2.
- **US4 (Phase 6)** : après US3 (réutilise `SyncDepositStatus` et `RefundDeposit`).
- **US5 (Phase 7)** : après US3 (états d'attente et `awaiting_since`).
- **US6 (Phase 8)** : après la phase 2 seulement (`ResolveDepositAmount`) ; peut être menée en parallèle d'US1.
- **Polish (Phase 9)** : après les stories retenues.

### Within Each User Story

- Tests d'abord, en échec, puis implémentation.
- Requêtes et value objects avant les actions, actions avant les composants Livewire.
- Commit à la fin de chaque phase validée (tests, PHPStan et Pint verts), sans mention d'outil d'IA.

## Parallel Examples

```text
# Phase 2, booking et deposit en parallèle :
T005 CustomerType          T012 enums deposit       T013 migration deposit_rates
T008 CustomerChangeGuards  T009 CustomerChanged     T014 migration deposits

# US1, tests en parallèle :
T027 DepartureRequiresDepositTest   T028 CollectDepositTest   T029 ReservationDepositSectionTest

# US3, tests puis calculs en parallèle :
T040–T047 (tous [P])   puis   T048 DepositRetention ‖ T049 BilledDamagesTotal
```

## Implementation Strategy

### MVP First (User Story 1)

1. Phases 1 et 2.
2. Phase 3 (US1) : la règle de la fiche est tenue pour les clients déjà qualifiés particuliers.
3. **Stop et valider** : quickstart scénario 1.

### Incremental Delivery

1. US1 → US2 : la règle couvre aussi les clients existants sans type et les nouveaux clients.
2. US3 : la caution protège réellement contre les dégâts (objectif des 85 000 €).
3. US4, US5 : aucune caution oubliée.
4. US6 : montants ajustés par catégorie (le montant par défaut suffit jusque-là).

### Coordination

- `UpdateCustomer`, `CustomerChanged` et `CustomerChangeGuards` (T008–T011) sont attendus par la 005 et la 006 : les livrer et commiter en premier, et signaler le commit à la coordination.
- PR vers `003-transmission-facturation`, après la 003.
