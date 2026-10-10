# Implementation Plan: Tableau de bord d'accueil

**Branch**: `008-tableau-de-bord` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/008-tableau-de-bord/spec.md`

## Summary

La route `dashboard` (page vide du starter kit) devient un écran Livewire en lecture seule. `app/` en est la racine de composition, comme pour le layout et la navigation qui assemblent déjà les layers. Il n'y a ni nouveau layer, ni point d'extension, ni table, ni permission.

- **Données** : chaque donnée est lue par une classe de requête du layer qui la possède.
  - Les compteurs réutilisent les requêtes existantes des listes de suivi : `TransmissionsToHandle`, `CertificatesToHandle`, `PendingDeposits`, `MissingPurchaseOrders`. Ils ont ainsi exactement leur définition (FR-002, SC-002).
  - Ce qui n'existe pas encore est ajouté sous forme de classes de requête en lecture seule, sans toucher aux fichiers existants :
    - `booking` : `DayOperations` ;
    - `fleet` : `FleetStatusCounts`, `VgpWatchList` ;
    - `inspection` : `DamagesToHandle` ;
    - `sales` : `OverdueSales`.
- **Écran** : la page `Dashboard` porte le choix d'agence, présent dans l'adresse. Elle assemble quatre composants enfants : opérations du jour, état du parc, VGP à surveiller et compteurs.
  - Les trois premiers reçoivent l'agence en propriété réactive.
  - Chaque enfant vérifie côté serveur la permission de l'écran vers lequel il renvoie.
- **Mise à jour** : chaque enfant se rafraîchit sur les événements diffusés existants et par une mise à jour périodique de 60 secondes.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: Livewire 4, Flux 2, `xefi/laravel-osdd`, `spatie/laravel-permission`, `pusher/pusher-php-server` (Soketi). Aucun nouveau package.

**Storage**: PostgreSQL, en lecture seule ; aucune migration.

**Testing**: PHPUnit 12, tests Feature sur PostgreSQL.
- Écrans dans `tests/Feature/Dashboard/`.
- Requêtes de layer dans `functional/<layer>/tests/Feature/`.
- Outils : factories avec `faker()`, `travelTo()`, `Livewire::test()`, `DB::enableQueryLog()` pour FR-020.
- Qualité : Larastan avec `xefi/phpstan-xefi-rules`, Pint.

**Target Platform**: application web Laravel servie par Sail (Docker).

**Project Type**: monolithe Laravel en layers OSDD.

**Performance Goals**:
- Page affichée en moins de 2 secondes (SC-003).
- Nombre de requêtes constant, quel que soit le volume (FR-020) :
  - 2 requêtes par section de liste (20 premières lignes, total) plus leurs chargements anticipés ;
  - 1 requête groupée pour les statuts du parc ;
  - 1 requête de comptage par compteur.

**Constraints**:
- Aucune modification de comportement des features 001 à 007 (SC-005).
- Aucune requête dans une boucle.
- Fichiers de moins de 200 lignes.
- Aucun layer n'importe une classe de `app/`.

**Scale/Scope**: ~400 machines, 7 agences, 85 salariés, quelques dizaines de mouvements par jour. Un écran, 5 composants Livewire, 5 classes de requête de layer.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principe | Application | Statut |
|---|---|---|
| I. Layers OSDD | Aucun nouveau domaine métier, donc aucun layer. L'écran vit dans `app/`, qui assemble déjà les layers (`layouts/app.blade.php`, `sidebar.blade.php`). Les nouvelles requêtes appartiennent au layer qui possède les données. Elles sont ajoutées sans modifier aucun fichier existant d'un layer. Aucun layer n'importe `app/`. | ✅ |
| II. Garanties base et serveur | Aucune écriture, aucune règle à garantir. Agrégats calculés en base (`count`, `group by status`). Aucune requête dans une boucle, vérifié par un test du nombre de requêtes (FR-020). | ✅ |
| III. Cycles de vie | Aucun statut nouveau. Les statuts existants (`ReservationStatus`, `MachineStatus`, `SaleStatus`) sont lus tels quels. « Aujourd'hui » est calculé en `Europe/Paris` (`config('app.timezone')`, FR-011). | ✅ |
| IV. Effets de bord explicites | Aucun effet de bord, aucun listener, aucun job. L'écran écoute les événements diffusés existants (`reservation.changed`, `machine.changed`, `certificate.changed`, `deposit.changed`, `damage.changed`, `sale.changed`). | ✅ |
| V. Accès par permission | Aucune permission nouvelle (FR-018). Chaque composant vérifie dans `mount()` la permission de l'écran ciblé, et le gabarit masque la section avec `@can`. La page elle-même reste ouverte à tout salarié connecté et actif, comme aujourd'hui. Elle n'affiche aucune donnée sans permission : le sélecteur d'agence n'est rendu qu'avec une permission d'une section filtrée par agence, et sinon seul l'état vide s'affiche. | ✅ |
| VI. Tests par scénario | Un test Feature par scénario d'acceptation (US1 à US5), plus les tests Feature des requêtes de layer (calculs en base). Aucun calcul en mémoire, donc pas de test Unit. Tests écrits avant l'implémentation dans chaque phase. | ✅ |
| VII. Code simple | Code en anglais ; textes dans `lang/fr/dashboard.php` ; seuils dans `config/dashboard.php` ; fichiers de moins de 200 lignes ; aucun package nouveau. | ✅ |

Re-check après la phase 1 : aucun écart. Voir [research.md](research.md), décision R1, pour les alternatives d'architecture écartées.

## Project Structure

### Documentation (this feature)

```text
specs/008-tableau-de-bord/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── layer-queries.md
│   └── screens.md
├── checklists/requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
config/dashboard.php                               # horizon des départs (7), seuil VGP (30), lignes par section (20)
lang/fr/dashboard.php                              # textes de l'écran
routes/web.php                                     # Route::livewire('dashboard', Dashboard::class)
resources/views/dashboard.blade.php                # supprimé (page vide du starter kit)
app/Dashboard/
├── DashboardSection.php                           # lignes affichées + total
├── PendingWorkCounter.php                         # clé, nombre, route
└── PendingWorkCounters.php                        # les 6 compteurs autorisés au salarié
app/Livewire/Dashboard/
├── Dashboard.php                                  # page : choix d'agence (#[Url] agence)
├── DayOperations.php                              # départs du jour, à venir, retours, retards, conflits
├── FleetStatus.php                                # 4 chiffres du parc
├── VgpWatch.php                                   # VGP à surveiller
└── PendingWork.php                                # compteurs réseau
resources/views/livewire/dashboard/
├── dashboard.blade.php, day-operations.blade.php, fleet-status.blade.php,
├── vgp-watch.blade.php, pending-work.blade.php
└── partials/reservation-row.blade.php
tests/Feature/Dashboard/
├── Concerns/BuildsDashboardFixtures.php
├── DayOperationsTest.php (US1), AnomaliesTest.php (US2), PendingWorkTest.php (US3),
├── FleetOverviewTest.php (US4), AgencySelectionTest.php (US5), DashboardQueryCountTest.php (FR-020)

functional/booking/src/Queries/DayOperations.php            + tests/Feature/DayOperationsQueryTest.php
functional/fleet/src/Queries/FleetStatusCounts.php          + tests/Feature/FleetStatusCountsTest.php
functional/fleet/src/Queries/VgpWatchList.php               + tests/Feature/VgpWatchListTest.php
functional/inspection/src/Queries/DamagesToHandle.php       + tests/Feature/DamagesToHandleTest.php
functional/sales/src/Queries/OverdueSales.php               + tests/Feature/OverdueSalesTest.php
```

**Structure Decision**: dépôt unique, application Laravel à la racine. L'écran et sa composition sont dans `app/`. Les cinq requêtes nouvelles sont des fichiers ajoutés dans leur layer, sans modification d'un fichier existant de layer.

## Prérequis et ordre

- Base : `origin/main`, qui contient les features 001 à 007 fusionnées. Aucune autre branche n'est attendue.
- Ordre d'implémentation :
  1. Requêtes de layer (fondation).
  2. Page et choix d'agence.
  3. US1, puis US2 et US3, puis US4. US5 est porté par la page dès la fondation et testé en dernier.

## Complexity Tracking

Aucune violation de la constitution à justifier.
