// Editor UI for template-parts/blocks/anwendungsgruppe/block.json. Same Vite/@wordpress-globals
// architecture as the other blocks (see blocks/buehne/edit.jsx's header comment) -- built via
// vite.config.editor-anwendungsgruppe.js.
//
// Gleiches Muster wie produktkategorie/edit.jsx: Kicker, Ueberschrift, Beschreibung und Anwendungen
// kommen aus der Gruppe (Produkte > Anwendungen), der Block waehlt nur Gruppe und Hintergrund; die
// Canvas zeigt die echte Ausgabe per `ServerSideRender`. Die Gruppenliste kommt per Inline-Script
// (window.hengegroupThemeAnwendungGroups, inc/setup/theme-blocks.php).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, Placeholder, SelectControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/anwendungsgruppe/block.json";

const groups = Array.isArray(window.hengegroupThemeAnwendungGroups)
    ? window.hengegroupThemeAnwendungGroups
    : [];

function GroupSelect({ value, onChange }) {
    return (
        <SelectControl
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            label={__("Anwendungsgruppe", "hengegroup-theme")}
            value={String(value || 0)}
            options={[
                { value: "0", label: __("— Gruppe wählen —", "hengegroup-theme") },
                ...groups.map((group) => ({ value: String(group.id), label: group.name })),
            ]}
            onChange={(next) => onChange(parseInt(next, 10) || 0)}
        />
    );
}

function Edit({ attributes, setAttributes }) {
    const { groupId, background } = attributes;
    const blockProps = useBlockProps({ className: "alignfull" });

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={__("Einstellungen", "hengegroup-theme")} initialOpen>
                    <GroupSelect
                        value={groupId}
                        onChange={(next) => setAttributes({ groupId: next })}
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
                            "Kicker, Überschrift, Text und die Anwendungen werden unter Produkte > Anwendungen gepflegt.",
                            "hengegroup-theme"
                        )}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {groupId > 0 ? (
                    <ServerSideRender block={metadata.name} attributes={attributes} />
                ) : (
                    <Placeholder
                        icon="screenoptions"
                        label={__("Anwendungsgruppe", "hengegroup-theme")}
                        instructions={__(
                            "Wählen Sie die Gruppe, deren Anwendungen hier erscheinen sollen.",
                            "hengegroup-theme"
                        )}
                    >
                        <GroupSelect
                            value={groupId}
                            onChange={(next) => setAttributes({ groupId: next })}
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
