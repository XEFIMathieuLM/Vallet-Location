# Quickstart : vérifier l'envoi de l'attestation VGP

## Prérequis

- Branche `005-attestation-vgp` mise à jour sur la 003 corrigée, avec les points d'extension de [contracts/extension-points.md](contracts/extension-points.md) livrés.
- Environnement Sail isolé des autres features (d'autres environnements occupent 8080, 8082, 8083 et 5432 à 5435) : `.env` copié depuis le dépôt principal, avec `COMPOSE_PROJECT_NAME=vallet-005` et des ports libres, par exemple `APP_PORT=8085`, `VITE_PORT=5178`, `FORWARD_DB_PORT=5437`, `FORWARD_MAILPIT_PORT=1029`, `FORWARD_MAILPIT_DASHBOARD_PORT=8029` (et le port Soketi, à décaler de la même façon).
- `CERTIFICATION_GO_LIVE_DATE` renseignée (date du jour pour la recette).

```bash
docker compose up -d
docker compose exec -u sail laravel.test php artisan migrate --seed
```

## Tests automatisés

```bash
docker compose exec -u sail laravel.test php artisan test functional/certification/tests
docker compose exec -u sail laravel.test php artisan test
docker compose exec -u sail laravel.test vendor/bin/phpstan analyse
docker compose exec -u sail laravel.test vendor/bin/pint --test
```

Attendu : tout vert, zéro erreur Larastan, aucun fichier à reformater.

## Recette manuelle (worker et planificateur lancés)

```bash
docker compose exec -u sail laravel.test php artisan queue:work
docker compose exec -u sail laravel.test php artisan schedule:work
```

Les e-mails arrivent dans Mailpit (`http://localhost:8029`).

| # | Étapes | Résultat attendu | Spec |
|---|---|---|---|
| 1 | « Rapports VGP » → une nacelle sans rapport → déposer un PDF (vérification aujourd'hui, échéance dans 6 mois) | rapport en vigueur affiché ; échéance de la machine mise à jour ; dépôt dans l'historique de la machine | US2 sc. 1 |
| 2 | Réserver cette nacelle pour un client avec e-mail | réservation confirmée immédiatement ; e-mail dans Mailpit avec le PDF ; section « Attestation VGP » : « Envoyée le … à … » sans recharger | US1 sc. 1, 3 |
| 3 | Réserver une mini-pelle non soumise à VGP | aucun e-mail, aucune section attestation | US1 sc. 2 |
| 4 | Réserver une nacelle pour un client sans e-mail, puis tenter la sortie le jour du départ | réservation confirmée ; sortie refusée « e-mail du client manquant » ; la réservation est dans « Attestations à traiter » et le bandeau s'affiche | US3 sc. 1, 2 ; US4 sc. 4 |
| 5 | Sur cette réservation, renseigner l'e-mail du client | e-mail envoyé ; sortie possible (sous réserve des photos et des autres conditions) | US3 sc. 3, 4 |
| 6 | Autre client sans e-mail : « Rapport VGP remis en main propre » | état « remise en main propre », trace dans l'historique, sortie débloquée | US3 sc. 7 |
| 7 | Réserver une machine soumise à VGP sans rapport | « en attente : rapport non déposé » ; déposer le rapport → l'e-mail part | US2 sc. 2, 3 |
| 8 | Arrêter Mailpit (`docker compose stop mailpit`), réserver une nacelle, attendre, relancer Mailpit | « en attente d'envoi », relances visibles ; après redémarrage, un seul e-mail reçu | US4 sc. 1, 2 |
| 9 | « Renvoyer l'attestation » sur la réservation du test 2 | second e-mail ; deux envois dans l'historique, le second avec son auteur | US5 sc. 1 |
| 10 | Avec une réservation confirmée créée avant `CERTIFICATION_GO_LIVE_DATE` (date dans le futur, puis remise à aujourd'hui) | aucune attestation avant la date ; à la date, l'attestation est ouverte et envoyée par le rattrapage | Clarification Q2 |
