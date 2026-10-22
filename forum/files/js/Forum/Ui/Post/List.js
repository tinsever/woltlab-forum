/**
 * Connects the posts of a thread to the interaction framework: forwards clicks in
 * the per-post interaction menus, refreshes the page after an interaction and,
 * for moderators, provides the selection of posts for bulk interactions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Helper/Selector", "WoltLabSuite/Core/Ui/Dropdown/Simple", "WoltLabSuite/Core/Component/ListView/Selection", "WoltLabSuite/Core/Api/Interactions/GetBulkContextMenuOptions", "WoltLabSuite/Core/Component/Dialog"], function (require, exports, tslib_1, Selector_1, Simple_1, Selection_1, GetBulkContextMenuOptions_1, Dialog_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    Simple_1 = tslib_1.__importDefault(Simple_1);

    function getDropdownMenu(toggle) {
        return Simple_1.default.getDropdownMenu(toggle.dataset.target) ?? toggle.closest(".dropdown")?.querySelector(".dropdownMenu");
    }

    function initInteractions(container) {
        (0, Selector_1.wheneverFirstSeen)(`#${container.id} .forumPost__interactions .dropdownToggle`, (toggle) => {
            const item = toggle.closest("[data-object-id]");
            getDropdownMenu(toggle)?.querySelectorAll("[data-interaction]").forEach((button) => {
                button.addEventListener("click", () => {
                    item.dispatchEvent(new CustomEvent("interaction:execute", {
                        detail: button.dataset,
                        bubbles: true,
                    }));
                });
            });
        });
    }

    function initSelection(container, providerClassName) {
        const footer = document.getElementById(`${container.id}_footer`);
        const selection = new Selection_1.Selection(container.id, container);
        let loading = false;

        const updateFooter = () => {
            footer.hidden = selection.getSelectedIds().length === 0;
        };
        selection.addEventListener("list-view:update-selection", updateFooter);
        selection.addEventListener("list-view:get-bulk-interactions", async (event) => {
            if (loading) {
                return;
            }
            loading = true;
            try {
                const { template } = await (0, GetBulkContextMenuOptions_1.getBulkContextMenuOptions)(providerClassName, event.detail.objectIds);
                selection.setBulkInteractionContextMenuOptions(template);
            }
            finally {
                loading = false;
            }
        });
        updateFooter();

        return selection;
    }

    function initDialogInteractions(container, selection, identifiers) {
        container.addEventListener("bulk-interaction", async (event) => {
            if (!identifiers.includes(event.detail.bulkInteraction)) {
                return;
            }

            const { ok, result } = await (0, Dialog_1.dialogFactory)().usingFormBuilder().fromEndpoint(event.detail.endpoint);
            if (!ok) {
                return;
            }

            selection.resetSelection();
            if (result?.redirectUrl) {
                window.location.href = result.redirectUrl;
            }
            else {
                window.location.reload();
            }
        });
    }

    function setup(containerId, bulkInteractionProviderClassName = "", dialogInteractions = []) {
        const container = document.getElementById(containerId);
        if (container === null) {
            return;
        }

        initInteractions(container);

        const selection = bulkInteractionProviderClassName !== "" ? initSelection(container, bulkInteractionProviderClassName) : null;
        if (selection !== null) {
            initDialogInteractions(container, selection, dialogInteractions);
        }

        // Bulk interactions report every processed post individually, the page
        // must only be reloaded once all of them have been handled.
        let bulkInteractionRunning = false;
        const reload = () => window.location.reload();

        container.addEventListener("bulk-interaction", (event) => {
            if (!dialogInteractions.includes(event.detail.bulkInteraction)) {
                bulkInteractionRunning = true;
            }
        });
        container.addEventListener("interaction:execute", () => {
            bulkInteractionRunning = false;
        });
        container.addEventListener("interaction:invalidate", () => {
            if (!bulkInteractionRunning) {
                reload();
            }
        });
        container.addEventListener("interaction:invalidate-all", reload);
        container.addEventListener("interaction:remove", (event) => {
            if (!bulkInteractionRunning) {
                event.target.closest(".listView__item, li")?.remove();
            }
        });
        container.addEventListener("interaction:reset-selection", () => {
            selection?.resetSelection();
            if (bulkInteractionRunning) {
                reload();
            }
        });
    }
});
