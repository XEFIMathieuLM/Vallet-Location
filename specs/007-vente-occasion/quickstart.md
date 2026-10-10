# Quickstart: Vente de machines d'occasion

## Prérequis

- Branche `007-vente-occasion` à jour par-dessus les prérequis déclarés dans `tasks.md` (001, 002, 003 et leurs corrections de conformité ; points d'extension E1 à E4 selon l'arbitrage de la coordination).
- Environnement Sail dédié si d'autres tournent : `.env` copié depuis le checkout principal, `COMPOSE_PROJECT_NAME=vallet-007` et ports distincts (`APP_PORT`, `FORWARD_DB_PORT`, `VITE_PORT`, `PUSHER_PORT`).

```bash
docker compose up -d
docker compose exec -u sail laravel.test composer install
docker compose exec -u sail laravel.test php artisan migrate:fresh --seed
```

## Validation automatique

```bash
docker compose exec -u sail laravel.test php artisan test --compact functional/sales/tests
docker compose exec -u sail laravel.test php artisan test --compact
docker compose exec -u sail laravel.test vendor/bin/phpstan analyse
docker compose exec -u sail laravel.test vendor/bin/pint --dirty --format agent
```

Attendu : chaque scénario d'acceptation de `spec.md` a son test Feature dans `functional/sales/tests/Feature` ; les suites 001 à 003 restent vertes ; PHPStan à zéro erreur.

## Validation manuelle (faux logiciel de facturation)

1. Se connecter comme salarié de l'agence A. « Ventes d'occasion » → « Mettre en vente » : machine `NAC-0042`, 18 000 € HT. → La vente apparaît « en vente » ; dans le parc et le planning, `NAC-0042` porte le badge « En vente ».
2. Depuis l'agence B, ouvrir la liste des ventes : la vente apparaît sans recharger (temps réel). Tenter de remettre `NAC-0042` en vente → refus.
3. Réserver `NAC-0042` en location du 10 au 14 novembre → accepté (une machine en vente reste louable).
4. Sur la vente, enregistrer une offre de 16 500 € (nouveau client) et une de 15 000 € (client existant). Accepter 16 500 € avec une remise prévue le 12 novembre → refus (location jusqu'au 14). Remise prévue le 20 novembre → vente « réservée », l'autre offre « refusée », badge « Vendue sous réserve — remise le 20/11 ».
5. Tenter une location du 18 au 22 novembre → refus citant la remise du 20/11. Location du 15 au 19 novembre → acceptée.
6. `php artisan billing:fake-gateway unreachable` (commande de 003), puis, après le retour des locations et l'annulation éventuelle des réservations, enregistrer la remise → la vente est « vendue », `NAC-0042` est « retirée du parc », la transmission est « en attente ».
7. `php artisan billing:fake-gateway accept` → la transmission part au prochain passage du job ou du rattrapage ; elle passe « transmise », une seule fois.
8. Ouvrir « Transmissions » : une vente refusée (acheteur sans identifiant) y figure avec le lien vers la vente ; saisir l'identifiant, relancer → transmise.
9. Créer une autre vente, la réserver, puis « Lever la réservation » (motif) → retour « en vente », offre « retirée ». Annuler (motif) → « annulée », la machine reste au parc ; l'historique de la machine (`/ventes/machines/{id}`) montre les deux ventes.
