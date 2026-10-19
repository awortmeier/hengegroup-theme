// Editor for "Anwendungsgruppe": group and section head in the sidebar; ServerSideRender
// preview. Groups (with head defaults) come from window.hengegroupThemeAnwendungGroups.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, Placeholder, SelectControl, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/anwendungsgruppe/block.json";
import {
    HeadingTagDropdown,
    SectionBackgroundDropdown,
    SectionIntroPanel,
    sectionSpacingClassName,
    SectionSpacingDropdown,
} from "../shared.jsx";

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
    const { groupId, background, headingTag } = attributes;
    const selected = groups.find((group) => group.id === groupId);
    const blockProps = useBlockProps({ className: "alignfull" });

    return (
        <Fragment>
            {selected && (
                <BlockControls group="block">
                    <ToolbarGroup>
                        <HeadingTagDropdown
                            value={headingTag}
                            onChange={(value) => setAttributes({ headingTag: value })}
                        />
                        <SectionBackgroundDropdown
                            value={background}
                            onChange={(value) => setAttributes({ background: value })}
                        />
                        <SectionSpacingDropdown
                            value={attributes.spacing}
                            onChange={(value) => setAttributes({ spacing: value })}
                        />
                    </ToolbarGroup>
                </BlockControls>
            )}
            <InspectorControls>
                <PanelBody title={__("Einstellungen", "hengegroup-theme")} initialOpen>
                    <GroupSelect
                        value={groupId}
                        onChange={(next) => setAttributes({ groupId: next })}
                    />
                </PanelBody>
                {selected && (
                    <SectionIntroPanel
                        attributes={attributes}
                        setAttributes={setAttributes}
                        defaults={selected.intro}
                    />
                )}
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
