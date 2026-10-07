// Editor UI for template-parts/blocks/galerie/block.json. Alles direkt in der Canvas (explizite
// Nachfrage 2026-10-07), gleiches Prinzip wie auszeichnungen/benefits:
//   - Ueberschrift als `RichText`, Element (h1-h6/p) ueber die Block-Toolbar.
//   - Bilder im selben Bento-Raster wie im Frontend (Formen aus layout.js, gespiegelt zu
//     hengegroup_theme_get_bento_tile_shapes()); je Bild Schaltflaechen zum Verschieben und
//     Entfernen (erscheinen beim Ueberfahren/Fokussieren), darunter "+ Bilder hinzufuegen"
//     (Mehrfachauswahl aus der Mediathek, wird angehaengt). "Galerie bearbeiten" in der Toolbar
//     oeffnet zusaetzlich den Mediathek-Galerie-Dialog (Sortieren per Drag & Drop).
// Gespeichert werden nur Attachment-IDs, Alternativtexte kommen aus der Mediathek (render.php).
// Die Canvas wird clientseitig gebaut (kein `ServerSideRender`), damit die Bedienelemente auf den
// Bildern sitzen koennen; Tailwind ist per add_editor_style() im Editor-Iframe geladen.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    useBlockProps,
} from "@wordpress/block-editor";
import { ToolbarButton, ToolbarDropdownMenu, ToolbarGroup } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { __, sprintf } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import metadata from "../../../../template-parts/blocks/galerie/block.json";
import { getBentoTileShapes, TILE_CLASSNAMES } from "./layout.js";

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

const TILE_BUTTON_CLASSNAME =
    "flex size-8 cursor-pointer items-center justify-center rounded-full border-0 bg-white/90 text-base font-bold text-grey-dark shadow hover:bg-white disabled:cursor-default disabled:opacity-40";

function Edit({ attributes, setAttributes }) {
    const { heading, headingTag, imageIds } = attributes;
    const blockProps = useBlockProps({ className: "alignfull py-16 md:py-24 lg:py-25" });
    const mediaById = useSelect(
        (select) => Object.fromEntries(imageIds.map((id) => [id, select("core").getMedia(id)])),
        [imageIds]
    );
    const shapes = getBentoTileShapes(imageIds.length);

    function moveImage(index, direction) {
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= imageIds.length) {
            return;
        }

        const nextIds = imageIds.slice();
        [nextIds[index], nextIds[targetIndex]] = [nextIds[targetIndex], nextIds[index]];
        setAttributes({ imageIds: nextIds });
    }

    function removeImage(index) {
        setAttributes({ imageIds: imageIds.filter((id, i) => i !== index) });
    }

    function appendImages(media) {
        const newIds = media.map((item) => item.id).filter((id) => !imageIds.includes(id));
        setAttributes({ imageIds: [...imageIds, ...newIds] });
    }

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
                {imageIds.length > 0 && (
                    <ToolbarGroup>
                        <MediaUploadCheck>
                            <MediaUpload
                                multiple
                                gallery
                                allowedTypes={["image"]}
                                value={imageIds}
                                onSelect={(media) =>
                                    setAttributes({ imageIds: media.map((item) => item.id) })
                                }
                                render={({ open }) => (
                                    <ToolbarButton onClick={open}>
                                        {__("Galerie bearbeiten", "hengegroup-theme")}
                                    </ToolbarButton>
                                )}
                            />
                        </MediaUploadCheck>
                    </ToolbarGroup>
                )}
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <RichText
                        tagName={headingTag}
                        className="col-span-12 text-4xl leading-tight font-semibold"
                        value={toRichTextValue(heading)}
                        onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                        placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                        disableLineBreaks
                    />
                    {imageIds.length > 0 && (
                        <ul className="col-span-12 grid grid-cols-1 gap-5 sm:grid-flow-dense sm:grid-cols-3">
                            {imageIds.map((id, index) => {
                                const media = mediaById[id];
                                const url =
                                    media?.media_details?.sizes?.large?.source_url ||
                                    media?.source_url;

                                return (
                                    <li
                                        key={id}
                                        className={`group relative ${TILE_CLASSNAMES[shapes[index]]}`}
                                    >
                                        {url ? (
                                            <img
                                                src={url}
                                                alt=""
                                                className="size-full rounded-2xl object-cover"
                                            />
                                        ) : (
                                            <div className="size-full animate-pulse rounded-2xl bg-neutral-200" />
                                        )}
                                        <div className="absolute top-2 right-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
                                            <button
                                                type="button"
                                                className={TILE_BUTTON_CLASSNAME}
                                                onClick={() => moveImage(index, -1)}
                                                disabled={index === 0}
                                                aria-label={__("Nach vorne", "hengegroup-theme")}
                                            >
                                                ←
                                            </button>
                                            <button
                                                type="button"
                                                className={TILE_BUTTON_CLASSNAME}
                                                onClick={() => moveImage(index, 1)}
                                                disabled={index === imageIds.length - 1}
                                                aria-label={__("Nach hinten", "hengegroup-theme")}
                                            >
                                                →
                                            </button>
                                            <button
                                                type="button"
                                                className={TILE_BUTTON_CLASSNAME}
                                                onClick={() => removeImage(index)}
                                                aria-label={sprintf(
                                                    __("Bild %d entfernen", "hengegroup-theme"),
                                                    index + 1
                                                )}
                                            >
                                                ×
                                            </button>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                    <MediaUploadCheck>
                        <MediaUpload
                            multiple
                            allowedTypes={["image"]}
                            onSelect={appendImages}
                            render={({ open }) => (
                                <button
                                    type="button"
                                    className="hover:border-grey-dark hover:text-grey-dark col-span-12 flex min-h-16 cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-neutral-300 bg-transparent text-base font-semibold text-neutral-600"
                                    onClick={open}
                                >
                                    {__("+ Bilder hinzufügen", "hengegroup-theme")}
                                </button>
                            )}
                        />
                    </MediaUploadCheck>
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
