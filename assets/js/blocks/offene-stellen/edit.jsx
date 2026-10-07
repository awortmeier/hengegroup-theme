// Editor UI for template-parts/blocks/offene-stellen/block.json. Same Vite/@wordpress-globals
// architecture as the other blocks (see blocks/buehne/edit.jsx's header comment) -- built via
// vite.config.editor-offene-stellen.js.
//
// Der Block hat keinen eigenen Text-Inhalt: die Stellen kommen aus dem Post-Type
// "stellenangebote", der Ansprechpartner aus Karriere > Einstellungen. Die Canvas zeigt deshalb
// eine echte `ServerSideRender`-Vorschau (gleiche Daten wie im Frontend), die Sidebar nur die
// beiden Darstellungs-Optionen.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/offene-stellen/block.json";

function Edit({ attributes, setAttributes }) {
    const { showContact, contactHeading } = attributes;
    // Volle Breite im Editor-Iframe, gleiche Begruendung wie produkte/edit.jsx's `alignfull`.
    const blockProps = useBlockProps({ className: "alignfull" });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Ansprechpartner", "hengegroup-theme")} initialOpen>
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__("Ansprechpartner-Karte anzeigen", "hengegroup-theme")}
                        help={__(
                            "Name, Funktion und E-Mail werden unter Karriere > Einstellungen gepflegt.",
                            "hengegroup-theme"
                        )}
                        checked={showContact}
                        onChange={(value) => setAttributes({ showContact: value })}
                    />
                    {showContact && (
                        <TextControl
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            label={__("Überschrift", "hengegroup-theme")}
                            value={contactHeading}
                            onChange={(value) => setAttributes({ contactHeading: value })}
                        />
                    )}
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <ServerSideRender block={metadata.name} attributes={attributes} />
            </div>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
