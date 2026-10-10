# Quickstart: Tableau de bord d'accueil

## Prérequis

Environnement Docker du worktree (projet `vallet-008`, port 8098) :

```bash
export WWWUSER=$(id -u) WWWGROUP=$(id -g)
docker compose up -d
docker compose exec -u sail laravel.test php artisan migrate:fresh --seed
```

## Tests automatisés

```bash
docker compose exec -u sail laravel.test php artisan test --compact tests/Feature/Dashboard
docker compose exec -u sail laravel.test php artisan test --compact --filter='DayOperationsQueryTest|FleetStatusCountsTest|VgpWatchListTest|DamagesToHandleTest|OverdueSalesTest'
docker compose exec -u sail laravel.test composer ci:check
```

Attendu : tout passe ; `composer ci:check` sort en code 0 (Pint, PHPStan à 0 erreur après `vendor/bin/phpstan clear-result-cache`, suite complète).

## Recette manuelle avec les données du client

Charger les données réelles dans l'environnement `vallet-008`, sans toucher l'environnement 8080 :

```bash
mkdir -p storage/app/load-client-data
cp /Users/macbook/Documents/Vallet-Location/storage/app/load-client-data/* storage/app/load-client-data/
docker compose exec -u sail laravel.test php storage/app/load-client-data/load.php
docker compose exec -u sail laravel.test php storage/app/load-client-data/reprise-ech40.php
```

Ces scripts restent hors du dépôt (`storage/app` est ignoré par git). Ensuite, se connecter sur http://localhost:8098 avec un salarié d'Annecy, à la date du 10/10/2026.

| Vérification | Résultat attendu |
|---|---|
| Arrivée après connexion | Le tableau de bord s'ouvre sur l'agence du salarié (Annecy) |
| VGP à surveiller | NAC118 « Expire le 15/10/2026 (dans 5 jours) » ; NAC089 « Expirée » dans son agence |
| État du parc | MINI07 compté dans « Atelier » et ECH40 dans « Sorties », dans leur agence respective |
| Départs à venir | Départs des 12, 13 et 14/10 groupés par date ; celui du 19/10 absent (au-delà de 7 jours) |
| COMP21 (particulier) | Son départ est listé ; le clic ouvre le détail, où la section Caution indique « à encaisser » |
| Toutes les agences | Les lignes affichent l'agence ; les compteurs ne changent pas |
| Temps réel | Une sortie enregistrée dans un autre onglet retire la ligne des départs sans rechargement |
| Bandeaux | Les bandeaux des transmissions et des attestations restent affichés au-dessus |
