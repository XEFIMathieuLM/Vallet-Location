# Quickstart : vérifier la transmission à la facturation

Guide de vérification de bout en bout. Le détail des entités est dans [data-model.md](data-model.md), le contrat du logiciel de facturation dans [contracts/billing-gateway.md](contracts/billing-gateway.md).

## Prérequis

- Les features 001 et 002 sont implémentées et commitées, et cette branche est à jour par-dessus.
- Conteneurs démarrés (`docker compose up -d`), base migrée et peuplée (agences, catégories, machines, un salarié).
- `.env` : `BILLING_GATEWAY=fake`, `BILLING_GO_LIVE_DATE` antérieure aux réservations de test.
- Un worker de file et le planificateur tournent :

```bash
docker compose exec -u sail laravel.test php artisan queue:work
```

```bash
docker compose exec -u sail laravel.test php artisan schedule:work
```

## Tests automatisés

```bash
docker compose exec -u sail laravel.test php artisan test functional/billing/tests
```

```bash
docker compose exec -u sail laravel.test vendor/bin/phpstan analyse
```

Un test Feature par scénario d'acceptation de la spec ; tests Unit pour les classes d'état de `Transmission` et le découpage en périodes.

## Scénarios manuels

### 1. Location simple (US1, scénarios 1 à 3)

1. Créer une réservation, enregistrer la sortie, puis le retour 4 jours plus tard (avec les photos de la 002).
2. **Attendu** : la section « Facturation » du détail montre une période `finale` de 5 jours, transmise. Le faux logiciel a reçu une ligne `rental_period` :

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway --received
```

### 2. Location à cheval sur deux mois (US1, scénarios 5 à 7)

1. Sortir une réservation le 20 d'un mois.
2. Avancer l'horloge au 1er du mois suivant, puis lancer la clôture mensuelle :

```bash
docker compose exec -u sail laravel.test php artisan billing:close-months
```

3. **Attendu** : une période `intermédiaire` du 20 à la fin du mois, transmise ; la réservation reste en cours.
4. Enregistrer le retour le 5. **Attendu** : une période `finale` du 1er au 5 ; total des jours = durée réelle.
5. Relancer `billing:close-months`. **Attendu** : aucune nouvelle période.

### 3. Logiciel injoignable puis rétabli (US2, scénarios 1, 2, 5)

1. Rendre le faux logiciel injoignable, puis clôturer une réservation :

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway unreachable
```

2. **Attendu** : le retour s'enregistre immédiatement ; la transmission reste `en attente` avec une échéance de relance.
3. Rétablir le faux logiciel et attendre au plus une minute (passage de `billing:reconcile`). **Attendu** : transmise, sans action.

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway accept
```
4. Laisser une transmission en attente plus de 24 h (horloge avancée). **Attendu** : le bandeau d'alerte apparaît.

### 4. Client inconnu, correction et relance (US2, scénarios 3 et 4)

1. Clôturer une réservation dont le client n'a pas de référence de facturation.
2. **Attendu** : la transmission est `en échec`, motif « client inconnu », sur l'écran des transmissions.
3. Renseigner la référence, relancer. **Attendu** : transmise, sort de la liste.

### 5. Export de secours (US2, scénarios 6 et 7)

1. Faux logiciel injoignable ; clôturer deux réservations ; en transmettre une troisième normalement avant la coupure.
2. Produire un export. **Attendu** : le fichier contient les deux transmissions en attente, pas la troisième ; elles passent « transmises par export ».
3. Rétablir le logiciel, lancer `billing:reconcile`. **Attendu** : rien n'est renvoyé. Un nouvel export est refusé (« aucun élément à exporter »).

### 5 bis. Location en cours à la mise en service (FR-001)

1. Sortir une réservation, puis fixer `BILLING_GO_LIVE_DATE` deux mois plus tard et avancer l'horloge au lendemain de cette date.
2. Lancer `billing:close-months`. **Attendu** : une période intermédiaire par mois écoulé depuis la sortie, toutes transmises.
3. Une réservation rendue avant `BILLING_GO_LIVE_DATE` n'a aucune période.

### 6. Dégâts (US3)

1. Sur une réservation clôturée et transmise, avec un dégât signalé : « Refacturer », 450 €, « remplacement capot ».
2. **Attendu** : une ligne `damage` de 45000 centimes reçue, rattachée à la même réservation ; la location n'est pas renvoyée ; le dégât sort de la liste des dégâts à traiter.
3. Sur un autre dégât : « Ne pas refacturer » sans motif → refus ; avec « usure normale » → accepté, rien n'est transmis.
4. Tenter de modifier un dégât réglé. **Attendu** : impossible.

### 7. Relevé (US4)

1. Avec le jeu de 10 réservations de l'Independent Test de la spec, ouvrir le relevé du mois pour l'agence.
2. **Attendu** : 10 locations transmises, le total refacturé, le motif du non-refacturé, et le dégât à traiter mis en évidence s'il a plus de 7 jours.

## Contrôle de non-doublon

Après les scénarios, aucune réservation ne doit avoir deux périodes qui partagent un jour (garanti par la contrainte d'exclusion), et `billing:fake-gateway --received` ne doit lister aucune clé d'idempotence deux fois. Les tests Feature vérifient les deux.
