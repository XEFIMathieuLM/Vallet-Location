# Quickstart : vérifier les photos de départ et de retour

Guide de vérification de bout en bout. Les règles sont dans [spec.md](spec.md), les écrans dans [contracts/screens.md](contracts/screens.md).

## Prérequis

- La feature 001 implémentée et l'application lancée avec Sail (PostgreSQL, Soketi, worker de file d'attente).
- Les migrations et seeders de la 001 et de la 002 passés : 7 agences, catégories dont « Nacelle », des machines, un salarié par agence, et l'historique de réservations de la 001 (`ReservationSeeder`), complété par l'`InspectionSeeder` : vues personnalisées d'une catégorie, deux locations en cours (dont une avec la prise de photos de retour engagée), trois retours (dégât non traité, dégât traité, sans dégât) et une réservation annulée pendant la prise de photos. Les adresses des salariés de démonstration sont affichées à la fin du seeding.
- Un smartphone sur le même réseau que le poste (l'adresse IP du poste suffit : le champ d'envoi avec appareil photo fonctionne en HTTP), ou un tunnel HTTPS vers l'application.
- Un chronomètre pour mesurer SC-003 et SC-004.

```bash
docker compose exec -u sail laravel.test php artisan migrate --seed
```

```bash
docker compose exec -u sail laravel.test php artisan queue:work
```

## Tests automatisés

```bash
docker compose exec -u sail laravel.test php artisan test functional/inspection
```

```bash
docker compose exec -u sail laravel.test vendor/bin/phpstan analyse
```

Attendu : tous verts. Chaque scénario d'acceptation de la spec a son test Feature.

## Parcours manuel

1. **Départ bloqué (US1)** : créer une réservation d'une nacelle commençant aujourd'hui, ouvrir son détail. Le panneau « Photos » propose « Lancer les photos de départ » ; le bouton de sortie est désactivé dès l'affichage de la page.
2. Lancer les photos : un QR code et 5 vues « manquantes » s'affichent. **Déclencher le chronomètre (SC-003).** Scanner avec le téléphone : la page affiche la référence, le client, « Départ », 5 vues.
3. Photographier 4 vues : chronométrer le délai entre la prise et l'affichage sur le poste pour chacune (**SC-004 : moins de 5 s**). Le bouton de sortie reste désactivé et indique la vue manquante.
4. Photographier la 5ᵉ : le bouton se débloque. **Arrêter le chronomètre (SC-003 : moins de 3 min).** Enregistrer la sortie. Recharger la page du téléphone : « lien plus valable ».
5. **Retour bloqué (US2)** : lancer les photos de retour, envoyer 4 vues sur 5 ; tenter le retour : refus listant la vue manquante. Envoyer la 5ᵉ, enregistrer le retour.
6. **Dégât (US3)** : ouvrir la comparaison, signaler un dégât sur « gauche » avec un commentaire. Ouvrir `/degats` depuis un autre poste : la réservation y apparaît sans rechargement. Marquer le dégât traité : elle disparaît.
7. **Vues (US4)** : depuis `/vues-photos`, ajouter « Godet » à la catégorie « Mini-pelle », lancer les photos de départ d'une mini-pelle : 6 vues requises.
8. **Régénération** : lancer un QR code, en régénérer un second ; scanner le premier : « lien plus valable ».

## Rétention

```bash
docker compose exec -u sail laravel.test php artisan model:prune --pretend --model='Functional\Inspection\Models\Photo' --model='Functional\Inspection\Models\PhotoSession'
```

Attendu : seules les photos de réservations clôturées depuis plus d'un an sans dégât en cours, et les sessions expirées ou révoquées depuis plus de 30 jours sans photo, sont listées. Sans `--model`, la commande ne voit que les modèles de `app/Models`.
