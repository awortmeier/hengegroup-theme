// wp.media() picker for every `.hengegroup-theme-seo-image-picker` (plain wp-admin script, no Vite).
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".hengegroup-theme-seo-image-picker").forEach((wrapper) => {
        const input = wrapper.querySelector('input[type="hidden"]');
        const preview = wrapper.querySelector(".hengegroup-theme-seo-image-picker__preview");
        const selectButton = wrapper.querySelector(".hengegroup-theme-seo-image-picker__select");
        const removeButton = wrapper.querySelector(".hengegroup-theme-seo-image-picker__remove");

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
