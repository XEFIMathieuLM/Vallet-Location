# Quickstart: Refonte graphique

## Prérequis

- Docker, Chrome installé sur le poste, Node 20 (pour les captures seulement).
- Environnement isolé de la feature : projet Compose `vallet-010` sur le port 8110. L'environnement de démo du dossier principal (port 8080) n'est jamais touché.

```bash
cp ../../../.env .env   # depuis le worktree, puis ajuster :
# COMPOSE_PROJECT_NAME=vallet-010  APP_PORT=8110  APP_URL=http://localhost:8110
# VITE_PUSHER_HOST=localhost  FORWARD_DB_PORT=5510  PUSHER_PORT=6110  FORWARD_MAILPIT_PORT=1110
# FORWARD_MAILPIT_DASHBOARD_PORT=8210  VITE_PORT=5210  PUSHER_METRICS_PORT=9710  FORWARD_S3_PORT=8410
# PUSHER_HOST=host.docker.internal   # sinon, avec PUSHER_PORT=6110, Laravel ne joint pas soketi:6001
export WWWUSER=$(id -u) WWWGROUP=$(id -g)
docker compose up -d
docker compose exec -u sail laravel.test composer install
docker compose exec -u sail laravel.test npm ci
docker compose exec -u sail laravel.test npm run build
docker compose exec -u sail laravel.test php storage/app/load-client-data/load.php
docker compose exec -u sail laravel.test php storage/app/load-client-data/reprise-ech40.php
```

Les deux scripts de données sont copiés depuis `storage/app/load-client-data/` du dossier principal. Comptes salariés : `<agence>@vallet.test`, mot de passe des données de démo. Pour l'espace client, créer un compte par l'inscription de `/espace-client`, puis confirmer l'adresse depuis Mailpit (port 8210).

## Vérifications automatisées

```bash
docker compose exec -u sail laravel.test php artisan test --compact tests/Feature/Design tests/Unit/Design
docker compose exec -u sail laravel.test php artisan test --compact functional/fleet/tests/Unit/MachineFamilyTest.php functional/booking/tests/Unit/PlanningCellKindTest.php
docker compose exec -u sail laravel.test composer ci:check
```

Résultat attendu : tout est vert. `ci:check` lance Pint, PHPStan et la suite complète ; il doit se terminer avec le code 0.

## Captures avant / après

Le script [outils/capture.mjs](outils/capture.mjs) se connecte avec un salarié puis un client. Il capture les écrans de référence en 1440 px et en 375 px, en pleine page, et affiche `OVERFLOW <écran> <largeur>: <n>px` pour toute page qui défile horizontalement. Il tourne hors du projet, sans ajouter de dépendance :

```bash
mkdir -p "$TMPDIR/pw" && cd "$TMPDIR/pw" && npm init -y && npm i playwright-core@1.48.2
export STAFF_EMAIL=lyon-est@vallet.test STAFF_PASSWORD=… CUSTOMER_EMAIL=… CUSTOMER_PASSWORD=…
node <worktree>/specs/010-refonte-graphique/outils/capture.mjs <worktree>/specs/010-refonte-graphique/captures/apres
```

Résultat attendu :

- 38 captures, et aucune ligne `OVERFLOW` (SC-002 : aujourd'hui, `03-planning mobile: 542px` et les trois pages de l'espace client à 5 px) ;
- en thème sombre (préférence « Sombre » dans Paramètres > Apparence), mêmes captures pour le tableau de bord, le planning et le détail de réservation.

## Revue visuelle (SC-001, SC-003, SC-004)

Pour chaque capture après :

1. **Langage visuel** : police Inter Tight, titres gras et serrés, angles droits, aucune ombre sur les cartes.
2. **Jaune** : au plus un bouton jaune hors modale, jamais de texte jaune sur blanc.
3. **Statuts** : badges en pastille, couleurs désaturées.
4. **Focus** : au clavier (Tab), un contour visible sur chaque élément interactif, sur fond blanc, noir et jaune.
5. **Contenu** : les mêmes informations et actions que la capture avant correspondante.
