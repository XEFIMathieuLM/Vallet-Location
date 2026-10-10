# Research: Envoi automatique de l'attestation VGP au client

Décisions de conception de la feature 005. Chaque décision est numérotée (C1…C12) et citée par le plan, le modèle de données et les tâches.

## C1 — Un layer `certification` au-dessus de `booking`

- **Decision** : nouveau layer OSDD `functional/certification`, qui dépend de `booking` et de `fleet`. Il porte les rapports de VGP, les attestations des réservations, les envois, la garde de sortie et les écrans.
- **Rationale** : la constitution impose un layer par domaine (principe I). Les rapports de VGP pourraient vivre dans `fleet`, mais leur seule raison d'être ici est la preuve remise au client d'une réservation ; les placer dans `fleet` obligerait `fleet` à connaître les réservations pour déclencher les envois, ce qui inverse le sens des dépendances.
- **Alternatives considered** : étendre `fleet` (rapports) et `booking` (attestations) — refusé : deux features 001 modifiées pour une feature 005, et le cycle de vie de l'attestation mélangé à celui de la réservation. Placer le layer au-dessus de `billing` — inutile, aucune dépendance à la facturation ni aux photos.

## C2 — Le rapport déposé, tel quel, sur un disque privé

- **Decision** : le salarié dépose un fichier PDF, JPEG ou PNG de 10 Mo au plus, avec la date de vérification et la date d'échéance. Le fichier est stocké sur un disque privé `vgp-reports` sous un nom généré, et n'est servi que par un contrôleur soumis à la permission. Le dépôt appelle l'action publique `Functional\Fleet\Actions\UpdateMachineVgp` pour reporter l'échéance sur la machine : `MachineChanged` est émis et la 001 recalcule les conflits. Le dernier rapport déposé (par `id`) est le rapport en vigueur ; les précédents restent consultables. Le dépôt est tracé dans l'historique de la machine (`activity()` sur `Machine`, journal `certification`).
- **Rationale** : la clarification Q1 impose le document officiel de l'organisme, envoyé sans transformation. Passer par l'action de `fleet` garde une seule écriture de `vgp_due_date` et réutilise le recalcul des conflits.
- **Alternatives considered** : générer une attestation — écarté par la clarification Q1. Lire les dates dans le PDF — hors périmètre (formats variables selon l'organisme).

## C3 — E-mail par Notification + Mailable, envoyé depuis un job de la feature

- **Decision** : `VgpCertificateNotification` (canal `mail`) dont `toMail()` rend `VgpCertificateMail`, un Mailable avec le rapport en pièce jointe (`Attachment::fromStorageDisk`). Le destinataire est un notifiable anonyme : `Notification::route('mail', $email)`. L'envoi automatique est mis en file par `SendCertificateJob` ; dans le job, la notification est envoyée immédiatement (`notifyNow`) sous le verrou de l'attestation, ce qui permet d'enregistrer le résultat dans la même transaction.
- **Rationale** : règle Xefi `laravel:mail-via-notifications` (jamais `Mail::to()`, `toMail()` rend un Mailable). Le client n'est pas un utilisateur de l'application et `Customer` appartient à `booking` : le notifiable anonyme évite d'ajouter `Notifiable` à un modèle d'un autre layer. Une Notification `ShouldQueue` seule ne permet pas de verrouiller l'attestation ni de connaître le résultat de l'envoi pour mettre à jour l'état ; le job de la feature est la file, la Notification reste le seul chemin vers le mail.
- **Alternatives considered** : Notification `ShouldQueue` + écoute de `NotificationSent` / méthode `failed()` — rejeté : l'état ne serait pas mis à jour sous verrou, et une reprise du worker pourrait renvoyer un mail déjà parti sans que rien ne l'empêche. `Mail::to()` direct — interdit par la règle.
- **Renvoi manuel** : synchrone, dans la requête du salarié (`notifyNow`), pour lui donner le résultat immédiatement au comptoir ; il passe par la même Notification et laisse une trace d'envoi.

## C4 — L'attestation en pattern State

- **Decision** : `ReservationCertificate` porte un statut texte casté en enum `CertificateStatus` à six valeurs, une classe d'état par valeur, et `IllegalCertificateTransitionException` pour toute transition interdite.

| Depuis \ vers | AwaitingReport | AwaitingEmail | Pending | Sent | HandDelivered | Failed |
|---|---|---|---|---|---|---|
| AwaitingReport | — | dépôt du rapport | dépôt du rapport | ✗ | ✗ | ✗ |
| AwaitingEmail | ✗ | — | saisie de l'e-mail | ✗ | remise | ✗ |
| Pending | ✗ | ✗ | relance planifiée | envoi réussi | remise | refus définitif |
| Failed | ✗ | ✗ | e-mail corrigé | renvoi manuel réussi | remise | — |
| Sent, HandDelivered | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ |

  « Livrée » signifie `Sent` ou `HandDelivered` ; ces deux états sont terminaux, et un renvoi ultérieur ajoute seulement une trace d'envoi. L'état initial est calculé par `ResolveCertificateReadiness` : pas de rapport → `AwaitingReport`, sinon pas d'e-mail → `AwaitingEmail`, sinon `Pending` et le job est mis en file.
- **Rationale** : principe III — plusieurs états et des transitions interdites (on ne revient jamais d'un état livré, on ne remet pas un rapport absent).
- **Alternatives considered** : déduire l'état des traces d'envoi — rejeté : la garde de sortie et la liste à traiter doivent lire un état unique, verrouillable.

## C5 — Un seul envoi automatique, des relances sans perte

- **Decision** :
  - Une attestation par réservation : index unique sur `reservation_certificates.reservation_id`. `OpenReservationCertificate` est idempotente (`firstOrCreate` dans une transaction ; une violation d'unicité concurrente est traduite en lecture de la ligne existante).
  - `SendCertificateJob` est `ShouldBeUnique` par attestation et `afterCommit`. `SendCertificate` verrouille l'attestation (`lockForUpdate()`), vérifie qu'elle est `Pending`, que sa relance est échue, que la réservation est confirmée et la machine toujours soumise à VGP, puis envoie, enregistre la trace et change l'état dans la même transaction.
  - Index unique partiel sur `certificate_dispatches (reservation_certificate_id) WHERE is_automatic AND outcome = 'sent'` : la base refuse un second envoi automatique réussi enregistré.
  - Échec temporaire : `attempts` + 1, `next_attempt_at` selon `retry_delays_minutes` = [1, 5, 15, 60] puis toutes les 60 minutes, l'attestation reste `Pending`. Refus définitif : `Failed` avec le motif, pas de relance.
  - `certification:reconcile`, chaque minute, sans chevauchement : ouvre les attestations manquantes (C8) et remet en file les attestations `Pending` dont la relance est échue.
- **Rationale** : principe IV (état persisté + rattrapage planifié) et principe II (unicités en base, verrou serveur). Même schéma que l'outbox de la 003 (`SendTransmissionJob`, `billing:reconcile`).
- **Limite assumée** : si le serveur mail accepte le message et que la transaction échoue juste après (panne entre l'acceptation SMTP et le commit), la relance renverra l'e-mail. Aucun service d'envoi n'offre de clé d'idempotence SMTP ; la fenêtre est de l'ordre de la milliseconde et l'effet (un e-mail en double) est sans conséquence financière.

## C6 — La garde de sortie dans la transaction de la 001

- **Decision** : `CertificateDeliveredGuard` implémente `ReservationTransitionGuard` et est enregistré dans `ReservationTransitionGuards` par le service provider. `beforeDeparture()` : si la mise en service n'est pas atteinte ou si la machine n'est pas soumise à VGP, rien ; sinon il lit l'attestation de la réservation **avec `lockForUpdate()`** (on est dans la transaction de `DepartReservation`, réservation verrouillée). Attestation absente (événement pas encore traité) → la sortie est refusée (« envoi en attente ») sans rien créer : le refus annule la transaction de sortie, et l'ouverture revient au listener et au rattrapage de la minute suivante ; attestation non livrée → `CertificateNotDeliveredException::because($status)` (sous-classe `final` de `RefusalException`, message traduit selon le motif). `beforeReturn()` : rien.
- **Rationale** : FR-011 à FR-013 ; le verrou sérialise la sortie avec l'envoi et la saisie d'e-mail (US3, scénario 6). La garde s'ajoute aux autres sans les connaître ; l'ordre d'appel est celui du registre, et le premier refus est affiché.
- **Alternatives considered** : vérifier dans la section du détail seulement — interdit (le refus serveur est la seule garantie).

## C7 — Classer les échecs d'envoi

- **Decision** : `DispatchFailureClassifier` traduit l'exception levée par le mailer en `DispatchFailureReason` :
  - adresse syntaxiquement invalide (`Symfony\Component\Mime\Exception\RfcComplianceException`) → `invalid_address`, définitif ;
  - refus du destinataire par le serveur SMTP avec un code 5xx (`Symfony\Component\Mailer\Exception\TransportExceptionInterface` dont le code ou le message porte 550, 551, 553, 554) → `recipient_rejected`, définitif ;
  - toute autre erreur de transport (connexion, délai, 4xx) → `mail_service_unavailable`, temporaire.
  Le classement se fait dans `rescue()`, qui relance toute exception qui n'est pas une erreur de transport du mailer.
- **Tests** : un transport mail factice, enregistré par les tests sous un nom dédié, simule « indisponible » ou « destinataire refusé » ; `Mail::fake()` / `Notification::fake()` pour les envois réussis. En local, Mailpit (déjà dans `compose.yaml`) reçoit les e-mails.
- **Rationale** : principe IV — le service d'envoi est un système externe ; le mailer de Laravel est déjà l'interface, avec ses transports factices (`array`, `log`) et `Mail::fake()`. Ajouter un port maison autour du mailer serait de la factorisation superficielle.
- **Hors périmètre** : les rebonds reçus après l'envoi (assumption de la spec).

## C8 — Mise en service et rattrapage

- **Decision** : `certification.go_live_date` (variable `CERTIFICATION_GO_LIVE_DATE`, date à l'heure de Paris), lue par `CertificationCalendar`. Avant cette date, le dépôt des rapports est disponible, mais aucune attestation n'est ouverte et la garde de sortie est inactive : les agences déposent les rapports avant la bascule. À partir de cette date, `certification:reconcile` ouvre une attestation pour **toute** réservation confirmée d'une machine soumise à VGP qui n'en a pas — les réservations déjà confirmées à la mise en service (clarification Q2) comme celles dont l'événement aurait été perdu. Sans date configurée, `MissingGoLiveDateException` en console, et la feature reste inactive.
- **Rationale** : un seul mécanisme, idempotent, sans commande jetable ; même principe que `billing.go_live_date` de la 003. La requête est un `whereNotExists` en SQL, traitée par lots (`chunkById`), sans requête dans une boucle (l'ouverture de chaque attestation est une écriture par réservation, comme dans `billing:reconcile`).
- **Alternatives considered** : commande unique lancée à la mise en service — interdite (`laravel:no-throwaway-commands`) et fragile si elle est oubliée.

## C9 — Modifier l'e-mail du client : point d'extension dans `booking`

- **Decision** : `booking` expose `UpdateCustomer::changeEmail()` (e-mail obligatoire et valide, journal d'activité sur le client) et émet `CustomerChanged` (`ShouldDispatchAfterCommit`). La section attestation du détail de réservation appelle cette action ; `certification` écoute `CustomerChanged` et résout les attestations `AwaitingEmail` des réservations confirmées de ce client, ainsi que les attestations `Failed` dont le dernier envoi raté visait une autre adresse que l'e-mail actuel (elles passent `Pending` et partent). Un `CustomerChanged` qui ne touche pas l'e-mail (type de client de la 004, grand compte de la 006) ne relance donc pas un échec définitif vers la même adresse.
- **Rationale** : `customers` appartient à `booking` ; `certification` ne peut pas y écrire (principe I). Modifier l'e-mail d'un client est une capacité de `booking`, utile au-delà de cette feature.
- **Arbitrage** : livré par cette feature en phase 0, dans `booking`, fichiers nouveaux uniquement. Nom générique : `UpdateCustomer` (méthode `changeEmail()`), pour que les features 004 (type de client) et 006 (grands comptes) y ajoutent leurs méthodes et émettent le même `CustomerChanged`. À la date du plan, ni la 004 ni la 006 n'ont de plan ; leurs noms sont à revérifier avant l'implémentation.

## C10 — Disponibilité de la sortie agrégée par section

- **Decision demandée à la 001** : `ReservationDetail` garde `readinessBySteps[step][section] = bool` ; l'événement `reservation-transition-readiness` porte `step`, `section` et `is_ready` ; l'étape est prête quand toutes les sections qui se sont prononcées pour cette étape le sont (sans section enregistrée, les boutons restent actifs, comme aujourd'hui).
- **Rationale** : avec les photos (002), la caution (004) et l'attestation (005), le booléen unique actuel est écrasé par la dernière section qui parle ; le bouton peut s'activer alors qu'une autre condition bloque. Le refus serveur reste correct, mais l'interface ment.
- **Arbitrage** : livré par la 001 dans son point d'extension (forme exacte transmise par la session de coordination) ; les sections de la 002 et de la 004 passent aussi `section`.
- **Repli** : tant que la 001 n'agrège pas, `ReservationCertificateSection` n'émet pas de disponibilité ; elle affiche le blocage, et la garde refuse côté serveur avec le motif.

## C11 — Permission

- **Decision** : `certification.manage` (déposer un rapport, renvoyer, enregistrer une remise, corriger l'e-mail depuis la section, voir la liste à traiter et télécharger un rapport), déclarée dans `CertificationPermissionSeeder` et attribuée au rôle salarié (`PermissionSeeder::EMPLOYEE_ROLE`), comme `billing.manage`.
- **Rationale** : principe V ; la spec ne prévoit pas de droits différenciés.

## C12 — Mise à jour à l'écran

- **Decision** : `CertificateChanged` (`ShouldBroadcast`, `ShouldDispatchAfterCommit`) diffusé sur le canal privé `fleet` avec une charge utile explicite (`reservation_id`, `status`, `delivered_at`) à chaque changement d'état ; la section du détail et le bandeau d'alerte se rafraîchissent à sa réception.
- **Rationale** : contrainte technique de la constitution (Soketi, canaux privés, charge utile explicite) ; le salarié voit l'attestation passer « envoyée » sans recharger, ce qui compte au comptoir.
