---

description: "Task list for feature 006-grands-comptes"
---

# Tasks: Grands comptes : tarifs négociés et bon de commande

**Input**: Design documents from `specs/006-grands-comptes/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/booking-extensions.md), [quickstart.md](quickstart.md)

**Tests**: Inclus, comme pour les features 001 à 003 : un test Feature PHPUnit par scénario d'acceptation (principe VI). Dans chaque story, écrire les tests d'abord et vérifier qu'ils échouent.

**Organization**: Tâches groupées par user story ; chaque story est testable indépendamment une fois la phase 2 terminée.

## ⚠️ Prérequis : features 001, 003 et 004

Cette feature s'appuie sur :

- la **001** jusqu'à `7423fc9` : `RefusalException` (message technique anglais, clé de traduction, `userMessage()`, factories nommées), `AssertsRefusals`, enum `ReservationTransition`, `ReservationTransitionGuards`, `ReservationDetailSections::register(name, position, ReservationTransition ...$guarded)` et la readiness par section (`reservation-transition-readiness` avec `step`, `section`, `is_ready` ; une gardienne muette bloque), contrat `Functional\Fleet\Contracts\AgencyMember` et trait de test `CreatesUsers`, permissions en enum par layer (seeders de layer qui créent seulement ; `PermissionSeeder` donne toutes les permissions au rôle salarié), trait `RecordsAuthorAgency`, composants `x-empty-state`, `x-loading-hint`, `flux:modal`, `Flux::toast` ;
- la **003** avec sa refonte (`Money`, suppression de `Support/`) : `CustomerBillingAccount`, `BillableLine`, `MakeBillableLine`, l'export de secours, `FakeBillingGateway` ;
- la **004** : type de client (particulier / professionnel / à renseigner), action unique d'écriture du client `Functional\Booking\Actions\UpdateCustomer`, événement `Functional\Booking\Events\CustomerChanged` (`ShouldDispatchAfterCommit`) et registre `CustomerChangeGuards` appelé par la qualification du type.

Avant T001 :

1. la 001, la 003 et la 004 sont implémentées et commitées (et la 007 si elle a déjà modifié `BillableLine`) ;
2. la branche `006-grands-comptes` est rebasée par-dessus ;
3. tous les noms de ce document sont définitifs : ceux de la 001 (7423fc9), celui du formatage d'export de la 003 (`Functional\Billing\Exports\ExportLineFormatter`) et le contrat client de la 004 (`specs/004-caution-particuliers/contracts/customer-contract.md` : enum `Functional\Booking\Enums\CustomerType` `Individual`/`Professional`, colonne `customers.type` nullable = à renseigner, `Functional\Booking\Actions\UpdateCustomer::qualify(Customer, CustomerType, Authenticatable&AgencyMember): Customer`, `CustomerChanged(Customer, list<string> $changedAttributes)`, `CustomerChangeGuard::beforeTypeChange(Customer, CustomerType $newType): void`, registre `CustomerChangeGuards::register(class-string)` / `all()`) : vérifier sur la branche rebasée qu'ils n'ont pas bougé ;
4. `docker compose exec -u sail laravel.test php artisan test` passe sur la branche rebasée ;
5. la coordination a donné son feu vert pour `speckit-implement`.

## Décisions prises

Voir [plan.md](plan.md#décisions-prises) :

- **Saisie du numéro à la création (US2 scénario 5)** : dans la section du détail ouvert juste après la création (spec amendée) ; aucun champ dans le formulaire de création (T031, T039).
- **Garde de changement de type (G6)** : registre `CustomerChangeGuards` livré par la 004 dans `UpdateCustomer` ; T023 vérifie qu'il existe, T024 y enregistre la garde.
- **Emplacement (G1)** : nouveau layer `accounts` ; `billing` expose le port `PurchaseOrderNumbers` ; `booking` expose `CustomerBadges`, aligné sur `MachineBadges` de la 007.
- **Modifications de `booking` et `billing`** : faites dans cette branche, après sa remise à jour sur la 001 à la 004 et la 003 corrigée.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US4)

## Conventions pour toutes les tâches

Celles de la 001 s'appliquent sans changement ([tasks.md de la 001](../001-reservation-machines/tasks.md#conventions-pour-toutes-les-tâches)) : commandes via `docker compose exec -u sail laravel.test …` (CLAUDE.md), code en anglais, textes dans `functional/accounts/resources/lang/fr/*.php`, pas de cascade, pas d'observer, pas de `try/catch` (`rescue()` qui relance), pas de commentaire, factories avec `faker()`, contrôles par permission, fichiers de code < 200 lignes. En plus :

- `accounts` dépend de `billing`, `inspection`, `booking` et `fleet` ; **aucun fichier de `billing`, `inspection`, `booking` ni `fleet` n'importe une classe d'`accounts`**.
- `Customer` et `Reservation` ne déclarent aucune relation vers les tables d'`accounts`.
- Les fichiers ajoutés dans `booking` et `billing` sont des points d'extension génériques : ils ne mentionnent ni « grand compte » ni « bon de commande » côté `booking` ; côté `billing`, seul le champ `purchase_order_number` du contrat de transmission.
- Aucun `App\Models\User` dans `accounts` : l'auteur est un `Authenticatable&AgencyMember`, les clés étrangères pointent la table `users` par son nom, les tests créent les salariés avec `Functional\Fleet\Tests\Concerns\CreatesUsers` (`employee()`, `userWithPermissions(AccountsPermission::…)`, `userWithoutPermission()`).
- Interface : composants partagés `x-empty-state`, `x-loading-hint`, `flux:modal`, `Flux::toast`.
- Chaque refus hérite de `Functional\Fleet\Exceptions\RefusalException` (message technique anglais, clé `accounts::refusals.*`, factory nommée) et se teste avec `AssertsRefusals`.
- Aucun prix, tarif ni montant n'est stocké ni affiché par `accounts` (SC-006).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Layer `accounts`, configuration, permissions.

- [X] T001 Créer le layer OSDD `functional/accounts/` avec les commandes `osdd:*` (`composer.json` LayerManifest déclarant la dépendance à `billing`, `inspection`, `booking` et `fleet`, namespace PSR-4 `Functional\\Accounts\\`, `AccountsServiceProvider`, dossiers `src/`, `config/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `resources/views/`, `resources/lang/fr/`, `routes/`, `tests/Feature/`, `tests/Unit/`) ; le déclarer dans le `require` du `composer.json` racine et dans l'`autoload-dev` (`Functional\\Accounts\\Tests\\`), mettre à jour `composer.lock` ; ajouter `functional/accounts/src` à `phpstan.neon` et à la `<source>` de `phpunit.xml`
- [X] T002 [P] Créer `functional/accounts/config/accounts.php` : `highlight_days_before_departure` = 3, `purchase_order_max_length` = 50 ; le charger depuis `AccountsServiceProvider`
- [X] T003 [P] Ajouter `AccountsPermissionSeeder` à `Tests\TestCase::seedPermissions()` (`tests/TestCase.php`) avant `PermissionSeeder`. Créer l'enum `functional/accounts/src/Access/AccountsPermission.php` (`ManageKeyAccounts = 'key_accounts.manage'`, `ManagePurchaseOrders = 'purchase_orders.manage'`) et `functional/accounts/database/seeders/AccountsPermissionSeeder.php` (même forme que `BookingPermissionSeeder` : crée seulement les permissions) ; l'appeler depuis `database/seeders/DatabaseSeeder.php` dans la liste des seeders de permissions des layers, avant `PermissionSeeder` ; **ne pas modifier** `database/seeders/PermissionSeeder.php` (le rôle salarié reçoit `Permission::all()`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Tables, modèles, normalisation du numéro, historique. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

- [X] T004 [P] Créer la migration `key_accounts` dans `functional/accounts/database/migrations/` : `customer_id` → customers « obligatoire, unique » (`->constrained()` sans cascade), `designated_by` → users « obligatoire », `designated_at` date-heure « obligatoire », timestamps
- [X] T005 [P] Créer la migration `reservation_purchase_orders` dans `functional/accounts/database/migrations/` : `reservation_id` → reservations « obligatoire, unique », `number` string(50) avec CHECK `length(btrim(number)) between 1 and 50`, `entered_by` → users « obligatoire », `agency_id` → agencies « obligatoire », `entered_at` date-heure « obligatoire », timestamps ; aucune unicité sur `number` (FR-006)
- [X] T006 [P] Créer le modèle `KeyAccount` dans `functional/accounts/src/Models/KeyAccount.php` (casts de dates ; relations `customer()`, `designator()`) et sa factory `functional/accounts/database/factories/KeyAccountFactory.php` (le client créé par défaut est professionnel)
- [X] T007 [P] Créer le modèle `ReservationPurchaseOrder` dans `functional/accounts/src/Models/ReservationPurchaseOrder.php` (relations `reservation()`, `author()`, `agency()`) et sa factory `functional/accounts/database/factories/ReservationPurchaseOrderFactory.php` (numéro généré avec `faker()`)
- [X] T008 [P] Écrire le test Unit `functional/accounts/tests/Unit/PurchaseOrderNumberTest.php` : espaces en tête et en fin retirés ; chaîne vide ou faite d'espaces refusée ; 50 caractères acceptés, 51 refusés (`InvalidPurchaseOrderNumberException`)
- [X] T009 Créer `functional/accounts/src/Support/PurchaseOrderNumber.php` (normalise et valide un numéro, longueur max lue dans `accounts.purchase_order_max_length`) et `functional/accounts/src/Exceptions/InvalidPurchaseOrderNumberException.php` ; T008 passe (depends on T008)
- [X] T010 [P] Créer `functional/accounts/src/Support/AccountsHistory.php` : `record(Model $subject, string $event, array $details)` via `activity('accounts')`, message traduit `accounts::history.{event}`, auteur, et agence de l'auteur sous la propriété `author_agency_id` (même clé que `RecordsAuthorAgency`, lue sur `Auth::user()` quand c'est un `AgencyMember`) ; textes des événements `key_account_designated`, `key_account_revoked`, `purchase_order_entered`, `purchase_order_corrected` dans `functional/accounts/resources/lang/fr/history.php`
- [X] T011 [P] Créer `functional/accounts/src/Support/KeyAccounts.php` : `isKeyAccount(int $customerId): bool` et `keyAccountIdsAmong(list<int> $customerIds): list<int>` (une seule requête), utilisés par les gardes, la section, les badges et la liste

**Checkpoint**: tables migrées, modèles et factories prêts.

---

## Phase 3: User Story 1 - Désigner un client professionnel comme grand compte (Priority: P1) 🎯 MVP

**Goal**: Un salarié désigne ou retire un grand compte ; le client est signalé à la réservation ; un grand compte ne peut pas être requalifié particulier.

**Independent Test**: Désigner un client professionnel dont l'identifiant de facturation est renseigné : le formulaire de création de réservation affiche le badge et la précision. Tenter la même désignation sur un client particulier : refus.

### Tests for User Story 1

> **Écrire ces tests d'abord et vérifier qu'ils échouent.**

- [X] T012 [P] [US1] Scénario 1 (désignation d'un professionnel avec identifiant de facturation : ligne `key_accounts`, historique avec auteur, agence, date) dans `functional/accounts/tests/Feature/DesignateKeyAccountTest.php`
- [X] T013 [P] [US1] Scénario 2 (refus pour un client particulier et pour un client de type à renseigner, `KeyAccountRefusedException::notProfessional`) dans `functional/accounts/tests/Feature/DesignateKeyAccountTest.php`
- [X] T014 [P] [US1] Scénario 3 (refus sans `CustomerBillingAccount` ou avec `external_ref` vide, `KeyAccountRefusedException::missingBillingRef`) et double désignation refusée (`::alreadyDesignated`) dans `functional/accounts/tests/Feature/DesignateKeyAccountTest.php`
- [X] T015 [P] [US1] Scénario 4 (badge « Grand compte » dans la liste de clients de `CreateReservationForm` et précision pour le client sélectionné ; un client ordinaire n'a pas de badge ; nombre de requêtes constant quel que soit le nombre de clients affichés) dans `functional/accounts/tests/Feature/KeyAccountBadgeTest.php`
- [X] T016 [P] [US1] Scénario 5 (retrait : ligne supprimée, historique, plus d'exigence pour ses réservations confirmées) dans `functional/accounts/tests/Feature/RevokeKeyAccountTest.php`
- [X] T017 [P] [US1] Scénario 6 (requalification « particulier » d'un grand compte refusée par `UpdateCustomer::qualify($customer, CustomerType::Individual, $author)` (004), `KeyAccountMustStayProfessionalException` ; acceptée après retrait) dans `functional/accounts/tests/Feature/KeyAccountTypeGuardTest.php`
- [X] T018 [P] [US1] Écran `/grands-comptes` : accès refusé sans `key_accounts.manage` ; liste, recherche limitée aux clients professionnels, désignation et retrait depuis l'écran, refus affichés par `userMessage()` dans `functional/accounts/tests/Feature/KeyAccountsScreenTest.php`

### Implementation for User Story 1

- [X] T019 [P] [US1] Créer `functional/accounts/src/Exceptions/KeyAccountRefusedException.php` (factories `notProfessional`, `missingBillingRef`, `alreadyDesignated`) et `functional/accounts/src/Exceptions/KeyAccountMustStayProfessionalException.php`, textes dans `functional/accounts/resources/lang/fr/refusals.php`
- [X] T020 [US1] Créer `functional/accounts/src/Actions/DesignateKeyAccount.php` (`handle(Authenticatable&AgencyMember $author, Customer $customer)`) : transaction, `lockForUpdate()` du client, refus « le client est de type professionnel (type de la 004) », « un `CustomerBillingAccount` existe pour ce client avec un `external_ref` non vide », « le client n'est pas déjà grand compte », création de la ligne, historique `key_account_designated`, `CustomerChanged($customer, ['key_account'])` émis après commit (depends on T019)
- [X] T021 [US1] Créer `functional/accounts/src/Actions/RevokeKeyAccount.php` (`handle(Authenticatable&AgencyMember $author, Customer $customer)`) : transaction, suppression de la ligne `key_accounts`, historique `key_account_revoked`, `CustomerChanged($customer, ['key_account'])` émis après commit ; les numéros déjà saisis restent
- [X] T022 [P] [US1] Ajouter le point d'extension `CustomerBadges` dans `booking` selon [contracts/booking-extensions.md](contracts/booking-extensions.md#nouveau--customerbadges-g5), même forme que `MachineBadges` de la 007 : `functional/booking/src/Contracts/CustomerBadgeProvider.php` (`badgesFor(list<int> $customerIds): array<int, list<CustomerBadge>>`, `refreshListeners(): list<string>`), `functional/booking/src/ValueObjects/CustomerBadge.php` (`label`, `color`, `?url`, `?description`), `functional/booking/src/Extensions/CustomerBadges.php` (`register`, `forCustomers`, `refreshListeners` ; singleton enregistré dans `BookingServiceProvider`) ; dans `functional/booking/src/Livewire/CreateReservationForm.php` et sa vue, un seul appel `forCustomers` sur la liste affichée, `flux:badge` à côté du nom, description du client sélectionné, écouteurs `refreshListeners()` via `getListeners()` ; test `functional/booking/tests/Feature/CustomerBadgesTest.php` avec un fournisseur de test (sans fournisseur, rien n'est affiché) ; non-régression : `php artisan test functional/booking/tests` vert
- [X] T023 [US1] Vérifier que le registre `Functional\Booking\Extensions\CustomerChangeGuards` (`register(class-string)`, `all()`) et le contrat `Functional\Booking\Contracts\CustomerChangeGuard::beforeTypeChange(Customer, CustomerType $newType): void` livrés par la 004 sont appelés dans la transaction de `Functional\Booking\Actions\UpdateCustomer::qualify(Customer, CustomerType, Authenticatable&AgencyMember): Customer`, client verrouillé ; s'il manque, s'arrêter et le signaler à la coordination (il appartient à la 004)
- [X] T024 [US1] Créer `functional/accounts/src/Guards/KeyAccountTypeGuard.php` (refuse tout nouveau type autre que professionnel pour un grand compte) et l'enregistrer dans `CustomerChangeGuards` depuis `AccountsServiceProvider` (depends on T019, T023)
- [X] T025 [US1] Créer `functional/accounts/src/Badges/KeyAccountBadgeProvider.php` (une requête via `KeyAccounts::keyAccountIdsAmong`) avec libellé « Grand compte », description « Tarif négocié appliqué par la facturation — bon de commande exigé avant la sortie », lien vers `/grands-comptes`, `refreshListeners()` vide, dans `functional/accounts/resources/lang/fr/key_accounts.php` ; l'enregistrer dans `CustomerBadges` depuis `AccountsServiceProvider` (depends on T022)
- [X] T026 [P] [US1] Créer le contrôle `functional/accounts/src/Access/Controls/KeyAccountControl.php` (permission `key_accounts.manage`) et l'ajouter depuis `AccountsServiceProvider`
- [X] T027 [US1] Créer l'écran `functional/accounts/src/Livewire/KeyAccounts.php` et sa vue `functional/accounts/resources/views/livewire/key-accounts.blade.php` (Flux) selon [contracts/screens.md](contracts/screens.md) : liste des grands comptes (nom, identifiant de facturation, auteur, date), recherche de clients professionnels, désigner, retirer, refus via `DisplaysRefusals` ; route `/grands-comptes` (`accounts.key-accounts`, `can:key_accounts.manage`) dans `functional/accounts/routes/web.php` ; liste vide par `x-empty-state`, recherche avec `x-loading-hint`, confirmation du retrait par `flux:modal`, retours par `Flux::toast` (depends on T020, T021, T026)
- [X] T028 [US1] Ajouter l'entrée « Grands comptes » (visible avec `key_accounts.manage`) dans `resources/views/layouts/app/sidebar.blade.php`

**Checkpoint**: T012 à T018 passent, la suite `booking` reste verte ; un grand compte se désigne, se retire et se voit à la réservation.

---

## Phase 4: User Story 2 - Exiger le bon de commande avant la sortie (Priority: P1)

**Goal**: La section « Bon de commande » permet la saisie et la correction jusqu'à la sortie ; la sortie d'un grand compte sans numéro est refusée côté serveur.

**Independent Test**: Sur une réservation confirmée d'un grand compte sans numéro, la sortie est refusée ; après saisie du numéro, elle est acceptée.

### Tests for User Story 2

> **Écrire ces tests d'abord et vérifier qu'ils échouent.**

- [X] T029 [P] [US2] Scénarios 1 et 3 (section « à saisir » avec mention du tarif négocié ; saisie de « BC-2026-0412 » : numéro, auteur, agence, date affichés, historique `purchase_order_entered`, readiness `departure` passe à prête : événement `reservation-transition-readiness` avec `section: 'accounts.purchase-order-section'`) dans `functional/accounts/tests/Feature/PurchaseOrderSectionTest.php`
- [X] T030 [P] [US2] Scénarios 2 et 4 (sortie refusée sans numéro, `MissingPurchaseOrderException`, même si l'interface a laissé le bouton actif : appel direct de `DepartReservation` ; sortie acceptée avec numéro ; FR-010 : réservation d'un grand compte déjà en cours sans numéro, le retour est accepté) dans `functional/accounts/tests/Feature/PurchaseOrderDepartureGuardTest.php`
- [X] T031 [P] [US2] Scénario 5 amendé (après `CreateReservationForm::save` pour un grand compte, le détail ouvert affiche la section « à saisir » et la saisie y aboutit) dans `functional/accounts/tests/Feature/PurchaseOrderSectionTest.php`
- [X] T032 [P] [US2] Scénarios 6 et 7 (correction avant la sortie avec historique `purchase_order_corrected` portant l'ancien et le nouveau numéro ; modification refusée sur une réservation en cours ou clôturée, `PurchaseOrderFrozenException` ; aucun effacement possible) dans `functional/accounts/tests/Feature/SetPurchaseOrderTest.php`
- [X] T033 [P] [US2] Scénarios 8 et 9 (même numéro sur deux réservations du même client accepté ; professionnel ordinaire : sortie non bloquée, numéro facultatif saisissable ; client particulier : section masquée qui émet quand même sa readiness prête, saisie refusée `PurchaseOrderRefusedException::notProfessional`) et cas limites (numéro trimé, vide refusé, 51 caractères refusé ; client désigné grand compte après la création : exigence à la sortie) dans `functional/accounts/tests/Feature/SetPurchaseOrderTest.php`
- [X] T034 [P] [US2] Écrire le test Unit `functional/accounts/tests/Unit/PurchaseOrderSectionStateTest.php` couvrant le tableau d'état de [data-model.md](data-model.md#état-affiché-de-la-section--bon-de-commande--dérivé-sans-pattern-state) : particulier ou type à renseigner → masquée et départ prêt ; professionnel sans numéro → facultatif et prêt ; grand compte sans numéro → exigé et non prêt ; numéro saisi sur réservation confirmée → saisi modifiable et prêt ; numéro saisi sur réservation en cours ou clôturée → figé

### Implementation for User Story 2

- [X] T035 [P] [US2] Créer `functional/accounts/src/Exceptions/MissingPurchaseOrderException.php`, `PurchaseOrderFrozenException.php` et `PurchaseOrderRefusedException.php` (factory `notProfessional`), textes dans `functional/accounts/resources/lang/fr/refusals.php`
- [X] T036 [US2] Créer `functional/accounts/src/Actions/SetPurchaseOrder.php` (`handle(Authenticatable&AgencyMember $author, Reservation $reservation, string $number)`) : transaction, `lockForUpdate()` de la réservation, refus si la réservation n'est pas `confirmed` (`PurchaseOrderFrozenException`) ou si son client n'est pas professionnel, normalisation par `PurchaseOrderNumber`, création ou remplacement de `number`, `entered_by`, `agency_id` (`$author->agencyId()`), `entered_at`, historique `purchase_order_entered` ou `purchase_order_corrected` (ancien et nouveau numéro) (depends on T009, T010, T035)
- [X] T037 [US2] Créer `functional/accounts/src/Guards/PurchaseOrderDepartureGuard.php` (`beforeDeparture` : grand compte au moment de la sortie et aucune ligne `reservation_purchase_orders` → `MissingPurchaseOrderException` ; `beforeReturn` sans effet) et l'enregistrer dans `ReservationTransitionGuards` depuis `AccountsServiceProvider` (depends on T011, T035)
- [X] T038 [P] [US2] Créer le contrôle `functional/accounts/src/Access/Controls/PurchaseOrderControl.php` (permission `purchase_orders.manage`) et l'ajouter depuis `AccountsServiceProvider`
- [X] T039 [US2] Créer l'enum `functional/accounts/src/Enums/PurchaseOrderSectionStatus.php` (`hidden`, `optional`, `required`, `entered`, `frozen`, avec `isReadyForDeparture()`) et `functional/accounts/src/Support/PurchaseOrderSectionState.php` qui le calcule à partir du type de client, de la désignation, du numéro et du statut de la réservation (T034 passe) ; créer la section `functional/accounts/src/Livewire/PurchaseOrderSection.php` et sa vue `functional/accounts/resources/views/livewire/purchase-order-section.blade.php` selon le tableau d'état de [data-model.md](data-model.md#état-affiché-de-la-section--bon-de-commande--dérivé-sans-pattern-state) : masquée pour un particulier ou un type à renseigner, « facultatif », « exigé, à saisir », « saisi (modifiable) », « saisi (figé) » ; mention du tarif négocié pour un grand compte ; saisie et correction via `SetPurchaseOrder` (bouton visible avec `purchase_orders.manage`) ; émet `dispatch('reservation-transition-readiness', step: ReservationTransition::Departure->value, section: 'accounts.purchase-order-section', is_ready: …)` au montage et après chaque saisie, **y compris quand elle est masquée** (une gardienne muette bloque la sortie) ; confirmation par `Flux::toast` ; l'enregistrer par `app(ReservationDetailSections::class)->register('accounts.purchase-order-section', 15, ReservationTransition::Departure)` depuis `AccountsServiceProvider` (depends on T034, T036, T038)

**Checkpoint**: T029 à T034 passent ; la suite de la 001 à la 004 reste verte.

---

## Phase 5: User Story 3 - Transmettre le numéro de bon de commande à la facturation (Priority: P1)

**Goal**: Chaque période et chaque dégât transmis, et chaque ligne de l'export de secours, portent le numéro de la réservation.

**Independent Test**: Clôturer une réservation d'un grand compte portant « BC-2026-0412 » : la ligne reçue par `FakeBillingGateway` contient le numéro ; un dégât refacturé de la même réservation aussi.

### Tests for User Story 3

> **Écrire ces tests d'abord et vérifier qu'ils échouent.**

- [ ] T040 [P] [US3] Scénarios 1 et 2 (période finale puis période intermédiaire de fin de mois portant `purchase_order_number`, sans montant de location ; horloge contrôlée) dans `functional/accounts/tests/Feature/PurchaseOrderTransmissionTest.php`
- [ ] T041 [P] [US3] Scénario 3 (dégât refacturé portant le numéro de la réservation d'origine) dans `functional/accounts/tests/Feature/PurchaseOrderTransmissionTest.php`
- [ ] T042 [P] [US3] Scénario 4 (export de secours : colonne `purchase_order_number` en dernière position, remplie pour les éléments qui en ont un, vide sinon ; numéros chargés en une requête) dans `functional/accounts/tests/Feature/PurchaseOrderExportTest.php`
- [ ] T043 [P] [US3] Scénarios 5 et 6 (réservation sans numéro transmise comme dans la 003 ; une transmission déjà `sent` n'est pas renvoyée quand un numéro existe) dans `functional/accounts/tests/Feature/PurchaseOrderTransmissionTest.php`
- [ ] T044 [P] [US3] Sans layer `accounts` lié : `NullPurchaseOrderNumbers` par défaut, `purchase_order_number` à `null` dans la ligne et vide dans l'export, dans `functional/billing/tests/Feature/PurchaseOrderNumbersPortTest.php`

### Implementation for User Story 3

- [ ] T045 [US3] Ajouter le port dans `billing` selon [contracts/billing-purchase-order.md](contracts/billing-purchase-order.md) : `functional/billing/src/Contracts/PurchaseOrderNumbers.php` (`forReservation(int): ?string`, `forReservations(list<int>): array<int, string>`) et `NullPurchaseOrderNumbers` (emplacement repris de la structure de `billing` après rebase de la 003), liée par défaut avec `bindIf` dans `BillingServiceProvider::register()`
- [ ] T046 [US3] Ajouter `?string $purchaseOrderNumber` à `functional/billing/src/Lines/RentalContext.php` (renseigné par `RentalContext::fromTransmission`) ; `functional/billing/src/Lines/RentalPeriodLine.php` et `functional/billing/src/Lines/DamageLine.php` ajoutent `purchase_order_number` en **dernière** clé de `toArray()` ; `functional/billing/src/Actions/MakeBillableLine.php` lit le numéro par `PurchaseOrderNumbers::forReservation` dans `handle()` et expose `handleAll(Collection<int, Transmission>): list<BillableLine>` qui précharge les numéros par `forReservations` (aucune requête par ligne) (depends on T045)
- [ ] T047 [US3] Ajouter la colonne `purchase_order_number` en dernière position de l'export de secours (`functional/billing/src/Exports/ExportLineFormatter.php` replace `purchase_order_number` après `amount_excl_tax`, pour une fusion simple avec la 007) ; `functional/billing/src/Actions/CreateBillingExport.php` construit ses lignes par `MakeBillableLine::handleAll` ; non-régression : `php artisan test functional/billing/tests` vert après T045 à T047 (depends on T046)
- [ ] T048 [US3] Créer `functional/accounts/src/Billing/KeyAccountPurchaseOrderNumbers.php` (lecture de `reservation_purchase_orders.number`, unitaire et groupée) et le lier à `PurchaseOrderNumbers` avec `bind` dans `AccountsServiceProvider::register()` (l'emporte sur le `bindIf` de `billing` quel que soit l'ordre des providers) (depends on T045)
- [ ] T049 [US3] Vérifier que `FakeBillingGateway` conserve `purchase_order_number` dans les lignes reçues (`billing:fake-gateway --received`) ; l'ajouter à l'affichage de la commande si nécessaire, dans `functional/billing/src/Console/FakeGatewayCommand.php`

**Checkpoint**: T040 à T044 passent ; la suite de la 003 reste verte.

---

## Phase 6: User Story 4 - Relancer les bons de commande manquants avant le départ (Priority: P2)

**Goal**: Liste des réservations confirmées de grands comptes sans numéro, triées par date de départ, avec mise en évidence et saisie en ligne.

**Independent Test**: Avec trois réservations (sans numéro à J+2, sans numéro à J+10, avec numéro), la liste affiche les deux premières, J+2 en tête et mise en évidence.

### Tests for User Story 4

> **Écrire ces tests d'abord et vérifier qu'ils échouent.**

- [ ] T050 [P] [US4] Scénarios 1 et 2 (contenu, tri par date de début, mise en évidence à 3 jours ou moins en heure de Paris via `travelTo()` ; réservations annulées, en cours et de clients ordinaires absentes ; nombre de requêtes constant) dans `functional/accounts/tests/Feature/MissingPurchaseOrdersTest.php`
- [ ] T051 [P] [US4] Scénarios 3 et 4 (saisie en ligne : la réservation sort de la liste ; filtre par agence de rattachement de la machine ; accès refusé sans `purchase_orders.manage`) dans `functional/accounts/tests/Feature/MissingPurchaseOrdersTest.php`

### Implementation for User Story 4

- [ ] T052 [US4] Créer `functional/accounts/src/Queries/MissingPurchaseOrders.php` : réservations `confirmed` dont le client a une ligne `key_accounts` et sans ligne `reservation_purchase_orders` (jointures et `whereNotExists`, pas de requête par ligne), filtre optionnel `agency_id` de la machine, tri par `start_date` (la « date de départ » de la spec est la date de début prévue), chargement de la machine, du client et de l'agence
- [ ] T053 [US4] Créer l'écran `functional/accounts/src/Livewire/MissingPurchaseOrders.php` et sa vue `functional/accounts/resources/views/livewire/missing-purchase-orders.blade.php` selon [contracts/screens.md](contracts/screens.md) : colonnes, filtre agence, mise en évidence selon `accounts.highlight_days_before_departure`, saisie en ligne via `SetPurchaseOrder`, refus via `DisplaysRefusals`, liste vide par `x-empty-state`, saisie confirmée par `Flux::toast` ; route `/bons-de-commande` (`accounts.missing-purchase-orders`, `can:purchase_orders.manage`) dans `functional/accounts/routes/web.php` (depends on T036, T052)
- [ ] T054 [US4] Ajouter l'entrée « Bons de commande » (visible avec `purchase_orders.manage`) dans `resources/views/layouts/app/sidebar.blade.php`

**Checkpoint**: T050 et T051 passent.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [ ] T055 [P] Ajouter des données de démonstration dans `functional/accounts/database/seeders/AccountsDemoSeeder.php` (deux grands comptes avec identifiant de facturation, réservations avec et sans numéro), appelé depuis `database/seeders/DatabaseSeeder.php` en environnement local
- [ ] T056 [P] Vérifier qu'aucun fichier de `billing`, `inspection`, `booking` ni `fleet` n'importe `Functional\Accounts` (test d'architecture dans `functional/accounts/tests/Unit/LayerBoundariesTest.php` qui parcourt les fichiers de ces layers)
- [ ] T057 Reporter le champ `purchase_order_number` dans les contrats de la 003 (`specs/003-transmission-facturation/contracts/billing-gateway.md` et `export-format.md`) si la coordination valide cette modification
- [ ] T058 `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse` à zéro erreur, `php artisan test` vert (suite complète) ; vérifier que chaque fichier de code fait moins de 200 lignes
- [ ] T059 Dérouler [quickstart.md](quickstart.md) dans l'environnement local et noter tout écart

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)** : après les prérequis (001, 003, 004 commitées, branche rebasée, feu vert de la coordination).
- **Foundational (Phase 2)** : après la phase 1 ; bloque toutes les stories.
- **US1 (Phase 3)** : après la phase 2.
- **US2 (Phase 4)** : après la phase 2 ; ses tests créent les grands comptes par factory, sans dépendre des écrans de la US1.
- **US3 (Phase 5)** : après la phase 2 ; ses tests créent les numéros par factory.
- **US4 (Phase 6)** : après T036 (saisie en ligne).
- **Polish (Phase 7)** : après toutes les stories.

### Story Completion Order

US1 → US2 → US3 → US4 en livraison séquentielle ; US2 et US3 peuvent avancer en parallèle après la phase 2 (fichiers distincts, sauf `AccountsServiceProvider` à fusionner).

### Within Each User Story

Tests d'abord (rouges), puis exceptions, actions, gardes et points d'extension, puis écrans.

### Parallel Opportunities

- Phase 2 : T004 à T008, T010, T011.
- US1 : tous les tests T012 à T018 ; T019, T022, T026 en parallèle.
- US2 : tous les tests T029 à T034 ; T035 et T038 en parallèle.
- US3 : tous les tests T040 à T044 ; T048 en parallèle de T046 après T045.

## Parallel Example: User Story 1

```text
T012, T013, T014 (DesignateKeyAccountTest), T015 (KeyAccountBadgeTest), T016, T017, T018
puis en parallèle : T019 (exceptions), T022 (CustomerBadges dans booking), T026 (contrôle)
```

## Parallel Example: US2 et US3

```text
Développeur A : T029–T039 (section, saisie, garde de sortie)
Développeur B : T040–T049 (port billing, BillableLine, export, liaison accounts)
```

## Implementation Strategy

### MVP First

Phases 1 et 2, puis US1 et US2 : les grands comptes sont désignés et aucune machine ne part sans bon de commande. Valider avec la coordination avant US3.

### Incremental Delivery

1. US1 + US2 : règle de la fiche garantie à la sortie.
2. US3 : le numéro arrive sur la facture.
3. US4 : relance anticipée des bons manquants.

Un commit par phase validée, messages sans mention d'outil d'IA, aucun push sans accord de l'utilisateur.

## Notes

- Tous les noms sont définitifs (001 7423fc9, 003 `ExportLineFormatter`, contrat client de la 004) ; au rebase, seulement vérifier qu'ils n'ont pas bougé.
- Toute découverte qui remet en cause le plan pendant l'implémentation : s'arrêter et revenir au plan, ne pas improviser.
