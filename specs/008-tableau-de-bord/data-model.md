# Data Model: Tableau de bord d'accueil

Cette feature ne crée ni table, ni colonne, ni migration, ni statut. Elle lit les données existantes et les présente au moyen de trois objets de valeur de `app/`, sans persistance.

## Données lues

| Donnée | Layer | Champs utilisés |
|---|---|---|
| `Agency` | fleet | `id`, `name` |
| `Machine` | fleet | `id`, `reference`, `machine_category_id`, `agency_id`, `status` (`MachineStatus`), `is_subject_to_vgp`, `vgp_due_date` |
| `MachineCategory` | fleet | `name` |
| `Reservation` | booking | `id`, `machine_id`, `customer_id`, `status` (`ReservationStatus`), `start_date`, `end_date`, `conflict_reason` (`ConflictReason`) |
| `Customer` | booking | `name` |
| `User` | app | `agency_id` (agence par défaut) |
| `Transmission` | billing | via `TransmissionsToHandle` |
| `ReservationCertificate` | certification | via `CertificatesToHandle` |
| `Deposit` | deposit | via `PendingDeposits` |
| `Reservation` sans bon de commande | accounts | via `MissingPurchaseOrders` |
| `Damage` | inspection | `resolved_at` (via `DamagesToHandle`) |
| `Sale` | sales | `status` (`SaleStatus`), `planned_handover_date` (via `OverdueSales`) |

## Objets de valeur (`app/Dashboard/`)

### DashboardSection

Une section de liste du tableau de bord.

| Champ | Type | Règle |
|---|---|---|
| `items` | `Collection<int, Reservation>` ou `Collection<int, Machine>` | au plus `config('dashboard.section_limit')` éléments (20), déjà triés |
| `total` | `int` | nombre total d'éléments de la section ; `total > items->count()` affiche le lien « voir tout » |

### PendingWorkCounter

Un compteur « à traiter ».

| Champ | Type | Règle |
|---|---|---|
| `key` | `string` | clé de traduction `dashboard.pending.<key>` (`transmissions`, `certificates`, `deposits`, `purchase_orders`, `damages`, `overdue_sales`) |
| `count` | `int` | nombre calculé par la requête du layer ; `0` est affiché de façon neutre |
| `url` | `string` | route de l'écran de la liste, sans filtre d'agence |

### PendingWorkCounters

Service qui renvoie la liste ordonnée des `PendingWorkCounter` que le salarié est autorisé à voir. Il vérifie la permission de chaque compteur avant d'exécuter sa requête.

## Paramètres (`config/dashboard.php`)

| Clé | Valeur | Spec |
|---|---|---|
| `upcoming_departure_days` | `7` | FR-006a |
| `vgp_watch_days` | `30` | FR-015a |
| `section_limit` | `20` | FR-010, FR-015a |

## Agence sélectionnée

| Paramètre `agence` de l'adresse | Agence affichée |
|---|---|
| absent ou vide | agence du salarié connecté |
| `toutes` | aucune (7 agences) |
| identifiant d'une agence existante | cette agence |
| autre valeur | agence du salarié connecté |
