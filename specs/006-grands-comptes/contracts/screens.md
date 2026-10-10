# Contrat : écrans

Tous les écrans exigent une session authentifiée et vérifiée. Textes en français via `functional/accounts/resources/lang/fr`.

| Écran | Route / composant | Permission | Ce qu'on y fait | Refus affichés | Couvre |
|-------|-------------------|------------|-----------------|----------------|--------|
| Grands comptes | `/grands-comptes`, `accounts.key-accounts` | `key_accounts.manage` | lister les grands comptes (nom, identifiant de facturation, date et auteur de la désignation) ; rechercher un client professionnel ; désigner ; retirer la désignation | client non professionnel, identifiant de facturation manquant, déjà grand compte | US1, FR-001, FR-002, FR-016 |
| Bons de commande manquants | `/bons-de-commande`, `accounts.missing-purchase-orders` | `purchase_orders.manage` | réservations `confirmed` de grands comptes sans numéro : réservation, client, machine, agence de rattachement, date de début ; tri par date de début ; filtre par agence ; mise en évidence à 3 jours ou moins ; saisie du numéro en ligne | numéro vide ou trop long, réservation déjà sortie | US4, FR-014, FR-015 |
| Section « Bon de commande » du détail de réservation | `accounts.purchase-order-section` dans `ReservationDetailSections` | lecture : `reservations.manage` ; saisie : `purchase_orders.manage` | état (exigé et à saisir, facultatif, saisi, figé) ; mention « tarif négocié appliqué par la facturation » pour un grand compte ; saisie ou correction tant que la réservation est confirmée ; auteur, agence, date | numéro vide ou trop long, réservation déjà sortie, client non professionnel | US2, FR-004, FR-005, FR-008, FR-009 |
| Formulaire de création de réservation (booking) | badge via `CustomerBadges` | `reservations.manage` | badge « Grand compte » dans la liste des clients ; précision « tarif négocié appliqué par la facturation, bon de commande exigé avant la sortie » pour le client sélectionné | — | US1 scénario 4, FR-004 |
| Détail de réservation (booking) | refus de sortie | `reservations.manage` | message « Le numéro de bon de commande du client grand compte doit être saisi avant la sortie. » | bon de commande manquant | US2 scénario 2, FR-007 |
| Barre latérale (`app/`) | entrées « Grands comptes » et « Bons de commande » | selon la permission | navigation | — | — |
