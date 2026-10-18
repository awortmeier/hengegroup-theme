// Editor for "Unternehmen der Gruppe" (parent) and "Unternehmen" (child card), edited directly in the
// canvas. Markup mirrors the render.php files (card.php via CardInner, button.php outline/grey-light).
import { createElement as el, Fragment, useEffect, useRef, useState } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    LinkControl,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import { Popover, ToolbarButton, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    ACCENT_FORMATS,
    AddItemTile,
    CardInner,
    cardClassName,
    cardVariant,
    cardVariations,
    buttonPreviewClassName,
    cardButtonVariant,
    CardToolbar,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    MediaPicker,
    MediaToolbarButtons,
    toRichTextValue,
    useMediaUrl,
    TileLayoutDropdown,
    tileGridClass,
    useInnerBlockCount,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/firmen/block.json";
import companyMetadata from "../../../../template-parts/blocks/firma/block.json";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, layout } = attributes;
    const gridClass = tileGridClass(
        layout,
        useInnerBlockCount(clientId),
        "md:grid-cols-2 lg:grid-cols-3"
    );
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({ className: "alignfull bg-neutral-200 section-spacing" });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid gap-6 ${gridClass} ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [companyMetadata.name],
            template: [[companyMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={companyMetadata.name}
                    label={__("+ Unternehmen hinzufügen", "hengegroup-theme")}
                    className="h-full min-h-60"
                />
            ),
        }
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <TileLayoutDropdown
                        value={layout}
                        onChange={(value) => setAttributes({ layout: value })}
                        withDefault
                    />
                    <HeadingTagDropdown
                        value={headingTag}
                        onChange={(value) => setAttributes({ headingTag: value })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-12">
                    <RichText
                        tagName={headingTag}
                        className="col-span-12 text-4xl leading-tight font-semibold"
                        value={heading}
                        onChange={(html) => setAttributes({ heading: html })}
                        placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                        allowedFormats={ACCENT_FORMATS}
                        disableLineBreaks
                    />
                    <ul {...innerBlocksProps} />
                </div>
            </section>
        </Fragment>
    );
}

function CompanyEdit({ attributes, setAttributes, isSelected }) {
    const { logoId, text, buttonText, buttonUrl, accent } = attributes;
    const variant = cardVariant(companyMetadata, attributes.variant);
    const blockProps = useBlockProps({
        className: cardClassName({
            variant,
            accent,
            accentBorder: variant === "default",
        }),
    });
    const logoUrl = useMediaUrl(logoId, "medium");
    const selectLogo = (media) => setAttributes({ logoId: media.id });
    const buttonRef = useRef();
    const [isEditingLink, setIsEditingLink] = useState(false);

    useEffect(() => {
        if (!isSelected) {
            setIsEditingLink(false);
        }
    }, [isSelected]);

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <CardToolbar
                        metadata={companyMetadata}
                        variant={variant}
                        accent={accent}
                        setAttributes={setAttributes}
                        accentFor={["default", "filled"]}
                    />
                    <MediaToolbarButtons
                        value={logoId}
                        onSelect={selectLogo}
                        onRemove={() => setAttributes({ logoId: 0 })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="admin-links"
                        label={__(
                            "Link bearbeiten (ohne Button-Text: ganze Karte)",
                            "hengegroup-theme"
                        )}
                        onClick={() => setIsEditingLink(true)}
                        isPressed={isEditingLink}
                    />
                </ToolbarGroup>
            </BlockControls>
            <li {...blockProps}>
                <CardInner
                    variant={variant}
                    accent={accent}
                    leading={
                        <div className="flex h-14 items-center">
                            {logoUrl ? (
                                <img
                                    src={logoUrl}
                                    alt=""
                                    className="h-auto max-h-13 w-auto max-w-55"
                                />
                            ) : (
                                <MediaPicker value={logoId} onSelect={selectLogo}>
                                    {(open) => (
                                        <button
                                            type="button"
                                            className="flex h-13 w-55 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-neutral-300 bg-transparent text-sm text-neutral-500"
                                            onClick={open}
                                        >
                                            {__("Logo auswählen", "hengegroup-theme")}
                                        </button>
                                    )}
                                </MediaPicker>
                            )}
                        </div>
                    }
                    content={
                        <RichText
                            tagName="p"
                            className="text-base leading-normal text-pretty"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                    }
                    footer={
                        <span ref={buttonRef} className="relative ml-auto inline-block">
                            <RichText
                                tagName="span"
                                className={buttonPreviewClassName(cardButtonVariant(variant))}
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
                                        onChange={(next) =>
                                            setAttributes({ buttonUrl: next.url || "" })
                                        }
                                        onRemove={() => setAttributes({ buttonUrl: "" })}
                                        settings={[]}
                                    />
                                </Popover>
                            )}
                        </span>
                    }
                />
            </li>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    // Dynamic block; only the inner blocks are saved.
    save: () => <InnerBlocks.Content />,
});

registerBlockType(companyMetadata, {
    edit: CompanyEdit,
    variations: cardVariations(companyMetadata),
    save: () => null,
});
