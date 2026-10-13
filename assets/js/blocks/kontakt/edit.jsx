// Editor for "Kontakt": heading/text as RichText, card + form via ServerSideRender of
// kontakt-vorschau (a preview of this block would show the texts twice).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/kontakt/block.json";
import vorschauMetadata from "../../../../template-parts/blocks/kontakt-vorschau/block.json";
import { ACCENT_FORMATS } from "../shared.jsx";

// Texts stay plain strings; render.php escapes them.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

function Edit({ attributes, setAttributes }) {
    const { heading, text } = attributes;
    const blockProps = useBlockProps({ className: "alignfull py-16 md:py-25" });

    return (
        <Fragment>
            <section {...blockProps}>
                <div className="wrapper mb-12">
                    <div className="col-span-12 max-w-3xl">
                        <RichText
                            tagName="h2"
                            className="mb-5 text-4xl leading-tight font-semibold lg:text-[42px]"
                            value={heading}
                            onChange={(html) => setAttributes({ heading: html })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={ACCENT_FORMATS}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className="text-lg leading-normal lg:text-[22px] lg:leading-[1.4]"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                    </div>
                </div>
                <ServerSideRender block={vorschauMetadata.name} />
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});

// Minimal client registration so ServerSideRender accepts the block.
registerBlockType(vorschauMetadata, {
    edit: () => null,
    save: () => null,
});
