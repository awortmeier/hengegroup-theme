// Editor UI for template-parts/blocks/stellen-liste/block.json -- eine Liste der Stellenanzeige mit
// fester Ueberschrift je Typ (Wir bieten dir/Dein Profil/Deine Aufgaben), Typ ueber die
// Block-Toolbar, darin eine normale `core/list` (InnerBlocks) -- Listenpunkte tippt man wie in jeder
// Gutenberg-Liste (Enter = neuer Punkt). Siehe render.php fuer die Begruendung und den
// Rueckgriff auf die Unternehmens-Benefits.
//
// Die Ueberschriften kommen per Inline-Script aus PHP (`window.hengegroupThemeJobListTypes`,
// hengegroup_theme_get_job_list_types()) -- dieselben Texte wie im Frontend, keine Kopie hier.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InnerBlocks,
    useBlockProps,
    useInnerBlocksProps,
} from "@wordpress/block-editor";
import { ToolbarDropdownMenu, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import metadata from "../../../../template-parts/blocks/stellen-liste/block.json";

const TYPES = window.hengegroupThemeJobListTypes || {};

function Edit({ attributes, setAttributes }) {
    const { type } = attributes;
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
                <h2 className="mb-3 text-2xl leading-normal font-bold">{TYPES[type] || type}</h2>
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
    // Dynamischer Block (render.php), gespeichert wird nur die innere Liste.
    save: () => <InnerBlocks.Content />,
});
