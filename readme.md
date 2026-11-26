# Flaveo – Page Banner

> Module PrestaShop pour afficher des bandeaux d'information programmables en haut de vos pages.
> PrestaShop module to display scheduled information banners at the top of your pages.

![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7.6%2B-df0067)
![PHP](https://img.shields.io/badge/PHP-7.1%2B-777bb4)
![Version](https://img.shields.io/badge/version-1.0.0-2ea44f)

**[🇫🇷 Français](#-français) · [🇬🇧 English](#-english)**

---

## 🇫🇷 Français

### Présentation

Dans PrestaShop, afficher une promo ou une info importante en haut du site demande souvent de toucher au thème, puis de penser à la retirer le moment venu. **Flaveo – Page Banner** permet de :

- **créer plusieurs bandeaux** et les programmer à l'avance ;
- les afficher **fixes ou défilants** (mode marquee) ;
- choisir leurs **couleurs** de fond et de texte ;
- laisser le visiteur les **fermer**, pour une durée choisie.

### Fonctionnalités

#### 1. Contenu

- **Message** multilingue, HTML simple autorisé (`<strong>`, `<em>`, `<span>`…)
- **Lien** optionnel, multilingue : tout le message devient cliquable

#### 2. Programmation

- **Date et heure de début**, et **date et heure de fin**, toutes deux optionnelles
- État visible directement dans la liste :

| État | Description |
|---|---|
| **En cours** | Actif et dans sa période d'affichage |
| **Programmé** | Actif, la date de début n'est pas encore atteinte |
| **Expiré** | Actif, la date de fin est dépassée |
| **Désactivé** | Coupé manuellement, quelles que soient les dates |

- Si plusieurs bandeaux sont en cours en même temps, ils **s'empilent** dans l'ordre de la liste (réordonnable par glisser-déposer)

#### 3. Apparence

- **Couleur de fond** et **couleur du texte** pour chaque bandeau
- **Mode marquee** : le texte défile en boucle continue
  - vitesse réglable en **px/s** (60 par défaut) ;
  - pause au survol ;
  - texte fixe pour les visiteurs ayant activé « réduire les animations ».

#### 4. Fermeture

- Bouton de fermeture **optionnel**
- Durée de masquage en **jours** après fermeture (`0` = jusqu'à la fermeture du navigateur)
- **Modifier un bandeau le réaffiche à tout le monde**, y compris à ceux qui l'avaient fermé

> 💡 Astuce : le bouton **Dupliquer** crée une copie **désactivée** du bandeau, pratique pour préparer la prochaine opération.

### Intégration au thème

Le module se greffe sur `displayAfterBodyOpeningTag`. Pour l'afficher ailleurs :

```smarty
{widget name='flaveo_pagebanner'}
```

> ℹ Le rendu n'est fait qu'une seule fois par page : le hook et le widget peuvent coexister sans doublon.

#### Variable CSS `--flaveo-banner-height`

La hauteur totale des bandeaux affichés est exposée sur le `<body>`. Elle vaut `0px` s'il n'y a aucun bandeau. Elle est posée dès le rendu, puis mise à jour au redimensionnement et à la fermeture.

```css
.header-sticky { top: var(--flaveo-banner-height, 0px); }
.main-content  { padding-top: calc(80px + var(--flaveo-banner-height, 0px)); }
```

#### Événement JS

À la fermeture d'un bandeau, l'événement `flaveo/pagebanner` est déclenché sur `document` :

```js
document.addEventListener('flaveo/pagebanner', function (e) {
    // e.detail = { id: 'flaveo-pagebanner-3', status: 'close' }
});
```

Si le thème expose `window.calculateMenuPosition()`, elle est appelée automatiquement.

#### Personnalisation CSS

- Classes : `.flaveo-pagebanner`, `.flaveo-pagebanner--marquee`, `.flaveo-pagebanner__close`
- Couleurs passées via les variables `--fpb-bg` et `--fpb-color`

### Ce que fait le module en base

- Création de `ps_flaveo_pagebanner` (réglages, dates, position) et de `ps_flaveo_pagebanner_lang` (message, lien)
- Ajout de l'onglet **Apparence > Bandeaux haut de page**
- Le cookie de fermeture est nommé `flaveo_pb_{id}_{clé}` ; la clé est régénérée à chaque enregistrement

### Compatibilité

- PrestaShop **1.7.6 et supérieur** (développé pour la 1.7.8.7)
- PHP **7.1+**
- Multiboutique : **non géré**, les bandeaux sont communs à toutes les boutiques

### Installation

1. Copier le dossier `flaveo_pagebanner` dans `/modules`
2. **Modules > Gestionnaire de modules**, rechercher **Flaveo – Page Banner**, puis **Installer**

### Utilisation rapide

1. **Apparence > Bandeaux haut de page** (ou **Configurer** sur le module)
2. **Ajouter un bandeau**
3. Saisir le message, choisir les dates et les couleurs, activer le marquee si besoin
4. **Enregistrer**

### ⚠ Avertissement

**La désinstallation supprime définitivement tous les bandeaux.**

---

## 🇬🇧 English

### Overview

In PrestaShop, showing a promo or important notice at the top of the site usually means editing the theme, then remembering to remove it later. **Flaveo – Page Banner** lets you:

- **create several banners** and schedule them ahead of time;
- display them **static or scrolling** (marquee mode);
- pick their background and text **colors**;
- let visitors **close** them, for a chosen duration.

### Features

#### 1. Content

- Multilingual **message**, simple HTML allowed (`<strong>`, `<em>`, `<span>`…)
- Optional multilingual **link**: the whole message becomes clickable

#### 2. Scheduling

- Optional **start date & time** and **end date & time**
- State shown directly in the list:

| State | Description |
|---|---|
| **Running** | Enabled and within its display period |
| **Scheduled** | Enabled, start date not reached yet |
| **Expired** | Enabled, end date passed |
| **Disabled** | Turned off manually, whatever the dates |

- When several banners run at the same time, they **stack** in list order (drag & drop to reorder)

#### 3. Appearance

- **Background color** and **text color** per banner
- **Marquee mode**: the text scrolls in a seamless loop
  - adjustable speed in **px/s** (60 by default);
  - pauses on hover;
  - static text for visitors who enabled "reduce motion".

#### 4. Closing

- **Optional** close button
- Hide duration in **days** after closing (`0` = until the browser is closed)
- **Editing a banner shows it again to everyone**, including visitors who closed it

> 💡 Tip: the **Duplicate** button creates a **disabled** copy of the banner, handy to prepare the next campaign.

### Theme integration

The module hooks on `displayAfterBodyOpeningTag`. To display it elsewhere:

```smarty
{widget name='flaveo_pagebanner'}
```

> ℹ Banners are rendered only once per page: the hook and the widget can coexist without duplicates.

#### `--flaveo-banner-height` CSS variable

The total height of displayed banners is exposed on `<body>`. It is `0px` when there is no banner. It is set right at render time, then updated on resize and on close.

```css
.header-sticky { top: var(--flaveo-banner-height, 0px); }
.main-content  { padding-top: calc(80px + var(--flaveo-banner-height, 0px)); }
```

#### JS event

When a banner is closed, a `flaveo/pagebanner` event is dispatched on `document`:

```js
document.addEventListener('flaveo/pagebanner', function (e) {
    // e.detail = { id: 'flaveo-pagebanner-3', status: 'close' }
});
```

If the theme exposes `window.calculateMenuPosition()`, it is called automatically.

#### CSS customization

- Classes: `.flaveo-pagebanner`, `.flaveo-pagebanner--marquee`, `.flaveo-pagebanner__close`
- Colors are passed through the `--fpb-bg` and `--fpb-color` variables

### What happens in the database

- Creates `ps_flaveo_pagebanner` (settings, dates, position) and `ps_flaveo_pagebanner_lang` (message, link)
- Adds the **Design > Page banners** tab
- The close cookie is named `flaveo_pb_{id}_{key}`; the key is regenerated on every save

### Compatibility

- PrestaShop **1.7.6 and above** (built for 1.7.8.7)
- PHP **7.1+**
- Multistore: **not supported**, banners are shared across all shops

### Installation

1. Copy the `flaveo_pagebanner` folder into `/modules`
2. **Modules > Module Manager**, search for **Flaveo – Page Banner**, then **Install**

### Quick start

1. **Design > Page banners** (or **Configure** on the module)
2. **Add a banner**
3. Type the message, pick the dates and colors, enable marquee if needed
4. **Save**

> ℹ The back-office is in French.

### ⚠ Warning

**Uninstalling permanently deletes all banners.**

---

## Structure

```
flaveo_pagebanner/
├── flaveo_pagebanner.php                              # Module principal / Main module (install, hook, widget)
├── classes/
│   └── FlaveoPageBannerItem.php                       # Modèle bandeau / Banner model
├── controllers/admin/
│   └── AdminFlaveoPageBannerController.php            # Liste & formulaire BO / BO list & form
├── views/
│   ├── templates/hook/pagebanner.tpl                  # Rendu front / Front rendering
│   ├── css/front.css                                  # Styles & marquee
│   └── js/front.js                                    # Fermeture, marquee, hauteur / Close, marquee, height
└── logo.png
```

## Auteur / Author

**Loris Pinna** — [lorispinna.com](https://lorispinna.com)
