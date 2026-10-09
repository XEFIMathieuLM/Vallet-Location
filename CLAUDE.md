@AGENTS.md

# Vallet Location

Outil de réservation de machines de travaux pour les 7 agences de Vallet Location. Laravel 13 + Livewire 4 (starter kit Livewire, Flux), PostgreSQL, temps réel Soketi.

## Spec-kit

Les specs vivent dans `specs/<NNN-feature>/` (spec, plan, tasks). Lire le `plan.md` et le `tasks.md` de la feature en cours avant de coder.

## Environnement

Aucun PHP local : tout passe par Docker (Sail, `compose.yaml` : `laravel.test`, `pgsql`, `soketi`, `mailpit`).

- Démarrer : `docker compose up -d`
- Artisan / Composer / npm : `docker compose exec -u sail laravel.test php artisan …` (idem `composer …`, `npm …`)
- Tests : `docker compose exec -u sail laravel.test php artisan test`
- Analyse statique : `docker compose exec -u sail laravel.test vendor/bin/phpstan analyse`

## Architecture

- Layers OSDD (`xefi/laravel-osdd`) dans `functional/` : `fleet` (agences, catégories, machines, VGP) et `booking` (clients, réservations). `booking` dépend de `fleet`, jamais l'inverse.
- `app/` ne contient que la colle : utilisateurs, authentification (Fortify), layout.
- Générer dans un layer avec les commandes `osdd:*` (`--layer=functional/fleet`).

## Conventions

- Code en anglais, interface en français via les fichiers de traduction (`lang/fr`, `functional/<layer>/resources/lang/fr`).
- Statuts : colonne texte + enum PHP ; transitions en pattern State.
- Règles `xefi/phpstan-xefi-rules` : pas de `try/catch` (utiliser `rescue()` qui relance), pas d'observers, pas de cascade, pas de noms génériques (`$data`, `$value`…), booléens préfixés `is_` / `has_` / `can_`.
- Contrôles d'accès par permission (`spatie/laravel-permission` + `lomkit/laravel-access-control`), jamais par nom de rôle.
- Factories avec le helper `faker()` de `xefi/faker-php-laravel`.
- Commits sans mention d'IA.
