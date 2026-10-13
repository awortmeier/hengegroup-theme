// Editor for "Unternehmen der Gruppe" (parent) and "Unternehmen" (child card), edited directly in the
// canvas. Markup mirrors the render.php files (card.php raised/lg, button.php outline).
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
    accentClasses,
    AccentDropdown,
    AddItemTile,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    MediaPicker,
    MediaToolbarButtons,
    toRichTextValue,
    useMediaUrl,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/firmen/block.json";
import companyMetadata from "../../../../template-parts/blocks/firma/block.json";

const BUTTON_PREVIEW_CLASSNAME =
    "inline-flex h-8 shrink-0 items-center justify-center gap-1.5 rounded-full border border-grey-dark bg-background px-4 text-base font-medium whitespace-nowrap shadow-xs";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag } = attributes;
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({ className: "alignfull bg-neutral-200 py-16 md:py-25" });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3 ${APPENDER_GRID_CELL_CLASSNAME}`,
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
                    className="hover:border-grey-dark hover:text-grey-dark rounded-card-raised h-full min-h-60 border-neutral-400 text-neutral-600"
                />
            ),
        }
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
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

function CompanyEdit({ attributes, setAttributes, isSelected }) {
    const { logoId, text, buttonText, buttonUrl, accent } = attributes;
    const blockProps = useBlockProps({
        className: `bg-card text-card-foreground rounded-card-raised shadow-card-raised flex flex-col gap-4 border-l-4 py-7 ${accentClasses(accent).border}`,
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
                    <MediaToolbarButtons
                        value={logoId}
                        onSelect={selectLogo}
                        onRemove={() => setAttributes({ logoId: 0 })}
                    />
                    <AccentDropdown
                        value={accent}
                        onChange={(value) => setAttributes({ accent: value })}
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
            <li {...blockProps}>
                <div className="flex-1 px-7">
                    <div className="mb-4.5 flex h-14 items-center">
                        {logoUrl ? (
                            <img src={logoUrl} alt="" className="h-auto max-h-13 w-auto max-w-55" />
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
                    <RichText
                        tagName="p"
                        className="text-base leading-normal text-pretty"
                        value={toRichTextValue(text)}
                        onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                        placeholder={__("Text eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                    />
                </div>
                <div className="flex items-center px-7">
                    <span ref={buttonRef} className="relative ml-auto inline-block">
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
                                    onChange={(next) =>
                                        setAttributes({ buttonUrl: next.url || "" })
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
    // Dynamic block; only the inner blocks are saved.
    save: () => <InnerBlocks.Content />,
});

registerBlockType(companyMetadata, {
    edit: CompanyEdit,
    save: () => null,
});
