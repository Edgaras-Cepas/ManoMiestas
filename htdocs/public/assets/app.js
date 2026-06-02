
// ManoMiestas front-end: zemelapis, pranesimu vedlys, UI (public/assets/app.js)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-28).
// Uzklausa: Reikia front-end logikos zemelaPiui, pranesimu saraSui ir report vedliui.
// Rezultatas dalinai koreguotas.

const tokenSource = "assets/tokens.json";

let mapInstance;
let selectionMarker;
let selecting = false;
let selectedLatLng = null;

const categoryColors = {
    pazeidimai: "#f15b5b",
    sezonine: "#4da3ff",
    gedimai: "#ff9f43",
    gyvunai: "#2f9b68",
    pastatai: "#2f5fb3",
    eismas: "#ff5050",
    remontas: "#2f2f2f"
};

// Normalizuoja kategorijos pavadinima spalvu zemelaPiui (be diakritiku)
function normalizeCategory(value) {
    const raw = (value || "").trim().toLowerCase();
    if (!raw) return "";
    if (typeof raw.normalize === "function") {
        return raw.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }
    return raw;
}

function resolveCategoryColor(category) {
    const key = normalizeCategory(category);
    return categoryColors[key] || "#f76c6c";
}

// Leaflet zyme su spalva pagal kategorija
function createIssueIcon(category) {
    const color = resolveCategoryColor(category);
    return L.divIcon({
        className: "map-issue-icon",
        html: `<span class="map-bubble" style="background:${color};"></span>`,
        iconSize: [22, 22],
        iconAnchor: [11, 11],
        popupAnchor: [0, -12]
    });
}

// Ikelia design tokens is JSON i CSS kintamuosius
async function applyTokens() {
    try {
        const res = await fetch(tokenSource);
        if (!res.ok) throw new Error(`Nepavyko nuskaityti tokens.json: ${res.status}`);
        const tokens = await res.json();
        const root = document.documentElement;
        root.style.setProperty("--color-brand", tokens.colors.brand);
        root.style.setProperty("--color-brand-strong", tokens.colors.brandStrong);
        root.style.setProperty("--color-surface", tokens.colors.surface);
        root.style.setProperty("--color-elevated", tokens.colors.elevated);
        root.style.setProperty("--color-text", tokens.colors.text);
        root.style.setProperty("--color-muted", tokens.colors.muted);
        root.style.setProperty("--color-border", tokens.colors.border);
        root.style.setProperty("--shadow-soft", tokens.shadows.soft);
        root.style.setProperty("--radius-card", tokens.radii.card);
        root.style.setProperty("--radius-pill", tokens.radii.pill);
    } catch (err) {
        console.warn(err.message);
    }
}

// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-28).
// Uzklausa: Reikia Leaflet zemelaPio su markeriais, cluster ir popup nuoroda.
// Rezultatas dalinai koreguotas.
// Pagrindinis zemelaPis index.php — zymes, klasteriai, lokacijos pasirinkimas
function initMap() {
    const fallbackCenter = [53.4808, -2.2426];
    const defaultCenter = window.MANO_DEFAULT_CENTER && typeof window.MANO_DEFAULT_CENTER.lat === "number"
        ? [window.MANO_DEFAULT_CENTER.lat, window.MANO_DEFAULT_CENTER.lng]
        : fallbackCenter;
    const hasDefaultCenter = defaultCenter !== fallbackCenter;
    mapInstance = L.map("map", { zoomControl: false }).setView(defaultCenter, 15);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap"
    }).addTo(mapInstance);

    const issues = Array.isArray(window.MANO_ISSUES) ? window.MANO_ISSUES : [];
    const markerGroup = typeof L.markerClusterGroup === "function"
        ? L.markerClusterGroup({
            showCoverageOnHover: false,
            maxClusterRadius: 55,
            iconCreateFunction: (cluster) => {
                const count = cluster.getChildCount();
                return L.divIcon({
                    html: `<span class="cluster-bubble">${count}</span>`,
                    className: "cluster-icon",
                    iconSize: [44, 44]
                });
            }
        })
        : L.layerGroup();
    if (issues.length && !hasDefaultCenter) {
        const first = issues[0];
        const lat = Number(first.lat);
        const lng = Number(first.lng);
        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
            mapInstance.setView([lat, lng], 14);
        }
    }

    issues.forEach(item => {
        const lat = Number(item.lat);
        const lng = Number(item.lng);
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
        const address = item.address || "Adresas nenurodytas";
        const title = item.title || item.category || "Pranesimas";
        const issueId = Number(item.id);
        const safeTitle = escapeHtml(title);
        const safeAddress = escapeHtml(address);
        const link = Number.isFinite(issueId)
            ? `<a class="map-popup-link" href="issue.php?id=${issueId}">${safeTitle}</a>`
            : `<strong>${safeTitle}</strong>`;
        const popup = `${link}<div class="map-popup-address">${safeAddress}</div>`;
        const marker = L.marker([lat, lng], { icon: createIssueIcon(item.category) })
            .bindPopup(popup);
        markerGroup.addLayer(marker);
    });
    markerGroup.addTo(mapInstance);

    mapInstance.on("click", (e) => {
        if (!selecting) return;
        setPinPosition(e.latlng);
        selectedLatLng = e.latlng;
    });
}

function setPinPosition(latlng) {
    const shell = document.getElementById("map-shell");
    if (!mapInstance || !shell) return;
    const point = mapInstance.latLngToContainerPoint(latlng);
    shell.style.setProperty("--pin-left", `${point.x}px`);
    shell.style.setProperty("--pin-top", `${point.y}px`);
}

function startSelection() {
    const shell = document.getElementById("map-shell");
    const fab = document.getElementById("fab-toggle");
    selecting = true;
    shell.classList.add("selecting");
    document.body.classList.add("selecting-mode");
    fab.disabled = true;
    selectedLatLng = mapInstance ? mapInstance.getCenter() : null;
    if (selectedLatLng) {
        setPinPosition(selectedLatLng);
    }
}

function cancelSelection() {
    const shell = document.getElementById("map-shell");
    const fab = document.getElementById("fab-toggle");
    selecting = false;
    shell.classList.remove("selecting");
    document.body.classList.remove("selecting-mode");
    fab.disabled = false;
}

// FAB rezimas: patvirtinus koordinates nukreipia i report.php su ?lat=&lng=
function confirmSelection() {
    if (!mapInstance) return;
    const center = selectedLatLng || mapInstance.getCenter();
    const params = new URLSearchParams({
        lat: center.lat.toFixed(6),
        lng: center.lng.toFixed(6)
    });
    window.location.href = `report.php?${params.toString()}`;
}

window.addEventListener("DOMContentLoaded", () => {
    applyTokens();
    const isAuthed = document.body.dataset.auth === "1";
    if (isAuthed) {
        document.querySelectorAll(".nav-account").forEach(link => {
            link.classList.add("disabled");
            link.setAttribute("aria-disabled", "true");
            link.setAttribute("tabindex", "-1");
        });
    }
    if (document.getElementById("map")) {
        initMap();
        initMapSearch();
        const fab = document.getElementById("fab-toggle");
        const cancelBtn = document.getElementById("cancel-select");
        const confirmBtn = document.getElementById("confirm-select");
        const searchInput = document.querySelector(".search-input");
        const clearBtn = document.querySelector(".clear-search");

        fab.addEventListener("click", startSelection);
        cancelBtn.addEventListener("click", cancelSelection);
        confirmBtn.addEventListener("click", confirmSelection);
        if (clearBtn && searchInput) {
            clearBtn.addEventListener("click", () => {
                searchInput.value = "";
                searchInput.focus();
                const suggestions = document.getElementById("search-suggestions");
                if (suggestions) {
                    suggestions.innerHTML = "";
                    suggestions.hidden = true;
                }
            });
        }
    }

    if (document.querySelector(".list-page")) {
        initListFilters();
    }

    initIssueMap();
    bindCommentToggles();
    bindShareButtons();
    initPhotoCarousels();
    initSettings();
    initReportWizard();
});

// issues.php / my-issues.php — paieska ir filtrai be perkrovimo
function initListFilters() {
    const searchInput = document.querySelector(".list-search .search-input");
    const clearBtn = document.querySelector(".list-search .clear-search");
    const filterToggle = document.getElementById("filter-toggle");
    const filterPanel = document.getElementById("filter-panel");
    const typeSel = document.getElementById("filter-type");
    const statusSel = document.getElementById("filter-status");
    const periodSel = document.getElementById("filter-period");
    const sortSel = document.getElementById("filter-sort");
    const list = document.querySelector(".list-scroll");
    const cards = Array.from(document.querySelectorAll(".list-card"));
    if (clearBtn && searchInput) {
        clearBtn.addEventListener("click", () => {
            searchInput.value = "";
            applyListFilters();
            searchInput.focus();
        });
    }
    if (filterToggle && filterPanel) {
        filterToggle.addEventListener("click", () => {
            filterPanel.classList.toggle("collapsed");
            const expanded = !filterPanel.classList.contains("collapsed");
            filterToggle.setAttribute("aria-expanded", expanded ? "true" : "false");
        });
    }
    [searchInput, typeSel, statusSel, periodSel, sortSel].forEach(el => {
        if (!el) return;
        el.addEventListener("input", applyListFilters);
        el.addEventListener("change", applyListFilters);
    });

    function applyListFilters() {
        const q = (searchInput && searchInput.value ? searchInput.value : "").toLowerCase().trim();
        const typeVal = (typeSel && typeSel.value ? typeSel.value : "").toLowerCase();
        const statusVal = (statusSel && statusSel.value ? statusSel.value : "").toLowerCase();
        const periodVal = (periodSel && periodSel.value ? periodSel.value : "all").toLowerCase();
        const sortVal = (sortSel && sortSel.value ? sortSel.value : "newest").toLowerCase();
        const now = new Date();

        cards.forEach(card => {
            const type = (card.dataset.type || "").toLowerCase();
            const status = (card.dataset.status || "").toLowerCase();
            const dateStr = card.dataset.date;
            const cardDate = dateStr ? new Date(dateStr) : null;
            const blob = card.textContent ? card.textContent.toLowerCase() : "";

            let visible = true;
            if (q && !blob.includes(q)) visible = false;
            if (typeVal && type !== typeVal) visible = false;
            if (statusVal && status !== statusVal) visible = false;

            if (periodVal !== "all" && cardDate) {
                const diffMs = now - cardDate;
                const dayMs = 24 * 60 * 60 * 1000;
                if (periodVal === "24h" && diffMs > dayMs) visible = false;
                if (periodVal === "7d" && diffMs > 7 * dayMs) visible = false;
                if (periodVal === "30d" && diffMs > 30 * dayMs) visible = false;
            }

            card.style.display = visible ? "" : "none";
        });

        // Rikiuoja matomas korteles pagal data
        const sorted = cards
            .filter(c => c.style.display !== "none")
            .sort((a, b) => {
                const da = new Date(a.dataset.date).getTime();
                const db = new Date(b.dataset.date).getTime();
                return sortVal === "oldest" ? da - db : db - da;
            });
        if (list) {
            sorted.forEach(c => list.appendChild(c));
        }
    }

    applyListFilters();

}

// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-28).
// Uzklausa: Rašant adresa zemelaPyje noriu matyti pasiulymus.
// Rezultatas dalinai koreguotas.
// Adreso paieska zemelaPyje (debounce -> geocode.php)
function initMapSearch() {
    const input = document.querySelector(".search-input");
    const suggestions = document.getElementById("search-suggestions");
    if (!input || !suggestions || !mapInstance) return;

    let timer = null;

    const clearSuggestions = () => {
        suggestions.innerHTML = "";
        suggestions.hidden = true;
    };

    const applyResult = (item) => {
        const lat = parseFloat(item.lat);
        const lng = parseFloat(item.lon);
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
        input.value = item.display_name || input.value;
        mapInstance.setView([lat, lng], 16);
        selectedLatLng = L.latLng(lat, lng);
        if (selecting) {
            setPinPosition(selectedLatLng);
        }
        clearSuggestions();
    };

    const renderSuggestions = (items) => {
        if (!items.length) {
            clearSuggestions();
            return;
        }
        suggestions.innerHTML = items.map((item) => {
            const label = item.display_name || "";
            return `<button type="button" class="suggestion-item" data-lat="${item.lat}" data-lng="${item.lon}">${label}</button>`;
        }).join("");
        suggestions.hidden = false;
    };

    const fetchSuggestions = (query) => {
        fetch(`geocode.php?q=${encodeURIComponent(query)}`, { headers: { "Accept": "application/json" } })
            .then(res => res.ok ? res.json() : [])
            .then(data => {
                if (!Array.isArray(data)) return;
                renderSuggestions(data.slice(0, 5));
            })
            .catch(() => {});
    };

    input.addEventListener("input", () => {
        const q = input.value.trim();
        if (q.length < 3) {
            clearSuggestions();
            return;
        }
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => fetchSuggestions(q), 300);
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            const first = suggestions.querySelector(".suggestion-item");
            if (first) {
                applyResult({
                    lat: first.getAttribute("data-lat"),
                    lon: first.getAttribute("data-lng"),
                    display_name: first.textContent
                });
            } else if (input.value.trim().length >= 3) {
                fetchSuggestions(input.value.trim());
            }
        }
    });

    suggestions.addEventListener("click", (e) => {
        const btn = e.target.closest(".suggestion-item");
        if (!btn) return;
        applyResult({
            lat: btn.getAttribute("data-lat"),
            lon: btn.getAttribute("data-lng"),
            display_name: btn.textContent
        });
    });

    document.addEventListener("click", (e) => {
        if (e.target === input || suggestions.contains(e.target)) return;
        clearSuggestions();
    });
}

function openComments() {
    const overlay = document.getElementById("comments-overlay");
    if (!overlay) return;
    overlay.classList.add("open");
}

// Saugus tekstas HTML sablone (popup, komentarai)
function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

// Uzkrauna komentarus JSON is issue-comments.php i slankų paneli
function openIssueComments(issueId) {
    const overlay = document.getElementById("comments-overlay");
    if (!overlay || !issueId) return;
    const countEl = overlay.querySelector(".comments-count");
    const body = overlay.querySelector(".comments-body");
    const link = overlay.querySelector(".comments-open-link");
    overlay.classList.add("open");
    if (link) {
        link.href = `issue.php?id=${issueId}`;
    }
    if (!body) return;
    body.innerHTML = "<div class=\"comment-item\"><div class=\"comment-main\"><div class=\"comment-text\">Kraunama...</div></div></div>";
    fetch(`issue-comments.php?id=${encodeURIComponent(issueId)}`, { headers: { "Accept": "application/json" } })
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (!data || !body) return;
            const comments = Array.isArray(data.comments) ? data.comments : [];
            if (countEl) countEl.textContent = `Komentarai: ${data.count || comments.length}`;
            if (!comments.length) {
                body.innerHTML = "<div class=\"comment-item\"><div class=\"comment-main\"><div class=\"comment-text\">Komentarų dar nėra.</div></div></div>";
                return;
            }
            body.innerHTML = comments.map(item => {
                const adminBadge = item.is_admin ? " <span class=\"badge-admin\">Admin</span>" : "";
                const author = escapeHtml(item.author || "Vartotojas");
                const text = escapeHtml(item.text || "");
                return `
                <div class="comment-item">
                    <div class="avatar${item.is_admin ? " success" : ""}"></div>
                    <div class="comment-main">
                        <div class="comment-meta">${author}${adminBadge}</div>
                        <div class="comment-text">${text}</div>
                    </div>
                </div>`;
            }).join("");
        })
        .catch(() => {
            if (body) {
                body.innerHTML = "<div class=\"comment-item\"><div class=\"comment-main\"><div class=\"comment-text\">Nepavyko gauti komentarų.</div></div></div>";
            }
        });
}

function closeComments() {
    const overlay = document.getElementById("comments-overlay");
    if (!overlay) return;
    overlay.classList.remove("open");
}

function bindCommentToggles() {
    const commentButtons = document.querySelectorAll(".comment-toggle, .icon-btn[aria-label='Komentarai']");
    commentButtons.forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            const issueId = btn.getAttribute("data-issue-id");
            if (issueId) {
                openIssueComments(issueId);
            } else {
                openComments();
            }
        });
    });
    const closeBtn = document.querySelector(".close-comments");
    if (closeBtn) closeBtn.addEventListener("click", closeComments);
}

function bindShareButtons() {
    const shareButtons = document.querySelectorAll(".share-copy");
    shareButtons.forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            const dataLink = btn.getAttribute("data-link");
            const url = dataLink ? new URL(dataLink, window.location.href).toString() : window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
                navigator.clipboard.writeText(url).catch(() => fallbackCopy(url));
            } else {
                fallbackCopy(url);
            }
        });
    });
}

function fallbackCopy(text) {
    const temp = document.createElement("textarea");
    temp.value = text;
    temp.setAttribute("readonly", "");
    temp.style.position = "absolute";
    temp.style.left = "-9999px";
    document.body.appendChild(temp);
    temp.select();
    let copied = false;
    try {
        copied = document.execCommand("copy");
    } catch (err) {
        copied = false;
    }
    temp.remove();
    if (!copied) {
        window.prompt("Nukopijuokite nuorodą:", text);
    }
}

// settings.php — miesto pasirinkimo UI ir formu busena
function initSettings() {
    const settingsPage = document.querySelector(".settings-page");
    if (!settingsPage) return;

    const cityModal = document.getElementById("city-modal");
    const cityEdit = document.querySelector(".city-edit");
    const cityValue = document.getElementById("setting-city");
    const cityForm = document.getElementById("city-form");
    const cityInput = document.getElementById("city-input");
    const closeBtn = cityModal ? cityModal.querySelector(".close-modal") : null;
    const cityOptions = cityModal ? cityModal.querySelectorAll(".city-option") : [];

    const openCityModal = () => {
        if (!cityModal) return;
        cityModal.hidden = false;
        cityModal.classList.add("open");
    };

    const closeCityModal = () => {
        if (!cityModal) return;
        cityModal.classList.remove("open");
        cityModal.hidden = true;
    };

    if (cityModal) {
        cityModal.hidden = true;
    }

    if (cityEdit && cityModal) {
        cityEdit.addEventListener("click", (e) => {
            e.preventDefault();
            openCityModal();
        });
    }

    if (closeBtn && cityModal) {
        closeBtn.addEventListener("click", (e) => {
            e.preventDefault();
            closeCityModal();
        });
    }

    if (cityModal) {
        cityModal.addEventListener("click", (e) => {
            if (e.target === cityModal) {
                closeCityModal();
            }
        });
    }

    cityOptions.forEach(option => {
        option.addEventListener("click", () => {
            const rawCity = option.getAttribute("data-city") || option.textContent.trim();
            const cleanedCity = rawCity.replace(/\s*>\s*$/, "").trim();
            if (cityValue) cityValue.textContent = cleanedCity;
            if (cityInput) cityInput.value = cleanedCity;
            if (cityForm) {
                cityForm.requestSubmit ? cityForm.requestSubmit() : cityForm.submit();
            }
            closeCityModal();
        });
    });

    const tabs = settingsPage.querySelectorAll(".settings-tab");
    const panels = settingsPage.querySelectorAll("[data-tab-panel]");

    const setActiveTab = (target) => {
        tabs.forEach(tab => {
            tab.classList.toggle("active", tab.getAttribute("data-tab") === target);
        });
        panels.forEach(panel => {
            panel.hidden = panel.getAttribute("data-tab-panel") !== target;
        });
    };

    if (tabs.length && panels.length) {
        const active = settingsPage.querySelector(".settings-tab.active") || tabs[0];
        if (active) {
            setActiveTab(active.getAttribute("data-tab"));
        }

        tabs.forEach(tab => {
            tab.addEventListener("click", () => {
                const target = tab.getAttribute("data-tab");
                if (target) setActiveTab(target);
            });
        });
    }
}

// issue.php — vieno pranesimo zemelaPis
function initIssueMap() {
    const mapEl = document.getElementById("issue-map");
    if (!mapEl || typeof L === "undefined") return;
    const lat = parseFloat(mapEl.getAttribute("data-lat"));
    const lng = parseFloat(mapEl.getAttribute("data-lng"));
    if (Number.isNaN(lat) || Number.isNaN(lng)) return;
    const map = L.map(mapEl, { zoomControl: false, attributionControl: false });
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19
    }).addTo(map);
    map.setView([lat, lng], 16);
    L.marker([lat, lng]).addTo(map);
}

// Keliu nuotrauku perziura issue / admin kortelese
function initPhotoCarousels() {
    document.querySelectorAll(".photo-carousel").forEach(carousel => {
        const photos = Array.from(carousel.querySelectorAll(".carousel-photo"));
        const prevBtn = carousel.querySelector(".carousel-prev");
        const nextBtn = carousel.querySelector(".carousel-next");
        if (photos.length <= 1) {
            if (prevBtn) prevBtn.style.display = "none";
            if (nextBtn) nextBtn.style.display = "none";
            return;
        }
        let index = 0;
        const show = (next) => {
            index = (next + photos.length) % photos.length;
            photos.forEach((img, idx) => {
                img.hidden = idx !== index;
            });
        };
        if (prevBtn) {
            prevBtn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                show(index - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                show(index + 1);
            });
        }
        show(0);
    });
}

// report.php — 3 zingsniu vedlys: kategorija, aprasymas+zemelaPis, perziura
function initReportWizard() {
    const reportPage = document.querySelector(".report-page");
    if (!reportPage) return;

    const reportForm = document.getElementById("report-form");
    const maxPhotoBytes = reportForm
        ? Number(reportForm.dataset.maxPhotoBytes) || 2097152
        : 2097152;
    const maxPhotoCount = reportForm
        ? Number(reportForm.dataset.maxPhotoCount) || 5
        : 5;

    const steps = Array.from(reportPage.querySelectorAll(".report-step"));
    const stepIndicator = document.getElementById("report-step-indicator");
    const backBtn = document.getElementById("report-back");
    const closeBtn = document.getElementById("report-close");
    const nextBtn1 = document.getElementById("report-next-1");
    const nextBtn2 = document.getElementById("report-next-2");
    const submitBtn = document.getElementById("report-submit");
    const categoryButtons = reportPage.querySelectorAll(".category-item");
    const descriptionInput = document.getElementById("report-description");
    const dateInput = document.getElementById("report-date");
    const timeInput = document.getElementById("report-time");
    const locationInput = document.getElementById("report-location");
    const categoryInput = document.getElementById("report-category");
    const latInput = document.getElementById("report-lat");
    const lngInput = document.getElementById("report-lng");
    const addressInput = document.getElementById("report-address");
    const summaryCategory = document.getElementById("summary-category");
    const summaryDescription = document.getElementById("summary-description");
    const summaryDate = document.getElementById("summary-date");
    const summaryTime = document.getElementById("summary-time");
    const summaryLocation = document.getElementById("summary-location");
    const selectedCategoryWrap = document.getElementById("report-selected-category");
    const selectedCategoryIcon = document.getElementById("report-selected-icon");
    const selectedCategoryLabel = document.getElementById("report-selected-label");
    const summaryCategoryIcon = document.getElementById("summary-category-icon");
    const fileInput = document.getElementById("report-photo");
    const fileName = document.getElementById("report-photo-name");
    const previewImage = document.getElementById("report-photo-preview");
    const reportMapEl = document.getElementById("report-map");
    const mapShell = document.getElementById("report-map-shell");
    const mapActions = document.getElementById("report-map-actions");
    const mapCancelBtn = document.getElementById("report-map-cancel");
    const mapConfirmBtn = document.getElementById("report-map-confirm");
    const locationEditBtn = document.getElementById("report-location-edit");
    const summaryMedia = document.getElementById("summary-media");
    const summaryPhoto = document.getElementById("summary-photo");
    const summaryPhotoPlaceholder = document.getElementById("summary-photo-placeholder");
    const reviewMapEl = document.getElementById("report-review-map");
    const descriptionField = document.getElementById("report-description-field");
    const photoErrorEl = document.getElementById("report-photo-error");
    const photoLimitHint = document.getElementById("report-photo-limit-hint");
    const flashErrorEl = document.getElementById("report-flash-error");

    // GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-05-12).
    // Uzklausa: Jei pasirenku per didelę nuotrauka, noriu matyti klaida is karto.
    // Rezultatas dalinai koreguotas.

    const clearPhotoError = () => {
        if (photoErrorEl) {
            photoErrorEl.textContent = "";
            photoErrorEl.hidden = true;
        }
    };

    const showPhotoError = (msg) => {
        if (photoErrorEl) {
            photoErrorEl.textContent = msg;
            photoErrorEl.hidden = false;
        }
    };

    const validateSelectedPhotos = (fileList) => {
        if (!fileList || !fileList.length) {
            return { ok: true, message: "" };
        }
        if (fileList.length > maxPhotoCount) {
            return {
                ok: false,
                message: `Galima įkelti ne daugiau kaip ${maxPhotoCount} nuotraukų.`,
            };
        }
        const mbLabel =
            Math.max(0.1, Math.round((maxPhotoBytes / (1024 * 1024)) * 10) / 10);
        for (let i = 0; i < fileList.length; i += 1) {
            const f = fileList[i];
            if (f.size > maxPhotoBytes) {
                return {
                    ok: false,
                    message: `Failas „${f.name}“ per didelis. Kiekviena nuotrauka turi būti ne didesnė nei ${mbLabel} MB.`,
                };
            }
        }
        return { ok: true, message: "" };
    };

    if (photoLimitHint && maxPhotoBytes) {
        const mbLabel =
            Math.max(0.1, Math.round((maxPhotoBytes / (1024 * 1024)) * 10) / 10);
        photoLimitHint.textContent = `Leidžiama iki ${maxPhotoCount} nuotraukų, kiekviena iki ${mbLabel} MB.`;
    }

    const initialReportErr = (document.body.dataset.reportError || "").trim();
    if (initialReportErr && flashErrorEl) {
        flashErrorEl.textContent = initialReportErr;
        flashErrorEl.hidden = false;
    }

    let currentStep = 1;
    let selectedCategory = "";
    let selectedCategoryIconHtml = "";
    let selectedCategoryClass = "cat-flag";
    let reportMap = null;
    let reportMarker = null;
    let reviewMap = null;
    let reviewMarker = null;
    let previewUrl = "";
    let reportCoords = null;
    let pendingCoords = null;
    let previousCoords = null;
    let selectingLocation = false;

    const setStep = (nextStep) => {
        currentStep = nextStep;
        steps.forEach(step => {
            const stepNum = Number(step.getAttribute("data-step"));
            step.hidden = stepNum !== currentStep;
        });
        if (stepIndicator) stepIndicator.textContent = `${currentStep}/3`;
        if (backBtn && closeBtn) {
            backBtn.hidden = currentStep === 1;
            closeBtn.hidden = currentStep !== 1;
        }
        if (currentStep === 2 && reportCoords) {
            initReportMap(reportCoords.lat, reportCoords.lng);
            if (reportMap) {
                setTimeout(() => reportMap.invalidateSize(), 60);
            }
        }
        if (currentStep === 3) {
            updateSummary();
            updateSummaryMedia();
            if (reportCoords) {
                initReviewMap(reportCoords.lat, reportCoords.lng);
                if (reviewMap) {
                    setTimeout(() => reviewMap.invalidateSize(), 60);
                }
            }
        }
    };

    const setLocationFromQuery = () => {
        const params = new URLSearchParams(window.location.search);
        const lat = params.get("lat");
        const lng = params.get("lng");
        if (locationInput && lat && lng) {
            reportCoords = { lat: Number(lat), lng: Number(lng) };
            locationInput.value = "Ieškoma adreso...";
            if (latInput) latInput.value = String(reportCoords.lat);
            if (lngInput) lngInput.value = String(reportCoords.lng);
            resizeLocationInput();
            reverseGeocode(lat, lng);
            if (currentStep === 2) {
                initReportMap(lat, lng);
            }
        }
    };

    const updateSummary = () => {
        if (summaryCategory) summaryCategory.textContent = selectedCategory || "Nepasirinkta";
        if (summaryDescription) summaryDescription.textContent = (descriptionInput && descriptionInput.value.trim()) || "Aprasymas neivestas.";
        if (summaryDate) summaryDate.textContent = (dateInput && dateInput.value) || "---- -- --";
        if (summaryTime) summaryTime.textContent = (timeInput && timeInput.value) || "--:--";
        if (summaryLocation) summaryLocation.textContent = (locationInput && locationInput.value.trim()) || "Adresas nepasirinktas.";
    };

    const syncSelectedCategory = () => {
        if (!selectedCategoryWrap) return;
        if (selectedCategory) {
            selectedCategoryWrap.hidden = false;
            if (selectedCategoryLabel) selectedCategoryLabel.textContent = selectedCategory;
            if (selectedCategoryIcon && selectedCategoryIconHtml) {
                selectedCategoryIcon.innerHTML = selectedCategoryIconHtml;
                selectedCategoryIcon.className = `category-icon ${selectedCategoryClass}`;
            }
            if (summaryCategoryIcon && selectedCategoryIconHtml) {
                summaryCategoryIcon.innerHTML = selectedCategoryIconHtml;
                summaryCategoryIcon.className = `category-icon ${selectedCategoryClass}`;
            }
        } else {
            selectedCategoryWrap.hidden = true;
        }
    };

    const resizeLocationInput = () => {
        if (!locationInput || locationInput.tagName !== "TEXTAREA") return;
        locationInput.style.height = "auto";
        locationInput.style.height = `${locationInput.scrollHeight}px`;
    };

    const updateSummaryMedia = () => {
        if (!summaryMedia || !summaryPhoto || !summaryPhotoPlaceholder) return;
        if (previewUrl) {
            summaryPhoto.src = previewUrl;
            summaryPhoto.hidden = false;
            summaryPhotoPlaceholder.hidden = true;
            summaryMedia.hidden = false;
        } else {
            summaryPhoto.src = "";
            summaryPhoto.hidden = true;
            summaryPhotoPlaceholder.hidden = false;
            summaryMedia.hidden = true;
        }
    };

    categoryButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            categoryButtons.forEach(item => item.classList.remove("selected"));
            btn.classList.add("selected");
            selectedCategory = btn.getAttribute("data-category") || btn.textContent.trim();
            const icon = btn.querySelector(".category-icon");
            selectedCategoryIconHtml = icon ? icon.innerHTML : "";
            selectedCategoryClass = Array.from(btn.classList).find(cls => cls.startsWith("cat-")) || "cat-flag";
            if (selectedCategoryIcon && selectedCategoryIconHtml) {
                selectedCategoryIcon.innerHTML = selectedCategoryIconHtml;
                selectedCategoryIcon.className = `category-icon ${selectedCategoryClass}`;
            }
            if (selectedCategoryLabel) {
                selectedCategoryLabel.textContent = selectedCategory;
            }
            if (summaryCategoryIcon && selectedCategoryIconHtml) {
                summaryCategoryIcon.innerHTML = selectedCategoryIconHtml;
                summaryCategoryIcon.className = `category-icon ${selectedCategoryClass}`;
            }
            if (categoryInput) {
                categoryInput.value = selectedCategory;
            }
            if (nextBtn1) nextBtn1.disabled = false;
        });
    });

    if (nextBtn1) {
        nextBtn1.addEventListener("click", () => {
            if (!selectedCategory) return;
            setStep(2);
            syncSelectedCategory();
        });
    }

    const isDescriptionValid = () => descriptionInput && descriptionInput.value.trim().length > 0;
    const updateNextBtn2 = () => {
        if (nextBtn2) nextBtn2.disabled = !isDescriptionValid();
    };

    if (nextBtn2) {
        nextBtn2.addEventListener("click", () => {
            if (!isDescriptionValid()) {
                if (descriptionField) descriptionField.classList.add("is-empty");
                return;
            }
            if (fileInput && fileInput.files && fileInput.files.length) {
                const check = validateSelectedPhotos(fileInput.files);
                if (!check.ok) {
                    showPhotoError(check.message);
                    return;
                }
            }
            clearPhotoError();
            updateSummary();
            setStep(3);
            syncSelectedCategory();
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener("click", () => {
            updateSummary();
        });
    }

    if (backBtn) {
        backBtn.addEventListener("click", () => {
            setStep(Math.max(1, currentStep - 1));
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", () => {
            window.location.href = "index.php";
        });
    }

    if (fileInput && fileName) {
        fileInput.addEventListener("change", () => {
            const files = fileInput.files;
            if (files && files.length) {
                const check = validateSelectedPhotos(files);
                if (!check.ok) {
                    showPhotoError(check.message);
                    fileInput.value = "";
                    fileName.textContent = "Nuotrauka nepasirinkta";
                    if (previewUrl) {
                        URL.revokeObjectURL(previewUrl);
                        previewUrl = "";
                    }
                    if (previewImage) {
                        previewImage.src = "";
                        previewImage.hidden = true;
                        previewImage.parentElement?.classList.remove("has-preview");
                    }
                    updateSummaryMedia();
                    return;
                }
            }
            clearPhotoError();
            fileName.textContent = files && files.length ? files[0].name : "Nuotrauka nepasirinkta";
            if (previewImage) {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    previewUrl = "";
                }
                if (files && files.length) {
                    previewUrl = URL.createObjectURL(files[0]);
                    previewImage.src = previewUrl;
                    previewImage.hidden = false;
                    previewImage.parentElement?.classList.add("has-preview");
                } else {
                    previewImage.src = "";
                    previewImage.hidden = true;
                    previewImage.parentElement?.classList.remove("has-preview");
                }
            }
            updateSummaryMedia();
        });
    }

    if (reportForm && fileInput) {
        reportForm.addEventListener("submit", (e) => {
            const check = validateSelectedPhotos(fileInput.files);
            if (!check.ok) {
                e.preventDefault();
                showPhotoError(check.message);
                setStep(2);
            }
        });
    }

    if (descriptionInput && descriptionField) {
        const updateDescriptionState = () => {
            const isEmpty = !descriptionInput.value.trim();
            descriptionField.classList.toggle("is-empty", isEmpty);
            updateNextBtn2();
        };
        descriptionInput.addEventListener("input", updateDescriptionState);
        updateDescriptionState();
    }

    resizeLocationInput();

    const setLocationEditMode = (isActive) => {
        selectingLocation = isActive;
        if (mapActions) mapActions.hidden = !isActive;
        if (mapShell) mapShell.classList.toggle("is-selecting", isActive);
        if (reportMarker && reportMarker.dragging) {
            if (isActive) {
                reportMarker.dragging.enable();
            } else {
                reportMarker.dragging.disable();
            }
        }
    };

    if (locationEditBtn) {
        locationEditBtn.addEventListener("click", () => {
            if (!reportCoords) return;
            previousCoords = { ...reportCoords };
            pendingCoords = { ...reportCoords };
            if (!reportMap) {
                initReportMap(reportCoords.lat, reportCoords.lng);
            }
            if (reportMap) {
                reportMap.setView([reportCoords.lat, reportCoords.lng], reportMap.getZoom() || 16);
                setTimeout(() => reportMap.invalidateSize(), 60);
            }
            setLocationEditMode(true);
        });
    }

    if (mapCancelBtn) {
        mapCancelBtn.addEventListener("click", () => {
            if (previousCoords && reportMarker && reportMap) {
                reportMarker.setLatLng(previousCoords);
                reportMap.setView([previousCoords.lat, previousCoords.lng], reportMap.getZoom() || 16);
            }
            pendingCoords = null;
            setLocationEditMode(false);
        });
    }

    if (mapConfirmBtn) {
        mapConfirmBtn.addEventListener("click", () => {
            if (!pendingCoords) return;
            reportCoords = { lat: pendingCoords.lat, lng: pendingCoords.lng };
            if (locationInput) locationInput.value = "Ieškoma adreso...";
            if (latInput) latInput.value = String(reportCoords.lat);
            if (lngInput) lngInput.value = String(reportCoords.lng);
            reverseGeocode(reportCoords.lat, reportCoords.lng);
            updateSummary();
            if (reviewMap && reportCoords) {
                initReviewMap(reportCoords.lat, reportCoords.lng);
                setTimeout(() => reviewMap.invalidateSize(), 60);
            }
            setLocationEditMode(false);
        });
    }

    const setDefaultDateTime = () => {
        const now = new Date();
        if (dateInput && !dateInput.value) {
            dateInput.value = now.toISOString().slice(0, 10);
        }
        if (timeInput && !timeInput.value) {
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");
            timeInput.value = `${hours}:${minutes}`;
        }
    };

    // Atvirkstinis geokodavimas — uzpildo adreso lauka report.php 2 zingsnyje
    const reverseGeocode = (lat, lng) => {
        const url = `reverse-geocode.php?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
        fetch(url, { headers: { "Accept": "application/json" } })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (!locationInput) return;
                if (data && data.display_name) {
                    locationInput.value = data.display_name;
                } else {
                    locationInput.value = "Adreso rasti nepavyko";
                }
                resizeLocationInput();
                if (addressInput) addressInput.value = locationInput.value;
                updateSummary();
            })
            .catch(() => {});
    };

    const initReportMap = (lat, lng) => {
        if (!reportMapEl || typeof L === "undefined") return;
        if (!reportMap) {
            reportMap = L.map(reportMapEl, { zoomControl: false, attributionControl: false });
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19
            }).addTo(reportMap);
            reportMap.on("click", (e) => {
                if (!selectingLocation) return;
                pendingCoords = { lat: e.latlng.lat, lng: e.latlng.lng };
                if (reportMarker) {
                    reportMarker.setLatLng(e.latlng);
                }
            });
        }
        const coords = [Number(lat), Number(lng)];
        reportMap.setView(coords, 16);
        if (!reportMarker) {
            reportMarker = L.marker(coords, { draggable: true }).addTo(reportMap);
            if (reportMarker.dragging) {
                reportMarker.dragging.disable();
            }
            reportMarker.on("dragend", () => {
                if (!selectingLocation) return;
                const next = reportMarker.getLatLng();
                pendingCoords = { lat: next.lat, lng: next.lng };
            });
        } else {
            reportMarker.setLatLng(coords);
        }
        setTimeout(() => reportMap.invalidateSize(), 60);
    };

    const initReviewMap = (lat, lng) => {
        if (!reviewMapEl || typeof L === "undefined") return;
        if (!reviewMap) {
            reviewMap = L.map(reviewMapEl, { zoomControl: false, attributionControl: false, dragging: false });
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19
            }).addTo(reviewMap);
        }
        const coords = [Number(lat), Number(lng)];
        reviewMap.setView(coords, 16);
        if (!reviewMarker) {
            reviewMarker = L.marker(coords).addTo(reviewMap);
        } else {
            reviewMarker.setLatLng(coords);
        }
    };

    setLocationFromQuery();
    setDefaultDateTime();
    if (nextBtn1) nextBtn1.disabled = true;
    setStep(1);
    syncSelectedCategory();
}



