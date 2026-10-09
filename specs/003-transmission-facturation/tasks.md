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

- la 001 **jusqu'à sa phase 5 incluse** : `Reservation` avec `departed_at` / `returned_at`, `ReturnReservation`, `ReservationChanged`, `ReservationDetail` ;
- la 002 **jusqu'à sa phase 5 incluse** : `Damage`, `ResolveDamage`, l'écran `DamagesList`, le registre `ReservationDetailSections` (T011 de la 002).

Avant T001 :

1. la 001 et la 002 sont implémentées jusqu'à ces phases et commitées ;
2. la branche `003-transmission-facturation` est mise à jour par-dessus ;
3. `./vendor/bin/sail artisan test` passe sur la branche mise à jour.

Le registre des actions d'un dégât (T050) modifie `inspection` : si la session de la 002 l'a déjà ajouté, cocher T050 et adapter T052 à son API.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US4)

## Conventions pour toutes les tâches

Celles de la 001 s'appliquent sans changement ([tasks.md de la 001](../001-reservation-machines/tasks.md#conventions-pour-toutes-les-tâches)) : Sail pour toutes les commandes, code en anglais, textes dans `layers/billing/resources/lang/fr/*.php`, statuts en `string` castés en enum, pas de cascade, pas d'observer, pas de `try/catch` (utiliser `rescue()` + rethrow de tout ce qui n'est pas l'exception de domaine attendue), pas de commentaire, factories avec `faker()`, contrôles par permission, fichiers de code < 200 lignes, agrégats calculés en base. En plus :

- `billing` dépend d'`inspection`, `booking` et `fleet` ; **aucun fichier d'`inspection`, `booking` ni `fleet` n'importe une classe de `billing`**.
- `Reservation`, `Customer` et `Damage` ne déclarent aucune relation vers les tables de `billing`.
- Les dates de période se calculent en heure de Paris à partir de `departed_at` et `returned_at`, **jamais** à partir de `end_date`.
- Montants en centimes entiers (`amount_cents`), jamais en flottant.
- Les tests manipulent l'horloge avec `$this->travelTo()` et le logiciel de facturation avec `FakeBillingGateway`, jamais un vrai appel réseau.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Layer `billing`, configuration, disque d'export, permission.

- [ ] T001 Créer le layer OSDD `layers/billing/` (`composer.json` LayerManifest déclarant la dépendance à `inspection`, `booking` et `fleet`, namespace PSR-4, service provider, dossiers `src/`, `config/`, `database/migrations/`, `database/factories/`, `database/seeders/`, `resources/views/`, `resources/lang/fr/`, `routes/`, `tests/Feature/`, `tests/Unit/`) ; enregistrer sa suite de tests dans `phpunit.xml`
- [ ] T002 [P] Créer `layers/billing/config/billing.php` avec les clés de [data-model.md](data-model.md#configuration-layersbillingconfigbillingphp) : `go_live_date` (lue depuis `BILLING_GO_LIVE_DATE`), `gateway` (`BILLING_GATEWAY`, défaut `fake`), `http_timeout_seconds` = 10, `retry_delays_minutes` = `[1, 5, 15, 60]`, `alert_after_hours` = 24, `damage_overdue_days` = 7, `export_disk` = `billing-exports` ; la charger depuis le service provider du layer
- [ ] T003 [P] Ajouter le disque privé `billing-exports` (driver `local`, racine `storage/app/private/billing-exports`) dans `config/filesystems.php`, et `BILLING_GATEWAY=fake`, `BILLING_GO_LIVE_DATE=` dans `.env.example`
- [ ] T004 [P] Ajouter la permission `billing.manage` au rôle `salarie` dans `layers/billing/database/seeders/BillingPermissionSeeder.php`, appelé depuis `database/seeders/DatabaseSeeder.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Tables, modèles, cycle de vie d'une transmission, port vers le logiciel de facturation, envoi. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

### Enums et tables

- [ ] T005 [P] Créer les enums backed `BillablePeriodKind` (`intermediate`, `final`), `TransmissionStatus` (`pending`, `sent`, `exported`, `failed`), `TransmissionFailureReason` (`customer_unknown`, `rejected`), `DamageOutcome` (`billed`, `waived`) dans `layers/billing/src/Enums/`, avec libellés traduits dans `layers/billing/resources/lang/fr/enums.php`
- [ ] T006 Créer la migration `billable_periods` dans `layers/billing/database/migrations/` : `reservation_id` (`->constrained()` sans cascade), `kind` string, `start_date` date, `end_date` date, `days` entier, timestamps ; CHECK `end_date >= start_date` et `days = end_date - start_date + 1` ; contrainte d'exclusion `EXCLUDE USING gist (reservation_id WITH =, daterange(start_date, end_date, '[]') WITH &&)` ; index unique partiel « au plus une période `final` par réservation » (`WHERE kind = 'final'`)
- [ ] T007 [P] Créer la migration `customer_billing_accounts` dans `layers/billing/database/migrations/` : `customer_id` « obligatoire, unique », `external_ref` « obligatoire », timestamps
- [ ] T008 [P] Créer la migration `billing_exports` dans `layers/billing/database/migrations/` : `created_by` → users « obligatoire », `line_count` entier CHECK `>= 1`, `file_path` string, timestamps
- [ ] T009 [P] Créer la migration `damage_settlements` dans `layers/billing/database/migrations/` : `damage_id` « obligatoire, unique », `outcome` string, `amount_cents` entier nullable, `label` texte nullable, `waiver_reason` texte nullable, `settled_by` → users, `settled_at` date-heure ; CHECK « `amount_cents` obligatoire et > 0 si `billed`, vide si `waived` », « `label` obligatoire si `billed` », « `waiver_reason` obligatoire si `waived` »
- [ ] T010 Créer la migration `transmissions` dans `layers/billing/database/migrations/` : `uuid` unique, `billable_period_id` nullable unique, `damage_settlement_id` nullable unique, `reservation_id` obligatoire, `status` string défaut `pending`, `attempts` entier défaut 0, `next_attempt_at`, `last_attempt_at`, `sent_at` date-heure nullable, `failure_reason` string nullable, `last_error` texte nullable, `external_ref` string nullable, `billing_export_id` nullable ; CHECK « exactement un de `billable_period_id` et `damage_settlement_id` est renseigné » ; index sur (`status`, `next_attempt_at`) (depends on T006, T008, T009)

### Modèles

- [ ] T011 [P] Créer le modèle `BillablePeriod` dans `layers/billing/src/Models/BillablePeriod.php` (casts `kind` → `BillablePeriodKind`, dates ; relations `reservation()`, `transmission()`) et sa factory dans `layers/billing/database/factories/BillablePeriodFactory.php`
- [ ] T012 [P] Créer le modèle `CustomerBillingAccount` dans `layers/billing/src/Models/CustomerBillingAccount.php` (relation `customer()`) et sa factory
- [ ] T013 [P] Créer le modèle `BillingExport` dans `layers/billing/src/Models/BillingExport.php` (relations `creator()`, `transmissions()`) et sa factory
- [ ] T014 [P] Créer le modèle `DamageSettlement` dans `layers/billing/src/Models/DamageSettlement.php` (casts `outcome` → `DamageOutcome` ; relations `damage()`, `settler()`, `transmission()`) et sa factory (états `billed` et `waived`)
- [ ] T015 Créer le modèle `Transmission` dans `layers/billing/src/Models/Transmission.php` (casts `status` → `TransmissionStatus`, `failure_reason` → `TransmissionFailureReason`, dates ; `uuid` généré à la création ; relations `billablePeriod()`, `damageSettlement()`, `reservation()`, `export()` ; accesseur `state()` qui renvoie la classe d'état) et sa factory (un état par statut) (depends on T011, T013, T014)

### Cycle de vie d'une transmission (pattern State)

Lire `design-patterns:state` avant T016.

- [ ] T016 [P] Écrire les tests Unit des transitions dans `layers/billing/tests/Unit/TransmissionStateTest.php` : `pending → sent`, `pending → failed`, `failed → pending`, `pending → exported`, `failed → exported` autorisées ; toute transition depuis `sent` ou `exported`, et `failed → sent`, lèvent `IllegalTransmissionTransitionException`
- [ ] T017 Créer `IllegalTransmissionTransitionException` dans `layers/billing/src/Exceptions/` et une classe d'état par statut dans `layers/billing/src/States/` (`PendingTransmission`, `SentTransmission`, `ExportedTransmission`, `FailedTransmission`) exposant `markSent(string $externalRef)`, `markFailed(TransmissionFailureReason, string $message)`, `requeue()`, `markExported(BillingExport)`, `canBeSent()`, `canBeExported()`, `canBeRetried()` ; faire passer T016 (depends on T015, T016)

### Port vers le logiciel de facturation

- [ ] T018 [P] Créer le value object `BillableLine` dans `layers/billing/src/ValueObjects/BillableLine.php` avec exactement les champs de [contracts/billing-gateway.md](contracts/billing-gateway.md#ce-qui-est-envoyé--une-ligne-facturable), et les exceptions `BillingSoftwareRejectedException` (porte un motif lisible) et `BillingSoftwareUnreachableException` dans `layers/billing/src/Exceptions/`
- [ ] T019 Créer l'interface `BillingGateway` dans `layers/billing/src/Contracts/BillingGateway.php` (`send(BillableLine $line): string` qui renvoie l'identifiant du logiciel ; docblock : lève `BillingSoftwareRejectedException` ou `BillingSoftwareUnreachableException`) (depends on T018)
- [ ] T020 Créer `FakeBillingGateway` dans `layers/billing/src/Gateways/FakeBillingGateway.php` : mode `accept` / `unreachable`, refus programmable par clé d'idempotence avec un motif, renvoie le même identifiant pour une clé déjà reçue sans ajouter de ligne ; mode et lignes reçues **stockés dans le cache de l'application** (partagés entre worker et console, store `array` en tests), jamais en propriété d'instance ; créer la commande `billing:fake-gateway {mode?} {--received}` dans `layers/billing/src/Console/FakeGatewayCommand.php`, qui refuse de s'exécuter hors des environnements `local` et `testing` ; lier `BillingGateway` à l'implémentation choisie par `config('billing.gateway')` dans le service provider (depends on T019)
- [ ] T021 Implémenter l'action `MakeBillableLine` dans `layers/billing/src/Actions/MakeBillableLine.php` : construit la `BillableLine` d'une `Transmission` (période ou dégât) selon le tableau du contrat ; `customer_ref` lu sur `CustomerBillingAccount`, `null` s'il n'existe pas (depends on T018, T015)

### Envoi

- [ ] T022 Écrire le test Feature `layers/billing/tests/Feature/SendTransmissionTest.php` : accepté → `sent`, `external_ref` et `sent_at` renseignés ; client sans `CustomerBillingAccount` → `failed` / `customer_unknown` sans appel au logiciel ; refusé → `failed` / `rejected` avec le motif dans `last_error` ; injoignable → reste `pending`, `attempts` + 1, `next_attempt_at` = maintenant + 1 min puis 5, 15, 60, puis 60 ; une transmission `sent` ou `exported` n'est jamais renvoyée ; deux envois de la même transmission n'appellent le faux logiciel qu'une fois
- [ ] T023 Implémenter l'action `SendTransmission` dans `layers/billing/src/Actions/SendTransmission.php` : transaction, `lockForUpdate()` sur la transmission, sortie immédiate si `! state()->canBeSent()` ; client inconnu → `markFailed(customer_unknown)` ; appel `BillingGateway::send()` via `rescue()` : `BillingSoftwareRejectedException` → `markFailed(rejected)`, `BillingSoftwareUnreachableException` → `attempts` + 1 et `next_attempt_at` selon `billing.retry_delays_minutes`, toute autre exception relancée ; `last_attempt_at` renseignée à chaque tentative ; faire passer T022 (depends on T017, T020, T021, T022)
- [ ] T024 Créer le job `SendTransmissionJob` dans `layers/billing/src/Jobs/SendTransmissionJob.php` (`ShouldQueue`, `ShouldBeUnique` par id de transmission, `afterCommit`) qui appelle `SendTransmission` (depends on T023)
- [ ] T025 [P] Activer `spatie/laravel-activitylog` sur `BillablePeriod`, `Transmission` et `DamageSettlement` (journal rattaché à la réservation, auteur = utilisateur courant ou « système ») (depends on T011, T014, T015)
- [ ] T026 [P] Créer les contrôles `TransmissionControl`, `DamageSettlementControl`, `BillingExportControl` dans `layers/billing/src/Controls/` (`lomkit/laravel-access-control`), tous fondés sur la permission `billing.manage` (depends on T015)

**Checkpoint** : `./vendor/bin/sail artisan test layers/billing/tests` passe ; une transmission créée à la main part dans le faux logiciel.

---

## Phase 3: User Story 1 - Transmettre automatiquement chaque location (Priority: P1) 🎯 MVP

**Goal**: chaque location est découpée en périodes (une par mois écoulé, puis une finale au retour) et chaque période est transmise sans action du salarié.

**Independent Test**: enregistrer le retour d'une réservation en cours : une période finale avec les bonnes dates et le bon nombre de jours est transmise. Simuler une fin de mois avec une réservation en cours : la période écoulée est transmise.

### Tests for User Story 1

- [ ] T027 [P] [US1] Écrire `layers/billing/tests/Feature/FinalPeriodTest.php` : scénarios 1 à 4 et 8 de l'US1 (sortie le 10, retour le 14 → 5 jours ; retour anticipé le 12 → 3 jours ; retour en retard le 17 → 8 jours ; réservation annulée → rien ; second déclenchement → aucun doublon) ; réservation rendue avant `billing.go_live_date` → aucune période ; réservation sortie avant `billing.go_live_date` et rendue après → transmise en entier depuis sa date de sortie
- [ ] T028 [P] [US1] Écrire `layers/billing/tests/Feature/MonthEndPeriodsTest.php` : scénarios 5 à 7 de l'US1 (sortie le 20 novembre, `billing:close-months` le 1er décembre → période `intermediate` du 20 au 30, 11 jours ; retour le 5 décembre → période `final` du 1er au 5 ; retour le 30 novembre → une seule période `final`) ; location sur 3 mois → 2 périodes intermédiaires + 1 finale ; relancer la commande ne crée rien ; la commande lancée le 3 du mois rattrape le mois précédent ; réservation sortie le 15 août, `billing.go_live_date` au 1er octobre, commande lancée le 1er octobre → périodes intermédiaires du 15 au 31 août et du 1er au 30 septembre ; la transmission du retour ne reprend pas les jours d'une période intermédiaire en échec
- [ ] T029 [P] [US1] Écrire `layers/billing/tests/Unit/PeriodSplitTest.php` : pour des dates de sortie et de retour variées (même jour, fin de mois, fin d'année, 29 février), la somme des `days` est égale à la durée réelle, bornes incluses, et aucune période ne se chevauche

### Implementation for User Story 1

- [ ] T030 [US1] Implémenter l'action `RecordFinalPeriod` dans `layers/billing/src/Actions/RecordFinalPeriod.php` : ne fait rien si la réservation n'est pas `closed`, si `returned_at` < `billing.go_live_date` ou si une période `final` existe ; début = lendemain de la dernière `end_date` ou date de sortie (Europe/Paris), fin = date de `returned_at` (Europe/Paris) ; crée la période et sa `Transmission` dans une transaction ; dispatche `SendTransmissionJob` (depends on T024)
- [ ] T031 [US1] Implémenter l'action `RecordMonthEndPeriods` dans `layers/billing/src/Actions/RecordMonthEndPeriods.php` : pour une réservation `in_progress`, quelle que soit sa date de sortie, crée une période `intermediate` par mois entièrement écoulé non couvert (fin = dernier jour du mois, uniquement si ce jour est passé), chacune avec sa `Transmission`, dans une transaction ; dispatche les jobs (depends on T024)
- [ ] T032 [US1] Créer le listener `RecordFinalPeriodOnReservationClosed` dans `layers/billing/src/Listeners/RecordFinalPeriodOnReservationClosed.php` (`ShouldQueue`, `afterCommit`) écoutant `ReservationChanged` : appelle `RecordFinalPeriod` (idempotent : l'événement est aussi émis par `RefreshReservationConflicts`) ; l'enregistrer dans le service provider de `billing` (depends on T030)
- [ ] T033 [US1] Créer la commande `billing:close-months` dans `layers/billing/src/Console/CloseMonthsCommand.php` qui appelle `RecordMonthEndPeriods` pour chaque réservation `in_progress` (par lots), et la planifier chaque jour à 00:15 `Europe/Paris` (depends on T031)
- [ ] T034 [US1] Créer le composant Livewire `ReservationBillingSection` dans `layers/billing/src/Livewire/ReservationBillingSection.php` et sa vue : périodes (dates, jours, type), état et date de chaque transmission ; l'enregistrer dans `ReservationDetailSections` depuis le service provider de `billing` (FR-017) (depends on T015)
- [ ] T035 [P] [US1] Ajouter les textes de l'US1 dans `layers/billing/resources/lang/fr/periods.php` et `layers/billing/resources/lang/fr/transmissions.php`

**Checkpoint** : US1 testable seule ; T027 à T029 passent.

---

## Phase 4: User Story 2 - Ne perdre aucune transmission en cas d'échec (Priority: P1)

**Goal**: relance automatique, liste des échecs avec motif, correction de la référence client, alerte, export de secours.

**Independent Test**: logiciel indisponible, clôturer une réservation → « en attente » ; logiciel rétabli → transmise sans action. Logiciel toujours indisponible, produire l'export → la location y figure et n'est plus relancée.

### Tests for User Story 2

- [ ] T036 [P] [US2] Écrire `layers/billing/tests/Feature/TransmissionRecoveryTest.php` : scénarios 1 et 2 (retour enregistré malgré le logiciel injoignable ; `billing:reconcile` après rétablissement → transmise) ; une réservation `closed` sans période finale (listener perdu) est rattrapée par `billing:reconcile` ; une transmission dont `next_attempt_at` est future n'est pas renvoyée
- [ ] T037 [P] [US2] Écrire `layers/billing/tests/Feature/FailedTransmissionsTest.php` : scénarios 3 et 4 (client inconnu → listé avec motif ; référence renseignée puis relance → transmise et sort de la liste) ; relancer une transmission `sent` est refusé ; un utilisateur sans `billing.manage` n'accède pas à l'écran
- [ ] T038 [P] [US2] Écrire `layers/billing/tests/Feature/BillingAlertTest.php` : scénario 5 (transmission `pending` créée il y a plus de 24 h → bandeau visible avec le nombre ; moins de 24 h → pas de bandeau) ; une transmission `failed` déclenche le bandeau immédiatement
- [ ] T039 [P] [US2] Écrire `layers/billing/tests/Feature/EmergencyExportTest.php` : scénarios 6 et 7 (export de 2 `pending` + 1 `failed`, la `sent` exclue ; toutes passent `exported` avec `billing_export_id` ; `billing:reconcile` ne les renvoie pas ; second export → `NothingToExportException`) ; contenu du fichier conforme à [contracts/export-format.md](contracts/export-format.md) (en-tête, séparateur `;`, BOM, montant `450,00`)

### Implementation for User Story 2

- [ ] T040 [US2] Créer la commande `billing:reconcile` dans `layers/billing/src/Console/ReconcileCommand.php` : appelle `RecordFinalPeriod` pour chaque réservation `closed` rendue après `billing.go_live_date` sans période finale, puis dispatche `SendTransmissionJob` pour chaque transmission `pending` dont `next_attempt_at` est vide ou passée ; la planifier **chaque minute** avec `withoutOverlapping()` (les premiers délais de relance sont d'une minute) (depends on T030, T024)
- [ ] T041 [US2] Implémenter les actions `RetryTransmission` (« `failed → pending` », `attempts` conservé, `next_attempt_at` = maintenant, dispatche le job) et `SetCustomerBillingRef` (crée ou met à jour le `CustomerBillingAccount`) dans `layers/billing/src/Actions/` (depends on T024, T012)
- [ ] T042 [US2] Créer l'écran Livewire `Transmissions` (`/facturation/transmissions`) dans `layers/billing/src/Livewire/Transmissions.php` et sa vue selon [contracts/screens.md](contracts/screens.md) : transmissions `failed` et `pending` créées depuis plus de `billing.alert_after_hours` (réservation, client, type, date, motif) ; saisie de la référence client ; bouton « Relancer » ; lien vers la réservation (depends on T041)
- [ ] T043 [US2] Implémenter l'action `CreateBillingExport` dans `layers/billing/src/Actions/CreateBillingExport.php` et `NothingToExportException` : transaction, `lockForUpdate()` sur les transmissions `pending` et `failed`, lève `NothingToExportException` s'il n'y en a aucune, écrit le CSV avec `spatie/simple-excel` sur le disque `billing.export_disk` (nom `export-facturation-AAAAMMJJ-HHMMSS.csv`), crée le `BillingExport`, passe chaque transmission `exported` ; aucune transmission ne change d'état si l'écriture échoue (depends on T017, T021)
- [ ] T044 [US2] Créer l'écran Livewire `Exports` (`/facturation/exports`) dans `layers/billing/src/Livewire/Exports.php` et sa vue : bouton « Produire un export », liste des exports (date, auteur, nombre de lignes) et téléchargement via une route protégée par `billing.manage` (depends on T043)
- [ ] T045 [US2] Créer le composant Livewire `BillingAlert` dans `layers/billing/src/Livewire/BillingAlert.php` (`wire:poll.60s`, compte les `failed` et les `pending` créées depuis plus de `billing.alert_after_hours`, lien vers `/facturation/transmissions`) et l'inclure dans le layout de `app/` (fichier de layout du starter kit sous `resources/views/`) pour les utilisateurs ayant `billing.manage`
- [ ] T046 [US2] Déclarer les routes `/facturation/*` dans `layers/billing/routes/web.php` (middleware `auth` + permission `billing.manage`) et ajouter l'entrée de menu « Facturation » (Transmissions, Exports, Relevé) dans la navigation de `app/`
- [ ] T047 [P] [US2] Ajouter les textes de l'US2 dans `layers/billing/resources/lang/fr/transmissions.php` et `layers/billing/resources/lang/fr/exports.php` (motifs d'échec en clair)

**Checkpoint** : US1 et US2 passent ; aucune transmission ne peut disparaître ni partir deux fois.

---

## Phase 5: User Story 3 - Chiffrer un dégât et le transmettre pour refacturation (Priority: P1)

**Goal**: chaque dégât signalé finit soit refacturé (montant transmis), soit non refacturé avec un motif.

**Independent Test**: sur une réservation clôturée et transmise, portant un dégât « bras rayé », saisir 450 € et valider : le faux logiciel reçoit une ligne de 45000 centimes liée à la même réservation, et le dégât passe « refacturé ».

### Tests for User Story 3

- [ ] T048 [P] [US3] Écrire `layers/billing/tests/Feature/DamageBillingTest.php` : scénarios 1 à 5 de l'US3 (refacturé 450 € « remplacement capot » → transmis avec vue et commentaire ; location déjà transmise → seul le dégât part ; « usure normale » → non refacturé, rien transmis ; motif vide → refus ; modification d'un dégât réglé → refus) ; montant nul ou négatif → refus ; dégât déjà traité → `DamageAlreadySettledException` ; un dégât réglé n'apparaît plus dans `DamagesList` ; un dégât d'une réservation rendue avant `billing.go_live_date` peut être refacturé et transmis
- [ ] T049 [P] [US3] Écrire `layers/inspection/tests/Feature/DamageActionsRegistryTest.php` : sans action enregistrée, `DamagesList` affiche « Marquer traité » ; avec une action enregistrée, il affiche cette action et plus « Marquer traité »

### Implementation for User Story 3

- [ ] T050 [US3] Créer le registre `DamageActions` (`register(string $livewireComponent, int $position)`, `all()` trié, `isEmpty()`) dans `layers/inspection/src/Support/DamageActions.php`, lié dans le service provider d'`inspection` ; dans la vue de `layers/inspection/src/Livewire/DamagesList.php`, rendre pour chaque dégât les actions enregistrées via `<livewire:dynamic-component :component="…" :damage="$damage" :key="…" />`, et l'action « Marquer traité » uniquement si le registre est vide ; faire passer T049 (modifie la 002)
- [ ] T051 [US3] Implémenter les actions `BillDamage` (montant HT en centimes « obligatoire et > 0 », libellé « obligatoire ») et `WaiveDamage` (motif « obligatoire ») dans `layers/billing/src/Actions/`, avec `DamageAlreadySettledException` : transaction, `lockForUpdate()` sur le dégât, refus si `resolved_at` est renseigné ou si un `DamageSettlement` existe ; crée le `DamageSettlement` ; appelle `ResolveDamage` d'`inspection` ; `BillDamage` crée aussi la `Transmission` et dispatche `SendTransmissionJob` (depends on T024, T014)
- [ ] T052 [US3] Créer le composant Livewire `DamageBillingActions` dans `layers/billing/src/Livewire/DamageBillingActions.php` et sa vue : « Refacturer » (montant en euros converti en centimes, libellé) et « Ne pas refacturer » (motif), erreurs de validation affichées ; l'enregistrer dans `DamageActions` depuis le service provider de `billing` (depends on T050, T051)
- [ ] T053 [US3] Étendre `ReservationBillingSection` (`layers/billing/src/Livewire/ReservationBillingSection.php`) avec les dégâts de la réservation : refacturé (montant, libellé, état de la transmission) ou non refacturé (motif, auteur, date) (depends on T034, T051)
- [ ] T054 [P] [US3] Ajouter les textes de l'US3 dans `layers/billing/resources/lang/fr/damages.php`

**Checkpoint** : US1 à US3 passent ; tout dégât signalé a une issue tracée.

---

## Phase 6: User Story 4 - Suivre ce qui a été transmis et ce qui reste à refacturer (Priority: P2)

**Goal**: un relevé par agence et par période.

**Independent Test**: sur 10 réservations clôturées dont 3 avec dégâts (1 refacturé, 1 non refacturé, 1 à traiter), le relevé du mois affiche 10 locations transmises, le montant refacturé, le motif du non-refacturé et le dégât à traiter avec son ancienneté.

### Tests for User Story 4

- [ ] T055 [P] [US4] Écrire `layers/billing/tests/Feature/BillingStatementTest.php` : scénarios 1 et 2 de l'US4 avec le jeu de l'Independent Test ; filtre par agence (agence de rattachement de la machine) ; un dégât à traiter depuis plus de `billing.damage_overdue_days` est marqué en retard ; les comptes et totaux sont calculés par la base (une seule requête par agrégat, vérifiée avec `DB::enableQueryLog()`)

### Implementation for User Story 4

- [ ] T056 [US4] Implémenter la requête `BillingStatement` dans `layers/billing/src/Queries/BillingStatement.php` : pour une agence (facultative) et une période, nombre de réservations ayant une période transmise (`sent` ou `exported`), total `amount_cents` des dégâts `billed`, liste des dégâts `waived` (motif, auteur, date), liste des dégâts non traités avec leur ancienneté ; agrégats en SQL (`count`, `sum`), pas en PHP
- [ ] T057 [US4] Créer l'écran Livewire `Statement` (`/facturation/releve`) dans `layers/billing/src/Livewire/Statement.php` et sa vue : filtres agence et période (mois courant par défaut), montants affichés en euros, dégâts en retard mis en évidence (depends on T056)
- [ ] T058 [P] [US4] Ajouter les textes de l'US4 dans `layers/billing/resources/lang/fr/statement.php`

**Checkpoint** : toutes les stories passent.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [ ] T059 [P] Écrire `layers/billing/tests/Feature/BillingHistoryTest.php` : FR-020, l'historique de la réservation contient chaque tentative, échec, relance manuelle, chiffrage, classement « non refacturé » et export, avec auteur et date
- [ ] T060 [P] Écrire `layers/billing/tests/Feature/LayerBoundariesTest.php` : aucun fichier sous `layers/inspection`, `layers/booking`, `layers/fleet` ne référence le namespace de `billing`
- [ ] T061 Lancer `./vendor/bin/sail php vendor/bin/phpstan analyse` et corriger jusqu'à zéro erreur ; vérifier qu'aucun fichier de code de `layers/billing/src/` ne dépasse 200 lignes
- [ ] T062 Dérouler les scénarios de [quickstart.md](quickstart.md) avec `BILLING_GATEWAY=fake`, worker et planificateur actifs
- [ ] T063 **Bloquée par le client** : écrire l'adaptateur du logiciel de facturation réel dans `layers/billing/src/Gateways/` selon [contracts/billing-gateway.md](contracts/billing-gateway.md#obligations-de-toute-implémentation) (idempotence, délai borné, motifs sans secret), avec un test Feature utilisant `Http::fake()` ; aligner la mise en forme de l'export sur le format d'import réel ([contracts/export-format.md](contracts/export-format.md)). Ne démarre qu'avec le nom du logiciel, son moyen d'envoi, son authentification et son format d'import. **Critère d'arrêt** : si le logiciel ne sait ni ignorer une clé déjà reçue ni retrouver une ligne par sa clé, ne pas écrire l'adaptateur et revenir à `/speckit-plan` ([billing-gateway.md](contracts/billing-gateway.md#critère-darrêt-pour-ladaptateur-réel)).

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
Développeur B : T048 → T054 (US3 : registre d'actions, chiffrage des dégâts)
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

- T050 modifie le layer `inspection` de la 002 : le faire relire par la personne qui a implémenté la 002.
- Sans T063, la feature est complète et testée mais ne parle qu'au faux logiciel ; la mise en production attend le client.
- Aucun commit avant validation de chaque phase.
