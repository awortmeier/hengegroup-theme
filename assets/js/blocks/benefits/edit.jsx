// Editor for "Benefits" (parent) and "Benefit" (child), edited directly in the canvas. Icons incl.
// SVG come from window.hengegroupThemeBenefitIcons (same list as the frontend).
import { createElement as el, Fragment } from "@wordpress/element";
import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    RichText,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import { ToolbarDropdownMenu, ToolbarGroup } from "@wordpress/components";
import { useDispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import metadata from "../../../../template-parts/blocks/benefits/block.json";
import benefitMetadata from "../../../../template-parts/blocks/benefit/block.json";

const CHILD_BLOCK_NAME = benefitMetadata.name;

const ICONS = window.hengegroupThemeBenefitIcons || {};
const DEFAULT_ICON = Object.keys(ICONS)[0] || "";

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

// Own "add" tile as the last grid cell (see auszeichnungen/edit.jsx).
function AddItemTile({ rootClientId }) {
    const { insertBlock } = useDispatch("core/block-editor");

    return (
        <button
            type="button"
            className="border-grey-light/40 text-grey-light/80 hover:border-grey-light hover:text-grey-light flex min-h-16 w-full cursor-pointer items-center justify-center rounded-xl border-2 border-dashed bg-transparent text-base font-semibold"
            onClick={() => insertBlock(createBlock(CHILD_BLOCK_NAME), undefined, rootClientId)}
        >
            {__("+ Benefit hinzufügen", "hengegroup-theme")}
        </button>
    );
}

function Edit({ attributes, setAttributes, clientId }) {
    const { heading, headingTag, text } = attributes;
    // Full width in the editor iframe (theme.json contentSize would otherwise apply).
    const blockProps = useBlockProps({
        className:
            "alignfull bg-grey-dark bg-[radial-gradient(ellipse_60%_50%_at_20%_20%,rgba(255,255,255,0.08),transparent_60%),radial-gradient(ellipse_50%_40%_at_80%_70%,rgba(255,255,255,0.05),transparent_60%)] py-16 shadow-[inset_0_40px_40px_-40px_rgba(0,0,0,0.5),inset_0_-40px_40px_-40px_rgba(0,0,0,0.5)] md:py-24 lg:py-25",
    });
    const innerBlocksProps = useInnerBlocksProps(
        {
            className:
                "col-span-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-3 [&>.block-list-appender]:!static [&>.block-list-appender]:!m-0 [&>.block-list-appender]:!w-full",
        },
        {
            allowedBlocks: [benefitMetadata.name],
            template: [[benefitMetadata.name]],
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
                <div className="wrapper gap-y-12">
                    <div className="col-span-12 max-w-3xl">
                        <RichText
                            tagName={headingTag}
                            className="text-grey-light mb-5 text-4xl leading-tight font-semibold"
                            value={toRichTextValue(heading)}
                            onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
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

function BenefitEdit({ attributes, setAttributes }) {
    const { icon, title, text } = attributes;
    const blockProps = useBlockProps({ className: "flex items-start gap-4" });
    const iconKey = ICONS[icon] ? icon : DEFAULT_ICON;

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
            <li {...blockProps}>
                <span
                    className="from-henge-grey via-henge-green to-henge-blue flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-135"
                    title={ICONS[iconKey]?.label}
                    // SVG from the theme's own sanitized Lucide files, not user input.
                    dangerouslySetInnerHTML={{ __html: ICONS[iconKey]?.svg || "" }}
                />
                <div className="min-w-0 flex-1">
                    <RichText
                        tagName="h3"
                        className="text-grey-light mb-1.5 text-lg font-bold"
                        value={toRichTextValue(title)}
                        onChange={(html) => setAttributes({ title: fromRichTextValue(html) })}
                        placeholder={__("Titel eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
                        disableLineBreaks
                    />
                    <RichText
                        tagName="p"
                        className="text-grey-light/85 text-base leading-normal"
                        value={toRichTextValue(text)}
                        onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                        placeholder={__("Text eingeben…", "hengegroup-theme")}
                        allowedFormats={[]}
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

registerBlockType(benefitMetadata, {
    edit: BenefitEdit,
    save: () => null,
});
