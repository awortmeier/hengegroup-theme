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
    CardInner,
    cardClassName,
    cardTitleClassName,
    cardVariant,
    cardVariations,
    CardToolbar,
    APPENDER_GRID_CELL_CLASSNAME,
    fromRichTextValue,
    HeadingTagDropdown,
    toRichTextValue,
    TileLayoutDropdown,
    tileGridClass,
    useInnerBlockCount,
    SectionHeaderInner,
    sectionHeaderClassNames,
    sectionBackground,
    SectionBackgroundDropdown,
    sectionSpacingClassName,
    SectionSpacingDropdown,
} from "../shared.jsx";
import metadata from "../../../../template-parts/blocks/benefits/block.json";
import benefitMetadata from "../../../../template-parts/blocks/benefit/block.json";

const CHILD_BLOCK_NAME = benefitMetadata.name;

const ICONS = window.hengegroupThemeBenefitIcons || {};
const DEFAULT_ICON = Object.keys(ICONS)[0] || "";

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, text, layout } = attributes;
    const background = sectionBackground(attributes.background);
    const gridClass = tileGridClass(
        layout,
        useInnerBlockCount(clientId),
        "sm:grid-cols-2 lg:grid-cols-3"
    );
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({
        className: `alignfull ${sectionSpacingClassName(attributes.spacing)} ${background.className}`,
    });
    const headerClasses = sectionHeaderClassNames({ color: background.color });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className: `col-span-12 grid gap-6 ${gridClass} ${APPENDER_GRID_CELL_CLASSNAME}`,
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
                    tone={background.tone}
                    className="min-h-16"
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
                    <TileLayoutDropdown
                        value={layout}
                        onChange={(value) => setAttributes({ layout: value })}
                        withDefault
                    />
                    <SectionBackgroundDropdown
                        value={background.name}
                        onChange={(value) => setAttributes({ background: value })}
                    />
                    <SectionSpacingDropdown
                        value={attributes.spacing}
                        onChange={(value) => setAttributes({ spacing: value })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <SectionHeaderInner
                        color={background.color}
                        className="col-span-12 max-w-3xl"
                        title={
                            <RichText
                                tagName={headingTag}
                                className={headerClasses.title}
                                value={heading}
                                onChange={(html) => setAttributes({ heading: html })}
                                placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                                allowedFormats={ACCENT_FORMATS}
                                disableLineBreaks
                            />
                        }
                        description={
                            <RichText
                                tagName="p"
                                className={headerClasses.description}
                                value={toRichTextValue(text)}
                                onChange={(html) =>
                                    setAttributes({ text: fromRichTextValue(html) })
                                }
                                placeholder={__("Einleitung eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                            />
                        }
                    />
                    <ul {...innerBlocksProps} />
                </div>
            </section>
        </Fragment>
    );
}

function BenefitEdit({ attributes, setAttributes, context }) {
    const { icon, title, text, accent } = attributes;
    // Benefits without their own variant follow the former parent setting (as render.php).
    const variant = cardVariant(
        benefitMetadata,
        attributes.variant,
        context["hengegroup-theme/benefitsVariant"] === "cards" ? "default" : "ghost"
    );
    const blockProps = useBlockProps({
        className: cardClassName({ variant, accent }),
    });
    const iconKey = ICONS[icon] ? icon : DEFAULT_ICON;

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <CardToolbar
                        metadata={benefitMetadata}
                        variant={variant}
                        accent={accent}
                        setAttributes={setAttributes}
                    />
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
            <li {...blockProps}>
                <CardInner
                    variant={variant}
                    accent={accent}
                    icon={
                        <span
                            className="flex"
                            title={ICONS[iconKey]?.label}
                            // SVG from the theme's own sanitized Lucide files, not user input.
                            dangerouslySetInnerHTML={{ __html: ICONS[iconKey]?.svg || "" }}
                        />
                    }
                    iconClassName="from-henge-grey via-henge-green to-henge-blue bg-linear-135 text-grey-light"
                    title={
                        <RichText
                            tagName="h3"
                            className={`min-w-0 flex-1 ${cardTitleClassName(variant, "body-base")}`}
                            value={toRichTextValue(title)}
                            onChange={(html) => setAttributes({ title: fromRichTextValue(html) })}
                            placeholder={__("Titel eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                    }
                    content={
                        <RichText
                            tagName="p"
                            className={`text-base leading-normal text-pretty ${variant === "ghost" ? "opacity-85" : ""}`}
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
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

registerBlockType(benefitMetadata, {
    edit: BenefitEdit,
    variations: cardVariations(benefitMetadata),
    save: () => null,
});
