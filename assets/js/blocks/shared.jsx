// Editor helpers shared by the block editors (bundled into each block's IIFE).
import { createElement as el, Fragment } from "@wordpress/element";
import { createBlock } from "@wordpress/blocks";
import { MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";
import { ToolbarButton, ToolbarDropdownMenu } from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";

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
    "henge-grey": { bg: "bg-henge-grey", border: "border-henge-grey" },
    "henge-green": { bg: "bg-henge-green", border: "border-henge-green" },
    "henge-blue": { bg: "bg-henge-blue", border: "border-henge-blue" },
    "grey-dark": { bg: "bg-grey-dark", border: "border-grey-dark" },
};

const ACCENT_OPTIONS = [
    { title: __("Grau (HENGEGROUP)", "hengegroup-theme"), value: "henge-grey" },
    { title: __("Grün (Baustoffe)", "hengegroup-theme"), value: "henge-green" },
    { title: __("Blau (Mineralien)", "hengegroup-theme"), value: "henge-blue" },
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

// Own "add" tile as the last grid cell (grid needs APPENDER_GRID_CELL_CLASSNAME): Gutenberg's
// appender sits absolutely over the last item.
export function AddItemTile({ rootClientId, blockName, label, className = "" }) {
    const { insertBlock } = useDispatch("core/block-editor");

    return (
        <button
            type="button"
            className={`flex w-full cursor-pointer items-center justify-center border-2 border-dashed bg-transparent text-base font-semibold ${className}`}
            onClick={() => insertBlock(createBlock(blockName), undefined, rootClientId)}
        >
            {label}
        </button>
    );
}

export const APPENDER_GRID_CELL_CLASSNAME =
    "[&>.block-list-appender]:!static [&>.block-list-appender]:!m-0 [&>.block-list-appender]:!w-full";
