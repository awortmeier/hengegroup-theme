// Editor for "Produkte": heading/text/button text as RichText in the canvas; heading tag and button
// link (LinkControl popover, like core/button) in the block toolbar, bound to block selection
// (a focus-bound toolbar would disappear on blur before the click). The product grid is a
// ServerSideRender of produkte-raster with only productIds. The sidebar holds the product picker.
// `[&>div]:contents` keeps the SSR wrapper from breaking the .wrapper grid.
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
    Button,
    ComboboxControl,
    PanelBody,
    PanelRow,
    Popover,
    ToolbarButton,
    ToolbarDropdownMenu,
    ToolbarGroup,
    __experimentalVStack as VStack,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { decodeEntities } from "@wordpress/html-entities";
import { __, sprintf } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/produkte/block.json";
import produkteRasterMetadata from "../../../../template-parts/blocks/produkte-raster/block.json";

// Texts stay plain strings (render.php escapes them); create()/toHTMLString() only handles entity
// escaping inside RichText.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

// Only the semantic element is selectable; the visual size stays headline-base.
const HEADING_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Überschrift 3 (H3)", "hengegroup-theme"), value: "h3" },
    { title: __("Überschrift 4 (H4)", "hengegroup-theme"), value: "h4" },
    { title: __("Überschrift 5 (H5)", "hengegroup-theme"), value: "h5" },
    { title: __("Überschrift 6 (H6)", "hengegroup-theme"), value: "h6" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

// Loads all published products once, then filters locally.
function ProductPicker({ productIds, onChange }) {
    const products = useSelect(
        (select) =>
            select("core").getEntityRecords("postType", "product", {
                per_page: -1,
                status: "publish",
                orderby: "title",
                order: "asc",
            }),
        []
    );

    const [comboboxValue, setComboboxValue] = useState("");

    const productsById = new Map((products || []).map((product) => [product.id, product]));

    const addOptions = (products || [])
        .filter((product) => !productIds.includes(product.id))
        .map((product) => ({
            value: String(product.id),
            label: decodeEntities(product.title.rendered) || String(product.id),
        }));

    function productLabel(productId) {
        const product = productsById.get(productId);

        return product
            ? decodeEntities(product.title.rendered) || String(productId)
            : sprintf(__("Produkt #%d", "hengegroup-theme"), productId);
    }

    function addProduct(value) {
        const productId = parseInt(value, 10);

        if (!Number.isNaN(productId) && !productIds.includes(productId)) {
            onChange([...productIds, productId]);
        }

        setComboboxValue("");
    }

    function removeProduct(index) {
        const nextProductIds = productIds.slice();
        nextProductIds.splice(index, 1);
        onChange(nextProductIds);
    }

    function moveProduct(index, direction) {
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= productIds.length) {
            return;
        }

        const nextProductIds = productIds.slice();
        const [moved] = nextProductIds.splice(index, 1);
        nextProductIds.splice(targetIndex, 0, moved);
        onChange(nextProductIds);
    }

    return (
        <VStack spacing={3}>
            <ComboboxControl
                __nextHasNoMarginBottom
                label={__("Produkt hinzufügen", "hengegroup-theme")}
                value={comboboxValue}
                onChange={addProduct}
                options={addOptions}
            />
            {productIds.map((productId, index) => (
                <PanelRow key={productId}>
                    {productLabel(productId)}
                    <Button
                        icon="arrow-up-alt2"
                        label={__("Nach oben", "hengegroup-theme")}
                        onClick={() => moveProduct(index, -1)}
                        disabled={index === 0}
                    />
                    <Button
                        icon="arrow-down-alt2"
                        label={__("Nach unten", "hengegroup-theme")}
                        onClick={() => moveProduct(index, 1)}
                        disabled={index === productIds.length - 1}
                    />
                    <Button
                        icon="trash"
                        isDestructive
                        label={__("Entfernen", "hengegroup-theme")}
                        onClick={() => removeProduct(index)}
                    />
                </PanelRow>
            ))}
        </VStack>
    );
}

// Mirrors button.php grey-light/lg (on a RichText span; the link is set via the toolbar).
const BUTTON_PREVIEW_CLASSNAME =
    "inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-full px-7 text-lg font-medium whitespace-nowrap !bg-grey-light !text-grey-light-foreground";

function Edit({ attributes, setAttributes, isSelected }) {
    const { heading, headingTag, text, buttonText, buttonUrl, productIds } = attributes;
    // alignfull without supports.align: theme.json contentSize would otherwise narrow the block.
    const blockProps = useBlockProps({
        className: "alignfull bg-grey-dark py-16 md:py-24 lg:py-35",
    });
    const buttonRef = useRef();
    const [isEditingButtonUrl, setIsEditingButtonUrl] = useState(false);

    // Close the link popover when the block loses selection, otherwise it reopens on reselect.
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
                <PanelBody title={__("Produkte", "hengegroup-theme")} initialOpen>
                    <ProductPicker
                        productIds={productIds}
                        onChange={(nextProductIds) => setAttributes({ productIds: nextProductIds })}
                    />
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <div className="col-span-12 max-w-2xl">
                        <RichText
                            tagName={headingTag}
                            className="text-grey-light mb-4 text-5xl leading-tight font-semibold"
                            value={toRichTextValue(heading)}
                            onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className={`text-grey-light text-lg leading-normal ${
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
                                    <LinkControl
                                        value={{ url: buttonUrl }}
                                        onChange={(nextValue) =>
                                            setAttributes({ buttonUrl: nextValue.url || "" })
                                        }
                                        settings={[]}
                                    />
                                </Popover>
                            )}
                        </span>
                    </div>
                    {productIds.length === 0 ? (
                        <p className="text-grey-light col-span-12">
                            {__(
                                "Noch keine Produkte ausgewählt — im rechten Bereich Produkte hinzufügen.",
                                "hengegroup-theme"
                            )}
                        </p>
                    ) : (
                        <div className="contents [&>div]:contents">
                            <ServerSideRender
                                block="hengegroup-theme/produkte-raster"
                                attributes={{ productIds }}
                            />
                        </div>
                    )}
                </div>
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});

// ServerSideRender checks the client registry first, so produkte-raster needs a minimal client
// registration in addition to the server one.
registerBlockType(produkteRasterMetadata, {
    edit: () => null,
    save: () => null,
});
