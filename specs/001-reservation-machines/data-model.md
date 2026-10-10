# Data Model: Cœur de réservation des machines

Noms de code en anglais, libellés d'interface en français (fichiers de traduction). Statuts stockés en colonne texte et castés en enums PHP (pas d'enum en base). Aucune suppression en cascade au niveau de la base.

## Layer `fleet`

### Agency

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| name | texte | obligatoire, unique |
| address | texte | facultatif |

7 agences, créées par seeder.

### MachineCategory

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| name | texte | obligatoire, unique (ex. « Nacelle ») |
| is_vgp_required | booléen | si vrai, toutes les machines de la catégorie sont soumises à VGP |

La catégorie « Nacelle » est créée avec `is_vgp_required = true`.

### Machine

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| reference | texte | obligatoire, **unique** (index unique en base, comparaison insensible à la casse et aux espaces de bord) |
| machine_category_id | référence → MachineCategory | obligatoire |
| agency_id | référence → Agency | obligatoire (agence de rattachement) |
| status | `MachineStatus` | défaut `available` |
| is_subject_to_vgp | booléen | forcé à vrai si la catégorie a `is_vgp_required` |
| vgp_due_date | date | facultative ; si `is_subject_to_vgp` et vide → VGP non à jour |

**Règle VGP** (FR-011, FR-014) : une machine est conforme pour une période `[début, fin]` si elle n'est pas soumise à VGP, ou si `vgp_due_date >= fin`.

**`MachineStatus` — transitions** (pattern State) :

```text
available ──(sortie)──────────► rented_out
rented_out ──(retour en état)─► available
rented_out ──(retour atelier)─► workshop
available ◄──────────────────► workshop
available ◄──────────────────► out_of_order
workshop  ◄──────────────────► out_of_order
available ──(retrait)─────────► retired     (refusé si réservations confirmed / in_progress)
workshop ──(retrait)──────────► retired     (même condition)
out_of_order ──(retrait)──────► retired     (même condition)
```

Toute autre transition lève une exception typée.

**Réservable** : en `available`, ou en `rented_out` pour une période qui ne chevauche pas la réservation en cours **et à condition que cette réservation ne soit pas en retard** (`end_date` ≥ aujourd'hui). Une machine `rented_out` dont la réservation en cours a une `end_date` passée n'est réservable pour aucune période tant qu'elle n'est pas rentrée.

## Layer `booking`

### Customer

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| name | texte | obligatoire |
| phone | texte | facultatif |
| email | texte | facultatif, format e-mail |

Au moins un moyen de contact (téléphone ou e-mail) est requis. Sera enrichi par les specs suivantes (type particulier / grand compte, tarifs).

### Reservation

| Champ | Type | Règles |
|-------|------|--------|
| id | identifiant | |
| machine_id | référence → Machine | obligatoire |
| customer_id | référence → Customer | obligatoire |
| agency_id | référence → Agency | agence de l'auteur à la création |
| created_by | référence → User | obligatoire |
| start_date | date | obligatoire, ≥ aujourd'hui à la création |
| end_date | date | obligatoire, ≥ start_date ; ramenée à la date de retour si retour anticipé |
| planned_end_date | date | fin prévue initiale, conservée |
| status | `ReservationStatus` | défaut `confirmed` |
| departed_at | date-heure | renseignée à la sortie |
| returned_at | date-heure | renseignée au retour |
| conflict_reason | `ConflictReason` | null si aucun conflit |

**Contrainte d'exclusion** (FR-008, FR-009) : pour une même `machine_id`, aucune paire de réservations dont le statut n'est pas `cancelled` ne peut avoir des plages `[start_date, end_date]` (bornes incluses) qui se chevauchent.

**`ReservationStatus` — transitions** (pattern State) :

```text
confirmed ──(sortie)──► in_progress ──(retour)──► closed
confirmed ──(annulation)──► cancelled
```

- **Sortie** : autorisée si `today >= start_date`, machine en `available`, et VGP conforme jusqu'à `end_date`. Passe la machine en `rented_out`.
- **Retour** : précise « en état » ou « atelier » ; passe la machine en `available` ou `workshop`. Si retour avant `end_date`, `end_date` est ramenée à la date de retour. Si retour après `end_date` (retard), `end_date` n'est **jamais** repoussée : seul `returned_at` porte la date réelle.
- **Annulation** : uniquement depuis `confirmed` ; efface `conflict_reason`.

**`ConflictReason`** : `machine_unavailable` (atelier, panne), `vgp_expired` (VGP ne couvre plus la période), `machine_not_returned` (réservation précédente en retard ; seule la prochaine réservation `confirmed` de la machine est signalée).

Le motif est recalculé à chaque changement de machine (`MachineChanged`) et chaque nuit par `booking:flag-late-returns`.

## Identité (`app/`)

### User (starter kit)

Champs du starter kit + `agency_id` (référence → Agency, obligatoire) + `deactivated_at` (date-heure, nullable ; un compte désactivé est refusé à la connexion et déconnecté à sa requête suivante). Un compte créé reçoit un e-mail pour choisir son mot de passe. Rôle unique « salarié » portant toutes les permissions (`machines.manage`, `fleet.view`, `reservations.manage`, `users.manage`). Les layers voient l'utilisateur via le contrat `AgencyMember` (agence de rattachement). Inscription publique désactivée.

## Historique

`spatie/laravel-activitylog` sur Machine (statut, VGP, agence) et Reservation (statut, dates). Chaque entrée porte l'auteur, la date et l'agence de l'auteur au moment de l'action (propriété enregistrée avec l'entrée).

## Relations

```text
Agency 1──* Machine *──1 MachineCategory
Agency 1──* User
Machine 1──* Reservation *──1 Customer
User 1──* Reservation (created_by)
```
