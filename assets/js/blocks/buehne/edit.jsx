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
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
    useBlockProps,
} from "@wordpress/block-editor";
import {
    Button,
    Card,
    CardBody,
    PanelBody,
    PanelRow,
    RangeControl,
    SelectControl,
    TextControl,
    TextareaControl,
    ToggleControl,
} from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/buehne/block.json";

// Gleiches Akzentfarben-Vokabular wie template-parts/base/button.php/badge.php's `variant` (siehe
// deren Kopfkommentare) -- keine freie Farbauswahl, siehe docs/entscheidungen.md.
const ACCENT_OPTIONS = [
    { label: __("Grün", "hengegroup-theme"), value: "henge-green" },
    { label: __("Blau", "hengegroup-theme"), value: "henge-blue" },
    { label: __("Grau", "hengegroup-theme"), value: "henge-grey" },
];

function createSlide() {
    return {
        imageId: 0,
        imageAlt: "",
        kickerImageId: 0,
        kickerImageAlt: "",
        badgeText: "",
        title: "",
        text: "",
        accent: "henge-green",
        showPrimaryButton: true,
        primaryButtonText: "",
        primaryButtonUrl: "",
        secondaryButtonText: "",
        secondaryButtonUrl: "",
    };
}

function SlideFields({ slide, index, slideCount, onChange, onRemove, onMove }) {
    return (
        <PanelBody
            key={index}
            title={
                slide.title ||
                slide.badgeText ||
                sprintf(__("Folie %d", "hengegroup-theme"), index + 1)
            }
            initialOpen={false}
        >
            <PanelRow>
                <Button
                    variant="secondary"
                    onClick={() => onMove(index, -1)}
                    disabled={index === 0}
                >
                    {__("Nach oben", "hengegroup-theme")}
                </Button>
                <Button
                    variant="secondary"
                    onClick={() => onMove(index, 1)}
                    disabled={index === slideCount - 1}
                >
                    {__("Nach unten", "hengegroup-theme")}
                </Button>
                <Button variant="secondary" isDestructive onClick={() => onRemove(index)}>
                    {__("Entfernen", "hengegroup-theme")}
                </Button>
            </PanelRow>
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={(media) =>
                        onChange(index, {
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
                label={__("Alternativtext", "hengegroup-theme")}
                value={slide.imageAlt}
                onChange={(value) => onChange(index, { imageAlt: value })}
            />
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={(media) =>
                        onChange(index, {
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
                label={__("Kicker-Bild: Alternativtext", "hengegroup-theme")}
                help={__(
                    "Erscheint anstelle des Textlabels im Auswahl-Button unten am Slider, nicht in der Folie selbst.",
                    "hengegroup-theme"
                )}
                value={slide.kickerImageAlt}
                onChange={(value) => onChange(index, { kickerImageAlt: value })}
            />
            <TextControl
                label={__("Badge-Text", "hengegroup-theme")}
                value={slide.badgeText}
                onChange={(value) => onChange(index, { badgeText: value })}
            />
            <TextControl
                label={__("Titel", "hengegroup-theme")}
                value={slide.title}
                onChange={(value) => onChange(index, { title: value })}
            />
            <TextareaControl
                label={__("Text", "hengegroup-theme")}
                value={slide.text}
                onChange={(value) => onChange(index, { text: value })}
            />
            <SelectControl
                label={__("Akzentfarbe", "hengegroup-theme")}
                value={slide.accent}
                options={ACCENT_OPTIONS}
                onChange={(value) => onChange(index, { accent: value })}
            />
            <ToggleControl
                label={__("Primären Button anzeigen", "hengegroup-theme")}
                checked={slide.showPrimaryButton}
                onChange={(value) => onChange(index, { showPrimaryButton: value })}
            />
            {slide.showPrimaryButton && (
                <TextControl
                    label={__("Primärer Button: Text", "hengegroup-theme")}
                    value={slide.primaryButtonText}
                    onChange={(value) => onChange(index, { primaryButtonText: value })}
                />
            )}
            {slide.showPrimaryButton && (
                <TextControl
                    label={__("Primärer Button: Link", "hengegroup-theme")}
                    value={slide.primaryButtonUrl}
                    onChange={(value) => onChange(index, { primaryButtonUrl: value })}
                />
            )}
            <TextControl
                label={__("Sekundärer Button: Text", "hengegroup-theme")}
                value={slide.secondaryButtonText}
                onChange={(value) => onChange(index, { secondaryButtonText: value })}
            />
            <TextControl
                label={__("Sekundärer Button: Link", "hengegroup-theme")}
                value={slide.secondaryButtonUrl}
                onChange={(value) => onChange(index, { secondaryButtonUrl: value })}
            />
        </PanelBody>
    );
}

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { slides, autoplay, autoplayInterval, loop, ariaLabel } = attributes;

    function updateSlide(index, changes) {
        const nextSlides = slides.slice();
        nextSlides[index] = { ...nextSlides[index], ...changes };
        setAttributes({ slides: nextSlides });
    }

    function addSlide() {
        setAttributes({ slides: [...slides, createSlide()] });
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
                    {slides.map((slide, index) => (
                        <SlideFields
                            key={index}
                            slide={slide}
                            index={index}
                            slideCount={slides.length}
                            onChange={updateSlide}
                            onRemove={removeSlide}
                            onMove={moveSlide}
                        />
                    ))}
                    <Button variant="primary" onClick={addSlide}>
                        {__("Folie hinzufügen", "hengegroup-theme")}
                    </Button>
                </PanelBody>
            </InspectorControls>
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
