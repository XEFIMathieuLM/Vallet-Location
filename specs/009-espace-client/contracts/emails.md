# Contrat : e-mails envoyés aux comptes clients

Tous les e-mails partent par une Notification du layer `portal`, envoyée au `CustomerAccount` (`Notifiable`). Le `toMail()` de chaque notification rend un Mailable (règle `mail-via-notifications`). Ils sont en français et partent de l'adresse d'expédition de l'application (`mail.from`), la même que pour les attestations (005).

| Notification | Mailable | Déclencheur | Contenu |
|---|---|---|---|
| `VerifyCustomerEmail` | `VerifyCustomerEmailMail` | inscription ; bouton « renvoyer le lien » | lien signé `portal.verification.verify`, valable 60 minutes |
| `ResetCustomerPassword` | `ResetCustomerPasswordMail` | « mot de passe oublié » | lien `portal.password.reset`, valable 60 minutes |
| `ReservationRequestDecided` | `ReservationRequestConfirmedMail` | demande confirmée | machine réservée (référence, catégorie), dates, agence de retrait (nom, adresse) ; mention : caution, bon de commande et attestation VGP sont gérés par l'agence |
| `ReservationRequestDecided` | `ReservationRequestRefusedMail` | demande refusée | machine demandée, dates, motif du refus, invitation à envoyer une nouvelle demande |
| `ReservationRequestDecided` | `ReservationRequestExpiredMail` | demande expirée | machine demandée, dates ; « votre demande n'a pas pu être traitée avant sa date de début » |

## Garanties

- **Mode d'envoi** : les deux premières notifications partent en file standard. `ReservationRequestDecided` part depuis le job `NotifyRequestDecisionJob`, unique par demande.
- **Trace de l'envoi** : après un envoi réussi, le job pose `customer_notified_at`. Une demande annulée par le client n'envoie rien.
- **Rattrapage** : `portal:reconcile` remet en file les demandes décidées depuis plus de 10 minutes sans `customer_notified_at` (R8).
- **Pas de doublon** : le job relit la demande sous verrou et ne fait rien si `customer_notified_at` est déjà posé.
- **Décision non bloquée** : la décision est validée avant tout envoi (job dispatché après commit), et un service d'envoi indisponible ne bloque aucune décision (FR-021).
- **Tests** : `Notification::fake()` pour le déclenchement, rendu des Mailables pour le contenu, transport `array` pour l'idempotence.
