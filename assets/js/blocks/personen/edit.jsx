// Editor for "Personen" (parent) and "Person" (child card), edited directly in the canvas. Markup
// mirrors the render.php files (card.php raised/lg).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import { ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    AddItemTile,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    MediaPicker,
    MediaToolbarButtons,
    toRichTextValue,
    useMediaUrl,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/personen/block.json";
import personMetadata from "../../../../template-parts/blocks/person/block.json";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag } = attributes;
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({ className: "alignfull py-16 md:py-25" });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 mx-auto grid w-full max-w-225 gap-8 md:grid-cols-2 ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [personMetadata.name],
            template: [[personMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={personMetadata.name}
                    label={__("+ Person hinzufügen", "hengegroup-theme")}
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
                    <ul {...innerBlocksProps} />
                </div>
            </section>
        </Fragment>
    );
}

function PersonEdit({ attributes, setAttributes }) {
    const { imageId, name, role } = attributes;
    const blockProps = useBlockProps({
        className:
            "bg-card text-card-foreground rounded-card-raised shadow-card-raised flex flex-col gap-4 py-7",
    });
    const imageUrl = useMediaUrl(imageId);
    const selectImage = (media) => setAttributes({ imageId: media.id });

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <MediaToolbarButtons
                        value={imageId}
                        onSelect={selectImage}
                        onRemove={() => setAttributes({ imageId: 0 })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <li {...blockProps}>
                <div className="rounded-t-card-raised relative -mt-7 overflow-hidden">
                    {imageUrl ? (
                        <img src={imageUrl} alt="" className="aspect-4/3 w-full object-cover" />
                    ) : (
                        <MediaPicker value={imageId} onSelect={selectImage}>
                            {(open) => (
                                <button
                                    type="button"
                                    className="flex aspect-4/3 w-full cursor-pointer items-center justify-center border-0 bg-neutral-200 text-base font-semibold text-neutral-600"
                                    onClick={open}
                                >
                                    {__("Porträt auswählen", "hengegroup-theme")}
                                </button>
                            )}
                        </MediaPicker>
                    )}
                </div>
                <div className="grid gap-2 px-7">
                    <RichText
                        tagName="h3"
                        className="text-lg leading-normal font-extrabold"
                        value={toRichTextValue(name)}
                        onChange={(html) => setAttributes({ name: fromRichTextValue(html) })}
                        placeholder={__("Name eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                        disableLineBreaks
                    />
                    <RichText
                        tagName="p"
                        className="text-muted-foreground text-base leading-normal"
                        value={toRichTextValue(role)}
                        onChange={(html) => setAttributes({ role: fromRichTextValue(html) })}
                        placeholder={__("Funktion eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                        disableLineBreaks
                    />
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

registerBlockType(personMetadata, {
    edit: PersonEdit,
    save: () => null,
});
