# Contrat : écrans et actions

L'application n'expose pas d'API : son interface est un ensemble d'écrans Livewire réservés aux salariés connectés. Ce contrat fixe ce que chaque écran permet et les refus qu'il doit afficher.

| Écran | Route | Actions | Refus affichés | Stories |
|-------|-------|---------|----------------|---------|
| Recherche de disponibilité | `/disponibilites` | filtrer par catégorie, agence de rattachement, période ; ouvrir la réservation d'une machine disponible | — | US1, FR-017 |
| Nouvelle réservation | `/reservations/nouvelle?machine=…&du=…&au=…` | choisir / créer le client, confirmer | chevauchement (dates + agence de la réservation en conflit), statut machine, VGP expirée (date), dates incohérentes | US1, US2 |
| Liste des réservations | `/reservations` | filtrer par statut, agence, période, « en conflit » ; ouvrir une réservation | — | US3, FR-019 |
| Détail d'une réservation | `/reservations/{id}` | enregistrer la sortie, enregistrer le retour (en état / atelier), annuler | sortie avant la date de début, VGP non conforme, machine indisponible, annulation après sortie | US2, US3 |
| Parc de machines | `/machines` | lister, filtrer, créer, modifier, changer le statut, retirer | référence en doublon, retrait avec réservations actives, transition de statut illégale | US4 |
| Fiche machine | `/machines/nouvelle`, `/machines/{id}/modifier` | créer, modifier (référence, catégorie, agence, soumise à VGP, échéance VGP) | référence en doublon | US4 |
| Import du parc | `/machines/import` | téléverser un fichier, voir le rapport | lignes rejetées avec motif (voir [import-format.md](import-format.md)) | US4, FR-005 |
| Planning | `/planning` | vue calendrier par machine, filtres catégorie / agence / période | — | US5 |
| Salariés | `/salaries` | créer un compte (e-mail pour choisir son mot de passe), changer son agence, désactiver / réactiver un compte | e-mail en doublon, désactivation de son propre compte | FR-021 |

## Mise à jour en temps réel

Les écrans Recherche, Liste des réservations, Parc et Planning se rafraîchissent à la réception des événements de [broadcast-events.md](broadcast-events.md), sans action de l'utilisateur.

## Messages

Tous les libellés et messages de refus passent par les fichiers de traduction `fr`. Un refus nomme toujours sa cause précise (FR-012).
