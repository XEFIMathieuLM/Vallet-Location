# Contrat : écrans

Textes en français par fichiers de traduction. Refus affichés par `DisplaysRefusals` (`@error('refusal')`). Confirmations par `Flux::toast`, saisies dans des `flux:modal`.

| Écran | Emplacement | Actions | Refus affichés | Exigences |
|-------|-------------|---------|----------------|-----------|
| Section « Caution » du détail de réservation | `deposit.reservation-section` (affichage et disponibilité de la sortie ; une action par sous-composant : encaisser, qualifier, corriger, restituer / solder), enregistrée dans `ReservationDetailSections` en position 30, gardienne de `ReservationTransition::Departure` | voir la situation (non requise, non suivie, type à renseigner, à encaisser, encaissée, à restituer, bloquée par un dégât avec la liste des dégâts, à solder avec retenue et restitution calculées, restituée, soldée) et le montant ; qualifier le client (particulier / professionnel) ; encaisser (moyen, référence) ; corriger l'encaissement (moyen, référence, motif) ; restituer (case « comparaison départ / retour faite, aucun dégât constaté » si la réservation est clôturée) ; valider le solde | réservation non confirmée ; client non particulier ; caution déjà encaissée ; référence manquante ; motif manquant ; confirmation manquante ; dégât à traiter ; caution déjà restituée ou soldée ; garde de changement de type | US1–US4, FR-002, FR-005 à FR-016 |
| Formulaire de réservation (001) | `reservations/nouvelle`, création d'un nouveau client | choisir « particulier » ou « professionnel » (obligatoire) | type manquant | US2, FR-001 |
| Cautions en attente | `/cautions` | lister les cautions à restituer, à solder et bloquées (réservation, client, agence, montant, état, en attente depuis) ; filtrer par agence de rattachement de la machine et par état ; mettre en évidence celles en attente depuis plus de 7 jours ; ouvrir la réservation | — | US5, FR-019, FR-020 |
| Montants de caution | `/cautions/montants` | voir le montant par défaut et, pour chaque catégorie, son montant effectif (propre ou par défaut) ; modifier le montant par défaut ; fixer ou retirer le montant d'une catégorie | montant vide, nul, négatif ou mal formé | US6, FR-017, FR-018 |

## Accès

- Section, liste des cautions : `deposits.manage` (`DepositPermission::ManageDeposits`). Sans la permission, la section n'affiche que la situation, sans action ; elle signale tout de même la disponibilité de la sortie.
- Montants de caution : `deposit_rates.manage` (`DepositPermission::ManageDepositRates`).
- Les deux permissions sont données au rôle `salarie` par `PermissionSeeder`.

## Menu

Une entrée « Cautions » avec deux sous-entrées : En attente, Montants.
