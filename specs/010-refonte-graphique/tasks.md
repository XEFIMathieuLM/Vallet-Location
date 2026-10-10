---

description: "Task list for the full visual redesign (feature 010)"
---

# Tasks: Refonte graphique

**Input**: Design documents from `specs/010-refonte-graphique/`

**Prerequisites**: plan.md, spec.md, research.md (R1 à R13), data-model.md, contracts/ (design-tokens, ui-components, screens), quickstart.md, maquettes validées (1 v3, 2 pour le planning, 3, pictogrammes)

**Tests** : obligatoires (constitution, principe VI).
- **Feature** (PHPUnit, PostgreSQL) : présence, ordre, liens et permissions des éléments ; au plus un bouton jaune hors modale (`tests/Concerns/InspectsPrimaryActions`) ; logotype et absence de la marque de la référence.
- **Unit** (`PHPUnit\Framework\TestCase`, sans framework) : `MachineFamily`, `PlanningCellKind::cssClasses()`, contrastes des jetons.
- **Ordre** : dans chaque phase, les tests sont écrits d'abord et échouent avant l'implémentation.
- **Captures** : les scénarios purement visuels (couleurs rendues, défilement horizontal à 375 px) sont vérifiés par les captures du [quickstart](quickstart.md), en 1440 px et en 375 px, à la fin de chaque phase d'écran (plan, Complexity Tracking).
- **Tests existants** : aucun ne doit casser. Une assertion purement visuelle adaptée est listée dans le message de commit de sa phase (SC-005).

**Organization**: tâches regroupées par user story ; chemins depuis la racine du dépôt (dépôt unique).

## Format: `[ID] [P?] [Story] Description`

- **[P]** : parallélisable (fichiers différents, aucune dépendance sur une tâche non terminée)
- **[Story]** : user story de la spec (US1 à US7)

## Prérequis

| Prérequis | Éléments attendus | État |
|---|---|---|
| 001 à 009 | écrans, layouts et composants listés dans [contracts/screens.md](contracts/screens.md) ; `data-counter` / `data-highlighted` du tableau de bord (008) ; `x-page-heading`, `x-section-heading`, `x-empty-state`, `x-loading-hint` | dans `main` |

- Base : `origin/main` à `72ce925`. Aucune branche à attendre.
- Environnement : projet Compose `vallet-010` sur le port 8110 avec les données du client chargées ([quickstart.md](quickstart.md)). L'environnement 8080 n'est pas touché.
- **Fin de chaque phase** :
  - `docker compose exec -u sail laravel.test composer ci:check` doit se terminer avec le code 0 (après `vendor/bin/phpstan clear-result-cache`), puis commit sans push et sans mention d'IA ;
  - ensuite, `npm run build` et les captures de la phase.

---

## Phase 1: Setup

**Purpose** : police, fichiers de jetons, bouton Flux publié, outil de capture.

- [X] T001 Copier la référence dans `specs/010-refonte-graphique/design-reference.md`, l'outil de capture dans `specs/010-refonte-graphique/outils/capture.mjs`, et produire les 38 captures avant dans `specs/010-refonte-graphique/captures/avant/` (fait pendant la spécification).
- [X] T002 Remplacer `bunny('Instrument Sans', { weights: [400, 500, 600] })` par `bunny('Inter Tight', { weights: [400, 600, 700, 800] })` dans `vite.config.js` (R1).
- [X] T003 Créer `resources/css/vallet/tokens.css`, `resources/css/vallet/components.css` et `resources/css/vallet/dark.css` (vides pour l'instant). Les importer depuis `resources/css/app.css` après `flux.css`. Retirer de `app.css` le bloc `@theme` des `zinc`, de l'accent et d'Instrument Sans, et le bloc `.dark` de l'accent, qui passent dans les nouveaux fichiers.
- [X] T004 Publier le bouton Flux : `docker compose exec -u sail laravel.test php artisan flux:publish button` → `resources/views/flux/button/index.blade.php` (copie conforme, aucune modification à cette étape).

**Checkpoint** : `npm run build` passe, l'outil s'affiche comme avant (même rendu), `composer ci:check` vert.

---

## Phase 2: Foundational (prérequis bloquants)

**Purpose** : outils de test et pictogrammes, utilisés par US1 à US5.

- [X] T005 [P] Créer le trait `tests/Concerns/InspectsPrimaryActions.php` avec `assertAtMostOnePrimaryAction(string $html): void`. Il charge le HTML dans `DOMDocument` (erreurs libxml ignorées), compte par XPath les éléments `[@data-flux-button][@data-variant="primary"][not(ancestor::ui-modal)]` et échoue au-delà de 1 avec la liste des libellés trouvés (contracts/ui-components.md, Vérification).
- [X] T006 [P] Écrire `functional/fleet/tests/Unit/MachineFamilyTest.php` (échoue d'abord). Cas attendus :
  - « Nacelle 12 m », « Nacelle 20 m » → `Nacelle` ;
  - « Mini-pelle 1.8 t », « Minipelle », « Pelle 8 t » → `MiniExcavator` ;
  - « Compacteur », « Rouleau tandem », « Plaque vibrante » → `Compactor` ;
  - « Échafaudage 40 m² » → `Scaffolding` ;
  - « Groupe électrogène », chaîne vide → `Neutral` ;
  - `pictogramView()` renvoie `fleet::pictograms.<famille>`.
- [X] T007 Créer l'enum `functional/fleet/src/Enums/MachineFamily.php` : cas `Nacelle = 'nacelle'`, `MiniExcavator = 'mini_excavator'`, `Compactor = 'compactor'`, `Scaffolding = 'scaffolding'`, `Neutral = 'neutral'`.
  - `fromCategoryName(string $categoryName): self` : nom normalisé avec `Str::ascii()` et `Str::lower()`, mots-clés de data-model.md testés dans l'ordre du tableau, `Neutral` par défaut.
  - `pictogramView(): string`.
  - T006 passe.
- [X] T008 [P] Créer les pictogrammes `functional/fleet/resources/views/pictograms/nacelle.blade.php`, `mini-pelle.blade.php`, `compacteur.blade.php`, `echafaudage.blade.php` (SVG de `specs/010-refonte-graphique/maquettes/illustrations/`) et `neutral.blade.php` (caisse géométrique, même style, viewBox `0 0 240 160`). Tous en `fill="currentColor"`, sans `width` ni `height` fixes ; ils reçoivent `$attributes` du composant.
- [X] T009 Créer le composant anonyme `functional/fleet/resources/views/components/pictogram.blade.php` (`<x-fleet::pictogram :category="…" class="…" />`). Il inclut la vue de `MachineFamily::fromCategoryName($category)` avec `aria-hidden="true"`, `focusable="false"` et les attributs reçus. Vérifier qu'il se résout par le `loadViewsFrom(…, 'fleet')` existant de `FleetServiceProvider`.

**Checkpoint** : T006 vert ; `<x-fleet::pictogram category="Nacelle 12 m" class="size-10" />` rend un SVG.

---

## Phase 3: User Story 1 - Un langage visuel unique (Priority: P1) 🎯 MVP

**Goal** : tous les écrans prennent les fondations d'un coup : police, palette, accent, géométrie, composants Flux, composants partagés, thème sombre.

**Independent Test** : ouvrir réservations, transmissions et cautions. On y voit la même police, les mêmes boutons et champs à bordure basse, des badges en pastille, et au plus un bouton jaune par écran (spec US1).

### Tests for User Story 1

- [X] T010 [P] [US1] Écrire `tests/Unit/Design/DesignTokensContrastTest.php` (échoue d'abord : `tokens.css` vide). Il lit `resources/css/vallet/tokens.css`, en extrait les valeurs hexadécimales des jetons par nom, calcule le contraste WCAG et vérifie chaque couple du tableau « Couples vérifiés » de `contracts/design-tokens.md`, y compris le garde-fou « jaune sur blanc < 3 ». Les mélanges à x % se font en sRGB sur le fond donné.
- [X] T011 [P] [US1] Écrire `tests/Feature/Design/ButtonVariantsTest.php`. Rendu Blade de `<flux:button variant="primary">`, `filled`, `outline`, `danger` et `ghost` :
  - chacun porte `data-variant="<variante>"` ;
  - aucun ne contient `shadow-` ;
  - la chaîne `--color-accent` (fond) et la chaîne `--color-accent-foreground` (texte) n'apparaissent que dans le `primary`.

### Implementation for User Story 1

- [X] T012 [US1] Remplir `resources/css/vallet/tokens.css` (`@theme`) selon `contracts/design-tokens.md` :
  - `--font-sans` Inter Tight ;
  - accent, accent-pressed, accent-foreground, accent-content ;
  - gamme `zinc` et alias `neutral` ;
  - gammes `red`, `green`, `amber`, `blue` ; alias `yellow`, `orange`, `lime`, `emerald`, `sky`, `indigo`, `rose` ;
  - `--radius-xs: 2px`.

  T010 passe.
- [X] T013 [US1] Remplir `resources/css/vallet/components.css` (R3, R9) :
  - utilitaires `.display-xl`, `.display-lg`, `.display-md`, `.heading-lg`, `.heading-md`, `.heading-sm`, `.eyebrow` (`@utility`, valeurs de design-tokens.md, réductions sous 640 px) ;
  - `[data-flux-heading]` en 700 / 0,95 ;
  - `[data-flux-control]` : bordure basse 1 px `zinc-950` (blanche sous `.dark`), autres bordures 0, rayon 0, sans ombre, fond transparent ; focus : bordure basse 2 px. Remplace la règle de focus actuelle en anneau (`ring-accent`) ;
  - `[data-flux-card]`, `[data-flux-callout]` (filet gauche 4 px selon la variante, via les couleurs de texte déjà posées par Flux : `border-inline-start-color: currentColor`), `[data-flux-badge]` (pastille, 600), `[data-flux-table]` et `[data-flux-column]` (surtitre, bord haut 2 px, filets), `ui-modal [data-flux-modal-content]`, `[data-flux-menu]`, `[data-flux-tooltip]`, `[data-flux-toast]`, cartes radio (rayon 0) ;
  - `[data-flux-sidebar-item]` dans `.dark` (blanc à 80 %, survol `zinc-900`, `[data-current]` fond blanc, texte noir, 700) ;
  - `[data-flux-navbar-item][data-current]` souligné 2 px ;
  - `:focus-visible { outline: 2px solid currentColor; outline-offset: 2px }`.
- [X] T014 [US1] Modifier `resources/views/flux/button/index.blade.php` (R3) :
  - `primary` : `bg-(--color-accent) hover:bg-(--color-accent-pressed) active:bg-(--color-accent-pressed) text-(--color-accent-foreground)` ;
  - `filled` : `bg-black text-white hover:bg-zinc-800`, inversé sous `dark:` ;
  - `outline` : `bg-white border border-black hover:bg-zinc-50`, sous `dark:` fond noir et contour blanc ;
  - `danger` : `bg-red-600 hover:bg-red-700 text-white` ;
  - `ghost` / `subtle` : sans fond, soulignement au survol ;
  - pour toutes : ombres retirées, `rounded-[2px]` à la place de `rounded-lg` / `rounded-md`, `font-bold tracking-[0.01em]`, `max-sm:h-11` pour `base` ;
  - attribut `data-variant="{{ $variant }}"` sur `flux:button-or-link-pure`.

  T011 passe.
- [X] T015 [US1] Remplir `resources/css/vallet/dark.css` (R11) : sous `.dark`, `--color-accent-content: #fff`, fonds de page `#000`, surfaces `zinc-900`, séparateurs `white/16`, cartes et champs ; `[data-flux-control]` à bordure basse blanche. Dans `resources/views/layouts/app.blade.php` et `layouts/app/sidebar.blade.php`, le `body` passe de `dark:bg-zinc-800` à `dark:bg-black`.
- [X] T016 [P] [US1] Refaire `resources/views/components/page-heading.blade.php` : prop facultative `eyebrow` ; `h1` en `.display-md lg:display-lg`, 700 ; `data-flux-heading` conservé ; actions à droite, passage à la ligne. Props et slot existants inchangés.
- [X] T017 [P] [US1] Refaire `resources/views/components/section-heading.blade.php` (niveaux 2 / 3 / 4 → `.heading-lg` / `.heading-md` / `.heading-sm`) et `resources/views/components/empty-state.blade.php` (bloc `bg-zinc-50` à angle droit, sans pointillés, `dark:bg-zinc-900`). Props inchangées.
- [X] T018 [P] [US1] Créer `resources/views/components/tile.blade.php` (`x-tile`, props `variant` = `light|soft|dark|accent`, défaut `soft`, et `href` facultatif) selon contracts/ui-components.md. Rendu en `<a>` si `href` est donné, en `<div>` sinon ; `focus-visible` hérité.
- [X] T019 [US1] `npm run build`, captures `05-reservations`, `07-transmissions` et `08-cautions` (bureau et téléphone) ; vérifier l'Independent Test. `composer ci:check` code 0, puis commit « Refonte graphique : fondations (police, jetons, composants) ».

**Checkpoint** : US1 livrable seule. Tous les écrans ont la nouvelle apparence de base.

---

## Phase 4: User Story 2 - Navigation salariés et identité (Priority: P1)

**Goal** : colonne noire, barre de 60 px, logotype « Vallet Location », menu mobile, favicon.

**Independent Test** : avec un salarié qui a toutes les permissions, chaque entrée mène au même écran qu'avant, l'entrée courante est en blanc inversé, et le compteur des demandes est visible. Sans permission de facturation, les entrées de facturation sont absentes. À 375 px, le menu s'ouvre et donne accès à tout (spec US2).

### Tests for User Story 2

- [X] T020 [P] [US2] Écrire `tests/Feature/Design/StaffNavigationTest.php` :
  - (1) salarié avec toutes les permissions : `get(route('dashboard'))` contient les 20 liens de la colonne (routes de `layouts/app/sidebar.blade.php`), dans l'ordre des groupes ;
  - (2) sur `route('reservations.index')`, l'élément `[data-flux-sidebar-item][data-current]` pointe vers `reservations.index` ;
  - (3) un salarié sans `BillingPermission::Manage` ne voit ni `billing.transmissions`, ni `billing.exports`, ni `billing.statement` ;
  - (4) le composant `portal.pending-requests-badge` est rendu pour `PortalPermission::HandleRequests` ;
  - (5) la page contient le logotype (`aria-label` « Vallet Location ») et `data-test="sidebar-menu-button"`.
- [X] T021 [P] [US2] Écrire `tests/Feature/Design/TopBarTest.php` : la barre de 60 px affiche le titre de la page (`#[Title]` du composant) et la date du jour en heure de Paris (`travelTo('2026-10-10 08:00')` → « samedi 10 octobre 2026 »), et contient la zone `id="top-bar-actions"`.

### Implementation for User Story 2

- [X] T022 [P] [US2] Refaire `resources/views/components/app-logo.blade.php` : logotype « VALLET » + pavé « LOCATION », prop `inverse`, mode `sidebar` conservé (`flux:sidebar.brand` / `flux:brand` avec le logotype en slot `logo`, sans nom dupliqué), `aria-label` = `config('app.name')`. Refaire `resources/views/components/app-logo-icon.blade.php` en monogramme carré « V » (SVG, `currentColor`).
- [X] T023 [P] [US2] Remplacer `public/favicon.svg` par le monogramme (carré noir, « V » blanc). Régénérer `public/favicon.ico` (32 px) et `public/apple-touch-icon.png` (180 px) depuis ce SVG, avec la capture Chrome de l'outil, sans nouvelle dépendance.
- [X] T024 [P] [US2] Créer `resources/views/components/top-bar.blade.php` (`x-top-bar`, prop `title`, slot `start`) :
  - 60 px, `border-b border-zinc-100` ;
  - titre en 600, date `now('Europe/Paris')->locale('fr')->isoFormat('dddd D MMMM YYYY')` en `zinc-500` ;
  - slot `start` pour le bouton du menu mobile ;
  - à droite, une zone vide `<div id="top-bar-actions" class="ms-auto flex items-center gap-3"></div>`, destinée aux `@teleport`.
- [X] T025 [US2] Réécrire `resources/views/layouts/app/sidebar.blade.php` (R4) :
  - `flux:sidebar` en `class="dark bg-black border-e border-white/16"`, logotype inversé ;
  - groupes et entrées inchangés (mêmes `@can`, routes, `:current`, badge des demandes) ;
  - titres de groupe en surtitre ;
  - menu personnel en bas (`x-desktop-user-menu` inchangé) ;
  - zone de contenu avec `x-top-bar :title="$title ?? null"` ;
  - sur mobile, la barre porte `flux:sidebar.toggle`, le logotype et le profil (dropdown existant), avec des cibles de 44 px.
- [X] T026 [US2] Ajuster `resources/views/components/desktop-user-menu.blade.php` pour la colonne noire (lisible en `dark`), `data-test` conservé, et le surtitre des groupes (11 px, 700, capitales, blanc à 55 % dans la colonne), posé par `components.css` sur `[data-flux-sidebar-group]` car la colonne utilise `flux:sidebar.group` et non `navlist/group`. Menu personnel mobile extrait dans `resources/views/components/mobile-user-menu.blade.php` (layout sous 200 lignes).
- [X] T027 [US2] Captures du tableau de bord et des réservations en 1440 et 375 px, menu ouvert sur téléphone. `composer ci:check` code 0, puis commit « Refonte graphique : navigation et logotype ».

**Checkpoint** : US1 + US2 : tout l'espace salariés a sa nouvelle structure.

---

## Phase 5: User Story 3 - Tableau de bord en tuiles et en bandes (Priority: P2)

**Goal** : maquette 1 v3, mêmes sections, chiffres et liens que la 008.

**Independent Test** : avec des départs, des retours en retard et des éléments à traiter, chaque section de la 008 est présente avec les mêmes chiffres et liens ; changer d'agence met à jour les sections (spec US3).

### Tests for User Story 3

- [X] T028 [P] [US3] Écrire `tests/Feature/Dashboard/DashboardLayoutTest.php` (`travelTo('2026-10-10 08:00')`, factories de la 008) :
  - (1) 3 départs, 1 retour et 2 retards du jour → le résumé « 3 départs, 1 retour et 2 retours en retard aujourd'hui. » ;
  - (2) la frise affiche autant de colonnes que `config('dashboard.upcoming_departure_days')` (7 : du dimanche 11 au samedi 17 octobre ; avec `config(['dashboard.upcoming_departure_days' => 3])` : 3 colonnes, du 11 au 13), jours vides compris (« Aucun départ » pour lecteur d'écran). Un départ du 14 mène à `reservations.show`, et « Voir tout » apparaît quand `section_limit` est dépassé ;
  - (3) avec au moins un retour en retard, la tuile des retours en retard a `data-tile="accent"` ; sans retour en retard, aucune tuile `accent` ;
  - (4) les `data-counter` / `data-highlighted` de `PendingWork` sont inchangés ;
  - (5) `assertAtMostOnePrimaryAction` sur le rendu complet ;
  - (6) le sélecteur d'agence (`wire:model.live="agency"`) est enveloppé d'un `@teleport('#top-bar-actions')`, et changer d'agence met toujours à jour les sections (test existant `AgencySelectionTest` vert).

  Les tests existants `tests/Feature/Dashboard/*` restent verts.

### Implementation for User Story 3

- [X] T029 [US3] Ajouter les textes de R12 dans `lang/fr/dashboard.php` : `welcome.title`, `welcome.summary` (avec `trans_choice` par total), `upcoming.no_departure`, `fleet.total`.
- [X] T030 [US3] Dans `render()` de `app/Livewire/Dashboard/DayOperations.php`, passer deux nouvelles variables de vue, sans requête supplémentaire :
  - `daySummary` : totaux des départs, retours et retards, déjà calculés par `DashboardSection::$total` ;
  - `upcomingDays` : la liste ordonnée des dates de `$today->addDay()` à `$today->addDays($upcomingDays)` (la plage de `DayOperationsQuery::upcomingDepartures`), chacune avec ses réservations prises dans `upcomingDepartures->items`, regroupées par `start_date`.

  Extraire au besoin une classe `app/Dashboard/UpcomingDays.php` (fabrique statique, testée par T028). Fichiers sous 200 lignes ; T028 (1) et (2) passent.
- [X] T031 [US3] Refaire `resources/views/livewire/dashboard/dashboard.blade.php` :
  - le sélecteur d'agence en pastille, enveloppé dans `@teleport('#top-bar-actions')` ;
  - mêmes `wire:model.live`, `@canany`, `wire:key` et état vide ;
  - grille des sections ;
  - `h1` « Tableau de bord » conservé pour les lecteurs d'écran (`sr-only`) si le bandeau d'accueil n'est pas rendu.
- [X] T032 [US3] Refaire `resources/views/livewire/dashboard/day-operations.blade.php` :
  - bandeau d'accueil (bloc `zinc-50` avec titre `.display-xl` et résumé ; bloc noir illustré par `x-fleet::pictogram` nacelle et mini-pelle sur une ligne de sol) ;
  - quatre `x-tile` (`accent` pour les retours en retard s'il y en a, avec `data-tile`), total en `.display-lg` ;
  - frise de 7 colonnes (empilées sous 640 px).

  Refaire aussi `resources/views/livewire/dashboard/partials/reservation-row.blade.php` (pictogramme 56 px, référence, client, dates, note en badge) et `partials/section-more.blade.php`. Liens et `wire:key` inchangés.
- [X] T033 [P] [US3] Refaire `resources/views/livewire/dashboard/pending-work.blade.php` : bloc noir, grille 3 × 2 (1 colonne sur téléphone) à filets `white/16`. Compteur `isHighlighted()` : nombre dans un carré `bg-accent text-black`. `data-counter` et `data-highlighted` conservés.
- [X] T034 [P] [US3] Refaire `resources/views/livewire/dashboard/fleet-status.blade.php` (barre de répartition aux largeurs proportionnelles, colonnes avec pastille du statut et chiffre en display, liens inchangés, `dashboard.fleet.total`) et `resources/views/livewire/dashboard/vgp-watch.blade.php` (liste à filets, badges inchangés).
- [X] T035 [US3] Captures du tableau de bord en 1440 et 375 px, thèmes clair et sombre, comparées à la maquette 1 v3. `composer ci:check` code 0, puis commit « Refonte graphique : tableau de bord ».

**Checkpoint** : tableau de bord conforme à la maquette validée.

---

## Phase 6: User Story 4 - Espace client à l'allure d'un site public (Priority: P2)

**Goal** : barre blanche, bandeau noir, cartes avec pictogramme, pied de page noir, pages d'authentification client en deux panneaux, thème clair forcé.

**Independent Test** : un client connecté cherche une catégorie disponible. Bandeau, cartes et pied de page s'affichent. Il envoie une demande, l'annule, ouvre ses réservations : tout fonctionne comme en 009 (spec US4).

### Tests for User Story 4

- [X] T036 [P] [US4] Écrire `functional/portal/tests/Feature/PortalLayoutTest.php` (`actingAs($account, 'customer')`) :
  - (1) `portal.search` contient le logotype, les entrées « Rechercher une machine », « Mes demandes », « Mes réservations », le `x-portal::hero` (`data-hero`) et le pied de page (`data-portal-footer`) ;
  - (2) la page ne contient ni `class="dark"` sur `<html>` ni le script d'apparence de Flux ;
  - (3) avec deux machines disponibles, deux cartes portent un pictogramme (`svg[aria-hidden]`) et un bouton `data-variant="filled"`, et `assertAtMostOnePrimaryAction` passe ;
  - (4) `portal.login` contient le logotype et « Espace client ».

  Les tests existants `functional/portal/tests/**` restent verts.

### Implementation for User Story 4

- [X] T037 [US4] Ajouter le paramètre `hasAppearanceChoice` (défaut `true`) à `resources/views/partials/head.blade.php` : `@fluxAppearance` n'est inclus que s'il est vrai.
- [X] T038 [P] [US4] Créer `functional/portal/resources/views/components/hero.blade.php` (`x-portal::hero`, props `title` et `eyebrow` avec repli sur `portal::navigation.space_name`, slot, `data-hero`) : bande noire pleine largeur, conteneur 1440 px, `.display-xl`.
- [X] T039 [US4] Réécrire `functional/portal/resources/views/layouts/portal.blade.php` (R7) :
  - `<html>` sans `dark`, `partials.head` avec `hasAppearanceChoice: false` ;
  - barre de 60 px (logotype, `flux:navbar` existante, profil et menu mobile existants) ;
  - `<main>` pleine largeur ;
  - pied de page noir (`data-portal-footer` : logotype inversé, liens de l'espace, `portal::navigation.footer.copyright`).
- [X] T040 [US4] Réécrire `functional/portal/resources/views/layouts/guest.blade.php` en deux panneaux (panneau noir avec logotype inversé, « Espace client » et pictogramme ; formulaire sur blanc ; bande noire sur téléphone), thème clair forcé.
- [X] T041 [US4] Refaire `functional/portal/resources/views/livewire/customer/search.blade.php` :
  - `x-portal::hero` contenant les quatre filtres (mêmes `wire:model.live`, `min`, libellés), bordure basse blanche sur fond noir, et la mention de prix ;
  - nombre de résultats (`portal::search.results_count`) ;
  - cartes : zone `zinc-50` en 16:9 avec `x-fleet::pictogram :category="$offer->categoryName"`, référence, catégorie et agence, prix indicatif, bouton `filled` « Demander » pleine largeur (même `wire:click`) ;
  - modale inchangée.
- [X] T042 [P] [US4] Refaire `functional/portal/resources/views/livewire/customer/my-requests.blade.php`, `my-reservations.blade.php` et `account-settings.blade.php` avec `x-portal::hero` et des listes à filets. Dans `account-settings`, le second formulaire (mot de passe) passe en `filled` ; actions, `data-test` et textes inchangés.
- [X] T043 [P] [US4] Ajuster les vues `functional/portal/resources/views/livewire/auth/*.blade.php` au layout en deux panneaux (`x-auth-header` partagé), sans changer champs, actions ni `data-test`.
- [X] T044 [US4] Ajouter `navigation.footer.copyright` et `search.results_count` dans `functional/portal/resources/lang/fr/`. Captures 20 à 23 en 1440 et 375 px, aucun `OVERFLOW`. `composer ci:check` code 0, puis commit « Refonte graphique : espace client ».

**Checkpoint** : espace client conforme à la maquette 3.

---

## Phase 7: User Story 5 - Écrans métier conformes (Priority: P2)

**Goal** : passage écran par écran selon [contracts/screens.md](contracts/screens.md) : couleurs codées en dur, un seul bouton jaune, planning, pictogrammes, défilement à 375 px.

**Independent Test** : tous les écrans de « Écrans concernés » à 1440 et 375 px suivent le langage, gardent leurs informations et actions, et aucun ne défile horizontalement (spec US5).

### Tests for User Story 5

- [X] T045 [P] [US5] Écrire `functional/booking/tests/Unit/PlanningCellKindTest.php` (échoue d'abord) : `cssClasses()` de chaque cas égal au tableau de data-model.md ; aucun ne contient `accent`, `yellow` ni `blue`.
- [X] T046 [P] [US5] Écrire `tests/Feature/Design/PrimaryActionPerScreenTest.php`, avec un salarié qui a toutes les permissions et les factories des layers. `assertAtMostOnePrimaryAction` sur le HTML de :
  - `availability.index` (avec résultats), `reservations.index`, `reservations.create`, `reservations.show` (confirmée avec caution à encaisser et bon de commande manquant) ;
  - `planning.index`, `inspection.damages`, `billing.transmissions`, `billing.exports` ;
  - `deposit.pending.index`, `deposit.rates.index`, `sales.index`, `sales.create`, `sales.show` (avec deux offres) ;
  - `certification.machines`, `certification.certificates`, `accounts.key-accounts`, `accounts.missing-purchase-orders` ;
  - `portal.staff.requests` (avec deux demandes), `portal.staff.prices`, `machines.index`, `users.index`, `profile.edit`, `security.edit`.

  Sur `reservations.show`, vérifier aussi par `assertSeeInOrder` que les titres des sections d'extension enregistrées apparaissent dans l'ordre de `ReservationDetailSections` (spec US5, scénario 3).

### Implementation for User Story 5

- [X] T047 [US5] Modifier `functional/booking/src/Planning/PlanningCellKind.php` (`cssClasses()` selon data-model.md). T045 passe.
- [X] T048 [US5] Refaire `functional/booking/resources/views/livewire/planning.blade.php` :
  - légende en cases carrées à lettre ;
  - tableau à bord haut 2 px noir et filets `zinc-100`, colonne de référence collante `bg-white dark:bg-black` ;
  - cases à angle droit ;
  - la grille défile dans sa zone (`overflow-x-auto` sur un conteneur `max-w-full min-w-0`), la page ne défile plus à 375 px (542 px aujourd'hui).
- [X] T049 [P] [US5] Booking :
  - `availability-search.blade.php` : pictogramme dans la colonne référence ; une action par ligne → `filled` ;
  - `reservation-list.blade.php` ;
  - `create-reservation-form.blade.php` : récapitulatif avec pictogramme ;
  - `reservation-detail.blade.php` : grille d'informations à filets, sections en blocs à bord haut 2 px, transition en jaune ;
  - `partials/reservation-status.blade.php` ;
  - remplacer les `text-zinc-500 dark:text-zinc-400` des `dt` par `.eyebrow`.

  Tous dans `functional/booking/resources/views/`.
- [X] T050 [P] [US5] Inspection (`functional/inspection/resources/views/`) : `photos-panel`, `comparison`, `damages-list`, `partials/damage-actions` (« Marquer résolu » → `filled`), `category-views/index` et `edit`, `layouts/phone` (en-tête noir avec logotype inversé, boutons de 48 px), `phone-capture`, `phone/expired` ; photos à angle droit, légendes en surtitre.
- [X] T051 [P] [US5] Billing (`functional/billing/resources/views/`) : `transmissions`, `exports` (déclencheur jaune, confirmation dans la modale), `statement`, `reservation-billing-section`, `damage-billing-actions` (boutons hors modale → `filled`), `billing-alert`, `partials/transmission-status` et `transmission-reason`.
- [X] T052 [P] [US5] Deposit (`functional/deposit/resources/views/`) : `pending-deposits-list`, `deposit-rates-index`, `deposit-rate-form`, `reservation-deposit-section`, `section/*` (déclencheurs hors modale → `filled`, confirmations de modale jaunes), `partials/*`.
- [X] T053 [P] [US5] Sales (`functional/sales/resources/views/`) : `sale-list`, `list-machine-form`, `sale-detail` (un seul jaune hors modale : « Remettre au client »), `sale-offers` (« Accepter » par offre → `filled`), `machine-sale-history`, `partials/listing-fields`.
- [X] T054 [P] [US5] Certification (`functional/certification/resources/views/`) : `vgp-machines`, `machine-vgp-reports`, `certificates-to-handle`, `reservation-certificate-section`, `hand-delivery-button` (hors modale → `filled`), `resend-certificate-button`, `customer-email-form`, `certification-alert`.
- [X] T055 [P] [US5] Accounts et portail salariés :
  - `functional/accounts/resources/views/livewire/` : `key-accounts`, `missing-purchase-orders`, `purchase-order-section` ;
  - `functional/portal/resources/views/livewire/staff/` : `online-requests` (« Confirmer » par ligne → `filled`), `confirm-request-modal`, `refuse-request-modal`, `indicative-prices`, `pending-requests-badge` (pastille jaune à texte noir), `reservation-origin-section`.
- [X] T056 [P] [US5] Fleet (`functional/fleet/resources/views/`) : `machine-index` (pictogramme par ligne, « Nouvelle machine » en jaune), `machine-form`, `import-fleet-form`, `partials/machine-badges`.
- [X] T057 [P] [US5] App : `resources/views/livewire/users/user-index.blade.php` ; `resources/views/pages/settings/layout.blade.php` (sous-navigation en pastilles) ; `profile.blade.php`, `security.blade.php` (un jaune hors modale), `appearance.blade.php`, `delete-user-form.blade.php` ; `resources/views/components/loading-hint.blade.php`, `auth-session-status.blade.php` ; le callout hors ligne de `layouts/app.blade.php`.
- [X] T058 [US5] Rechercher les couleurs codées en dur restantes dans `functional/*/resources/views` et `resources/views` (`grep -rn "bg-\(red\|green\|blue\|amber\|yellow\)-\|text-\(red\|green\)-\|rounded-\(lg\|xl\|md\)\|shadow"`). Les remplacer par les jetons, ou les justifier dans le message de commit. T046 passe.
- [X] T059 [US5] Captures 03 à 15 en 1440 et 375 px, plus planning et détail de réservation en sombre ; aucun `OVERFLOW`. `composer ci:check` code 0, puis commit « Refonte graphique : écrans métier ».

**Checkpoint** : tous les écrans salariés sont conformes.

---

## Phase 8: User Story 6 - Connexion salariés, paramètres et accueil (Priority: P3)

**Goal** : pages d'authentification en deux panneaux, accueil `/` aux couleurs de Vallet Location.

**Independent Test** : se déconnecter, afficher `/` puis la connexion, se connecter, ouvrir les paramètres : même langage, mêmes champs, même comportement (spec US6).

### Tests for User Story 6

- [ ] T060 [P] [US6] Écrire `tests/Feature/Design/WelcomePageTest.php` :
  - (1) invité : `get(route('home'))` contient le logotype, un lien vers `route('login')` et un lien vers `route('portal.login')`, et ne contient pas « Laravel » ;
  - (2) salarié connecté : le lien « Espace salariés » mène à `route('dashboard')` ;
  - (3) `get(route('login'))` contient le logotype et un seul bouton `primary`.

  `tests/Feature/ExampleTest.php` et `tests/Feature/Auth/*` restent verts.

### Implementation for User Story 6

- [X] T061 [US6] Réécrire `resources/views/layouts/auth/simple.blade.php` en deux panneaux (R8), via le composant partagé `resources/views/components/auth-split.blade.php` (aussi utilisé par `portal::layouts.guest`, fait en US4). Ajuster `resources/views/components/auth-header.blade.php` (titre `.display-md`, description `zinc-500`).
- [ ] T062 [US6] Réécrire `resources/views/welcome.blade.php` (`@vite`, `partials.head`, barre de 60 px, bande noire avec titre display et deux `x-tile`, pied de page noir). Ajouter `lang/fr/welcome.php` (titre, accroche, deux tuiles, R12).
- [ ] T063 [P] [US6] Vérifier les pages `resources/views/pages/auth/*.blade.php` (connexion, mot de passe oublié, réinitialisation, vérification, double authentification, confirmation) : composants partagés seulement, `data-test` inchangés. `passkey-*` et le code de récupération : un seul jaune hors modale.
- [ ] T064 [US6] Captures 01 et `/` en 1440 et 375 px. `composer ci:check` code 0, puis commit « Refonte graphique : connexion et accueil ».

---

## Phase 9: User Story 7 - E-mails (Priority: P3)

**Goal** : thème Markdown `vallet` pour tous les e-mails clients, contenu inchangé.

**Independent Test** : envoyer une demande confirmée et une attestation VGP ; les deux e-mails ont le nouveau style et le même texte (spec US7).

- [ ] T065 [P] [US7] Écrire `tests/Feature/Design/MailThemeTest.php`. Rendre `(new ReservationRequestConfirmedMail($request))->render()` et `VgpCertificateMail` avec les factories de leurs layers. Vérifier :
  - le logotype texte « VALLET » ;
  - la couleur `#ffed00` dans le style en ligne du bouton ;
  - l'absence de « Laravel » ;
  - le même texte d'introduction qu'avant (`portal::mail.decision.confirmed.intro`).
- [ ] T066 [US7] Ajouter `'markdown' => ['theme' => 'vallet', 'paths' => [resource_path('views/vendor/mail')]]` dans `config/mail.php`. Créer `resources/views/vendor/mail/html/themes/vallet.css` (R10) et `resources/views/vendor/mail/html/header.blade.php` (logotype texte, lien vers `$url`). T065 passe.
- [ ] T067 [US7] Vérifier dans Mailpit (port 8210) un e-mail de confirmation d'adresse. `composer ci:check` code 0, puis commit « Refonte graphique : e-mails ».

---

## Phase 10: Polish & Cross-Cutting Concerns

- [ ] T068 Écrire `tests/Feature/Design/BrandingTest.php` (SC-007, FR-011). Rendre `home`, `login`, `dashboard`, `planning.index`, `reservations.show`, `portal.login`, `portal.search` et les deux e-mails de T065 : aucun ne contient « Renault », « NouvelR » ni « Laravel » (insensible à la casse), et tous contiennent le logotype.
- [ ] T069 Audit du jaune et du focus :
  - `grep -rn "text-accent\b\|text-yellow\|text-\[#ffed00\]" resources functional` ne renvoie rien hors du logotype ;
  - au clavier, focus visible sur les fonds blanc, noir et jaune (tableau de bord, recherche client, connexion) ;
  - cibles de 44 px sur téléphone.
- [ ] T070 Captures après complètes dans `specs/010-refonte-graphique/captures/apres/` (38 captures + accueil `/`), plus tableau de bord, planning et détail de réservation en sombre ; aucun `OVERFLOW` (SC-001, SC-002).
- [ ] T071 `vendor/bin/phpstan clear-result-cache`, puis `composer ci:check` code 0. Lister dans le dernier commit les assertions de tests existants adaptées (SC-005).
- [ ] T072 Lancer `/speckit-converge` ; répéter l'implémentation et la convergence jusqu'à « Converged ».
- [ ] T073 Envoyer le STATUT final à la coordinatrice (« STATUT 010 | … »), avec les chemins des captures après.

---

## Dependencies & Execution Order

- **Setup (T001–T004)** → **Foundational (T005–T009)** → **US1 (T010–T019)** : bloquants pour toutes les stories.
- **US2 (T020–T027)** dépend d'US1 (jetons, logotype dans les composants).
- **US3 (T028–T035)** dépend d'US1, d'US2 (layout) et de T009 (pictogramme).
- **US4 (T036–T044)** dépend d'US1 et de T009 ; indépendante d'US2 et d'US3.
- **US5 (T045–T059)** dépend d'US1, de T005 et de T009 ; ses tâches de layer T049 à T057 sont parallélisables entre elles.
- **US6 (T060–T064)** dépend d'US1 et de T022 (logotype).
- **US7 (T065–T067)** est indépendante (seul le logotype texte est commun).
- **Polish (T068–T073)** après toutes les stories.

## Parallel Example

```text
# Phase 7, après T047 / T048 :
T049 booking  |  T050 inspection  |  T051 billing  |  T052 deposit
T053 sales    |  T054 certification  |  T055 accounts + portal staff  |  T056 fleet  |  T057 app
```

## Implementation Strategy

1. **MVP** : Setup + Foundational + US1. L'outil entier change d'apparence (police, couleurs, géométrie) sans changer de structure.
2. **Identité** : US2. Puis US3 (tableau de bord) et US4 (espace client), les deux vitrines.
3. **Exhaustivité** : US5, US6 et US7. Puis Polish, captures après et convergence.
4. Un commit par phase, `composer ci:check` code 0 avant chaque commit, aucun push : la coordinatrice pousse et ouvre la PR.
