# Contrat : écrans

Tous les écrans et actions exigent la permission `certification.manage`. Textes en français par fichiers de traduction (`certification::…`). Les refus sont des `RefusalException` affichées par `DisplaysRefusals`.

| Écran | Emplacement | Actions | Refus affichés | Exigences |
|-------|-------------|---------|----------------|-----------|
| Rapports VGP | `/vgp/machines` | lister les machines soumises à VGP (référence, catégorie, agence, rapport en vigueur, date de vérification, échéance, « aucun rapport » en évidence) ; filtrer par agence et par « sans rapport » ; ouvrir la page VGP d'une machine | — | FR-004 |
| Page VGP d'une machine | `/vgp/machines/{machine}` | déposer un rapport (fichier, date de vérification, date d'échéance) ; voir et télécharger le rapport en vigueur et les précédents (date du dépôt, auteur) | machine non soumise à VGP ; format ou taille refusés ; dates manquantes ou incohérentes | US2, FR-001 à FR-004 |
| Téléchargement d'un rapport | `/vgp/rapports/{report}/fichier` (contrôleur) | servir le fichier depuis le disque privé | 403 sans permission | FR-004 |
| Section « Attestation VGP » du détail de réservation | section enregistrée dans `ReservationDetailSections` (position 30) ; absente si la machine n'est pas soumise à VGP ou avant la mise en service | voir l'état, l'adresse, la date du dernier envoi ou de la remise, les tentatives ; renseigner ou corriger l'e-mail du client (via `UpdateCustomer::changeEmail()` de `booking`) ; « Renvoyer l'attestation » ; « Rapport VGP remis en main propre » (avec confirmation) | renvoi sur réservation annulée ou clôturée, sans rapport ou sans e-mail ; échec immédiat du renvoi (motif) ; remise sans rapport ou sur réservation non confirmée ; e-mail invalide | US1, US3, US5, FR-011a, FR-018, FR-019, FR-021 |
| Attestations à traiter | `/vgp/attestations` | lister les attestations à traiter des réservations confirmées (date de départ, réservation, machine, client, adresse, état, date, motif), triées par date de départ ; ouvrir la réservation | — | US4, FR-016 |
| Alerte | bandeau dans le layout de `app/`, sous `@can('certification.manage')` | indiquer le nombre d'attestations à traiter ; lien vers la liste | — | FR-017 |
| Sortie refusée | bouton « Enregistrer la sortie » du détail de `booking` | — | « Attestation VGP non envoyée : e-mail du client manquant » / « rapport de VGP non déposé » / « envoi en attente » / « envoi en échec : motif » | US3, FR-011 |

## Menu

Une entrée « Conformité VGP » avec deux sous-entrées : Rapports VGP, Attestations à traiter.

## Temps réel

La section et le bandeau se rafraîchissent à la réception de `CertificateChanged` (canal privé `fleet`, événement `certificate.changed`, charge utile `{reservation_id, status, delivered_at}`).
