---

description: "Task list for feature 001-reservation-machines"
---

# Tasks: Cœur de réservation des machines

**Input**: Design documents from `specs/001-reservation-machines/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/screens.md), [quickstart.md](quickstart.md)

**Tests**: Inclus. Le plan (R11) et le quickstart exigent un test Feature PHPUnit par scénario d'acceptation. Dans chaque story, écrire les tests d'abord et vérifier qu'ils échouent.

**Organization**: Tâches groupées par user story ; chaque story est testable indépendamment une fois la phase 2 terminée.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]**: user story de [spec.md](spec.md) (US1 à US5)

## Conventions pour toutes les tâches

- Toutes les commandes passent par Sail : `./vendor/bin/sail artisan …`, `./vendor/bin/sail composer …`, `./vendor/bin/sail npm …`.
- Code en anglais ; tout texte affiché passe par `layers/<layer>/resources/lang/fr/*.php` (ou `lang/fr/*.php` pour `app/`).
- Statuts : colonne `string` + cast vers un enum PHP backed ; jamais d'enum en base.
- Aucune suppression en cascade en base (`->constrained()` sans `cascadeOnDelete()`), aucun observer, aucun `try/catch`, aucun commentaire de code.
- Factories avec le helper `faker()` de `xefi/faker-php-laravel`.
- Contrôles d'accès par permission uniquement, jamais par nom de rôle.
- Fichiers de code < 200 lignes : découper en classes dédiées si besoin.
- Avant d'écrire une transition d'état, lire le skill `design-patterns:state` et sa référence Laravel.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Scaffold Laravel à la racine du dépôt et outillage.

- [ ] T001 Créer l'application Laravel (dernière version stable) avec le starter kit Livewire dans un dossier temporaire via Docker (`docker run --rm -v "$PWD":/app -w /app laravelsail/php84-composer composer create-project laravel/livewire-starter-kit tmp-app`), puis déplacer son contenu à la racine du dépôt sans écraser `.specify/`, `specs/`, `.claude/`, `.gitignore` (fusionner les entrées du `.gitignore` Laravel dans `.gitignore`) ni `.gitattributes`, et supprimer `tmp-app/`
- [ ] T002 Installer Sail avec les services `pgsql`, `mailpit` et ajouter un service `soketi` (image `quay.io/soketi/soketi`, port 6001, variables `SOKETI_DEFAULT_APP_ID/KEY/SECRET`) dans `docker-compose.yml` ; configurer `.env` et `.env.example` : `DB_CONNECTION=pgsql`, `BROADCAST_CONNECTION=pusher`, `PUSHER_HOST=soketi`, `PUSHER_PORT=6001`, `PUSHER_SCHEME=http`, `QUEUE_CONNECTION=database`, `APP_LOCALE=fr`, `APP_TIMEZONE=Europe/Paris`
- [ ] T003 Installer les dépendances : `xefi/laravel-osdd`, `spatie/laravel-permission`, `lomkit/laravel-access-control`, `spatie/laravel-activitylog`, `spatie/simple-excel`, `pusher/pusher-php-server` ; en dev : `laravel/boost`, `xefi/faker-php-laravel`, `larastan/larastan`, `xefi/phpstan-xefi-rules` ; côté JS : `laravel-echo`, `pusher-js` (`package.json`)
- [ ] T004 Publier et migrer les configs de `spatie/laravel-permission` et `spatie/laravel-activitylog` (`config/permission.php`, `config/activitylog.php`, migrations dans `database/migrations/`)
- [ ] T005 Lancer `php artisan boost:install` (écrit `CLAUDE.md` à la racine avec les guidelines Laravel) ; vérifier que `CLAUDE.md` mentionne Sail, PostgreSQL, OSDD et la commande de test
- [ ] T006 [P] Créer `phpstan.neon` au niveau 7 minimum, incluant les extensions Larastan et `xefi/phpstan-xefi-rules`, analysant `app/` et `layers/*/src/`
- [ ] T007 [P] Configurer `config/broadcasting.php` (connexion `pusher` lue depuis `PUSHER_*`) et `resources/js/echo.js` (Echo `broadcaster: 'pusher'`, `wsHost`/`wsPort` depuis `VITE_PUSHER_*`, `forceTLS` selon le schéma), importé dans `resources/js/app.js`
- [ ] T008 Créer les layers OSDD `layers/fleet/` et `layers/booking/` (chacun avec `composer.json` LayerManifest, namespace PSR-4, service provider, dossiers `src/`, `database/migrations/`, `database/factories/`, `resources/views/`, `resources/lang/fr/`, `tests/Feature/`, `tests/Unit/`) ; déclarer `booking` dépendant de `fleet`, jamais l'inverse ; enregistrer les suites de tests des layers dans `phpunit.xml` avec PostgreSQL de test
- [ ] T009 Désactiver l'inscription publique du starter kit (supprimer la route et la vue d'inscription dans `routes/auth.php` et `resources/views/livewire/auth/`) et passer l'interface en français (`lang/fr/` via `php artisan lang:publish` + traductions fr)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Agences, catégories, machines, utilisateurs rattachés à une agence, permissions et navigation. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

- [ ] T010 [P] Créer la migration et le modèle `Agency` dans `layers/fleet/database/migrations/` et `layers/fleet/src/Models/Agency.php` : `name` « obligatoire, unique », `address` « facultatif » ; factory `layers/fleet/database/factories/AgencyFactory.php`
- [ ] T011 [P] Créer la migration et le modèle `MachineCategory` dans `layers/fleet/database/migrations/` et `layers/fleet/src/Models/MachineCategory.php` : `name` « obligatoire, unique », `requires_vgp` booléen ; factory associée
- [ ] T012 [P] Créer l'enum `MachineStatus` (`available`, `rented_out`, `workshop`, `out_of_order`, `retired`) avec `label()` traduit dans `layers/fleet/src/Enums/MachineStatus.php`
- [ ] T013 Créer la migration et le modèle `Machine` dans `layers/fleet/database/migrations/` et `layers/fleet/src/Models/Machine.php` : `reference` « obligatoire, unique » (index unique sur la référence normalisée : trim + insensible à la casse), `machine_category_id` et `agency_id` obligatoires (FK sans cascade), `status` string casté `MachineStatus` défaut `available`, `subject_to_vgp` booléen, `vgp_due_date` date nullable ; trait `LogsActivity` sur `status`, `vgp_due_date`, `agency_id` ; factory `layers/fleet/database/factories/MachineFactory.php` (depends on T010–T012)
- [ ] T014 Implémenter la règle « soumise à VGP » dans `layers/fleet/src/Models/Machine.php` : `subject_to_vgp` « forcé à vrai si la catégorie a `requires_vgp` » (positionné à la création et au changement de catégorie, dans l'action d'écriture, pas par observer) et la méthode `isVgpCompliantUntil(CarbonImmutable $endDate): bool` — « conforme si non soumise à VGP, ou si `vgp_due_date >= fin` » ; « soumise sans date renseignée → VGP non à jour » ; test Unit dans `layers/fleet/tests/Unit/MachineVgpComplianceTest.php`
- [ ] T015 Ajouter `agency_id` obligatoire (FK → `agencies`, sans cascade) au modèle `User` : migration dans `database/migrations/`, relation dans `app/Models/User.php`, `UserFactory` mise à jour
- [ ] T016 Créer le seeder des permissions `machines.manage`, `reservations.manage`, `users.manage` et du rôle unique `salarie` qui les porte toutes dans `database/seeders/PermissionSeeder.php` ; ajouter `HasRoles` à `app/Models/User.php`
- [ ] T017 [P] Créer les Controls `lomkit/laravel-access-control` `layers/fleet/src/Controls/MachineControl.php` (perimètre global si `machines.manage`) et brancher `HasControl` sur `Machine`
- [ ] T018 Créer `database/seeders/DatabaseSeeder.php` qui appelle : les 7 agences (`layers/fleet/database/seeders/AgencySeeder.php`), les catégories dont « Nacelle » avec `requires_vgp = true` (`layers/fleet/database/seeders/MachineCategorySeeder.php`), 40 machines de démonstration, `PermissionSeeder`, un salarié par agence avec le rôle `salarie`
- [ ] T019 Créer le canal privé `fleet` dans `routes/channels.php`, autorisé pour tout utilisateur ayant la permission `reservations.manage`
- [ ] T020 Ajouter dans le layout du starter kit (`resources/views/components/layouts/app/sidebar.blade.php`) les entrées de navigation des écrans de [contracts/screens.md](contracts/screens.md) : Disponibilités, Réservations, Parc, Planning, Salariés ; libellés traduits

**Checkpoint**: `sail artisan migrate:fresh --seed` passe ; un salarié se connecte et voit la navigation.

---

## Phase 3: User Story 1 - Réserver une machine disponible sans risque de doublon (Priority: P1) 🎯 MVP

**Goal**: Rechercher une machine disponible sur tout le parc et la réserver ; tout chevauchement est refusé, y compris en concurrence.

**Independent Test**: Créer une réservation, puis tenter une réservation chevauchante de la même machine depuis une autre agence : refusée avec la réservation en conflit indiquée.

### Tests for User Story 1 ⚠️

- [ ] T021 [P] [US1] Test Feature « réservation acceptée puis visible pour toutes les agences » (scénario 1) dans `layers/booking/tests/Feature/CreateReservationTest.php`
- [ ] T022 [P] [US1] Tests Feature « chevauchement refusé avec dates et agence de la réservation en conflit » (scénario 2), « réservation adjacente acceptée » (scénario 3), « une journée en commun = chevauchement », « dates incohérentes refusées (fin avant début, début dans le passé) » dans `layers/booking/tests/Feature/CreateReservationTest.php`
- [ ] T023 [P] [US1] Test Feature « la base refuse une seconde réservation non annulée chevauchante insérée directement, et accepte si la première est `cancelled` » (garantie de la contrainte d'exclusion, scénario 4) dans `layers/booking/tests/Feature/ReservationExclusionConstraintTest.php`
- [ ] T024 [P] [US1] Test Feature de la recherche de disponibilité (filtres catégorie, agence, période ; une machine réservée sur la période n'apparaît pas) dans `layers/booking/tests/Feature/AvailabilitySearchTest.php`

### Implementation for User Story 1

- [ ] T025 [P] [US1] Créer la migration et le modèle `Customer` dans `layers/booking/database/migrations/` et `layers/booking/src/Models/Customer.php` : `name` « obligatoire », `phone` « facultatif », `email` « facultatif, format e-mail », « au moins un moyen de contact (téléphone ou e-mail) est requis » ; factory associée
- [ ] T026 [P] [US1] Créer les enums `ReservationStatus` (`confirmed`, `in_progress`, `closed`, `cancelled`) et `ConflictReason` (`machine_unavailable`, `vgp_expired`, `machine_not_returned`) avec `label()` traduit dans `layers/booking/src/Enums/`
- [ ] T027 [US1] Créer la migration et le modèle `Reservation` dans `layers/booking/database/migrations/` et `layers/booking/src/Models/Reservation.php` : `machine_id`, `customer_id`, `agency_id`, `created_by` obligatoires (FK sans cascade), `start_date` date, `end_date` date « ≥ start_date », `planned_end_date` date, `status` string casté `ReservationStatus` défaut `confirmed`, `departed_at` et `returned_at` datetime nullable, `conflict_reason` string nullable casté `ConflictReason` ; trait `LogsActivity` sur `status`, `start_date`, `end_date` ; factory associée (depends on T025, T026)
- [ ] T028 [US1] Dans la même migration, activer `btree_gist` et ajouter la contrainte « pour une même `machine_id`, aucune paire de réservations dont le statut n'est pas `cancelled` ne peut avoir des plages `[start_date, end_date]` (bornes incluses) qui se chevauchent » : `EXCLUDE USING gist (machine_id WITH =, daterange(start_date, end_date, '[]') WITH &&) WHERE (status <> 'cancelled')` dans `layers/booking/database/migrations/` (depends on T027)
- [ ] T029 [P] [US1] Créer `ReservationControl` dans `layers/booking/src/Controls/ReservationControl.php` (perimètre global si `reservations.manage`) et brancher `HasControl` sur `Reservation`
- [ ] T030 [US1] Implémenter la requête de disponibilité `layers/booking/src/Queries/AvailableMachinesQuery.php` : machines du parc filtrées par catégorie, agence de rattachement et période, excluant celles qui ont une réservation non annulée chevauchant la période (une seule requête SQL, pas de requête par machine)
- [ ] T031 [US1] Créer l'exception typée `ReservationOverlapException` (porte la réservation en conflit : dates, agence) dans `layers/booking/src/Exceptions/ReservationOverlapException.php`, rendue en message traduit
- [ ] T032 [US1] Implémenter l'action `CreateReservation` dans `layers/booking/src/Actions/CreateReservation.php` : transaction, `lockForUpdate()` sur la ligne `Machine`, vérification de chevauchement (lève `ReservationOverlapException`), validation « `start_date` ≥ aujourd'hui », « `end_date` ≥ `start_date` », `planned_end_date = end_date`, `agency_id` = agence de l'auteur, `created_by` = auteur ; dispatche l'événement `ReservationChanged` (créé ici dans `layers/booking/src/Events/ReservationChanged.php`, sans broadcast pour l'instant) (depends on T027, T031)
- [ ] T033 [US1] Mapper la violation de la contrainte d'exclusion (SQLSTATE `23P01`) vers `ReservationOverlapException` dans `bootstrap/app.php` (`withExceptions` → `render`), sans `try/catch`
- [ ] T034 [US1] Créer l'écran Livewire « Disponibilités » (`/disponibilites`) dans `layers/booking/src/Livewire/AvailabilitySearch.php` et `layers/booking/resources/views/livewire/availability-search.blade.php` : filtres catégorie, agence, du / au ; bouton « Réserver » vers la nouvelle réservation pré-remplie (depends on T030)
- [ ] T035 [US1] Créer l'écran Livewire « Nouvelle réservation » (`/reservations/nouvelle?machine=…&du=…&au=…`) dans `layers/booking/src/Livewire/CreateReservationForm.php` et sa vue : choix d'un client existant ou création inline, confirmation, affichage du refus précis (FR-012) (depends on T032)
- [ ] T036 [US1] Enregistrer les routes des écrans booking (middleware `auth` + permission `reservations.manage`) dans `layers/booking/routes/web.php` chargé par le service provider du layer
- [ ] T037 [US1] Ajouter les traductions de la story (libellés, messages de refus) dans `layers/booking/resources/lang/fr/reservations.php`

**Checkpoint**: T021–T024 passent ; MVP démontrable : rechercher, réserver, se voir refuser un chevauchement.

---

## Phase 4: User Story 2 - Empêcher la réservation d'une machine indisponible ou non conforme (Priority: P1)

**Goal**: Refuser la réservation d'une machine à l'atelier, en panne, retirée, ou dont la VGP ne couvre pas toute la période.

**Independent Test**: Passer une machine en atelier puis la réserver : refus. Mettre une VGP qui expire au milieu de la période : refus avec la date d'échéance.

### Tests for User Story 2 ⚠️

- [ ] T038 [P] [US2] Tests Feature « refus si machine `workshop` / `out_of_order` / `retired` avec statut affiché » (scénario 1), « refus pour toute période si la machine est sortie et en retard », « refus si VGP expire avant la fin, date affichée » (scénario 2), « accepté si VGP couvre la période » (scénario 3), « refus si soumise à VGP sans date » (scénario 4) dans `layers/booking/tests/Feature/ReservationEligibilityTest.php`
- [ ] T039 [P] [US2] Tests Unit des transitions de `MachineStatus` (chaque transition légale de [data-model.md](data-model.md) réussit, toute autre lève `IllegalMachineTransitionException`) dans `layers/fleet/tests/Unit/MachineStateTest.php`

### Implementation for User Story 2

- [ ] T040 [US2] Implémenter le pattern State de la machine dans `layers/fleet/src/States/` (une classe par état, interface commune, fabrique keyed sur `MachineStatus`) et l'exception `layers/fleet/src/Exceptions/IllegalMachineTransitionException.php`, avec exactement les transitions : `available → rented_out` (sortie), `rented_out → available` (retour en état), `rented_out → workshop` (retour atelier), `available ↔ workshop`, `available ↔ out_of_order`, `workshop ↔ out_of_order`, `available → retired`, `workshop → retired`, `out_of_order → retired`
- [ ] T041 [US2] Implémenter l'action `ChangeMachineStatus` dans `layers/fleet/src/Actions/ChangeMachineStatus.php` (passe par les classes d'état, dispatche `MachineChanged` créé dans `layers/fleet/src/Events/MachineChanged.php`) et l'action `UpdateMachineVgp` dans `layers/fleet/src/Actions/UpdateMachineVgp.php` (dispatche `MachineChanged`) (depends on T040)
- [ ] T042 [US2] Créer l'exception typée `MachineNotReservableException` (motif : statut ou échéance VGP) dans `layers/booking/src/Exceptions/MachineNotReservableException.php`, rendue en message traduit
- [ ] T043 [US2] Étendre `CreateReservation` dans `layers/booking/src/Actions/CreateReservation.php` : refus si statut `workshop`, `out_of_order` ou `retired` ; refus si `! $machine->isVgpCompliantUntil($endDate)` (« la VGP doit être valide jusqu'à la date de fin incluse ») ; une machine `rented_out` reste réservable pour une période qui ne chevauche pas sa réservation en cours, **sauf si cette réservation est en retard** (`end_date` < aujourd'hui) : alors refus pour toute période, motif « machine pas encore rentrée » (depends on T041, T042)
- [ ] T044 [US2] Exclure de `AvailableMachinesQuery` les machines non réservables (statut, VGP ne couvrant pas la fin de période, machine `rented_out` dont la réservation en cours a une `end_date` passée) dans `layers/booking/src/Queries/AvailableMachinesQuery.php`
- [ ] T045 [US2] Ajouter les traductions des refus statut / VGP dans `layers/booking/resources/lang/fr/reservations.php` et des statuts dans `layers/fleet/resources/lang/fr/machines.php`

**Checkpoint**: T038–T039 passent ; US1 toujours vert.

---

## Phase 5: User Story 3 - Suivre la vie d'une réservation : sortie, retour, annulation (Priority: P2)

**Goal**: Enregistrer sortie, retour (en état / atelier) et annulation ; signaler les réservations devenues impossibles.

**Independent Test**: Créer une réservation, enregistrer la sortie puis le retour « atelier » : réservation clôturée, machine en atelier et non réservable.

### Tests for User Story 3 ⚠️

- [ ] T046 [P] [US3] Tests Feature sortie / retour / annulation (scénarios 1 à 4 de US3) et « sortie bloquée si VGP plus valide jusqu'à `end_date` » (US2 scénario 5), « sortie refusée avant `start_date` », « annulation refusée après sortie », « retour anticipé ramène `end_date` à la date de retour et libère les jours restants », « retour en retard ne modifie pas `end_date` et réussit même si la réservation suivante a déjà commencé » dans `layers/booking/tests/Feature/ReservationLifecycleTest.php`
- [ ] T047 [P] [US3] Tests Unit des transitions de `ReservationStatus` dans `layers/booking/tests/Unit/ReservationStateTest.php`
- [ ] T048 [P] [US3] Tests Feature des conflits : machine passée en atelier / panne avec réservation à venir → `machine_unavailable` ; VGP modifiée ne couvrant plus la période → `vgp_expired` ; réservation en cours non rentrée après `end_date` → la suivante passe `machine_not_returned` ; la réservation n'est jamais supprimée ; levée du conflit quand la cause disparaît, dans `layers/booking/tests/Feature/ReservationConflictsTest.php`

### Implementation for User Story 3

- [ ] T049 [US3] Implémenter le pattern State de la réservation dans `layers/booking/src/States/` et l'exception `layers/booking/src/Exceptions/IllegalReservationTransitionException.php`, avec exactement : `confirmed → in_progress` (sortie), `in_progress → closed` (retour), `confirmed → cancelled` (annulation)
- [ ] T050 [US3] Implémenter `DepartReservation` dans `layers/booking/src/Actions/DepartReservation.php` : « autorisée si `today >= start_date`, machine en `available`, et VGP conforme jusqu'à `end_date` » ; renseigne `departed_at` ; passe la machine en `rented_out` via `ChangeMachineStatus` ; dispatche `ReservationChanged` (depends on T049)
- [ ] T051 [US3] Implémenter `ReturnReservation` dans `layers/booking/src/Actions/ReturnReservation.php` : paramètre « en état » ou « atelier » → machine `available` ou `workshop` ; renseigne `returned_at` ; « si retour avant `end_date`, `end_date` est ramenée à la date de retour » ; si retour en retard, `end_date` n'est jamais repoussée (seul `returned_at` porte la date réelle) ; dispatche `ReservationChanged` (depends on T049)
- [ ] T052 [US3] Implémenter `CancelReservation` dans `layers/booking/src/Actions/CancelReservation.php` : « uniquement depuis `confirmed` » ; dispatche `ReservationChanged` (depends on T049)
- [ ] T053 [US3] Implémenter `RefreshReservationConflicts` dans `layers/booking/src/Actions/RefreshReservationConflicts.php` : pour les réservations `confirmed` d'une machine, positionne ou efface `conflict_reason` (`machine_unavailable`, `vgp_expired`, `machine_not_returned`) ; dispatche `ReservationChanged` pour chaque réservation modifiée
- [ ] T054 [US3] Créer le listener `RefreshConflictsOnMachineChanged` dans `layers/booking/src/Listeners/RefreshConflictsOnMachineChanged.php` écoutant `MachineChanged`, enregistré dans le service provider du layer `booking` (depends on T053)
- [ ] T055 [US3] Créer la commande planifiée quotidienne `booking:flag-late-returns` dans `layers/booking/src/Console/FlagLateReturns.php` (réservations `in_progress` dont `end_date` < aujourd'hui → `RefreshReservationConflicts` sur leur machine) et la planifier dans `routes/console.php`
- [ ] T056 [US3] Créer l'écran Livewire « Liste des réservations » (`/reservations`) dans `layers/booking/src/Livewire/ReservationList.php` et sa vue : filtres statut, agence, période, « en conflit » avec motif affiché
- [ ] T057 [US3] Créer l'écran Livewire « Détail d'une réservation » (`/reservations/{id}`) dans `layers/booking/src/Livewire/ReservationDetail.php` et sa vue : boutons Sortie, Retour (choix en état / atelier), Annuler, affichés selon l'état ; refus précis affichés (depends on T050–T052)
- [ ] T058 [US3] Ajouter les traductions du cycle de vie et des conflits dans `layers/booking/resources/lang/fr/reservations.php`

**Checkpoint**: T046–T048 passent ; US1 et US2 toujours verts.

---

## Phase 6: User Story 4 - Gérer le parc de machines à référence unique (Priority: P2)

**Goal**: Créer, modifier, changer le statut et retirer des machines ; importer le parc depuis un fichier sans doublon.

**Independent Test**: Importer un fichier contenant deux références identiques : la seconde ligne est rejetée et listée avec son motif.

### Tests for User Story 4 ⚠️

- [ ] T059 [P] [US4] Tests Feature « création refusée si référence existante (y compris casse / espaces différents) » (scénario 1), « changement de statut visible » (scénario 3), « retrait refusé si réservations `confirmed` ou `in_progress` », « `subject_to_vgp` forcé pour une catégorie `requires_vgp` » dans `layers/fleet/tests/Feature/ManageMachinesTest.php`
- [ ] T060 [P] [US4] Tests Feature d'import avec un fichier de fixtures (`layers/fleet/tests/Fixtures/fleet-with-duplicates.csv`) : lignes valides créées, rejets listés pour chaque motif de [contracts/import-format.md](contracts/import-format.md), aucune machine existante modifiée (scénario 2) dans `layers/fleet/tests/Feature/ImportFleetTest.php`

### Implementation for User Story 4

- [ ] T061 [US4] Implémenter `CreateMachine` et `UpdateMachine` dans `layers/fleet/src/Actions/` : référence normalisée (trim, unicité insensible à la casse), `subject_to_vgp` forcé selon la catégorie ; dispatchent `MachineChanged`
- [ ] T062 [US4] Implémenter `RetireMachine` dans `layers/fleet/src/Actions/RetireMachine.php` : « refusé si réservations confirmed / in_progress ». `fleet` ne dépend pas de `booking` : exposer dans `fleet` un contrat `MachineRetirementGuard` (`layers/fleet/src/Contracts/MachineRetirementGuard.php`) implémenté dans `booking` (`layers/booking/src/Guards/ActiveReservationsRetirementGuard.php`) et lié dans le service provider de `booking`
- [ ] T063 [US4] Implémenter `ImportFleet` dans `layers/fleet/src/Actions/ImportFleet.php` avec `spatie/simple-excel` selon [contracts/import-format.md](contracts/import-format.md) : CSV `;` UTF-8 ou XLSX, colonnes `reference`, `categorie`, `agence`, `soumise_vgp`, `echeance_vgp` (`JJ/MM/AAAA`), validation ligne à ligne, rapport (créées + rejets avec numéro de ligne, référence, motif), ne modifie jamais une machine existante ; dispatche `FleetImported` (`layers/fleet/src/Events/FleetImported.php`)
- [ ] T064 [US4] Créer l'écran Livewire « Parc de machines » (`/machines`) dans `layers/fleet/src/Livewire/MachineIndex.php`, `layers/fleet/src/Livewire/MachineForm.php` et leurs vues : liste filtrable, création, modification, changement de statut (seules les transitions légales proposées), mise à jour VGP, retrait
- [ ] T065 [US4] Créer l'écran Livewire « Import du parc » (`/machines/import`) dans `layers/fleet/src/Livewire/ImportFleetForm.php` et sa vue : téléversement, rapport des lignes rejetées
- [ ] T066 [US4] Enregistrer les routes `fleet` (middleware `auth` + permission `machines.manage`) dans `layers/fleet/routes/web.php` et ajouter les traductions dans `layers/fleet/resources/lang/fr/machines.php`

**Checkpoint**: T059–T060 passent ; toutes les stories précédentes vertes.

---

## Phase 7: User Story 5 - Voir la disponibilité de tout le parc depuis n'importe quelle agence (Priority: P3)

**Goal**: Planning partagé et mise à jour en temps réel de tous les écrans.

**Independent Test**: Une réservation créée par l'agence A apparaît sur le planning de l'agence B sans recharger.

### Tests for User Story 5 ⚠️

- [ ] T067 [P] [US5] Tests Feature broadcasting (`Event::fake` / `Broadcast` assertions) : `reservation.changed` et `machine.changed` émis sur le canal privé `fleet` avec exactement les payloads de [contracts/broadcast-events.md](contracts/broadcast-events.md) ; canal refusé à un utilisateur sans `reservations.manage` dans `layers/booking/tests/Feature/BroadcastingTest.php`
- [ ] T068 [P] [US5] Test Feature du planning (filtres catégorie / agence / période ; réservations et indisponibilités atelier, panne, VGP non valide affichées par machine) dans `layers/booking/tests/Feature/PlanningTest.php`

### Implementation for User Story 5

- [ ] T069 [US5] Rendre `ReservationChanged` et `MachineChanged` diffusables : `ShouldBroadcast`, `broadcastOn()` → `PrivateChannel('fleet')`, `broadcastAs()` → `reservation.changed` / `machine.changed`, `broadcastWith()` = allow-list de [contracts/broadcast-events.md](contracts/broadcast-events.md) ; idem `FleetImported` → `fleet.imported` `{ created_count }` (fichiers `layers/booking/src/Events/ReservationChanged.php`, `layers/fleet/src/Events/MachineChanged.php`, `layers/fleet/src/Events/FleetImported.php`)
- [ ] T070 [US5] Ajouter un worker de queue au `docker-compose.yml` (service Sail exécutant `php artisan queue:work`) pour le broadcasting
- [ ] T071 [US5] Créer l'écran Livewire « Planning » (`/planning`) dans `layers/booking/src/Livewire/Planning.php` et sa vue : une ligne par machine, jours en colonnes sur la période filtrée, réservations et indisponibilités colorées, requêtes groupées (pas de requête par machine)
- [ ] T072 [US5] Brancher l'écoute temps réel (`#[On('echo-private:fleet,.reservation.changed')]`, `.machine.changed`, `.fleet.imported`) sur `AvailabilitySearch`, `ReservationList`, `MachineIndex` et `Planning` dans leurs classes Livewire respectives

**Checkpoint**: T067–T068 passent ; vérification manuelle n°1 du [quickstart.md](quickstart.md) en deux navigateurs.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [ ] T073 Créer l'écran Livewire « Salariés » (`/salaries`) dans `app/Livewire/Users/UserIndex.php` et sa vue : créer un compte (nom, e-mail unique, agence, rôle `salarie`), désactiver un compte (migration `deactivated_at` nullable sur `users` ; connexion refusée pour un compte désactivé) ; permission `users.manage` ; test Feature dans `tests/Feature/ManageUsersTest.php`
- [ ] T074 [P] Vérifier que chaque création, sortie, retour, annulation et changement de statut produit une entrée d'historique avec auteur et date (FR-022) : test Feature dans `layers/booking/tests/Feature/ActivityLogTest.php`
- [ ] T075 [P] Lancer `./vendor/bin/sail php vendor/bin/phpstan analyse` et corriger toutes les erreurs dans `app/` et `layers/*/src/`
- [ ] T076 [P] Vérifier qu'aucun fichier de code de `app/` et `layers/*/src/` ne dépasse 200 lignes ; découper sinon
- [ ] T077 Lancer `./vendor/bin/sail artisan test` : toute la suite doit passer
- [ ] T078 Dérouler les vérifications manuelles 1 à 8 de [quickstart.md](quickstart.md) et corriger les écarts

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)** : aucune dépendance.
- **Foundational (Phase 2)** : dépend de la phase 1 ; bloque toutes les stories.
- **US1 (Phase 3)** : dépend de la phase 2.
- **US2 (Phase 4)** : dépend de US1 (étend `CreateReservation` et `AvailableMachinesQuery`).
- **US3 (Phase 5)** : dépend de US2 (utilise `ChangeMachineStatus`, `MachineChanged`, la règle VGP).
- **US4 (Phase 6)** : dépend de la phase 2 et de US2 (`ChangeMachineStatus`) ; T062 dépend du modèle `Reservation` (US1). Peut se faire en parallèle de US3.
- **US5 (Phase 7)** : dépend des événements de US1–US4.
- **Polish (Phase 8)** : après les stories voulues.

### Story Completion Order

```text
Setup → Foundational → US1 → US2 ─┬─► US3 ─┬─► US5 → Polish
                                  └─► US4 ─┘
```

### Within Each User Story

- Tests d'abord, en échec avant implémentation.
- Enums / modèles → requêtes / actions → écrans Livewire → routes et traductions.

### Parallel Opportunities

- Phase 1 : T006 et T007 en parallèle après T003.
- Phase 2 : T010, T011, T012 en parallèle ; T017 en parallèle de T015–T016.
- Dans chaque story : toutes les tâches de test [P] en parallèle.
- US3 et US4 en parallèle une fois US2 terminée.

## Parallel Example: User Story 1

```text
# Tests de US1, en parallèle :
T021 + T022 (CreateReservationTest.php — même fichier, un seul agent)
T023 (ReservationExclusionConstraintTest.php)
T024 (AvailabilitySearchTest.php)

# Modèles de US1, en parallèle :
T025 (Customer), T026 (enums), T029 (ReservationControl)
```

## Parallel Example: User Story 3 + User Story 4

```text
# Après US2, deux flux indépendants :
Flux A : T046 → T058 (cycle de vie et conflits, layer booking)
Flux B : T059 → T066 (parc et import, layer fleet)
```

## Implementation Strategy

### MVP First

1. Phase 1 + Phase 2.
2. Phase 3 (US1) → démontrer : rechercher, réserver, refus de chevauchement.
3. Phase 4 (US2) → les deux P1 forment le premier livrable montrable au client.

### Incremental Delivery

1. Setup + Foundational → socle.
2. US1 + US2 (P1) → démo client : plus de doubles réservations ni de machines non conformes réservées.
3. US3 + US4 (P2) → sorties / retours et parc complet importé : utilisable en agence.
4. US5 (P3) → planning temps réel.
5. Polish → écran Salariés, historique, qualité.

## Notes

- `[P]` = fichiers différents, aucune dépendance sur une tâche non terminée.
- Committer après chaque tâche ou groupe logique, sans mention d'IA dans les messages.
- S'arrêter à chaque checkpoint pour valider la story indépendamment.
