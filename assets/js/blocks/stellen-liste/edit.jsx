// Editor for "Stellen-Liste": type via toolbar, heading as RichText, core/list inside. Default
// headings come from window.hengegroupThemeJobListTypes.
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
import metadata from "../../../../template-parts/blocks/stellen-liste/block.json";
import { fromRichTextValue, toRichTextValue } from "../shared.jsx";

const TYPES = window.hengegroupThemeJobListTypes || {};

function Edit({ attributes, setAttributes }) {
    const { type, heading } = attributes;
    const blockProps = useBlockProps({
        className:
            "my-6 text-base text-grey-dark [&_li]:leading-[1.7] [&_ul]:list-disc [&_ul]:pl-5",
    });
    const innerBlocksProps = useInnerBlocksProps(
        {},
        {
            allowedBlocks: ["core/list"],
            template: [["core/list"]],
            renderAppender: false,
        }
    );

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="editor-ul"
                        label={__("Listen-Typ ändern", "hengegroup-theme")}
                        controls={Object.entries(TYPES).map(([value, label]) => ({
                            title: label,
                            isActive: value === type,
                            onClick: () => setAttributes({ type: value }),
                        }))}
                    />
                </ToolbarGroup>
            </BlockControls>
            <section {...blockProps}>
                <RichText
                    tagName="h2"
                    className="mb-3 text-2xl leading-normal font-bold"
                    value={toRichTextValue(heading)}
                    onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                    placeholder={TYPES[type] || type}
                    allowedFormats={[]}
                    disableLineBreaks
                />
                {type === "benefits" && (
                    <p className="mb-2 text-sm text-neutral-500">
                        {__(
                            "Leer lassen = Standard-Benefits des Unternehmens werden angezeigt.",
                            "hengegroup-theme"
                        )}
                    </p>
                )}
                <div {...innerBlocksProps} />
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    // Dynamic block; only the inner list is saved.
    save: () => <InnerBlocks.Content />,
});
