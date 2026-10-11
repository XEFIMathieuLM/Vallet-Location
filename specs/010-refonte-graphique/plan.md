# Implementation Plan: Refonte graphique

**Branch**: `010-refonte-graphique` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/010-refonte-graphique/spec.md`

## Summary

La refonte applique le langage visuel de [design-reference.md](design-reference.md) à tout l'outil sans toucher au comportement. Elle travaille du centre vers les bords.

1. **Jetons centralisés** dans `resources/css/vallet/` et importés par `resources/css/app.css` :
   - la police Inter Tight, servie par l'application (plugin de polices de Vite, comme Instrument Sans aujourd'hui) ;
   - l'accent jaune via les variables de Flux (`--color-accent`, `--color-accent-foreground`, `--color-accent-content`) ;
   - les gammes Tailwind `zinc`, `red`, `green`, `amber`, `yellow`, `blue` redéfinies à partir des couleurs de la référence. Tous les badges, alertes et couleurs de statut existants (`MachineStatus::color()`, `ReservationStatus::color()`…) changent ainsi d'un coup, sans toucher aux enums (R2).
2. **Composants Flux habillés par CSS** (attributs `data-flux-*`) : champs à bordure basse, cartes, tableaux, alertes, modales, menus. Seul le bouton est publié et modifié, parce qu'il n'expose pas sa variante au CSS (R3).
3. **Composants partagés** de `app/` : logotype, en-têtes de page et de section, état vide, tuiles. Un composant de pictogramme de machine dans `fleet`, avec l'enum `MachineFamily` (R5).
4. **Mises en page** :
   - colonne noire et barre de 60 px pour les salariés ;
   - connexion en deux panneaux ;
   - espace client avec barre blanche, bandeau noir et pied de page noir ;
   - page d'accueil `/` aux couleurs de Vallet Location (R4, R7).
5. **Tableau de bord** recomposé selon la maquette 1 v3 (R6).
6. **Passage écran par écran** : couleurs codées en dur, un seul bouton jaune par écran, planning (R8, R9).
7. **E-mails** : un thème Markdown `vallet` (R10).

Le thème sombre reste au choix dans les paramètres : il est redéfini en noir et jaune dans `resources/css/vallet/dark.css`. L'espace client et les e-mails restent clairs (R11).

Aucune migration, aucune route, aucune permission, aucune règle ni aucun texte existant ne change. Le code PHP modifié se limite à :
- l'enum `PlanningCellKind::cssClasses()` ;
- l'enum `MachineFamily`, nouvelle ;
- des propriétés calculées de présentation pour le tableau de bord (résumé du jour, frise de 7 jours) ;
- la configuration du thème des e-mails.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13 ; Tailwind CSS 4 (configuration CSS, `@theme`) ; Blade.

**Primary Dependencies**:
- Livewire 4, Flux 2.20 (édition gratuite), `laravel-vite-plugin` avec son module `fonts` (fournisseur Bunny, fichiers téléchargés au build et servis depuis `/build`).
- **Aucun nouveau package PHP ni JS.**
- Pour les captures uniquement : `playwright-core` dans un dossier temporaire, hors du projet ([quickstart.md](quickstart.md)).

**Storage**: aucun changement (aucune migration).

**Testing**:
- **Feature** : PHPUnit 12, rendu des écrans par `Livewire::test()` / `get()`. On y vérifie :
  - la présence des entrées et sections ;
  - au plus un bouton jaune hors modale ;
  - le logotype, et l'absence de la marque de la référence et de « Laravel ».
- **Unit** : `MachineFamily`, `PlanningCellKind::cssClasses()`, contrastes des jetons.
- **Vérification visuelle** : captures automatisées avant / après, sur bureau (1440 px) et téléphone (375 px), avec détection du défilement horizontal.

**Target Platform**: navigateurs récents (bureau, tablette, téléphone) ; clients de messagerie pour les e-mails.

**Project Type**: monolithe Laravel en layers OSDD.

**Performance Goals**:
- Aucune requête de plus par écran, sauf sur le tableau de bord, dont le résumé du jour réutilise les totaux déjà calculés par `DayOperations`.
- Polices en `woff2` avec `font-display: swap`, 4 graisses au plus.

**Constraints**:
- FR-017 : aucun comportement ni texte existant modifié ; les textes ajoutés sont listés en R12.
- Contraste AA calculé pour chaque couple de jetons (R2).
- 375 px sans défilement horizontal de la page.
- Fichiers de code de moins de 200 lignes, bouton Flux publié compris (variantes et options inutilisées retirées).

**Scale/Scope**:
- 79 vues de layers et 49 vues d'`app/`/`resources` passées en revue, dont 44 avec un bouton `primary` à arbitrer ; environ 30 modifiées en profondeur.
- 3 fichiers CSS de jetons, 1 composant Flux publié, 9 composants Blade (nouveaux ou refaits), 5 pictogrammes.
- 4 mises en page refaites (salariés, connexion, espace client, téléphone) et 1 thème d'e-mail.

## Affected Repos

Dépôt unique : l'application Laravel à la racine, sans `repos.yml`, comme pour les features 001 à 009.

**Prérequis** : `origin/main` à `72ce925`, qui contient les features 001 à 009 fusionnées. Aucune autre branche n'est attendue.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principe | Application | Statut |
|---|---|---|
| I. Layers OSDD | La présentation transverse vit dans `app/` (layouts, navigation, tableau de bord, composants partagés) et `resources/css`. `MachineFamily` et le pictogramme vivent dans `fleet`, au bas du graphe, et sont consommés par `booking`, `portal` et `app/`. Chaque layer ne change que ses propres vues. Aucun import vers un layer supérieur. | ✅ R5 |
| II. Garanties base et serveur | Sans objet : aucune règle ni donnée ne change. Les refus serveur existants restent la seule garantie ; aucun contrôle n'est déplacé dans l'interface. | ✅ |
| III. Cycles de vie explicites | Sans objet : aucun statut ni transition ne change. Seule la présentation des statuts change, via les gammes de couleurs. | ✅ |
| IV. Effets de bord explicites | Sans objet. Les e-mails gardent leurs Mailables et leurs files ; seul leur thème Markdown change. | ✅ |
| V. Accès par permission | Inchangé : la navigation garde ses `@can` / `@canany`, vérifiés par test entrée par entrée. | ✅ |
| VI. Tests par scénario | Un test Feature par scénario vérifiable sans navigateur : présence, ordre, liens, permissions, bouton jaune unique, logotype, absence de la marque. Les scénarios purement visuels (couleurs rendues, défilement horizontal) sont vérifiés par les captures du quickstart, joints au statut final. Unit pour `MachineFamily`, `PlanningCellKind` et les contrastes. Tests écrits d'abord dans chaque phase. | ✅ voir Complexity Tracking |
| VII. Code simple | Code en anglais, textes ajoutés dans `lang/fr` et `functional/<layer>/resources/lang/fr`, pas de commentaire, pas de package nouveau. Fichiers de moins de 200 lignes : le bouton Flux publié est réduit aux variantes utilisées (156 lignes). | ✅ |

**Re-check après la phase 1** : les contrats ([contracts/](contracts/)) n'ajoutent aucune dépendance entre layers et aucune donnée. Le seul écart est justifié ci-dessous.

## Project Structure

### Documentation (this feature)

```text
specs/010-refonte-graphique/
├── design-reference.md   # référence fournie par l'utilisateur (langage visuel, sans la marque)
├── spec.md
├── plan.md
├── research.md           # R1 à R13
├── data-model.md         # jetons, familles de pictogrammes, correspondance des statuts
├── contracts/
│   ├── design-tokens.md  # jetons CSS, gammes, contrastes
│   ├── ui-components.md  # composants Blade et Flux habillés, props
│   └── screens.md        # carte écran par écran : mise en page, action jaune, changements
├── quickstart.md         # environnement 8110, captures, vérifications
├── maquettes/            # maquettes HTML et PNG validées (1 v3, 2, 3) et pictogrammes
├── captures/avant/       # 38 captures avant la refonte
├── captures/apres/       # captures après (phase finale)
├── checklists/requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
resources/css/
├── app.css                       # importe les trois fichiers ci-dessous
└── vallet/
    ├── tokens.css                # @theme : police, accent, gammes, rayons, échelle typographique
    ├── components.css            # habillage des composants Flux par data-flux-* ; utilitaires .display, .eyebrow
    └── dark.css                  # thème sombre noir / jaune
vite.config.js                    # bunny('Inter Tight', [400, 600, 700, 800])
resources/views/
├── flux/button/index.blade.php   # bouton Flux publié : variantes, rayon 2 px, data-variant
├── components/
│   ├── app-logo.blade.php        # logotype « VALLET LOCATION » (variantes claire / inversée)
│   ├── app-logo-icon.blade.php   # monogramme carré (favicon, menus repliés)
│   ├── page-heading.blade.php    # titre display + surtitre facultatif + actions
│   ├── section-heading.blade.php
│   ├── empty-state.blade.php
│   ├── tile.blade.php            # tuile light | soft | dark | accent
│   └── top-bar.blade.php         # barre de 60 px des salariés
├── layouts/app/sidebar.blade.php # colonne noire, barre de 60 px, en-tête mobile
├── layouts/auth/simple.blade.php # connexion en deux panneaux
├── livewire/dashboard/*.blade.php
├── welcome.blade.php             # accueil `/` aux couleurs de Vallet Location
└── vendor/mail/html/
    ├── header.blade.php          # logotype texte
    └── themes/vallet.css
app/Livewire/Dashboard/DayOperations.php   # résumé du jour, frise de 7 jours (présentation)
config/mail.php                    # markdown.theme = vallet
public/favicon.svg, favicon.ico, apple-touch-icon.png
functional/fleet/
├── src/Enums/MachineFamily.php
├── resources/views/components/pictogram.blade.php
└── resources/views/pictograms/{nacelle,mini-pelle,compacteur,echafaudage,neutral}.blade.php
functional/booking/src/Planning/PlanningCellKind.php   # cssClasses()
functional/*/resources/views/**    # passage écran par écran (contracts/screens.md)
functional/portal/resources/views/layouts/{portal,guest}.blade.php
functional/portal/resources/views/components/hero.blade.php
functional/inspection/resources/views/layouts/phone.blade.php
tests/
├── Feature/Design/               # navigation, logotype, bouton jaune unique, accueil, e-mails
├── Unit/Design/DesignTokensContrastTest.php
└── Concerns/InspectsPrimaryActions.php
functional/fleet/tests/Unit/MachineFamilyTest.php
functional/booking/tests/Unit/PlanningCellKindTest.php
```

**Structure Decision** : la feature n'ajoute aucun layer. La présentation commune reste dans `app/` et `resources/`, qui contiennent déjà la colle et les layouts. Chaque layer ne modifie que ses propres vues. `fleet` porte le seul élément visuel lié au métier, la famille de machine, parce que tous les layers qui affichent une machine en dépendent déjà.

## Phases

1. **Setup** : police, fichiers de jetons, bouton Flux publié, outil de capture.
2. **Fondations (US1)** : jetons, habillage Flux, composants partagés, thème sombre, pictogrammes.
3. **US2** : navigation salariés et logotype.
4. **US3** : tableau de bord.
5. **US4** : espace client.
6. **US5** : écrans métier.
7. **US6** : connexion, paramètres et accueil `/`.
8. **US7** : e-mails.
9. **Finitions** : captures après, audit contraste et bouton jaune, `composer ci:check`, convergence.

Chaque phase se termine par `composer ci:check` (code 0) et un commit, sans push.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Scénarios purement visuels non couverts par PHPUnit (règle VI) | Couleurs effectivement rendues, défilement horizontal à 375 px et lisibilité ne se mesurent qu'avec un navigateur, et le projet n'a pas de suite navigateur. | Ajouter Dusk ou Playwright au projet est un nouveau package (règle VII) pour une seule feature. Les captures automatisées du quickstart (script hors projet) détectent le défilement horizontal et sont jointes au statut. Tout ce qui se vérifie sur le HTML rendu a un test PHPUnit. |
