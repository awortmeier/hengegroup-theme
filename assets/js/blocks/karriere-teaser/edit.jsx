// Editor for "Karriere-Teaser" (same pattern as produkte/edit.jsx): RichText texts, heading tag and
// link via toolbar, job list via ServerSideRender of karriere-teaser-liste. An empty link means the
// career page, so the popover shows a hint. Companies come from window.hengegroupThemeJobCompanies.
import { createElement as el, Fragment, useEffect, useRef, useState } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import {
    BlockControls,
    InspectorControls,
    LinkControl,
    RichText,
    useBlockProps,
} from "@wordpress/block-editor";
import {
    PanelBody,
    Popover,
    RangeControl,
    SelectControl,
    TextareaControl,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/karriere-teaser/block.json";
import listeMetadata from "../../../../template-parts/blocks/karriere-teaser-liste/block.json";
import {
    ACCENT_FORMATS,
    buttonPreviewClassName,
    SectionHeaderInner,
    sectionHeaderClassNames,
    sectionBackground,
    SectionBackgroundDropdown,
    sectionSpacingClassName,
    SectionSpacingDropdown,
} from "../shared.jsx";

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

const companies = Array.isArray(window.hengegroupThemeJobCompanies)
    ? window.hengegroupThemeJobCompanies
    : [];

function Edit({ attributes, setAttributes, isSelected }) {
    const {
        heading,
        headingTag,
        text,
        buttonText,
        buttonUrl,
        limit,
        companyId,
        variant,
        emptyText,
    } = attributes;
    // The background is stored as `variant` (existing content).
    const background = sectionBackground(variant);
    const headerClasses = sectionHeaderClassNames({ size: "base", color: background.color });
    const blockProps = useBlockProps({
        className: `alignfull ${sectionSpacingClassName(attributes.spacing)} ${background.className}`,
    });
    const buttonRef = useRef();
    const [isEditingButtonUrl, setIsEditingButtonUrl] = useState(false);

    useEffect(() => {
        if (!isSelected) {
            setIsEditingButtonUrl(false);
        }
    }, [isSelected]);

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
                    <SectionBackgroundDropdown
                        value={background.name}
                        onChange={(value) => setAttributes({ variant: value })}
                    />
                    <SectionSpacingDropdown
                        value={attributes.spacing}
                        onChange={(value) => setAttributes({ spacing: value })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="admin-links"
                        label={__("Button-Link bearbeiten", "hengegroup-theme")}
                        onClick={() => setIsEditingButtonUrl(true)}
                        isPressed={isEditingButtonUrl}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Stellen", "hengegroup-theme")} initialOpen>
                    <RangeControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Anzahl angezeigter Stellen", "hengegroup-theme")}
                        help={__(
                            "Die neuesten aktiven Stellen; abgelaufene werden automatisch ausgeblendet.",
                            "hengegroup-theme"
                        )}
                        min={1}
                        max={10}
                        value={limit}
                        onChange={(value) => setAttributes({ limit: value || 4 })}
                    />
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Unternehmen", "hengegroup-theme")}
                        value={String(companyId || 0)}
                        options={[
                            { value: "0", label: __("Alle Unternehmen", "hengegroup-theme") },
                            ...companies.map((company) => ({
                                value: String(company.id),
                                label: company.name,
                            })),
                        ]}
                        onChange={(value) => setAttributes({ companyId: parseInt(value, 10) || 0 })}
                    />
                    <TextareaControl
                        __nextHasNoMarginBottom
                        label={__("Text ohne offene Stellen", "hengegroup-theme")}
                        help={
                            companyId
                                ? __(
                                      "Mit Unternehmen wird die Sektion ohne offene Stellen auf der Website ausgeblendet.",
                                      "hengegroup-theme"
                                  )
                                : __("Leer lassen = kein Hinweis.", "hengegroup-theme")
                        }
                        value={emptyText}
                        onChange={(value) => setAttributes({ emptyText: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper items-start gap-y-10">
                    <SectionHeaderInner
                        size="base"
                        color={background.color}
                        className="col-span-12 lg:col-span-6 lg:pr-8"
                        title={
                            <RichText
                                tagName={headingTag}
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
                        actions={
                            <span ref={buttonRef} className="relative inline-block">
                                <RichText
                                    tagName="span"
                                    className={buttonPreviewClassName(
                                        background.dark ? "grey-light" : "grey-dark",
                                        "lg"
                                    )}
                                    value={toRichTextValue(buttonText)}
                                    onChange={(html) =>
                                        setAttributes({ buttonText: fromRichTextValue(html) })
                                    }
                                    placeholder={__("Button-Text eingeben…", "hengegroup-theme")}
                                    allowedFormats={[]}
                                    disableLineBreaks
                                />
                                {isEditingButtonUrl && isSelected && (
                                    <Popover
                                        placement="bottom-start"
                                        anchor={buttonRef.current}
                                        onClose={() => setIsEditingButtonUrl(false)}
                                    >
                                        {/* Outside the editor iframe, no Tailwind there. */}
                                        <p
                                            style={{
                                                margin: 0,
                                                padding: "12px 16px 0",
                                                fontSize: "12px",
                                                color: "#757575",
                                            }}
                                        >
                                            {__(
                                                "Leer lassen = Karriereseite (Karriere > Einstellungen), Abschnitt „Offene Stellen“.",
                                                "hengegroup-theme"
                                            )}
                                        </p>
                                        <LinkControl
                                            value={{ url: buttonUrl }}
                                            onChange={(nextValue) =>
                                                setAttributes({ buttonUrl: nextValue.url || "" })
                                            }
                                            onRemove={() => setAttributes({ buttonUrl: "" })}
                                            settings={[]}
                                        />
                                    </Popover>
                                )}
                            </span>
                        }
                    />
                    <div className="col-span-12 lg:col-span-6">
                        <ServerSideRender
                            block={listeMetadata.name}
                            attributes={{
                                limit,
                                companyId,
                                emptyText,
                                background: background.name,
                            }}
                        />
                    </div>
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});

// Minimal client registration so ServerSideRender accepts the block.
registerBlockType(listeMetadata, {
    edit: () => null,
    save: () => null,
});
