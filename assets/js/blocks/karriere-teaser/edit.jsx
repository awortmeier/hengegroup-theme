// Editor UI for template-parts/blocks/karriere-teaser/block.json. Gleiches Muster wie
// blocks/produkte/edit.jsx (siehe dessen Kopfkommentar fuer die ausfuehrliche Begruendung):
// Ueberschrift/Text/Button-Text sind natives `RichText` direkt in der Canvas, das
// Ueberschrift-Element (h1-h6/p) und der Button-Link laufen ueber die Block-Toolbar
// (`ToolbarDropdownMenu` bzw. `LinkControl` im `Popover`), die Sidebar haelt nur die Konfiguration
// (Anzahl Stellen). Die Stellenliste rechts ist eine echte `ServerSideRender`-Vorschau gegen den
// inserter-versteckten Zwillingsblock `hengegroup-theme/karriere-teaser-liste` (nur `limit` als
// Attribut, damit Tastatureingaben links keinen REST-Request ausloesen) -- dieser Block wird unten
// zusaetzlich clientseitig registriert, siehe produkte/edit.jsx fuer den Grund.
//
// Leere Button-URL = Karriereseite aus Karriere > Einstellungen (render.php), deshalb zeigt der
// Link-Popover einen Hinweis statt einer leeren Eingabe als "fehlt".
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
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/karriere-teaser/block.json";
import listeMetadata from "../../../../template-parts/blocks/karriere-teaser-liste/block.json";

// Gleicher Plain-String-Rundlauf wie produkte/edit.jsx.
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

// 1:1 aus template-parts/base/button.php's `variant: 'grey-dark'`/`size: 'lg'` gespiegelt.
const BUTTON_PREVIEW_CLASSNAME =
    "inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-full px-7 text-lg font-medium whitespace-nowrap !bg-grey-dark !text-grey-dark-foreground";

function Edit({ attributes, setAttributes, isSelected }) {
    const { heading, headingTag, text, buttonText, buttonUrl, limit } = attributes;
    const blockProps = useBlockProps({
        className: "alignfull bg-grey-light py-16 md:py-24 lg:py-25",
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
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper items-start gap-y-10">
                    <div className="col-span-12 lg:col-span-6 lg:pr-8">
                        <RichText
                            tagName={headingTag}
                            className="mb-5 text-5xl leading-tight font-semibold"
                            value={toRichTextValue(heading)}
                            onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className={`text-2xl leading-normal ${
                                buttonText.trim() !== "" ? "mb-8" : ""
                            }`}
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                        <span ref={buttonRef} className="relative inline-block">
                            <RichText
                                tagName="span"
                                className={BUTTON_PREVIEW_CLASSNAME}
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
                                    {/* Popover sitzt ausserhalb des Editor-Iframes (kein
                                    Theme-Tailwind dort) -- Inline-Style ist hier reine
                                    wp-admin-UI, gleiche Abgrenzung wie theme-seo-admin.php. */}
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
                    </div>
                    <div className="col-span-12 lg:col-span-6">
                        <ServerSideRender block={listeMetadata.name} attributes={{ limit }} />
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

// Clientseitige Minimal-Registrierung des Vorschau-Zwillings, siehe produkte/edit.jsx.
registerBlockType(listeMetadata, {
    edit: () => null,
    save: () => null,
});
