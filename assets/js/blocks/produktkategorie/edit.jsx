// Editor for "Produktkategorie": category, background and section head in the sidebar;
// ServerSideRender preview. Categories (with head defaults) come from
// window.hengegroupThemeProductCategories.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, Placeholder, SelectControl, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/produktkategorie/block.json";
import { HeadingTagDropdown, SectionIntroPanel } from "../shared.jsx";

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
    const { categoryId, background, headingTag } = attributes;
    const selected = categories.find((category) => category.id === categoryId);
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
                    </ToolbarGroup>
                </BlockControls>
            )}
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
