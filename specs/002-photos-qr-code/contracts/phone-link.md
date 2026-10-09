# Contrat : lien du QR code

## Format

```text
https://<domaine de l'application>/photos/{jeton}
```

- `{jeton}` : 40 caractères aléatoires (alphabet URL-safe), générés par un générateur cryptographique.
- La base ne stocke que le SHA-256 du jeton. Le jeton en clair n'existe que dans le QR code affiché.

## Validité

Un jeton est accepté si et seulement si :

1. son hachage correspond à une `PhotoSession` ;
2. `revoked_at` est vide ;
3. `expires_at` est dans le futur (30 min après la génération) ;
4. l'étape de la session est toujours ouverte pour la réservation (voir `InspectionStep` dans [data-model.md](../data-model.md)).

Toute autre situation renvoie la même page « lien plus valable », sans distinguer jeton inconnu, expiré ou révoqué.

## Ce que le jeton autorise

| Autorisé | Interdit |
|----------|----------|
| lire la référence de la machine, le nom du client, l'étape, la liste des vues et leur état | coordonnées du client, dates, prix, autres réservations, photos de l'autre étape |
| envoyer une photo sur une vue de cette réservation, pour cette étape | envoyer sur une autre réservation ou une autre étape |
| voir et supprimer les photos de cette étape tant qu'elle n'est pas validée | modifier une étape validée |

## Protections

- Limite de débit par IP sur `/photos/*` (60 requêtes par minute).
- En-têtes `X-Robots-Tag: noindex` et `Referrer-Policy: no-referrer`.
- Photos : JPEG, PNG ou WebP, 15 Mo au plus (après réduction côté navigateur, environ 600 Ko attendus).
- Les miniatures affichées sur le téléphone passent par des URL temporaires signées de 30 min au plus.
