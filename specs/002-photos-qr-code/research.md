# Research: Photos de départ et de retour via QR code

Décisions techniques prises pour [plan.md](plan.md). Elles prolongent celles de la feature 001 ([research.md](../001-reservation-machines/research.md)) sans les remettre en cause : Laravel + Livewire, OSDD, PostgreSQL, Soketi, pattern State, Sail.

## P1 — Un nouveau layer `inspection`

- **Decision**: un layer OSDD `inspection` porte les vues requises, les sessions de prise de photos, les photos et les dégâts. Il dépend de `booking` et de `fleet` ; ni l'un ni l'autre ne dépend de lui.
- **Rationale**: la 001 avait prévu que chaque spec suivante devienne un layer. Les photos sont un domaine à part (preuve d'état, dégâts, rétention) qui ne doit pas alourdir `booking`.
- **Alternatives considered**: ajouter les photos dans `booking` (le layer grossit à chaque spec, et la facturation des dégâts y entrerait aussi).

## P2 — Bloquer la sortie et la clôture sans que `booking` connaisse `inspection`

- **Decision**: `booking` expose un **point d'extension** : une interface `ReservationTransitionGuard` (méthodes `beforeDeparture(Reservation)` et `beforeReturn(Reservation)`, qui lèvent une exception typée en cas de refus). Les actions `DepartReservation` et `ReturnReservation` appellent tous les guards enregistrés, dans leur transaction, avant de changer d'état. `inspection` enregistre son guard `PhotosCompleteGuard` dans son service provider.
- **Rationale**: FR-016 / FR-017 exigent un blocage côté serveur, pas seulement un bouton grisé. Le point d'extension garde le sens de dépendance `inspection → booking`. Il servira aussi à la spec caution (« caution encaissée avant le départ »).
- **Alternatives considered**: appeler `inspection` depuis `booking` (dépendance inversée, `booking` ne peut plus être testé seul) ; écouter un événement « avant sortie » (un listener ne peut pas annuler proprement une transition, et le refus doit remonter avec la liste des vues manquantes).
- **Impact sur la 001**: ajout de l'interface, du registre et de l'appel dans deux actions. À faire une fois le code de la 001 commité.

## P3 — Afficher le panneau photos dans le détail de réservation

- **Decision**: même principe : `booking` expose un registre de **sections du détail de réservation** (nom d'un composant Livewire + ordre). Le détail rend les sections enregistrées en leur passant la réservation. `inspection` y enregistre son panneau « Photos ».
- **Rationale**: le salarié reste sur l'écran où il enregistre la sortie et le retour ; le panneau photos se met à jour en direct à côté du bouton bloqué. Pour griser les boutons, une section envoie un événement Livewire générique `reservation-transition-readiness` `{ step, is_ready }`, dès son chargement puis à chaque changement ; le détail ne connaît pas `inspection`, et un bouton reste désactivé tant qu'une section enregistrée n'a pas répondu.
- **Alternatives considered**: un écran photos séparé (un aller-retour de plus pour le salarié, et `booking` devrait quand même connaître son URL).

## P4 — Le lien du QR code

- **Decision**: une table `photo_sessions` porte un **jeton aléatoire de 40 caractères**, stocké haché (SHA-256), avec `expires_at` (création + 30 min) et `revoked_at`. Le QR code encode `https://<app>/photos/{jeton}`. La route est publique, limitée en débit par IP, et la page renvoie `noindex`. Le jeton est révoqué quand l'étape est validée, quand la réservation est annulée ou quand un nouveau QR code est généré pour la même réservation et la même étape.
- **Rationale**: FR-005 exige une invalidation avant expiration (régénération, validation), ce qu'une URL signée Laravel seule ne permet pas. Le hachage empêche de reconstituer un lien valide à partir d'une copie de la base (FR-008).
- **Page publique Livewire**: le composant ne garde que le jeton comme propriété publique, verrouillée (`#[Locked]`) ; il retrouve la session à chaque action et n'accepte d'identifiant de vue ou de photo que s'il appartient à la réservation et à l'étape de cette session.
- **Alternatives considered**: `URL::temporarySignedRoute` (pas révocable avant l'échéance) ; connexion du salarié sur le téléphone (écarté en prise de besoin).

## P5 — Génération du QR code

- **Decision**: `bacon/bacon-qr-code`, rendu SVG inline dans le composant Livewire.
- **Rationale**: déjà installé par le starter kit (Fortify l'utilise pour la double authentification). Aucun package supplémentaire.
- **Alternatives considered**: `simplesoftwareio/simple-qrcode` (surcouche du même moteur, peu maintenue) ; génération côté navigateur en JavaScript (une dépendance front de plus).

## P6 — Prise et envoi des photos depuis le téléphone

- **Decision**: une page Livewire publique, optimisée mobile, avec un champ `<input type="file" accept="image/*" capture="environment">` par vue. Avant l'envoi, un petit script Alpine **réduit la photo dans le navigateur** (côté le plus long à 2 560 px, JPEG qualité 0,85) puis l'envoie par l'upload Livewire. Le serveur valide le type (JPEG, PNG, WebP) et la taille (15 Mo au plus). La limite d'envoi temporaire de Livewire (12 Mo par défaut) et les limites PHP (`upload_max_filesize`, `post_max_size`) sont relevées à 15 Mo, pour qu'une photo non réduite (script en échec) passe quand même.
- **Rationale**: une photo brute de smartphone pèse 4 à 10 Mo ; réduite, elle pèse environ 600 Ko, ce qui tient SC-004 (moins de 5 s) sur un réseau mobile ordinaire. La conversion en JPEG par le navigateur règle aussi le format HEIC des iPhone. Un échec d'envoi est affiché sur la vue avec un bouton « réessayer » (FR-015).
- **Alternatives considered**: envoi de l'original (trop lent en 4G) ; application native (hors périmètre).

## P7 — Stockage des fichiers

- **Decision**: `spatie/laravel-medialibrary` sur le modèle `Photo` (un fichier par photo), disque privé `photos` : disque local en développement, stockage S3-compatible en production. Deux conversions : `thumb` (400 px, pour le panneau et le téléphone) **immédiate**, pour qu'elle existe déjà quand le poste reçoit l'événement « photo reçue » (SC-004) ; `display` (1 600 px, pour la comparaison) en file d'attente, l'original servant en attendant. Les images sont servies par une route authentifiée (poste) ou une URL temporaire signée (téléphone, ses propres photos seulement).
- **Rationale**: package recommandé Xefi pour les médias ; il gère les conversions et supprime les fichiers quand le modèle est supprimé, ce dont dépend la rétention (P9).
- **Alternatives considered**: `Storage::put` à la main (conversions, nettoyage des fichiers et URL à réécrire).
- **Action infra**: demander un bucket S3-compatible privé avant la mise en production.

## P8 — Temps réel

- **Decision**: réutiliser Soketi (R4 de la 001). Nouveau canal privé `reservation.{id}`, autorisé pour la permission `reservations.manage`. Événement `photo.changed` à chaque réception ou suppression de photo ; le panneau photos du poste l'écoute et se recharge. Les listes « dégâts à traiter » écoutent `damage.changed` sur le canal `fleet` existant.
- **Rationale**: FR-012 et SC-004. Un canal par réservation évite d'envoyer chaque photo aux 85 postes.
- **Alternatives considered**: `wire:poll` sur le panneau (latence fixe, charge inutile).

## P9 — Rétention

- **Decision**: le modèle `Photo` utilise le trait `Prunable` (et non `MassPrunable`, pour que la médiathèque supprime les fichiers). La requête de purge retient les photos dont la réservation est clôturée depuis plus d'un an et n'a aucun dégât non traité ni dégât traité depuis moins d'un an. Les photos d'une réservation annulée sont purgées un an après leur réception. `model:prune` tourne chaque nuit.
- **Rationale**: FR-023. `Prunable` est la convention Xefi pour la rétention.
- **Alternatives considered**: commande artisan dédiée (réinvente `model:prune`).

## P10 — Vues requises et figement

- **Decision**: les vues par catégorie vivent dans une table `category_views` (catégorie, libellé, ordre). La liste par défaut vit dans la configuration du layer (`inspection.default_views`). Au lancement de la première session d'une réservation, la liste en vigueur est **copiée** dans `reservation_views` ; les photos et les dégâts pointent vers ces lignes copiées.
- **Rationale**: FR-003 (le retour exige les vues du départ, même si la catégorie a changé entre-temps). La copie rend la règle impossible à contourner sans dépendre de l'historique.
- **Alternatives considered**: versionner la liste par catégorie (plus complexe pour le même résultat).

## P11 — Dégâts et indicateur « à refacturer »

- **Decision**: une table `damages` avec `resolved_at` / `resolved_by` nullables. L'indicateur « à refacturer » n'est **pas stocké** : c'est l'existence d'au moins un dégât non traité, calculée en base (`whereHas`).
- **Rationale**: un indicateur stocké peut se désynchroniser du détail des dégâts. Un dégât n'a que deux états et une transition : un pattern State serait disproportionné.
- **Alternatives considered**: colonne `to_reinvoice` sur la réservation (modifie la table de `booking` et peut diverger).

## P12 — Droits

- **Decision**: deux nouvelles permissions sur le rôle « salarié » : `damages.manage` (signaler, traiter) et `inspection_views.manage` (paramétrer les vues). La prise de photos relève de `reservations.manage` (existante).
- **Rationale**: même logique que R6 de la 001 : des permissions dès le départ, pour que les rôles différenciés n'aient pas à toucher le code.
