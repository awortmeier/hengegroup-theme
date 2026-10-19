// Editor for "Kontakt": heading/text as RichText, card data in the sidebar, card + form via
// ServerSideRender of kontakt-vorschau (a preview of this block would show the texts twice).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import {
    PanelBody,
    SelectControl,
    TextControl,
    TextareaControl,
    ToolbarGroup,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/kontakt/block.json";
import vorschauMetadata from "../../../../template-parts/blocks/kontakt-vorschau/block.json";
import {
    ACCENT_FORMATS,
    fromRichTextValue,
    sectionBackground,
    SectionBackgroundDropdown,
    SectionHeaderInner,
    sectionHeaderClassNames,
    toRichTextValue,
    sectionSpacingClassName,
    SectionSpacingDropdown,
    ACCENT_OPTIONS,
} from "../shared.jsx";

const CONTACT_FIELDS = [
    ["contactName", __("Name", "hengegroup-theme")],
    ["contactEmail", __("E-Mail", "hengegroup-theme")],
    ["contactPhone", __("Telefon", "hengegroup-theme")],
    ["contactFax", __("Fax", "hengegroup-theme")],
];

function Edit({ attributes, setAttributes }) {
    const { heading, text, accent, contactAddress } = attributes;
    const background = sectionBackground(attributes.background);
    const previewAttributes = {
        accent,
        background: background.name,
        contactAddress,
        ...Object.fromEntries(CONTACT_FIELDS.map(([key]) => [key, attributes[key]])),
    };
    const blockProps = useBlockProps({
        className: `alignfull flex flex-col gap-10 ${sectionSpacingClassName(attributes.spacing)} ${background.className}`,
    });
    const headerClasses = sectionHeaderClassNames({ color: background.color });

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
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
            <InspectorControls>
                <PanelBody title={__("Kontaktkarte", "hengegroup-theme")} initialOpen>
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Akzentfarbe", "hengegroup-theme")}
                        value={accent}
                        options={ACCENT_OPTIONS}
                        onChange={(value) => setAttributes({ accent: value })}
                    />
                    <p>
                        {__(
                            "Alle Felder leer = Daten aus Einstellungen > Footer.",
                            "hengegroup-theme"
                        )}
                    </p>
                    {CONTACT_FIELDS.map(([key, label]) => (
                        <TextControl
                            key={key}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            label={label}
                            value={attributes[key]}
                            onChange={(value) => setAttributes({ [key]: value })}
                        />
                    ))}
                    <TextareaControl
                        __nextHasNoMarginBottom
                        label={__("Adresse (eine Zeile pro Zeile)", "hengegroup-theme")}
                        value={contactAddress}
                        onChange={(value) => setAttributes({ contactAddress: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper">
                    <SectionHeaderInner
                        color={background.color}
                        className="col-span-12 max-w-3xl"
                        title={
                            <RichText
                                tagName="h2"
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
                                placeholder={__("Text eingeben…", "hengegroup-theme")}
                                allowedFormats={[]}
                            />
                        }
                    />
                </div>
                <ServerSideRender block={vorschauMetadata.name} attributes={previewAttributes} />
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
