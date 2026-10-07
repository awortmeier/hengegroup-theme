// Editor UI for template-parts/blocks/kontakt/block.json. Same Vite/@wordpress-globals architecture
// as the other blocks (see blocks/buehne/edit.jsx's header comment) -- built via
// vite.config.editor-kontakt.js.
//
// Ueberschrift und Text sind direkt in der Canvas editierbar (`RichText`, gleiches Muster wie
// offene-stellen/edit.jsx); Kontaktkarte und Formular kommen als echte `ServerSideRender`-Vorschau
// ueber den inserter-versteckten Zwilling `hengegroup-theme/kontakt-vorschau`, weil ein
// `ServerSideRender` gegen diesen Block Ueberschrift/Text ein zweites Mal statisch zeigen wuerde.
// Die Kontaktdaten der Karte kommen aus Einstellungen > Footer.
//
// Markup (section/wrapper/col-span) spiegelt render.php, damit die Canvas wie das Frontend aussieht.
import { createElement as el, Fragment } from "@wordpress/element";
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import { create, toHTMLString } from "@wordpress/rich-text";
import ServerSideRender from "@wordpress/server-side-render";
import metadata from "../../../../template-parts/blocks/kontakt/block.json";
import vorschauMetadata from "../../../../template-parts/blocks/kontakt-vorschau/block.json";

// Gleicher Plain-String-Rundlauf wie produkte/edit.jsx: Ueberschrift/Text bleiben reine Strings,
// render.php gibt sie ueber typography.php's esc_html() aus.
function toRichTextValue(text) {
    return toHTMLString({ value: create({ text }) });
}

function fromRichTextValue(html) {
    return create({ html }).text;
}

function Edit({ attributes, setAttributes }) {
    const { heading, text } = attributes;
    const blockProps = useBlockProps({ className: "alignfull py-16 md:py-25" });

    return (
        <Fragment>
            <section {...blockProps}>
                <div className="wrapper mb-12">
                    <div className="col-span-12 lg:col-span-7">
                        <RichText
                            tagName="h2"
                            className="mb-5 text-4xl leading-tight font-semibold lg:text-[42px]"
                            value={toRichTextValue(heading)}
                            onChange={(html) => setAttributes({ heading: fromRichTextValue(html) })}
                            placeholder={__("Überschrift eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                            disableLineBreaks
                        />
                        <RichText
                            tagName="p"
                            className="text-lg leading-normal lg:text-[22px] lg:leading-[1.4]"
                            value={toRichTextValue(text)}
                            onChange={(html) => setAttributes({ text: fromRichTextValue(html) })}
                            placeholder={__("Text eingeben…", "hengegroup-theme")}
                            allowedFormats={[]}
                        />
                    </div>
                </div>
                <ServerSideRender block={vorschauMetadata.name} />
            </section>
        </Fragment>
    );
}

registerBlockType(metadata, {
    edit: Edit,
    save: () => null,
});

// Clientseitige Minimal-Registrierung des Vorschau-Zwillings, siehe produkte/edit.jsx.
registerBlockType(vorschauMetadata, {
    edit: () => null,
    save: () => null,
});
