# Quickstart : vérifier l'espace client

## Prérequis

- Environnement du worktree :
  - variables : `COMPOSE_PROJECT_NAME=vallet-009`, application sur `http://localhost:8099`, Mailpit sur `http://localhost:8199` ;
  - lancement : `export WWWUSER=$(id -u) WWWGROUP=$(id -g) && docker compose up -d`.
- Base de données : `docker compose exec -u sail laravel.test php artisan migrate:fresh --seed`.
- Données réelles du client (facultatif) : les scripts `storage/app/load-client-data/load.php` et `reprise-ech40.php`, copiés depuis le dossier principal.
- Un worker de file actif : `docker compose up -d queue`.

## Tests automatisés

```bash
docker compose exec -u sail laravel.test php artisan test functional/portal/tests
docker compose exec -u sail laravel.test php artisan test
docker compose exec -u sail laravel.test composer ci:check
```

## Parcours manuel

1. **Inscription** (US1) :
   - ouvrir `/espace-client/inscription`, créer un compte « professionnel » ;
   - Mailpit reçoit le lien de confirmation ;
   - avant le clic, `/espace-client` renvoie vers la page de confirmation ; après le clic, la recherche s'affiche.
2. **Séparation** (US1) : connecté comme client, ouvrir `/dashboard` puis `/reservations` : redirection vers la connexion des salariés. Se connecter à `/espace-client/connexion` avec un compte salarié : refusé.
3. **Prix** (US6) : comme salarié, `/prix-indicatifs`, saisir 95 € pour « Nacelle ». La recherche client de nacelles affiche « à partir de 95,00 € HT / jour ».
4. **Demande** (US2) :
   - chercher les nacelles d'une agence pour la semaine prochaine et demander une machine ;
   - la demande apparaît « en attente » dans `/espace-client/demandes` ;
   - elle apparaît sans rechargement dans `/demandes-en-ligne` côté salarié, et le badge de la barre latérale passe à 1.
5. **Confirmation avec rattachement** (US3) :
   - côté salarié, confirmer en choisissant « Créer la fiche » ;
   - une réservation confirmée existe (`/reservations`), avec la section « Demande en ligne » ;
   - Mailpit reçoit l'e-mail de confirmation, et l'attestation VGP si la machine y est soumise (005) ;
   - côté client, `/espace-client/reservations` montre la réservation et le lien de l'attestation dès son envoi.
6. **Refus de la 001** (US3) : envoyer une demande, réserver la même machine aux mêmes dates côté salarié, puis tenter de confirmer la demande. Le refus de chevauchement s'affiche et la demande reste en attente. La refuser avec un motif : le client reçoit l'e-mail avec le motif.
7. **Annulation et expiration** (US4) :
   - annuler une demande en attente depuis l'espace client ;
   - pour l'expiration, créer une demande pour aujourd'hui, puis lancer `php artisan portal:reconcile` le lendemain (`travelTo` dans les tests) : la demande passe « expirée » et un e-mail part.
8. **Cloisonnement** (US4, US5) : avec un second compte client, ouvrir l'URL de l'attestation d'une réservation du premier : 404.

## Résultats attendus

- Tous les scénarios d'acceptation des US1 à US6 ont leur test Feature vert (voir `tasks.md`).
- `composer ci:check` termine avec le code 0 : Pint, PHPStan à zéro erreur, tests.
- Les tests des features 001 à 008 restent verts sans modification.
