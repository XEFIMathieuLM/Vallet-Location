---

description: "Task list for feature 003-transmission-facturation"
---

# Tasks: Transmission des locations et des réparations au logiciel de facturation

**Input**: Design documents from `specs/003-transmission-facturation/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/billing-gateway.md), [quickstart.md](quickstart.md)

**Tests**: Inclus, comme pour la 001 et la 002 : un test Feature PHPUnit par scénario d'acceptation. Dans chaque story, écrire les tests d'abord et vérifier qu'ils échouent.

**Organization**: Tâches groupées par user story ; chaque story est testable indépendamment une fois la phase 2 terminée.

## ⚠️ Prérequis : features 001 et 002

Cette feature s'appuie sur :

- la 001 **jusqu'à sa phase 5 incluse** : `Reservation` avec `departed_at` / `returned_at`, `ReturnReservation`, `ReservationChanged`, `ReservationDetail`, ainsi que les points d'extension de `booking` (contrôles de sortie et de retour, registre `ReservationDetailSections` des sections du détail), déplacés de la 002 (ses anciennes T007 à T012) vers la 001 ;
- la 002 **jusqu'à sa phase 5 incluse** : `Damage`, `ResolveDamage`, le registre `DamageActions` et la vue partielle `inspection::partials.damage-actions` (sa T052), les écrans « Comparaison » (`Comparison`, sa T053) et « Dégâts à traiter » (`DamagesList`, sa T054).

Avant T001 :

1. la 001 et la 002 sont implémentées jusqu'à ces phases et commitées ;
2. la branche `003-transmission-facturation` est mise à jour par-dessus ;
3. `docker compose exec -u sail laravel.test php artisan test` passe sur la branche mise à jour.

Le registre des actions d'un dégât est livré par la 002 (sa T052, commit `d0f2213` sur `002-photos-qr-code`) : la 003 ne modifie plus `inspection`. T050 se limite à vérifier ce registre.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US4)

## Conventions pour toutes les tâches

Celles de la 001 s'appliquent sans changement ([tasks.md de la 001](../001-reservation-machines/tasks.md#conventions-pour-toutes-les-tâches)) : commandes via `docker compose exec -u sail laravel.test …` (CLAUDE.md), code en anglais, textes dans `functional/billing/resources/lang/fr/*.php`, statuts en `string` castés en enum, pas de cascade, pas d'observer, pas de `try/catch` (utiliser `rescue()` + rethrow de tout ce qui n'est pas l'exception de domaine attendue), pas de commentaire, factories avec `faker()`, contrôles par permission, fichiers de code < 200 lignes, agrégats calculés en base. En plus :

- `billing` dépend d'`inspection`, `booking` et `fleet` ; **aucun fichier d'`inspection`, `booking` ni `fleet` n'importe une classe de `billing`**.
- `Reservation`, `Customer` et `Damage` ne déclarent aucune relation vers les tables de `billing`.
- Les dates de période se calculent en heure de Paris à partir de `departed_at` et `returned_at`, **jamais** à partir de `end_date`.
- Montants en centimes entiers (`amount_cents`), jamais en flottant.
- Les tests manipulent l'horloge avec `$this->travelTo()` et le logiciel de facturation avec `FakeBillingGateway`, jamais un vrai appel réseau.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Layer `billing`, configuration, disque d'export, permission.

- [X] T001 Créer le layer OSDD `functional/billing/` (`composer.json` LayerManifest déclarant la dépendance à `inspection`, `booking` et `fleet`, namespace PSR-4, service provider, dossiers `src/`, `config/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `resources/views/`, `resources/lang/fr/`, `routes/`, `tests/Feature/`, `tests/Unit/`) ; déclarer `functional/billing` dans le `require` du `composer.json` racine (dépôt `path` `./functional/*`) et dans l'`autoload-dev` (`Functional\\Billing\\Tests\\`), mettre à jour `composer.lock` ; ajouter `functional/billing/src` à `phpstan.neon` et à la `<source>` de `phpunit.xml` (les suites Unit et Feature couvrent déjà `functional/*/tests`) ; enregistrer sa suite de tests dans `phpunit.xml`
- [X] T002 [P] Créer `functional/billing/config/billing.php` avec les clés de [data-model.md](data-model.md#configuration-functionalbillingconfigbillingphp) : `go_live_date` (lue depuis `BILLING_GO_LIVE_DATE`, date au format AAAA-MM-JJ, sans valeur par défaut ; elle n'est lue que par `BillingGoLive::date()` dans `functional/billing/src/Support/BillingGoLive.php`, qui lève `MissingGoLiveDateException` si elle est vide ou invalide : sans date, aucune période n'est créée ni transmise et les commandes `billing:*` échouent avec un message explicite), `gateway` (`BILLING_GATEWAY`, défaut `fake`), `http_timeout_seconds` = 10, `retry_delays_minutes` = `[1, 5, 15, 60]`, `alert_after_hours` = 24, `damage_overdue_days` = 7, `export_disk` = `billing-exports` ; la charger depuis le service provider du layer
- [X] T003 [P] Ajouter le disque privé `billing-exports` (driver `local`, racine `storage/app/private/billing-exports`) dans `config/filesystems.php`, et `BILLING_GATEWAY=fake`, `BILLING_GO_LIVE_DATE=` dans `.env.example` (vide volontairement : la valeur se fixe au déploiement ; sans elle, rien n'est transmis, voir T002)
- [X] T004 [P] Ajouter la permission `billing.manage` au rôle `PermissionSeeder::EMPLOYEE_ROLE` dans `functional/billing/database/seeders/BillingPermissionSeeder.php` (même forme que `InspectionPermissionSeeder`), appelé depuis `database/seeders/DatabaseSeeder.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Tables, modèles, cycle de vie d'une transmission, port vers le logiciel de facturation, envoi. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

### Enums et tables

- [X] T005 [P] Créer les enums backed `BillablePeriodKind` (`intermediate`, `final`), `TransmissionStatus` (`pending`, `sent`, `exported`, `failed`), `TransmissionFailureReason` (`customer_unknown`, `rejected`), `DamageOutcome` (`billed`, `waived`) dans `functional/billing/src/Enums/`, avec libellés traduits dans `functional/billing/resources/lang/fr/enums.php`
- [X] T006 Créer la migration `billable_periods` dans `functional/billing/database/migrations/` : `reservation_id` (`->constrained()` sans cascade), `kind` string, `start_date` date, `end_date` date, `days` entier, timestamps ; CHECK `end_date >= start_date` et `days = end_date - start_date + 1` ; contrainte d'exclusion `EXCLUDE USING gist (reservation_id WITH =, daterange(start_date, end_date, '[]') WITH &&)` ; index unique partiel « au plus une période `final` par réservation » (`WHERE kind = 'final'`)
- [X] T007 [P] Créer la migration `customer_billing_accounts` dans `functional/billing/database/migrations/` : `customer_id` « obligatoire, unique », `external_ref` « obligatoire », timestamps
- [X] T008 [P] Créer la migration `billing_exports` dans `functional/billing/database/migrations/` : `created_by` → users « obligatoire », `line_count` entier CHECK `>= 1`, `file_path` string, timestamps
- [X] T009 [P] Créer la migration `damage_settlements` dans `functional/billing/database/migrations/` : `damage_id` « obligatoire, unique », `outcome` string, `amount_cents` entier nullable, `label` texte nullable, `waiver_reason` texte nullable, `settled_by` → users, `settled_at` date-heure ; CHECK « `amount_cents` obligatoire et > 0 si `billed`, vide si `waived` », « `label` obligatoire si `billed` », « `waiver_reason` obligatoire si `waived` »
- [X] T010 Créer la migration `transmissions` dans `functional/billing/database/migrations/` : `uuid` unique, `billable_period_id` nullable unique, `damage_settlement_id` nullable unique, `reservation_id` obligatoire, `status` string défaut `pending`, `attempts` entier défaut 0, `next_attempt_at`, `last_attempt_at`, `sent_at` date-heure nullable, `failure_reason` string nullable, `last_error` texte nullable, `external_ref` string nullable, `billing_export_id` nullable ; CHECK « exactement un de `billable_period_id` et `damage_settlement_id` est renseigné » ; index sur (`status`, `next_attempt_at`) (depends on T006, T008, T009)

### Modèles

- [X] T011 [P] Créer le modèle `BillablePeriod` dans `functional/billing/src/Models/BillablePeriod.php` (casts `kind` → `BillablePeriodKind`, dates ; relations `reservation()`, `transmission()`) et sa factory dans `functional/billing/database/factories/BillablePeriodFactory.php`
- [X] T012 [P] Créer le modèle `CustomerBillingAccount` dans `functional/billing/src/Models/CustomerBillingAccount.php` (relation `customer()`) et sa factory
- [X] T013 [P] Créer le modèle `BillingExport` dans `functional/billing/src/Models/BillingExport.php` (relations `creator()`, `transmissions()`) et sa factory
- [X] T014 [P] Créer le modèle `DamageSettlement` dans `functional/billing/src/Models/DamageSettlement.php` (casts `outcome` → `DamageOutcome` ; relations `damage()`, `settler()`, `transmission()`) et sa factory (états `billed` et `waived`)
- [X] T015 Créer le modèle `Transmission` dans `functional/billing/src/Models/Transmission.php` (casts `status` → `TransmissionStatus`, `failure_reason` → `TransmissionFailureReason`, dates ; `uuid` généré à la création ; relations `billablePeriod()`, `damageSettlement()`, `reservation()`, `export()` ; accesseur `state()` qui renvoie la classe d'état) et sa factory (un état par statut) (depends on T011, T013, T014)

### Cycle de vie d'une transmission (pattern State)

Lire `design-patterns:state` avant T016.

- [X] T016 [P] Écrire les tests Unit des transitions dans `functional/billing/tests/Unit/TransmissionStateTest.php` : `pending → sent`, `pending → failed`, `failed → pending`, `pending → exported`, `failed → exported` autorisées ; toute transition depuis `sent` ou `exported`, et `failed → sent`, lèvent `IllegalTransmissionTransitionException`
- [X] T017 Créer `IllegalTransmissionTransitionException` dans `functional/billing/src/Exceptions/` et une classe d'état par statut dans `functional/billing/src/States/` (`PendingTransmission`, `SentTransmission`, `ExportedTransmission`, `FailedTransmission`, sur le modèle des états de réservation de la 001) exposant les transitions `send()`, `fail()`, `requeue()`, `export()` et `canBeSent()`, `canBeExported()`, `canBeRetried()` ; les écritures en base (état, référence, motif, échéance de relance, export) et l'historique passent par `functional/billing/src/Support/TransmissionLifecycle.php` ; faire passer T016 (depends on T015, T016)

### Port vers le logiciel de facturation

- [X] T018 [P] Créer le value object `BillableLine` dans `functional/billing/src/ValueObjects/BillableLine.php` avec exactement les champs de [contracts/billing-gateway.md](contracts/billing-gateway.md#ce-qui-est-envoyé--une-ligne-facturable), et les exceptions `BillingSoftwareRejectedException` (porte un motif lisible) et `BillingSoftwareUnreachableException` dans `functional/billing/src/Exceptions/`
- [X] T019 Créer l'interface `BillingGateway` dans `functional/billing/src/Contracts/BillingGateway.php` (`send(BillableLine $line): string` qui renvoie l'identifiant du logiciel ; docblock : lève `BillingSoftwareRejectedException` ou `BillingSoftwareUnreachableException`) (depends on T018)
- [X] T020 Créer `FakeBillingGateway` dans `functional/billing/src/Gateways/FakeBillingGateway.php` : mode `accept` / `unreachable`, refus programmable par clé d'idempotence avec un motif, renvoie le même identifiant pour une clé déjà reçue sans ajouter de ligne ; mode et lignes reçues **stockés dans le cache de l'application** (partagés entre worker et console, store `array` en tests), jamais en propriété d'instance ; créer la commande `billing:fake-gateway {mode?} {--received}` dans `functional/billing/src/Console/FakeGatewayCommand.php`, qui refuse de s'exécuter hors des environnements `local` et `testing` ; lier `BillingGateway` à l'implémentation choisie par `config('billing.gateway')` dans le service provider (depends on T019)
- [X] T021 Implémenter l'action `MakeBillableLine` dans `functional/billing/src/Actions/MakeBillableLine.php` : construit la `BillableLine` d'une `Transmission` (période ou dégât) selon le tableau du contrat ; `customer_ref` lu sur `CustomerBillingAccount`, `null` s'il n'existe pas (depends on T018, T015)

### Envoi

- [X] T022 Écrire le test Feature `functional/billing/tests/Feature/SendTransmissionTest.php` : accepté → `sent`, `external_ref` et `sent_at` renseignés ; la ligne reçue par le faux logiciel pour une période comporte, champ par champ, les valeurs attendues de [contracts/billing-gateway.md](contracts/billing-gateway.md#ce-qui-est-envoyé--une-ligne-facturable) (`customer_ref`, `reservation_ref`, `machine_reference`, `machine_category`, `home_agency`, `booking_agency`, `period_start`, `period_end`, `period_kind`, `days`, aucun montant) ; idem pour un dégât (`damage_view`, `damage_comment`, `label`, `amount_excl_tax_cents`, aucun champ de période) ; client sans `CustomerBillingAccount` → `failed` / `customer_unknown` sans appel au logiciel ; refusé → `failed` / `rejected` avec le motif dans `last_error` ; injoignable → reste `pending`, `attempts` + 1, `next_attempt_at` = maintenant + 1 min puis 5, 15, 60, puis 60 ; une transmission `sent` ou `exported` n'est jamais renvoyée ; deux envois de la même transmission n'appellent le faux logiciel qu'une fois
- [X] T023 Implémenter l'action `SendTransmission` dans `functional/billing/src/Actions/SendTransmission.php` : transaction, `lockForUpdate()` sur la transmission, sortie immédiate si `! state()->canBeSent()` ; client inconnu → `markFailed(customer_unknown)` ; appel `BillingGateway::send()` via `rescue()` : `BillingSoftwareRejectedException` → `markFailed(rejected)`, `BillingSoftwareUnreachableException` → `attempts` + 1 et `next_attempt_at` selon `billing.retry_delays_minutes`, toute autre exception relancée ; `last_attempt_at` renseignée à chaque tentative ; faire passer T022 (depends on T017, T020, T021, T022)
- [X] T024 Créer le job `SendTransmissionJob` dans `functional/billing/src/Jobs/SendTransmissionJob.php` (`ShouldQueue`, `ShouldBeUnique` par id de transmission, `afterCommit`) qui appelle `SendTransmission` (depends on T023)
- [X] T025 [P] Créer `functional/billing/src/Support/BillingHistory.php` qui écrit dans l'historique de la réservation avec `activity('billing')->performedOn($reservation)->causedBy($user)` (`spatie/laravel-activitylog` v5, comme `Reservation`) : création d'une période, tentative, échec, relance manuelle, export, chiffrage, classement « non refacturé » ; sans utilisateur (job, commande), causer vide = « système » ; les actions de `billing` l'appellent explicitement (depends on T011, T014, T015)
- [X] T026 [P] Créer les contrôles `TransmissionControl`, `DamageSettlementControl`, `BillingExportControl` dans `functional/billing/src/Access/Controls/` (`lomkit/laravel-access-control`), tous fondés sur la permission `billing.manage` ; `DamageSettlementControl` porte l'action « régler un dégât » (refacturer ou ne pas refacturer), l'accès aux écrans hôtes de la 002 restant régi par `damages.manage` (depends on T015)

**Checkpoint** : `docker compose exec -u sail laravel.test php artisan test functional/billing/tests` passe ; une transmission créée à la main part dans le faux logiciel.

---

## Phase 3: User Story 1 - Transmettre automatiquement chaque location (Priority: P1) 🎯 MVP

**Goal**: chaque location est découpée en périodes (une par mois écoulé, puis une finale au retour) et chaque période est transmise sans action du salarié.

**Independent Test**: enregistrer le retour d'une réservation en cours : une période finale avec les bonnes dates et le bon nombre de jours est transmise. Simuler une fin de mois avec une réservation en cours : la période écoulée est transmise.

### Tests for User Story 1

- [X] T027 [P] [US1] Écrire `functional/billing/tests/Feature/FinalPeriodTest.php` : scénarios 1 à 4 et 8 de l'US1 (sortie le 10, retour le 14 → 5 jours ; retour anticipé le 12 → 3 jours ; retour en retard le 17 → 8 jours ; réservation annulée → rien ; second déclenchement → aucun doublon) ; réservation rendue la veille de `billing.go_live_date` → aucune période ; réservation rendue le jour même de `billing.go_live_date` → transmise ; réservation sortie avant `billing.go_live_date` et rendue après → transmise en entier depuis sa date de sortie
- [X] T028 [P] [US1] Écrire `functional/billing/tests/Feature/MonthEndPeriodsTest.php` : scénarios 5 à 7 de l'US1 (sortie le 20 novembre, `billing:close-months` le 1er décembre → période `intermediate` du 20 au 30, 11 jours ; retour le 5 décembre → période `final` du 1er au 5 ; retour le 30 novembre → une seule période `final`) ; location sur 3 mois → 2 périodes intermédiaires + 1 finale ; relancer la commande ne crée rien ; la commande lancée le 3 du mois rattrape le mois précédent ; réservation sortie le 15 août, `billing.go_live_date` au 1er octobre, commande lancée le 1er octobre → périodes intermédiaires du 15 au 31 août et du 1er au 30 septembre ; même réservation, commande lancée le 1er septembre (avant `billing.go_live_date`) → aucune période ; `billing.go_live_date` vide → la commande échoue avec `MissingGoLiveDateException` et aucune période n'est créée ; la transmission du retour ne reprend pas les jours d'une période intermédiaire en échec
- [X] T029 [P] [US1] Écrire `functional/billing/tests/Unit/PeriodSplitTest.php` : pour des dates de sortie et de retour variées (même jour, fin de mois, fin d'année, 29 février), la somme des `days` est égale à la durée réelle, bornes incluses, et aucune période ne se chevauche

### Implementation for User Story 1

- [X] T030 [US1] Implémenter l'action `RecordFinalPeriod` dans `functional/billing/src/Actions/RecordFinalPeriod.php` : ne fait rien si la réservation n'est pas `closed`, si la date de retour (date de `returned_at` en Europe/Paris) est antérieure à `billing.go_live_date` ou si une période `final` existe ; début = lendemain de la dernière `end_date` ou date de sortie (Europe/Paris), fin = date de `returned_at` (Europe/Paris) ; crée la période et sa `Transmission` dans une transaction ; dispatche `SendTransmissionJob` (depends on T024)
- [X] T031 [US1] Implémenter l'action `RecordMonthEndPeriods` dans `functional/billing/src/Actions/RecordMonthEndPeriods.php` : ne fait rien tant que la date du jour (Europe/Paris) est antérieure à `billing.go_live_date` ; sinon, pour une réservation `in_progress`, quelle que soit sa date de sortie, crée une période `intermediate` par mois entièrement écoulé non couvert (fin = dernier jour du mois, uniquement si ce jour est passé), chacune avec sa `Transmission`, dans une transaction ; dispatche les jobs (depends on T024)
- [X] T032 [US1] Créer le listener `RecordFinalPeriodOnReservationClosed` dans `functional/billing/src/Listeners/RecordFinalPeriodOnReservationClosed.php` (`ShouldQueue`, `afterCommit`) écoutant `ReservationChanged` : appelle `RecordFinalPeriod` (idempotent : l'événement est aussi émis par `RefreshReservationConflicts`) ; sans `billing.go_live_date` configurée, il ne fait rien (le retour n'est jamais bloqué, FR-006 ; `billing:close-months` et `billing:reconcile` échouent, elles, avec `MissingGoLiveDateException`) ; `phpunit.xml` laisse `BILLING_GO_LIVE_DATE` vide pour que les tests des autres layers ne déclenchent pas `billing` ; l'enregistrer dans le service provider de `billing` (depends on T030)
- [X] T033 [US1] Créer la commande `billing:close-months` dans `functional/billing/src/Console/CloseMonthsCommand.php` qui ne fait rien tant que la date du jour (Europe/Paris) est antérieure à `billing.go_live_date`, puis appelle `RecordMonthEndPeriods` pour chaque réservation `in_progress` (par lots), et la planifier chaque jour à 00:15 `Europe/Paris` (depends on T031)
- [X] T034 [US1] Créer le composant Livewire `ReservationBillingSection` dans `functional/billing/src/Livewire/ReservationBillingSection.php` et sa vue : périodes (dates, jours, type), état et date de chaque transmission ; l'enregistrer comme composant Livewire `billing.reservation-section` et dans `Functional\Booking\Extensions\ReservationDetailSections` (position 20, après les photos) depuis le service provider de `billing` ; il reçoit `$reservation` (FR-017) (depends on T015)
- [X] T035 [P] [US1] Ajouter les textes de l'US1 dans `functional/billing/resources/lang/fr/periods.php` et `functional/billing/resources/lang/fr/transmissions.php`

**Checkpoint** : US1 testable seule ; T027 à T029 passent.

---

## Phase 4: User Story 2 - Ne perdre aucune transmission en cas d'échec (Priority: P1)

**Goal**: relance automatique, liste des échecs avec motif, correction de la référence client, alerte, export de secours.

**Independent Test**: logiciel indisponible, clôturer une réservation → « en attente » ; logiciel rétabli → transmise sans action. Logiciel toujours indisponible, produire l'export → la location y figure et n'est plus relancée.

### Tests for User Story 2

- [X] T036 [P] [US2] Écrire `functional/billing/tests/Feature/TransmissionRecoveryTest.php` : scénarios 1 et 2 (retour enregistré malgré le logiciel injoignable ; `billing:reconcile` après rétablissement → transmise) ; une réservation `closed` sans période finale (listener perdu) est rattrapée par `billing:reconcile` ; une transmission dont `next_attempt_at` est future n'est pas renvoyée
- [X] T037 [P] [US2] Écrire `functional/billing/tests/Feature/FailedTransmissionsTest.php` : scénarios 3 et 4 (client inconnu → listé avec motif ; référence renseignée puis relance → transmise et sort de la liste) ; relancer une transmission `sent` est refusé ; un utilisateur sans `billing.manage` n'accède pas à l'écran
- [X] T038 [P] [US2] Écrire `functional/billing/tests/Feature/BillingAlertTest.php` : scénario 5 (transmission `pending` créée il y a plus de 24 h → bandeau visible avec le nombre ; moins de 24 h → pas de bandeau) ; une transmission `failed` déclenche le bandeau immédiatement
- [X] T039 [P] [US2] Écrire `functional/billing/tests/Feature/EmergencyExportTest.php` : scénarios 6 et 7 (export de 2 `pending` + 1 `failed`, la `sent` exclue ; toutes passent `exported` avec `billing_export_id` ; `billing:reconcile` ne les renvoie pas ; second export → `NothingToExportException`) ; contenu du fichier conforme à [contracts/export-format.md](contracts/export-format.md) (en-tête, séparateur `;`, BOM, montant `450,00`)

### Implementation for User Story 2

- [X] T040 [US2] Créer la commande `billing:reconcile` dans `functional/billing/src/Console/ReconcileCommand.php` : appelle `RecordFinalPeriod` pour chaque réservation `closed` dont la date de retour (Europe/Paris) est postérieure ou égale à `billing.go_live_date` et sans période finale, puis dispatche `SendTransmissionJob` pour chaque transmission `pending` dont `next_attempt_at` est vide ou passée ; la planifier **chaque minute** avec `withoutOverlapping()` (les premiers délais de relance sont d'une minute) (depends on T030, T024)
- [X] T041 [US2] Implémenter les actions `RetryTransmission` (« `failed → pending` », `attempts` conservé, `next_attempt_at` = maintenant, dispatche le job) et `SetCustomerBillingRef` (crée ou met à jour le `CustomerBillingAccount`) dans `functional/billing/src/Actions/` (depends on T024, T012)
- [X] T042 [US2] Créer l'écran Livewire `Transmissions` (`/facturation/transmissions`) dans `functional/billing/src/Livewire/Transmissions.php` et sa vue selon [contracts/screens.md](contracts/screens.md) : transmissions `failed` et `pending` créées depuis plus de `billing.alert_after_hours` (réservation, client, type, date, motif) ; saisie de la référence client ; bouton « Relancer » ; lien vers la réservation (depends on T041)
- [X] T043 [US2] Implémenter l'action `CreateBillingExport` dans `functional/billing/src/Actions/CreateBillingExport.php` et `NothingToExportException` : transaction, `lockForUpdate()` sur les transmissions `pending` et `failed`, lève `NothingToExportException` s'il n'y en a aucune, écrit le CSV avec `spatie/simple-excel` sur le disque `billing.export_disk` (nom `export-facturation-AAAAMMJJ-HHMMSS.csv`), crée le `BillingExport`, passe chaque transmission `exported` ; aucune transmission ne change d'état si l'écriture échoue (depends on T017, T021)
- [X] T044 [US2] Créer l'écran Livewire `Exports` (`/facturation/exports`) dans `functional/billing/src/Livewire/Exports.php` et sa vue : bouton « Produire un export », liste des exports (date, auteur, nombre de lignes) et téléchargement via une route protégée par `billing.manage` (depends on T043)
- [X] T045 [US2] Créer le composant Livewire `BillingAlert` dans `functional/billing/src/Livewire/BillingAlert.php` (`wire:poll.60s`, compte les `failed` et les `pending` créées depuis plus de `billing.alert_after_hours`, lien vers `/facturation/transmissions`) et l'inclure dans `resources/views/layouts/app.blade.php`, en tête de `<flux:main>`, pour les utilisateurs ayant `billing.manage`
- [X] T046 [US2] Déclarer les routes `/facturation/*` dans `functional/billing/routes/web.php` (`Route::livewire`, middleware `auth`, `verified`, `can:billing.manage`, comme les layers existants) et ajouter le groupe de menu « Facturation » (Transmissions, Exports, Relevé) sous `@can('billing.manage')` dans `resources/views/layouts/app/sidebar.blade.php`
- [X] T047 [P] [US2] Ajouter les textes de l'US2 dans `functional/billing/resources/lang/fr/transmissions.php` et `functional/billing/resources/lang/fr/exports.php` (motifs d'échec en clair)

**Checkpoint** : US1 et US2 passent ; aucune transmission ne peut disparaître ni partir deux fois.

---

## Phase 5: User Story 3 - Chiffrer un dégât et le transmettre pour refacturation (Priority: P1)

**Goal**: chaque dégât signalé finit soit refacturé (montant transmis), soit non refacturé avec un motif.

**Independent Test**: sur une réservation clôturée et transmise, portant un dégât « bras rayé », saisir 450 € et valider : le faux logiciel reçoit une ligne de 45000 centimes liée à la même réservation, et le dégât passe « refacturé ».

### Tests for User Story 3

- [X] T048 [P] [US3] Écrire `functional/billing/tests/Feature/DamageBillingTest.php` : scénarios 1 à 5 de l'US3 (refacturé 450 € « remplacement capot » → transmis avec vue et commentaire ; location déjà transmise → seul le dégât part ; « usure normale » → non refacturé, rien transmis ; motif vide → refus ; modification d'un dégât réglé → refus) ; montant nul ou négatif → refus ; dégât déjà traité → `DamageAlreadySettledException` ; un dégât réglé n'apparaît plus dans `DamagesList` ; un dégât d'une réservation rendue avant `billing.go_live_date` peut être refacturé et transmis
- [X] T049 [P] [US3] Écrire `functional/billing/tests/Feature/DamageBillingActionsScreensTest.php` : avec `billing` installé, les **deux** écrans de la 002 qui rendent la vue partielle `inspection::partials.damage-actions` (« Dégâts à traiter » `/degats` et « Comparaison » `/reservations/{id}/photos`) affichent « Refacturer » et « Ne pas refacturer » et n'affichent plus « Marquer traité » ; un utilisateur ayant `damages.manage` sans `billing.manage` ne voit pas ces actions et ne peut pas régler le dégât ; refacturer depuis la comparaison produit le même `DamageSettlement` et la même transmission que depuis la liste

### Implementation for User Story 3

- [X] T050 [US3] Vérifier le registre livré par la 002 dans `functional/inspection/src/Support/DamageActions.php` : singleton lié dans le service provider d'`inspection`, API `register(string $livewireComponent, int $position)`, `all()` trié, `isEmpty()` ; composants rendus avec `:damage="$damage"` par `inspection::partials.damage-actions` sur « Dégâts à traiter » et « Comparaison » ; `ResolveDamage::handle(Damage, User)` publique, refusant un dégât déjà traité avec `DamageAlreadyResolvedException`. **Vérifié le 2026-10-10 sur `fb3e51a` : conforme.** Aucun code à écrire si c'est conforme ; sinon, signaler l'écart à la session de la 002 avant d'adapter T052
- [X] T051 [US3] Implémenter les actions `BillDamage` (montant HT en centimes « obligatoire et > 0 », libellé « obligatoire ») et `WaiveDamage` (motif « obligatoire ») dans `functional/billing/src/Actions/`, avec `DamageAlreadySettledException` : transaction, `lockForUpdate()` sur le dégât, refus si `resolved_at` est renseigné ou si un `DamageSettlement` existe ; crée le `DamageSettlement` ; appelle `ResolveDamage` d'`inspection` ; `BillDamage` crée aussi la `Transmission` et dispatche `SendTransmissionJob` (depends on T024, T014)
- [X] T052 [US3] Créer le composant Livewire `DamageBillingActions` dans `functional/billing/src/Livewire/DamageBillingActions.php` et sa vue : « Refacturer » (montant en euros converti en centimes, libellé) et « Ne pas refacturer » (motif), erreurs de validation affichées ; boutons masqués et action refusée côté serveur sans `billing.manage` (via `DamageSettlementControl`) ; le composant ne reçoit que `$damage` et ne suppose rien de l'écran hôte (liste ou comparaison) ; après règlement, il recharge l'écran hôte par `$this->js('$wire.$parent.$refresh()')` (les hôtes de la 002 n'écoutent pas d'événement Livewire générique ; « Dégâts à traiter » se recharge aussi sur `damage.changed`, diffusé par `ResolveDamage`) ; l'enregistrer comme composant Livewire `billing.damage-actions` et dans `DamageActions` depuis le service provider de `billing` ; faire passer T049 (depends on T050, T051)
- [X] T053 [US3] Étendre `ReservationBillingSection` (`functional/billing/src/Livewire/ReservationBillingSection.php`) avec les dégâts de la réservation : refacturé (montant, libellé, état de la transmission) ou non refacturé (motif, auteur, date) (depends on T034, T051)
- [X] T054 [P] [US3] Ajouter les textes de l'US3 dans `functional/billing/resources/lang/fr/damages.php`

**Checkpoint** : US1 à US3 passent ; tout dégât signalé a une issue tracée.

---

## Phase 6: User Story 4 - Suivre ce qui a été transmis et ce qui reste à refacturer (Priority: P2)

**Goal**: un relevé par agence et par période.

**Independent Test**: sur 10 réservations clôturées dont 3 avec dégâts (1 refacturé, 1 non refacturé, 1 à traiter), le relevé du mois affiche 10 locations transmises, le montant refacturé, le motif du non-refacturé et le dégât à traiter avec son ancienneté.

### Tests for User Story 4

- [X] T055 [P] [US4] Écrire `functional/billing/tests/Feature/BillingStatementTest.php` : scénarios 1 et 2 de l'US4 avec le jeu de l'Independent Test ; filtre par agence (agence de rattachement de la machine) ; un dégât à traiter depuis plus de `billing.damage_overdue_days` est marqué en retard ; les comptes et totaux sont calculés par la base (une seule requête par agrégat, vérifiée avec `DB::enableQueryLog()`)

### Implementation for User Story 4

- [X] T056 [US4] Implémenter la requête `BillingStatement` dans `functional/billing/src/Queries/BillingStatement.php` : pour une agence (facultative) et une période, nombre de réservations ayant une période transmise (`sent` ou `exported`), total `amount_cents` des dégâts `billed`, liste des dégâts `waived` (motif, auteur, date), liste des dégâts non traités avec leur ancienneté ; agrégats en SQL (`count`, `sum`), pas en PHP
- [X] T057 [US4] Créer l'écran Livewire `Statement` (`/facturation/releve`) dans `functional/billing/src/Livewire/Statement.php` et sa vue : filtres agence et période (mois courant par défaut), montants affichés en euros, dégâts en retard mis en évidence (depends on T056)
- [X] T058 [P] [US4] Ajouter les textes de l'US4 dans `functional/billing/resources/lang/fr/statement.php`

**Checkpoint** : toutes les stories passent.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T059 [P] Écrire `functional/billing/tests/Feature/BillingHistoryTest.php` : FR-020, l'historique de la réservation contient chaque tentative, échec, relance manuelle, chiffrage, classement « non refacturé » et export, avec auteur et date
- [X] T060 [P] Écrire `functional/billing/tests/Feature/LayerBoundariesTest.php` : aucun fichier sous `functional/inspection`, `functional/booking`, `functional/fleet` ne référence le namespace de `billing`
- [X] T061 Lancer `docker compose exec -u sail laravel.test vendor/bin/phpstan analyse` et corriger jusqu'à zéro erreur ; vérifier qu'aucun fichier de code de `functional/billing/src/` ne dépasse 200 lignes
- [X] T062 Dérouler les scénarios de [quickstart.md](quickstart.md) avec `BILLING_GATEWAY=fake`, worker et planificateur actifs — déroulé le 2026-10-10 sur l'environnement Docker local avec le worker de file réel : scénarios 1 (location simple, transmise par le worker), 3 (logiciel injoignable puis rattrapage par `billing:reconcile`) et 4 (client inconnu, référence saisie puis relance depuis l'écran) ; les autres scénarios sont couverts par les tests Feature
- [ ] T063 **Bloquée par le client** : écrire l'adaptateur du logiciel de facturation réel dans `functional/billing/src/Gateways/` selon [contracts/billing-gateway.md](contracts/billing-gateway.md#obligations-de-toute-implémentation) (idempotence, délai borné, motifs sans secret), avec un test Feature utilisant `Http::fake()` ; aligner la mise en forme de l'export sur le format d'import réel ([contracts/export-format.md](contracts/export-format.md)). Ne démarre qu'avec le nom du logiciel, son moyen d'envoi, son authentification et son format d'import. **Critère d'arrêt** : si le logiciel ne sait ni ignorer une clé déjà reçue ni retrouver une ligne par sa clé, ne pas écrire l'adaptateur et revenir à `/speckit-plan` ([billing-gateway.md](contracts/billing-gateway.md#critère-darrêt-pour-ladaptateur-réel)).

---

## Phase 8: Conformité aux skills Xefi

**Purpose**: corriger les écarts relevés par l'audit de conformité du 2026-10-10 (skills `laravel`, `global`, `design-patterns`, `design`) avant la fusion des PR. Périmètre : `functional/billing` ; un fichier racine partagé n'est touché que si c'est nécessaire, et c'est signalé dans le commit. Lire le `SKILL.md` du skill cité avant chaque tâche. Pour les points comportementaux, écrire le test d'abord.

**Hors périmètre (décision de l'audit)** : nommage de `SendTransmissionJob`, attribut `#[Test]`, `no-db-enums` face aux CHECK exigés par la constitution, API REST lomkit (décision utilisateur : pas d'API).

### HAUTE

- [X] T064 [Conf] `transaction-boundaries` + `no-unenforced-guarantees` : écrire d'abord dans `functional/billing/tests/Feature/SendTransmissionTest.php` les tests « aucun appel au logiciel sous verrou » (un export lancé pendant l'appel n'attend pas et n'inclut pas la transmission réservée), « une réservation expirée est reprise » et « rien n'est écrit si le règlement échoue » ; puis découper `SendTransmission` en **réserver → commit → appel externe → régler** : une 1re transaction courte verrouille la transmission, vérifie `canBeSent()` et l'absence de réservation en cours, incrémente `attempts`, renseigne `last_attempt_at` et `reserved_until` (nouvelle colonne nullable de `transmissions`) ; l'appel à `BillingGateway::send()` se fait hors transaction ; une 2e transaction courte verrouille à nouveau, règle l'issue (`sent`, `failed`, nouvelle échéance) et efface `reserved_until`. `CreateBillingExport` et `billing:reconcile` ignorent une transmission dont `reserved_until` est dans le futur. Remplacer la clé morte `billing.http_timeout_seconds` par `billing.gateway_timeout_seconds`, réellement appliquée : durée de la réservation (`reserved_until` = maintenant + délai + marge `billing.reservation_margin_seconds`) et `$timeout` de `SendTransmissionJob` (le worker interrompt un envoi trop long) ; le contrat `BillingGateway` exige que l'adaptateur réel borne son appel HTTP avec cette clé (T063)
- [X] T065 [Conf] Passerelle factice interdite hors `local` / `testing` : test d'abord (`functional/billing/tests/Feature/BillingGatewayBindingTest.php` : en environnement `production` ou `staging`, résoudre `BillingGateway` configurée sur `fake` lève `FakeBillingGatewayNotAllowedException` et aucune transmission ne passe `sent` ; en `local` et `testing` la résolution réussit) ; implémenter le contrôle dans la liaison du service provider ; `billing.gateway` n'a plus de valeur par défaut (`.env.example` garde `fake` pour le local)
- [X] T066 [Conf] `seed-new-features` + `seeder-conventions` : `functional/billing/database/seeders/BillingSeeder.php` par factories, sans orphelin, sur les agences et machines du `FleetSeeder` : comptes clients de facturation et clients sans compte (motif `customer_unknown`), périodes `intermediate` et `final`, transmissions dans les 4 statuts et les 2 motifs d'échec, règlements `billed` et `waived`, un `BillingExport`, des dégâts encore à traiter dont un en retard ; appelé depuis `database/seeders/DatabaseSeeder.php` (fichier racine, nécessaire) ; test `functional/billing/tests/Feature/BillingSeederTest.php` vérifiant chaque statut, motif et issue

### MOYENNE

- [X] T067 [Conf] `no-queries-in-loops` : `CreateBillingExport` charge en une fois tout ce que les lignes lisent (réservation, machine, catégorie, agences, période, règlement, dégât, vue) et les références clients en une requête ; `MakeBillableLine` ne fait plus aucune requête quand les relations sont chargées ; test : le nombre de requêtes de l'export ne dépend pas du nombre de lignes
- [X] T068 [Conf] `value-object` (décision utilisateur) : value object `Money` (`final readonly`, centimes entiers + enum `Currency`, aucun float, `fromInput()` qui refuse une saisie invalide, `fromStored()`, `plus()`, `isPositive()`, `equals()`, `format()`) et son cast Eloquent sur `damage_settlements.amount_cents` ; `BillableLine`, `StatementFigures`, `BillDamage` et l'export manipulent `Money` ; `EuroAmount` disparaît ; value object `DateRange` à la place des tuples de `PeriodSplitter` et des paires de dates de `RecordBillablePeriod`. `brick/money` n'est pas ajouté (nouvelle dépendance non validée) : `Money` maison selon la variante prévue par le skill, à signaler
- [X] T069 [Conf] `no-god-classes` : supprimer le dossier `functional/billing/src/Support/` ; ranger par concept : `Calendar/BillingCalendar`, `Periods/PeriodSplitter` + `Periods/DateRange`, `History/BillingHistory`, `Transmissions/TransmissionLifecycle`, `Exports/ExportLineFormatter`, `Money/Money` + `Money/Currency` + `Money/MoneyCast`
- [X] T070 [Conf] `no-generic-exceptions` + `code-in-english` : messages d'exception en anglais pour les développeurs ; le texte affiché à l'écran reste traduit et est résolu séparément (méthode `userMessage()` lue par l'écran) ; `MissingGoLiveDateException` en anglais ; `InvalidDamageSettlementException::because(string)` remplacée par des constructeurs nommés (`amountNotPositive()`, `labelMissing()`, `waiverReasonMissing()`) ; sortie console, descriptions et arguments des commandes `billing:*` en anglais
- [X] T071 [Conf] `artisan-command-conventions` : `billing:reconcile` et `billing:close-months` affichent une ligne par élément traité avant de le traiter et une ligne de fin (résumé) ; tests de sortie avec `expectsOutputToContain`
- [X] T072 [Conf] `always-use-models` : remplacer `->from('transmissions')` et la jointure manuelle de `BillingStatement`, et `->from('billable_periods')` de `ReconcileCommand`, par des requêtes sur les modèles (`whereHas`, `whereRelation`, sous-requêtes `Model::query()`)
- [X] T073 [Conf] `no-magic-strings` : enum `BillingHistoryEvent` pour les événements d'historique ; enum `BillingPermission` (cas `Manage`) à la place des 7 occurrences de `'billing.manage'` dans le code (les routes et vues lisent l'enum) ; `whereBelongsTo` à la place des clés étrangères en chaîne (`SettleDamage`, `RecordBillablePeriod`, `ReservationBillingSection`, `MakeBillableLine`)
- [X] T074 [Conf] `object-construction` : remplacer `BillableLine` (16 paramètres dont 8 nullables selon le type) par une interface `BillableLine` et deux types `RentalPeriodLine` et `DamageLine`, chacun composé d'un `RentalContext` construit par `RentalContext::fromReservation()` ; la forme envoyée (`toArray()`) et le contrat [billing-gateway.md](contracts/billing-gateway.md) sont inchangés
- [X] T075 [Conf] `custom-faker-extensions` : extension `functional/billing/src/Faker/BillingExtension.php` (référence client du logiciel, nom de fichier d'export, référence renvoyée par le faux logiciel) enregistrée par un provider Faker déclaré dans `functional/billing/composer.json` ; les factories `BillingExportFactory`, `CustomerBillingAccountFactory` et `TransmissionFactory` n'appellent plus qu'un générateur par attribut
- [X] T076 [Conf] `layer-owned-config` : le disque `billing-exports` quitte `config/filesystems.php` (fichier racine) pour `functional/billing/config/filesystems.php`, chargé par `overrideConfigFrom()` de `xefi/laravel-osdd` (fusion des `disks`) ; nom du fichier d'export tiré d'une traduction avec interpolation ; test : la configuration lue par l'application est celle déclarée par le layer
- [X] T077 [Conf] `osdd` : `functional/billing/composer.json` déclare toutes ses dépendances (`functional/inspection`, `functional/booking`, `functional/fleet`, `spatie/simple-excel`, `spatie/laravel-activitylog`, `spatie/laravel-permission`, `lomkit/laravel-access-control`, `livewire/livewire`, `livewire/flux`, `xefi/faker-php-laravel` en dev)
- [X] T078 [Conf] `aggregate-in-the-database` : `ReservationBillingSection` trie en SQL (`orderBy` sur la période via sous-requête) et filtre avec `has('billablePeriod')` au lieu de `whereNotNull` + `sortBy` en PHP
- [X] T079 [Conf] Contrat d'écran : test d'abord, puis bouton « Relancer » d'une transmission en échec dans la section « Facturation » du détail de réservation (FR-010, [screens.md](contracts/screens.md)) ; aligner le libellé du relevé dans `screens.md` sur FR-018 (« locations transmises »)
- [X] T080 [Conf] Tests manquants : échec de l'écriture du fichier d'export (aucune transmission ne change d'état, aucun `BillingExport`), échec du règlement d'un dégât (rien d'écrit, rien envoyé), commande `billing:fake-gateway` (bascule de mode, liste des clés reçues, refus hors `local` / `testing`)

### UI (skills `design`)

- [X] T081 [Conf] `accessibility` : libellé visible pour le champ « référence client » de l'écran des transmissions ; erreurs affichées par le composant d'erreur de Flux (variante sombre comprise) au lieu de `text-red-600` ; l'état d'une transmission n'est jamais porté par la seule couleur
- [X] T082 [Conf] `buttons` : un seul bouton primaire par contexte ; dans les actions d'un dégât, « Refacturer » et « Ne pas refacturer » restent secondaires tant qu'aucun formulaire n'est ouvert, et le formulaire ouvert n'a qu'un primaire ; tailles selon le contexte (liste dense : XS, carte : S)
- [X] T083 [Conf] `screen-states` : états vides avec une issue (relevé : changer d'agence ou de mois ; transmissions : lien vers le relevé) ; message de succès après refacturer, ne pas refacturer, relancer une transmission et enregistrer une référence client
- [X] T084 [Conf] `spacing` / `foundations` : espacements de l'échelle Xefi (4, 8, 16, 24, 32, 48 px ; pas de `space-y-3`) ; chiffres du relevé affichés en texte mis en valeur et non en titres ; échelle des titres alignée sur la convention de la 001 dès sa fusion — échelle de l'échelle Xefi et chiffres du relevé faits ; tailles de titres alignées sur la convention de la 001 en T088
- [X] T085 [Conf] `ux-writing` : libellés explicites (« Classer non refacturé » au lieu de « Valider ») ; le message « injoignable » dit que la relance est automatique ; le motif brut renvoyé par le logiciel tiers n'est plus affiché tel quel : message maîtrisé et détail technique repliable

**Checkpoint** : la suite complète, PHPStan et Pint passent ; `migrate:fresh --seed` montre chaque état de la facturation.

---

## Phase 9: Alignement sur le socle 001

**Purpose**: reprendre les conventions livrées par la 001 (39d253a) et la 002 (ffc711b), fusionnées dans cette branche par la coordination (3ed749a), à la place des équivalents propres à `billing` écrits en phase 8.

- [X] T086 [Socle] Refus : les refus de `billing` (`IllegalTransmissionTransitionException`, `NothingToExportException`, `DamageAlreadySettledException`, `InvalidDamageSettlementException`) étendent `Functional\Fleet\Exceptions\RefusalException` (message technique anglais, clé de traduction, `userMessage()`), affichés par `DisplaysRefusals` ; supprimer `BillingRefusalException` et `DisplaysBillingRefusals` ; les tests de refus passent par `AssertsRefusals::assertRefused()` ; les exceptions qui ne sont pas des refus affichés restent typées avec un message anglais
- [X] T087 [Socle] Faker : renommer `BillingExtension` en `BillingFakerExtension` (convention de la 001 et de la 002) et régénérer `faker_mixin.php`
- [X] T088 [Socle] Titres (suite de T084) : `<x-page-heading>` pour les écrans Transmissions, Exports de secours et Relevé ; `<x-section-heading>` pour les sections du relevé et la section « Facturation » du détail de réservation (niveau 3 pour ses sous-parties)
- [X] T089 [Socle] Navigation : `@can` du layout et de la barre latérale lisent `BillingPermission::Manage` au lieu de la chaîne `'billing.manage'`

`model:prune` : aucun modèle de `billing` n'est purgeable, rien à enregistrer.

**Checkpoint** : suite complète, PHPStan (cache vidé) et Pint passent.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Prérequis 001 et 002** (phases 5 commitées) → bloque tout.
- **Setup (Phase 1)** → **Foundational (Phase 2)** → stories.
- **US1 (Phase 3)** : après la phase 2. MVP.
- **US2 (Phase 4)** : après US1 (le rattrapage réutilise `RecordFinalPeriod`).
- **US3 (Phase 5)** : après la phase 2 ; T053 attend T034 (US1). Parallélisable avec US2.
- **US4 (Phase 6)** : après US1 et US3 (le relevé lit périodes et dégâts réglés).
- **Polish (Phase 7)** : après les stories voulues. T063 attend le client, indépendamment du reste.
- **Conformité (Phase 8)** : après la phase 7. T064 et T065 d'abord (HAUTE), puis T069 (rangement) avant T068, T073 et T074 qui déplacent les mêmes classes ; T081 à T085 après T079 (mêmes vues).

### Story Completion Order

```text
Prérequis 001 + 002 → Setup → Foundational → US1 ─┬─► US2 ─┬─► US4 → Polish
                                                   └─► US3 ─┘
                                        T063 (adaptateur réel) : dès que le client répond
```

### Within Each User Story

- Tests écrits et en échec avant l'implémentation.
- Migrations → modèles → actions → jobs / listeners / commandes → composants Livewire → traductions.

### Parallel Opportunities

- Phase 1 : T002, T003, T004.
- Phase 2 : T005 ; T007, T008, T009 ; T011 à T014 ; T016 et T018 ; T025 et T026.
- Chaque story : ses tâches de test [P] ensemble.
- US2 et US3 en parallèle une fois US1 terminée.

## Parallel Example: User Story 1

```text
T027 FinalPeriodTest   T028 MonthEndPeriodsTest   T029 PeriodSplitTest
puis : T030 RecordFinalPeriod → T032 listener
       T031 RecordMonthEndPeriods → T033 billing:close-months
       T034 ReservationBillingSection   T035 traductions
```

## Parallel Example: US2 et US3

```text
Développeur A : T036 → T047 (US2 : relances, échecs, export, alerte)
Développeur B : T048 → T054 (US3 : chiffrage des dégâts)
```

## Implementation Strategy

### MVP First

1. Attendre les phases 5 de la 001 et de la 002, mettre à jour la branche.
2. Phases 1 et 2.
3. US1 : chaque location part dans le (faux) logiciel de facturation. **Démo possible** avec le faux logiciel.

### Incremental Delivery

1. US1 → démo « toute location est transmise ».
2. US2 → rien ne se perd : relances, liste des échecs, export de secours. **Livrable en production dès T063**, l'export de secours couvrant l'attente.
3. US3 → les dégâts sont refacturés : la perte de 85 000 € est adressée de bout en bout.
4. US4 → le relevé mesure le résultat.

## Notes

- La 003 ne modifie aucun fichier d'`inspection`, `booking` ni `fleet` : elle s'enregistre dans les registres qu'ils exposent.
- Sans T063, la feature est complète et testée mais ne parle qu'au faux logiciel ; la mise en production attend le client.
- Aucun commit avant validation de chaque phase.
