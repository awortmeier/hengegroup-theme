// Editor for "Buehne (statisch)": edited in the canvas (mirrors render.php); image, alt text and
// title tag via the block toolbar.
import { createElement as el, Fragment, useEffect, useRef, useState } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    useBlockProps,
} from "@wordpress/block-editor";
import {
    Popover,
    TextareaControl,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import metadata from "../../../../template-parts/blocks/buehne-statisch/block.json";

function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

const TITLE_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

function Edit({ attributes, setAttributes, isSelected }) {
    const { imageId, imageAlt, title, titleTag, text } = attributes;
    const blockProps = useBlockProps({
        className: "relative h-[70vh] min-h-[480px] w-full overflow-hidden bg-grey-dark",
    });
    const media = useSelect(
        (select) => (imageId ? select("core").getMedia(imageId) : null),
        [imageId]
    );
    const imageUrl = media?.media_details?.sizes?.large?.source_url || media?.source_url;
    const altButtonRef = useRef();
    const [isEditingAlt, setIsEditingAlt] = useState(false);

    useEffect(() => {
        if (!isSelected) {
            setIsEditingAlt(false);
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
                        <Fragment>
                            <ToolbarButton
                                ref={altButtonRef}
                                icon="editor-help"
                                label={__("Alternativtext", "hengegroup-theme")}
                                onClick={() => setIsEditingAlt(!isEditingAlt)}
                                isPressed={isEditingAlt}
                            />
                            <ToolbarButton
                                icon="no-alt"
                                label={__("Bild entfernen", "hengegroup-theme")}
                                onClick={() => setAttributes({ imageId: 0, imageAlt: "" })}
                            />
                        </Fragment>
                    )}
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="heading"
                        label={__("Titel-Element ändern", "hengegroup-theme")}
                        controls={TITLE_TAG_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === titleTag,
                            onClick: () => setAttributes({ titleTag: option.value }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            {isEditingAlt && isSelected && (
                <Popover
                    placement="bottom-start"
                    anchor={altButtonRef.current}
                    onClose={() => setIsEditingAlt(false)}
                >
                    {/* Outside the editor iframe, no Tailwind there. */}
                    <div style={{ padding: "16px", width: "280px" }}>
                        <TextareaControl
                            __nextHasNoMarginBottom
                            label={__("Alternativtext", "hengegroup-theme")}
                            help={__(
                                "Leer lassen, wenn das Bild nur Dekoration ist.",
                                "hengegroup-theme"
                            )}
                            value={imageAlt}
                            onChange={(value) => setAttributes({ imageAlt: value })}
                        />
                    </div>
                </Popover>
            )}
            <section {...blockProps}>
                {imageUrl ? (
                    <img
                        src={imageUrl}
                        alt=""
                        className="absolute inset-0 size-full object-cover object-[center_40%]"
                    />
                ) : (
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={selectImage}
                            allowedTypes={["image"]}
                            value={imageId}
                            render={({ open }) => (
                                <button
                                    type="button"
                                    className="border-grey-light/60 text-grey-light absolute top-6 right-6 z-10 cursor-pointer rounded-full border-2 border-dashed bg-transparent px-5 py-2"
                                    onClick={open}
                                >
                                    {__("Bild auswählen", "hengegroup-theme")}
                                </button>
                            )}
                        />
                    </MediaUploadCheck>
                )}
                <div className="from-grey-dark/25 to-grey-dark/70 pointer-events-none absolute inset-0 bg-linear-to-b"></div>
                <div className="absolute inset-x-0 bottom-0 pb-10 md:pb-14">
                    <div className="wrapper">
                        <div className="col-span-12 flex flex-col gap-3.5">
                            <RichText
                                tagName={titleTag}
                                className="font-accent text-grey-light m-0 text-[clamp(2.375rem,6vw,4rem)] leading-[1.05] font-semibold"
                                value={toRichTextValue(title)}
                                onChange={(html) =>
                                    setAttributes({ title: fromRichTextValue(html) })
                                }
                                placeholder={__("Titel eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                                disableLineBreaks
                            />
                            <RichText
                                tagName="p"
                                className="text-grey-light max-w-2xl text-2xl leading-normal font-medium"
                                value={toRichTextValue(text)}
                                onChange={(html) =>
                                    setAttributes({ text: fromRichTextValue(html) })
                                }
                                placeholder={__("Unterzeile eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                                disableLineBreaks
                            />
                        </div>
                    </div>
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
