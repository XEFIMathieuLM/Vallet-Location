# Vallet Location Constitution

## Core Principles

### I. Architecture en layers OSDD

- Le code métier vit dans des layers OSDD (`xefi/laravel-osdd`) sous `functional/<layer>/`.
  `app/` ne contient que la colle : utilisateurs, authentification, layout, navigation.
- Un nouveau domaine métier DOIT être un nouveau layer, généré avec les commandes `osdd:*`.
- Le sens des dépendances entre layers est fixé et DOIT être respecté
  (aujourd'hui `billing → inspection → booking → fleet`). Aucun layer n'importe une classe
  d'un layer qui dépend de lui, ni n'en déclare une relation Eloquent.
- Quand un layer inférieur doit laisser un layer supérieur agir sur ses écrans ou ses
  transitions, il expose un point d'extension générique (registre, événement, guard) que le
  layer supérieur remplit depuis son service provider. Un layer ne modifie jamais les
  fichiers ni les tables d'un autre layer.

**Rationale** : chaque domaine reste testable et livrable seul ; une feature s'ajoute sans
toucher au code des précédentes.

### II. Garanties portées par la base et le serveur

- Toute règle dont la violation coûte de l'argent ou crée une incohérence (chevauchement,
  doublon, unicité, état final) DOIT être garantie par la base (contrainte d'exclusion,
  index unique, CHECK) ou par le serveur dans une transaction avec verrou
  (`lockForUpdate()`), jamais seulement par l'interface ou par la documentation.
- Un contrôle côté interface (bouton désactivé, message) est un confort ; le refus serveur
  reste la seule garantie.
- Aucune suppression en cascade en base : les clés étrangères sont déclarées sans
  `cascadeOnDelete()`.
- Les agrégats (comptes, sommes) sont calculés en base, pas en PHP ; aucune requête dans une
  boucle.

**Rationale** : deux salariés de deux agences agissent en même temps ; seule la base voit les
deux requêtes.

### III. Cycles de vie explicites

- Un statut est une colonne texte castée en enum PHP backed ; jamais d'enum en base.
- Un cycle de vie à plusieurs états avec des transitions interdites DOIT suivre le pattern
  State : une classe par état, et une exception typée pour toute transition illégale.
  Un état dérivé ou à une seule transition n'en a pas besoin, et le plan le justifie.
- Les dates métier se calculent en heure de Paris (`Europe/Paris`) ; les montants sont des
  entiers en centimes, jamais des flottants.

**Rationale** : les états de réservation, de machine et de transmission portent des règles
métier ; les rendre explicites empêche les transitions silencieuses.

### IV. Effets de bord explicites et erreurs typées

- Pas d'observers Eloquent. Une réaction à un changement passe par un événement explicite et
  un listener, un job en file ou une commande planifiée.
- Pas de `try/catch` : `rescue()` qui relance tout ce qui n'est pas l'exception de domaine
  attendue. Chaque refus métier est une exception typée, jamais un booléen ou un code
  d'erreur.
- Un système externe (logiciel de facturation, stockage, service tiers) DOIT être isolé
  derrière une interface, avec une implémentation factice qui permet de développer et de
  tester toute la feature sans lui.
- Un traitement qui ne doit pas se perdre s'appuie sur un état persisté en base et un
  rattrapage planifié, pas seulement sur un job en file.

**Rationale** : ce qui se passe après une action doit être lisible depuis l'action elle-même,
et une panne ne doit faire disparaître aucune donnée.

### V. Accès par permission

- Tout accès est contrôlé par une permission (`spatie/laravel-permission` et
  `lomkit/laravel-access-control`), jamais par un nom de rôle dans le code.
- Chaque feature déclare ses permissions dans un seeder de son layer et les attribue au rôle
  « salarié » tant que la spec ne prévoit pas de droits différenciés.

**Rationale** : les rôles évoluent avec l'entreprise ; les permissions décrivent ce que le code
autorise.

### VI. Tests par scénario d'acceptation

- Chaque scénario d'acceptation de la spec a un test Feature PHPUnit. Pas de Pest.
- Les transitions d'état et les calculs exécutés en mémoire sont testés en Unit : la classe de
  test étend `PHPUnit\Framework\TestCase`, sans démarrer le framework ni toucher la base.
- Les calculs exécutés en base (requêtes, agrégats, contraintes) sont testés en Feature, sur
  PostgreSQL.
- Dans chaque user story, les tests sont écrits d'abord et échouent avant l'implémentation.
- Les tests utilisent les factories (helper `faker()` de `xefi/faker-php-laravel`), l'horloge
  contrôlée (`travelTo()`) et les implémentations factices ; jamais d'appel réseau réel.
- Larastan avec `xefi/phpstan-xefi-rules` DOIT passer à zéro erreur, et la suite de tests
  DOIT être verte avant chaque commit de phase.

**Rationale** : la spec est le contrat avec le client ; un scénario sans test n'est pas livré.

### VII. Code simple et lisible

- Code en anglais ; tout texte affiché est en français via les fichiers de traduction
  (`functional/<layer>/resources/lang/fr`, `lang/fr` pour `app/`).
- Fichiers de code de moins de 200 lignes ; au-delà, découper en classes dédiées.
- Pas de commentaire de code ; des noms explicites (pas de `$data`, `$value`…), booléens
  préfixés `is_`, `has_` ou `can_`.
- Un package existant et maintenu est préféré à du code maison ; tout nouveau package est
  justifié dans le plan.

**Rationale** : le code est relu par d'autres sessions et d'autres personnes ; il doit se lire
sans son auteur.

## Contraintes techniques

- Stack : Laravel (dernière version stable) avec le starter kit Livewire (Livewire, Flux),
  PostgreSQL, temps réel Soketi sur des canaux privés avec une charge utile explicite.
- Environnement : aucun PHP local ; tout passe par Docker (`compose.yaml` :
  `laravel.test`, `pgsql`, `soketi`, `mailpit`). Commandes :
  `docker compose exec -u sail laravel.test php artisan …` (idem `composer`, `npm`,
  `vendor/bin/phpstan`).
- Production : un worker de file et le planificateur Laravel sont actifs.
- Dépôt unique : l'application Laravel à la racine.

## Workflow de développement

- Chaque feature suit spec-kit dans l'ordre : `/speckit-specify` → `/speckit-clarify` →
  `/speckit-plan` → `/speckit-tasks` → `/speckit-analyze` → `/speckit-implement`.
  Les artefacts vivent dans `specs/<NNN-feature>/`, sur une branche `NNN-feature`.
- Le plan contient un « Constitution Check » qui cite les principes ci-dessus ; une violation
  est justifiée dans « Complexity Tracking » ou corrigée.
- `/speckit-analyze` DOIT être sans problème CRITIQUE, HAUT ou MOYEN avant
  `/speckit-implement`.
- Une feature qui s'appuie sur une autre déclare ses prérequis (phases et éléments attendus)
  dans son `tasks.md` et n'est implémentée qu'une fois ces prérequis commités et sa branche
  mise à jour par-dessus.
- Aucun commit avant validation de chaque phase. Les messages de commit et de PR ne
  mentionnent jamais d'outil d'IA.

## Governance

- Cette constitution prime sur toute autre convention du projet. Le `CLAUDE.md` donne les
  consignes d'exécution courantes ; en cas de contradiction, la constitution l'emporte et le
  `CLAUDE.md` est corrigé.
- Un amendement passe par `/speckit-constitution` sur une branche dédiée, est validé par le
  responsable du projet, puis fusionné dans `main` avant que les features en cours ne s'en
  servent.
- Versionnement sémantique : MAJEURE pour retirer ou redéfinir un principe, MINEURE pour
  ajouter un principe ou une section, CORRECTIVE pour une clarification.
- `/speckit-analyze` traite toute violation de la constitution comme CRITIQUE.

**Version**: 1.0.1 | **Ratified**: 2026-10-10 | **Last Amended**: 2026-10-10
