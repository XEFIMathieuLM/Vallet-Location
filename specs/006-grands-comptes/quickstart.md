# Quickstart : vérifier les grands comptes et le bon de commande

Guide de vérification de bout en bout. Les entités sont dans [data-model.md](data-model.md), les points d'extension dans [contracts/booking-extensions.md](contracts/booking-extensions.md) et [contracts/billing-purchase-order.md](contracts/billing-purchase-order.md), les écrans dans [contracts/screens.md](contracts/screens.md).

## Prérequis

- Les features 001 à 004 sont implémentées et commitées, et cette branche est rebasée par-dessus (type de client de la 004, readiness par section de la 001).
- Conteneurs démarrés (`docker compose up -d`, avec `COMPOSE_PROJECT_NAME` et des ports distincts si d'autres environnements Sail tournent), base migrée et peuplée.
- `.env` : `BILLING_GATEWAY=fake`, `BILLING_GO_LIVE_DATE` antérieure aux réservations de test.
- Un worker de file tourne (transmissions de la 003) :

```bash
docker compose exec -u sail laravel.test php artisan queue:work
```

## Tests automatisés

```bash
docker compose exec -u sail laravel.test php artisan test functional/accounts/tests
```

```bash
docker compose exec -u sail laravel.test php artisan test
```

```bash
docker compose exec -u sail laravel.test vendor/bin/phpstan analyse
```

Un test Feature par scénario d'acceptation de la spec ; la suite complète vérifie que la 001 à la 004 restent vertes avec les points d'extension ajoutés.

## Scénarios manuels

### 1. Désigner un grand compte (US1)

1. Créer un client professionnel « Bâti-Ouest ». Sur `/grands-comptes`, tenter de le désigner. **Attendu** : refus « identifiant de facturation manquant ».
2. Renseigner son identifiant de facturation (écran des transmissions de la 003), puis le désigner. **Attendu** : il apparaît dans la liste, avec l'auteur et la date.
3. Tenter de désigner un client particulier. **Attendu** : refus « seul un client professionnel peut être grand compte ».
4. Ouvrir `/reservations/nouvelle` et rechercher « Bâti ». **Attendu** : badge « Grand compte » et précision sur le tarif négocié et le bon de commande.
5. Tenter de requalifier « Bâti-Ouest » en particulier. **Attendu** : refus tant que la désignation n'est pas retirée.

### 2. Bloquer la sortie sans bon de commande (US2)

1. Créer une réservation pour « Bâti-Ouest » commençant aujourd'hui ; compléter les photos de départ (002).
2. Enregistrer la sortie. **Attendu** : refus « bon de commande manquant ».
3. Dans la section « Bon de commande », saisir « BC-2026-0412 », puis enregistrer la sortie. **Attendu** : sortie acceptée ; la section affiche le numéro figé.
4. Tenter de modifier le numéro. **Attendu** : refus « numéro figé à la sortie ».

### 3. Numéro transmis à la facturation (US3)

1. Enregistrer le retour de la réservation du scénario 2.
2. **Attendu** : la ligne reçue par le faux logiciel porte `purchase_order_number = BC-2026-0412` et aucun montant de location :

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway --received
```

3. Refacturer un dégât de cette réservation. **Attendu** : la ligne `damage` porte le même numéro.
4. Passer le faux logiciel en indisponible, clôturer une autre réservation de grand compte, produire l'export de secours. **Attendu** : la dernière colonne `purchase_order_number` contient le numéro.

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway unreachable
```

### 4. Relancer les bons manquants (US4)

1. Créer deux réservations de « Bâti-Ouest » sans numéro, au départ dans 2 jours et dans 10 jours.
2. Ouvrir `/bons-de-commande`. **Attendu** : les deux réservations, la plus proche en tête et mise en évidence.
3. Saisir un numéro en ligne sur la première. **Attendu** : elle sort de la liste.
