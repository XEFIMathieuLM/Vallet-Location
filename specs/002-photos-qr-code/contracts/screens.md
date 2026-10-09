# Contrat : écrans et actions

Les écrans du poste sont réservés aux salariés connectés. La page téléphone est publique mais n'agit que par son jeton (voir [phone-link.md](phone-link.md)).

## Poste (salariés connectés)

| Écran | Route | Actions | Refus affichés | Stories |
|-------|-------|---------|----------------|---------|
| Panneau « Photos » dans le détail d'une réservation | `/reservations/{id}` (section enregistrée par `inspection`) | lancer / régénérer le QR code de l'étape ouverte ; voir chaque vue (manquante / reçue, miniatures) ; supprimer une photo tant que l'étape n'est pas validée | étape non ouverte (réservation non confirmée, date de début non atteinte, réservation non en cours) | US1, US2 |
| Boutons « Enregistrer la sortie » / « Enregistrer le retour » (001) | `/reservations/{id}` | inchangés | **ajout** : « Photos manquantes : {vues} » quand le guard refuse ; le bouton est désactivé tant que le panneau indique une vue manquante, et le serveur refuse quoi qu'il arrive | US1, US2, FR-016, FR-017 |
| Comparaison départ / retour | `/reservations/{id}/photos` | voir vue par vue départ à gauche, retour à droite, date et auteur ; agrandir ; signaler un dégât (vue, commentaire) | signalement sans photos de retour complètes, commentaire vide | US3, FR-019, FR-020 |
| Dégâts à traiter | `/degats` | lister les réservations « à refacturer » (machine, client, agence, dégâts) ; ouvrir la comparaison ; marquer un dégât traité | — | US3, FR-021, FR-022 |
| Vues par catégorie | `/vues-photos` (liste des catégories), `/vues-photos/{categorie}` | choisir une catégorie ; ajouter, renommer, réordonner, retirer une vue ; revenir à la liste par défaut | retrait de la dernière vue, libellé en doublon dans la catégorie | US4, FR-001, FR-002 |

## Téléphone (public, par jeton)

| Écran | Route | Actions | Refus affichés | Stories |
|-------|-------|---------|----------------|---------|
| Prise de photos | `/photos/{jeton}` | voir machine (référence), client (nom), étape ; pour chaque vue : prendre une photo, voir sa miniature, la supprimer, réessayer un envoi échoué | lien expiré / remplacé / étape validée / réservation annulée (message unique : « Ce lien n'est plus valable, générez un nouveau QR code depuis le poste »), fichier non image, fichier trop lourd | US1, US2, FR-006, FR-007, FR-015 |

## Messages

Tous les libellés passent par les fichiers de traduction `fr` du layer `inspection`. La page téléphone ne révèle jamais pourquoi un jeton inconnu est refusé (même message qu'un jeton expiré).
