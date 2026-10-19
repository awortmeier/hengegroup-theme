// Editor for "Instagram": texts as RichText, posts from window.hengegroupThemeInstagram (stored
// centrally under Settings > Instagram, so the block has no image selection).
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { BlockControls, InspectorControls, RichText, useBlockProps } from "@wordpress/block-editor";
import { ExternalLink, Notice, PanelBody, RangeControl, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import metadata from "../../../../template-parts/blocks/instagram/block.json";
import {
    ACCENT_FORMATS,
    buttonPreviewClassName,
    fromRichTextValue,
    HeadingTagDropdown,
    sectionBackground,
    SectionBackgroundDropdown,
    SectionHeaderInner,
    sectionHeaderClassNames,
    sectionSpacingClassName,
    SectionSpacingDropdown,
    toRichTextValue,
} from "../shared.jsx";

const instagram = window.hengegroupThemeInstagram || { posts: [], settingsUrl: "" };

function Edit({ attributes, setAttributes }) {
    const { heading, headingTag, text, buttonText, count } = attributes;
    const background = sectionBackground(attributes.background);
    const headerClasses = sectionHeaderClassNames({ color: background.color });
    const blockProps = useBlockProps({
        className: `alignfull ${sectionSpacingClassName(attributes.spacing)} ${background.className}`,
    });
    const posts = instagram.posts.slice(0, count);

    return (
        <Fragment>
            <BlockControls group="block">
                <ToolbarGroup>
                    <HeadingTagDropdown
                        value={headingTag}
                        onChange={(value) => setAttributes({ headingTag: value })}
                    />
                    <SectionBackgroundDropdown
                        value={background.name}
                        onChange={(value) => setAttributes({ background: value })}
                    />
                    <SectionSpacingDropdown
                        value={attributes.spacing}
                        onChange={(value) => setAttributes({ spacing: value })}
                    />
                </ToolbarGroup>
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("Beiträge", "hengegroup-theme")} initialOpen>
                    <RangeControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__("Anzahl Beiträge", "hengegroup-theme")}
                        help={__(
                            "Die neuesten Beiträge, in Reihen zu je drei.",
                            "hengegroup-theme"
                        )}
                        min={3}
                        max={12}
                        step={3}
                        value={count}
                        onChange={(value) => setAttributes({ count: value || 3 })}
                    />
                    {instagram.settingsUrl && (
                        <ExternalLink href={instagram.settingsUrl}>
                            {__("Instagram-Verbindung verwalten", "hengegroup-theme")}
                        </ExternalLink>
                    )}
                </PanelBody>
            </InspectorControls>
            <section {...blockProps}>
                <div className="wrapper gap-y-10">
                    <SectionHeaderInner
                        color={background.color}
                        className="col-span-12 lg:col-span-6"
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
                        }
                    />
                    {posts.length > 0 ? (
                        <ul className="col-span-12 grid grid-cols-1 gap-5 sm:grid-cols-3">
                            {posts.map((post) => (
                                <li key={post.src}>
                                    <img
                                        src={post.src}
                                        alt={post.alt}
                                        className="shadow-raised aspect-[4/3] w-full rounded-2xl object-cover sm:aspect-video"
                                    />
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="col-span-12">
                            <Notice status="warning" isDismissible={false}>
                                {__(
                                    "Noch keine Instagram-Beiträge gespeichert. Ohne Beiträge wird der Block auf der Website ausgeblendet.",
                                    "hengegroup-theme"
                                )}{" "}
                                {instagram.settingsUrl && (
                                    <ExternalLink href={instagram.settingsUrl}>
                                        {__("Einstellungen > Instagram", "hengegroup-theme")}
                                    </ExternalLink>
                                )}
                            </Notice>
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
