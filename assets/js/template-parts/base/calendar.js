// Progressive-enhancement layer for template-parts/base/calendar.php: intercepts clicks on the
// prev/next month links and rebuilds the month grid in place instead of letting the browser follow
// them (a real page reload -- see that file's header comment on why this needs its own grid math
// in JS rather than reading an already-rendered DOM, unlike select.js/combobox.js). Everything else
// (mode, week_starts_on, show_outside_days, name, min/max/disabled/selected dates) is read from the
// `data-*` attributes calendar.php already renders -- config still flows from PHP once.
//
// Each day cell reuses toggle.php's own markup (see calendar.php's header
// comment) -- toggle.js's initToggle() DOMContentLoaded sweep already picks up the initially
// server-rendered days, but cells this file creates client-side during month navigation never go
// through that sweep. enhanceToggle() is called on every freshly created cell below so navigated-to
// months announce "button, pressed" just like the initial month does (the established
// ARIA-gap-closing rule), not raw "checkbox"/"radio button" after the first month switch.
//
// The Tailwind classes below (input/label/outside-cell) mirror calendar.php's own render-time
// classes verbatim -- see that file's header comment on why (same "reimplemented formula-for-
// formula" duplication already documented there for the date-grid math itself, just for CSS classes
// this time). Keep both in sync by hand; there is no shared source for either.
import { enhanceToggle } from "./toggle.js";

const TOGGLE_INPUT_CLASSES = "peer sr-only";

const DAY_LABEL_CLASSES =
    "peer-focus-visible:border-ring peer-focus-visible:ring-[3px] " +
    "peer-focus-visible:ring-ring/50 focus-visible:border-ring focus-visible:outline-none " +
    "focus-visible:ring-[3px] focus-visible:ring-ring/50 flex h-10 w-full cursor-pointer " +
    "items-center justify-center rounded-xl border border-transparent text-base font-normal " +
    "text-foreground transition-colors hover:bg-muted peer-checked:border-henge-green " +
    "peer-checked:bg-henge-green peer-checked:font-semibold " +
    "peer-checked:text-henge-green-foreground peer-checked:hover:bg-henge-green/90 " +
    "peer-disabled:pointer-events-none peer-disabled:cursor-not-allowed peer-disabled:opacity-50";

const DAY_TODAY_CLASSES = "ring-1 ring-border";

const OUTSIDE_CELL_CLASSES = "h-10 p-0 text-center align-middle text-base text-muted-foreground";

const OUTSIDE_CELL_EMPTY_CLASSES = "h-10 p-0";

const INTERACTIVE_CELL_CLASSES = "p-0 text-center align-middle";

function parseJsonAttribute(el, name, fallback) {
    const raw = el.getAttribute(name);

    if (!raw) {
        return fallback;
    }

    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : fallback;
    } catch {
        return fallback;
    }
}

function pad2(n) {
    return String(n).padStart(2, "0");
}

function isoDate(year, month1, day) {
    return `${year}-${pad2(month1)}-${pad2(day)}`;
}

// Mirrors calendar.php's own leading/trailing formulas (DateTimeImmutable there, native Date here)
// formula-for-formula -- see this file's header comment on why that duplication is unavoidable.
function buildMonthCells(year, month1, weekStartsOn) {
    const monthIndex0 = month1 - 1;
    const daysInMonth = new Date(year, monthIndex0 + 1, 0).getDate();
    const firstWeekday = new Date(year, monthIndex0, 1).getDay();
    const leading = (firstWeekday - weekStartsOn + 7) % 7;
    const trailing = (7 - ((leading + daysInMonth) % 7)) % 7;
    const prevMonthDays = new Date(year, monthIndex0, 0).getDate();

    const cells = [];

    for (let i = 0; i < leading; i++) {
        cells.push({ outside: true, day: prevMonthDays - leading + i + 1 });
    }

    for (let day = 1; day <= daysInMonth; day++) {
        cells.push({ outside: false, day });
    }

    for (let i = 0; i < trailing; i++) {
        cells.push({ outside: true, day: i + 1 });
    }

    return cells;
}

function addMonths(year, month1, delta) {
    const date = new Date(year, month1 - 1 + delta, 1);
    return { year: date.getFullYear(), month: date.getMonth() + 1 };
}

function buildDayCell(config, year, month1, day, uidPrefix, uidCounter) {
    const iso = isoDate(year, month1, day);
    const isSelected = config.selected.has(iso);
    const isDisabled =
        config.disabledDates.has(iso) ||
        (config.minDate !== "" && iso < config.minDate) ||
        (config.maxDate !== "" && iso > config.maxDate);
    const isToday = iso === config.today;
    const inputType = config.mode === "single" ? "radio" : "checkbox";
    const inputName = config.mode === "single" ? config.name : `${config.name}[]`;
    const id = `${uidPrefix}-${uidCounter}`;

    const td = document.createElement("td");
    td.setAttribute("data-slot", "calendar-cell");
    td.className = INTERACTIVE_CELL_CLASSES;

    const input = document.createElement("input");
    input.type = inputType;
    input.className = TOGGLE_INPUT_CLASSES;
    input.setAttribute("data-slot", "toggle-input");
    input.id = id;
    input.name = inputName;
    input.value = iso;

    if (isSelected) {
        input.checked = true;
    }

    if (isDisabled) {
        input.disabled = true;
        input.setAttribute("data-disabled", "true");
    }

    input.setAttribute("aria-label", config.dateFormatter.format(new Date(year, month1 - 1, day)));

    const label = document.createElement("label");
    label.className = isToday ? `${DAY_LABEL_CLASSES} ${DAY_TODAY_CLASSES}` : DAY_LABEL_CLASSES;
    label.setAttribute("data-slot", "toggle");
    label.setAttribute("data-variant", "default");
    label.setAttribute("data-size", "default");
    label.setAttribute("data-state", isSelected ? "on" : "off");
    label.setAttribute("for", id);

    if (isDisabled) {
        label.setAttribute("data-disabled", "true");
    }

    if (isToday) {
        label.setAttribute("data-today", "true");
    }

    label.textContent = String(day);

    td.appendChild(input);
    td.appendChild(label);
    enhanceToggle(input);

    input.addEventListener("change", () => {
        if (input.checked) {
            if (config.mode === "single") {
                config.selected.clear();
            }

            config.selected.add(iso);
        } else {
            config.selected.delete(iso);
        }

        label.setAttribute("data-state", input.checked ? "on" : "off");
    });

    return td;
}

function renderMonth(wrapper, table, config, year, month1) {
    const tbody = table.querySelector("tbody");

    if (!tbody) {
        return;
    }

    tbody.innerHTML = "";

    const cells = buildMonthCells(year, month1, config.weekStartsOn);
    let row = document.createElement("tr");
    row.setAttribute("data-slot", "calendar-row");
    let column = 0;
    let uidCounter = 0;

    cells.forEach((cell) => {
        let td;

        if (cell.outside) {
            td = document.createElement("td");
            td.setAttribute("data-slot", "calendar-cell");
            td.setAttribute("data-outside", "true");
            td.className = config.showOutsideDays
                ? OUTSIDE_CELL_CLASSES
                : OUTSIDE_CELL_EMPTY_CLASSES;
            td.textContent = config.showOutsideDays ? String(cell.day) : "";
        } else {
            uidCounter += 1;
            td = buildDayCell(config, year, month1, cell.day, `${wrapper.id}-js`, uidCounter);
        }

        row.appendChild(td);
        column += 1;

        if (column === 7) {
            tbody.appendChild(row);
            row = document.createElement("tr");
            row.setAttribute("data-slot", "calendar-row");
            column = 0;
        }
    });

    table.setAttribute("data-month", String(month1));
    table.setAttribute("data-year", String(year));

    const captionText = config.captionFormatter.format(new Date(year, month1 - 1, 1));
    const caption = wrapper.querySelector('[data-slot="calendar-caption"]');

    if (caption) {
        caption.textContent = captionText;
    }

    // calendar.php always sets aria-label on the <table> -- either a caller-supplied custom label
    // or the auto-generated "F Y" caption -- so checking wrapper.hasAttribute("aria-label") can't
    // distinguish the two (it's never set on the wrapper itself). data-aria-label-custom is the
    // actual marker calendar.php renders only for the custom case; only overwrite the table's
    // aria-label with the freshly formatted caption when that marker is absent.
    if (!wrapper.hasAttribute("data-aria-label-custom")) {
        table.setAttribute("aria-label", captionText);
    }

    if (config.navName) {
        const url = new URL(window.location.href);
        const prev = addMonths(year, month1, -1);
        const next = addMonths(year, month1, 1);

        const prevLink = wrapper.querySelector('[data-nav="prev"]');
        const nextLink = wrapper.querySelector('[data-nav="next"]');

        if (prevLink) {
            const prevUrl = new URL(url);
            prevUrl.searchParams.set(config.navName, `${prev.year}-${pad2(prev.month)}`);
            prevLink.setAttribute("href", prevUrl.toString());
        }

        if (nextLink) {
            const nextUrl = new URL(url);
            nextUrl.searchParams.set(config.navName, `${next.year}-${pad2(next.month)}`);
            nextLink.setAttribute("href", nextUrl.toString());
        }
    }
}

function setupCalendar(wrapper) {
    const table = wrapper.querySelector('[data-slot="calendar-grid"]');
    const nav = wrapper.querySelector('[data-slot="calendar-nav"]');

    if (!table) {
        return;
    }

    const locale = document.documentElement.lang || undefined;

    const config = {
        mode: wrapper.getAttribute("data-mode") === "multiple" ? "multiple" : "single",
        weekStartsOn: parseInt(wrapper.getAttribute("data-week-starts-on") || "0", 10),
        showOutsideDays: wrapper.getAttribute("data-show-outside-days") !== "false",
        name: wrapper.getAttribute("data-name") || wrapper.id,
        navName: wrapper.getAttribute("data-nav-name") || "",
        minDate: wrapper.getAttribute("data-min-date") || "",
        maxDate: wrapper.getAttribute("data-max-date") || "",
        disabledDates: new Set(parseJsonAttribute(wrapper, "data-disabled-dates", [])),
        selected: new Set(parseJsonAttribute(wrapper, "data-selected", [])),
        today: (() => {
            const now = new Date();
            return isoDate(now.getFullYear(), now.getMonth() + 1, now.getDate());
        })(),
        dateFormatter: new Intl.DateTimeFormat(locale, {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        }),
        captionFormatter: new Intl.DateTimeFormat(locale, { month: "long", year: "numeric" }),
    };

    // The initially server-rendered month may not have carried every selected date (a `selected`
    // date outside that month never gets rendered by PHP at all) -- `data-selected` above is
    // always the complete, authoritative list, not just what's visible on load.

    let year = parseInt(table.getAttribute("data-year") || "0", 10);
    let month1 = parseInt(table.getAttribute("data-month") || "0", 10);

    if (!year || !month1) {
        return;
    }

    if (nav) {
        nav.addEventListener("click", (event) => {
            const link = event.target.closest('[data-nav="prev"], [data-nav="next"]');

            if (!link) {
                return;
            }

            event.preventDefault();

            const delta = link.getAttribute("data-nav") === "prev" ? -1 : 1;
            const target = addMonths(year, month1, delta);
            year = target.year;
            month1 = target.month;

            renderMonth(wrapper, table, config, year, month1);
        });
    }

    wrapper.setAttribute("data-js", "calendar");
}

export function initCalendar() {
    document.querySelectorAll('[data-slot="calendar"]').forEach((wrapper) => {
        setupCalendar(wrapper);
    });
}
