# Feature Specification: Envoi automatique de l'attestation VGP au client

**Feature Branch**: `005-attestation-vgp`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: fiche `spec.md.txt` (Vallet Location) — « Le logiciel intégrera […] l'envoi automatique des attestations VGP par e-mail dès la réservation. » Règle : « L'attestation VGP est envoyée automatiquement au client. » S'appuie sur `001-reservation-machines` (machines soumises à VGP, date d'échéance, clients avec e-mail facultatif, réservations, sortie, historique). Retiré du périmètre des features 001, 002 et 003 (« Envoi automatique de l'attestation VGP par e-mail au client »).

## Contexte

Une machine de levage ou une nacelle louée doit avoir passé sa vérification générale périodique (VGP), et le loueur doit pouvoir en apporter la preuve à son client : c'est le client, utilisateur de la machine sur son chantier, qui doit la présenter en cas de contrôle. Aujourd'hui, le rapport de VGP est envoyé à la main, quand on y pense, ou réclamé par le client au moment du départ.

La feature 001 garantit déjà qu'une machine soumise à VGP ne peut être réservée que si sa VGP est valide jusqu'à la fin de la location. Cette fonctionnalité ajoute la preuve côté client : le rapport officiel de l'organisme de contrôle est déposé dans l'outil pour chaque machine, et dès qu'une réservation d'une machine soumise à VGP est enregistrée, le client le reçoit par e-mail, sans action du salarié. Une machine ne sort pas tant que son client n'a pas reçu l'attestation.

## Clarifications

### Session 2026-10-10

- Q: D'où vient l'attestation envoyée au client ? → A: Le rapport officiel de l'organisme de contrôle, déposé dans l'outil pour chaque machine et envoyé tel quel ; l'outil ne génère aucune attestation.
- Q: À quel moment l'attestation est-elle envoyée ? → A: À la création de la réservation, conformément à la fiche (« dès la réservation ») ; pas de second envoi automatique au départ.
- Q: Que se passe-t-il pour un client sans adresse e-mail ? → A: La réservation est acceptée, mais la sortie de la machine est bloquée tant que l'attestation n'a pas été envoyée.
- Q: Un salarié peut-il débloquer la sortie quand l'attestation n'a pas pu partir par e-mail (ex. particulier sans e-mail) ? → A: Oui, en enregistrant la remise en main propre du rapport en vigueur au client ; la remise est tracée dans l'historique (auteur, date, rapport remis) et débloque la sortie. Aucun forçage sans remise du rapport.
- Q: Les réservations déjà confirmées à la mise en service sont-elles soumises au blocage de sortie ? → A: Oui : à la mise en service, l'outil envoie automatiquement, par le même mécanisme de rattrapage, l'attestation de toutes les réservations confirmées de machines soumises à VGP ; le blocage s'applique ensuite à toutes les réservations.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Envoyer automatiquement l'attestation au client dès la réservation (Priority: P1)

Un salarié réserve une nacelle dont le rapport de VGP est déposé, pour un client qui a une adresse e-mail. Sans autre action, le client reçoit un e-mail avec, en pièce jointe, le rapport de VGP de la machine réservée, et dans le corps du message la référence et la catégorie de la machine, les dates de la réservation, l'agence de retrait et la date d'échéance de la VGP. Le salarié voit sur la réservation « Attestation VGP envoyée le … à … (adresse) ».

**Why this priority**: C'est la règle de la fiche. Sans envoi automatique, la fonctionnalité n'existe pas.

**Independent Test**: Créer une réservation d'une nacelle avec rapport déposé pour un client avec e-mail : un e-mail avec le rapport en pièce jointe part vers cette adresse, et la réservation affiche « envoyée » avec la date et l'adresse. Créer une réservation d'une mini-pelle non soumise à VGP : aucun e-mail.

**Acceptance Scenarios**:

1. **Given** une nacelle soumise à VGP, échéance au 30 novembre, rapport de VGP déposé, et un client avec l'e-mail `chantier@exemple.fr`, **When** un salarié la réserve du 10 au 14 novembre, **Then** un e-mail est envoyé à `chantier@exemple.fr` avec le rapport en pièce jointe, et la réservation affiche « Attestation VGP envoyée le … à chantier@exemple.fr ».
2. **Given** une machine non soumise à VGP, avec ou sans rapport déposé, **When** un salarié la réserve, **Then** aucune attestation n'est envoyée et la réservation n'affiche aucune section attestation.
3. **Given** une réservation d'une machine soumise à VGP, **When** le salarié valide la réservation, **Then** la validation n'attend pas l'envoi de l'e-mail : la réservation est confirmée même si l'envoi n'a pas encore eu lieu.
4. **Given** une réservation annulée avant que l'attestation ne soit partie, **When** l'envoi aurait dû avoir lieu, **Then** aucune attestation n'est envoyée.
5. **Given** une attestation envoyée, **When** un salarié consulte l'historique de la réservation, **Then** il voit l'envoi, l'adresse destinataire, le rapport envoyé et la date.

---

### User Story 2 - Déposer le rapport de VGP d'une machine (Priority: P1)

Après chaque vérification, un salarié dépose dans l'outil le rapport remis par l'organisme de contrôle pour la machine concernée, avec la date de vérification et la date d'échéance. Ce rapport devient le rapport en vigueur de la machine et c'est lui qui est envoyé aux clients. Les attestations qui attendaient un rapport pour cette machine partent alors automatiquement.

**Why this priority**: Sans rapport déposé, il n'y a rien à envoyer. Le dépôt est la condition de l'envoi automatique.

**Independent Test**: Déposer un rapport sur une nacelle qui n'en avait pas et qui a une réservation confirmée en attente de rapport : l'attestation part vers le client de cette réservation, et la fiche machine affiche le rapport en vigueur et ses dates.

**Acceptance Scenarios**:

1. **Given** une machine soumise à VGP, **When** un salarié dépose un rapport avec une date de vérification au 2 octobre et une échéance au 1er avril, **Then** le rapport devient le rapport en vigueur de la machine, la date d'échéance VGP de la machine passe au 1er avril, et la fiche machine affiche le rapport et ses dates.
2. **Given** une machine soumise à VGP sans rapport déposé et une réservation confirmée de cette machine, **When** la réservation est créée, **Then** la réservation est confirmée et affiche « Attestation VGP en attente : rapport de VGP non déposé pour la machine ».
3. **Given** une réservation en attente de rapport, **When** un salarié dépose le rapport de la machine, **Then** l'attestation part automatiquement vers le client de la réservation.
4. **Given** un fichier qui n'est pas un document accepté (format ou taille), **When** un salarié tente de le déposer, **Then** l'outil refuse et indique le motif.
5. **Given** une machine avec un rapport en vigueur, **When** un salarié dépose un nouveau rapport, **Then** le nouveau rapport devient le rapport en vigueur et l'ancien reste consultable dans l'historique de la machine.

---

### User Story 3 - Bloquer la sortie tant que l'attestation n'est pas envoyée ou remise (Priority: P1)

Le jour du départ, le salarié ne peut pas enregistrer la sortie d'une machine soumise à VGP tant que l'attestation n'a pas été envoyée au client de la réservation, quelle qu'en soit la raison (e-mail du client manquant, rapport non déposé, envoi en attente ou en échec). L'outil indique pourquoi et ce qu'il faut faire. Si le client est au comptoir et ne peut pas recevoir l'e-mail, le salarié lui remet le rapport imprimé et enregistre cette remise en main propre, ce qui débloque la sortie.

**Why this priority**: C'est ce qui rend la règle « L'attestation VGP est envoyée automatiquement au client » vraie pour chaque machine qui sort ; la remise en main propre tracée couvre les clients sans e-mail sans laisser sortir une machine sans preuve remise.

**Independent Test**: Créer une réservation d'une nacelle pour un client sans e-mail : la réservation est acceptée, la sortie est refusée avec le motif « e-mail du client manquant » ; renseigner l'e-mail : l'attestation part et la sortie devient possible.

**Acceptance Scenarios**:

1. **Given** un client sans adresse e-mail, **When** un salarié réserve pour lui une machine soumise à VGP, **Then** la réservation est confirmée et affiche « Attestation VGP non envoyée : e-mail du client manquant ».
2. **Given** une réservation d'une machine soumise à VGP dont l'attestation n'est pas envoyée, **When** un salarié tente d'enregistrer la sortie, **Then** l'outil refuse et indique le motif (e-mail manquant, rapport non déposé, envoi en attente ou en échec).
3. **Given** une attestation non envoyée pour e-mail manquant, **When** un salarié renseigne l'adresse e-mail du client depuis la réservation, **Then** l'attestation part automatiquement vers cette adresse, et la sortie n'est plus bloquée par l'attestation.
4. **Given** une réservation dont l'attestation a été envoyée, **When** un salarié enregistre la sortie, **Then** l'attestation ne bloque pas la sortie ; les autres conditions de sortie (VGP valide et machine disponible de la feature 001, photos de départ de la feature 002, et toute autre condition ajoutée par une autre fonctionnalité) restent exigées.
5. **Given** une réservation d'une machine non soumise à VGP, **When** un salarié enregistre la sortie, **Then** l'attestation ne bloque pas la sortie.
6. **Given** deux salariés dont l'un renseigne l'e-mail du client pendant que l'autre tente la sortie, **When** les deux actions arrivent en même temps, **Then** la sortie n'est acceptée que si l'attestation est effectivement envoyée ou remise au moment où la sortie est vérifiée.
7. **Given** une réservation confirmée d'une machine avec rapport en vigueur, pour un client sans e-mail présent au comptoir, **When** le salarié enregistre « Rapport VGP remis en main propre », **Then** l'attestation passe « remise en main propre le … par … », sort de la liste des attestations à traiter, l'historique trace la remise et le rapport remis, et l'attestation ne bloque plus la sortie.
8. **Given** une machine soumise à VGP sans rapport déposé, **When** un salarié tente d'enregistrer une remise en main propre, **Then** l'outil refuse : il n'y a pas de rapport à remettre.

---

### User Story 4 - Ne perdre aucun envoi en cas d'échec (Priority: P1)

Si l'envoi échoue (service d'envoi indisponible, adresse refusée), l'attestation n'est pas perdue : l'envoi est relancé automatiquement, et toute attestation qui reste non envoyée apparaît dans une liste visible de toutes les agences, avec le motif, jusqu'à ce qu'elle parte.

**Why this priority**: Un envoi qui échoue en silence bloque la sortie le jour du départ, devant le client. La liste permet de corriger avant.

**Independent Test**: Rendre le service d'envoi indisponible, créer une réservation de nacelle : la réservation affiche « en attente d'envoi » ; rétablir le service : l'attestation part sans action et la réservation affiche « envoyée ».

**Acceptance Scenarios**:

1. **Given** le service d'envoi indisponible, **When** une réservation de nacelle est créée, **Then** la réservation est confirmée normalement et l'attestation est marquée « en attente d'envoi ».
2. **Given** une attestation en attente suite à une indisponibilité, **When** le service redevient disponible, **Then** l'attestation est envoyée automatiquement, sans action d'un salarié, et n'est envoyée qu'une fois.
3. **Given** une adresse e-mail refusée définitivement (adresse invalide), **When** un salarié consulte la liste des attestations à traiter, **Then** il voit la réservation, le client, l'adresse, la date de l'échec et le motif en clair.
4. **Given** des attestations non envoyées (en échec, e-mail manquant, rapport non déposé, ou en attente depuis plus d'une heure), **When** un salarié ouvre l'outil, **Then** l'existence d'attestations à traiter est visible sans avoir à la chercher.
5. **Given** la liste des attestations à traiter, **When** un salarié la consulte, **Then** les réservations dont le départ est le plus proche apparaissent en premier.

---

### User Story 5 - Renvoyer l'attestation à la demande (Priority: P2)

Le client dit ne pas avoir reçu l'attestation, l'a perdue, ou donne une autre adresse. Depuis la réservation, le salarié corrige au besoin l'adresse e-mail du client et renvoie l'attestation en un clic.

**Why this priority**: Le cas arrive au comptoir, souvent au moment du départ. L'envoi automatique couvre le cas nominal sans lui.

**Independent Test**: Sur une réservation dont l'attestation a été envoyée, cliquer « Renvoyer l'attestation » : un second e-mail part, et l'historique montre les deux envois.

**Acceptance Scenarios**:

1. **Given** une réservation confirmée ou en cours d'une machine soumise à VGP avec rapport déposé, **When** un salarié clique « Renvoyer l'attestation », **Then** l'attestation est renvoyée à l'adresse e-mail actuelle du client et l'envoi est ajouté à l'historique avec son auteur.
2. **Given** une attestation en échec pour adresse refusée, **When** un salarié corrige l'adresse e-mail du client, **Then** l'attestation part vers la nouvelle adresse et sort de la liste des attestations à traiter.
3. **Given** une réservation annulée ou clôturée, **When** un salarié consulte la réservation, **Then** le renvoi n'est pas proposé, et une demande de renvoi est refusée par le serveur.
4. **Given** un nouveau rapport déposé depuis l'envoi initial, **When** un salarié renvoie l'attestation, **Then** c'est le rapport en vigueur à la date du renvoi qui est envoyé.

---

### Edge Cases

- **Machine soumise à VGP sans rapport déposé** : la réservation est acceptée (sous réserve des règles VGP de la feature 001) ; l'attestation est « en attente de rapport », figure dans la liste à traiter, et la sortie est bloquée jusqu'au dépôt du rapport et à l'envoi.
- **Machine non soumise à VGP** : aucune attestation, aucun blocage, qu'un rapport soit déposé ou non.
- **Rapport renouvelé après l'envoi** : l'attestation déjà envoyée reste valable (la feature 001 garantit à la réservation que la VGP couvre toute la location) ; aucun nouvel envoi automatique ; le salarié peut renvoyer le nouveau rapport (US5).
- **Rapport déposé dont l'échéance ne couvre plus une réservation existante** : la réservation passe « en conflit » selon les règles de la feature 001 ; l'attestation déjà envoyée n'est pas rappelée.
- **Réservation créée plusieurs semaines à l'avance** : l'attestation part à la création ; elle couvre toute la période louée.
- **Plusieurs réservations du même client le même jour** : une attestation par réservation, chacune avec le rapport de sa machine.
- **Double déclenchement** (double validation, reprise après panne, relance automatique après une réponse perdue) : le client ne reçoit pas deux fois l'envoi automatique de la même réservation.
- **Adresse e-mail du client modifiée après un envoi réussi** : pas de renvoi automatique ; le salarié renvoie s'il le souhaite (US5). La modification vaut pour toutes les réservations de ce client.
- **Cumul avec les autres conditions de sortie** : l'attestation est une condition de sortie parmi d'autres (VGP et disponibilité de la feature 001, photos de départ de la feature 002, et celles ajoutées par d'autres fonctionnalités, comme la caution) ; chacune est vérifiée indépendamment, et le refus indique au moins un motif bloquant. Le bouton de sortie suit la règle de la feature 001 : il s'active quand chaque section concernée signale l'étape prête.
- **Réservations confirmées avant la mise en service** : leur attestation est envoyée automatiquement à la mise en service, comme si elles venaient d'être créées (mêmes états, même liste à traiter, même garantie d'envoi unique) ; les réservations déjà en cours ou clôturées ne sont pas concernées.
- **Machine soumise à VGP uniquement par sa catégorie** : traitée comme toute machine soumise à VGP (règle de la feature 001).
- **Retour d'une réservation en cours** : l'attestation n'a aucun effet sur l'enregistrement du retour.
- **Remise en main propre puis e-mail renseigné** : aucune attestation automatique n'est envoyée en plus ; le renvoi manuel reste possible (US5).
- **Remise en main propre puis nouveau rapport déposé** : la remise reste valable (même règle que pour un envoi).

## Requirements *(mandatory)*

### Functional Requirements

**Rapport de VGP**

- **FR-001**: Les salariés DOIVENT pouvoir déposer, pour une machine soumise à VGP, le rapport de VGP remis par l'organisme de contrôle, avec sa date de vérification et sa date d'échéance.
- **FR-002**: Le dernier rapport déposé DOIT devenir le rapport en vigueur de la machine, et sa date d'échéance DOIT devenir la date d'échéance VGP de la machine ; les rapports précédents DOIVENT rester consultables.
- **FR-003**: Le système DOIT refuser le dépôt d'un fichier dont le format ou la taille n'est pas accepté, et en indiquer le motif.
- **FR-004**: La fiche machine DOIT afficher le rapport en vigueur, sa date de vérification et sa date d'échéance, ou l'absence de rapport.

**Envoi automatique**

- **FR-005**: Le système DOIT envoyer, sans action d'un salarié, le rapport de VGP en vigueur de la machine réservée à l'adresse e-mail du client, pour toute réservation confirmée d'une machine soumise à VGP : à sa création, ou à la mise en service pour les réservations déjà confirmées à cette date.
- **FR-006**: Le système NE DOIT PAS envoyer d'attestation pour une machine non soumise à VGP, ni pour une réservation annulée avant l'envoi.
- **FR-007**: L'e-mail DOIT comporter, en français : le nom du client, la référence et la catégorie de la machine, les dates de début et de fin de la réservation, l'agence de retrait, la date d'échéance de la VGP, et le rapport en pièce jointe, tel que déposé.
- **FR-008**: L'enregistrement de la réservation NE DOIT PAS être bloqué ni ralenti par l'envoi de l'attestation ou par l'état du service d'envoi.
- **FR-009**: Le système DOIT garantir que l'envoi automatique d'une réservation n'est fait qu'une fois, quel que soit le nombre de tentatives.
- **FR-010**: Quand une attestation attend un rapport (non déposé) ou une adresse (e-mail manquant), le dépôt du rapport ou la saisie de l'adresse DOIT déclencher l'envoi automatiquement.

**Condition de sortie**

- **FR-011**: Le système DOIT refuser l'enregistrement de la sortie d'une réservation d'une machine soumise à VGP tant que son attestation n'a été ni envoyée ni remise en main propre, et indiquer le motif (e-mail manquant, rapport non déposé, envoi en attente, envoi en échec).
- **FR-011a**: Les salariés DOIVENT pouvoir enregistrer, sur une réservation confirmée d'une machine soumise à VGP dont le rapport est déposé, la remise en main propre du rapport en vigueur au client ; le système DOIT refuser la remise si aucun rapport n'est déposé ou si la réservation n'est pas confirmée. Aucun autre moyen ne débloque la sortie.
- **FR-012**: Ce refus DOIT être garanti par le serveur au moment de la sortie ; l'état affiché sur la réservation n'est qu'un confort.
- **FR-013**: Cette condition DOIT s'ajouter aux autres conditions de sortie sans les remplacer ni les modifier, et n'avoir aucun effet sur le retour.

**Échecs et relances**

- **FR-014**: Le système DOIT donner à l'attestation de chaque réservation concernée un état : en attente d'envoi, envoyée, remise en main propre, en échec, en attente de rapport, en attente d'e-mail ; et conserver la date de chaque tentative et le motif de chaque échec.
- **FR-015**: Le système DOIT relancer automatiquement un envoi qui a échoué pour une cause temporaire (service d'envoi indisponible), jusqu'à réussite ; un refus définitif (adresse invalide) passe l'attestation « en échec » sans relance automatique.
- **FR-016**: Le système DOIT présenter à toutes les agences la liste des attestations à traiter (ni envoyées ni remises) — en échec, en attente d'e-mail, en attente de rapport, ou en attente d'envoi depuis plus d'une heure — pour les réservations confirmées, avec la réservation, la date de départ, le client, l'adresse, la date et le motif en clair, triée par date de départ.
- **FR-017**: Le système DOIT rendre visible dès l'ouverture de l'outil l'existence d'attestations à traiter.

**Renvoi et correction**

- **FR-018**: Les salariés DOIVENT pouvoir renvoyer l'attestation d'une réservation confirmée ou en cours d'une machine soumise à VGP avec rapport déposé, à l'adresse e-mail actuelle du client ; le système DOIT refuser le renvoi pour une réservation annulée ou clôturée.
- **FR-019**: Les salariés DOIVENT pouvoir renseigner ou corriger l'adresse e-mail du client depuis la réservation ; une attestation en échec ou en attente d'e-mail DOIT alors partir automatiquement vers la nouvelle adresse.
- **FR-020**: Une attestation envoyée ou renvoyée DOIT contenir le rapport en vigueur de la machine au moment de l'envoi.

**Suivi et traçabilité**

- **FR-021**: Le système DOIT afficher sur chaque réservation concernée l'état de l'attestation, l'adresse destinataire et la date du dernier envoi.
- **FR-022**: Le système DOIT enregistrer dans l'historique de la réservation chaque envoi, chaque échec, chaque renvoi manuel, chaque remise en main propre (avec le rapport remis) et chaque correction d'adresse e-mail, avec l'auteur (ou « automatique ») et la date ; et dans l'historique de la machine chaque dépôt de rapport, avec l'auteur et la date.

### Key Entities

- **Rapport de VGP** : le document remis par l'organisme de contrôle après une vérification ; machine, fichier, date de vérification, date d'échéance, auteur et date du dépôt. Le dernier déposé est le rapport en vigueur de la machine.
- **Attestation d'une réservation** : l'obligation d'envoyer le rapport au client d'une réservation d'une machine soumise à VGP ; état (en attente d'envoi, envoyée, remise en main propre, en échec, en attente de rapport, en attente d'e-mail), motif du dernier échec, date du dernier envoi ou de la remise, adresse destinataire.
- **Envoi d'attestation** : une tentative d'envoi ou une remise en main propre ; attestation concernée, rapport envoyé ou remis, canal (e-mail ou main propre), adresse le cas échéant, automatique ou manuel avec son auteur, date, résultat.
- **Machine** (feature 001) : soumise à VGP, date d'échéance ; associée à ses rapports de VGP et à son rapport en vigueur.
- **Client** (feature 001) : son adresse e-mail devient modifiable depuis la réservation.
- **Réservation** (feature 001) : associée à son attestation ; sa sortie est conditionnée à l'envoi ; son historique trace les envois.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Zéro sortie d'une machine soumise à VGP, à partir de la mise en service, sans que l'attestation ait été envoyée au client ou que sa remise en main propre ait été enregistrée.
- **SC-002**: 100 % des réservations confirmées de machines soumises à VGP ont leur attestation envoyée ou remise en main propre, ou figurent dans la liste des attestations à traiter ; aucune n'est absente des deux.
- **SC-003**: 95 % des attestations parviennent au client dans les 5 minutes qui suivent la création de la réservation, lorsque le client a une adresse e-mail valide, que le rapport est déposé et que le service d'envoi est disponible.
- **SC-004**: Zéro envoi automatique en double pour une même réservation sur les 3 premiers mois.
- **SC-005**: Zéro rapport de VGP envoyé à la main par les agences pour une réservation créée dans l'outil.
- **SC-006**: Un salarié dépose un rapport de VGP en moins d'une minute, et renvoie une attestation en moins de 30 secondes depuis la réservation.

## Out of Scope

- Planification des VGP (rendez-vous avec l'organisme de contrôle, relance avant échéance).
- Génération d'une attestation par l'outil : seul le rapport de l'organisme est envoyé.
- Lecture automatique du contenu du rapport (dates extraites du document) : les dates sont saisies au dépôt.
- Envoi d'autres documents au client (contrat, conditions générales, photos, facture).
- Envoi par SMS ou par courrier ; impression du rapport depuis un modèle de l'outil (le rapport déposé est imprimé tel quel).
- Suivi de l'ouverture ou de la lecture de l'e-mail par le client, et des rebonds reçus après l'envoi.
- Envoi d'attestation pour les réservations déjà en cours ou clôturées à la mise en service.
- Droits différenciés par rôle : tous les salariés peuvent déposer un rapport, renvoyer l'attestation et corriger l'e-mail du client, comme dans les features 001 à 003.

## Assumptions

- « Dès la réservation » s'entend à la création de la réservation dans l'outil ; la feature 001 garantit qu'à ce moment la VGP couvre toute la période louée, donc l'attestation envoyée reste valable jusqu'à la fin de la location.
- Le rapport de VGP est un fichier PDF ou une image (scan) fourni par l'organisme de contrôle ; une taille maximale de 10 Mo par fichier suffit.
- La date d'échéance saisie au dépôt du rapport remplace la date saisie à la main de la feature 001 ; la saisie manuelle reste possible pour les machines dont le rapport n'est pas encore numérisé.
- Une seule adresse e-mail par client (celle de la fiche client de la feature 001) ; pas de copie à l'agence ni à un autre contact dans cette version.
- L'e-mail part au nom de Vallet Location, avec une adresse d'expédition et de réponse fournies par le client avant la mise en production.
- Le seuil d'une heure avant qu'une attestation en attente d'envoi apparaisse dans la liste à traiter est une proposition à confirmer avec M. Vallet.
- À la mise en service, les réservations confirmées de machines soumises à VGP reçoivent leur attestation automatiquement ; celles dont le client n'a pas d'e-mail ou dont la machine n'a pas de rapport déposé apparaissent dans la liste à traiter. Les agences sont prévenues de déposer les rapports des machines concernées avant la bascule.
- Cette fonctionnalité s'appuie sur la feature 001 (machines, VGP, clients, réservations, sortie, historique) ; elle se combine avec les conditions de sortie des features 002 (photos) et 004 (caution) sans en dépendre.
