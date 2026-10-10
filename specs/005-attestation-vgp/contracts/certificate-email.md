# Contrat : e-mail d'attestation VGP

## Chemin d'envoi

`Notification::route('mail', $recipientEmail)->notifyNow(new VgpCertificateNotification($certificate, $report))` — automatique depuis `SendCertificateJob`, manuel depuis `ResendCertificate`. `VgpCertificateNotification::via()` = `['mail']` ; `toMail()` rend `VgpCertificateMail` avec `->to($recipientEmail)`. Jamais `Mail::to()` (règle `laravel:mail-via-notifications`).

## Contenu (`VgpCertificateMail`, gabarit Markdown `certification::mail.vgp-certificate`, en français)

| Élément | Source |
|---|---|
| Expéditeur / réponse | `mail.from` (fourni par Vallet Location avant la mise en production) |
| Objet | « Attestation VGP – {référence machine} – réservation du {début} au {fin} » |
| Salutation | nom du client |
| Machine | référence et catégorie |
| Réservation | dates de début et de fin, agence de retrait (agence de rattachement de la machine) |
| VGP | date de vérification et date d'échéance du rapport envoyé |
| Pièce jointe | le fichier du rapport en vigueur, tel que déposé, nommé `VGP-{référence}-{date de vérification}.{extension}` |

Dates au format français (`d/m/Y`), à l'heure de Paris.

## Résultat d'un envoi

| Résultat | Effet sur l'attestation | Trace |
|---|---|---|
| accepté par le service d'envoi | `sent`, `delivered_at` = maintenant (renvoi manuel : seulement si elle n'était pas livrée) | `outcome = sent` |
| `RfcComplianceException` | `failed`, motif `invalid_address` | `outcome = failed` |
| erreur de transport 5xx sur le destinataire (550, 551, 553, 554) | `failed`, motif `recipient_rejected` | `outcome = failed` |
| autre erreur de transport (connexion, délai, 4xx) | reste `pending`, `attempts` + 1, `next_attempt_at` selon `retry_delays_minutes` | `outcome = failed`, motif `mail_service_unavailable` |
| autre exception | relancée (`rescue()`), le job échoue et sera remis en file par le rattrapage | aucune |

Un renvoi manuel en échec ne change pas l'état d'une attestation déjà livrée ; le salarié voit le motif immédiatement.
