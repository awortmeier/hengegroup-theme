// Editor UI for template-parts/blocks/produktkategorie/block.json. Same Vite/@wordpress-globals
// architecture as the other blocks (see blocks/buehne/edit.jsx's header comment) -- built via
// vite.config.editor-produktkategorie.js.
//
// Bewusst schlank: Kicker, Ueberschrift, Beschreibung und Produkte kommen aus der Produktkategorie
// selbst (Produkte > Kategorien), der Block waehlt nur Kategorie und Hintergrund. Die Canvas zeigt
// deshalb die echte Ausgabe per `ServerSideRender` -- es gibt hier nichts direkt zu tippen, anders
// als bei produkte/offene-stellen. Die Kategorienliste kommt per Inline-Script
// (window.hengegroupThemeProductCategories, inc/setup/theme-blocks.php) statt per REST-Abfrage.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, Placeholder, SelectControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/produktkategorie/block.json";

const categories = Array.isArray(window.hengegroupThemeProductCategories)
    ? window.hengegroupThemeProductCategories
    : [];

function CategorySelect({ value, onChange }) {
    return (
        <SelectControl
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            label={__("Produktkategorie", "hengegroup-theme")}
            value={String(value || 0)}
            options={[
                { value: "0", label: __("— Kategorie wählen —", "hengegroup-theme") },
                ...categories.map((category) => ({
                    value: String(category.id),
                    label: category.name,
                })),
            ]}
            onChange={(next) => onChange(parseInt(next, 10) || 0)}
        />
    );
}

function Edit({ attributes, setAttributes }) {
    const { categoryId, background } = attributes;
    const blockProps = useBlockProps({ className: "alignfull" });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Einstellungen", "hengegroup-theme")} initialOpen>
                    <CategorySelect
                        value={categoryId}
                        onChange={(next) => setAttributes({ categoryId: next })}
                    />
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Hintergrund", "hengegroup-theme")}
                        value={background}
                        options={[
                            { value: "light", label: __("Hell", "hengegroup-theme") },
                            { value: "muted", label: __("Grau", "hengegroup-theme") },
                        ]}
                        onChange={(next) => setAttributes({ background: next })}
                        help={__(
                            "Kicker, Überschrift, Text und Ansprechpartner werden unter Produkte > Kategorien gepflegt.",
                            "hengegroup-theme"
                        )}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {categoryId > 0 ? (
                    <ServerSideRender block={metadata.name} attributes={attributes} />
                ) : (
                    <Placeholder
                        icon="category"
                        label={__("Produktkategorie", "hengegroup-theme")}
                        instructions={__(
                            "Wählen Sie die Kategorie, deren Produkte hier erscheinen sollen.",
                            "hengegroup-theme"
                        )}
                    >
                        <CategorySelect
                            value={categoryId}
                            onChange={(next) => setAttributes({ categoryId: next })}
                        />
                    </Placeholder>
                )}
            </div>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});
