# Feature Specification: Grands comptes : tarifs négociés et bon de commande

**Feature Branch**: `006-grands-comptes`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: fiche `spec.md.txt` (Vallet Location) — règle « Les grands comptes réservent avec leurs tarifs négociés et un bon de commande ». S'appuie sur `001-reservation-machines` (clients, réservations, sortie), `003-transmission-facturation` (identifiant client dans le logiciel de facturation, transmission des périodes et des dégâts, export de secours) et `004-caution-particuliers` (type de client particulier / professionnel).

## Contexte

Une partie du chiffre d'affaires de Vallet Location vient de quelques grandes entreprises (BTP, industriels, collectivités) qui ont négocié des tarifs avec la direction et qui n'acceptent une facture que si elle porte le numéro de leur bon de commande. Aujourd'hui, rien dans l'outil ne distingue ces clients : un salarié peut faire partir une machine sans bon de commande, et la facture correspondante est contestée ou payée avec des mois de retard.

Le logiciel de facturation reste la référence pour les tarifs, y compris les tarifs négociés (feature 003) : l'outil ne saisit ni ne calcule aucun prix. Cette fonctionnalité identifie les grands comptes, fait savoir au salarié que le tarif négocié s'appliquera, exige le numéro de bon de commande avant que la machine ne parte, et transmet ce numéro au logiciel de facturation avec chaque élément facturé de la réservation.

## Clarifications

### Session 2026-10-10

- Q: Où vivent les tarifs négociés des grands comptes ? → A: Uniquement dans le logiciel de facturation. L'outil marque le client « grand compte », indique au salarié que le tarif négocié est appliqué par la facturation, et ne saisit ni ne transmet aucun prix (FR-004, Out of Scope).
- Q: À quel moment le bon de commande devient-il obligatoire ? → A: Avant la sortie. Une réservation peut être créée sans bon de commande ; la sortie est refusée tant qu'il manque (FR-007).
- Q: Que saisit-on pour le bon de commande ? → A: Le numéro seul, sans document joint (FR-005).
- Q: Comment un bon de commande se rattache-t-il aux réservations ? → A: Chaque réservation porte son numéro ; un même numéro peut servir à plusieurs réservations du même client (bon cadre, chantier). Aucun plafond ni aucune date de validité ne sont suivis dans l'outil (FR-006).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Désigner un client professionnel comme grand compte (Priority: P1)

Un salarié ouvre la fiche d'un client professionnel qui a un accord tarifaire avec Vallet Location et le désigne « grand compte ». Dès lors, partout où le client apparaît (recherche de client à la réservation, détail de la réservation), l'outil indique qu'il s'agit d'un grand compte, que son tarif négocié est appliqué par le logiciel de facturation, et qu'un bon de commande est exigé avant la sortie.

**Why this priority**: Toutes les autres règles en dépendent. Sans désignation fiable, l'outil ne sait pas à qui exiger un bon de commande.

**Independent Test**: Désigner grand compte un client professionnel dont l'identifiant dans le logiciel de facturation est renseigné : à la création d'une réservation pour ce client, l'outil affiche « Grand compte — tarif négocié appliqué par la facturation — bon de commande exigé avant la sortie ». Tenter la même désignation sur un client particulier : refus.

**Acceptance Scenarios**:

1. **Given** un client professionnel dont l'identifiant dans le logiciel de facturation est renseigné, **When** un salarié le désigne grand compte, **Then** le client est grand compte et l'historique du client conserve l'auteur, l'agence et la date.
2. **Given** un client particulier, ou un client dont le type est « à renseigner », **When** un salarié tente de le désigner grand compte, **Then** l'outil refuse en indiquant que seul un client professionnel peut être grand compte.
3. **Given** un client professionnel sans identifiant dans le logiciel de facturation, **When** un salarié tente de le désigner grand compte, **Then** l'outil refuse en indiquant que l'identifiant de facturation doit être renseigné d'abord, faute de quoi le tarif négocié ne pourrait pas s'appliquer.
4. **Given** un client grand compte, **When** un salarié le recherche pour créer une réservation, **Then** le client est signalé grand compte dans la liste, et le formulaire indique que le tarif négocié est appliqué par la facturation et que le bon de commande sera exigé avant la sortie.
5. **Given** un client grand compte, **When** un salarié retire la désignation, **Then** le client redevient un professionnel ordinaire et le bon de commande n'est plus exigé pour ses réservations non sorties.
6. **Given** un client grand compte, **When** un salarié tente de le requalifier « particulier », **Then** l'outil refuse tant que la désignation grand compte n'a pas été retirée.

---

### User Story 2 - Exiger le bon de commande avant la sortie (Priority: P1)

Le client grand compte réserve souvent par téléphone et envoie son bon de commande ensuite. Le salarié crée la réservation sans attendre ; le détail de la réservation affiche une section « Bon de commande » à l'état « à saisir ». Dès que le client transmet son numéro, un salarié le saisit. Le jour du départ, la sortie est refusée tant que le numéro n'est pas saisi.

**Why this priority**: C'est la règle de la fiche. Une machine partie sans bon de commande produit une facture que le grand compte refuse de payer.

**Independent Test**: Sur une réservation confirmée d'un grand compte sans bon de commande, tenter la sortie : refus « bon de commande manquant ». Saisir le numéro, retenter : la sortie est acceptée (sous réserve des autres règles).

**Acceptance Scenarios**:

1. **Given** une réservation confirmée d'un client grand compte, **When** le salarié ouvre son détail, **Then** une section « Bon de commande » affiche l'état « à saisir » et rappelle que le tarif négocié est appliqué par la facturation.
2. **Given** une réservation confirmée d'un grand compte sans numéro de bon de commande, **When** le salarié tente d'enregistrer la sortie, **Then** l'outil refuse en indiquant que le numéro de bon de commande doit être saisi.
3. **Given** la même réservation, **When** le salarié saisit le numéro « BC-2026-0412 », **Then** la section affiche le numéro avec l'auteur, l'agence et la date de saisie, et l'étape « départ » est signalée prête du point de vue du bon de commande.
4. **Given** une réservation d'un grand compte dont le numéro est saisi, **When** le salarié enregistre la sortie, **Then** la sortie est acceptée si les autres règles (VGP, disponibilité, photos de départ) sont respectées.
5. **Given** le formulaire de création de réservation pour un grand compte, **When** le salarié connaît déjà le numéro et le saisit, **Then** la réservation est créée avec son numéro de bon de commande.
6. **Given** un numéro saisi sur une réservation confirmée, **When** un salarié le corrige avant la sortie, **Then** le nouveau numéro remplace l'ancien et l'historique conserve les deux, avec l'auteur et la date.
7. **Given** une réservation en cours ou clôturée, **When** un salarié tente de modifier son numéro de bon de commande, **Then** l'outil refuse : le numéro est figé à la sortie.
8. **Given** le numéro « BC-CHANTIER-ROUEN » déjà saisi sur une réservation d'un grand compte, **When** un salarié saisit le même numéro sur une autre réservation de ce client, **Then** la saisie est acceptée.
9. **Given** une réservation confirmée d'un client professionnel qui n'est pas grand compte, **When** le salarié enregistre la sortie sans numéro de bon de commande, **Then** la sortie n'est pas bloquée par le bon de commande ; s'il le souhaite, le salarié peut saisir un numéro, qui sera transmis de la même façon.

---

### User Story 3 - Transmettre le numéro de bon de commande à la facturation (Priority: P1)

Chaque élément de la réservation transmis au logiciel de facturation (périodes de location et dégâts refacturés, feature 003) porte le numéro de bon de commande de la réservation, pour que la facture du grand compte le reprenne et que le tarif négocié s'applique au bon client. L'export de secours le contient aussi.

**Why this priority**: Saisir le numéro sans le transmettre ne change rien pour le grand compte : c'est sur la facture que le numéro est attendu.

**Independent Test**: Clôturer une réservation d'un grand compte portant le numéro « BC-2026-0412 » : la période transmise contient ce numéro et l'identifiant du client dans le logiciel de facturation. Refacturer un dégât de la même réservation : la ligne de dégât porte aussi ce numéro.

**Acceptance Scenarios**:

1. **Given** une réservation d'un grand compte sortie avec le numéro « BC-2026-0412 », **When** sa période finale est transmise au retour, **Then** l'élément transmis contient le numéro de bon de commande en plus des informations de la feature 003, et aucun montant de location.
2. **Given** la même réservation encore en cours à une fin de mois, **When** la période intermédiaire est transmise, **Then** elle porte le même numéro.
3. **Given** un dégât refacturé sur cette réservation, **When** il est transmis, **Then** il porte le même numéro de bon de commande.
4. **Given** des éléments en attente ou en échec portant un numéro de bon de commande, **When** un salarié produit l'export de secours, **Then** chaque ligne de l'export contient le numéro.
5. **Given** une réservation sans numéro de bon de commande (client particulier, professionnel ordinaire, ou location sortie avant la mise en service), **When** ses éléments sont transmis, **Then** ils sont transmis comme dans la feature 003, sans numéro.
6. **Given** une période déjà transmise avant la mise en service de cette fonctionnalité, **When** la fonctionnalité est mise en service, **Then** cette période n'est pas retransmise pour y ajouter un numéro.

---

### User Story 4 - Relancer les bons de commande manquants avant le départ (Priority: P2)

Un salarié consulte la liste des réservations confirmées de grands comptes qui n'ont pas encore de numéro de bon de commande, triées par date de départ, pour relancer chaque client avant le jour de la sortie plutôt que de le découvrir au comptoir.

**Why this priority**: Le blocage de la sortie (US2) garantit la règle ; la liste évite qu'un client attende au comptoir parce que son bon de commande n'a pas été demandé à temps.

**Independent Test**: Avec 3 réservations de grands comptes (une sans numéro au départ dans 2 jours, une sans numéro au départ dans 10 jours, une avec numéro), la liste affiche les deux premières, la plus proche en tête et mise en évidence.

**Acceptance Scenarios**:

1. **Given** des réservations confirmées de grands comptes sans numéro dans plusieurs agences, **When** un salarié ouvre la liste, **Then** il voit pour chacune la réservation, le client, la machine, l'agence de rattachement et la date de départ, triées par date de départ.
2. **Given** une réservation sans numéro dont le départ a lieu dans 3 jours ou moins, **When** un salarié consulte la liste, **Then** elle est mise en évidence.
3. **Given** une réservation de la liste, **When** un salarié saisit son numéro de bon de commande, **Then** elle sort de la liste.
4. **Given** la liste, **When** un salarié filtre sur une agence, **Then** seules les réservations dont la machine est rattachée à cette agence sont affichées.

---

### Edge Cases

- **Client désigné grand compte alors qu'il a des réservations confirmées** : le bon de commande devient exigé avant leur sortie ; elles apparaissent dans la liste de la US4.
- **Désignation retirée alors qu'une réservation confirmée a déjà un numéro** : le numéro reste sur la réservation et est transmis ; il n'est simplement plus exigé.
- **Réservation déjà en cours à la mise en service** : aucun bon de commande n'est exigé ; ses périodes restantes sont transmises sans numéro.
- **Réservation confirmée à la mise en service d'un client ensuite désigné grand compte** : le numéro est exigé avant sa sortie.
- **Client requalifié professionnel → particulier** : refusé tant qu'il est grand compte (US1, scénario 6) ; une fois la désignation retirée, les règles de la feature 004 s'appliquent (caution).
- **Numéro saisi avec des espaces avant ou après** : les espaces superflus sont retirés ; un numéro vide ou composé uniquement d'espaces est refusé.
- **Numéro trop long** : refusé au-delà de 50 caractères.
- **Deux salariés qui saisissent au même instant un numéro différent sur la même réservation** : le dernier enregistré l'emporte, chaque saisie est tracée dans l'historique ; aucune sortie ne peut être enregistrée sans qu'un numéro soit présent au moment de la sortie.
- **Sortie refusée pour une autre raison** (VGP, photos, caution) après saisie du numéro : le numéro reste sur la réservation et reste corrigeable.
- **Réservation annulée** : son numéro reste visible dans l'historique ; rien n'est transmis (feature 003).
- **Identifiant de facturation du grand compte retiré ou modifié après la désignation** : la désignation reste ; une transmission sans identifiant échoue avec le motif « client inconnu », comme dans la feature 003.
- **Logiciel de facturation qui refuse un numéro de bon de commande** (format non reconnu) : la transmission passe en échec avec le motif renvoyé, comme tout refus de la feature 003 ; le numéro étant figé après la sortie, la correction se fait dans le logiciel de facturation avant relance.
- **Grand compte réservant depuis n'importe quelle agence** : la désignation vaut pour les 7 agences ; tout salarié de toute agence peut saisir le numéro.

## Requirements *(mandatory)*

### Functional Requirements

**Désignation des grands comptes**

- **FR-001**: Les salariés DOIVENT pouvoir désigner un client « grand compte » et retirer cette désignation. Seul un client de type « professionnel » (feature 004) peut être désigné ; un client particulier ou de type « à renseigner » NE DOIT PAS l'être.
- **FR-002**: Le système DOIT refuser la désignation d'un client dont l'identifiant dans le logiciel de facturation (feature 003) n'est pas renseigné, en indiquant pourquoi.
- **FR-003**: Le système DOIT refuser de requalifier « particulier » un client désigné grand compte tant que la désignation n'est pas retirée.
- **FR-004**: Le système DOIT signaler un client grand compte partout où il est choisi ou affiché pour une réservation (recherche de client, formulaire de création, détail de la réservation), avec la mention que le tarif négocié est appliqué par le logiciel de facturation. Le système NE DOIT saisir, afficher ni calculer aucun prix.

**Bon de commande**

- **FR-005**: Les salariés DOIVENT pouvoir saisir un numéro de bon de commande sur une réservation confirmée d'un client professionnel, à sa création ou ensuite : texte de 1 à 50 caractères, espaces superflus retirés. Aucun document n'est joint.
- **FR-006**: Un même numéro de bon de commande DOIT pouvoir être saisi sur plusieurs réservations ; le système ne suit ni plafond ni date de validité du bon de commande.
- **FR-007**: Le système DOIT refuser l'enregistrement de la sortie d'une réservation dont le client est grand compte au moment de la sortie et qui n'a pas de numéro de bon de commande, en indiquant la raison. Ce refus DOIT être garanti côté serveur, au moment même de la sortie, même si l'interface a laissé le bouton actif.
- **FR-008**: Le système DOIT afficher, dans le détail de chaque réservation d'un client professionnel, une section « Bon de commande » indiquant l'état (exigé et à saisir, saisi, facultatif) et, si le numéro est saisi, l'auteur, l'agence et la date ; la section DOIT signaler l'étape « départ » prête dès que le numéro est saisi ou qu'il n'est pas exigé.
- **FR-009**: Les salariés DOIVENT pouvoir corriger le numéro tant que la réservation est confirmée ; à la sortie, le numéro est figé et NE DOIT plus être modifiable. Le numéro ne peut pas être effacé d'une réservation d'un grand compte, seulement remplacé.
- **FR-010**: Le bon de commande NE DOIT PAS être exigé pour une réservation déjà sortie à la mise en service de cette fonctionnalité.

**Transmission à la facturation**

- **FR-011**: Chaque élément transmis au logiciel de facturation pour une réservation portant un numéro de bon de commande (périodes intermédiaires et finale, dégâts refacturés) DOIT contenir ce numéro, en plus des informations prévues par la feature 003.
- **FR-012**: L'export de secours (feature 003) DOIT contenir le numéro de bon de commande de chaque élément qui en porte un.
- **FR-013**: Un élément d'une réservation sans numéro DOIT être transmis comme dans la feature 003 ; aucun élément déjà transmis NE DOIT être retransmis pour y ajouter un numéro.

**Suivi et traçabilité**

- **FR-014**: Le système DOIT présenter à toutes les agences la liste des réservations confirmées de grands comptes sans numéro de bon de commande, triées par date de départ, filtrable par agence de rattachement de la machine, avec la réservation, le client, la machine et la date de départ.
- **FR-015**: Le système DOIT mettre en évidence dans cette liste les réservations dont le départ a lieu dans 3 jours ou moins.
- **FR-016**: Le système DOIT enregistrer dans l'historique chaque désignation et chaque retrait de grand compte, et chaque saisie ou correction de numéro de bon de commande (ancien et nouveau numéro), avec l'auteur, l'agence et la date.
- **FR-017**: Ces actions sont ouvertes à tous les salariés dans cette version, chacune contrôlée par une autorisation dédiée (désigner un grand compte, saisir un bon de commande).

### Key Entities

- **Client** (features 001 et 004) : enrichi de l'indication « grand compte », possible seulement pour un client professionnel ; avec l'auteur et la date de la désignation.
- **Bon de commande d'une réservation** : le numéro fourni par le client pour une réservation ; numéro, auteur, agence et date de la saisie, et trace des corrections. Figé à la sortie.
- **Réservation** (feature 001) : porte au plus un numéro de bon de commande ; sa sortie dépend de ce numéro si le client est grand compte.
- **Élément transmis** (feature 003 : période de location, dégât refacturé) : enrichi du numéro de bon de commande de sa réservation.
- **Identifiant de facturation du client** (feature 003) : préalable à la désignation grand compte ; c'est par lui que le logiciel de facturation applique le tarif négocié.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100 % des sorties de réservations de grands comptes enregistrées après la mise en service ont un numéro de bon de commande.
- **SC-002**: 100 % des éléments transmis pour ces réservations (périodes et dégâts, y compris par export de secours) portent ce numéro.
- **SC-003**: Zéro facture de grand compte refusée par le client pour absence de numéro de bon de commande sur les 6 mois suivant la mise en service (relevé fourni par la comptabilité).
- **SC-004**: Un salarié saisit un numéro de bon de commande en moins de 30 secondes depuis le détail de la réservation ou depuis la liste des bons manquants.
- **SC-005**: Moins de 5 % des départs de grands comptes sont retardés au comptoir par un bon de commande manquant, grâce à la relance anticipée.
- **SC-006**: Aucun prix n'est saisi dans l'outil : les tarifs négociés n'existent qu'à un seul endroit, le logiciel de facturation.

## Out of Scope

- Saisie, affichage ou calcul de tarifs dans l'outil (grille tarifaire, tarifs négociés, remises, devis) : ils restent dans le logiciel de facturation (feature 003).
- Document du bon de commande (PDF, photo) : seul le numéro est saisi.
- Gestion du bon de commande comme fiche à part : plafond, montant consommé, dates de validité, contrats cadres.
- Synchronisation de la liste des grands comptes ou de leurs tarifs depuis le logiciel de facturation.
- Bon de commande pour un client particulier.
- Conditions de paiement, encours et relances des grands comptes : dans le logiciel de facturation.
- Accès des grands comptes à l'outil (portail client, réservation en ligne).
- Caution des professionnels : la feature 004 n'en exige aucune, et cette fonctionnalité n'en ajoute pas.
- Droits différenciés par rôle : tous les salariés peuvent désigner un grand compte et saisir un bon de commande, comme dans les features 001 à 004.

## Assumptions

- Le logiciel de facturation sait appliquer à un client, identifié par l'identifiant transmis par la feature 003, son tarif négocié, et sait reporter sur la facture un numéro de bon de commande reçu avec chaque élément. Le nom exact du champ et son format seront confirmés avec le client en même temps que l'adaptateur réel de la feature 003 ; le reste se construit avec le faux logiciel de facturation.
- Le type de client « particulier / professionnel / à renseigner » est celui défini par la feature 004 ; cette fonctionnalité ne crée pas de type supplémentaire : « grand compte » est une qualification d'un client professionnel. Si le modèle de la 004 change, cette spec s'y réaligne.
- La liste des grands comptes est courte (quelques dizaines au plus) et chaque désignation est décidée par la direction, puis saisie par un salarié.
- Le numéro de bon de commande est une chaîne libre : son format varie d'un grand compte à l'autre et l'outil n'en vérifie pas la structure.
- Le seuil de 3 jours avant le départ pour la mise en évidence est une proposition à confirmer avec M. Vallet.
- Le blocage de la sortie s'ajoute à ceux des features 001 (VGP, disponibilité), 002 (photos de départ) et 004 (caution des particuliers), sans les remplacer. Selon la règle de la feature 001, le bouton de sortie s'active dès qu'une section a signalé l'étape prête : il peut être actif sans numéro saisi, et c'est le refus serveur qui garantit la règle.
- Un client grand compte étant professionnel, aucune caution ne lui est demandée (feature 004).
- Cette fonctionnalité s'appuie sur la feature 001 (clients, réservations, sortie, historique), la feature 003 (identifiant client de facturation, transmissions, export de secours) et la feature 004 (type de client).
