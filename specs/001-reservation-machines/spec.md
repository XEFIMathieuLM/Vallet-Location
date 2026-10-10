# Feature Specification: Cœur de réservation des machines

**Feature Branch**: `001-reservation-machines`

**Created**: 2026-10-09

**Status**: Draft

**Input**: User description: fiche `spec.md.txt` (Vallet Location) — périmètre retenu : le cœur de réservation (parc de machines à référence unique, statuts, réservations sans chevauchement, disponibilité partagée entre les 7 agences, blocage VGP). Les autres capacités de la fiche feront l'objet de specs séparées.

## Contexte

M. Vallet dirige une PME de location de machines de travaux : 85 salariés, 7 agences, environ 400 machines. Les réservations sont aujourd'hui mal coordonnées entre agences, ce qui provoque des doubles réservations, des machines promises alors qu'elles sont à l'atelier, et une perte de 85 000 € l'an dernier en réparations non refacturées. Cette première fonctionnalité pose le socle : un parc unique, partagé, et des réservations que l'outil rend impossibles à mettre en conflit.

## Clarifications

### Session 2026-10-10

- Q: Quand une machine louée n'est pas rentrée à sa date de fin, quelles réservations à venir sont signalées « en conflit » ? → A: Seulement la prochaine réservation confirmée de cette machine ; les suivantes ne sont pas signalées.
- Q: Une réservation en conflit que l'on annule garde-t-elle son motif de conflit ? → A: Non, l'annulation efface le motif ; une réservation annulée n'est jamais « en conflit ».
- Q: Quand le détail d'une réservation affiche des sections ajoutées par d'autres fonctionnalités (ex. photos), que faut-il pour activer « Enregistrer la sortie » ou « Enregistrer le retour » ? → A: Le bouton d'une étape s'active dès qu'une section a signalé que cette étape est prête ; sans section ajoutée, les boutons sont actifs. Le refus côté serveur reste la seule garantie.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Réserver une machine disponible sans risque de doublon (Priority: P1)

Un salarié d'agence reçoit une demande client. Il cherche une machine du type voulu pour des dates données, voit lesquelles sont disponibles dans les 7 agences, et en réserve une pour ce client. L'outil refuse toute réservation qui chevaucherait une réservation existante sur la même machine.

**Why this priority**: C'est la raison d'être de l'outil. Sans réservation fiable et partagée, aucune autre fonctionnalité (photos, caution, facturation) n'a de support.

**Independent Test**: Avec un parc de quelques machines déjà saisi, créer une réservation, puis tenter une seconde réservation de la même machine sur des dates qui se chevauchent depuis une autre agence : la seconde est refusée.

**Acceptance Scenarios**:

1. **Given** une machine disponible du 10 au 14 novembre, **When** un salarié la réserve du 10 au 14 novembre pour un client, **Then** la réservation est enregistrée et la machine apparaît réservée sur ces dates pour toutes les agences.
2. **Given** une machine réservée du 10 au 14 novembre, **When** un salarié d'une autre agence tente de la réserver du 13 au 16 novembre, **Then** l'outil refuse et indique la réservation en conflit (dates, agence).
3. **Given** une machine réservée du 10 au 14 novembre, **When** un salarié la réserve du 15 au 18 novembre, **Then** la réservation est acceptée.
4. **Given** deux salariés de deux agences qui valident au même instant une réservation de la même machine sur des dates qui se chevauchent, **When** les deux validations arrivent, **Then** une seule réservation est enregistrée et l'autre salarié reçoit un refus explicite.

---

### User Story 2 - Empêcher la réservation d'une machine indisponible ou non conforme (Priority: P1)

Une machine à l'atelier ou en panne ne peut être ni réservée ni promise. Une machine soumise à la VGP (vérification générale périodique) dont la VGP n'est pas valide sur toute la durée de la location ne peut pas être réservée, et ne peut pas sortir.

**Why this priority**: Ces règles protègent l'entreprise (sécurité, responsabilité légale, image client). Elles doivent exister dès la première réservation.

**Independent Test**: Passer une machine en statut « atelier », tenter de la réserver : refus. Saisir une VGP qui expire au milieu d'une période demandée : refus de la réservation.

**Acceptance Scenarios**:

1. **Given** une machine en statut « atelier » ou « en panne », **When** un salarié tente de la réserver, **Then** l'outil refuse et affiche le statut de la machine.
2. **Given** une nacelle dont la VGP expire le 12 novembre, **When** un salarié tente de la réserver du 10 au 14 novembre, **Then** l'outil refuse en indiquant la date d'expiration de la VGP.
3. **Given** une nacelle dont la VGP expire le 30 novembre, **When** un salarié la réserve du 10 au 14 novembre, **Then** la réservation est acceptée.
4. **Given** une machine soumise à la VGP sans date de VGP renseignée, **When** un salarié tente de la réserver, **Then** l'outil refuse (VGP considérée comme non à jour).
5. **Given** une réservation confirmée dont la VGP de la machine ne couvre plus la période jusqu'à la date de fin (date modifiée entre-temps), **When** un salarié enregistre la sortie, **Then** la sortie est bloquée.

---

### User Story 3 - Suivre la vie d'une réservation : sortie, retour, annulation (Priority: P2)

Le jour du départ, un salarié enregistre la sortie de la machine. Au retour, il enregistre la rentrée et indique si la machine revient en état (disponible) ou doit passer à l'atelier. Une réservation peut être annulée avant la sortie, ce qui libère les dates.

**Why this priority**: La disponibilité en temps réel dépend de ces événements. Sans eux, une machine rendue en retard ou abîmée resterait « disponible » à tort.

**Independent Test**: Créer une réservation, enregistrer la sortie, puis le retour avec passage à l'atelier : la machine n'est plus réservable tant qu'elle n'est pas remise disponible.

**Acceptance Scenarios**:

1. **Given** une réservation confirmée, **When** un salarié enregistre la sortie, **Then** la réservation passe « en cours » et la machine « sortie ».
2. **Given** une réservation en cours, **When** un salarié enregistre le retour en indiquant « en état », **Then** la réservation est clôturée et la machine redevient disponible.
3. **Given** une réservation en cours, **When** un salarié enregistre le retour en indiquant « à l'atelier », **Then** la réservation est clôturée et la machine passe en statut « atelier ».
4. **Given** une réservation confirmée non sortie, **When** un salarié l'annule, **Then** les dates sont libérées pour d'autres réservations.

---

### User Story 4 - Gérer le parc de machines à référence unique (Priority: P2)

Un salarié ajoute, modifie ou retire une machine du parc. Chaque machine porte une référence unique ; l'outil refuse toute création ou modification qui produirait un doublon. Le parc existant (environ 400 machines) peut être chargé en une fois depuis un fichier.

**Why this priority**: Le parc doit exister avant la première réservation, mais une saisie minimale suffit pour tester la P1 ; la gestion complète et l'import viennent ensuite.

**Independent Test**: Importer un fichier de machines contenant deux lignes avec la même référence : l'import signale le doublon et n'enregistre pas la ligne en double.

**Acceptance Scenarios**:

1. **Given** une machine de référence « NAC-0042 » existe, **When** un salarié crée une machine avec la référence « NAC-0042 », **Then** l'outil refuse et signale le doublon.
2. **Given** un fichier de 400 machines dont 3 références en double, **When** un salarié l'importe, **Then** 397 machines sont créées et les 3 lignes rejetées sont listées avec leur motif.
3. **Given** une machine, **When** un salarié change son statut en « en panne », **Then** le changement est visible par toutes les agences.

---

### User Story 5 - Voir la disponibilité de tout le parc depuis n'importe quelle agence (Priority: P3)

Depuis n'importe quelle agence, un salarié consulte le planning d'une machine ou d'une catégorie de machines, filtré par agence de rattachement, type et période, et voit en temps réel les réservations, sorties et indisponibilités.

**Why this priority**: La recherche de disponibilité de la P1 couvre le besoin minimal ; une vue planning complète améliore l'organisation mais n'est pas bloquante.

**Independent Test**: Créer une réservation depuis l'agence A ; un salarié de l'agence B voit la machine occupée sur ces dates sans recharger manuellement.

**Acceptance Scenarios**:

1. **Given** un salarié de l'agence B consulte le planning, **When** un salarié de l'agence A crée une réservation, **Then** le planning de l'agence B se met à jour sans action de l'utilisateur.
2. **Given** le planning d'une catégorie « nacelles », **When** un salarié filtre sur une période, **Then** il voit pour chaque machine ses réservations et ses périodes d'indisponibilité (atelier, panne, VGP non valide).

---

### Edge Cases

- **Machine passée à l'atelier ou en panne alors qu'elle a des réservations à venir** : les réservations ne sont pas supprimées, mais signalées « en conflit » et listées pour que l'agence concernée reloge le client.
- **Date de VGP modifiée après une réservation** : si la nouvelle date ne couvre plus la période, la réservation est signalée en conflit, et la sortie est bloquée tant que la VGP n'est pas à jour.
- **Retour en retard** : une machine toujours « sortie » après la date de fin prévue reste indisponible ; seule la prochaine réservation confirmée de cette machine est signalée en conflit, jusqu'au retour de la machine.
- **Annulation d'une réservation en conflit** : l'annulation efface le motif de conflit ; la réservation sort de la liste « en conflit ».
- **Retour anticipé** : la clôture avant la date de fin libère immédiatement les jours restants.
- **Sortie anticipée ou tardive** : une sortie ne peut être enregistrée qu'à partir de la date de début de la réservation.
- **Machine retirée du parc avec des réservations à venir** : le retrait est refusé tant que ces réservations ne sont pas annulées ou déplacées.
- **Réservation sur une seule journée** : début et fin le même jour, acceptée.
- **Dates incohérentes** (fin avant début, début dans le passé) : refusées.

## Requirements *(mandatory)*

### Functional Requirements

**Parc de machines**

- **FR-001**: Le système DOIT tenir un parc unique de machines partagé par les 7 agences.
- **FR-002**: Chaque machine DOIT porter une référence unique ; le système DOIT refuser la création, la modification ou l'import d'une machine dont la référence existe déjà.
- **FR-003**: Chaque machine DOIT avoir une catégorie (ex. nacelle, mini-pelle, chariot), une agence de rattachement, un statut (disponible, atelier, en panne, retirée du parc) et l'indication « soumise à VGP » ; les nacelles sont toujours soumises à VGP.
- **FR-004**: Pour une machine soumise à VGP, le système DOIT conserver la date d'échéance de la VGP ; une machine soumise sans date renseignée est considérée comme non à jour.
- **FR-005**: Les utilisateurs DOIVENT pouvoir importer le parc depuis un fichier ; le système DOIT rejeter les lignes en doublon ou incomplètes et restituer la liste des lignes rejetées avec leur motif.
- **FR-006**: Les utilisateurs DOIVENT pouvoir changer le statut d'une machine (disponible, atelier, en panne, retirée du parc).

**Réservations**

- **FR-007**: Les utilisateurs DOIVENT pouvoir créer une réservation portant sur une machine, un client (nom et coordonnées), une date de début et une date de fin, depuis n'importe quelle agence et pour n'importe quelle machine du parc.
- **FR-008**: Le système DOIT refuser toute réservation dont les dates chevauchent, même d'un seul jour, une réservation non annulée de la même machine.
- **FR-009**: Le système DOIT garantir qu'en cas de validations simultanées sur la même machine et des dates qui se chevauchent, une seule réservation est enregistrée.
- **FR-010**: Le système DOIT refuser la réservation d'une machine en statut atelier, en panne ou retirée du parc.
- **FR-011**: Le système DOIT refuser la réservation d'une machine soumise à VGP si sa VGP n'est pas valide jusqu'à la date de fin incluse.
- **FR-012**: Chaque refus DOIT indiquer la raison précise (réservation en conflit avec ses dates et son agence, statut de la machine, ou date d'échéance de la VGP).
- **FR-013**: Une réservation DOIT suivre le cycle : confirmée → en cours (sortie enregistrée) → clôturée (retour enregistré), ou confirmée → annulée.
- **FR-014**: Le système DOIT bloquer l'enregistrement d'une sortie si la VGP de la machine n'est pas valide jusqu'à la date de fin, ou si la machine n'est pas disponible.
- **FR-015**: Au retour, l'utilisateur DOIT indiquer si la machine revient disponible ou passe à l'atelier ; le statut de la machine est mis à jour en conséquence.
- **FR-016**: L'annulation d'une réservation confirmée DOIT libérer ses dates ; une réservation en cours ou clôturée ne peut pas être annulée.

**Disponibilité et conflits**

- **FR-017**: Le système DOIT permettre de rechercher les machines disponibles par catégorie, agence de rattachement et période, sur l'ensemble du parc.
- **FR-018**: Toute modification de réservation ou de statut DOIT être visible par toutes les agences sans action manuelle de rafraîchissement.
- **FR-019**: Le système DOIT signaler comme « en conflit » toute réservation à venir devenue impossible (machine passée à l'atelier ou en panne, VGP ne couvrant plus la période, machine précédente pas encore rentrée) et en présenter la liste.
- **FR-020**: Le système DOIT refuser le retrait du parc d'une machine ayant des réservations confirmées ou en cours.

**Accès et traçabilité**

- **FR-021**: Chaque salarié DOIT se connecter avec un compte personnel ; tous les salariés ont les mêmes droits dans cette version.
- **FR-022**: Le système DOIT enregistrer, pour chaque réservation et chaque changement de statut, l'auteur, l'agence et la date.

### Key Entities

- **Agence** : l'une des 7 agences de Vallet Location ; nom, adresse.
- **Machine** : un engin du parc ; référence unique, catégorie, agence de rattachement, statut, soumise à VGP, date d'échéance VGP.
- **Catégorie de machine** : famille d'engins (nacelle, mini-pelle, chariot…) ; sert à la recherche et indique si la VGP est obligatoire.
- **Client** : la personne ou l'entreprise qui loue ; nom, coordonnées (téléphone, e-mail). Enrichi par les specs suivantes (particulier / grand compte, tarifs).
- **Réservation** : une machine louée à un client sur une période ; dates de début et de fin, statut (confirmée, en cours, clôturée, annulée), indicateur de conflit, agence et auteur de la création.
- **Salarié** : utilisateur de l'outil ; nom, identifiant, agence.
- **Historique** : trace horodatée de chaque action (création, sortie, retour, annulation, changement de statut).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Zéro double réservation d'une même machine sur des dates qui se chevauchent, sur les 3 premiers mois d'utilisation.
- **SC-002**: Zéro sortie d'une machine soumise à VGP dont la VGP n'est pas valide sur la période louée.
- **SC-003**: Zéro réservation acceptée sur une machine à l'atelier ou en panne.
- **SC-004**: Un salarié trouve une machine disponible d'une catégorie donnée, toutes agences confondues, en moins de 30 secondes.
- **SC-005**: Une réservation est créée en moins de 2 minutes, recherche comprise.
- **SC-006**: Une réservation ou un changement de statut est visible dans les autres agences en moins de 5 secondes.
- **SC-007**: Le parc complet (environ 400 machines) est chargé en une seule opération, sans aucun doublon de référence.

## Out of Scope

Ces éléments de la fiche font l'objet de specs séparées :

- Photos de départ et de retour via QR code et téléphone, et le blocage de la sortie et de la clôture tant qu'elles manquent.
- Caution des particuliers encaissée avant le départ.
- Grands comptes : tarifs négociés et bon de commande.
- Tarification et devis.
- Transmission des locations au logiciel de facturation / outil comptable du client.
- Envoi automatique de l'attestation VGP par e-mail au client.
- Vente de machines d'occasion.
- Transferts de machines entre agences.
- Droits différenciés par rôle.

## Assumptions

- Les réservations se font à la journée : date de début et date de fin incluses. Deux réservations qui partagent un même jour se chevauchent.
- La VGP doit être valide jusqu'à la date de fin incluse de la location.
- La règle VGP s'applique à toute machine marquée « soumise à VGP » ; les nacelles le sont toujours, les autres catégories selon le paramétrage.
- Une réservation est retirée et rendue à l'agence de rattachement de la machine ; une réservation peut être créée depuis n'importe quelle agence.
- « Promettre » une machine équivaut à la réserver : il n'existe pas de pré-réservation ou d'option distincte dans cette version.
- Des fonctionnalités ultérieures peuvent ajouter des sections au détail d'une réservation et bloquer la sortie ou le retour ; le bouton d'une étape s'active dès qu'une section signale cette étape prête, et le blocage réel reste vérifié côté serveur.
- Les 85 salariés ont les mêmes droits (choix du client pour cette version).
- Le parc initial est fourni par le client sous forme de fichier tableur.
- Les salariés disposent d'un poste connecté à internet dans chaque agence.
