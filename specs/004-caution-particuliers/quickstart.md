# Quickstart : vérifier la caution des particuliers

Guide de vérification de bout en bout. Entités et états : [data-model.md](data-model.md). Écrans : [contracts/screens.md](contracts/screens.md). Contrat client : [contracts/customer-contract.md](contracts/customer-contract.md).

## Prérequis

- Les features 001, 002 et 003 sont implémentées et commitées, et cette branche est à jour par-dessus (contrat de disponibilité par section `7423fc9`, `AgencyMember`, enums de permissions).
- La migration `transmissions` de la 003 ayant été modifiée sur place, repartir d'une base propre :

```bash
docker compose exec -u sail laravel.test php artisan migrate:fresh --seed
```

- Si d'autres environnements Sail tournent, démarrer celui-ci avec un `COMPOSE_PROJECT_NAME` et des ports distincts (`APP_PORT`, `FORWARD_DB_PORT`…).
- Le planificateur tourne (rattrapage `deposit:reconcile`) :

```bash
docker compose exec -u sail laravel.test php artisan schedule:work
```

## Tests automatisés

```bash
docker compose exec -u sail laravel.test php artisan test functional/deposit/tests functional/booking/tests
```

```bash
docker compose exec -u sail laravel.test vendor/bin/phpstan analyse
```

```bash
docker compose exec -u sail laravel.test vendor/bin/pint --test
```

Un test Feature par scénario d'acceptation ; tests Unit purs pour les classes d'état de `Deposit` et le calcul de la retenue.

## Scénarios manuels

### 1. Sortie bloquée sans caution (US1)

1. Créer une réservation pour un nouveau client « particulier » sur une nacelle.
2. **Attendu** : la section « Caution » affiche « à encaisser » et le montant de la catégorie ; le bouton de sortie reste inactif.
3. Prendre les photos de départ (002), tenter la sortie depuis un autre onglet ouvert avant : refus « caution non encaissée ».
4. Encaisser (chèque, n° 1234567). **Attendu** : « encaissée », la sortie est acceptée.

### 2. Client existant sans type (US2)

1. Ouvrir une réservation confirmée d'un client créé avant la feature.
2. **Attendu** : « type de client à renseigner » ; sortie refusée. Qualifier « professionnel » : « non requise », la sortie redevient possible.

### 3. Restitution sans dégât (US3)

1. Enregistrer le retour avec les photos, sans dégât. **Attendu** : « à restituer ».
2. Restituer sans cocher la confirmation : refus. Cocher « aucun dégât constaté » : « restituée » ; l'historique montre la confirmation, l'auteur et l'agence.

### 4. Retenue sur dégât (US3)

1. Caution de 1 500 €, retour, signaler un dégât (002). **Attendu** : « bloquée par un dégât » ; la restitution est refusée.
2. Refacturer le dégât 450 € (003). **Attendu** : « à solder », retenue 450 €, restitution 1 050 €, non modifiables. Valider : « soldée ».

### 5. Annulation (US4)

Encaisser la caution d'une réservation confirmée, l'annuler. **Attendu** : « à restituer » ; la restitution ne demande pas de confirmation de dégât.

### 6. Liste et montants (US5, US6)

1. `/cautions` : les cautions en attente apparaissent avec leur ancienneté ; voyager de 8 jours (`travelTo` en test) : mise en évidence.
2. `/cautions/montants` : fixer 3 000 € pour « nacelle » ; une nouvelle réservation de nacelle exige 3 000 €, une caution déjà encaissée garde son montant.

### 7. Rattrapage

Supprimer à la main l'effet d'un listener (passer une caution `collected` d'une réservation clôturée) puis lancer :

```bash
docker compose exec -u sail laravel.test php artisan deposit:reconcile
```

**Attendu** : la caution passe « à restituer » ou « bloquée par un dégât » selon les dégâts.
