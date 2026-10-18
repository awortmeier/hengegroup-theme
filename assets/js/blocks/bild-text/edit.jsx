// Editor for "Bild & Text": edited in the canvas (mirrors render.php). Text as inner blocks
// (paragraph, heading, list); image, position, background and heading tag via the toolbar.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    InspectorControls,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import {
    PanelBody,
    TextControl,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    ACCENT_FORMATS,
    HeadingTagDropdown,
    MediaPicker,
    MediaToolbarButtons,
    useMediaUrl,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/bild-text/block.json";

const BACKGROUND_OPTIONS = [
    { title: __("Hell", "hengegroup-theme"), value: "light" },
    { title: __("Grau", "hengegroup-theme"), value: "muted" },
    { title: __("Dunkel", "hengegroup-theme"), value: "dark" },
];

// Literals mirror render.php (Tailwind scanner).
const SECTION_CLASSES = {
    light: "",
    muted: "bg-neutral-200",
    dark: "section-dark text-grey-light",
};

const BODY_CLASSNAME =
    "flex flex-col gap-4.5 text-lg leading-[1.6] [&_a]:underline [&_a]:underline-offset-4 [&_:is(h2,h3,h4)]:mt-1.5 [&_:is(h2,h3,h4)]:text-[15px] [&_:is(h2,h3,h4)]:font-semibold [&_p]:m-0 [&_p]:text-pretty [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:flex [&_ol]:flex-col [&_ol]:[counter-reset:item] [&_ol>li]:flex [&_ol>li]:items-baseline [&_ol>li]:gap-3.5 [&_ol>li]:border-t [&_ol>li]:py-3 [&_ol>li]:text-base [&_ol>li]:leading-[1.45] [&_ol>li]:[counter-increment:item] [&_ol>li:last-child]:border-b [&_ol>li]:before:shrink-0 [&_ol>li]:before:text-[13px] [&_ol>li]:before:font-bold [&_ol>li]:before:tabular-nums [&_ol>li]:before:content-[counter(item,decimal-leading-zero)]";

const BODY_TONE_CLASSNAME = {
    light: "[&_a]:text-henge-blue [&_:is(h2,h3,h4)]:text-henge-blue [&_ol>li]:border-grey-dark/12 [&_ol>li]:before:text-henge-blue",
    dark: "[&_a]:text-henge-blue-light [&_:is(h2,h3,h4)]:text-henge-blue-light [&_ol>li]:border-grey-light/20 [&_ol>li]:before:text-henge-blue-light",
};

const INNER_TEMPLATE = [
    [
        "core/paragraph",
        { placeholder: __("Text eingeben oder „/“ für Liste/Überschrift…", "hengegroup-theme") },
    ],
];

function Edit({ attributes, setAttributes }) {
    const { imageId, imageAlt, imagePosition, background, heading, headingTag } = attributes;
    const isDark = background === "dark";
    const imageUrl = useMediaUrl(imageId);
    const blockProps = useBlockProps({
        className: `alignfull section-spacing ${SECTION_CLASSES[background] || ""}`,
    });
    const innerBlocksProps = useInnerBlocksProps(
        { className: `${BODY_CLASSNAME} ${BODY_TONE_CLASSNAME[isDark ? "dark" : "light"]}` },
        {
            allowedBlocks: ["core/paragraph", "core/heading", "core/list"],
            template: INNER_TEMPLATE,
        }
    );

    function selectImage(media) {
        setAttributes({ imageId: media.id, imageAlt: imageAlt || media.alt || "" });
    }

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <HeadingTagDropdown
                        value={headingTag}
                        onChange={(value) => setAttributes({ headingTag: value })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <MediaToolbarButtons
                        value={imageId}
                        onSelect={selectImage}
                        onRemove={() => setAttributes({ imageId: 0 })}
                    />
                    <ToolbarButton
                        icon="align-pull-left"
                        label={__("Bild links", "hengegroup-theme")}
                        isPressed={imagePosition === "left"}
                        onClick={() => setAttributes({ imagePosition: "left" })}
                    />
                    <ToolbarButton
                        icon="align-pull-right"
                        label={__("Bild rechts", "hengegroup-theme")}
                        isPressed={imagePosition === "right"}
                        onClick={() => setAttributes({ imagePosition: "right" })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="admin-appearance"
                        label={__("Hintergrund", "hengegroup-theme")}
                        controls={BACKGROUND_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === background,
                            onClick: () => setAttributes({ background: option.value }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Bild", "hengegroup-theme")} initialOpen>
                    <TextControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Alternativtext", "hengegroup-theme")}
                        help={__("Leer lassen, wenn das Bild nur schmückt.", "hengegroup-theme")}
                        value={imageAlt}
                        onChange={(value) => setAttributes({ imageAlt: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper items-center gap-y-10">
                    <div
                        className={`col-span-12 lg:col-span-6 ${
                            imagePosition === "right" ? "lg:order-2 lg:pl-6" : "lg:pr-6"
                        }`}
                    >
                        <MediaPicker value={imageId} onSelect={selectImage}>
                            {(open) =>
                                imageUrl ? (
                                    <button
                                        type="button"
                                        className="block w-full cursor-pointer border-0 bg-transparent p-0"
                                        onClick={open}
                                        aria-label={__("Bild ändern", "hengegroup-theme")}
                                    >
                                        <img
                                            src={imageUrl}
                                            alt=""
                                            className="rounded-card-raised shadow-card-raised aspect-4/3 w-full object-cover"
                                        />
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        className="rounded-card-raised flex aspect-4/3 w-full cursor-pointer items-center justify-center border-2 border-dashed border-neutral-400 bg-transparent text-base font-semibold text-neutral-500"
                                        onClick={open}
                                    >
                                        {__("Bild auswählen", "hengegroup-theme")}
                                    </button>
                                )
                            }
                        </MediaPicker>
                    </div>
                    <div className="col-span-12 lg:col-span-6">
                        <RichText
                            tagName={headingTag}
                            className={`mb-4.5 text-4xl leading-tight font-semibold text-balance lg:text-[42px] lg:leading-[1.12] ${
                                isDark ? "text-grey-light" : ""
                            }`}
                            value={heading}
                            onChange={(html) => setAttributes({ heading: html })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={ACCENT_FORMATS}
                            disableLineBreaks
                        />
                        <div {...innerBlocksProps} />
                    </div>
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    // Dynamic block; only the inner blocks are saved.
    save: () => <InnerBlocks.Content />,
});
