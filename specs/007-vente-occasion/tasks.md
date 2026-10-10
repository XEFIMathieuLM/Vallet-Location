---

description: "Task list for the used machine sales feature"
---

# Tasks: Vente de machines d'occasion

**Input**: Design documents from `specs/007-vente-occasion/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ (extension-points, billing-sale-line, screens), quickstart.md

**Tests**: obligatoires (constitution, principe VI) : un test Feature par scénario d'acceptation ; tests Unit purs (sans base ni application) pour les calculs et transitions en mémoire, Feature pour tout ce qui passe par la base (constitution 1.0.1 annoncée) ; refus vérifiés par `AssertsRefusals::assertRefused` (classe, texte français, message technique anglais) ; dans chaque phase, les tests sont écrits d'abord et doivent échouer avant l'implémentation.

**Organization**: tâches regroupées par user story ; chemins depuis la racine du dépôt (dépôt unique).

## Format: `[ID] [P?] [Story] Description`

- **[P]** : parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]** : user story de la spec (US1 à US5)

## Prérequis (constitution : « Une feature qui s'appuie sur une autre déclare ses prérequis »)

| Prérequis | Éléments attendus | État |
|---|---|---|
| 001-reservation-machines (toutes phases + corrections de conformité) | `Machine`, `MachineStatus::Retired`, `RetireMachine`, `MachineRetirementGuard`, `RefusalException`, `DisplaysRefusals` ; `Customer`, `Reservation`, `ReservationStatus`, `CreateReservation` (verrou `machines`), `AvailableMachinesQuery`, `Planning`, `AvailabilitySearch`, `ReservationTransitionGuards` (modèle de registre) | sur `origin/003-transmission-facturation` ; corrections en cours |
| 002-photos-qr-code | aucun élément utilisé directement ; présent dans la base | idem |
| Socle 001 corrigé (`e8cba36`) | `RefusalException` abstraite à constructeur protégé (message technique anglais, clé de traduction, remplacements, `userMessage()`), contrat `HasLabel`, `DisplaysRefusals` affichant `userMessage()`, `AssertsRefusals::assertRefused`, enum `ReservationTransition` | commit local de la coordination, à fusionner |
| Socle 003 corrigé (`bdef4c6`) | `Functional\Billing\Money\Money` (`fromInput`, `fromStored`, `isPositive`, `plus`, `format`), `MoneyCast`, `InvalidMoneyException`, `BillableLine::amountExclTax: ?Money`, `Exports/ExportLineFormatter` | idem |
| 003-transmission-facturation (toutes phases + corrections) | `Transmission`, `TransmissionStatus`, `SendTransmissionJob`, `TransmissionLifecycle`, `BillingHistory`, `MakeBillableLine`, `BillableLine`, `BillableLineType`, `CustomerBillingAccount`, `FakeBillingGateway`, `billing:reconcile`, écran `Transmissions`, export de secours | idem |
| Points d'extension E1 à E4 (Phase 2) | voir `contracts/extension-points.md` | **arbitré** : réalisés dans cette branche, un commit par bloc ; E1 et E2 seulement après la remise à jour de la branche sur les corrections de 001 et 003 |

### Formes finales à respecter (base `a1c47ae`, constitution 1.0.1)

- **Utilisateurs** : plus de `App\Models\User` dans les layers ; auteur typé `Model&AgencyMember` (`Functional\Fleet\Contracts\AgencyMember`), modèle résolu par `config('auth.providers.users.model')` ; Livewire : trait `ActsAsAgencyMember` (layer inspection) ; tests : traits `CreatesUsers` et `AssertsRefusals` (`functional/fleet/tests/Concerns`).
- **Permissions** : enum `SalesPermission` (`sales.manage`) ; `SalesPermissionSeeder` crée seulement les permissions ; appelé dans `DatabaseSeeder` avant `PermissionSeeder` (qui donne `Permission::all()` au rôle salarié) et ajouté à `seedPermissions()` de `tests/TestCase.php`.
- **Historique** : `activity('sales')->performedOn()->causedBy($agencyMember)` avec la propriété `author_agency_id`, sur le modèle de `Functional\Billing\History\BillingHistory` ; événements dans un enum `SaleHistoryEvent`.
- **Écrans** : `x-page-heading`, `x-empty-state`, `x-loading-hint`, `flux:modal` pour les motifs, `Flux::toast` après chaque action, refus via `DisplaysRefusals`.
- **billing** : lignes = interface `Functional\Billing\Lines\BillableLine` (`idempotencyKey()`, `customerRef()`, `toArray()`) ; la ligne de vente est une classe de sales (`SaleLine`) qui l'implémente ; `Money` / `MoneyCast` dans `Functional\Billing\Money` ; `Exports/ExportLineFormatter` ; historique dans `History/BillingHistory` ; cycle dans `Transmissions/TransmissionLifecycle`.

`speckit-implement` ne démarre qu'après le feu vert de la session de coordination et la mise à jour de la branche 007 par-dessus les corrections de 001 à 003.

---

## Phase 1: Setup (layer `sales`)

**Purpose**: créer le layer et le brancher dans l'application.

- [X] T001 Générer le layer avec `docker compose exec -u sail laravel.test php artisan osdd:layer functional/sales --no-interaction` (vérifier la commande exacte via `php artisan list osdd`) et compléter `functional/sales/composer.json` : `require` `functional/billing`, `functional/booking`, `functional/fleet`, `xefi/laravel-osdd` ; `functional/inspection` (trait `ActsAsAgencyMember`) ; autoload `Functional\\Sales\\` (src, seeders, factories, et `Functional\\Sales\\Tests\\` en autoload-dev racine comme les autres layers) ; provider `Functional\\Sales\\Providers\\SalesServiceProvider`
- [X] T002 Ajouter `functional/sales` au `composer.json` racine (path repository + `require`), puis `composer update functional/sales` dans le conteneur
- [X] T003 [P] Ajouter `functional/sales/src` à `<source>` de `phpunit.xml` et `functional/sales/src/`, `database/`, `routes/` aux `paths` de `phpstan.neon`
- [X] T004 Créer `functional/sales/src/Providers/SalesServiceProvider.php` (extends `LayerServiceProvider`) : migrations, traductions `sales`, vues `sales`, routes `web` et `channels` ; enregistrements des points d'extension laissés vides jusqu'aux phases suivantes
- [X] T005 [P] Créer l'enum `functional/sales/src/Access/SalesPermission.php` (`Manage = 'sales.manage'`) et `functional/sales/database/seeders/SalesPermissionSeeder.php` (crée seulement, modèle `BillingPermissionSeeder`) ; l'appeler dans `database/seeders/DatabaseSeeder.php` avant `PermissionSeeder` et l'ajouter à `seedPermissions()` de `tests/TestCase.php`
- [X] T006 [P] Créer `functional/sales/routes/channels.php` : canal privé `sales` autorisé par `$user->can(SalesPermission::Manage->value)`
- [X] T007 [P] Créer `functional/sales/tests/Feature/LayerBoundariesTest.php` : aucun fichier de `functional/{billing,inspection,booking,fleet}` ne contient `Functional\Sales` (modèle : `functional/billing/tests/Feature/LayerBoundariesTest.php`)

**Checkpoint**: `php artisan about` liste le provider ; `LayerBoundariesTest` vert ; PHPStan vert.

---

## Phase 2: Points d'extension des layers existants (E1 à E4) — modification de layers existants (arbitrée : dans 007)

**Purpose**: exposer les registres génériques dont sales a besoin, sans aucune référence à la vente. Chaque sous-bloc est commité séparément. Aucun changement de comportement tant que rien n'est enregistré : les suites 001 à 003 restent vertes. E1 et E2 ne démarrent qu'après la remise à jour de la branche sur les corrections de 001 et 003 (conflits sur `CreateReservation`, `AvailableMachinesQuery`, `RetireMachine`).

### E1 — booking : gardes de création de réservation

- [X] T008 [P] Test `functional/booking/tests/Feature/ReservationRequestGuardsTest.php` : un garde de test enregistré qui lève une `RefusalException` fait refuser `CreateReservation` (aucune réservation créée) ; un garde qui restreint le builder exclut la machine de `AvailableMachinesQuery` ; sans garde, comportement inchangé ; non-régression : `CreateReservationTest`, `ReservationExclusionConstraintTest`, `AvailabilitySearchTest`, `ReservationEligibilityTest` de booking verts sans modification
- [X] T009 [P] Créer `functional/booking/src/Contracts/ReservationRequestGuard.php` (`ensureCanReserve(Machine $lockedMachine, CarbonImmutable $startDate, CarbonImmutable $endDate): void`, `excludeUnavailable(Builder $machines, CarbonImmutable $startDate, CarbonImmutable $endDate): void`) et `functional/booking/src/Extensions/ReservationRequestGuards.php` (même forme que `ReservationTransitionGuards`)
- [X] T010 Enregistrer `ReservationRequestGuards` en singleton dans `functional/booking/src/Providers/BookingServiceProvider.php` ; appeler chaque garde dans `functional/booking/src/Actions/CreateReservation.php` (`reserve()`, après `ensureReservableUntil`, sous le verrou) et dans `functional/booking/src/Queries/AvailableMachinesQuery.php`

### E2 — fleet : gardes de retrait en registre

- [X] T011 [P] Test `functional/fleet/tests/Feature/MachineRetirementGuardsTest.php` : deux gardes enregistrés sont tous deux appelés ; le refus de l'un annule le retrait ; sans garde, le retrait passe ; vérifier que `functional/booking/tests/Feature/MachineRetirementTest.php` reste vert
- [X] T012 Créer `functional/fleet/src/Extensions/MachineRetirementGuards.php` ; `functional/fleet/src/Actions/RetireMachine.php` dépend du registre et appelle tous les gardes ; `functional/fleet/src/Providers/FleetServiceProvider.php` : singleton du registre, suppression du `bindIf` ; supprimer `functional/fleet/src/Guards/UnrestrictedRetirement.php`
- [X] T013 `functional/booking/src/Providers/BookingServiceProvider.php` : remplacer `bind(MachineRetirementGuard::class, ActiveReservationsRetirementGuard::class)` par `$this->app->make(MachineRetirementGuards::class)->register(ActiveReservationsRetirementGuard::class)` dans `boot()`

### E3 — fleet : badges de machine

- [X] T014 [P] Test `functional/fleet/tests/Feature/MachineBadgesTest.php` : un fournisseur de test enregistré fait apparaître son badge (libellé, lien) dans `MachineIndex` ; le nombre de requêtes ne croît pas avec le nombre de machines ; un événement listé par `refreshListeners()` rafraîchit les badges ; test équivalent dans `functional/booking/tests/Feature/PlanningTest.php` et `AvailabilitySearchTest.php` (ajout de cas)
- [X] T015 [P] Créer `functional/fleet/src/Contracts/MachineBadgeProvider.php` (`badgesFor(array $machineIds): array<int, list<MachineBadge>>`, `refreshListeners(): list<string>`), `functional/fleet/src/Data/MachineBadge.php` (`label`, `color`, `?url`), `functional/fleet/src/Extensions/MachineBadges.php` (`register`, `forMachines`) ; singleton dans `FleetServiceProvider`
- [X] T016 Afficher les badges (`flux:badge`, lien si `url`) dans `functional/fleet/resources/views/livewire/machine-index.blade.php`, `functional/booking/resources/views/livewire/planning.blade.php`, `functional/booking/resources/views/livewire/availability-search.blade.php` ; les composants `MachineIndex`, `Planning`, `AvailabilitySearch` chargent `MachineBadges::forMachines()` une fois pour les machines affichées et ajoutent `MachineBadges::refreshListeners()` à leurs écouteurs (`getListeners()`)

### E4 — billing : sources de transmission

- [X] T017 [P] Test `functional/billing/tests/Feature/BillableSourcesTest.php` : une source de test enregistrée ; `QueueSourceTransmission` crée une transmission (`reservation_id` null, `source_type`, `source_id`) puis renvoie la même au second appel ; l'envoi utilise `line()` de la source ; échec, relance, liste à traiter, export de secours et historique (rattaché à `historySubject`) fonctionnent comme pour une période ; l'écran `Transmissions` affiche `subject()` ; type inconnu → `UnknownBillableSourceException`
- [X] T018 [P] Test `functional/billing/tests/Feature/TransmissionSourceConstraintsTest.php` : la base refuse une transmission sans source, avec deux sources, avec `source_type` sans `source_id`, une période sans `reservation_id`, et un doublon `(source_type, source_id)` ; `migrate:rollback` de la migration 000065 restaure le schéma de 003 (transmissions existantes intactes) ; non-régression : toute la suite `functional/billing/tests` verte sans modification
- [X] T019 Migration `functional/billing/database/migrations/2026_10_10_000065_add_source_to_transmissions_table.php` : `reservation_id` nullable ; `source_type` string nullable ; `source_id` bigint nullable ; unique `(source_type, source_id)` ; remplacer le CHECK `transmissions_single_source` par `num_nonnulls(billable_period_id, damage_settlement_id, source_id) = 1` ; CHECK `(source_id IS NULL) = (source_type IS NULL)` ; CHECK `source_id IS NOT NULL OR reservation_id IS NOT NULL` ; `down()` symétrique
- [X] T020 [P] Ajouter `UsedMachineSale = 'used_machine_sale'` à `functional/billing/src/Enums/BillableLineType.php` ; ajouter `amountExclTax(): ?Money` à l'interface `functional/billing/src/Lines/BillableLine.php` (implémenté par `RentalPeriodLine` → null et `DamageLine`) ; `functional/billing/src/Exports/ExportLineFormatter.php` produit une liste de colonnes fixe (colonnes actuelles + `source_ref`, `sale_date`, valeurs absentes à null) et formate `amountExclTax()` au lieu de tester `DamageLine`
- [X] T021 [P] Créer `functional/billing/src/Contracts/BillableSource.php`, `functional/billing/src/Transmissions/TransmissionSubject.php`, `functional/billing/src/Extensions/BillableSources.php`, `functional/billing/src/Exceptions/UnknownBillableSourceException.php` (`final`, erreur de configuration comme `TransmissionWithoutSourceException`, factory `forType()`) ; singleton dans `BillingServiceProvider`
- [X] T022 Créer `functional/billing/src/Actions/QueueSourceTransmission.php` (idempotent : `firstOrCreate` sur `(source_type, source_id)` dans la transaction appelante ; `SendTransmissionJob` dispatché `afterCommit`) ; ajouter `source_type` (cast `BillableLineType`), `source_id` à `Transmission` (fillable, PHPDoc) et à `TransmissionFactory` (état `forSource`)
- [X] T023 Déléguer à la source quand `source_type` est renseigné : `functional/billing/src/Actions/MakeBillableLine.php` (avant le contexte de location), `functional/billing/src/History/BillingHistory.php` (accepte un `Model`), `functional/billing/src/Transmissions/TransmissionLifecycle.php` (sujet = réservation ou `historySubject` de la source), `functional/billing/src/Livewire/Transmissions.php` + `functional/billing/resources/views/livewire/transmissions.blade.php` (sujets résolus par lot, pas de requête dans une boucle)

**Checkpoint**: suite complète verte (001 à 003 inchangées), PHPStan vert ; un commit par sous-bloc E1, E2, E3, E4.

---

## Phase 3: Foundational (socle de la vente)

**Purpose**: tables, états, modèles et outillage communs à toutes les user stories.

- [X] T024 [P] Tests Unit `functional/sales/tests/Unit/SaleStateTest.php` : transitions autorisées `reserve` (listed → reserved), `release` (reserved → listed), `sell` (reserved → sold), `cancel` (listed|reserved → cancelled) ; toute autre transition lève `IllegalSaleTransitionException`
- [X] T025 [P] Tests Unit `functional/sales/tests/Unit/OfferStateTest.php` : `accept`, `reject` depuis pending ; `withdraw` depuis pending et accepted ; tout le reste lève `IllegalOfferTransitionException`
- [X] T026 [P] Test `functional/sales/tests/Feature/SaleConstraintsTest.php` (Feature : passe par la base) : la base refuse un prix `<= 0`, une deuxième vente non annulée sur la même machine (y compris après une vente `sold`), une deuxième offre acceptée sur une vente, une vente `reserved` sans acheteur / prix final / date de remise, une vente `sold` sans `handed_over_on`, une vente `cancelled` sans motif
- [X] T027 Migration `functional/sales/database/migrations/2026_10_10_000070_create_sales_table.php` selon `data-model.md` : `machine_id` FK ; `status` string défaut `listed` ; `asking_price_cents` integer CHECK `> 0` ; `year_of_manufacture` smallint nullable CHECK `>= 1950` ; `operating_hours` integer nullable CHECK `>= 0` ; `condition` string ; `comment` text nullable ; `agency_id`, `listed_by` FK ; `buyer_id`, `accepted_offer_id` (sans FK ici), `final_price_cents` (CHECK `> 0`), `planned_handover_date`, `handed_over_on`, `handed_over_by`, `cancellation_reason`, `cancelled_by` nullables ; index unique partiel `sales_one_live_sale_per_machine` `(machine_id) WHERE status <> 'cancelled'` ; CHECK `sales_reservation_fields`, `sales_handover_fields`, `sales_cancellation_fields` ; index `(status, planned_handover_date)` ; aucune FK en cascade
- [X] T028 Migrations `functional/sales/database/migrations/2026_10_10_000071_create_sale_offers_table.php` (`sale_id`, `customer_id`, `recorded_by` FK ; `amount_cents` CHECK `> 0` ; `offered_on` date ; `status` défaut `pending` ; `decided_by` FK nullable, `decided_at` nullable ; CHECK `(status = 'pending') = (decided_by IS NULL)` ; index unique partiel `sale_offers_one_accepted_per_sale` `(sale_id) WHERE status = 'accepted'`) et `2026_10_10_000072_add_accepted_offer_foreign_key_to_sales_table.php`
- [X] T029 [P] Enums `functional/sales/src/Enums/SaleStatus.php` et `functional/sales/src/Enums/OfferStatus.php` (implémentent `HasLabel`, `label()` et `color()` traduits)
- [X] T030 [P] États `functional/sales/src/States/` : `SaleState` (interface), `ListedSaleState`, `ReservedSaleState`, `SoldSaleState`, `CancelledSaleState`, `RefusesSaleTransitions` (trait), `SaleStateFactory` ; `OfferState`, `PendingOfferState`, `AcceptedOfferState`, `RejectedOfferState`, `WithdrawnOfferState`, `RefusesOfferTransitions`, `OfferStateFactory` ; enums `SaleTransition`, `OfferTransition` (`HasLabel`) ; exceptions `final` `IllegalSaleTransitionException`, `IllegalOfferTransitionException` dans `functional/sales/src/Exceptions/` (étendent `Functional\Fleet\Exceptions\RefusalException`, factory nommée avec message technique anglais + clé `sales::refusals.*`, sur le modèle de `IllegalMachineTransitionException`)
- [X] T031 Modèles `functional/sales/src/Models/Sale.php` (casts enums, `immutable_date`, `asking_price` et `final_price` via `MoneyCast::class.':asking_price_cents'` / `':final_price_cents'`, relations `machine`, `agency`, `buyer`, `acceptedOffer`, `offers`, `lister` ; `state()` ; `HasControl`) et `functional/sales/src/Models/SaleOffer.php` (`amount` via `MoneyCast` sur `amount_cents` ; relations `sale`, `customer`, `recorder` ; `state()`)
- [X] T032 [P] Factories `functional/sales/database/factories/SaleFactory.php` (états `listed`, `reserved`, `sold`, `cancelled` cohérents avec les CHECK) et `SaleOfferFactory.php`, avec `faker()`
- [X] T033 [P] `functional/sales/src/History/SaleHistory.php` + enum `SaleHistoryEvent` (`activity('sales')->performedOn($sale)->causedBy($agencyMember)`, propriété `author_agency_id`, libellés `sales::history.*`) ; `functional/sales/src/Events/SaleChanged.php` (`ShouldBroadcast`, `ShouldDispatchAfterCommit`, canal privé `sales`, `broadcastAs` `sale.changed`, charge utile `id`, `machine_id`, `status`, `planned_handover_date`)
- [X] T034 [P] `functional/sales/src/Access/Controls/SaleControl.php` (permission `sales.manage`, périmètre global comme fleet) ; enregistrement dans `SalesServiceProvider`
- [X] T035 [P] `functional/sales/tests/Concerns/BuildsSalesFixtures.php` (utilise `CreatesUsers`, `AssertsRefusals`, `seedPermissions()`) : `machineForSale()`, `listedSale()`, `reservedSale(string $plannedHandoverDate)`, `confirmedReservation(...)`, `fakeGateway()`
- [X] T036 [P] Fichiers de traduction `functional/sales/resources/lang/fr/sales.php`, `history.php`, `refusals.php` (une clé par factory de refus)

**Checkpoint**: tests Unit des états et `SaleConstraintsTest` verts ; PHPStan vert.

---

## Phase 4: User Story 1 — Mettre une machine en vente et la proposer depuis n'importe quelle agence (P1) 🎯 MVP

**Goal**: ouvrir une vente unique par machine, visible et filtrable par toutes les agences, prix modifiable avec historique, mention « en vente » dans le parc et le planning.

**Independent Test**: mettre en vente une machine à 18 000 € HT depuis l'agence A ; l'agence B la voit ; une seconde mise en vente est refusée.

### Tests for User Story 1

- [X] T037 [P] [US1] `functional/sales/tests/Feature/ListMachineForSaleTest.php` : scénarios US1-1 (vente `listed`, visible avec prix, agence, statut au parc), US1-2 (refus avec prix, agence, date de la vente ouverte), US1-4 (changement de prix, ancien prix dans l'historique ; refusé sur une vente réservée), US1-5 (refus après vente conclue), FR-001 (machine en panne, à l'atelier ou déjà retirée acceptée ; prix nul refusé, montant mal formé refusé par `InvalidMoneyException`)
- [X] T038 [P] [US1] `functional/sales/tests/Feature/ConcurrentListingTest.php` : US1-3, deux mises en vente simultanées → une seule vente, l'autre reçoit `MachineAlreadyForSaleException` (violation `23505` traduite)
- [X] T039 [P] [US1] `functional/sales/tests/Feature/SaleBadgesTest.php` : US1-6, badge « En vente » dans le parc et le planning ; la machine reste réservable en location (location du 10 au 14 acceptée) ; les écrans du parc et du planning écoutent `echo-private:sales,.sale.changed` (FR-025)
- [X] T040 [P] [US1] `functional/sales/tests/Feature/SaleListScreenTest.php` : liste filtrable par catégorie, agence, statut ; accès refusé sans `sales.manage` ; rafraîchissement sur `sale.changed`

### Implementation for User Story 1

- [X] T041 [US1] `functional/sales/src/Actions/ListMachineForSale.php` : transaction, verrou `machines`, création `listed`, `rescue()` qui traduit `23505` en `MachineAlreadyForSaleException::withOpenSale()` (texte : vente ouverte avec prix, agence, date) ou `MachineAlreadySoldException::for()` ; prix reçu en `Money`, refus si `! isPositive()`, `SaleHistory`, `SaleChanged`
- [X] T042 [US1] `functional/sales/src/Actions/UpdateSaleListing.php` : prix demandé modifiable en `listed` uniquement, descriptif en `listed` et `reserved` (sinon exception d'état), ancien et nouveau prix dans l'historique
- [X] T043 [P] [US1] `functional/sales/src/Badges/SaleMachineBadges.php` (une requête pour toutes les machines ; « En vente » / « Vendue sous réserve — remise le JJ/MM » ; lien `sales.show`) et enregistrement dans `MachineBadges` (E3) depuis `SalesServiceProvider`
- [X] T044 [US1] `functional/sales/src/Queries/SaleListQuery.php` (filtres statut, catégorie, agence ; eager loading) et `functional/sales/src/Livewire/SaleList.php` + `functional/sales/resources/views/livewire/sale-list.blade.php` (écoute `echo-private:sales,.sale.changed`)
- [X] T045 [US1] `functional/sales/src/Livewire/ListMachineForm.php` + vue `list-machine-form.blade.php` (recherche machine par référence, prix saisi converti par `Money::fromInput()`, descriptif, validation, `DisplaysRefusals`) ; routes `sales.index`, `sales.create` dans `functional/sales/routes/web.php` (`auth`, `verified`, `can:sales.manage`, préfixe `ventes`)
- [X] T046 [US1] `functional/sales/src/Livewire/SaleDetail.php` + vue `sale-detail.blade.php` (annonce, modification du prix et du descriptif, historique) et route `sales.show` ; entrée « Ventes d'occasion » dans `resources/views/layouts/app/sidebar.blade.php` (visible avec `sales.manage`)

**Checkpoint**: US1 testable seule ; MVP démontrable.

---

## Phase 5: User Story 2 — Enregistrer les offres et réserver la machine pour un acheteur (P1)

**Goal**: offres multiples, acheteur client existant ou nouveau, acceptation avec date de remise sans conflit de location, blocage des locations au-delà de la remise, levée de la réservation.

**Independent Test**: deux offres, accepter 16 500 € avec remise le 20/11 → vente réservée, autre offre refusée, location du 18 au 22/11 refusée.

### Tests for User Story 2

- [X] T047 [P] [US2] `functional/sales/tests/Feature/RecordOfferTest.php` : US2-1 (offre client existant, auteur, date), US2-2 (création du client au moment de l'offre), US2-7 (offre refusée sur une vente réservée), montant `<= 0` refusé, offre retirée ou refusée manuellement (FR-012)
- [X] T048 [P] [US2] `functional/sales/tests/Feature/AcceptOfferTest.php` : US2-3 (vente réservée, prix = offre, autres offres refusées), US2-4 (refus si location confirmée finit le 25/11 pour une remise le 20/11, message avec dates, agence, client ; refus aussi si elle finit le 20/11, jour de la remise), US2-9 (offre bien inférieure au prix acceptée, auteur tracé), date de remise dans le passé refusée, remise le 20/11 compatible avec une location finissant le 19/11
- [X] T049 [P] [US2] `functional/sales/tests/Feature/ReservedSaleBlocksRentalsTest.php` : US2-5 (location 18–22/11 refusée, message avec date de remise ; machine absente de la recherche de disponibilité sur cette période), US2-6 (location 10–14/11 acceptée), FR-008a (report de la date de remise refusé si une location finit après la nouvelle date ; accepté sinon)
- [X] T050 [P] [US2] `functional/sales/tests/Feature/ConcurrentAcceptanceTest.php` : deux acceptations simultanées → une seule ; acceptation et création de location simultanées → l'une des deux est refusée, jamais les deux enregistrées en conflit
- [X] T051 [P] [US2] `functional/sales/tests/Feature/ReleaseSaleReservationTest.php` : US2-8 (retour `listed`, offre acceptée `withdrawn`, acheteur / prix final / date effacés, location au-delà de l'ancienne date de nouveau acceptée), motif obligatoire

### Implementation for User Story 2

- [X] T052 [US2] `functional/sales/src/Actions/RecordOffer.php` (vente `listed` uniquement ; client existant ou nouveau `Customer` créé dans la même transaction ; `offered_on` pas dans le futur) ; `RejectOffer.php` et `WithdrawOffer.php` (transitions d'offre, `decided_by`, `decided_at`)
- [X] T053 [US2] `functional/sales/src/Queries/HandoverConflicts.php` : réservations `confirmed` ou `in_progress` de la machine avec `end_date >= planned_handover_date` (une requête)
- [X] T054 [US2] `functional/sales/src/Actions/AcceptOffer.php` : transaction ; verrou `machines` puis `sales` ; date de remise `>= aujourd'hui` ; `HandoverConflicts` → `HandoverConflictsWithReservationException` ; vente `reserve()`, offre `accept()`, autres offres pending `reject()` en une requête ; `rescue()` traduit `23505` ; historique ; `SaleChanged`
- [X] T055 [US2] `functional/sales/src/Actions/ChangePlannedHandoverDate.php` (vente `reserved`, même verrou et même contrôle) et `functional/sales/src/Actions/ReleaseSaleReservation.php` (motif obligatoire, `release()`, offre acceptée `withdraw()`, champs de réservation effacés)
- [X] T056 [US2] `functional/sales/src/Guards/ReservedSaleReservationGuard.php` (E1) : `ensureCanReserve` lève `MachineReservedForSaleException` si une vente `reserved` a `planned_handover_date <= $endDate` ; `excludeUnavailable` ajoute le `whereNotExists` correspondant ; enregistrement dans `ReservationRequestGuards` depuis `SalesServiceProvider`
- [X] T057 [US2] `functional/sales/src/Livewire/SaleOffers.php` + vue `sale-offers.blade.php` intégrée à `sale-detail` : liste des offres, formulaire (client existant ou nouveau), accepter avec date de remise, refuser, retirer ; dans `SaleDetail` : changer la date de remise, lever la réservation (motif) ; refus via `DisplaysRefusals`

**Checkpoint**: US1 + US2 vertes ; une location ne peut plus chevaucher une remise.

---

## Phase 6: User Story 3 — Conclure la vente et faire sortir la machine du parc (P1)

**Goal**: remise qui passe la vente à « vendue », retire la machine, crée la transmission, et fige la vente.

**Independent Test**: remise d'une vente réservée → vendue, machine retirée et absente de la recherche, transmission créée avec acheteur, machine, prix.

### Tests for User Story 3

- [X] T058 [P] [US3] `functional/sales/tests/Feature/HandOverSaleTest.php` : US3-1 (vendue, date et prix final, machine `retired`), US3-2 (refus si machine sortie), US3-3 (refus si location confirmée, liste des réservations), US3-4 (machine absente de la recherche et non réservable), US3-5 (une transmission `used_machine_sale` en attente, ligne conforme à `contracts/billing-sale-line.md`), FR-013 (remise avant et après la date prévue acceptée), machine déjà retirée : statut inchangé, refus en cascade : ni vente conclue ni transmission si `RetireMachine` refuse
- [X] T059 [P] [US3] `functional/sales/tests/Feature/SoldSaleIsFrozenTest.php` : US3-6 (prix et acheteur non modifiables, message avoir), annulation refusée, aucune nouvelle offre
- [X] T060 [P] [US3] `functional/sales/tests/Feature/OpenSaleBlocksRetirementTest.php` : FR-016, retrait manuel d'une machine `listed` ou `reserved` refusé ; après annulation, retrait accepté ; le garde des réservations de booking s'applique toujours

### Implementation for User Story 3

- [X] T061 [US3] `functional/sales/src/Guards/OpenSaleRetirementGuard.php` (E2) + `MachineHasOpenSaleException` ; enregistrement dans `MachineRetirementGuards` depuis `SalesServiceProvider`
- [X] T062 [US3] `functional/sales/src/Billing/SaleLine.php` (implémente `BillableLine`) et `functional/sales/src/Billing/SaleBillableSource.php` (E4) : `type()` = `UsedMachineSale` ; `line()` renvoie une `SaleLine` selon `contracts/billing-sale-line.md` (identifiant client via `CustomerBillingAccount`) ; `subject()` (« Vente {référence} », acheteur, `route('sales.show')`, la vente comme sujet d'historique) ; enregistrement dans `BillableSources`
- [X] T063 [US3] `functional/sales/src/Actions/HandOverSale.php` selon « Déroulé de la remise » de `plan.md` : verrous `machines` puis `sales`, `sell()`, refus si réservation `in_progress` ou `confirmed` (`SaleHandoverRefusedException`), champs de remise, `RetireMachine` si la machine n'est pas déjà retirée, `QueueSourceTransmission`, historique, `SaleChanged`
- [X] T064 [US3] Bouton « Enregistrer la remise » et état figé dans `functional/sales/src/Livewire/SaleDetail.php` et sa vue (prix final, date de remise, état de transmission)

**Checkpoint**: le cycle complet mise en vente → offre → réservation → remise fonctionne.

---

## Phase 7: User Story 4 — Ne perdre aucune vente transmise (P1)

**Goal**: la vente bénéficie de toutes les garanties de 003.

**Independent Test**: logiciel injoignable, remise → « en attente » ; logiciel rétabli → transmise une seule fois.

### Tests for User Story 4

- [ ] T065 [P] [US4] `functional/sales/tests/Feature/SaleTransmissionReliabilityTest.php` (faux logiciel) : US4-1 (remise enregistrée, transmission en attente), US4-2 (`billing:reconcile` la transmet sans action), US4-3 (acheteur sans identifiant → échec « client inconnu » visible dans la liste des transmissions avec lien vers la vente), US4-4 (double envoi → une seule réception, même clé d'idempotence), US4-5 (export de secours : la vente y figure, passe « transmise par export »), FR-021 (la remise ne dépend pas du logiciel)

### Implementation for User Story 4

- [ ] T066 [US4] Combler les écarts révélés par T065 dans `SaleBillableSource` et l'affichage de l'état de transmission de `SaleDetail` (aucune logique de relance dans sales : elle reste dans billing)

**Checkpoint**: zéro vente perdue ni dupliquée dans les scénarios de panne.

---

## Phase 8: User Story 5 — Annuler une vente et suivre l'historique des ventes (P2)

**Goal**: annulation avec motif, remise en vente, liste des ventes avec total et retards, historique par machine.

**Independent Test**: annuler une vente réservée → machine de nouveau réservable, historique de la machine avec la vente annulée.

### Tests for User Story 5

- [ ] T067 [P] [US5] `functional/sales/tests/Feature/CancelSaleTest.php` : US5-1 (annulée, machine au parc, location 18–22/11 acceptée), US5-2 (motif obligatoire), US5-3 (vente vendue non annulable), US5-4 (remise en vente après annulation, ancienne vente conservée), offres pending → rejected et acceptée → withdrawn
- [ ] T068 [P] [US5] `functional/sales/tests/Feature/SaleListStatementTest.php` : US5-5 (liste filtrée par agence et période avec machine, statut, prix, acheteur, état de transmission, total HT des ventes conclues calculé en base), FR-023 (vente réservée à date de remise dépassée mise en évidence, avec `travelTo()`)
- [ ] T069 [P] [US5] `functional/sales/tests/Feature/SaleHistoryTest.php` : FR-024, chaque action (mise en vente, prix, offre, acceptation, refus, retrait, date de remise, levée, remise, annulation, transmission) avec auteur, agence et date ; page historique de la machine
- [ ] T070 [P] [US5] `functional/sales/tests/Feature/SaleBroadcastingTest.php` : FR-025, `SaleChanged` diffusé après commit sur `private-sales` avec la charge utile explicite pour ouverture, réservation, conclusion, annulation

### Implementation for User Story 5

- [ ] T071 [US5] `functional/sales/src/Actions/CancelSale.php` (motif obligatoire, `cancel()`, offres pending rejetées et acceptée retirée en requêtes groupées, historique, `SaleChanged`) ; bouton et modale dans `SaleDetail`
- [ ] T072 [US5] Compléter `SaleListQuery` et `SaleList` : filtre de période (mise en vente ou remise), colonne état de transmission (chargée par lot depuis `transmissions` par `source_type`/`source_id`), total `SUM(final_price_cents)` en base restitué par `Money::fromStored()`, mise en évidence des ventes en retard
- [ ] T073 [US5] `functional/sales/src/Livewire/MachineSaleHistory.php` + vue `machine-sale-history.blade.php` et route `sales.machine-history` : toutes les ventes de la machine avec offres et activités (chargement groupé)

**Checkpoint**: toutes les user stories vertes et indépendantes.

---

## Phase 9: Polish & Cross-Cutting Concerns

- [ ] T074 [P] `functional/sales/database/seeders/SalesDemoSeeder.php` (quelques ventes à différents états pour la démo), appelé depuis `DatabaseSeeder` en environnement local uniquement
- [ ] T075 [P] Vérifier qu'aucun fichier de `functional/sales` ne dépasse 200 lignes, sans commentaire de code ni nom générique ; tous les textes affichés passent par `sales::`
- [ ] T076 Lancer la suite complète, `vendor/bin/phpstan analyse` (zéro erreur) et `vendor/bin/pint --dirty --format agent`
- [ ] T077 Dérouler `specs/007-vente-occasion/quickstart.md` (validation manuelle avec le faux logiciel) et consigner les écarts

---

## Dependencies & Execution Order

- **Phase 1 (Setup)** : aucune dépendance (après les prérequis).
- **Phase 2 (E1–E4)** : indépendante de la Phase 1 ; ses quatre sous-blocs sont indépendants entre eux ; E1 et E2 attendent la remise à jour de la branche sur les corrections de 001 et 003. Bloque : E3 → T043 (US1), E1 → T056 (US2), E2 → T061 (US3), E4 → T062 (US3) et US4. 
- **Phase 3 (Foundational)** : après la Phase 1 ; bloque toutes les user stories.
- **US1** : après Phase 3 (+ E3 pour T043).
- **US2** : après US1 (une offre porte sur une vente ouverte par US1) + E1.
- **US3** : après US2 (la remise part d'une vente réservée) + E2 + E4.
- **US4** : après US3.
- **US5** : après US1 pour l'annulation d'une vente `listed` ; ses scénarios sur les ventes réservées et vendues demandent US2 et US3.
- **Polish** : en dernier.

Dans chaque phase : tests d'abord (rouges), puis modèles et actions, puis écrans ; commit à la fin de chaque phase validée (tests verts, PHPStan vert, Pint).

## Parallel Execution Examples

- Phase 2 : E1 (T008–T010), E2 (T011–T013), E3 (T014–T016), E4 (T017–T023) en parallèle ; dans E4, T017, T018, T020, T021 en parallèle avant T019 → T022 → T023.
- Phase 3 : T024, T025, T026 (tests) puis T029, T030, T032, T033, T034, T035, T036 en parallèle après T027–T028.
- US1 : T037, T038, T039, T040 en parallèle ; T043 en parallèle de T041–T042.
- US2 : T047 à T051 en parallèle.
- US5 : T067 à T070 en parallèle.

## Implementation Strategy

1. **MVP** : Phases 1, 2 (au moins E3), 3 et US1 : les ventes sont visibles de toutes les agences et une machine ne peut plus être proposée deux fois.
2. **Incrément 2** : US2 : plus aucune location ne chevauche une vente promise.
3. **Incrément 3** : US3 + US4 : la vente sort la machine du parc et arrive dans le logiciel de facturation sans perte.
4. **Incrément 4** : US5 : annulation, suivi et historique.

Chaque incrément est démontrable seul et laisse les suites 001 à 003 vertes.
