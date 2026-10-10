@AGENTS.md

# Vallet Location

Outil de réservation de machines de travaux pour les 7 agences de Vallet Location. Laravel 13 + Livewire 4 (starter kit Livewire, Flux), PostgreSQL, temps réel Soketi.

La constitution du projet (`.specify/memory/constitution.md`) prime sur ce fichier : en cas de contradiction, c'est elle qui s'applique et ce fichier est corrigé.

## Spec-kit

- Les specs vivent dans `specs/<NNN-feature>/` (spec, plan, tasks), sur une branche `NNN-feature`. Lire le `plan.md` et le `tasks.md` de la feature en cours avant de coder.
- Ordre obligatoire : `/speckit-specify` → `/speckit-clarify` → `/speckit-plan` → `/speckit-tasks` → `/speckit-analyze` → `/speckit-implement`. `/speckit-analyze` doit être sans problème CRITIQUE, HAUT ou MOYEN avant d'implémenter.
- Une décision qui change le comportement métier passe par `/speckit-clarify` et `spec.md` avant le code ; un changement de périmètre passe par `plan.md` / `tasks.md`.
- Une feature empilée sur une autre déclare ses prérequis dans son `tasks.md`. Ne jamais réécrire l'historique d'une branche sur laquelle une autre est empilée (pas de squash ni de force push) ; prévenir les sessions des branches empilées après chaque push.

## Environnement

Aucun PHP local : tout passe par Docker (`compose.yaml` : `laravel.test`, `queue` (worker de file pour le temps réel), `pgsql`, `soketi`, `mailpit`).

- Démarrer : `docker compose up -d` (avec `WWWUSER` / `WWWGROUP` exportés)
- Artisan / Composer / npm : `docker compose exec -u sail laravel.test php artisan …` (idem `composer …`, `npm …`)
- Tests : `docker compose exec -u sail laravel.test php artisan test`
- Analyse statique : `docker compose exec -u sail laravel.test vendor/bin/phpstan analyse` ; vider le cache (`vendor/bin/phpstan clear-result-cache`) avant de conclure, un cache périmé masque des erreurs
- Formatage : `vendor/bin/pint --dirty`, et `vendor/bin/pint --test` doit passer
- CI (`.github/workflows/tests.yml`) : PHP 8.5 + PostgreSQL 18, lance `composer ci:check` (Pint, PHPStan, tests)

## Architecture

- Layers OSDD (`xefi/laravel-osdd`) dans `functional/`, générés avec les commandes `osdd:*` (`--layer=functional/<layer>`). Un nouveau domaine métier est un nouveau layer.
- Sens des dépendances : `billing → inspection → booking → fleet`. Un layer n'importe jamais une classe d'un layer qui dépend de lui.
- Quand un layer inférieur doit laisser un layer supérieur agir, il expose un point d'extension : contrat (`fleet` : `MachineRetirementGuard`), registres de `booking` (`Extensions/ReservationTransitionGuards`, `Extensions/ReservationDetailSections`). Le layer supérieur les remplit depuis son service provider.
- `app/` ne contient que la colle : utilisateurs, authentification (Fortify), layout, navigation, écran Salariés.
- Chaque layer déclare ses permissions (enum `<Layer>Permission`) et les crée dans son seeder (`<Layer>PermissionSeeder`), sans jamais toucher au rôle ; `database/seeders/DatabaseSeeder` appelle ces seeders avant `PermissionSeeder`, qui donne toutes les permissions existantes au rôle `salarie`. Les tests utilisent `Tests\TestCase::seedPermissions()` (à compléter avec le seeder de permissions de chaque nouveau layer).

## Conventions

- Code en anglais, interface en français via les fichiers de traduction (`lang/fr`, `functional/<layer>/resources/lang/fr`). Tailwind scanne `functional/*/resources/views` et `functional/*/src`.
- Statuts : colonne texte + enum PHP ; transitions en pattern State.
- Refus métier : sous-classe de `Functional\Fleet\Exceptions\RefusalException`, affichée dans les écrans par le trait Livewire `DisplaysRefusals` (pas de `try/catch`). Les hooks Livewire reçoivent leurs arguments par nom (`$e`, `$stopPropagation`).
- Règles `xefi/phpstan-xefi-rules` : pas de `try/catch` (utiliser `rescue()` qui relance), pas d'observers, pas de cascade, pas de noms génériques (`$data`, `$value`…), booléens préfixés `is_` / `has_` / `can_`, méthodes de moins de 40 lignes.
- Pas de commentaire de code ; fichiers de moins de 200 lignes.
- Contrôles d'accès par permission (`spatie/laravel-permission` + `lomkit/laravel-access-control`), jamais par nom de rôle.
- Factories avec le helper `faker()` de `xefi/faker-php-laravel`. Tests des layers dans `functional/<layer>/tests/` (autoload de dev) ; les tests de `booking` qui sortent, rentrent ou affichent une réservation utilisent `WithoutTransitionExtensions`.
- Commits et PR sans mention d'IA.
