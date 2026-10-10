# Quickstart : vérifier le cœur de réservation

## Prérequis

- Docker Desktop démarré (WSL 2 sous Windows). PHP et Composer ne sont pas nécessaires sur le poste : tout passe par Laravel Sail.
- Le projet a été scaffoldé (tâches de setup de `tasks.md`).

## Démarrage

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm run dev
```

Le seeder crée 7 agences, des catégories soumises ou non à la VGP, des machines dans chaque statut (dont VGP expirée, proche de l'échéance ou non renseignée), des clients, des réservations dans chaque statut et les trois motifs de conflit, et un compte salarié par agence. Il affiche à la fin la liste des comptes (agence, e-mail) ; le mot de passe est celui par défaut de `UserFactory`.

## Vérifications automatiques

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail php vendor/bin/phpstan analyse
```

Chaque scénario d'acceptation de [spec.md](spec.md) correspond à un test Feature. Les deux commandes doivent passer sans erreur.

## Vérifications manuelles

Ouvrir l'application dans deux navigateurs, connectés avec deux salariés d'agences différentes (A et B).

| # | Action | Résultat attendu | Spec |
|---|--------|------------------|------|
| 1 | A réserve une machine du jour J+2 au J+5 | réservation créée ; B la voit apparaître sur le planning sans recharger | US1, US5, SC-006 |
| 2 | B tente de réserver la même machine du J+4 au J+7 | refus indiquant la réservation de A (dates, agence) | US1 |
| 3 | B réserve la même machine du J+6 au J+8 | acceptée | US1 |
| 4 | Passer une machine en « atelier » puis tenter de la réserver | refus « machine à l'atelier » | US2 |
| 5 | Mettre l'échéance VGP d'une nacelle à J+3 puis la réserver du J+2 au J+5 | refus indiquant l'échéance VGP | US2 |
| 6 | Passer en « atelier » une machine qui a une réservation à venir | la réservation apparaît « en conflit » dans la liste | FR-019 |
| 7 | Enregistrer la sortie puis le retour « atelier » d'une réservation du jour | réservation clôturée, machine en atelier | US3 |
| 8 | Importer [le fichier d'exemple](contracts/import-format.md) contenant des doublons | machines valides créées, doublons listés avec motif | US4, SC-007 |

La concurrence (deux validations simultanées, US1 scénario 4) est couverte par un test automatique, pas par une vérification manuelle.
