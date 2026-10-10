# Contrat : `BillingGateway`

Le port entre `billing` et le logiciel de facturation du client (B2). Toute implémentation respecte ce contrat ; les tests de `billing` utilisent `FakeBillingGateway`.

## Ce qui est envoyé : une ligne facturable

| Champ | Période de location | Dégât refacturé |
|-------|---------------------|-----------------|
| `idempotency_key` | UUID de la transmission | UUID de la transmission |
| `type` | `rental_period` | `damage` |
| `customer_ref` | référence client (`CustomerBillingAccount`) | idem |
| `reservation_ref` | identifiant de la réservation | identifiant de la réservation d'origine |
| `machine_reference` | référence unique de la machine | idem |
| `machine_category` | libellé de la catégorie | idem |
| `home_agency` | agence de rattachement de la machine | idem |
| `booking_agency` | agence qui a créé la réservation | idem |
| `period_start`, `period_end` | dates de la période (AAAA-MM-JJ) | — |
| `period_kind` | `intermediate` ou `final` | — |
| `days` | nombre de jours facturables | — |
| `damage_view` | — | vue concernée (ex. « gauche ») |
| `damage_comment` | — | commentaire du constat |
| `label` | — | libellé de la réparation |
| `amount_excl_tax_cents` | — (le logiciel applique ses tarifs) | montant HT en centimes |

## Les trois issues possibles

| Issue | Signal de l'implémentation | Effet dans `billing` |
|-------|---------------------------|---------------------|
| Accepté | retourne l'identifiant attribué par le logiciel | `pending → sent`, `external_ref` renseigné |
| Refusé (donnée invalide, client inconnu chez le logiciel) | lève `BillingSoftwareRejectedException` avec un motif lisible en français | `pending → failed`, `failure_reason = rejected`, `last_error` = motif |
| Injoignable (délai dépassé, erreur réseau, erreur serveur) | lève `BillingSoftwareUnreachableException` | reste `pending`, nouvelle échéance de relance |

Toute autre exception est un bug : elle remonte et la transmission reste `pending`, donc rattrapée par la relance.

## Obligations de toute implémentation

1. **Idempotence** : deux envois de la même `idempotency_key` ne créent qu'une ligne chez le logiciel. Si le logiciel ne dédoublonne pas lui-même, l'implémentation cherche la clé avant de créer, et renvoie l'identifiant existant.
2. **Délai borné** : aucun appel ne dépasse `billing.http_timeout_seconds`.
3. **Pas de secret dans les messages** : `last_error` est affiché aux salariés ; il ne contient ni jeton ni URL authentifiée.
4. **Rattachement** : le logiciel peut retrouver, à partir de `reservation_ref`, toutes les lignes (périodes et dégâts) d'une même location.

## `FakeBillingGateway`

Pour le développement et les tests. Modes : accepter tout, refuser une clé donnée avec un motif, simuler l'indisponibilité. Le mode et les lignes reçues sont gardés dans le cache de l'application, pour être partagés entre le worker de file et la console :

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway unreachable
```

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway accept
```

```bash
docker compose exec -u sail laravel.test php artisan billing:fake-gateway --received
```

La commande refuse de s'exécuter hors des environnements `local` et `testing`. Pour une clé déjà reçue, le faux logiciel renvoie le même identifiant sans ajouter de ligne.

## Critère d'arrêt pour l'adaptateur réel

Si le logiciel du client ne sait **ni** ignorer une clé déjà reçue, **ni** retrouver une ligne par sa clé, l'obligation 1 ne peut pas être tenue et SC-003 n'est plus garanti. Dans ce cas, on ne met pas en production : on revient au plan (`/speckit-plan`) pour choisir une autre garantie (par exemple, export de fichier seul, contrôlé par la comptabilité).

## À obtenir du client avant d'écrire l'adaptateur réel

- Le nom et la version du logiciel de facturation.
- Le moyen d'envoi automatique (API, connecteur, dépôt de fichier surveillé) et l'authentification.
- Sa capacité à dédoublonner sur une clé fournie.
- La façon dont il identifie un client (référence, numéro de compte).
- Son format d'import de fichier, pour l'export de secours ([export-format.md](export-format.md)).
