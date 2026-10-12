// Editor-wide adjustments (loaded via enqueue_block_editor_assets).
import { unregisterBlockVariation } from "@wordpress/blocks";
import { dispatch } from "@wordpress/data";
import domReady from "@wordpress/dom-ready";
import { addFilter } from "@wordpress/hooks";

// No "Additional CSS class(es)" field on any block (anchors stay available).
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

// Hidden from the inserter only; existing content keeps working.
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
    // One block for both "word count" and "time to read".
    "core/post-to-read", // Anzahl Woerter, Lesedauer
];

// Embed providers are variations of core/embed, so they are unregistered individually.
const HIDDEN_EMBED_VARIATIONS = [
    "wordpress",
    "soundcloud",
    "flickr",
    "animoto",
    "cloudup",
    "crowdsignal",
    "dailymotion",
    "imgur",
    "issuu",
    "kickstarter",
    "mixcloud",
    "pocket-casts",
    "reddit",
    "reverbnation",
    "scribd",
    "smugmug",
    "speaker-deck",
    "ted",
    "tumblr",
    "videopress",
    "wordpress-tv",
    "amazon-kindle",
    "pinterest",
    "wolfram-cloud",
    "bluesky",
];

domReady(() => {
    dispatch("core/edit-post").hideBlockTypes(HIDDEN_BLOCK_TYPES);

    HIDDEN_EMBED_VARIATIONS.forEach((variationName) =>
        unregisterBlockVariation("core/embed", variationName)
    );
});
