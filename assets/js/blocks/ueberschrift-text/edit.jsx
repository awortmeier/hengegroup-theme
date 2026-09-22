// Editor UI for template-parts/blocks/ueberschrift-text/block.json. Same Vite/@wordpress-globals
// architecture as blocks/buehne/edit.jsx (see that file's header comment + docs/entscheidungen.md's
// "Phase-3-Block-Architektur") -- built via vite.config.editor-ueberschrift-text.js, which shares
// its actual build config with vite.config.editor.js through vite.config.editor.factory.js instead
// of duplicating the @wordpress/*-external/globals list per block.
//
// The canvas preview is a live `ServerSideRender` call against this exact block instead of a second,
// hand-rolled JS re-implementation of render.php's markup -- same reasoning as buehne/edit.jsx.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import {
    Card,
    CardBody,
    Notice,
    PanelBody,
    SelectControl,
    TextControl,
    TextareaControl,
} from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/ueberschrift-text/block.json";

const TEXT_ALIGN_OPTIONS = [
    { label: __("Zentriert", "hengegroup-theme"), value: "center" },
    { label: __("Linksbündig", "hengegroup-theme"), value: "left" },
];

// Values match render.php's `$container_width`/`.wrapper`-`.wrapper-small` choice (assets/css/
// app.css, see docs/entscheidungen.md "12-Spalten-Grid ueber .wrapper") -- "small" is this block's
// original, narrower default (schmale Textspalte), "default" opts into the wider `.wrapper` used
// by full-width block layouts.
const CONTAINER_WIDTH_OPTIONS = [
    { label: __("Schmal (Textspalte)", "hengegroup-theme"), value: "small" },
    { label: __("Breit (Seitenbreite)", "hengegroup-theme"), value: "default" },
];

function parseAccentWords(value) {
    return value
        .split(",")
        .map((word) => word.trim())
        .filter((word) => word !== "");
}

// render.php/typography.php's accent_words only WRAP words that already occur verbatim in the
// heading string -- they never insert anything. Editors coming from the old, hand-coded design
// (where "HENGEGROUP" was hardcoded as part of the heading) can easily type only the surrounding
// text and expect the accent word to be added by this field instead, which then silently renders
// nothing for it (see docs/entscheidungen.md's note on this block for the concrete case). Surfaced
// as an inline Notice instead of only prose in `help` below, since that prose alone wasn't enough
// to prevent this exact mistake.
function findUnmatchedAccentWords(heading, accentWords) {
    return accentWords.filter((word) => !heading.includes(word));
}

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { heading, accentWords, text, textAlign, containerWidth } = attributes;
    const unmatchedAccentWords = findUnmatchedAccentWords(heading, accentWords);

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Einstellungen", "hengegroup-theme")} initialOpen>
                    <TextControl
                        label={__("Überschrift", "hengegroup-theme")}
                        help={__(
                            "Vollständiger Überschrift-Text, inkl. der Wörter, die unten als Akzent-Wörter hervorgehoben werden sollen.",
                            "hengegroup-theme"
                        )}
                        value={heading}
                        onChange={(value) => setAttributes({ heading: value })}
                    />
                    <TextControl
                        label={__("Akzent-Wörter", "hengegroup-theme")}
                        help={__(
                            "Kommagetrennt — hebt diese Wörter in der Akzent-Schrift hervor. Muss WÖRTLICH oben in der Überschrift vorkommen, wird nicht ergänzt.",
                            "hengegroup-theme"
                        )}
                        value={accentWords.join(", ")}
                        onChange={(value) =>
                            setAttributes({ accentWords: parseAccentWords(value) })
                        }
                    />
                    {unmatchedAccentWords.length > 0 && (
                        <Notice status="warning" isDismissible={false}>
                            {sprintf(
                                __(
                                    "Kommt nicht in der Überschrift vor und wird deshalb nicht angezeigt: %s",
                                    "hengegroup-theme"
                                ),
                                unmatchedAccentWords.join(", ")
                            )}
                        </Notice>
                    )}
                    <TextareaControl
                        label={__("Text", "hengegroup-theme")}
                        value={text}
                        onChange={(value) => setAttributes({ text: value })}
                    />
                    <SelectControl
                        label={__("Ausrichtung", "hengegroup-theme")}
                        value={textAlign}
                        options={TEXT_ALIGN_OPTIONS}
                        onChange={(value) => setAttributes({ textAlign: value })}
                    />
                    <SelectControl
                        label={__("Breite", "hengegroup-theme")}
                        value={containerWidth}
                        options={CONTAINER_WIDTH_OPTIONS}
                        onChange={(value) => setAttributes({ containerWidth: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {heading === "" && text === "" ? (
                    <Card>
                        <CardBody>
                            {__(
                                "Noch kein Inhalt — im rechten Bereich Überschrift/Text eingeben.",
                                "hengegroup-theme"
                            )}
                        </CardBody>
                    </Card>
                ) : (
                    <ServerSideRender block={metadata.name} attributes={attributes} />
                )}
            </div>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
