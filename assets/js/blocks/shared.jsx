// Editor helpers shared by the block editors (bundled into each block's IIFE).
import { createElement as el, Fragment } from "@wordpress/element";
import { createBlock, getBlockVariations } from "@wordpress/blocks";
import { MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";
import {
    Dropdown,
    MenuGroup,
    MenuItem,
    PanelBody,
    SelectControl,
    TextareaControl,
    TextControl,
    ToolbarButton,
    ToolbarDropdownMenu,
} from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import CARD from "../../../template-parts/base/card-classes.json";

// Texts stay plain strings; render.php escapes them.
export function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

export function fromRichTextValue(html) {
    return create({ html }).text;
}

// Format from editor-customizations.js; render.php keeps only this one (accent_markup).
export const ACCENT_FORMATS = ["hengegroup-theme/akzent"];

const HEADING_TAG_OPTIONS = [
    { title: __("Überschrift 1 (H1)", "hengegroup-theme"), value: "h1" },
    { title: __("Überschrift 2 (H2)", "hengegroup-theme"), value: "h2" },
    { title: __("Überschrift 3 (H3)", "hengegroup-theme"), value: "h3" },
    { title: __("Überschrift 4 (H4)", "hengegroup-theme"), value: "h4" },
    { title: __("Überschrift 5 (H5)", "hengegroup-theme"), value: "h5" },
    { title: __("Überschrift 6 (H6)", "hengegroup-theme"), value: "h6" },
    { title: __("Absatz (P)", "hengegroup-theme"), value: "p" },
];

export function HeadingTagDropdown({ value, onChange }) {
    return (
        <ToolbarDropdownMenu
            icon="heading"
            label={__("Überschrift-Element ändern", "hengegroup-theme")}
            controls={HEADING_TAG_OPTIONS.map((option) => ({
                title: option.title,
                isActive: option.value === value,
                onClick: () => onChange(option.value),
            }))}
        />
    );
}

// Mirrors hengegroup_theme_get_accent_classes() (literals for the Tailwind scanner).
export const ACCENT_CLASSES = {
    "henge-grey": {
        bg: "bg-henge-grey",
        border: "border-henge-grey",
        fg: "text-henge-grey-foreground",
    },
    "henge-green": {
        bg: "bg-henge-green",
        border: "border-henge-green",
        fg: "text-henge-green-foreground",
    },
    "henge-blue": {
        bg: "bg-henge-blue",
        border: "border-henge-blue",
        fg: "text-henge-blue-foreground",
    },
    "grey-dark": {
        bg: "bg-grey-dark",
        border: "border-grey-dark",
        fg: "text-grey-dark-foreground",
    },
};

const ACCENT_OPTIONS = [
    { title: __("Blau", "hengegroup-theme"), value: "henge-blue" },
    { title: __("Grün", "hengegroup-theme"), value: "henge-green" },
    { title: __("Grau", "hengegroup-theme"), value: "henge-grey" },
    { title: __("Dunkelgrau", "hengegroup-theme"), value: "grey-dark" },
];

export function accentClasses(value) {
    return ACCENT_CLASSES[value] || ACCENT_CLASSES["grey-dark"];
}

export function AccentDropdown({ value, onChange }) {
    return (
        <ToolbarDropdownMenu
            icon="admin-appearance"
            label={__("Akzentfarbe", "hengegroup-theme")}
            controls={ACCENT_OPTIONS.map((option) => ({
                title: option.title,
                isActive: option.value === value,
                onClick: () => onChange(option.value),
            }))}
        />
    );
}

// Section head of produktkategorie/anwendungsgruppe; empty fields fall back to the term (`defaults`).
export function SectionIntroPanel({ attributes, setAttributes, defaults = {} }) {
    const defaultVariant = ACCENT_OPTIONS.find((option) => option.value === defaults.kickerVariant);
    const textField = (key, label, Control = TextControl) => (
        <Control
            __nextHasNoMarginBottom
            {...(Control === TextControl ? { __next40pxDefaultSize: true } : {})}
            label={label}
            value={attributes[key]}
            placeholder={defaults[key] || ""}
            onChange={(value) => setAttributes({ [key]: value })}
        />
    );

    return (
        <PanelBody title={__("Kopf", "hengegroup-theme")} initialOpen>
            <p>{__("Leere Felder = Angaben der Kategorie bzw. Gruppe.", "hengegroup-theme")}</p>
            {textField("kicker", __("Kicker", "hengegroup-theme"))}
            <SelectControl
                __nextHasNoMarginBottom
                __next40pxDefaultSize
                label={__("Kicker-Farbe", "hengegroup-theme")}
                value={attributes.kickerVariant}
                options={[
                    {
                        value: "",
                        label: defaultVariant
                            ? `${__("Standard", "hengegroup-theme")} (${defaultVariant.title})`
                            : __("Standard", "hengegroup-theme"),
                    },
                    ...ACCENT_OPTIONS.map((option) => ({
                        value: option.value,
                        label: option.title,
                    })),
                ]}
                onChange={(value) => setAttributes({ kickerVariant: value })}
            />
            {textField("heading", __("Überschrift", "hengegroup-theme"))}
            {textField("text", __("Text", "hengegroup-theme"), TextareaControl)}
        </PanelBody>
    );
}

export function useMediaUrl(id, size = "large") {
    const media = useSelect((select) => (id ? select("core").getMedia(id) : null), [id]);

    return media?.media_details?.sizes?.[size]?.source_url || media?.source_url || "";
}

// Opens the media library; `children(open)` renders the trigger.
export function MediaPicker({ value, onSelect, children }) {
    return (
        <MediaUploadCheck>
            <MediaUpload
                onSelect={onSelect}
                allowedTypes={["image"]}
                value={value}
                render={({ open }) => children(open)}
            />
        </MediaUploadCheck>
    );
}

export function MediaToolbarButtons({ value, onSelect, onRemove }) {
    return (
        <MediaPicker value={value} onSelect={onSelect}>
            {(open) => (
                <>
                    <ToolbarButton
                        icon="format-image"
                        label={
                            value
                                ? __("Bild ändern", "hengegroup-theme")
                                : __("Bild auswählen", "hengegroup-theme")
                        }
                        onClick={open}
                    />
                    {value > 0 && (
                        <ToolbarButton
                            icon="no-alt"
                            label={__("Bild entfernen", "hengegroup-theme")}
                            onClick={onRemove}
                        />
                    )}
                </>
            )}
        </MediaPicker>
    );
}

// Mirrors hengegroup_theme_get_tile_grid_class() (literals for the Tailwind scanner).
export function tileGridClass(layout, count, defaultClass) {
    if (layout === "grid-4") {
        return "sm:grid-cols-2 lg:grid-cols-4";
    }

    if (layout !== "full") {
        return defaultClass;
    }

    if (count <= 1) {
        return "";
    }

    if (count === 2) {
        return "sm:grid-cols-2";
    }

    return count === 3 ? "sm:grid-cols-2 lg:grid-cols-3" : "sm:grid-cols-2 lg:grid-cols-4";
}

export function useInnerBlockCount(clientId) {
    return useSelect((select) => select("core/block-editor").getBlockCount(clientId), [clientId]);
}

// `withDefault` adds "Standard" (the block's own grid) for blocks that had one before.
export function TileLayoutDropdown({ value, onChange, withDefault = false }) {
    const options = [
        ...(withDefault ? [{ title: __("Standard", "hengegroup-theme"), value: "default" }] : []),
        { title: __("Volle Breite", "hengegroup-theme"), value: "full" },
        { title: __("4er-Raster", "hengegroup-theme"), value: "grid-4" },
    ];

    return (
        <ToolbarDropdownMenu
            icon="columns"
            label={__("Kachel-Anordnung", "hengegroup-theme")}
            controls={options.map((option) => ({
                title: option.title,
                isActive: option.value === value,
                onClick: () => onChange(option.value),
            }))}
        />
    );
}

// Own "add" tile as the last grid cell (grid needs APPENDER_GRID_CELL_CLASSNAME): Gutenberg's
// appender sits absolutely over the last item. With card variants it asks for one, marking the
// variant of the last card.
const ADD_ITEM_TILE_TONES = {
    light: "rounded-card-raised border-neutral-400 text-neutral-600 hover:border-grey-dark hover:text-grey-dark",
    dark: "rounded-xl border-grey-light/40 text-grey-light/80 hover:border-grey-light hover:text-grey-light",
};

export function AddItemTile({ rootClientId, blockName, label, tone = "light", className = "" }) {
    const { insertBlock } = useDispatch("core/block-editor");
    const lastVariant = useSelect(
        (select) => {
            const { getBlockOrder, getBlockAttributes } = select("core/block-editor");
            const order = getBlockOrder(rootClientId);

            return order.length ? getBlockAttributes(order[order.length - 1])?.variant : undefined;
        },
        [rootClientId]
    );
    const variations = getBlockVariations(blockName, "inserter") || [];
    const insert = (attributes = {}) =>
        insertBlock(createBlock(blockName, attributes), undefined, rootClientId);
    const tileClassName = `flex size-full cursor-pointer items-center justify-center border-2 border-dashed bg-transparent text-base font-semibold ${ADD_ITEM_TILE_TONES[tone]} ${className}`;

    if (variations.length < 2) {
        return (
            <button type="button" className={tileClassName} onClick={() => insert()}>
                {label}
            </button>
        );
    }

    return (
        <Dropdown
            className="block size-full"
            popoverProps={{ placement: "bottom" }}
            renderToggle={({ isOpen, onToggle }) => (
                <button
                    type="button"
                    className={tileClassName}
                    onClick={onToggle}
                    aria-expanded={isOpen}
                >
                    {label}
                </button>
            )}
            renderContent={({ onClose }) => (
                <MenuGroup label={__("Variante", "hengegroup-theme")}>
                    {variations.map((variation) => (
                        <MenuItem
                            key={variation.name}
                            icon={variation.attributes.variant === lastVariant ? "yes" : undefined}
                            iconPosition="right"
                            onClick={() => {
                                insert(variation.attributes);
                                onClose();
                            }}
                        >
                            {CARD_VARIANT_LABELS[variation.attributes.variant]}
                        </MenuItem>
                    ))}
                </MenuGroup>
            )}
        />
    );
}

export const APPENDER_GRID_CELL_CLASSNAME =
    "[&>.block-list-appender]:!static [&>.block-list-appender]:!m-0 [&>.block-list-appender]:!w-full";

export const CARD_VARIANT_LABELS = {
    default: __("Weiß", "hengegroup-theme"),
    filled: __("Einfarbig", "hengegroup-theme"),
    split: __("Zweigeteilt", "hengegroup-theme"),
    ghost: __("Transparent", "hengegroup-theme"),
    glass: __("Glas", "hengegroup-theme"),
};

// Allowed variants are the `variant` enum in block.json, the same list render.php checks.
export function cardVariations(metadata) {
    return metadata.attributes.variant.enum.map((variant, index) => ({
        name: variant,
        title: `${metadata.title} – ${CARD_VARIANT_LABELS[variant]}`,
        attributes: { variant },
        isDefault: index === 0,
        scope: ["inserter", "transform"],
        isActive: (attributes) => attributes.variant === variant,
    }));
}

// Mirrors hengegroup_theme_pick_card_variant().
export function cardVariant(metadata, value, fallback) {
    const allowed = metadata.attributes.variant.enum;

    if (allowed.includes(value)) {
        return value;
    }

    return allowed.includes(fallback) ? fallback : allowed[0];
}

export function CardVariantDropdown({ metadata, value, onChange }) {
    return (
        <ToolbarDropdownMenu
            icon="layout"
            label={__("Kartenvariante", "hengegroup-theme")}
            controls={metadata.attributes.variant.enum.map((variant) => ({
                title: CARD_VARIANT_LABELS[variant],
                isActive: variant === value,
                onClick: () => onChange(variant),
            }))}
        />
    );
}

export function CardToolbar({ metadata, variant, accent, setAttributes, accentFor = ["filled"] }) {
    return (
        <Fragment>
            <CardVariantDropdown
                metadata={metadata}
                value={variant}
                onChange={(value) => setAttributes({ variant: value })}
            />
            {metadata.attributes.accent && accentFor.includes(variant) && (
                <AccentDropdown
                    value={accent}
                    onChange={(value) => setAttributes({ accent: value })}
                />
            )}
        </Fragment>
    );
}

// Mirrors button.php (variant + size, without hover/focus: the preview is RichText).
const BUTTON_PREVIEW_VARIANTS = {
    "grey-dark": "!bg-grey-dark !text-grey-dark-foreground",
    "grey-light": "!bg-grey-light !text-grey-light-foreground",
    outline: "border border-grey-dark !bg-background shadow-xs",
};
const BUTTON_PREVIEW_SIZES = {
    base: "h-8 gap-1.5 px-4 text-base",
    lg: "h-10 gap-2 px-7 text-lg",
};

export function buttonPreviewClassName(variant, size = "base") {
    return `inline-flex shrink-0 items-center justify-center rounded-full font-medium whitespace-nowrap ${BUTTON_PREVIEW_SIZES[size]} ${BUTTON_PREVIEW_VARIANTS[variant]}`;
}

const isDarkCard = (variant) => CARD.darkVariants.includes(variant);

// Mirrors hengegroup_theme_get_card_button_variant().
export function cardButtonVariant(variant) {
    return CARD.button[isDarkCard(variant) ? "dark" : "light"];
}

function cardAccent(variant, accent) {
    return (
        ACCENT_CLASSES[accent] ||
        ACCENT_CLASSES[CARD.defaultAccent[variant] || CARD.defaultAccent.other]
    );
}

// Outer element of card.php; pass it to useBlockProps().
export function cardClassName({
    variant = "default",
    accent,
    accentBorder = false,
    horizontal = false,
    hasMedia = false,
}) {
    return [
        CARD.surface[variant],
        variant === "filled" ? cardAccent(variant, accent).bg : "",
        CARD.root,
        CARD.shape[CARD.plainVariants.includes(variant) ? "plain" : "raised"],
        horizontal && hasMedia ? CARD.horizontal : "",
        hasMedia || variant === "split" ? CARD.clip : "",
        accentBorder ? `${CARD.accentBorder} ${cardAccent(variant, accent).border}` : "",
    ]
        .filter(Boolean)
        .join(" ");
}

// Mirrors typography.php for the card title variants.
const CARD_TITLE_CLASSES = {
    "body-lg": "text-2xl leading-normal",
    "body-base": "text-lg leading-normal",
    "body-sm": "text-base leading-normal",
};

export function cardTitleClassName(variant, titleVariant = "body-lg") {
    return `${CARD_TITLE_CLASSES[titleVariant]} ${CARD.title} ${isDarkCard(variant) ? "text-grey-light" : ""}`;
}

export function cardDescriptionClassName(variant) {
    const tone = isDarkCard(variant) ? "dark" : variant === "ghost" ? "ghost" : "light";

    return `text-base leading-normal ${CARD.description[tone]}`;
}

// Subheading inside a card, as hengegroup_theme_render_card_subheading().
export const CARD_SUBHEADING_CLASSNAME = `text-lg leading-normal ${CARD.title}`;

// Inner markup of card.php; slots are editor nodes (RichText etc.), the outer element comes from
// the block (cardClassName() + useBlockProps()).
export function CardInner({
    variant = "default",
    accent,
    horizontal = false,
    media,
    kicker,
    icon,
    iconClassName,
    leading,
    title,
    description,
    content,
    footer,
}) {
    const padding = variant === "ghost" ? "" : CARD.padding;
    const leadingNode = icon ? (
        <span
            className={`${CARD.iconBox} ${iconClassName || CARD.icon[isDarkCard(variant) ? "dark" : "light"]}`}
            data-slot="card-icon"
        >
            {icon}
        </span>
    ) : (
        leading
    );
    const heading =
        title || description ? (
            <Fragment>
                {title}
                {description}
            </Fragment>
        ) : null;
    // The subline sits next to the leading visual, under the title (as card.php).
    const header = leadingNode ? (
        <div data-slot="card-header">
            <div className={CARD.titleRow} data-slot="card-title-row">
                {leadingNode}
                {heading && <div className="min-w-0 flex-1">{heading}</div>}
            </div>
        </div>
    ) : (
        heading && <div data-slot="card-header">{heading}</div>
    );
    const top = (kicker || header || content) && (
        <Fragment>
            {kicker && <div data-slot="card-kicker">{kicker}</div>}
            {header}
            {content && (
                <div className={CARD.content} data-slot="card-content">
                    {content}
                </div>
            )}
        </Fragment>
    );
    const footerNode = footer && (
        <div
            className={
                variant === "split"
                    ? `${CARD.splitFooter} ${top ? CARD.splitFooterPadding : padding}`
                    : CARD.footer
            }
            data-slot="card-footer"
        >
            {footer}
        </div>
    );

    return (
        <Fragment>
            {media && (
                <div
                    className={CARD.media[horizontal ? "horizontal" : "vertical"]}
                    data-slot="card-media"
                >
                    {media}
                </div>
            )}
            {variant === "split" ? (
                <div className={CARD.splitBody} data-slot="card-body">
                    {top && (
                        <div
                            className={`${CARD.splitTop} ${cardAccent(variant, accent).bg} ${footer ? CARD.splitTopPadding : padding}`}
                            data-slot="card-split"
                        >
                            {top}
                        </div>
                    )}
                    {footerNode}
                </div>
            ) : (
                <div className={`${CARD.body} ${padding}`} data-slot="card-body">
                    {top}
                    {footerNode}
                </div>
            )}
        </Fragment>
    );
}
