# Feature Specification: Tableau de bord d'accueil

**Feature Branch**: `008-tableau-de-bord`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: "La page d'accueil devient un écran de synthèse en lecture seule qui montre en un coup d'œil ce qui demande une action aujourd'hui : départs et retours du jour, retours en retard, réservations en conflit, disponibilité du parc, et les compteurs « à traiter » de chaque domaine, chacun avec un lien vers l'écran existant. Filtre par agence, par défaut celle du salarié. Périmètre strict : aucune nouvelle règle métier, pas de graphiques complexes, aucune modification du comportement des features 001 à 007." S'appuie sur les features 001 à 007, toutes livrées.

## Contexte

Les features 001 à 007 ont chacune ajouté un écran de suivi : réservations et planning (001), dégâts à traiter (002), transmissions à traiter (003), cautions en attente (004), attestations VGP à traiter (005), bons de commande manquants (006), ventes (007). Deux de ces listes signalent déjà leur existence par un bandeau en haut de chaque page (transmissions et attestations). Mais la page d'accueil de l'outil est encore vide : un salarié qui arrive le matin doit ouvrir sept écrans pour savoir ce qui l'attend.

Cette fonctionnalité fait de la page d'accueil le point de départ de la journée : elle rassemble, pour l'agence du salarié, les machines qui partent et qui rentrent aujourd'hui, les anomalies à régler (retours en retard, réservations en conflit), l'état du parc, et le nombre d'éléments en attente dans chaque liste de suivi, avec un lien direct vers l'écran où l'action se fait. Elle ne permet aucune action et n'invente aucune règle : chaque chiffre est celui de l'écran vers lequel il renvoie.

## Clarifications

### Session 2026-10-10

- Q: Les compteurs « à traiter » suivent-ils l'agence choisie sur le tableau de bord ? → A: Non : ils portent sur l'ensemble du réseau, quelle que soit l'agence choisie, et restent égaux aux listes et aux bandeaux actuels, partagés entre agences ; seuls les départs, retours, anomalies et l'état du parc suivent l'agence (FR-014).
- Q: L'état du parc affiche-t-il un chiffre « réservées aujourd'hui » ? → A: Non : les statuts du parc seuls (disponibles, sorties, atelier, en panne), chacun concordant avec la liste qu'il ouvre (FR-017).
- Q: Les départs et retours couvrent-ils aujourd'hui seulement, ou aussi demain ? → A: Aujourd'hui, plus les départs prévus les jours passés et pas encore enregistrés (FR-006) ; réponse complétée par la question suivante.
- Q: Faut-il une section « Départs à venir » sur les 7 prochains jours, en plus des départs du jour ? → A: Oui : les départs des 7 jours suivants, regroupés par date, pour préparer caution, bon de commande et attestation avant le jour du départ (FR-006a).
- Q: Faut-il remplacer le chiffre « VGP non valide » par une liste « VGP à surveiller » ? → A: Oui : les machines de l'agence dont la VGP est expirée, non renseignée ou à échéance dans les 30 jours, avec leur date d'échéance et un lien vers leur page VGP ; seuil d'affichage seulement, sans règle de blocage nouvelle (FR-015a).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Voir les départs et les retours du jour de son agence (Priority: P1)

En arrivant le matin, le salarié ouvre l'outil et voit sur la page d'accueil la liste des machines de son agence qui doivent partir aujourd'hui, celles qui partent dans les 7 prochains jours, et celles qui doivent rentrer aujourd'hui, avec pour chacune la machine, le client et les dates. Un clic sur une ligne ouvre le détail de la réservation, où se font la sortie et le retour.

**Why this priority**: C'est la question que se pose chaque agence chaque matin. Sans elle, le tableau de bord n'a pas de raison d'être la page d'accueil.

**Independent Test**: Avec, pour l'agence du salarié, deux réservations confirmées qui commencent aujourd'hui, une réservation en cours qui se termine aujourd'hui, et une réservation d'une autre agence qui commence aujourd'hui : la page d'accueil affiche les deux départs et le retour, et pas la réservation de l'autre agence.

**Acceptance Scenarios**:

1. **Given** un salarié de l'agence de Rouen et deux réservations confirmées de machines rattachées à Rouen qui commencent aujourd'hui, **When** il ouvre la page d'accueil, **Then** la section « Départs du jour » liste ces deux réservations avec la référence et la catégorie de la machine, le client et les dates, triées par machine.
2. **Given** une réservation en cours d'une machine rattachée à Rouen dont la date de fin est aujourd'hui, **When** le salarié de Rouen ouvre la page d'accueil, **Then** la section « Retours du jour » la liste avec la machine, le client et les dates.
3. **Given** une ligne de la section « Départs du jour », **When** le salarié clique dessus, **Then** le détail de la réservation s'ouvre, avec les conditions de sortie des features 001 à 007 inchangées.
4. **Given** une réservation confirmée d'une machine rattachée à Rouen dont la date de début est passée et qui n'est pas sortie, **When** le salarié de Rouen ouvre la page d'accueil, **Then** elle figure parmi les départs du jour, signalée « départ prévu le … ».
5. **Given** aucune réservation qui part ni ne rentre aujourd'hui pour l'agence, **When** le salarié ouvre la page d'accueil, **Then** chaque section affiche un message « aucun départ prévu aujourd'hui » / « aucun retour prévu aujourd'hui ».
6. **Given** une réservation confirmée d'une machine rattachée à Rouen qui commence dans 3 jours, **When** le salarié de Rouen ouvre la page d'accueil, **Then** elle figure dans la section « Départs à venir », sous sa date ; une réservation qui commence dans 8 jours n'y figure pas.
7. **Given** une réservation qui part aujourd'hui depuis l'agence de Rouen, **When** un salarié d'une autre agence enregistre sa sortie, **Then** elle disparaît des départs du jour de la page d'accueil du salarié de Rouen sans qu'il recharge la page.

---

### User Story 2 - Repérer les anomalies : retours en retard et réservations en conflit (Priority: P1)

Le salarié voit sur la page d'accueil les machines de son agence qui auraient dû rentrer et ne sont pas rentrées, et les réservations à venir de son agence que l'outil a signalées « en conflit » (feature 001 : machine à l'atelier ou en panne, VGP ne couvrant plus la période, machine précédente pas rentrée). Chaque ligne mène au détail de la réservation.

**Why this priority**: Un retour en retard bloque la location suivante, et une réservation en conflit est un client à reloger. Plus tôt l'agence les voit, plus tôt elle appelle le client.

**Independent Test**: Avec une réservation en cours dont la date de fin était hier et une réservation confirmée signalée en conflit, toutes deux de machines de l'agence du salarié : la page d'accueil liste la première dans « Retours en retard » avec son nombre de jours de retard, et la seconde dans « Réservations en conflit » avec son motif.

**Acceptance Scenarios**:

1. **Given** une réservation en cours d'une machine de l'agence dont la date de fin était il y a 3 jours, **When** le salarié ouvre la page d'accueil, **Then** la section « Retours en retard » la liste avec la machine, le client, la date de fin prévue et « 3 jours de retard ».
2. **Given** une réservation confirmée d'une machine de l'agence signalée en conflit, **When** le salarié ouvre la page d'accueil, **Then** la section « Réservations en conflit » la liste avec la machine, le client, les dates et le motif du conflit tel qu'affiché par la feature 001.
3. **Given** une réservation en conflit, **When** un salarié l'annule (l'annulation efface le motif, feature 001), **Then** elle sort de la section « Réservations en conflit » sans rechargement de la page.
4. **Given** aucune anomalie pour l'agence, **When** le salarié ouvre la page d'accueil, **Then** les deux sections indiquent qu'il n'y a ni retour en retard ni réservation en conflit.

---

### User Story 3 - Voir le nombre d'éléments à traiter dans chaque liste de suivi (Priority: P1)

La page d'accueil affiche un compteur par liste de suivi existante : transmissions à traiter (features 003 et 007), attestations VGP à traiter (005), cautions en attente d'action (004), bons de commande manquants (006), dégâts à traiter (002 et 003), ventes dont la date de remise prévue est dépassée (007). Chaque compteur renvoie vers l'écran correspondant, où l'action se fait.

**Why this priority**: Ces listes existent déjà, mais il faut penser à les ouvrir. Un compteur visible dès l'accueil évite qu'une caution, une attestation ou une transmission reste oubliée.

**Independent Test**: Avec une transmission en échec, deux attestations en attente d'e-mail, une caution à restituer, un bon de commande manquant, un dégât à traiter et une vente réservée dont la remise prévue est dépassée : chaque compteur affiche le nombre attendu, égal au nombre de lignes de l'écran vers lequel il renvoie, et un clic ouvre cet écran.

**Acceptance Scenarios**:

1. **Given** des éléments dans chaque liste de suivi, **When** le salarié ouvre la page d'accueil, **Then** chaque compteur affiche le nombre d'éléments de sa liste, calculé selon la définition de la feature qui a créé cette liste, sans nouvelle règle.
2. **Given** le compteur « Cautions en attente », **When** le salarié clique dessus, **Then** l'écran des cautions en attente de la feature 004 s'ouvre.
3. **Given** une liste de suivi vide, **When** le salarié ouvre la page d'accueil, **Then** son compteur affiche 0 de façon neutre ; un compteur non nul est mis en évidence.
4. **Given** une attestation à traiter, **When** elle est envoyée (feature 005), **Then** le compteur des attestations diminue sans rechargement de la page.
5. **Given** un salarié qui n'a pas l'autorisation d'ouvrir un écran de suivi, **When** il ouvre la page d'accueil, **Then** le compteur de cet écran ne lui est pas affiché.
6. **Given** une caution en attente sur une machine d'Évreux, **When** un salarié de Rouen ouvre la page d'accueil sur l'agence de Rouen, **Then** le compteur des cautions en attente la compte : les compteurs portent sur l'ensemble du réseau, comme les listes vers lesquelles ils renvoient.

---

### User Story 4 - Voir l'état du parc de son agence (Priority: P2)

La page d'accueil indique combien de machines de l'agence sont, à cet instant, disponibles, sorties en location, à l'atelier ou en panne ; chaque chiffre renvoie vers la liste du parc filtrée sur l'agence et ce statut. Elle liste aussi les machines dont la VGP est à surveiller : expirée, non renseignée, ou à échéance dans les 30 jours ; chacune renvoie vers sa page VGP (feature 005), où se dépose le nouveau rapport.

**Why this priority**: Utile pour répondre vite à un client au téléphone et pour voir l'atelier se remplir, mais la recherche de disponibilité (feature 001) couvre déjà le besoin d'une réservation.

**Independent Test**: Avec, pour l'agence, 5 machines disponibles, 3 sorties, 1 à l'atelier, 1 en panne, 2 retirées du parc, une machine soumise à VGP dont l'échéance est passée, une autre dont l'échéance est dans 5 jours et une troisième dont l'échéance est dans 60 jours : la page d'accueil affiche 5, 3, 1 et 1, et la liste « VGP à surveiller » contient les deux premières machines soumises à VGP, l'expirée en tête.

**Acceptance Scenarios**:

1. **Given** les machines de l'agence dans différents statuts, **When** le salarié ouvre la page d'accueil, **Then** il voit le nombre de machines disponibles, sorties, à l'atelier et en panne ; les machines retirées du parc ne sont pas comptées.
2. **Given** une machine soumise à VGP, non retirée du parc, dont l'échéance est passée, non renseignée, ou dans les 30 jours, **When** le salarié ouvre la page d'accueil, **Then** elle figure dans « VGP à surveiller » avec sa date d'échéance (ou « non renseignée ») et la mention « expirée » si l'échéance est passée, les échéances les plus proches en premier.
5. **Given** une machine de « VGP à surveiller », **When** le salarié clique dessus, **Then** la page VGP de la machine (feature 005) s'ouvre.
3. **Given** le chiffre « À l'atelier », **When** le salarié clique dessus, **Then** la liste du parc s'ouvre filtrée sur l'agence et le statut « atelier ».
4. **Given** un salarié d'une autre agence qui passe une machine de l'agence « en panne », **When** le changement est enregistré, **Then** les chiffres du parc se mettent à jour sans rechargement de la page.

---

### User Story 5 - Choisir l'agence affichée (Priority: P2)

Par défaut, la page d'accueil montre l'agence du salarié connecté. Le salarié peut choisir une autre agence, ou « Toutes les agences », pour aider une agence voisine ou avoir une vue d'ensemble. Le choix se retrouve dans l'adresse de la page, ce qui permet de la mettre en favori.

**Why this priority**: L'agence par défaut couvre le cas courant ; le choix d'une autre agence sert ponctuellement (remplacement, responsable de plusieurs agences).

**Independent Test**: Un salarié de Rouen ouvre la page d'accueil : il voit Rouen. Il choisit Évreux : les départs, retours, anomalies et l'état du parc d'Évreux s'affichent. Il choisit « Toutes les agences » : ceux des 7 agences.

**Acceptance Scenarios**:

1. **Given** un salarié rattaché à l'agence de Rouen, **When** il ouvre la page d'accueil, **Then** l'agence sélectionnée est Rouen.
2. **Given** la page d'accueil sur Rouen, **When** le salarié choisit l'agence d'Évreux, **Then** les départs, retours, anomalies et l'état du parc affichent les données d'Évreux ; les compteurs « à traiter » ne changent pas.
3. **Given** la page d'accueil, **When** le salarié choisit « Toutes les agences », **Then** les sections affichent les données des 7 agences, et chaque ligne de départ, de retour ou d'anomalie indique l'agence de rattachement de la machine.
4. **Given** une adresse de page d'accueil portant l'agence d'Évreux, **When** un salarié de Rouen l'ouvre, **Then** la page affiche Évreux.

---

### Edge Cases

- **Réservation d'une seule journée** (début et fin aujourd'hui, feature 001) : confirmée, elle figure dans les départs du jour ; une fois sortie, elle figure dans les retours du jour.
- **Départ prévu un jour passé et jamais enregistré** : la réservation confirmée reste dans les départs du jour, avec sa date de début, jusqu'à sa sortie ou son annulation ; aucune règle nouvelle ne l'annule ni ne la signale en conflit.
- **Départ prévu dans 7 jours exactement** : figure dans « Départs à venir » ; au-delà, il n'apparaît pas, la liste des réservations (feature 001) servant à voir plus loin.
- **Retour prévu aujourd'hui** : la réservation est dans « Retours du jour », pas dans « Retours en retard » ; elle passe dans les retours en retard le lendemain si elle n'est pas rentrée.
- **Agence d'une réservation** : une réservation appartient à l'agence de rattachement de sa machine, où elle est retirée et rendue (feature 001), et non à l'agence qui l'a créée.
- **Machine retirée du parc** : absente des chiffres du parc et de « VGP à surveiller ».
- **Machine à l'atelier ou en panne avec une VGP à surveiller** : comptée dans son statut et listée dans « VGP à surveiller ».
- **Machine non soumise à VGP** : jamais listée dans « VGP à surveiller », quelle que soit sa date d'échéance.
- **Machine en vente** (feature 007) : comptée selon son statut au parc, comme toute machine.
- **Salarié sans agence ou agence supprimée** : sans objet, chaque salarié est rattaché à une agence (écran Salariés) ; une agence inconnue dans l'adresse affiche l'agence du salarié.
- **Nombre de départs élevé** (vue « Toutes les agences ») : la section affiche les 20 premiers et un lien vers la liste des réservations.
- **Changement de jour pendant que la page est ouverte** : à la mise à jour suivante, les sections reflètent le nouveau jour (heure de Paris).
- **Liste de suivi dont la feature n'accorde pas l'autorisation au salarié** : compteur et lien masqués (US3, scénario 5).

## Requirements *(mandatory)*

### Functional Requirements

**Page d'accueil**

- **FR-001**: La page d'accueil de l'outil, ouverte après la connexion, DOIT être le tableau de bord ; elle remplace la page vide actuelle et ne propose aucune action de modification.
- **FR-002**: Le tableau de bord NE DOIT introduire aucune règle métier : chaque liste et chaque chiffre DOIT reprendre la définition de la feature qui porte la donnée (001 à 007), et chaque action se fait sur l'écran existant vers lequel il renvoie.
- **FR-003**: Les bandeaux d'alerte existants (transmissions à traiter, attestations à traiter) DOIVENT rester affichés comme aujourd'hui, y compris sur la page d'accueil.

**Agence affichée**

- **FR-004**: Le tableau de bord DOIT afficher par défaut l'agence de rattachement du salarié connecté, et permettre de choisir une autre agence ou « Toutes les agences ». L'agence choisie DOIT figurer dans l'adresse de la page.
- **FR-005**: Une réservation DOIT être rattachée, pour le tableau de bord, à l'agence de rattachement de sa machine.

**Opérations du jour et anomalies**

- **FR-006**: Le système DOIT lister dans « Départs du jour » les réservations confirmées dont la date de début est aujourd'hui ou passée, avec la référence et la catégorie de la machine, le client, les dates et, en vue « Toutes les agences », l'agence ; une date de début passée DOIT être signalée.
- **FR-006a**: Le système DOIT lister dans « Départs à venir » les réservations confirmées dont la date de début est comprise entre demain et dans 7 jours inclus, regroupées par date de début, avec les mêmes informations que les départs du jour.
- **FR-007**: Le système DOIT lister dans « Retours du jour » les réservations en cours dont la date de fin est aujourd'hui.
- **FR-008**: Le système DOIT lister dans « Retours en retard » les réservations en cours dont la date de fin est passée, avec le nombre de jours de retard, les plus anciennes en premier.
- **FR-009**: Le système DOIT lister dans « Réservations en conflit » les réservations confirmées signalées en conflit par la feature 001, avec leur motif, les plus proches en premier.
- **FR-010**: Chaque ligne de ces sections DOIT ouvrir le détail de la réservation. Chaque section DOIT afficher au plus 20 lignes, puis le nombre total et un lien vers la liste des réservations.
- **FR-011**: « Aujourd'hui » DOIT s'entendre à l'heure de Paris.

**Listes de suivi**

- **FR-012**: Le système DOIT afficher un compteur pour chaque liste de suivi existante, égal au nombre d'éléments qu'affiche cette liste : transmissions à traiter (003, 007), attestations VGP à traiter (005), cautions en attente d'action (004), bons de commande manquants (006), dégâts à traiter (002, 003), ventes réservées dont la date de remise prévue est dépassée (007), c'est-à-dire les ventes que la liste des ventes réservées met en évidence.
- **FR-013**: Chaque compteur DOIT renvoyer vers l'écran de sa liste ; un compteur nul DOIT être affiché de façon neutre et un compteur non nul mis en évidence.
- **FR-014**: Les compteurs DOIVENT porter sur l'ensemble du réseau, quelle que soit l'agence affichée, et renvoyer vers leur liste sans filtre, comme les bandeaux actuels.

**État du parc**

- **FR-015**: Le système DOIT afficher, pour l'agence affichée, le nombre de machines disponibles, sorties, à l'atelier et en panne, hors machines retirées du parc.
- **FR-015a**: Le système DOIT lister dans « VGP à surveiller » les machines de l'agence affichée, soumises à VGP et non retirées du parc, dont l'échéance VGP est passée, non renseignée, ou dans les 30 jours à venir, avec leur référence, leur catégorie, leur statut et leur date d'échéance ; les échéances non renseignées puis les plus proches en premier ; chaque machine DOIT renvoyer vers sa page VGP (feature 005) ; au-delà de 20 machines, la section affiche le nombre total et un lien vers la liste des machines soumises à VGP filtrée sur l'agence. Ce seuil de 30 jours est un seuil d'affichage : il ne bloque ni ne signale rien d'autre.
- **FR-016**: Chaque chiffre du parc DOIT renvoyer vers la liste du parc filtrée sur l'agence et le statut.
- **FR-017**: L'état du parc NE DOIT comporter que ces quatre chiffres et cette liste ; aucun chiffre « réservées » n'est calculé, faute d'écran existant vers lequel il renverrait exactement.

**Accès, fraîcheur et performance**

- **FR-018**: Chaque section et chaque compteur NE DOIT être affiché qu'au salarié autorisé à ouvrir l'écran vers lequel il renvoie ; le tableau de bord n'ajoute aucune autorisation propre à ses données.
- **FR-019**: Le tableau de bord DOIT se mettre à jour sans action du salarié quand une réservation, une machine ou un élément suivi change, dans les mêmes délais que les écrans existants, et au moins toutes les minutes.
- **FR-020**: Le nombre de traitements nécessaires pour afficher le tableau de bord NE DOIT PAS croître avec le nombre de réservations ou de machines affichées.

### Key Entities

Aucune donnée nouvelle : le tableau de bord lit les données existantes.

- **Agence** (feature 001) : l'agence affichée ; celle du salarié par défaut.
- **Réservation** (feature 001) : statut, dates, indicateur et motif de conflit, machine et client.
- **Machine** (feature 001) : statut, agence de rattachement, soumise à VGP, échéance VGP.
- **Listes de suivi** (features 002 à 007) : transmissions, attestations, cautions, bons de commande manquants, dégâts, ventes ; le tableau de bord n'en lit que le nombre d'éléments.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un salarié connaît les départs et les retours du jour de son agence en moins de 10 secondes après sa connexion, sans ouvrir d'autre écran.
- **SC-002**: 100 % des compteurs affichent le même nombre que la liste vers laquelle ils renvoient, au même instant.
- **SC-003**: Le tableau de bord s'affiche en moins de 2 secondes avec le parc complet (environ 400 machines, 7 agences) et un an de réservations.
- **SC-004**: Un changement de réservation ou de machine fait par une autre agence apparaît sur le tableau de bord en moins de 5 secondes ; un changement d'une liste de suivi, en moins d'une minute.
- **SC-005**: Zéro écran des features 001 à 007 dont le comportement change avec la mise en service du tableau de bord.

## Out of Scope

- Toute action depuis le tableau de bord (sortie, retour, encaissement, relance…) : elle se fait sur l'écran existant.
- Graphiques, courbes, historiques, indicateurs de chiffre d'affaires ou de taux d'occupation.
- Personnalisation du tableau de bord par salarié (choix et ordre des sections, agence mémorisée).
- Ajout de filtres par agence aux écrans existants qui n'en ont pas.
- Notifications par e-mail ou sur téléphone.
- Toute nouvelle règle : départ non enregistré annulé automatiquement, alerte de retard, seuils nouveaux.
- Droits différenciés par rôle : tous les salariés voient le même tableau de bord, selon leurs autorisations existantes.

## Assumptions

- Chaque salarié est rattaché à une agence (écran Salariés de l'application).
- Le seuil de 30 jours de « VGP à surveiller » et l'horizon de 7 jours des départs à venir sont des propositions à confirmer avec M. Vallet.
- Le volume affiché reste modeste : quelques dizaines de départs et de retours par jour pour l'ensemble des agences.
- Les définitions des listes de suivi sont celles en vigueur dans les features 002 à 007, y compris leurs seuils (24 heures pour une transmission en attente, 1 heure pour une attestation en attente d'envoi).
- La mise à jour en direct s'appuie sur les mêmes signaux que les écrans existants ; une mise à jour périodique couvre les changements qui n'en émettent pas.
- Cette fonctionnalité s'appuie sur les features 001 (agences, machines, réservations, conflits), 002 et 003 (dégâts, transmissions), 004 (cautions), 005 (attestations VGP), 006 (bons de commande) et 007 (ventes).
