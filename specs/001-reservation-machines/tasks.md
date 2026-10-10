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
- Code en anglais ; tout texte affiché passe par `functional/<layer>/resources/lang/fr/*.php` (ou `lang/fr/*.php` pour `app/`).
- Statuts : colonne `string` + cast vers un enum PHP backed ; jamais d'enum en base.
- Aucune suppression en cascade en base (`->constrained()` sans `cascadeOnDelete()`), aucun observer, aucun `try/catch`, aucun commentaire de code.
- Factories avec le helper `faker()` de `xefi/faker-php-laravel`.
- Contrôles d'accès par permission uniquement, jamais par nom de rôle.
- Fichiers de code < 200 lignes : découper en classes dédiées si besoin.
- Avant d'écrire une transition d'état, lire le skill `design-patterns:state` et sa référence Laravel.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Scaffold Laravel à la racine du dépôt et outillage.

- [x] T001 Créer l'application Laravel (dernière version stable) avec le starter kit Livewire dans un dossier temporaire via Docker (`docker run --rm -v "$PWD":/app -w /app laravelsail/php84-composer composer create-project laravel/livewire-starter-kit tmp-app`), puis déplacer son contenu à la racine du dépôt sans écraser `.specify/`, `specs/`, `.claude/`, `.gitignore` (fusionner les entrées du `.gitignore` Laravel dans `.gitignore`) ni `.gitattributes`, et supprimer `tmp-app/`
- [x] T002 Installer Sail avec les services `pgsql`, `mailpit` et ajouter un service `soketi` (image `quay.io/soketi/soketi`, port 6001, variables `SOKETI_DEFAULT_APP_ID/KEY/SECRET`) dans `compose.yaml` ; configurer `.env` et `.env.example` : `DB_CONNECTION=pgsql`, `BROADCAST_CONNECTION=pusher`, `PUSHER_HOST=soketi`, `PUSHER_PORT=6001`, `PUSHER_SCHEME=http`, `QUEUE_CONNECTION=database`, `APP_LOCALE=fr`, `APP_TIMEZONE=Europe/Paris`
- [x] T003 Installer les dépendances : `xefi/laravel-osdd`, `spatie/laravel-permission`, `lomkit/laravel-access-control`, `spatie/laravel-activitylog`, `spatie/simple-excel`, `pusher/pusher-php-server` ; en dev : `laravel/boost`, `xefi/faker-php-laravel`, `larastan/larastan`, `xefi/phpstan-xefi-rules` ; côté JS : `laravel-echo`, `pusher-js` (`package.json`)
- [x] T004 Publier et migrer les configs de `spatie/laravel-permission` et `spatie/laravel-activitylog` (`config/permission.php`, `config/activitylog.php`, migrations dans `database/migrations/`)
- [x] T005 Lancer `php artisan boost:install` (écrit `CLAUDE.md` à la racine avec les guidelines Laravel) ; vérifier que `CLAUDE.md` mentionne Sail, PostgreSQL, OSDD et la commande de test
- [x] T006 [P] Créer `phpstan.neon` au niveau 7 minimum, incluant les extensions Larastan et `xefi/phpstan-xefi-rules`, analysant `app/` et `functional/*/src/`
- [x] T007 [P] Configurer `config/broadcasting.php` (connexion `pusher` lue depuis `PUSHER_*`) et `resources/js/echo.js` (Echo `broadcaster: 'pusher'`, `wsHost`/`wsPort` depuis `VITE_PUSHER_*`, `forceTLS` selon le schéma), importé dans `resources/js/app.js`
- [x] T008 Créer les layers OSDD `functional/fleet/` et `functional/booking/` (chacun avec `composer.json` LayerManifest, namespace PSR-4, service provider, dossiers `src/`, `database/migrations/`, `database/factories/`, `resources/views/`, `resources/lang/fr/`, `tests/Feature/`, `tests/Unit/`) ; déclarer `booking` dépendant de `fleet`, jamais l'inverse ; enregistrer les suites de tests des layers dans `phpunit.xml` avec PostgreSQL de test
- [x] T009 Désactiver l'inscription publique du starter kit (retirer `Features::registration()` de `config/fortify.php` ; vues d'authentification dans `resources/views/pages/auth/`) et passer l'interface en français (`lang/fr/` via `php artisan lang:publish` + traductions fr)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Agences, catégories, machines, utilisateurs rattachés à une agence, permissions et navigation. Requis par toutes les stories.

**⚠️ CRITICAL**: Aucune story ne démarre avant la fin de cette phase.

- [x] T010 [P] Créer la migration et le modèle `Agency` dans `functional/fleet/database/migrations/` et `functional/fleet/src/Models/Agency.php` : `name` « obligatoire, unique », `address` « facultatif » ; factory `functional/fleet/database/factories/AgencyFactory.php`
- [x] T011 [P] Créer la migration et le modèle `MachineCategory` dans `functional/fleet/database/migrations/` et `functional/fleet/src/Models/MachineCategory.php` : `name` « obligatoire, unique », `is_vgp_required` booléen ; factory associée
- [x] T012 [P] Créer l'enum `MachineStatus` (`available`, `rented_out`, `workshop`, `out_of_order`, `retired`) avec `label()` traduit dans `functional/fleet/src/Enums/MachineStatus.php`
- [x] T013 Créer la migration et le modèle `Machine` dans `functional/fleet/database/migrations/` et `functional/fleet/src/Models/Machine.php` : `reference` « obligatoire, unique » (index unique sur la référence normalisée : trim + insensible à la casse), `machine_category_id` et `agency_id` obligatoires (FK sans cascade), `status` string casté `MachineStatus` défaut `available`, `is_subject_to_vgp` booléen, `vgp_due_date` date nullable ; trait `LogsActivity` sur `status`, `vgp_due_date`, `agency_id` ; factory `functional/fleet/database/factories/MachineFactory.php` (depends on T010–T012)
- [x] T014 Implémenter la règle « soumise à VGP » dans `functional/fleet/src/Models/Machine.php` : `is_subject_to_vgp` « forcé à vrai si la catégorie a `is_vgp_required` » (positionné à la création et au changement de catégorie, dans l'action d'écriture, pas par observer) et la méthode `isVgpCompliantUntil(CarbonImmutable $endDate): bool` — « conforme si non soumise à VGP, ou si `vgp_due_date >= fin` » ; « soumise sans date renseignée → VGP non à jour » ; test Unit dans `functional/fleet/tests/Unit/MachineVgpComplianceTest.php` — forçage à l'écriture livré par T061 (`CreateMachine`, `UpdateMachine`) et l'import (T063)
- [x] T015 Ajouter `agency_id` obligatoire (FK → `agencies`, sans cascade) au modèle `User` : migration dans `database/migrations/`, relation dans `app/Models/User.php`, `UserFactory` mise à jour
- [x] T016 Créer le seeder des permissions `machines.manage`, `reservations.manage`, `users.manage` et du rôle unique `salarie` qui les porte toutes dans `database/seeders/PermissionSeeder.php` ; ajouter `HasRoles` à `app/Models/User.php` — depuis la constitution (principe V) : chaque layer déclare ses permissions (`functional/fleet/database/seeders/FleetPermissionSeeder.php`, `functional/booking/database/seeders/BookingPermissionSeeder.php`) ; `PermissionSeeder` garde `users.manage` et le rôle, et appelle les seeders des layers
- [x] T017 [P] Créer les Controls `lomkit/laravel-access-control` `functional/fleet/src/Access/Controls/MachineControl.php` (perimètre global si `machines.manage`) et brancher `HasControl` sur `Machine`
- [x] T018 Créer `database/seeders/DatabaseSeeder.php` qui appelle : `functional/fleet/database/seeders/FleetSeeder.php` (les 7 agences, les catégories dont « Nacelle » avec `is_vgp_required = true`, 40 machines de démonstration), `PermissionSeeder`, un salarié par agence avec le rôle `salarie`
- [x] T019 Créer le canal privé `fleet` dans `functional/fleet/routes/channels.php`, autorisé pour tout utilisateur ayant la permission `reservations.manage`
- [X] T020 Ajouter dans le layout du starter kit (`resources/views/layouts/app/sidebar.blade.php`) les entrées de navigation des écrans de [contracts/screens.md](contracts/screens.md) : Disponibilités, Réservations, Parc, Planning, Salariés ; libellés traduits — fichier réel `resources/views/layouts/app/sidebar.blade.php` ; chaque groupe affiché selon sa permission

**Checkpoint**: `sail artisan migrate:fresh --seed` passe ; un salarié se connecte et voit la navigation.

---

## Phase 3: User Story 1 - Réserver une machine disponible sans risque de doublon (Priority: P1) 🎯 MVP

**Goal**: Rechercher une machine disponible sur tout le parc et la réserver ; tout chevauchement est refusé, y compris en concurrence.

**Independent Test**: Créer une réservation, puis tenter une réservation chevauchante de la même machine depuis une autre agence : refusée avec la réservation en conflit indiquée.

### Tests for User Story 1 ⚠️

- [x] T021 [P] [US1] Test Feature « réservation acceptée puis visible pour toutes les agences » (scénario 1) dans `functional/booking/tests/Feature/CreateReservationTest.php`
- [x] T022 [P] [US1] Tests Feature « chevauchement refusé avec dates et agence de la réservation en conflit » (scénario 2), « réservation adjacente acceptée » (scénario 3), « une journée en commun = chevauchement », « dates incohérentes refusées (fin avant début, début dans le passé) » dans `functional/booking/tests/Feature/CreateReservationTest.php`
- [x] T023 [P] [US1] Test Feature « la base refuse une seconde réservation non annulée chevauchante insérée directement, et accepte si la première est `cancelled` » (garantie de la contrainte d'exclusion, scénario 4) dans `functional/booking/tests/Feature/ReservationExclusionConstraintTest.php`
- [x] T024 [P] [US1] Test Feature de la recherche de disponibilité (filtres catégorie, agence, période ; une machine réservée sur la période n'apparaît pas) dans `functional/booking/tests/Feature/AvailabilitySearchTest.php`

### Implementation for User Story 1

- [x] T025 [P] [US1] Créer la migration et le modèle `Customer` dans `functional/booking/database/migrations/` et `functional/booking/src/Models/Customer.php` : `name` « obligatoire », `phone` « facultatif », `email` « facultatif, format e-mail », « au moins un moyen de contact (téléphone ou e-mail) est requis » ; factory associée
- [x] T026 [P] [US1] Créer les enums `ReservationStatus` (`confirmed`, `in_progress`, `closed`, `cancelled`) et `ConflictReason` (`machine_unavailable`, `vgp_expired`, `machine_not_returned`) avec `label()` traduit dans `functional/booking/src/Enums/`
- [x] T027 [US1] Créer la migration et le modèle `Reservation` dans `functional/booking/database/migrations/` et `functional/booking/src/Models/Reservation.php` : `machine_id`, `customer_id`, `agency_id`, `created_by` obligatoires (FK sans cascade), `start_date` date, `end_date` date « ≥ start_date », `planned_end_date` date, `status` string casté `ReservationStatus` défaut `confirmed`, `departed_at` et `returned_at` datetime nullable, `conflict_reason` string nullable casté `ConflictReason` ; trait `LogsActivity` sur `status`, `start_date`, `end_date` ; factory associée (depends on T025, T026)
- [x] T028 [US1] Dans la même migration, activer `btree_gist` et ajouter la contrainte « pour une même `machine_id`, aucune paire de réservations dont le statut n'est pas `cancelled` ne peut avoir des plages `[start_date, end_date]` (bornes incluses) qui se chevauchent » : `EXCLUDE USING gist (machine_id WITH =, daterange(start_date, end_date, '[]') WITH &&) WHERE (status <> 'cancelled')` dans `functional/booking/database/migrations/` (depends on T027)
- [x] T029 [P] [US1] Créer `ReservationControl` dans `functional/booking/src/Access/Controls/ReservationControl.php` (perimètre global si `reservations.manage`) et brancher `HasControl` sur `Reservation`
- [x] T030 [US1] Implémenter la requête de disponibilité `functional/booking/src/Queries/AvailableMachinesQuery.php` : machines du parc filtrées par catégorie, agence de rattachement et période, excluant celles qui ont une réservation non annulée chevauchant la période (une seule requête SQL, pas de requête par machine)
- [x] T031 [US1] Créer l'exception typée `ReservationOverlapException` (porte la réservation en conflit : dates, agence) dans `functional/booking/src/Exceptions/ReservationOverlapException.php`, rendue en message traduit
- [x] T032 [US1] Implémenter l'action `CreateReservation` dans `functional/booking/src/Actions/CreateReservation.php` : transaction, `lockForUpdate()` sur la ligne `Machine`, vérification de chevauchement (lève `ReservationOverlapException`), validation « `start_date` ≥ aujourd'hui », « `end_date` ≥ `start_date` », `planned_end_date = end_date`, `agency_id` = agence de l'auteur, `created_by` = auteur ; dispatche l'événement `ReservationChanged` (créé ici dans `functional/booking/src/Events/ReservationChanged.php`, sans broadcast pour l'instant) (depends on T027, T031)
- [x] T033 [US1] Traduire la violation de la contrainte d'exclusion (SQLSTATE `23P01`) en `ReservationOverlapException` dans `CreateReservation` via `rescue()`, sans `try/catch` (voir research R3 : Livewire intercepte les exceptions avant `bootstrap/app.php`)
- [x] T034 [US1] Créer l'écran Livewire « Disponibilités » (`/disponibilites`) dans `functional/booking/src/Livewire/AvailabilitySearch.php` et `functional/booking/resources/views/livewire/availability-search.blade.php` : filtres catégorie, agence, du / au ; bouton « Réserver » vers la nouvelle réservation pré-remplie (depends on T030)
- [x] T035 [US1] Créer l'écran Livewire « Nouvelle réservation » (`/reservations/nouvelle?machine=…&du=…&au=…`) dans `functional/booking/src/Livewire/CreateReservationForm.php` et sa vue : choix d'un client existant ou création inline, confirmation, affichage du refus précis (FR-012) (depends on T032)
- [x] T036 [US1] Enregistrer les routes des écrans booking (middleware `auth` + permission `reservations.manage`) dans `functional/booking/routes/web.php` chargé par le service provider du layer
- [x] T037 [US1] Ajouter les traductions de la story (libellés, messages de refus) dans `functional/booking/resources/lang/fr/reservations.php`

**Checkpoint**: T021–T024 passent ; MVP démontrable : rechercher, réserver, se voir refuser un chevauchement.

---

## Phase 4: User Story 2 - Empêcher la réservation d'une machine indisponible ou non conforme (Priority: P1)

**Goal**: Refuser la réservation d'une machine à l'atelier, en panne, retirée, ou dont la VGP ne couvre pas toute la période.

**Independent Test**: Passer une machine en atelier puis la réserver : refus. Mettre une VGP qui expire au milieu de la période : refus avec la date d'échéance.

### Tests for User Story 2 ⚠️

- [x] T038 [P] [US2] Tests Feature « refus si machine `workshop` / `out_of_order` / `retired` avec statut affiché » (scénario 1), « refus pour toute période si la machine est sortie et en retard », « refus si VGP expire avant la fin, date affichée » (scénario 2), « accepté si VGP couvre la période » (scénario 3), « refus si soumise à VGP sans date » (scénario 4) dans `functional/booking/tests/Feature/ReservationEligibilityTest.php`
- [x] T039 [P] [US2] Tests Unit des transitions de `MachineStatus` (chaque transition légale de [data-model.md](data-model.md) réussit, toute autre lève `IllegalMachineTransitionException`) dans `functional/fleet/tests/Unit/MachineStateTest.php`

### Implementation for User Story 2

- [x] T040 [US2] Implémenter le pattern State de la machine dans `functional/fleet/src/States/` (une classe par état, interface commune, fabrique keyed sur `MachineStatus`) et l'exception `functional/fleet/src/Exceptions/IllegalMachineTransitionException.php`, avec exactement les transitions : `available → rented_out` (sortie), `rented_out → available` (retour en état), `rented_out → workshop` (retour atelier), `available ↔ workshop`, `available ↔ out_of_order`, `workshop ↔ out_of_order`, `available → retired`, `workshop → retired`, `out_of_order → retired`
- [x] T041 [US2] Implémenter l'action `ChangeMachineStatus` dans `functional/fleet/src/Actions/ChangeMachineStatus.php` (passe par les classes d'état, dispatche `MachineChanged` créé dans `functional/fleet/src/Events/MachineChanged.php`) et l'action `UpdateMachineVgp` dans `functional/fleet/src/Actions/UpdateMachineVgp.php` (dispatche `MachineChanged`) (depends on T040)
- [x] T042 [US2] Créer l'exception typée `MachineNotReservableException` (motif : statut ou échéance VGP) dans `functional/booking/src/Exceptions/MachineNotReservableException.php`, rendue en message traduit
- [x] T043 [US2] Étendre `CreateReservation` dans `functional/booking/src/Actions/CreateReservation.php` : refus si statut `workshop`, `out_of_order` ou `retired` ; refus si `! $machine->isVgpCompliantUntil($endDate)` (« la VGP doit être valide jusqu'à la date de fin incluse ») ; une machine `rented_out` reste réservable pour une période qui ne chevauche pas sa réservation en cours, **sauf si cette réservation est en retard** (`end_date` < aujourd'hui) : alors refus pour toute période, motif « machine pas encore rentrée » (depends on T041, T042)
- [x] T044 [US2] Exclure de `AvailableMachinesQuery` les machines non réservables (statut, VGP ne couvrant pas la fin de période, machine `rented_out` dont la réservation en cours a une `end_date` passée) dans `functional/booking/src/Queries/AvailableMachinesQuery.php`
- [x] T045 [US2] Ajouter les traductions des refus statut / VGP dans `functional/booking/resources/lang/fr/reservations.php` et des statuts dans `functional/fleet/resources/lang/fr/machines.php`

**Checkpoint**: T038–T039 passent ; US1 toujours vert.

---

## Phase 5: User Story 3 - Suivre la vie d'une réservation : sortie, retour, annulation (Priority: P2)

**Goal**: Enregistrer sortie, retour (en état / atelier) et annulation ; signaler les réservations devenues impossibles.

**Independent Test**: Créer une réservation, enregistrer la sortie puis le retour « atelier » : réservation clôturée, machine en atelier et non réservable.

### Tests for User Story 3 ⚠️

- [x] T046 [P] [US3] Tests Feature sortie / retour / annulation (scénarios 1 à 4 de US3) et « sortie bloquée si VGP plus valide jusqu'à `end_date` » (US2 scénario 5), « sortie refusée avant `start_date` », « annulation refusée après sortie », « retour anticipé ramène `end_date` à la date de retour et libère les jours restants », « retour en retard ne modifie pas `end_date` et réussit même si la réservation suivante a déjà commencé » dans `functional/booking/tests/Feature/ReservationLifecycleTest.php`
- [x] T047 [P] [US3] Tests Unit des transitions de `ReservationStatus` dans `functional/booking/tests/Unit/ReservationStateTest.php`
- [x] T048 [P] [US3] Tests Feature des conflits : machine passée en atelier / panne avec réservation à venir → `machine_unavailable` ; VGP modifiée ne couvrant plus la période → `vgp_expired` ; réservation en cours non rentrée après `end_date` → la suivante passe `machine_not_returned` ; la réservation n'est jamais supprimée ; levée du conflit quand la cause disparaît, dans `functional/booking/tests/Feature/ReservationConflictsTest.php`

### Implementation for User Story 3

- [x] T049 [US3] Implémenter le pattern State de la réservation dans `functional/booking/src/States/` et l'exception `functional/booking/src/Exceptions/IllegalReservationTransitionException.php`, avec exactement : `confirmed → in_progress` (sortie), `in_progress → closed` (retour), `confirmed → cancelled` (annulation)
- [x] T050 [US3] Implémenter `DepartReservation` dans `functional/booking/src/Actions/DepartReservation.php` : « autorisée si `today >= start_date`, machine en `available`, et VGP conforme jusqu'à `end_date` » ; renseigne `departed_at` ; passe la machine en `rented_out` via `ChangeMachineStatus` ; dispatche `ReservationChanged` (depends on T049)
- [x] T051 [US3] Implémenter `ReturnReservation` dans `functional/booking/src/Actions/ReturnReservation.php` : paramètre « en état » ou « atelier » → machine `available` ou `workshop` ; renseigne `returned_at` ; « si retour avant `end_date`, `end_date` est ramenée à la date de retour » ; si retour en retard, `end_date` n'est jamais repoussée (seul `returned_at` porte la date réelle) ; dispatche `ReservationChanged` (depends on T049)
- [x] T052 [US3] Implémenter `CancelReservation` dans `functional/booking/src/Actions/CancelReservation.php` : « uniquement depuis `confirmed` » ; dispatche `ReservationChanged` (depends on T049)
- [x] T053 [US3] Implémenter `RefreshReservationConflicts` dans `functional/booking/src/Actions/RefreshReservationConflicts.php` : pour les réservations `confirmed` d'une machine, positionne ou efface `conflict_reason` (`machine_unavailable`, `vgp_expired`, `machine_not_returned`) ; dispatche `ReservationChanged` pour chaque réservation modifiée
- [x] T054 [US3] Créer le listener `RefreshConflictsOnMachineChanged` dans `functional/booking/src/Listeners/RefreshConflictsOnMachineChanged.php` écoutant `MachineChanged`, enregistré dans le service provider du layer `booking` (depends on T053)
- [x] T055 [US3] Créer la commande planifiée quotidienne `booking:flag-late-returns` dans `functional/booking/src/Console/FlagLateReturns.php` (réservations `in_progress` dont `end_date` < aujourd'hui → `RefreshReservationConflicts` sur leur machine) et la planifier dans `functional/booking/routes/console.php`
- [x] T056 [US3] Créer l'écran Livewire « Liste des réservations » (`/reservations`) dans `functional/booking/src/Livewire/ReservationList.php` et sa vue : filtres statut, agence, période, « en conflit » avec motif affiché
- [x] T057 [US3] Créer l'écran Livewire « Détail d'une réservation » (`/reservations/{id}`) dans `functional/booking/src/Livewire/ReservationDetail.php` et sa vue : boutons Sortie, Retour (choix en état / atelier), Annuler, affichés selon l'état ; refus précis affichés (depends on T050–T052)
- [x] T058 [US3] Ajouter les traductions du cycle de vie et des conflits dans `functional/booking/resources/lang/fr/reservations.php`

### Points d'extension pour la feature 002 (repris de `specs/002-photos-qr-code/tasks.md` T007–T012, research P2 / P3)

- [x] T079 [US3] Créer l'interface `ReservationTransitionGuard` dans `functional/booking/src/Contracts/ReservationTransitionGuard.php` avec `beforeDeparture(Reservation $reservation): void` et `beforeReturn(Reservation $reservation): void` (docblock : lève une exception de domaine pour refuser) — 002 T007
- [x] T080 [US3] Créer le registre `ReservationTransitionGuards` (singleton, `register(class-string<ReservationTransitionGuard>)`, `all()`) dans `functional/booking/src/Extensions/ReservationTransitionGuards.php`, lié dans le service provider de `booking` — 002 T008
- [x] T081 [US3] Appeler `beforeDeparture()` de chaque guard enregistré dans `DepartReservation` et `beforeReturn()` dans `ReturnReservation`, **dans la transaction et avant tout changement d'état** (depends on T080, T050, T051) — 002 T009
- [x] T082 [US3] Test Feature `functional/booking/tests/Feature/ReservationTransitionGuardsTest.php` : un guard de test qui refuse bloque la sortie et le retour, sans modifier la réservation ni la machine ; sans guard enregistré, le comportement est inchangé (depends on T081) — 002 T010
- [x] T083 [US3] Créer le registre `ReservationDetailSections` (`register(string $livewireComponent, int $position)`, `all()` trié) dans `functional/booking/src/Extensions/ReservationDetailSections.php`, lié dans le service provider de `booking` ; rendre chaque section enregistrée dans la vue de `ReservationDetail` via `<livewire:dynamic-component :component="…" :reservation="$reservation" :key="…" />` (depends on T057) — 002 T011
- [x] T084 [US3] Dans `ReservationDetail` : afficher le message de toute exception de domaine levée par un guard comme refus de l'action (même rendu que les autres refus) ; écouter l'événement Livewire `reservation-transition-readiness` `{ step, is_ready }` et désactiver « Enregistrer la sortie » (`step = departure`) ou « Enregistrer le retour » (`step = return`) tant qu'aucune section enregistrée n'a répondu `is_ready = true` pour cette étape (état suivi par étape, pas par section : clarification 2026-10-10) ; sans section enregistrée, les boutons restent actifs. Le refus serveur reste la seule garantie (depends on T081, T083) — 002 T012

**Checkpoint**: T046–T048 et T082 passent ; US1 et US2 toujours verts.

---

## Phase 6: User Story 4 - Gérer le parc de machines à référence unique (Priority: P2)

**Goal**: Créer, modifier, changer le statut et retirer des machines ; importer le parc depuis un fichier sans doublon.

**Independent Test**: Importer un fichier contenant deux références identiques : la seconde ligne est rejetée et listée avec son motif.

### Tests for User Story 4 ⚠️

- [X] T059 [P] [US4] Tests Feature « création refusée si référence existante (y compris casse / espaces différents) » (scénario 1), « changement de statut visible » (scénario 3), « retrait refusé si réservations `confirmed` ou `in_progress` », « `is_subject_to_vgp` forcé pour une catégorie `is_vgp_required` » dans `functional/fleet/tests/Feature/ManageMachinesTest.php`
- [X] T060 [P] [US4] Tests Feature d'import avec un fichier de fixtures (`functional/fleet/tests/Fixtures/fleet-with-duplicates.csv`) : lignes valides créées, rejets listés pour chaque motif de [contracts/import-format.md](contracts/import-format.md), aucune machine existante modifiée (scénario 2) dans `functional/fleet/tests/Feature/ImportFleetTest.php`

### Implementation for User Story 4

- [X] T061 [US4] Implémenter `CreateMachine` et `UpdateMachine` dans `functional/fleet/src/Actions/` : référence normalisée (trim, unicité insensible à la casse), `is_subject_to_vgp` forcé selon la catégorie ; dispatchent `MachineChanged`
- [X] T062 [US4] Implémenter `RetireMachine` dans `functional/fleet/src/Actions/RetireMachine.php` : « refusé si réservations confirmed / in_progress ». `fleet` ne dépend pas de `booking` : exposer dans `fleet` un contrat `MachineRetirementGuard` (`functional/fleet/src/Contracts/MachineRetirementGuard.php`) implémenté dans `booking` (`functional/booking/src/Guards/ActiveReservationsRetirementGuard.php`) et lié dans le service provider de `booking`
- [X] T063 [US4] Implémenter `ImportFleet` dans `functional/fleet/src/Actions/ImportFleet.php` avec `spatie/simple-excel` selon [contracts/import-format.md](contracts/import-format.md) : CSV `;` UTF-8 ou XLSX, colonnes `reference`, `categorie`, `agence`, `soumise_vgp`, `echeance_vgp` (`JJ/MM/AAAA`), validation ligne à ligne, rapport (créées + rejets avec numéro de ligne, référence, motif), ne modifie jamais une machine existante ; dispatche `FleetImported` (`functional/fleet/src/Events/FleetImported.php`)
- [X] T064 [US4] Créer l'écran Livewire « Parc de machines » (`/machines`) dans `functional/fleet/src/Livewire/MachineIndex.php`, `functional/fleet/src/Livewire/MachineForm.php` et leurs vues : liste filtrable, création, modification, changement de statut (seules les transitions légales proposées), mise à jour VGP, retrait
- [X] T065 [US4] Créer l'écran Livewire « Import du parc » (`/machines/import`) dans `functional/fleet/src/Livewire/ImportFleetForm.php` et sa vue : téléversement, rapport des lignes rejetées
- [X] T066 [US4] Enregistrer les routes `fleet` (middleware `auth` + permission `machines.manage`) dans `functional/fleet/routes/web.php` et ajouter les traductions dans `functional/fleet/resources/lang/fr/machines.php`

**Checkpoint**: T059–T060 passent ; toutes les stories précédentes vertes.

---

## Phase 7: User Story 5 - Voir la disponibilité de tout le parc depuis n'importe quelle agence (Priority: P3)

**Goal**: Planning partagé et mise à jour en temps réel de tous les écrans.

**Independent Test**: Une réservation créée par l'agence A apparaît sur le planning de l'agence B sans recharger.

### Tests for User Story 5 ⚠️

- [X] T067 [P] [US5] Tests Feature broadcasting (`Event::fake` / `Broadcast` assertions) : `reservation.changed` et `machine.changed` émis sur le canal privé `fleet` avec exactement les payloads de [contracts/broadcast-events.md](contracts/broadcast-events.md) ; canal refusé à un utilisateur sans `reservations.manage` dans `functional/booking/tests/Feature/BroadcastingTest.php`
- [X] T068 [P] [US5] Test Feature du planning (filtres catégorie / agence / période ; réservations et indisponibilités atelier, panne, VGP non valide affichées par machine) dans `functional/booking/tests/Feature/PlanningTest.php`

### Implementation for User Story 5

- [X] T069 [US5] Rendre `ReservationChanged` et `MachineChanged` diffusables : `ShouldBroadcast`, `broadcastOn()` → `PrivateChannel('fleet')`, `broadcastAs()` → `reservation.changed` / `machine.changed`, `broadcastWith()` = allow-list de [contracts/broadcast-events.md](contracts/broadcast-events.md) ; idem `FleetImported` → `fleet.imported` `{ created_count }` (fichiers `functional/booking/src/Events/ReservationChanged.php`, `functional/fleet/src/Events/MachineChanged.php`, `functional/fleet/src/Events/FleetImported.php`)
- [X] T070 [US5] Ajouter un worker de queue au `compose.yaml` (service Sail exécutant `php artisan queue:work`) pour le broadcasting
- [X] T071 [US5] Créer l'écran Livewire « Planning » (`/planning`) dans `functional/booking/src/Livewire/Planning.php` et sa vue : une ligne par machine, jours en colonnes sur la période filtrée, réservations et indisponibilités colorées, requêtes groupées (pas de requête par machine)
- [X] T072 [US5] Brancher l'écoute temps réel (`#[On('echo-private:fleet,.reservation.changed')]`, `.machine.changed`, `.fleet.imported`) sur `AvailabilitySearch`, `ReservationList`, `MachineIndex` et `Planning` dans leurs classes Livewire respectives

**Checkpoint**: T067–T068 passent ; vérification manuelle n°1 du [quickstart.md](quickstart.md) en deux navigateurs.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [X] T073 Créer l'écran Livewire « Salariés » (`/salaries`) dans `app/Livewire/Users/UserIndex.php` et sa vue : créer un compte (nom, e-mail unique, agence, rôle `salarie`), désactiver un compte (migration `deactivated_at` nullable sur `users` ; connexion refusée pour un compte désactivé) ; permission `users.manage` ; test Feature dans `tests/Feature/ManageUsersTest.php`
- [X] T074 [P] Vérifier que chaque création, sortie, retour, annulation et changement de statut produit une entrée d'historique avec auteur et date (FR-022) : test Feature dans `functional/booking/tests/Feature/ActivityLogTest.php`
- [X] T075 [P] Lancer `./vendor/bin/sail php vendor/bin/phpstan analyse` et corriger toutes les erreurs dans `app/` et `functional/*/src/`
- [X] T076 [P] Vérifier qu'aucun fichier de code de `app/` et `functional/*/src/` ne dépasse 200 lignes ; découper sinon
- [X] T077 Lancer `./vendor/bin/sail artisan test` : toute la suite doit passer
- [X] T078 Dérouler les vérifications manuelles 1 à 8 de [quickstart.md](quickstart.md) et corriger les écarts
- [X] T085 [P] Mesurer la recherche de disponibilité (`AvailableMachinesQuery`) sur un parc de 400 machines : moins d'1 s (Performance Goals du plan) — mesuré le 2026-10-10 : ~16 ms pour 443 machines et 300 réservations (requête filtrée par catégorie, période de 5 jours)


---

## Phase 9: Conformité aux skills Xefi (audit du 2026-10-10)

**Ordre** : T094 (séparation message utilisateur / message technique des refus) et T106 d'abord, car la 002 et la 003 en dépendent ; puis les autres tâches.

**Purpose**: Corriger les écarts HAUTS, MOYENS et UI relevés par l'audit des skills Xefi (plugins laravel, global, design-patterns, design) sur fleet, booking, `app/` et le socle partagé, avant la fusion des PR. Décisions utilisateur : pas d'API REST lomkit ; dépendance des layers à `User` coupée par contrats + config d'auth ; paquet `xefi/faker-php-locales-fr-fr` ajouté ; brief déplacé dans `specs/` ; skills Boost des paquets installés. Lire le `SKILL.md` concerné avant chaque tâche ; tests d'abord quand le comportement change.

### Données de démonstration (HAUTE)

- [X] T086 [seed-new-features, seeder-conventions] Réécrire `functional/fleet/database/seeders/FleetSeeder.php` avec les factories (états `MachineFactory` : `workshop`, `outOfOrder`, `rentedOut`, `retired`, VGP expirée / non renseignée) au lieu de `Model::create`, `sprintf` et listes en dur ; créer `functional/booking/database/seeders/CustomerSeeder.php` et `functional/booking/database/seeders/ReservationSeeder.php` (réservations `confirmed`, `in_progress`, `closed`, `cancelled` et les 3 `ConflictReason`) ; `database/seeders/DatabaseSeeder.php` sans nom ni e-mail en dur (salariés par factory, un par agence), qui affiche en fin de seed les comptes créés ; `quickstart.md` indique où trouver ces comptes

### Frontières des layers (MOYENNE)

- [X] T087 [osdd, prefer-manifests] Compléter les manifests : `functional/booking/composer.json` déclare `functional/fleet` ; `functional/fleet/composer.json` et `functional/booking/composer.json` déclarent les paquets qu'ils utilisent (`livewire/livewire`, `livewire/flux`, `lomkit/laravel-access-control`, `spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/simple-excel` pour fleet)
- [X] T088 [osdd] Casser le cycle `app` ↔ layers des seeders : `FleetPermissionSeeder` et `BookingPermissionSeeder` créent seulement leurs permissions ; `database/seeders/PermissionSeeder.php` les attribue au rôle `salarie`
- [X] T089 [osdd] Couper la dépendance des layers à `App\Models\User` : contrat `functional/fleet/src/Contracts/AgencyMember.php` (`agencyId(): int`) implémenté par `app/Models/User.php` ; `booking` type ses paramètres en `AgencyMember` / `Authenticatable` et ses relations `belongsTo` lisent `config('auth.providers.users.model')` (`Reservation::author()`, `ReservationFactory`) ; `functional/fleet/routes/channels.php` type en `Authorizable` ; les tests des layers créent l'utilisateur via le modèle configuré (trait `functional/fleet/tests/Concerns/CreatesUsers.php`) ; `ReservationFactory` résout la factory de l'utilisateur par `Factory::factoryForModel()`
- [X] T090 [permissions-for-access-only] `functional/fleet/routes/channels.php` : le canal `fleet` est autorisé par une permission de fleet (`fleet.view`, nouvelle, donnée au rôle `salarie`) et non plus par `reservations.manage`

### Requêtes et constantes (MOYENNE)

- [X] T091 [always-use-models] Remplacer `->from('reservations')` par des sous-requêtes Eloquent corrélées via la relation `machine()` (`functional/booking/src/Queries/OuterMachineReservations.php`, `whereExists` / `whereNotExists`) dans `functional/booking/src/Queries/AvailableMachinesQuery.php` et `functional/booking/src/Console/FlagLateReturns.php`
- [X] T092 [no-magic-strings] Clés étrangères en chaîne → `whereBelongsTo` / relations dans `functional/booking/src/` et `functional/fleet/src/Livewire/MachineIndex.php` ; permissions en enums par layer (`functional/fleet/src/Access/FleetPermission.php`, `functional/booking/src/Access/BookingPermission.php`, `app/Access/AppPermission.php`) utilisés par seeders, Controls, routes, canal et navigation ; `abort(403)` de `MachineIndex` remplacé par `AuthorizationException` ; les filtres par identifiant passent les modèles (`whereBelongsTo`)
- [X] T093 [prefer-relation-accessors] `functional/booking/src/Actions/DepartReservation.php` et `ReturnReservation.php` verrouillent la machine via la relation `machine()` au lieu de `findOrFail($reservation->machine_id)`

### Exceptions et langue du code (MOYENNE)

- [X] T094 [no-generic-exceptions, code-in-english, no-hardcoded-user-text] Les exceptions de refus portent un message développeur en anglais ; le texte affiché (clé de traduction + paramètres) est exposé par `RefusalException::userMessage()` et rendu par `DisplaysRefusals` ; `app/Exceptions/SelfDeactivationException.php` a une fabrique nommée ; la sortie console de `functional/booking/src/Console/FlagLateReturns.php` passe par les traductions de la console en anglais

### Factories et tests (MOYENNE)

- [X] T095 [no-fakerphp, custom-faker-extensions, faker-extensions] Retirer `fakerphp/faker` de `composer.json` ; `database/factories/UserFactory.php` utilise `faker()` ; extension faker du layer fleet (`functional/fleet/src/Faker/FleetFakerExtension.php`, enregistrée via `extra.faker`) pour les noms d'agence, de catégorie et les références machine, et du layer booking (`functional/booking/src/Faker/BookingFakerExtension.php`, téléphone client : celui du paquet fr-FR produit des numéros de longueur variable) ; providers déclarés dans le `composer.json` de chaque layer (`extra.faker.providers`) puis `composer update functional/<layer>` ; `xefi/faker-php-locales-fr-fr` en dev et `faker_locale` `fr_FR` dans `config/app.php` et `.env.example`
- [X] T096 [automated-tests] `functional/fleet/tests/Unit/MachineStateTest.php`, `functional/booking/tests/Unit/ReservationStateTest.php` étendent `PHPUnit\Framework\TestCase` ; `functional/fleet/tests/Unit/MachineVgpComplianceTest.php` teste en Unit la règle VGP extraite dans `functional/fleet/src/Vgp/VgpCompliance.php` (calcul en mémoire, constitution 1.0.1)

### Transactions, traçabilité, socle (MOYENNE)

- [X] T097 [transaction-boundaries] Retirer la transaction du composant `functional/booking/src/Livewire/CreateReservationForm.php` (création du client inline portée par l'action via le DTO `functional/booking/src/Data/NewCustomer.php`) et celle, inutile, de `functional/fleet/src/Actions/ImportFleet.php`
- [X] T098 [FR-022] Tracer l'agence de l'auteur dans l'historique des changements de machine et de réservation (trait `functional/fleet/src/Activity/RecordsAuthorAgency.php`, hook `beforeActivityLogged` de laravel-activitylog v5, propriété `author_agency_id`) ; `functional/booking/tests/Feature/ActivityLogTest.php` l'affirme
- [X] T099 [retention-via-prunable, layer-owned-config, latest-stable-versions, boost] Point d'enregistrement commun des modèles prunables des layers pour `model:prune` : clé `prunable.models` de `config/prunable.php`, remplie par chaque layer depuis son service provider (`config()->push('prunable.models', Model::class)`), lue par l'unique `Schedule::command('model:prune', ['--model' => …])->daily()` de `routes/console.php` ; `composer.json` `"php": "^8.5"` ; installer les skills Boost de `xefi/laravel-osdd` et `lomkit/laravel-access-control` (`boost.json`, `.claude/skills/`)
- [X] T100 [no-project-docs] Déplacer `spec.md.txt` dans `specs/brief-client.md`

### Interface (skills design)

- [ ] T101 [accessibility] `functional/booking/resources/views/livewire/planning.blade.php` : nom accessible des liens de cellule, état de cellule lisible sans la couleur (motif ou texte), contraste en mode sombre
- [ ] T102 [buttons] Un seul bouton primaire par contexte : actions de ligne en boutons secondaires de taille `xs` (`availability-search`, `reservation-list`, `machine-index`, `user-index`) ; dans `reservation-detail`, la sortie / le retour ne sont plus en concurrence avec un primaire de section
- [ ] T103 [screen-states] États vides avec issue (`machine-index`, `planning`, `availability-search` propose d'élargir la période), `wire:loading` sur les filtres `wire:model.live`, messages de succès après changement de statut machine, sortie, retour, annulation, changement d'agence ou d'activation d'un salarié
- [X] T104 [foundations, spacing] Composant commun de titres d'écran (`resources/views/components/page-heading.blade.php`, H1 32 px, sections 24 px) utilisé par tous les écrans 001 et réutilisable par 002/003 ; espacements multiples de 8 px (plus de 12 px ni de padding < 8 px), champs de formulaire espacés de 16 px
- [ ] T105 [ux-writing] Confirmations `wire:confirm` remplacées par des modales Flux aux libellés explicites (annulation de réservation, retrait de machine, désactivation de salarié) ; le refus « référence en double » dit quoi faire

### Contrat des points d'extension (priorité : demandé par la 002)

- [X] T106 [osdd, enums-with-behavior] Enum `functional/booking/src/Enums/ReservationTransition.php` (`Departure`, `Return`, `Cancellation` pour les refus de transition) dans le contrat du point d'extension : `ReservationDetail` remplace `DEPARTURE_STEP` / `RETURN_STEP` par cet enum et l'événement `reservation-transition-readiness` porte sa valeur ; les layers supérieurs (inspection) s'y rattachent au lieu de recopier des chaînes ; research R12 mis à jour

**Checkpoint**: `speckit-analyze` sans problème CRITIQUE, HAUT ou MOYEN ; Pint, PHPStan (cache vidé) et toute la suite verts ; CI de la PR #1 verte.
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
