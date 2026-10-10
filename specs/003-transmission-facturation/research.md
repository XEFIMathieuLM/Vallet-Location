# Research: Transmission des locations et des réparations au logiciel de facturation

Les décisions de la 001 (R1–R11) et de la 002 (P1–P13) restent valables : Laravel + Livewire, OSDD, PostgreSQL, Sail, `spatie/laravel-activitylog`, `spatie/simple-excel`, contrôles par permission. Ce document ne traite que ce que la 003 ajoute.

## B1 — Un nouveau layer `billing`

- **Decision**: un layer OSDD `billing`, au-dessus des trois autres : `billing → inspection → booking → fleet`. Il possède les périodes facturables, les transmissions, le chiffrage des dégâts, l'export de secours et le relevé.
- **Rationale**: la facturation est un domaine distinct, avec son propre cycle de vie (envoi, échec, relance) et une dépendance externe (le logiciel du client). La placer dans `booking` ferait dépendre la réservation d'un système tiers.
- **Alternatives considered**: étendre `booking` (mélange réservation et échange externe ; `booking` ne pourrait plus être testé sans le logiciel de facturation) ; étendre `inspection` pour les dégâts (le dégât y est un constat ; son chiffrage et sa transmission sont de la facturation).

## B2 — Isoler le logiciel de facturation derrière un port

- **Decision**: `billing` définit une interface `BillingGateway` (une méthode : envoyer une ligne facturable, recevoir l'identifiant attribué par le logiciel). L'implémentation est choisie par configuration (`billing.gateway`). Deux implémentations au départ : `FakeBillingGateway` (dev et tests, pilotable : accepte, refuse, injoignable) et l'adaptateur du logiciel du client.
- **Rationale**: le nom du logiciel, son API et son format d'import ne sont pas encore connus (Assumptions de la spec). Tout le reste de la feature (périodes, file de transmission, relances, export, écrans) peut être construit et testé sans lui. Seul l'adaptateur attend l'information.
- **Alternatives considered**: attendre l'identification du logiciel avant de planifier (bloque toute la feature pour un seul composant) ; coder directement contre une API supposée (à réécrire).
- **Dépendance externe bloquante pour la mise en production** : le nom du logiciel, son mode d'authentification, son moyen d'envoi automatique, sa capacité à dédoublonner sur une clé, et son format d'import. Voir [contracts/billing-gateway.md](contracts/billing-gateway.md).
- **Faux logiciel observable hors tests** : `FakeBillingGateway` garde son mode (accepter, injoignable, refus par clé) et les lignes reçues dans le cache de l'application, pas en mémoire. Les envois passent par le worker de file, un autre processus que celui qui vérifie : sans stockage partagé, le quickstart ne pourrait ni basculer le faux logiciel ni constater ce qu'il a reçu. Une commande `billing:fake-gateway` change le mode et liste les clés reçues (refusée hors `local` et `testing`).

## B3 — Une file d'envoi persistante (outbox)

- **Decision**: chaque élément à transmettre (période de location ou dégât chiffré) crée **dans la même transaction** une ligne `Transmission` à l'état `pending`. Un job en file envoie la ligne ; une commande planifiée **chaque minute** renvoie en file les transmissions `pending` dont l'échéance de relance est passée. Une cadence plus lente rendrait inopérants les premiers délais de relance (1 minute, B5) ; la requête est bornée par l'index (`status`, `next_attempt_at`).
- **Rationale**: FR-005 à FR-008 et SC-001 exigent que rien ne se perde, même si le logiciel est injoignable, si le worker plante ou si un job est perdu. La ligne en base est la source de vérité ; le job n'est qu'un moyen de la traiter. FR-006 : le retour n'attend jamais le logiciel de facturation.
- **Alternatives considered**: envoyer directement depuis l'action de retour (bloque le salarié si le logiciel est lent, perd l'envoi si la requête échoue) ; un job sans ligne en base (un job perdu n'est pas rattrapable, pas de liste des échecs).

## B4 — Aucun doublon : garanties en base et clé d'idempotence

- **Decision**: trois garanties en base, plus une côté logiciel.
  1. `billable_periods` : contrainte d'exclusion PostgreSQL sur `reservation_id` + `daterange(start_date, end_date, '[]')`. Deux périodes d'une même location ne peuvent pas partager un jour.
  2. `transmissions` : au plus une transmission par période et par chiffrage de dégât (index uniques).
  3. Envoi sous verrou : le job prend `lockForUpdate()` sur la transmission, vérifie qu'elle est encore `pending`, envoie, puis change l'état dans la même transaction. L'export de secours prend le même verrou ; il attend donc la fin d'un envoi en cours et exclut ce qui vient d'être transmis.
  4. Chaque envoi porte une **clé d'idempotence** stable (l'UUID de la transmission). L'adaptateur l'utilise pour que le logiciel ignore un second envoi de la même clé ; si le logiciel ne sait pas dédoublonner, l'adaptateur cherche d'abord la clé chez lui avant de créer.
- **Rationale**: SC-003 (zéro jour facturé en double) et le cas limite « la réponse se perd ». Les garanties 1 à 3 sont portées par la base, pas par une convention. La 4 couvre le seul trou restant : un envoi accepté dont la réponse n'arrive jamais.
- **Alternatives considered**: vérifier « déjà transmis ? » en PHP avant d'envoyer (course entre deux workers) ; s'en remettre au comptable pour repérer les doublons.
- **Délai d'envoi** : le verrou est tenu pendant l'appel au logiciel ; le délai HTTP est donc borné (10 s, configurable).

## B5 — Cycle de vie d'une transmission (pattern State)

- **Decision**: `TransmissionStatus` (`pending`, `sent`, `exported`, `failed`) en colonne texte castée en enum, avec une classe d'état par statut et une exception typée pour toute transition illégale.

  ```text
  pending ──(accepté)──► sent
  pending ──(refusé)───► failed ──(relance manuelle)──► pending
  pending ──(export)───► exported
  failed ───(export)───► exported
  ```

  `sent` et `exported` sont terminaux.
- **Rationale**: plusieurs comportements changent selon l'état (envoyable, relançable, exportable, affichage), et des transitions sont interdites (relancer un élément transmis, exporter un élément déjà parti). C'est la règle Xefi pour un nouveau cycle de vie (`design-patterns:state`).
- **Injoignable ≠ refusé** : un logiciel injoignable (délai dépassé, erreur réseau, erreur 5xx) laisse la transmission `pending` avec une nouvelle échéance de relance (1, 5, 15, 60 minutes, puis toutes les heures) — FR-008. Un refus métier (client inconnu, donnée invalide) la passe `failed` avec le motif — FR-009, relance manuelle seulement (FR-010).

## B6 — Création des périodes facturables

- **Decision**:
  - **Période finale** : un listener sur `ReservationChanged` (001) crée la période finale quand la réservation passe `closed` : du lendemain de la dernière période (ou de la date de sortie) jusqu'à la date de retour.
  - **Périodes de fin de mois** : une commande `billing:close-months` planifiée chaque jour à 00:15 (Europe/Paris). Pour chaque réservation `in_progress`, elle crée une période intermédiaire pour chaque mois entièrement écoulé et pas encore couvert.
  - **Rattrapage** : la commande `billing:reconcile` (chaque minute, B3) crée la période finale de toute réservation `closed` qui n'en a pas, puis relance les transmissions dues.
  - Chaque création est idempotente : elle repart de la dernière période existante, et la contrainte d'exclusion de B4 refuse tout chevauchement.
- **Rationale**: le listener donne l'immédiateté (SC-002 : moins de 5 minutes). Le rattrapage garantit qu'un événement perdu ne fait pas disparaître une location (SC-001). Une exécution quotidienne de la clôture mensuelle, plutôt que le 1er du mois seulement, se rattrape seule après une panne (SC-001a : sous 24 h).
- **Dates** : la date de sortie est la date (heure de Paris) de `departed_at`, la date de retour celle de `returned_at`, jamais `end_date` (qui ne reflète pas un retard, voir data-model de la 001). Jours facturables = jours calendaires, bornes incluses.
- **Mise en service** : `billing.go_live_date` (config, obligatoire, sans valeur par défaut). Une réservation dont la date de retour (heure de Paris) est antérieure à cette date ne produit aucune période ; rendue le jour même, elle est transmise. Tant que la date du jour est antérieure à `go_live_date`, la clôture mensuelle ne crée rien : l'ancien circuit facture encore, et un déploiement anticipé ne doit pas provoquer de double facturation (SC-003). Sans date configurée, rien n'est transmis. Toute autre réservation (encore en cours, ou rendue après) est transmise **en entier depuis sa date de sortie**, même si elle est sortie avant (clarification du 2026-10-09, option A). La première exécution de `billing:close-months` après la mise en service crée donc d'un coup les périodes de tous les mois déjà écoulés des locations en cours.
- **Alternatives considered**: modifier `ReturnReservation` (001) pour créer la période dans sa transaction (couplage `booking → billing` interdit) ; ajouter un point d'extension « après retour » dans `booking` (possible, mais le rattrapage est de toute façon nécessaire et suffit à garantir l'absence de perte).

## B7 — Chiffrage des dégâts sans modifier le modèle d'`inspection`

- **Decision**: `billing` possède une table `damage_settlements` (une par dégât au plus) : issue `billed` (montant HT en centimes, libellé) ou `waived` (motif). Le règlement d'un dégât crée le `DamageSettlement`, crée la `Transmission` si `billed`, et appelle l'action `ResolveDamage` d'`inspection` pour renseigner `resolved_at`. La notion « à refacturer » de la 002 reste donc juste sans changement.
- **Point d'extension dans `inspection`** (livré par la 002, sa T052) : le registre `DamageActions`, rendu par la vue partielle `inspection::partials.damage-actions` sur les deux écrans qui permettent de clore un dégât, « Dégâts à traiter » et « Comparaison ». Quand `billing` enregistre ses actions (« Refacturer », « Ne pas refacturer »), l'action par défaut « Marquer traité » disparaît des deux écrans. FR-016 de la 003 remplace FR-022 de la 002, qui renvoie désormais à la 003.
- **Rationale**: `inspection` ne connaît pas `billing`, le sens de dépendance est respecté. Montant en centimes entiers : pas d'erreur d'arrondi.
- **Alternatives considered**: ajouter montant et motif sur `damages` (une migration de `billing` modifierait une table d'`inspection`) ; un écran de refacturation séparé en laissant « Marquer traité » actif (un dégât pourrait être clos sans être ni facturé ni justifié, ce qui contredit SC-006).
- **Coordination** : réglée avec la session de la 002 le 2026-10-09 (commit `d0f2213` sur `002-photos-qr-code`). Les actions de `billing` doivent fonctionner dans les deux écrans hôtes : elles ne reçoivent que le dégât.

## B8 — Identifiant client du logiciel de facturation

- **Decision**: une table `customer_billing_accounts` dans `billing` (`customer_id` unique, `external_ref`). Sans référence, la transmission passe directement `failed` avec le motif `customer_unknown`, sans appeler le logiciel. La référence se saisit depuis la liste des échecs, puis l'utilisateur relance.
- **Rationale**: la 003 ne crée pas le client dans le logiciel de facturation (Out of Scope) et ne modifie pas la table `customers` de `booking`. Le motif est clair et corrigeable (US2, scénarios 3 et 4).
- **Alternatives considered**: une colonne sur `customers` (migration de `billing` sur une table de `booking`) ; envoyer quand même et laisser le logiciel refuser (motif moins clair, appel inutile).

## B9 — Export de secours

- **Decision**: un export CSV produit par `spatie/simple-excel` (déjà installé), enregistré comme `BillingExport` (auteur, date, fichier sur un disque privé `billing-exports`). Toutes les transmissions `pending` et `failed` sont prises sous verrou, écrites dans le fichier, puis passent `exported` dans la même transaction. Le fichier reste téléchargeable depuis l'écran des exports.
- **Rationale**: FR-022 et FR-023. Le verrou commun avec l'envoi (B4) empêche qu'un élément soit à la fois envoyé et exporté. Le format exact sera aligné sur le format d'import du logiciel du client une fois connu ([contracts/export-format.md](contracts/export-format.md)) ; seule la mise en forme d'une ligne change.
- **Alternatives considered**: un export qui ne change pas l'état (chaque export suivant reprendrait les mêmes lignes : doublons).
- **Fichier en échec d'écriture** : la transaction est annulée, aucun élément ne passe `exported`.

## B10 — Alerte, relevé et détail de réservation

- **Decision**:
  - **Alerte** (FR-011) : un composant Livewire `billing` inclus dans le layout de `app/`. Il compte les transmissions `failed` et les `pending` créées il y a plus de 24 h (seuil configurable) et se rafraîchit toutes les 60 s.
  - **Relevé** (FR-018, FR-019) : un écran filtré par agence et par période, calculé par agrégats SQL (compter, sommer en base, pas en PHP). Les dégâts à traiter depuis plus de 7 jours (configurable) sont mis en évidence.
  - **Détail de réservation** (FR-017) : `billing` enregistre une section « Facturation » dans le registre `ReservationDetailSections` de `booking` (point d'extension conçu par la 002, P3, et livré par la 001). Elle liste les périodes, les dégâts chiffrés et l'état de chaque transmission.
- **Rationale**: réutilise les points d'extension existants ; aucune diffusion temps réel n'est exigée par la spec pour la facturation.

## B11 — Droits et traçabilité

- **Decision**: une permission `billing.manage` (chiffrer, classer non refacturé, relancer, renseigner la référence client, exporter, consulter le relevé), ajoutée au rôle unique « salarié » (même règle que la 001). `spatie/laravel-activitylog` (v5) : écritures explicites `activity('billing')->performedOn($reservation)` depuis les actions de `billing` (période créée, tentative, échec, relance, export, chiffrage, non-refacturation), pour que tout soit rattaché à la réservation (FR-020). Le trait `LogsActivity` sur les modèles de `billing` rattacherait l'entrée au modèle lui-même, pas à la réservation.
- **Rationale**: Out of Scope « droits différenciés par rôle » ; le code teste une permission, jamais un nom de rôle.
