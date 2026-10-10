---

description: "Task list for the home dashboard feature"
---

# Tasks: Tableau de bord d'accueil

**Input**: Design documents from `specs/008-tableau-de-bord/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ (layer-queries, screens), quickstart.md

**Tests**: obligatoires (constitution, principe VI).
- Un test Feature par scénario d'acceptation, plus les tests Feature des requêtes de layer (calculs en base).
- Aucun calcul en mémoire, donc pas de test Unit.
- Dans chaque phase, les tests sont écrits d'abord et doivent échouer avant l'implémentation.
- Outils : factories avec `faker()`, `travelTo()` (date de référence `2026-10-10`, heure de Paris), `Livewire::test()`, traits `Functional\Fleet\Tests\Concerns\CreatesUsers` et `$this->seedPermissions()`.

**Organization**: tâches regroupées par user story ; chemins depuis la racine du dépôt (dépôt unique).

## Format: `[ID] [P?] [Story] Description`

- **[P]** : parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]** : user story de la spec (US1 à US5)

## Prérequis (constitution : « Une feature qui s'appuie sur une autre déclare ses prérequis »)

| Prérequis | Éléments attendus | État |
|---|---|---|
| 001-reservation-machines | `Agency`, `Machine` (`status`, `is_subject_to_vgp`, `vgp_due_date`), `MachineStatus`, `Reservation` (`status`, `start_date`, `end_date`, `conflict_reason`), `ConflictReason::label()`, `ReservationChanged` et `MachineChanged` sur le canal `fleet`, écrans `reservations.show`, `reservations.index`, `machines.index` (filtres `agence`, `statut`) | dans `main` |
| 002 / 003 | `Damage` (`resolved_at`), `DamageChanged`, écran `inspection.damages` ; `TransmissionsToHandle`, écran `billing.transmissions` | dans `main` |
| 004 | `PendingDeposits`, `DepositChanged`, écran `deposit.pending.index` | dans `main` |
| 005 | `CertificatesToHandle`, `CertificateChanged`, écrans `certification.certificates`, `certification.machines` (filtre `agence`), `certification.machines.show` | dans `main` |
| 006 | `MissingPurchaseOrders`, écran `accounts.missing-purchase-orders` | dans `main` |
| 007 | `Sale` (`status`, `planned_handover_date`), `SaleStatus::Reserved`, `SaleChanged` sur le canal `sales`, écran `sales.index` (filtre `statut`) | dans `main` |

Aucune branche à attendre. Aucune modification d'un fichier existant d'un layer : les seuls fichiers de layer touchés sont les cinq requêtes ajoutées et leurs tests.

Chaque phase se termine par `composer ci:check` en code 0 (après `vendor/bin/phpstan clear-result-cache`), puis un commit de phase sans mention d'outil d'IA.

---

## Phase 1: Setup

**Purpose**: paramètres, textes et outillage de test partagés.

- [ ] T001 [P] Créer `config/dashboard.php` avec trois clés :
  - `'upcoming_departure_days' => 7` (FR-006a) ;
  - `'vgp_watch_days' => 30` (FR-015a) ;
  - `'section_limit' => 20` (FR-010).
- [ ] T002 [P] Créer `lang/fr/dashboard.php` avec les textes de [contracts/screens.md](contracts/screens.md), complété au fil des phases :
  - `title` (« Tableau de bord »), `agency.label`, `agency.all` (« Toutes les agences »), `empty.heading`, `empty.description` ;
  - sections `operations.*`, `pending.*`, `fleet.*`, `vgp.*`.
- [ ] T003 [P] Créer `tests/Feature/Dashboard/Concerns/BuildsDashboardFixtures.php`. Ce trait utilise `CreatesUsers` et expose les aides suivantes, toutes par factories :
  - `agencyNamed(string)` ;
  - `machineIn(Agency, array $attributes = [])` ;
  - `reservationOf(Machine, ReservationStatus, string $start, string $end, array $attributes = [])` ;
  - `employeeOf(Agency, BackedEnum ...$permissions)`, qui renvoie un utilisateur rattaché à l'agence avec ces permissions.

**Checkpoint**: `composer ci:check` vert ; commit « Paramètres et outillage du tableau de bord ».

---

## Phase 2: Foundational (requêtes de layer et page)

**Purpose**: les cinq requêtes en lecture seule de [contracts/layer-queries.md](contracts/layer-queries.md), l'objet `DashboardSection` et la page, encore sans sections. Le choix d'agence vient en US5 ; jusque-là, la page affiche l'agence du salarié.

### Tests (écrits d'abord, en échec)

- [ ] T004 [P] `functional/booking/tests/Feature/DayOperationsQueryTest.php`. Vérifier, pour chaque méthode de `DayOperations`, avec `travelTo('2026-10-10')` :
  - `departures` : `confirmed` avec un début le 10/10 ou avant ; pas un début le 11/10 ni une réservation `in_progress`.
  - `upcomingDepartures(…, 7)` : débuts du 11/10 au 17/10 inclus ; pas le 10/10 ni le 18/10.
  - `returns` : `in_progress` dont la fin est le 10/10.
  - `lateReturns` : `in_progress` dont la fin est le 09/10 ou avant, triées par fin croissante.
  - `conflicts` : `confirmed` avec `conflict_reason` non nul ; pas une réservation annulée.
  - Réservation d'une seule journée (début et fin le 10/10) : dans `departures` tant que `confirmed`, dans `returns` une fois `in_progress`.
  - Tri des départs : `start_date`, puis référence de la machine.
  - Filtre par agence de rattachement de la machine, et non par `reservations.agency_id`.
  - `null` = toutes les agences.
- [ ] T005 [P] `functional/fleet/tests/Feature/FleetStatusCountsTest.php` :
  - nombres par statut pour une agence, retirées exclues ;
  - 0 pour un statut absent ;
  - `null` = toutes les agences ;
  - une seule requête (`DB::enableQueryLog()`).
- [ ] T006 [P] `functional/fleet/tests/Feature/VgpWatchListTest.php` :
  - inclus : échéance passée, non renseignée, échéance au 09/11 (`until` = 10/10 + 30 jours) ;
  - exclus : échéance au 10/11 ; machine non soumise à VGP ; machine retirée ; autre agence ;
  - tri : non renseignées d'abord, puis échéance croissante.
- [ ] T007 [P] `functional/inspection/tests/Feature/DamagesToHandleTest.php` : le nombre est égal au nombre total de dégâts renvoyés par `ReservationsToReinvoice::get()` (somme des groupes). Il exclut un dégât dont `resolved_at` est renseigné.
- [ ] T008 [P] `functional/sales/tests/Feature/OverdueSalesTest.php` :
  - inclus : vente `reserved` avec une remise prévue le 09/10 ;
  - exclus : remise le 10/10, vente `listed`, `sold` ou `cancelled`.

### Implémentation

- [ ] T009 [P] Créer `functional/booking/src/Queries/DayOperations.php` (`final`), conformément à `contracts/layer-queries.md` :
  - méthodes `departures`, `upcomingDepartures`, `returns`, `lateReturns`, `conflicts`, chacune renvoyant un `Builder<Reservation>` ;
  - filtre d'agence par sous-requête `Machine::query()->select('id')->where('agency_id', …)` ;
  - `with(['machine.category', 'machine.agency', 'customer'])`.
- [ ] T010 [P] Créer `functional/fleet/src/Queries/FleetStatusCounts.php` (`final`) :
  - `count(?int $agencyId): array<string, int>` ;
  - une requête `select status, count(*)`, `where status <> retired`, `groupBy('status')` ;
  - clés `available`, `rented_out`, `workshop`, `out_of_order` complétées à 0.
- [ ] T011 [P] Créer `functional/fleet/src/Queries/VgpWatchList.php` (`final`) :
  - `query(?int $agencyId, CarbonImmutable $until): Builder<Machine>` ;
  - tri `orderByRaw('vgp_due_date asc nulls first')`, puis `reference` ;
  - `with('category')`.
- [ ] T012 [P] Créer `functional/inspection/src/Queries/DamagesToHandle.php` (`final`) : `query(): Builder<Damage>`, `whereNull('resolved_at')`.
- [ ] T013 [P] Créer `functional/sales/src/Queries/OverdueSales.php` (`final`) : `query(CarbonImmutable $today): Builder<Sale>`, `where('status', SaleStatus::Reserved)`, `whereDate('planned_handover_date', '<', $today)`.
- [ ] T014 Créer `app/Dashboard/DashboardSection.php` (`final readonly`) :
  - `items` (Collection) et `total` (int) ;
  - fabrique `fromQuery(Builder $query, int $limit)` qui exécute `(clone $query)->limit($limit)->get()` et `$query->toBase()->getCountForPagination()`, soit 2 requêtes plus les chargements anticipés ;
  - `hasMore(): bool`.
- [ ] T015 Créer `app/Livewire/Dashboard/Dashboard.php` et `resources/views/livewire/dashboard/dashboard.blade.php` :
  - `x-page-heading` « Tableau de bord » ;
  - propriété calculée `agencyId` égale à l'agence du salarié connecté (`agencyId()` de `AgencyMember`) ;
  - `x-empty-state` quand le salarié n'a aucune des permissions de [research.md](research.md) R7 ;
  - titre de page `__('dashboard.title')`.
- [ ] T016 Dans `routes/web.php`, remplacer `Route::view('dashboard', 'dashboard')` par `Route::livewire('dashboard', Dashboard::class)->name('dashboard')`. Supprimer `resources/views/dashboard.blade.php`. `tests/Feature/DashboardTest.php` reste vert (redirection des invités, accès d'un salarié connecté).

**Checkpoint**: tests T004 à T008 et `DashboardTest` verts ; `composer ci:check` vert ; commit « Requêtes de lecture et page du tableau de bord ».

---

## Phase 3: User Story 1 — Voir les départs et les retours du jour de son agence (P1) 🎯 MVP

**Goal**: départs du jour, départs à venir (7 jours) et retours du jour de l'agence, chaque ligne menant au détail de la réservation.

**Independent Test**: un salarié d'une agence voit ses deux départs du jour, son retour du jour et ses départs à venir, pas ceux d'une autre agence (spec US1).

### Tests (écrits d'abord, en échec)

- [ ] T017 [US1] `tests/Feature/Dashboard/DayOperationsTest.php` : un test par scénario de la US1.
  1. Deux départs du jour listés avec la référence, la catégorie, le client et les dates.
  2. Retour du jour listé.
  3. Le lien de la ligne pointe vers `route('reservations.show', $reservation)`.
  4. Départ passé non enregistré listé avec « départ prévu le … ».
  5. Messages vides.
  6. Départ dans 3 jours listé sous sa date dans « Départs à venir » ; départ dans 8 jours absent.
  7. Le composant écoute `echo-private:fleet,.reservation.changed` ; après la sortie d'une réservation (`status` → `in_progress`) et un `$refresh`, elle n'est plus dans les départs.

  S'y ajoutent trois tests :
  - la réservation d'une autre agence est absente ;
  - un salarié sans `reservations.manage` reçoit 403 sur le composant, et la section est absente de la page ;
  - FR-010 : avec 21 départs du jour, 20 lignes sont affichées avec « 20 sur 21 » et un lien vers `reservations.index`.

### Implémentation

- [ ] T018 [US1] Créer `app/Livewire/Dashboard/DayOperations.php` :
  - `#[Reactive] public ?int $agencyId` ;
  - `mount()` avec `Gate::authorize(BookingPermission::ManageReservations->value)` ;
  - écoute `#[On('echo-private:fleet,.reservation.changed')]` et `#[On('echo-private:fleet,.machine.changed')]` ;
  - `render()` construit les sections `departures`, `upcomingDepartures` et `returns` avec `DashboardSection::fromQuery()`, `CarbonImmutable::today()`, `config('dashboard.upcoming_departure_days')` et `config('dashboard.section_limit')`.
- [ ] T019 [US1] Créer `resources/views/livewire/dashboard/day-operations.blade.php` et `resources/views/livewire/dashboard/partials/reservation-row.blade.php` :
  - `wire:poll.60s` ;
  - une `x-section-heading level=3` par section ;
  - départs à venir groupés par `start_date` (`groupBy` sur la collection affichée, au plus 20 lignes) sous un intitulé `isoFormat('dddd D MMMM')` ;
  - ligne-lien `wire:navigate` vers `reservations.show` ;
  - agence affichée quand `agencyId` est nul ;
  - « 20 sur N » et lien vers `reservations.index` quand `hasMore()` ;
  - textes vides de `lang/fr/dashboard.php`.
- [ ] T020 [US1] Insérer `<livewire:dashboard.day-operations :agency-id="$this->agencyId" />` dans `dashboard.blade.php`, sous `@can(BookingPermission::ManageReservations->value)`, et compléter `lang/fr/dashboard.php` (`operations.*`).

**Checkpoint**: `DayOperationsTest` vert ; `composer ci:check` vert ; commit « Tableau de bord : départs et retours du jour ».

---

## Phase 4: User Story 2 — Repérer les anomalies : retours en retard et réservations en conflit (P1)

**Goal**: sections « Retours en retard » et « Réservations en conflit » dans les opérations du jour.

**Independent Test**: une réservation en cours finie hier figure dans les retours en retard avec son nombre de jours, et une réservation en conflit figure avec son motif (spec US2).

### Tests (écrits d'abord, en échec)

- [ ] T021 [US2] `tests/Feature/Dashboard/AnomaliesTest.php` : un test par scénario de la US2.
  1. Fin il y a 3 jours, ligne avec « 3 jours de retard ».
  2. Conflit listé avec `ConflictReason::label()`.
  3. Une réservation annulée (motif effacé) disparaît après `$refresh`.
  4. Messages vides.

  S'y ajoutent deux tests :
  - un retour prévu aujourd'hui n'est pas dans les retards ;
  - les retards sont triés du plus ancien au plus récent.

### Implémentation

- [ ] T022 [US2] Ajouter à `app/Livewire/Dashboard/DayOperations.php` les sections `lateReturns` et `conflicts` (`DashboardSection::fromQuery()`).
- [ ] T023 [US2] Ajouter les deux sections à `resources/views/livewire/dashboard/day-operations.blade.php` :
  - « N jours de retard » avec `end_date->diffInDays($today)` et `trans_choice` ;
  - motif de conflit par `conflict_reason->label()`.

  Compléter `lang/fr/dashboard.php`.

**Checkpoint**: `AnomaliesTest` et `DayOperationsTest` verts ; `composer ci:check` vert ; commit « Tableau de bord : retours en retard et conflits ».

---

## Phase 5: User Story 3 — Voir le nombre d'éléments à traiter dans chaque liste de suivi (P1)

**Goal**: six compteurs sur l'ensemble du réseau, chacun lié à son écran et masqué sans la permission de cet écran.

**Independent Test**: avec un élément connu dans chaque liste, chaque compteur affiche le nombre de sa liste et renvoie vers elle (spec US3).

### Tests (écrits d'abord, en échec)

- [ ] T024 [US3] `tests/Feature/Dashboard/PendingWorkTest.php` : un test par scénario de la US3.
  1. Fixtures et nombres attendus :
     - une transmission en échec (`TransmissionFactory`) → 1 ;
     - deux attestations en attente d'e-mail (`ReservationCertificateFactory`, réservations confirmées) → 2 ;
     - une caution à restituer (`DepositFactory`) → 1 ;
     - un bon de commande manquant (`KeyAccountFactory` et réservation confirmée) → 1 ;
     - un dégât non résolu (`DamageFactory`) → 1 ;
     - une vente réservée dont la remise est dépassée (`SaleFactory`) → 1.

     Chaque nombre doit aussi être égal à `->count()` de la requête de la liste correspondante.
  2. Le lien du compteur des cautions pointe vers `route('deposit.pending.index')`, et chaque compteur vers la route de [contracts/screens.md](contracts/screens.md).
  3. Un compteur à 0 est rendu neutre, un compteur non nul en évidence.
  4. Le compteur des attestations diminue après l'envoi (statut `sent`) et un `$refresh` ; les écouteurs incluent `echo-private:fleet,.certificate.changed`.
  5. Un salarié sans `billing.manage` ne voit pas le compteur des transmissions, et sa requête n'est pas exécutée.
  6. Une caution sur une machine d'une autre agence est comptée.

  S'y ajoute un test : `echo-private:sales,.sale.changed` n'est écouté qu'avec `sales.manage`.

### Implémentation

- [ ] T025 [P] [US3] Créer `app/Dashboard/PendingWorkCounter.php` (`final readonly` : `key`, `count`, `url`) et `app/Dashboard/PendingWorkCounters.php`.
  - `PendingWorkCounters` contient une table ordonnée de six définitions : permission, clé, route et paramètres, `Closure` de comptage.
  - Les comptages appellent `TransmissionsToHandle`, `CertificatesToHandle`, `PendingDeposits`, `MissingPurchaseOrders`, `DamagesToHandle` et `OverdueSales` (`CarbonImmutable::today()`).
  - `forUser(Authorizable $user): list<PendingWorkCounter>` ne calcule que les compteurs autorisés.
- [ ] T026 [US3] Créer `app/Livewire/Dashboard/PendingWork.php` :
  - `render()` avec `PendingWorkCounters::forUser(auth()->user())` ;
  - `getListeners()` : `.certificate.changed`, `.deposit.changed`, `.damage.changed` et `.reservation.changed` sur `echo-private:fleet`, plus `echo-private:sales,.sale.changed` si `sales.manage`, tous vers `$refresh`.
- [ ] T027 [US3] Créer `resources/views/livewire/dashboard/pending-work.blade.php` :
  - `wire:poll.60s` ;
  - grille `flux:card`, avec l'intitulé `trans_choice('dashboard.pending.<key>', …)` et le nombre ;
  - `flux:badge color="amber"` si non nul, texte atténué si nul ;
  - lien `wire:navigate`.

  L'insérer dans `dashboard.blade.php` sous `@canany` des six permissions. Compléter `lang/fr/dashboard.php`.

**Checkpoint**: `PendingWorkTest` vert ; `composer ci:check` vert ; commit « Tableau de bord : compteurs à traiter ».

---

## Phase 6: User Story 4 — Voir l'état du parc de son agence (P2)

**Goal**: quatre chiffres du parc liés à la liste du parc filtrée, et la liste « VGP à surveiller ».

**Independent Test**: 5 disponibles, 3 sorties, 1 atelier, 1 en panne, 2 retirées → 5, 3, 1, 1 ; « VGP à surveiller » contient l'expirée puis l'échéance à 5 jours, et pas celle à 60 jours (spec US4).

### Tests (écrits d'abord, en échec)

- [ ] T028 [US4] `tests/Feature/Dashboard/FleetOverviewTest.php` : un test par scénario de la US4.
  1. Chiffres par statut, retirées non comptées.
  2. « VGP à surveiller » liste, dans cet ordre, la non renseignée, l'expirée (« Expirée le … ») et l'échéance à 5 jours (« dans 5 jours »).
  3. Le lien « Atelier » pointe vers `route('machines.index', ['agence' => $agency->id, 'statut' => 'workshop'])`.
  4. Mise à jour après le passage d'une machine en panne et un `$refresh` ; écouteur `.machine.changed`.
  5. Une ligne VGP pointe vers `route('certification.machines.show', $machine)`.

  S'y ajoutent trois tests :
  - sans `machines.manage`, les chiffres sont masqués (403 sur `FleetStatus`) ;
  - sans `certification.manage`, la liste VGP est masquée ;
  - FR-015a : avec 21 machines à surveiller, 20 lignes et un lien vers `route('certification.machines', ['agence' => $agency->id])`.

### Implémentation

- [ ] T029 [P] [US4] Créer `app/Livewire/Dashboard/FleetStatus.php` et `resources/views/livewire/dashboard/fleet-status.blade.php` :
  - `#[Reactive] ?int $agencyId` ;
  - `Gate::authorize(FleetPermission::ManageMachines->value)` ;
  - écouteurs `.machine.changed`, `.reservation.changed` et `.fleet.imported` ; `wire:poll.60s` ;
  - quatre chiffres via `FleetStatusCounts::count()`, libellés par `MachineStatus::label()` et couleurs par `MachineStatus::color()` ;
  - liens `machines.index` avec `statut`, et `agence` si une agence est choisie.
- [ ] T030 [P] [US4] Créer `app/Livewire/Dashboard/VgpWatch.php` et `resources/views/livewire/dashboard/vgp-watch.blade.php` :
  - `#[Reactive] ?int $agencyId` ;
  - `Gate::authorize(CertificationPermission::Manage->value)` ;
  - écouteur `.machine.changed` ; `wire:poll.60s` ;
  - `DashboardSection::fromQuery(VgpWatchList::query($agencyId, today()->addDays(config('dashboard.vgp_watch_days'))), config('dashboard.section_limit'))` ;
  - libellés d'échéance de [contracts/screens.md](contracts/screens.md) ;
  - lien « voir tout » vers `certification.machines` avec `agence`.
- [ ] T031 [US4] Insérer les deux composants dans `dashboard.blade.php`, chacun sous son `@can`, et compléter `lang/fr/dashboard.php` (`fleet.*`, `vgp.*`).

**Checkpoint**: `FleetOverviewTest` vert ; `composer ci:check` vert ; commit « Tableau de bord : état du parc et VGP à surveiller ».

---

## Phase 7: User Story 5 — Choisir l'agence affichée (P2)

**Goal**: sélecteur d'agence dans l'en-tête ; agence dans l'adresse ; option « Toutes les agences ».

**Independent Test**: Rouen par défaut ; Évreux après choix ; les 7 agences avec « Toutes les agences » (spec US5).

### Tests (écrits d'abord, en échec)

- [ ] T032 [US5] `tests/Feature/Dashboard/AgencySelectionTest.php` : un test par scénario de la US5.
  1. Agence par défaut : celle du salarié.
  2. `set('agency', $evreux->id)` : les départs d'Évreux sont affichés, ceux de Rouen absents ; les compteurs sont inchangés.
  3. `set('agency', 'toutes')` : les départs des deux agences sont affichés, avec le nom de l'agence sur chaque ligne.
  4. `GET /dashboard?agence=<id d'Évreux>` affiche Évreux.

  S'y ajoutent deux tests :
  - `?agence=999999` ou `?agence=abc` affiche l'agence du salarié ;
  - Principe V : le sélecteur d'agence n'est rendu qu'au salarié qui a au moins une des permissions `reservations.manage`, `machines.manage` ou `certification.manage` ; un salarié sans permission ne voit que l'état vide.

### Implémentation

- [ ] T033 [US5] Dans `app/Livewire/Dashboard/Dashboard.php` :
  - ajouter `#[Url(as: 'agence')] public string $agency = ''` ;
  - la propriété calculée `agencyId` (`?int`) applique la table « Agence sélectionnée » de [data-model.md](data-model.md) ;
  - passer `agencies` (par nom) à la vue.
- [ ] T034 [US5] Dans `resources/views/livewire/dashboard/dashboard.blade.php` :
  - `flux:select wire:model.live="agency"` dans les actions de `x-page-heading`, avec les agences et `toutes`, rendu sous `@canany` des trois permissions des sections filtrées par agence ;
  - `wire:key` des enfants dépendant de l'agence.

  Compléter `lang/fr/dashboard.php`.

**Checkpoint**: `AgencySelectionTest` et toutes les user stories vertes ; `composer ci:check` vert ; commit « Tableau de bord : choix de l'agence ».

---

## Phase 8: Polish & Cross-Cutting Concerns

- [ ] T035 `tests/Feature/Dashboard/DashboardQueryCountTest.php` (FR-020, SC-003) :
  - le nombre de requêtes du rendu complet de la page est identique avec 2 puis 30 réservations et machines par section (`DB::enableQueryLog()`), en vue agence comme en vue « Toutes les agences » ;
  - avec le volume de SC-003 (400 machines dans 7 agences, 3 000 réservations sur un an, par factories), le rendu complet en vue « Toutes les agences » prend moins de 2 secondes.
- [ ] T036 [P] Vérifier qu'aucun fichier existant de `functional/*` n'est modifié par la branche (`git diff --stat origin/main -- functional` ne liste que les dix fichiers ajoutés), et que les bandeaux `billing.alert` et `certification.alert` sont toujours rendus sur `/dashboard` (FR-003, à ajouter à `tests/Feature/DashboardTest.php`).
- [ ] T037 Recette manuelle de [quickstart.md](quickstart.md) avec les données du client chargées dans `vallet-008` (scripts copiés depuis le dossier principal, sans toucher l'environnement 8080) ; contrôle visuel en clair et en sombre, et à largeur de téléphone.
- [ ] T038 `vendor/bin/pint --dirty`, `vendor/bin/phpstan clear-result-cache`, puis `composer ci:check` en code 0 ; commit « Tableau de bord : finitions ».

---

## Dependencies & Execution Order

- **Phase 1** → **Phase 2** → user stories.
- **US1** (Phase 3) crée `DayOperations`. **US2** (Phase 4) l'étend et vient donc après US1.
- **US3** (Phase 5) et **US4** (Phase 6) ne dépendent que de la Phase 2 ; elles peuvent se faire dans n'importe quel ordre après US1.
- **US5** (Phase 7) dépend de la présence d'au moins une section filtrée (US1) pour ses tests.
- **Phase 8** après toutes les user stories.

## Parallel Execution Examples

- Phase 1 : T001, T002 et T003 en parallèle.
- Phase 2 : tests T004 à T008 en parallèle, puis implémentations T009 à T013 en parallèle ; T014 à T016 ensuite.
- Phase 5 : T025 en parallèle de l'écriture des vues de la Phase 6.
- Phase 6 : T029 et T030 en parallèle.

## Implementation Strategy

- **MVP** : Phases 1 à 3 (US1). La page d'accueil montre déjà les départs et retours de l'agence.
- **Incréments** : US2 (anomalies), US3 (compteurs), US4 (parc et VGP), US5 (choix d'agence). Chaque incrément est testable seul et fait l'objet d'un commit de phase.
- **Périmètre gelé** : rien au-delà de la spec. Aucune action, aucun graphique, aucun filtre ajouté aux écrans existants.
