// Vanilla wp.media() picker for every `.base-theme-seo-image-picker` field rendered by
// inc/setup/theme-seo-admin.php (the "Standard Social-Bild" field on Settings > SEO, and each
// post/page's own "Social-Bild" field in its "SEO" meta box). Enqueued directly via
// wp_enqueue_script(), not through the Vite/Tailwind pipeline in vite.config.js -- that pipeline
// only builds the public assets/js/app.js and assets/js/login.js bundles; this is a small
// wp-admin-only script with no styling/build step of its own, so a plain enqueue is simpler than
// wiring up a third Vite entry for it.
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".base-theme-seo-image-picker").forEach((wrapper) => {
        const input = wrapper.querySelector('input[type="hidden"]');
        const preview = wrapper.querySelector(".base-theme-seo-image-picker__preview");
        const selectButton = wrapper.querySelector(".base-theme-seo-image-picker__select");
        const removeButton = wrapper.querySelector(".base-theme-seo-image-picker__remove");

        if (!input || !preview || !selectButton || !removeButton) {
            return;
        }

        let frame = null;

        selectButton.addEventListener("click", (event) => {
            event.preventDefault();

            if (!window.wp || !window.wp.media) {
                return;
            }

            if (frame) {
                frame.open();
                return;
            }

            frame = window.wp.media({
                multiple: false,
                library: { type: "image" },
            });

            frame.on("select", () => {
                const attachment = frame.state().get("selection").first().toJSON();

                input.value = String(attachment.id);
                preview.innerHTML = "";

                const image = document.createElement("img");
                image.src =
                    attachment.sizes && attachment.sizes.medium
                        ? attachment.sizes.medium.url
                        : attachment.url;
                image.alt = "";
                image.style.display = "block";
                image.style.height = "auto";
                image.style.maxWidth = "200px";
                preview.appendChild(image);

                removeButton.style.display = "";
            });

            frame.open();
        });

        removeButton.addEventListener("click", (event) => {
            event.preventDefault();
            input.value = "0";
            preview.innerHTML = "";
            removeButton.style.display = "none";
        });
    });
});
