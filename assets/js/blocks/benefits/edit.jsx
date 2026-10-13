// Editor for "Benefits" (parent) and "Benefit" (child), edited directly in the canvas. Icons incl.
// SVG come from window.hengegroupThemeBenefitIcons (same list as the frontend).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import { ToolbarDropdownMenu, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    ACCENT_FORMATS,
    AddItemTile,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    toRichTextValue,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/benefits/block.json";
import benefitMetadata from "../../../../template-parts/blocks/benefit/block.json";

const CHILD_BLOCK_NAME = benefitMetadata.name;

const ICONS = window.hengegroupThemeBenefitIcons || {};
const DEFAULT_ICON = Object.keys(ICONS)[0] || "";

const VARIANT_OPTIONS = [
    { title: __("Liste", "hengegroup-theme"), value: "list" },
    { title: __("Karten", "hengegroup-theme"), value: "cards" },
];

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, text, variant } = attributes;
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({
        className:
            "alignfull bg-grey-dark bg-[radial-gradient(ellipse_60%_50%_at_20%_20%,rgba(255,255,255,0.08),transparent_60%),radial-gradient(ellipse_50%_40%_at_80%_70%,rgba(255,255,255,0.05),transparent_60%)] py-16 shadow-[inset_0_40px_40px_-40px_rgba(0,0,0,0.5),inset_0_-40px_40px_-40px_rgba(0,0,0,0.5)] md:py-24 lg:py-25",
    });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid sm:grid-cols-2 lg:grid-cols-3 ${variant === "cards" ? "gap-6" : "gap-7"} ${APPENDER_GRID_CELL_CLASSNAME}`,
        },
        {
            allowedBlocks: [benefitMetadata.name],
            template: [[benefitMetadata.name]],
            orientation: "horizontal",
            renderAppender: () => (
                <AddItemTile
                    rootClientId={clientId}
                    blockName={CHILD_BLOCK_NAME}
                    label={__("+ Benefit hinzufügen", "hengegroup-theme")}
                    className="border-grey-light/40 text-grey-light/80 hover:border-grey-light hover:text-grey-light min-h-16 rounded-xl"
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
                    <ToolbarDropdownMenu
                        icon="grid-view"
                        label={__("Darstellung", "hengegroup-theme")}
                        controls={VARIANT_OPTIONS.map((option) => ({
                            title: option.title,
                            isActive: option.value === variant,
                            onClick: () => setAttributes({ variant: option.value }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-12">
                    <div className="col-span-12 max-w-3xl">
                        <RichText
                            tagName={headingTag}
                            className="text-grey-light mb-5 text-4xl leading-tight font-semibold"
                            value={heading}
                            onChange={(html) => setAttributes({ heading: html })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={ACCENT_FORMATS}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className="text-grey-light text-lg leading-normal"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Einleitung eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                    </div>
                    <ul {...innerBlocksProps} />
                </div>
            </section>
        </Fragment>
    );
}

function BenefitEdit({ attributes, setAttributes, context }) {
    const { icon, title, text } = attributes;
    const isCard = context["hengegroup-theme/benefitsVariant"] === "cards";
    // Card mirrors card.php raised/lg with icon box.
    const blockProps = useBlockProps({
        className: isCard
            ? "bg-card text-card-foreground rounded-card-raised shadow-card-raised flex flex-col gap-4 py-7"
            : "flex items-start gap-4",
    });
    const iconKey = ICONS[icon] ? icon : DEFAULT_ICON;
    const iconElement = (
        <span
            className={`from-henge-grey via-henge-green to-henge-blue flex shrink-0 items-center justify-center rounded-xl bg-linear-135 ${isCard ? "size-11" : "size-12"}`}
            title={ICONS[iconKey]?.label}
            // SVG from the theme's own sanitized Lucide files, not user input.
            dangerouslySetInnerHTML={{ __html: ICONS[iconKey]?.svg || "" }}
        />
    );
    const titleField = (className) => (
        <RichText
            tagName="h3"
            className={className}
            value={toRichTextValue(title)}
            onChange={(html) => setAttributes({ title: fromRichTextValue(html) })}
            placeholder={__("Titel eingeben…", "hengegroup-theme")}
            allowedFormats={[]}
            disableLineBreaks
        />
    );
    const textField = (className) => (
        <RichText
            tagName="p"
            className={className}
            value={toRichTextValue(text)}
            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
            placeholder={__("Text eingeben…", "hengegroup-theme")}
            allowedFormats={[]}
        />
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="art"
                        label={__("Icon wählen", "hengegroup-theme")}
                        controls={Object.entries(ICONS).map(([key, option]) => ({
                            title: option.label,
                            isActive: key === iconKey,
                            onClick: () => setAttributes({ icon: key }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            {isCard ? (
                <li {...blockProps}>
                    <div className="flex min-w-0 items-center gap-3.5 px-7">
                        {iconElement}
                        {titleField("min-w-0 flex-1 text-lg leading-normal font-extrabold")}
                    </div>
                    <div className="px-7">{textField("text-base leading-normal text-pretty")}</div>
                </li>
            ) : (
                <li {...blockProps}>
                    {iconElement}
                    <div className="min-w-0 flex-1">
                        {titleField("text-grey-light mb-1.5 text-lg font-bold")}
                        {textField("text-grey-light/85 text-base leading-normal")}
                    </div>
                </li>
            )}
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    // Dynamic block; only the inner blocks are saved.
    save: () => <InnerBlocks.Content />,
});

registerBlockType(benefitMetadata, {
    edit: BenefitEdit,
    save: () => null,
});
