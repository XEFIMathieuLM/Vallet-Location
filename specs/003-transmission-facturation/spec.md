# Feature Specification: Transmission des locations et des réparations au logiciel de facturation

**Feature Branch**: `003-transmission-facturation`

**Created**: 2026-10-09

**Status**: Draft

**Input**: User description: "Transmission de chaque location au logiciel de facturation du client, pour que les locations et les réparations constatées au retour soient refacturées". Fiche `spec.md.txt` (Vallet Location) — règle « Toute location est transmise au logiciel de facturation actuel » et connexion avec l'outil comptable du client. S'appuie sur `001-reservation-machines` (réservations, sortie, retour) et `002-photos-qr-code` (dégâts signalés au retour, réservation « à refacturer »).

## Contexte

Vallet Location a perdu 85 000 € l'an dernier en réparations non refacturées. La feature 002 apporte la preuve du dégât (photos de départ et de retour) et le signalement ; il manque la dernière étape : que chaque location et chaque dégât constaté arrivent effectivement dans le logiciel de facturation que l'entreprise utilise déjà, sans ressaisie, et qu'aucun ne se perde en route.

Cette fonctionnalité ne remplace pas le logiciel de facturation : elle lui transmet ce qu'il doit facturer, garde la trace de chaque transmission, et rend visible tout ce qui n'est pas encore parti ou a échoué.

## Clarifications

### Session 2026-10-09

- Q: Le logiciel de facturation accepte-t-il une transmission automatique, ou faut-il produire un fichier importé par la comptabilité ? → A: Transmission automatique, avec un export fichier en secours (FR-021 à FR-023).
- Q: D'où vient le montant de la location, alors que la tarification fait l'objet d'une spec séparée ? → A: L'outil transmet machine, client et nombre de jours ; le logiciel de facturation applique ses tarifs (FR-002).
- Q: Les locations longues sont-elles transmises au fil de l'eau ? → A: Au retour, et à chaque fin de mois pour les locations encore en cours (FR-003a, FR-003b).
- Q: Que fait-on d'une location déjà sortie au moment de la mise en service ? → A: Elle est transmise en entier, depuis sa date de sortie, à son retour ou à la prochaine fin de mois ; seules les locations clôturées avant la mise en service ne sont pas transmises (FR-001).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Transmettre automatiquement chaque location (Priority: P1)

Quand le retour d'une machine est enregistré, la location est transmise au logiciel de facturation sans action du salarié : client, machine, agence, dates réelles de la période et nombre de jours facturables. Le logiciel de facturation applique ses propres tarifs. Une location encore en cours à la fin d'un mois est transmise pour la partie du mois écoulée, puis le reste est transmis au retour. Le salarié voit sur la réservation ce qui a été transmis, et quand.

**Why this priority**: C'est la règle de la fiche : toute location est transmise. Sans elle, une location oubliée n'est jamais facturée, et les dégâts n'ont pas de support de facturation.

**Independent Test**: Enregistrer le retour d'une réservation en cours : la location apparaît dans le logiciel de facturation avec les bonnes dates et le bon nombre de jours, et la réservation affiche « transmise » avec la date. Simuler une fin de mois avec une réservation en cours : la période écoulée est transmise.

**Acceptance Scenarios**:

1. **Given** une réservation sortie le 10 novembre, **When** le salarié enregistre le retour le 14 novembre, **Then** la location est transmise avec le client, la référence de la machine, l'agence, les dates réelles du 10 au 14 novembre et 5 jours facturables, et la réservation affiche « transmise le … ».
2. **Given** une réservation prévue du 10 au 14 novembre, **When** le retour est enregistré le 12 novembre (retour anticipé), **Then** la location est transmise sur les dates réelles, soit 3 jours.
3. **Given** une réservation prévue du 10 au 14 novembre, **When** le retour est enregistré le 17 novembre (retour en retard), **Then** la location est transmise sur les dates réelles, soit 8 jours.
4. **Given** une réservation annulée avant la sortie, **When** l'annulation est enregistrée, **Then** rien n'est transmis.
5. **Given** une réservation sortie le 20 novembre et toujours en cours, **When** le mois de novembre se termine, **Then** la période du 20 au 30 novembre (11 jours) est transmise sans action d'un salarié, et la réservation reste en cours.
6. **Given** la même réservation, **When** le retour est enregistré le 5 décembre, **Then** seule la période du 1er au 5 décembre (5 jours) est transmise ; aucun jour n'est transmis deux fois.
7. **Given** une réservation sortie le 20 novembre, **When** le retour est enregistré le 30 novembre, **Then** une seule période du 20 au 30 novembre est transmise, au retour.
8. **Given** une location déjà transmise, **When** une nouvelle tentative de transmission a lieu pour la même location (relance, double clic, reprise après panne), **Then** le logiciel de facturation ne reçoit pas de doublon.

---

### User Story 2 - Ne perdre aucune transmission en cas d'échec (Priority: P1)

Si le logiciel de facturation est injoignable ou refuse une location (client inconnu, donnée manquante), la transmission n'est pas perdue : elle est relancée automatiquement, et toute transmission qui reste en échec apparaît dans une liste visible de toutes les agences, avec le motif, jusqu'à ce qu'elle soit corrigée et transmise.

**Why this priority**: Une transmission qui échoue en silence reproduit exactement la perte d'aujourd'hui. La garantie « rien ne se perd » vaut autant que la transmission elle-même.

**Independent Test**: Rendre le logiciel de facturation indisponible, clôturer une réservation : elle apparaît « en attente » ; rétablir le logiciel : elle part sans action et sort de la liste. Avec le logiciel toujours indisponible, produire l'export de secours : la location y figure, puis n'est plus relancée automatiquement.

**Acceptance Scenarios**:

1. **Given** le logiciel de facturation injoignable, **When** une réservation est clôturée, **Then** le retour est enregistré normalement pour le salarié, et la location est marquée « en attente de transmission ».
2. **Given** une location en attente suite à une indisponibilité, **When** le logiciel redevient joignable, **Then** la location est transmise automatiquement, sans action d'un salarié.
3. **Given** une location refusée par le logiciel de facturation (ex. client inconnu), **When** un salarié consulte la liste des transmissions en échec, **Then** il voit la réservation, le client, la date de l'échec et le motif en clair.
4. **Given** une location en échec dont le motif a été corrigé (ex. client créé dans le logiciel de facturation), **When** un salarié relance la transmission, **Then** elle est transmise et sort de la liste.
5. **Given** une location en attente depuis plus de 24 heures, **When** un salarié ouvre l'outil, **Then** l'alerte des transmissions en échec est visible sans avoir à la chercher.
6. **Given** des éléments en attente ou en échec et l'envoi automatique indisponible, **When** un salarié produit l'export de secours, **Then** il obtient un fichier importable par la comptabilité contenant tous ces éléments, et chacun passe « transmis par export » : il n'est plus relancé automatiquement et ne figurera dans aucun autre export.
7. **Given** un élément déjà transmis automatiquement, **When** un export de secours est produit, **Then** cet élément n'y figure pas.

---

### User Story 3 - Chiffrer un dégât et le transmettre pour refacturation (Priority: P1)

Pour chaque dégât signalé au retour (feature 002), un salarié saisit le montant à refacturer et le libellé de la réparation, puis valide. Le dégât est transmis au logiciel de facturation comme une facturation complémentaire de la même location et du même client, même si la location elle-même a déjà été transmise. Un dégât que l'entreprise décide de ne pas refacturer est classé « non refacturé » avec un motif obligatoire.

**Why this priority**: C'est le cœur du problème des 85 000 € : un dégât prouvé mais jamais facturé ne rapporte rien. Chaque dégât doit finir soit refacturé, soit abandonné de façon tracée.

**Independent Test**: Sur une réservation clôturée et transmise, portant un dégât « bras rayé », saisir 450 € et valider : le logiciel de facturation reçoit une ligne de 450 € liée à la même location et au même client, et le dégât passe « refacturé ».

**Acceptance Scenarios**:

1. **Given** une réservation avec un dégât « à traiter », **When** un salarié saisit un montant de 450 € et le libellé « remplacement capot » puis valide, **Then** le dégât est transmis avec la référence de la location d'origine, le client, la machine, la vue concernée et le commentaire du constat, et passe « refacturé ».
2. **Given** une location déjà transmise la veille, **When** un dégât de cette location est chiffré et validé, **Then** il est transmis comme facturation complémentaire, sans retransmettre la location.
3. **Given** un dégât « à traiter », **When** un salarié le classe « non refacturé » avec le motif « usure normale », **Then** le dégât sort de la liste des dégâts à traiter, rien n'est transmis, et le motif, l'auteur et la date sont conservés.
4. **Given** un dégât « à traiter », **When** un salarié tente de le classer « non refacturé » sans motif, **Then** l'outil refuse.
5. **Given** un dégât « refacturé », **When** un salarié tente d'en modifier le montant, **Then** l'outil refuse et indique que toute correction se fait dans le logiciel de facturation (avoir).

---

### User Story 4 - Suivre ce qui a été transmis et ce qui reste à refacturer (Priority: P2)

Un responsable consulte un relevé : locations transmises sur une période, dégâts refacturés avec leurs montants, dégâts classés non refacturés avec leurs motifs, et dégâts encore à traiter avec leur ancienneté. Il peut filtrer par agence et par période.

**Why this priority**: Il permet de mesurer l'objectif (réduire les 85 000 € perdus) et de relancer les agences en retard ; la transmission fonctionne sans lui.

**Independent Test**: Sur un jeu de 10 réservations clôturées dont 3 avec dégâts (1 refacturé, 1 non refacturé, 1 à traiter), le relevé du mois affiche 10 locations transmises, le montant refacturé, le motif du non-refacturé et le dégât à traiter avec son ancienneté.

**Acceptance Scenarios**:

1. **Given** des locations et des dégâts sur novembre, **When** le responsable affiche le relevé de novembre pour l'agence de Rouen, **Then** il voit le nombre de locations transmises, le total refacturé en dégâts, les dégâts non refacturés avec leurs motifs, et les dégâts encore à traiter.
2. **Given** un dégât à traiter depuis plus de 7 jours, **When** le responsable consulte le relevé, **Then** ce dégât est mis en évidence comme en retard.

---

### Edge Cases

- **Client inconnu du logiciel de facturation** : la transmission échoue avec le motif « client inconnu » ; la location reste dans la liste des échecs jusqu'à correction et relance (US2).
- **Location à cheval sur plusieurs mois** : chaque fin de mois transmet la période du mois écoulé ; le retour transmet la période restante. La somme des jours transmis égale la durée réelle de la location.
- **Retour enregistré le dernier jour du mois** : une seule transmission au retour, pas de transmission de fin de mois en plus.
- **Transmission de fin de mois en échec** : traitée comme toute transmission en échec (US2) ; la transmission du retour ne reprend pas les jours de la période en échec.
- **Export de secours produit alors qu'une transmission automatique est en cours** : un élément ne peut figurer à la fois dans un envoi automatique abouti et dans un export.
- **Erreur de date découverte après transmission** : la feature 001 ne permet pas de corriger une date de sortie ou de retour ; une période transmise n'est jamais retransmise ni modifiée, et la correction se fait par un avoir dans le logiciel de facturation.
- **Réservation clôturée avant la mise en service** : non transmise.
- **Réservation sortie avant la mise en service et encore en cours** : transmise en entier depuis sa date de sortie ; la première clôture mensuelle après la mise en service transmet tous les mois déjà écoulés, et le retour transmet le reste.
- **Réservation clôturée avant la mise en service mais portant un dégât à traiter** : le dégât peut être chiffré et transmis comme facturation complémentaire.
- **Plusieurs dégâts sur une même location** : chacun est chiffré et transmis séparément ; l'ordre d'arrivée dans le logiciel de facturation n'est pas garanti.
- **Montant de dégât nul ou négatif** : refusé ; un dégât sans refacturation passe par « non refacturé » avec motif.
- **Le logiciel de facturation accepte la transmission mais la réponse se perd** : la relance ne crée pas de doublon (US1, scénario 5).
- **Indisponibilité prolongée du logiciel de facturation** (plusieurs jours) : les transmissions s'accumulent en attente et partent toutes dès le retour du logiciel, sans ordre garanti.

## Requirements *(mandatory)*

### Functional Requirements

**Transmission des locations**

- **FR-001**: Le système DOIT transmettre au logiciel de facturation, sans action d'un salarié, chaque réservation qui n'était pas clôturée à la mise en service de cette fonctionnalité, qu'elle soit sortie avant ou après : à l'enregistrement du retour, et à chaque fin de mois tant qu'elle est en cours, en entier depuis sa date de sortie.
- **FR-002**: Chaque période transmise DOIT comporter : le client, la référence et la catégorie de la machine, l'agence de rattachement, l'agence qui a créé la réservation, la date de début et la date de fin de la période, le nombre de jours facturables, et l'indication « période intermédiaire » ou « période finale ». Aucun montant n'est transmis : le logiciel de facturation applique ses tarifs.
- **FR-003**: Le nombre de jours facturables DOIT être calculé sur les dates réelles, bornes incluses, cohérent avec la règle de la feature 001 (location à la journée).
- **FR-003a**: À la fin de chaque mois calendaire, le système DOIT transmettre, pour chaque réservation encore en cours, la période allant du lendemain de la dernière période transmise (ou de la date de sortie) jusqu'au dernier jour du mois.
- **FR-003b**: Au retour, le système DOIT transmettre la période allant du lendemain de la dernière période transmise (ou de la date de sortie) jusqu'à la date de retour ; la somme des jours de toutes les périodes d'une location DOIT égaler sa durée réelle.
- **FR-004**: Le système NE DOIT PAS transmettre une réservation annulée.
- **FR-005**: Le système DOIT garantir qu'une même location ou un même dégât n'est jamais facturé deux fois, quel que soit le nombre de tentatives de transmission.
- **FR-006**: L'enregistrement du retour par le salarié NE DOIT PAS être bloqué ni ralenti par l'état du logiciel de facturation.

**Échecs et relances**

- **FR-007**: Le système DOIT donner à chaque élément à transmettre (période de location ou dégât) un état : en attente, transmis, transmis par export, en échec ; et conserver la date de chaque tentative et le motif de chaque échec.
- **FR-008**: Le système DOIT relancer automatiquement une transmission qui a échoué parce que le logiciel de facturation était injoignable, jusqu'à réussite.
- **FR-009**: Le système DOIT présenter à toutes les agences la liste des transmissions à traiter : celles en échec, dès l'échec, et celles en attente depuis plus de 24 heures ; avec la réservation, le client, la date et le motif en clair.
- **FR-010**: Les salariés DOIVENT pouvoir relancer manuellement une transmission en échec après correction de sa cause.
- **FR-011**: Le système DOIT rendre visible dès l'ouverture de l'outil l'existence de transmissions à traiter (au sens de FR-009).

**Refacturation des dégâts**

- **FR-012**: Pour chaque dégât « à traiter » (feature 002), les salariés DOIVENT pouvoir saisir un montant hors taxes strictement positif et un libellé de réparation, puis valider la refacturation.
- **FR-013**: Un dégât validé DOIT être transmis comme facturation complémentaire rattachée à la location d'origine et au même client, indépendamment de la transmission de la location elle-même.
- **FR-014**: Chaque dégât transmis DOIT comporter : la référence de la location d'origine, le client, la machine, la vue concernée, le commentaire du constat, le libellé et le montant.
- **FR-015**: Les salariés DOIVENT pouvoir classer un dégât « non refacturé » avec un motif obligatoire ; rien n'est alors transmis.
- **FR-016**: Un dégât refacturé ou non refacturé DOIT sortir de la liste des dégâts à traiter (remplace le « traité » de la feature 002) ; son montant, son libellé ou son motif NE DOIVENT plus être modifiables dans l'outil.

**Suivi et traçabilité**

- **FR-017**: Le système DOIT afficher sur chaque réservation l'état de transmission de la location et de chacun de ses dégâts, avec les dates.
- **FR-018**: Le système DOIT fournir un relevé filtrable par agence et par période : locations transmises, dégâts refacturés et leurs montants, dégâts non refacturés et leurs motifs, dégâts encore à traiter et leur ancienneté.
- **FR-019**: Le système DOIT mettre en évidence les dégâts à traiter depuis plus de 7 jours.
- **FR-020**: Le système DOIT enregistrer dans l'historique de la réservation chaque tentative de transmission, chaque échec, chaque relance manuelle, chaque chiffrage et chaque classement « non refacturé », avec l'auteur et la date.

**Mode d'échange**

- **FR-021**: Le système DOIT transmettre automatiquement chaque élément au logiciel de facturation actuel du client, sans manipulation de fichier.
- **FR-022**: En secours, les salariés DOIVENT pouvoir produire à tout moment un export, importable par le logiciel de facturation, de tous les éléments en attente ou en échec.
- **FR-023**: Un élément inclus dans un export de secours DOIT passer « transmis par export » : il n'est plus relancé automatiquement et ne figure dans aucun export suivant ; un élément transmis automatiquement ne figure dans aucun export.

### Key Entities

- **Période facturable** : une tranche d'une location à transmettre ; réservation, date de début, date de fin, nombre de jours, intermédiaire (fin de mois) ou finale (retour).
- **Transmission** : l'envoi d'un élément facturable (période de location ou dégât) au logiciel de facturation ; type, élément concerné, état (en attente, transmis, transmis par export, en échec), date de chaque tentative, motif du dernier échec, identifiant reçu du logiciel de facturation une fois transmis, export de secours d'origine le cas échéant.
- **Export de secours** : un fichier produit par un salarié ; date, auteur, éléments inclus.
- **Réservation** (feature 001) : enrichie de ses périodes facturables et de leur état de transmission.
- **Dégât** (feature 002) : enrichi d'un montant, d'un libellé de réparation, et d'un état final « refacturé » ou « non refacturé » avec motif, auteur et date, en remplacement du simple « traité ».
- **Client** (feature 001) : enrichi de son identifiant dans le logiciel de facturation, nécessaire pour que la transmission aboutisse.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100 % des réservations clôturées après la mise en service sont transmises au logiciel de facturation (automatiquement ou par export) ou figurent dans la liste des échecs ; aucune n'est absente des deux.
- **SC-001a**: 100 % des locations en cours à une fin de mois ont leur période du mois transmise dans les 24 heures qui suivent.
- **SC-002**: 95 % des locations sont transmises dans les 5 minutes qui suivent l'enregistrement du retour, lorsque le logiciel de facturation est joignable.
- **SC-003**: Zéro jour de location ou dégât facturé en double sur les 3 premiers mois, y compris entre envoi automatique et export de secours.
- **SC-004**: Zéro ressaisie manuelle d'une location dans le logiciel de facturation par la comptabilité.
- **SC-005**: 100 % des dégâts signalés sont, 15 jours après leur signalement, refacturés ou classés non refacturés avec un motif.
- **SC-006**: Sur les 12 mois suivant la mise en service, le montant des réparations constatées mais non refacturées sans motif est nul, contre 85 000 € perdus l'année précédente.

## Out of Scope

- Émission des factures, avoirs, relances de paiement et encaissements : ils restent dans le logiciel de facturation.
- Calcul des montants de location : le logiciel de facturation applique ses tarifs. Grille tarifaire, devis et tarifs négociés des grands comptes font l'objet de specs séparées.
- Caution des particuliers (spec séparée).
- Synchronisation des clients depuis ou vers le logiciel de facturation : l'outil reçoit l'identifiant du client, il ne crée pas le client dans le logiciel de facturation.
- Transmission des locations clôturées avant la mise en service.
- Correction d'une date de sortie ou de retour après transmission (la feature 001 ne le permet pas).
- Facturation de la vente d'occasion (spec séparée).
- Droits différenciés par rôle : tous les salariés peuvent chiffrer, classer et relancer, comme dans les features 001 et 002.

## Assumptions

- Le logiciel de facturation reste la référence pour la facture : tarifs de location, numérotation, TVA, mentions légales, avoirs. L'outil transmet des quantités (jours) pour les locations et des montants hors taxes pour les dégâts.
- Le logiciel de facturation actuel accepte une transmission automatique et l'import d'un fichier. Son nom, ses moyens d'échange et le format d'import restent à identifier avec le client avant le plan.
- Le logiciel de facturation sait rattacher une période ou un dégât à un client et à une référence de location transmise par l'outil.
- La fin de mois s'entend au dernier jour calendaire, à l'heure de Paris ; la transmission de fin de mois part après la fin de ce jour.
- À la mise en service, l'ancien circuit de facturation ne facture plus les locations encore en cours : elles sont transmises en entier par l'outil. À confirmer avec la comptabilité de M. Vallet pour éviter une double facturation au basculement.
- Une correction après transmission (erreur de date, montant de dégât erroné) se fait par un avoir dans le logiciel de facturation, pas dans l'outil.
- Chaque client a, ou aura, un identifiant dans le logiciel de facturation ; son absence bloque la transmission avec un motif clair plutôt que de créer un client.
- Les dates réelles de sortie et de retour sont celles enregistrées dans la feature 001.
- Le seuil de 24 heures pour l'alerte et de 7 jours pour un dégât en retard sont des propositions à confirmer avec M. Vallet.
- Cette fonctionnalité s'appuie sur la feature 001 (réservations, sortie, retour, historique) et la feature 002 (dégâts, liste des dégâts à traiter).
