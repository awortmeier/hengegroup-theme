// Editor UI for template-parts/blocks/auszeichnungen/block.json (Eltern-Block) und
// template-parts/blocks/auszeichnung/block.json (Kind-Block = eine Karte), beide in diesem Bundle
// (vite.config.editor-auszeichnungen.js). Alles wird direkt in der Canvas bearbeitet (explizite
// Nachfrage 2026-10-07):
//   - Eltern-Block: Ueberschrift als `RichText`, Element (h1-h6/p) ueber die Block-Toolbar, die
//     Karten als InnerBlocks -- Hinzufuegen ueber das "+" im Raster, Verschieben/Duplizieren/
//     Loeschen ueber Gutenbergs eigene Block-Werkzeuge.
//   - Kind-Block: Titel/Text/Button-Beschriftung als `RichText`; Bild per Klick auf das Bild (bzw.
//     den Platzhalter) aus der Mediathek, Bildform und Button-Link ueber die Toolbar der Karte
//     (Link als `LinkControl`-Popover wie beim Produkte-Block); nur der Alternativtext sitzt in der
//     Sidebar, wie bei Cores eigenem Bild-Block.
// Markup und Klassen spiegeln auszeichnungen/render.php bzw. auszeichnung/render.php (Tailwind ist
// per add_editor_style() im Editor-Iframe geladen), damit die Canvas wie das Frontend aussieht.
// Sidebar/Popover rendern ausserhalb des Iframes -- dort entsteht kein eigenes Styling.
import { createElement as el, Fragment, useEffect, useRef, useState } from "@wordpress/element";
import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    InspectorControls,
    LinkControl,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import {
    PanelBody,
    Popover,
    TextControl,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import metadata from "../../../../template-parts/blocks/auszeichnungen/block.json";
import cardMetadata from "../../../../template-parts/blocks/auszeichnung/block.json";

const CHILD_BLOCK_NAME = cardMetadata.name;

// Gleicher Plain-String-Rundlauf wie produkte/edit.jsx: alle Texte bleiben reine Strings, die
// render.php-Dateien geben sie ueber esc_html() aus.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

const HEADING_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Überschrift 3 (H3)", "hengegroup-theme"), value: "h3" },
    { title: __("Überschrift 4 (H4)", "hengegroup-theme"), value: "h4" },
    { title: __("Überschrift 5 (H5)", "hengegroup-theme"), value: "h5" },
    { title: __("Überschrift 6 (H6)", "hengegroup-theme"), value: "h6" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

const SHAPE_OPTIONS = [
    { title: __("Rund (Siegel)", "hengegroup-theme"), value: "circle" },
    { title: __("Abgerundet (Logo)", "hengegroup-theme"), value: "rounded" },
];

// 1:1 aus template-parts/base/button.php's `variant: 'outline'`/`size: 'base'` gespiegelt.
const BUTTON_PREVIEW_CLASSNAME =
    "inline-flex h-8 shrink-0 items-center justify-center gap-1.5 self-end rounded-full border border-grey-dark px-4 text-base font-medium whitespace-nowrap";

// Eigene "Hinzufuegen"-Kachel als letzte Rasterzelle statt Gutenbergs Standard-"+": dessen Wrapper
// sitzt im Raster absolut positioniert ueber der letzten Karte und ist auf dunklem Grund kaum
// sichtbar. `[&>.block-list-appender]:!static` (Important-Modifier, Gutenbergs eigene Editor-Styles
// gewinnen sonst) am Raster (siehe `useInnerBlocksProps`) macht den
// Wrapper zu einer normalen Rasterzelle.
function AddItemTile({ rootClientId }) {
    const { insertBlock } = useDispatch("core/block-editor");

    return (
        <button
            type="button"
            className="hover:border-grey-dark hover:text-grey-dark flex min-h-40 w-full cursor-pointer items-center justify-center rounded-[20px] border-2 border-dashed border-neutral-400 bg-transparent text-base font-semibold text-neutral-600"
            onClick={() => insertBlock(createBlock(CHILD_BLOCK_NAME), undefined, rootClientId)}
        >
            {__("+ Auszeichnung hinzufügen", "hengegroup-theme")}
        </button>
    );
}

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag } = attributes;
    // Volle Breite im Editor-Iframe, gleiche Begruendung wie produkte/edit.jsx's `alignfull`.
    const blockProps = useBlockProps({
        className: "alignfull bg-neutral-200 py-16 md:py-24 lg:py-25",
    });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className:
                "col-span-12 grid gap-8 md:grid-cols-2 [&>.block-list-appender]:!static [&>.block-list-appender]:!m-0 [&>.block-list-appender]:!w-full",
        },
        {
            allowedBlocks: [cardMetadata.name],
            template: [[cardMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => <AddItemTile rootClientId={clientId} />,
        }
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="heading"
                        label={__("Überschrift-Element ändern", "hengegroup-theme")}
                        controls={HEADING_TAG_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === headingTag,
                            onClick: () => setAttributes({ headingTag: option.value }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <RichText
                        tagName={headingTag}
                        className="col-span-12 text-3xl leading-tight font-semibold"
                        value={toRichTextValue(heading)}
                        onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                        placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                        disableLineBreaks
                    />
                    <ul {...innerBlocksProps} />
                </div>
            </section>
        </Fragment>
    );
}

function CardEdit({ attributes, setAttributes, isSelected }) {
    const { imageId, imageAlt, imageShape, title, text, buttonText, buttonUrl } = attributes;
    const blockProps = useBlockProps({
        className:
            "flex items-start gap-6 rounded-[20px] bg-white p-6 shadow-[0_8px_24px_rgba(0,0,0,0.08)] sm:p-8",
    });
    const media = useSelect(
        (select) => (imageId ? select("core").getMedia(imageId) : null),
        [imageId]
    );
    const buttonRef = useRef();
    const [isEditingLink, setIsEditingLink] = useState(false);
    const shapeClassName = imageShape === "rounded" ? "rounded-xl" : "rounded-full";
    const imageUrl = media?.media_details?.sizes?.thumbnail?.source_url || media?.source_url;

    useEffect(() => {
        if (!isSelected) {
            setIsEditingLink(false);
        }
    }, [isSelected]);

    function selectImage(nextMedia) {
        setAttributes({ imageId: nextMedia.id, imageAlt: imageAlt || nextMedia.alt || "" });
    }

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={selectImage}
                            allowedTypes={["image"]}
                            value={imageId}
                            render={({ open }) => (
                                <ToolbarButton
                                    icon="format-image"
                                    label={
                                        imageId
                                            ? __("Bild ändern", "hengegroup-theme")
                                            : __("Bild auswählen", "hengegroup-theme")
                                    }
                                    onClick={open}
                                />
                            )}
                        />
                    </MediaUploadCheck>
                    {imageId > 0 && (
                        <ToolbarButton
                            icon="no-alt"
                            label={__("Bild entfernen", "hengegroup-theme")}
                            onClick={() => setAttributes({ imageId: 0 })}
                        />
                    )}
                    <ToolbarDropdownMenu
                        icon="image-crop"
                        label={__("Bildform", "hengegroup-theme")}
                        controls={SHAPE_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === imageShape,
                            onClick: () => setAttributes({ imageShape: option.value }),
                        }))}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="admin-links"
                        label={__("Button-Link bearbeiten", "hengegroup-theme")}
                        onClick={() => setIsEditingLink(true)}
                        isPressed={isEditingLink}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Bild", "hengegroup-theme")} initialOpen>
                    <TextControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Alternativtext", "hengegroup-theme")}
                        help={__(
                            "Beschreibt das Bild für Screenreader, z. B. „Siegel Krisensicherste Unternehmen 2023“.",
                            "hengegroup-theme"
                        )}
                        value={imageAlt}
                        onChange={(value) => setAttributes({ imageAlt: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <li {...blockProps}>
                <MediaUploadCheck>
                    <MediaUpload
                        onSelect={selectImage}
                        allowedTypes={["image"]}
                        value={imageId}
                        render={({ open }) =>
                            imageUrl ? (
                                <button
                                    type="button"
                                    className="shrink-0 cursor-pointer border-0 bg-transparent p-0"
                                    onClick={open}
                                    aria-label={__("Bild ändern", "hengegroup-theme")}
                                >
                                    <img
                                        src={imageUrl}
                                        alt=""
                                        className={`size-19 object-cover ${shapeClassName}`}
                                    />
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    className={`flex size-19 shrink-0 cursor-pointer items-center justify-center border-2 border-dashed border-neutral-300 bg-transparent text-sm text-neutral-500 ${shapeClassName}`}
                                    onClick={open}
                                >
                                    {__("Bild", "hengegroup-theme")}
                                </button>
                            )
                        }
                    />
                </MediaUploadCheck>
                <div className="flex min-w-0 flex-1 flex-col">
                    <RichText
                        tagName="h3"
                        className="text-grey-dark mb-2 text-lg font-bold"
                        value={toRichTextValue(title)}
                        onChange={(html) => setAttributes({ title: fromRichTextValue(html) })}
                        placeholder={__("Titel eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                        disableLineBreaks
                    />
                    <RichText
                        tagName="p"
                        className="text-grey-dark mb-4 text-base leading-normal"
                        value={toRichTextValue(text)}
                        onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                        placeholder={__("Text eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                    />
                    <span ref={buttonRef} className="relative self-end">
                        <RichText
                            tagName="span"
                            className={BUTTON_PREVIEW_CLASSNAME}
                            value={toRichTextValue(buttonText)}
                            onChange={(html) =>
                                setAttributes({ buttonText: fromRichTextValue(html) })
                            }
                            placeholder={__("Button-Text…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                        {isEditingLink && isSelected && (
                            <Popover
                                placement="bottom-end"
                                anchor={buttonRef.current}
                                onClose={() => setIsEditingLink(false)}
                            >
                                <LinkControl
                                    value={{ url: buttonUrl }}
                                    onChange={(nextValue) =>
                                        setAttributes({ buttonUrl: nextValue.url || "" })
                                    }
                                    onRemove={() => setAttributes({ buttonUrl: "" })}
                                    settings={[]}
                                />
                            </Popover>
                        )}
                    </span>
                </div>
            </li>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    // Dynamischer Block (render.php), gespeichert werden nur die Kind-Bloecke.
    save: () => <InnerBlocks.Content />,
});

registerBlockType(cardMetadata, {
    edit: CardEdit,
    save: () => null,
});
