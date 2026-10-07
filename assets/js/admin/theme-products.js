// Wiederholbare Zeilen (chemische Analyse, Downloads) und Dateiauswahl aus der Mediathek im
// Produkt-Tab "Technische Daten" (inc/setup/theme-products-admin.php). Gleiche Einbindung wie
// theme-seo.js: kleines wp-admin-Skript ohne Build-Schritt, per wp_enqueue_script() geladen.
//
// Markup-Vertrag:
//   [data-hengegroup-repeatable="<key>"] tbody     Zeilen
//   template[data-hengegroup-template="<key>"]     leere Vorlage-Zeile
//   [data-hengegroup-add-row="<key>"]              haengt eine Vorlage-Zeile an
//   [data-hengegroup-remove-row]                   entfernt die eigene Zeile
//   [data-hengegroup-select-file]                  oeffnet wp.media(), schreibt die ID in
//                                                  [data-hengegroup-file-input] und den Dateinamen in
//                                                  [data-hengegroup-file-name] derselben Zeile
document.addEventListener("click", (event) => {
    const target = event.target instanceof Element ? event.target : null;

    if (!target) {
        return;
    }

    const addButton = target.closest("[data-hengegroup-add-row]");

    if (addButton) {
        event.preventDefault();
        const key = addButton.getAttribute("data-hengegroup-add-row");
        const template = document.querySelector(`template[data-hengegroup-template="${key}"]`);
        const body = document.querySelector(`[data-hengegroup-repeatable="${key}"] tbody`);

        if (template && body) {
            body.appendChild(template.content.cloneNode(true));
        }

        return;
    }

    const removeButton = target.closest("[data-hengegroup-remove-row]");

    if (removeButton) {
        event.preventDefault();
        removeButton.closest("[data-hengegroup-row]")?.remove();
        return;
    }

    const selectButton = target.closest("[data-hengegroup-select-file]");

    if (!selectButton || !window.wp || !window.wp.media) {
        return;
    }

    event.preventDefault();

    const row = selectButton.closest("[data-hengegroup-row]");
    const input = row?.querySelector("[data-hengegroup-file-input]");
    const name = row?.querySelector("[data-hengegroup-file-name]");

    if (!input) {
        return;
    }

    const frame = window.wp.media({ multiple: false });

    frame.on("select", () => {
        const attachment = frame.state().get("selection").first().toJSON();

        input.value = String(attachment.id);

        if (name) {
            name.textContent = attachment.filename || attachment.title || "";
        }
    });

    frame.open();
});
