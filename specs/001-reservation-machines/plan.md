# Implementation Plan: Cœur de réservation des machines

**Branch**: `001-reservation-machines` | **Date**: 2026-10-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-reservation-machines/spec.md`

## Summary

Un outil de réservation partagé par les 7 agences de Vallet Location. Il tient un parc unique d'environ 400 machines à référence unique et accepte uniquement les réservations sans chevauchement, sur des machines disponibles et dont la VGP couvre toute la période. Il suit la sortie et le retour des machines, et diffuse chaque changement en temps réel à toutes les agences.

Approche : une application **100 % Laravel** avec une interface Livewire, découpée en deux layers OSDD (`fleet`, `booking`). Le non-chevauchement est garanti par une contrainte d'exclusion PostgreSQL. Les cycles de vie de la réservation et de la machine suivent le pattern State. Le temps réel passe par Soketi et Echo.

`booking` expose aussi deux points d'extension pour les fonctionnalités suivantes (feature 002 photos, puis caution) : des guards qui peuvent refuser la sortie ou le retour, et des sections ajoutées au détail d'une réservation (voir R12).

## Technical Context

**Language/Version**: PHP 8.5 (conteneur Sail), Laravel 13, Livewire 4 ; PHP 8.4 (Herd) accepté en local pour Composer et PHPStan

**Primary Dependencies**: Livewire (starter kit Livewire officiel), `xefi/laravel-osdd`, `spatie/laravel-permission`, `lomkit/laravel-access-control`, `spatie/laravel-activitylog`, `spatie/simple-excel`, `pusher/pusher-php-server` + Laravel Echo (vers Soketi), `laravel/boost` (dev), `xefi/faker-php-laravel` (dev), Larastan + `xefi/phpstan-xefi-rules` (dev)

**Storage**: PostgreSQL (contrainte d'exclusion `btree_gist` sur les réservations)

**Testing**: PHPUnit, tests Feature par scénario d'acceptation et tests Unit pour les classes d'état. Larastan niveau ≥ 7.

**Target Platform**: serveur Linux (conteneurs). Développement local avec Laravel Sail (Docker/WSL 2) : PostgreSQL, Soketi, Mailpit.

**Project Type**: application web monolithique (rendu serveur + Livewire)

**Performance Goals**: recherche de disponibilité sur 400 machines en moins d'1 s ; propagation d'un changement aux autres agences en moins de 5 s (SC-006)

**Constraints**: zéro double réservation, même en concurrence (FR-009). Interface en français. Aucune suppression en cascade en base. Ni observers ni `try/catch` (règles Xefi).

**Scale/Scope**: 85 utilisateurs, 7 agences, environ 400 machines, environ 8 écrans

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

`.specify/memory/constitution.md` est encore le modèle vide : aucun principe projet n'est ratifié. À défaut, les conventions Xefi servent de portes :

| Porte | Statut |
|-------|--------|
| Stack Laravel + Livewire, layout OSDD | ✅ R1, R2 |
| Packages obligatoires (permission, access-control, boost, faker Xefi) ; `lomkit/laravel-rest-api` sans objet (aucune API CRUD exposée) | ✅ R6, R11 |
| Contrôles par permission, jamais par nom de rôle | ✅ R6 |
| Statuts en colonne texte + enum PHP, transitions en pattern State | ✅ R5 |
| Pas d'observers, réactions par événements / listeners | ✅ R9 |
| Pas de cascade en base | ✅ data-model |
| Temps réel Soketi, canal privé, payload explicite | ✅ R4, contrats |
| Garanties portées par le code ou la base, pas seulement par la doc | ✅ R3 (contrainte d'exclusion) |
| Fichiers de code < 200 lignes, code en anglais, textes traduits | à vérifier pendant l'implémentation |

**Résultat** : aucune violation. Recommandation : lancer `/speckit-constitution` avant la 2e feature pour inscrire ces principes dans le projet.

**Re-check post-design** : le modèle de données et les contrats respectent toutes les portes ci-dessus.

## Project Structure

### Documentation (this feature)

```text
specs/001-reservation-machines/
├── spec.md
├── plan.md              # ce fichier
├── research.md          # décisions R1–R11
├── data-model.md        # entités, contraintes, transitions
├── quickstart.md        # guide de vérification
├── contracts/
│   ├── screens.md           # écrans et refus
│   ├── broadcast-events.md  # temps réel
│   └── import-format.md     # fichier d'import du parc
├── checklists/
│   └── requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/                              # colle : utilisateurs, authentification, layout
├── Models/User.php               # agency_id, deactivated_at, HasRoles
├── Actions/Users/                # CreateEmployee, ChangeUserActivation
├── Http/Middleware/              # EnsureUserIsActive (déconnecte un compte désactivé)
├── Exceptions/                   # SelfDeactivationException
└── Livewire/Users/               # écran Salariés (/salaries)
functional/                       # layers métier (convention xefi/laravel-osdd)
├── fleet/
│   ├── composer.json             # LayerManifest
│   ├── src/
│   │   ├── Models/               # Agency, MachineCategory, Machine
│   │   ├── Enums/                # MachineStatus, MachineTransition
│   │   ├── States/               # une classe par état de machine, transitions autorisées
│   │   ├── Actions/              # CreateMachine, UpdateMachine, ChangeMachineStatus, UpdateMachineVgp, RetireMachine, ImportFleet
│   │   ├── Data/                 # MachineAttributes, FleetImportReport
│   │   ├── Import/               # FleetImportLineParser (validation ligne à ligne)
│   │   ├── Uniqueness/           # MachineReferences (référence normalisée, unicité)
│   │   ├── Contracts/            # MachineRetirementGuard (implémenté par booking)
│   │   ├── Guards/               # UnrestrictedRetirement (liaison par défaut)
│   │   ├── Events/               # MachineChanged, FleetImported (diffusés sur fleet)
│   │   ├── Exceptions/           # RefusalException (base des refus affichés) et ses sous-classes
│   │   ├── Access/               # Controls/MachineControl, Perimeters/GlobalPerimeter
│   │   └── Livewire/             # MachineIndex, MachineForm, ImportFleetForm ; Concerns/DisplaysRefusals
│   ├── routes/{web,channels}.php # écrans du parc ; canal privé fleet
│   ├── database/{migrations,factories,seeders}/
│   ├── resources/{views,lang/fr}/
│   └── tests/{Feature,Unit,Doubles,Fixtures}/
└── booking/
    ├── composer.json
    ├── src/
    │   ├── Models/               # Customer, Reservation
    │   ├── Enums/                # ReservationStatus, ConflictReason, ReturnCondition
    │   ├── States/               # une classe par état de réservation
    │   ├── Actions/              # CreateReservation, DepartReservation, ReturnReservation, CancelReservation, RefreshReservationConflicts
    │   ├── Queries/              # AvailableMachinesQuery (une seule requête SQL)
    │   ├── Eligibility/          # MachineEligibility (statut, retard, VGP)
    │   ├── Planning/             # PlanningGrid, PlanningRow, PlanningCell, PlanningCellKind
    │   ├── Contracts/            # ReservationTransitionGuard (point d'extension, R12)
    │   ├── Extensions/           # ReservationTransitionGuards, ReservationDetailSections (registres, R12)
    │   ├── Guards/               # ActiveReservationsRetirementGuard (pour fleet)
    │   ├── Events/               # ReservationChanged (diffusé sur fleet)
    │   ├── Listeners/            # RefreshConflictsOnMachineChanged
    │   ├── Console/              # booking:flag-late-returns
    │   ├── Exceptions/           # refus typés (chevauchement, machine non réservable, sortie, dates, transition)
    │   ├── Access/Controls/      # ReservationControl
    │   └── Livewire/             # AvailabilitySearch, CreateReservationForm, ReservationList, ReservationDetail, Planning
    ├── routes/{web,console}.php  # écrans booking ; planification quotidienne
    ├── database/{migrations,factories}/
    ├── resources/{views,lang/fr}/
    └── tests/{Feature,Unit,Doubles,Concerns}/
compose.yaml                      # Sail : laravel.test, queue (worker), pgsql, soketi, mailpit
```

Les tests des layers sont chargés par l'autoload de dev de `composer.json` et lancés par les suites `Unit` et `Feature` de `phpunit.xml`. Tailwind scanne `functional/*/resources/views` et `functional/*/src`.

**Structure Decision**: un seul dépôt Laravel à la racine, à côté de `.specify/` et `specs/`. Le scaffold Laravel est créé dans un dossier temporaire puis déplacé à la racine, parce que `composer create-project` refuse un dossier non vide. `booking` dépend de `fleet` (réservation → machine), jamais l'inverse : `fleet` signale ses changements par événements, et `booking` les écoute pour recalculer les conflits. Quand `fleet` a besoin d'une règle de `booking` (refuser le retrait d'une machine réservée), il déclare un contrat (`MachineRetirementGuard`) que `booking` implémente et lie dans son service provider.

## Complexity Tracking

Aucune violation à justifier.
