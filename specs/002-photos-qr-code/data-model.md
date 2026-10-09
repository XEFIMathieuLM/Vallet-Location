# Data Model: Photos de départ et de retour via QR code

Mêmes conventions que la 001 : code en anglais, libellés en français par traduction, statuts en texte casté en enum PHP, aucune suppression en cascade en base. Toutes les tables ci-dessous appartiennent au layer `inspection`.

## Enum `InspectionStep`

`departure`, `return`. Porte la règle d'ouverture (FR-009) :

| Étape | Réservation autorisée |
|-------|-----------------------|
| `departure` | `confirmed` et `today >= start_date` |
| `return` | `in_progress` |

## CategoryView

Les vues requises d'une catégorie (FR-001).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| machine_category_id | référence → MachineCategory | obligatoire |
| label | texte | obligatoire, unique par catégorie |
| position | entier | ordre d'affichage |

Une catégorie sans ligne utilise `config('inspection.default_views')` : avant, arrière, gauche, droite, compteur d'heures (FR-002). Retirer la dernière vue d'une catégorie qui en a est refusé ; pour revenir au défaut, on le choisit explicitement (FR-001, US4 scénario 4).

## ReservationView

La liste de vues figée pour une réservation (FR-003).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| reservation_id | référence → Reservation | obligatoire |
| label | texte | copié de la catégorie ou du défaut |
| position | entier | |

Créée en une fois, au **premier** de ces deux moments : l'ouverture de la première session de la réservation, ou la première vérification de complétude par le guard (sortie ou retour tentés sans QR code jamais lancé). Pour une réservation sortie avant la mise en service, c'est donc au retour (FR-018). Jamais modifiée ensuite.

## PhotoSession

L'autorisation matérialisée par le QR code (FR-004 à FR-008).

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| reservation_id | référence → Reservation | obligatoire |
| step | `InspectionStep` | obligatoire |
| token_hash | texte | SHA-256 du jeton, unique ; le jeton en clair n'est jamais stocké |
| created_by | référence → User | obligatoire ; auteur des photos de la session (FR-013) |
| expires_at | date-heure | création + 30 min |
| revoked_at | date-heure | nullable |
| revoked_reason | `RevocationReason` | nullable : `replaced`, `step_validated`, `reservation_cancelled` |

**État dérivé, non stocké** : `active` si `revoked_at` est vide et `expires_at` est dans le futur ; sinon `expired` ou révoquée avec sa raison. Une seule transition réelle (révocation), donc pas de pattern State.

**Règles** :
- Générer une session révoque (`replaced`) la session active de la même réservation et de la même étape.
- Valider la sortie révoque les sessions `departure` actives (`step_validated`) ; valider le retour révoque les sessions `return`.
- Annuler la réservation révoque toutes ses sessions actives (`reservation_cancelled`), via un listener sur `ReservationChanged`.

## Photo

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| reservation_id | référence → Reservation | obligatoire |
| reservation_view_id | référence → ReservationView | obligatoire, appartient à la même réservation |
| step | `InspectionStep` | obligatoire |
| photo_session_id | référence → PhotoSession | obligatoire |
| created_at | date-heure | date de réception |

Fichier attaché par la médiathèque (collection `photo`, un seul fichier). Conversion `thumb` (400 px) **immédiate**, pour qu'elle existe quand le poste reçoit `photo.changed` ; conversion `display` (1 600 px) en file d'attente, l'original servant tant qu'elle n'est pas prête.

**Règles** :
- Ajout et suppression possibles seulement par une session active de la même étape (téléphone), ou depuis le poste tant que l'étape n'est pas validée (FR-014).
- Depuis le téléphone, une photo ne peut être supprimée que si sa réservation **et** son étape sont celles de la session du jeton ; toute autre photo est refusée comme si elle n'existait pas.
- Étape validée = `departure` dès que la réservation n'est plus `confirmed`, `return` dès qu'elle est `closed`. Après validation, toute écriture est refusée par exception typée.
- **Complétude** d'une étape : chaque `ReservationView` de la réservation a au moins une `Photo` de cette étape. C'est ce que vérifie `PhotosCompleteGuard` (FR-016, FR-017). Le guard **fige d'abord les vues** si la réservation n'en a pas encore : une réservation sans vue figée n'est jamais considérée comme complète.
- **Purge** (`Prunable`, FR-023) : réservation `closed` avec `returned_at` < il y a un an, sans dégât non traité, et sans dégât traité depuis moins d'un an ; ou réservation `cancelled` et photo reçue il y a plus d'un an.

## Damage

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| reservation_id | référence → Reservation | obligatoire ; la réservation doit avoir des photos de retour complètes |
| reservation_view_id | référence → ReservationView | obligatoire |
| comment | texte | obligatoire |
| reported_by | référence → User | obligatoire |
| reported_at | date-heure | obligatoire |
| resolved_by | référence → User | nullable |
| resolved_at | date-heure | nullable |

**« À refacturer »** (FR-021, FR-022) : une réservation l'est si elle a au moins un `Damage` dont `resolved_at` est vide. Calculé par requête, jamais stocké.

## Historique

`spatie/laravel-activitylog` (déjà en place par la 001) sur `PhotoSession` (création, révocation), `Photo` (création, suppression) et `Damage` (signalement, traitement), rattachés à la réservation (FR-024).

## Relations

```text
MachineCategory 1──* CategoryView
Reservation 1──* ReservationView 1──* Photo
Reservation 1──* PhotoSession 1──* Photo
Reservation 1──* Damage *──1 ReservationView
User 1──* PhotoSession (created_by)
User 1──* Damage (reported_by, resolved_by)
```

Les relations partent toujours du layer `inspection` vers `booking` et `fleet` : `Reservation` et `MachineCategory` ne déclarent aucune relation vers ces tables.
