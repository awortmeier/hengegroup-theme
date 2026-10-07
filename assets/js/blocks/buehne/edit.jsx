// Editor UI for template-parts/blocks/buehne/block.json. Deliberately NOT built with
// @wordpress/scripts/webpack -- see docs/entscheidungen.md's "Phase-3-Block-Architektur" entry for
// why this is instead a second Vite build entry (vite.config.js) that marks every `@wordpress/*`
// import below as external against WordPress' own `wp.*` globals (no bundled React of our own).
// JSX compiles through esbuild's configurable pragma (see vite.config.js's `esbuild.jsxFactory`)
// straight to `el(...)` calls against the externalized `@wordpress/element`, exactly like
// `React.createElement` would for a stock React/JSX setup.
//
// The canvas preview is a live `ServerSideRender` call against this exact block (see below)
// instead of a second, hand-rolled JS re-implementation of template-parts/blocks/buehne/render.php
// -- Phase-2 Tailwind styling then only ever needs to be written once, in that PHP file. This is
// why inc/setup/theme-setup.php now also calls `add_theme_support('editor-styles')` +
// `add_editor_style()`: without the compiled Tailwind CSS loaded into the editor's iframed canvas,
// this preview would render unstyled.
//
// Folien-Felder (Bild, Badge, Titel, Text, Buttons, ...) sitzen in einem `Modal` statt dauerhaft
// offen im Sidebar-`PanelBody` -- explizite Anfrage 2026-09-22, siehe docs/entscheidungen.md fuer
// die Begruendung. Die Sidebar zeigt dafuer nur noch eine kompakte Liste (Titel, Verschieben,
// Entfernen, "Bearbeiten" oeffnet das Modal fuer genau diese Folie).
//
// `VStack`s `spacing`-Prop (nicht Tailwind) sorgt fuer den Abstand zwischen Modal-Feldern bzw.
// Sidebar-Zeilen -- Modal und Sidebar rendern AUSSERHALB des Editor-Canvas-Iframes (siehe
// Kopfkommentar oben zu `add_editor_style()`), Tailwind-Klassen erreichen dort also technisch gar
// nichts; Regel 1 der CLAUDE.md verlangt Tailwind nur, "wenn ueberhaupt Styling-Code entsteht" --
// hier entsteht keiner, nur Komposition der ohnehin bereits genutzten `@wordpress/components`.
// `__nextHasNoMarginBottom` auf den Feldern in `SlideFields` schaltet deren eigenen (inkonsistent
// wirkenden) Default-Abstand ab, damit `VStack`s `spacing` die einzige, gleichmaessige Abstandsquelle
// bleibt -- sonst addieren sich Feld-eigener Bottom-Margin und `VStack`-Gap.
//
// Button-Links sind bewusst interne Seiten statt freier URL-Eingabe (explizite Anfrage
// 2026-09-22, siehe docs/entscheidungen.md) -- `PageLinkControl` unten laedt alle veroeffentlichten
// Seiten ueber den `core`-Datenstore (`@wordpress/data`s `useSelect()` gegen den WP-REST-Endpunkt
// `wp/v2/pages`, siehe `wp-core-data`-Dependency in inc/setup/theme-blocks.php) und bietet sie als
// durchsuchbare `ComboboxControl`-Liste an; gespeichert wird weiterhin nur die fertige Permalink-
// URL in `primaryButtonUrl`/`secondaryButtonUrl` -- render.php und das Attribut-Schema bleiben
// dadurch unveraendert, nur die Editor-UI aendert sich.
//
// Buttons: kein "Primaeren Button anzeigen"-Schalter mehr, ein Button erscheint, sobald sein Text
// gepflegt ist; Text und Seite stehen je Button nebeneinander (ButtonFields, explizite Nachfrage
// 2026-10-07). Bei nur einer Folie blendet render.php den Folien-Picker unten aus.
import { createElement as el, Fragment, useState } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
    useBlockProps,
} from "@wordpress/block-editor";
import {
    BaseControl,
    Button,
    Card,
    CardBody,
    ComboboxControl,
    Flex,
    FlexBlock,
    Modal,
    PanelBody,
    PanelRow,
    RangeControl,
    SelectControl,
    TextControl,
    TextareaControl,
    ToggleControl,
    __experimentalVStack as VStack,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { decodeEntities } from "@wordpress/html-entities";
import { __, sprintf } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/buehne/block.json";

// Gleiches Akzentfarben-Vokabular wie template-parts/base/button.php/badge.php's `variant` (siehe
// deren Kopfkommentare) -- keine freie Farbauswahl, siehe docs/entscheidungen.md.
const ACCENT_OPTIONS = [
    { label: __("Henge Grün", "hengegroup-theme"), value: "henge-green" },
    { label: __("Henge Blau", "hengegroup-theme"), value: "henge-blue" },
    { label: __("Henge Grau", "hengegroup-theme"), value: "henge-grey" },
];

function createSlide() {
    return {
        adminLabel: "",
        imageId: 0,
        imageAlt: "",
        kickerImageId: 0,
        kickerImageAlt: "",
        badgeText: "",
        title: "",
        text: "",
        accent: "henge-green",
        primaryButtonText: "",
        primaryButtonUrl: "",
        secondaryButtonText: "",
        secondaryButtonUrl: "",
    };
}

// `adminLabel` (siehe SlideFields' erstes Feld) hat Vorrang vor `title`/`badgeText` -- rein
// redaktioneller Verwaltungstitel fuer Sidebar-Liste/Modal-Ueberschrift, unabhaengig vom
// tatsaechlichen (evtl. noch leeren oder bewusst kryptischen) Folien-Inhalt.
function slideLabel(slide, index) {
    return (
        slide.adminLabel ||
        slide.title ||
        slide.badgeText ||
        sprintf(__("Folie %d", "hengegroup-theme"), index + 1)
    );
}

function SlideListItem({ slide, index, slideCount, onEdit, onRemove, onMove }) {
    return (
        <PanelRow>
            <Button variant="link" onClick={() => onEdit(index)}>
                {slideLabel(slide, index)}
            </Button>
            <Button
                icon="arrow-up-alt2"
                label={__("Nach oben", "hengegroup-theme")}
                onClick={() => onMove(index, -1)}
                disabled={index === 0}
            />
            <Button
                icon="arrow-down-alt2"
                label={__("Nach unten", "hengegroup-theme")}
                onClick={() => onMove(index, 1)}
                disabled={index === slideCount - 1}
            />
            <Button
                icon="edit"
                label={__("Bearbeiten", "hengegroup-theme")}
                onClick={() => onEdit(index)}
            />
            <Button
                icon="trash"
                isDestructive
                label={__("Entfernen", "hengegroup-theme")}
                onClick={() => onRemove(index)}
            />
        </PanelRow>
    );
}

const NO_PAGE_OPTION = { value: "", label: __("— Keine Seite —", "hengegroup-theme") };

// Reine Seiten-Auswahl statt freier URL-Eingabe -- `value`/`onChange` bleiben dabei ganz normale
// Permalink-Strings (siehe Kopfkommentar), `ComboboxControl` macht daraus nur eine durchsuchbare
// Liste. Ohne veroeffentlichte Seiten oder waehrend des Ladens bleibt die Liste leer statt mit
// einer Ladeanzeige -- kein zusaetzlicher State fuer einen Zustand, der in der Praxis (Seiten sind
// bereits im Editor-Preloading-Cache) kaum sichtbar wird.
function PageLinkControl({ label, help, value, onChange }) {
    const pages = useSelect(
        (select) =>
            select("core").getEntityRecords("postType", "page", {
                per_page: -1,
                status: "publish",
                orderby: "title",
                order: "asc",
            }),
        []
    );

    const options = [
        NO_PAGE_OPTION,
        ...(pages || []).map((page) => ({
            value: page.link,
            label: decodeEntities(page.title.rendered) || page.link,
        })),
    ];

    return (
        <ComboboxControl
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            label={label}
            help={help}
            value={value}
            onChange={(nextValue) => onChange(nextValue || "")}
            options={options}
        />
    );
}

// Ein Button als Feldgruppe: Text und Zielseite nebeneinander (explizite Nachfrage 2026-10-07).
// Kein An/Aus-Schalter -- ohne Text erscheint der Button nicht (render.php). <fieldset>/<legend>
// gruppiert die beiden Felder auch fuer Screenreader unter "Primaerer/Sekundaerer Button".
// Inline-Style setzt nur Browser-Defaults des <fieldset> zurueck (wp-admin-UI ausserhalb des
// Iframes, kein Theme-Styling, siehe Kopfkommentar).
function ButtonFields({ legend, text, url, onChange }) {
    return (
        <fieldset style={{ border: 0, margin: 0, padding: 0, minWidth: 0 }}>
            <BaseControl.VisualLabel as="legend">{legend}</BaseControl.VisualLabel>
            <Flex align="flex-start" gap={4}>
                <FlexBlock>
                    <TextControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Text", "hengegroup-theme")}
                        help={__("Leer = Button wird nicht angezeigt.", "hengegroup-theme")}
                        value={text}
                        onChange={(value) => onChange(value, url)}
                    />
                </FlexBlock>
                <FlexBlock>
                    <PageLinkControl
                        label={__("Seite", "hengegroup-theme")}
                        value={url}
                        onChange={(value) => onChange(text, value)}
                    />
                </FlexBlock>
            </Flex>
        </fieldset>
    );
}

function SlideFields({ slide, onChange }) {
    return (
        <VStack spacing={6}>
            <TextControl
                __nextHasNoMarginBottom
                label={__("Verwaltungstitel", "hengegroup-theme")}
                help={__(
                    "Nur zur Wiedererkennung hier im Backend (Modal-Titel, Sidebar-Liste) -- erscheint nicht auf der Website.",
                    "hengegroup-theme"
                )}
                value={slide.adminLabel}
                onChange={(value) => onChange({ adminLabel: value })}
            />
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={(media) =>
                        onChange({
                            imageId: media.id,
                            imageAlt: slide.imageAlt || media.alt || "",
                        })
                    }
                    allowedTypes={["image"]}
                    value={slide.imageId}
                    render={({ open }) => (
                        <Button variant="secondary" onClick={open}>
                            {slide.imageId
                                ? __("Bild ändern", "hengegroup-theme")
                                : __("Bild auswählen", "hengegroup-theme")}
                        </Button>
                    )}
                />
            </MediaUploadCheck>
            <TextControl
                __nextHasNoMarginBottom
                label={__("Alternativtext", "hengegroup-theme")}
                value={slide.imageAlt}
                onChange={(value) => onChange({ imageAlt: value })}
            />
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={(media) =>
                        onChange({
                            kickerImageId: media.id,
                            kickerImageAlt: slide.kickerImageAlt || media.alt || "",
                        })
                    }
                    allowedTypes={["image"]}
                    value={slide.kickerImageId}
                    render={({ open }) => (
                        <Button variant="secondary" onClick={open}>
                            {slide.kickerImageId
                                ? __("Kicker-Bild ändern", "hengegroup-theme")
                                : __("Kicker-Bild auswählen", "hengegroup-theme")}
                        </Button>
                    )}
                />
            </MediaUploadCheck>
            <TextControl
                __nextHasNoMarginBottom
                label={__("Kicker-Bild: Alternativtext", "hengegroup-theme")}
                help={__(
                    "Erscheint anstelle des Textlabels im Auswahl-Button unten am Slider, nicht in der Folie selbst.",
                    "hengegroup-theme"
                )}
                value={slide.kickerImageAlt}
                onChange={(value) => onChange({ kickerImageAlt: value })}
            />
            <TextControl
                __nextHasNoMarginBottom
                label={__("Badge-Text", "hengegroup-theme")}
                value={slide.badgeText}
                onChange={(value) => onChange({ badgeText: value })}
            />
            <TextControl
                __nextHasNoMarginBottom
                label={__("Titel", "hengegroup-theme")}
                value={slide.title}
                onChange={(value) => onChange({ title: value })}
            />
            <TextareaControl
                __nextHasNoMarginBottom
                label={__("Text", "hengegroup-theme")}
                value={slide.text}
                onChange={(value) => onChange({ text: value })}
            />
            <SelectControl
                __nextHasNoMarginBottom
                label={__("Akzentfarbe", "hengegroup-theme")}
                value={slide.accent}
                options={ACCENT_OPTIONS}
                onChange={(value) => onChange({ accent: value })}
            />
            <ButtonFields
                legend={__("Primärer Button", "hengegroup-theme")}
                text={slide.primaryButtonText}
                url={slide.primaryButtonUrl}
                onChange={(text, url) =>
                    onChange({ primaryButtonText: text, primaryButtonUrl: url })
                }
            />
            <ButtonFields
                legend={__("Sekundärer Button", "hengegroup-theme")}
                text={slide.secondaryButtonText}
                url={slide.secondaryButtonUrl}
                onChange={(text, url) =>
                    onChange({ secondaryButtonText: text, secondaryButtonUrl: url })
                }
            />
        </VStack>
    );
}

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { slides, autoplay, autoplayInterval, loop, ariaLabel } = attributes;
    // null = kein Modal offen; sonst Index der Folie, deren Felder das Modal gerade zeigt.
    const [editingIndex, setEditingIndex] = useState(null);

    function updateSlide(index, changes) {
        const nextSlides = slides.slice();
        nextSlides[index] = { ...nextSlides[index], ...changes };
        setAttributes({ slides: nextSlides });
    }

    function addSlide() {
        setAttributes({ slides: [...slides, createSlide()] });
        setEditingIndex(slides.length);
    }

    function removeSlide(index) {
        const nextSlides = slides.slice();
        nextSlides.splice(index, 1);
        setAttributes({ slides: nextSlides });
    }

    function moveSlide(index, direction) {
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= slides.length) {
            return;
        }

        const nextSlides = slides.slice();
        const [moved] = nextSlides.splice(index, 1);
        nextSlides.splice(targetIndex, 0, moved);
        setAttributes({ slides: nextSlides });
    }

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Bühne-Einstellungen", "hengegroup-theme")} initialOpen>
                    <ToggleControl
                        label={__("Automatisch weiterschalten", "hengegroup-theme")}
                        checked={autoplay}
                        onChange={(value) => setAttributes({ autoplay: value })}
                    />
                    {autoplay && (
                        <RangeControl
                            label={__("Intervall (Sekunden)", "hengegroup-theme")}
                            min={2}
                            max={20}
                            value={Math.round(autoplayInterval / 1000)}
                            onChange={(value) =>
                                setAttributes({ autoplayInterval: (value || 6) * 1000 })
                            }
                        />
                    )}
                    <ToggleControl
                        label={__(
                            "Nach der letzten Folie zur ersten zurückspringen",
                            "hengegroup-theme"
                        )}
                        checked={loop}
                        onChange={(value) => setAttributes({ loop: value })}
                    />
                    <TextControl
                        label={__("ARIA-Label (Screenreader)", "hengegroup-theme")}
                        value={ariaLabel}
                        onChange={(value) => setAttributes({ ariaLabel: value })}
                    />
                </PanelBody>
                <PanelBody title={__("Folien", "hengegroup-theme")} initialOpen>
                    <VStack spacing={3}>
                        {slides.map((slide, index) => (
                            <SlideListItem
                                key={index}
                                slide={slide}
                                index={index}
                                slideCount={slides.length}
                                onEdit={setEditingIndex}
                                onRemove={removeSlide}
                                onMove={moveSlide}
                            />
                        ))}
                    </VStack>
                    <Button variant="primary" onClick={addSlide}>
                        {__("Folie hinzufügen", "hengegroup-theme")}
                    </Button>
                </PanelBody>
            </InspectorControls>
            {editingIndex !== null && slides[editingIndex] && (
                <Modal
                    title={slideLabel(slides[editingIndex], editingIndex)}
                    onRequestClose={() => setEditingIndex(null)}
                >
                    <SlideFields
                        slide={slides[editingIndex]}
                        onChange={(changes) => updateSlide(editingIndex, changes)}
                    />
                </Modal>
            )}
            <div {...blockProps}>
                {slides.length === 0 ? (
                    <Card>
                        <CardBody>
                            {__(
                                "Noch keine Folien — im rechten Bereich Folien hinzufügen.",
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
