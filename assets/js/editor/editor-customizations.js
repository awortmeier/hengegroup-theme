// Block-Editor-weite Anpassungen (kein einzelner Block) -- ueber `enqueue_block_editor_assets`
// geladen statt eines block.json's `editorScript`, siehe inc/setup/theme-blocks.php's
// `hengegroup_theme_enqueue_editor_assets()`. Gebaut wie jedes andere Editor-Bundle als externes
// IIFE gegen WordPress' `wp.*`-Globals (vite.config.editor-customizations.js ueber
// vite.config.editor.factory.js), siehe assets/js/blocks/buehne/edit.jsx's Kopfkommentar fuer die
// grundsaetzliche Architekturbegruendung. Ein Script statt mehrerer kleiner, weil alle drei
// Anpassungen unten denselben Bootstrap (Registrierung/Dependencies) brauchen -- bei einer
// deutlich groesseren, thematisch eigenstaendigen Anpassung lohnt sich ggf. eine Aufteilung.
import { unregisterBlockVariation } from "@wordpress/blocks";
import { dispatch } from "@wordpress/data";
import domReady from "@wordpress/dom-ready";
import { addFilter } from "@wordpress/hooks";

// Deaktiviert `customClassName`-Support fuer JEDEN Block (Core-Bloecke wie eigene) global, damit
// das "Erweitert"-Panel im Block-Inspector nirgends mehr das "Zusaetzliche CSS-Klasse(n)"-Feld
// zeigt -- explizite Anfrage 2026-09-22 ("generell in der Sidebar", nicht block.json-lokal), siehe
// docs/entscheidungen.md. Ein freies CSS-Klassen-Feld widerspraeche ohnehin Regel 1 der CLAUDE.md
// (ausschliesslich Tailwind ueber die Config-API der Komponenten) -- eine frei getippte Klasse
// haette nie eine zugehoerige Tailwind-Definition.
//
// `anchor`-Support bleibt bewusst unangetastet: HTML-Anker/Sprungmarken sind ein eigenstaendiges,
// potenziell genutztes Feature (z. B. Inhaltsverzeichnis-Links), kein Ziel dieser Anfrage. Bloecke
// mit aktiviertem `anchor`-Support (z. B. core/heading) zeigen "Erweitert" deshalb weiterhin, nur
// ohne das CSS-Klassen-Feld.
//
// `blocks.registerBlockType`-Filter statt eines `supports`-Eintrags in jedem einzelnen block.json:
// wirkt automatisch auch auf Core-Bloecke und jeden kuenftigen eigenen Block, ohne dass das jedes
// Mal einzeln nachgezogen werden muesste.
addFilter(
    "blocks.registerBlockType",
    "hengegroup-theme/disable-custom-class-name-support",
    (settings) => ({
        ...settings,
        supports: {
            ...settings.supports,
            customClassName: false,
        },
    })
);

// Diese Standard-Gutenberg-Bloecke sind fuer Redakteure nicht vorgesehen (kein Tailwind-Styling
// dafuer, kein Teil der Theme-Blockpalette) -- explizite Anfrage 2026-09-22. `hideBlockTypes()`
// (WordPress' eigener Mechanismus fuer "Editor-Einstellungen > Bloecke", `core/edit-post`-
// Datenstore) blendet sie nur aus dem Inserter aus statt sie per `unregisterBlockType()`
// vollstaendig zu deregistrieren -- bereits vorhandener Content, der einen dieser Bloecke nutzt,
// bleibt dadurch weiterhin normal render-/editierbar, nur die NEU-Einfuegen-Option verschwindet.
// `domReady()`, weil `hideBlockTypes()` den `core/edit-post`-Store braucht, der erst nach dessen
// eigenem Bootstrap sicher verfuegbar ist (Standard-Pattern aus dem Block-Editor-Handbook).
const HIDDEN_BLOCK_TYPES = [
    "core/quote", // Zitat
    "core/pullquote", // Zitatkasten
    "core/code", // Code
    "core/verse", // Lyrik
    "core/freeform", // Klassisch
    "core/audio", // Audio
    "core/playlist", // Wiedergabeliste (Jetpack-Block, falls aktiv -- sonst wirkungslos)
    "core/html", // Individuelles HTML
    "core/latest-comments", // Neuste Kommentare
    "core/page-list", // Seitenliste
    "core/rss", // RSS
    "core/post-author-biography", // Biografie des Autors
    "core/post-author-name", // Name des Autors
    "core/avatar", // Avatar
    // "Anzahl Woerter" UND "Lesedauer" sind beide nur Varianten dieses EINEN Blocks
    // (`word-count`/`time-to-read`, block.json-Name ist `core/post-to-read`, nicht
    // `core/post-time-to-read`) -- kein zweiter Eintrag noetig/moeglich, ein Block deckt beide
    // Anfrage-Punkte ab.
    "core/post-to-read", // Anzahl Woerter, Lesedauer
];

// Diese Embed-ANBIETER sind explizit unerwuenscht (Anfrage 2026-09-22) -- technisch keine eigenen
// Blocktypen, sondern Varianten des einen `core/embed`-Blocks (`packages/block-library/src/embed/
// variations.js` in Gutenberg-Core, `name` je Anbieter-Slug), deshalb kein Eintrag in
// `HIDDEN_BLOCK_TYPES` oben, sondern ein eigener `unregisterBlockVariation()`-Aufruf pro Slug.
// Nur aus dem Inserter/der Anbieter-Auswahl entfernt, kein `unregisterBlockType('core/embed')` --
// bereits vorhandener Content mit einem dieser Embeds (`providerNameSlug`-Attribut) bleibt dadurch
// weiterhin normal render-/editierbar, exakt dieselbe Nicht-destruktiv-Ueberlegung wie bei
// `HIDDEN_BLOCK_TYPES`. Bewusst NICHT ausgeblendet, weil vom Auftraggeber nicht genannt: Twitter/X,
// YouTube, Facebook, Instagram, Spotify, Vimeo, TikTok, CollegeHumor.
const HIDDEN_EMBED_VARIATIONS = [
    "wordpress", // WordPress
    "soundcloud", // SoundCloud
    "flickr", // Flickr
    "animoto", // Animoto
    "cloudup", // Cloudup
    "crowdsignal", // CrowdSignal
    "dailymotion", // Dailymotion
    "imgur", // Imgur
    "issuu", // Issuu
    "kickstarter", // Kickstarter
    "mixcloud", // Mixcloud
    "pocket-casts", // Pocket Casts
    "reddit", // Reddit
    "reverbnation", // ReverbNation
    "scribd", // Scribd
    "smugmug", // SmugMug
    "speaker-deck", // Speaker Deck
    "ted", // TED
    "tumblr", // Tumblr
    "videopress", // VideoPress
    "wordpress-tv", // WordPress.tv
    "amazon-kindle", // Amazon Kindle
    "pinterest", // Pinterest
    "wolfram-cloud", // Wolfram
    "bluesky", // Bluesky
];

domReady(() => {
    dispatch("core/edit-post").hideBlockTypes(HIDDEN_BLOCK_TYPES);

    HIDDEN_EMBED_VARIATIONS.forEach((variationName) =>
        unregisterBlockVariation("core/embed", variationName)
    );
});
