/**
 * Global forum JavaScript, executed on every page.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "WoltLabSuite/Core/LazyLoader", "WoltLabSuite/Core/Ajax/Backend"], function (require, exports, LazyLoader_1, Backend_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;

    function setupPopover(selector, identifier, path) {
        (0, LazyLoader_1.whenFirstSeen)(selector, () => {
            require(["WoltLabSuite/Core/Component/Popover"], ({ setupFor }) => {
                setupFor({
                    endpoint: async (objectId) => {
                        const response = await (0, Backend_1.prepareRequest)(`${window.WSC_RPC_API_URL}forum/${path}/${objectId}/popover`)
                            .get()
                            .fetchAsJson();
                        return response.template;
                    },
                    identifier,
                    selector,
                });
            });
        });
    }

    /**
     * Shows the active sort field as the label of the sort button in thread lists.
     */
    function setupThreadListSorting() {
        (0, LazyLoader_1.whenFirstSeen)(".listView__items.forumThreadList", () => {
            document.querySelectorAll(".listView__items.forumThreadList").forEach((items) => {
                const listView = items.closest(".listView");
                if (!listView || listView.classList.contains("forumThreadListView")) {
                    return;
                }
                listView.classList.add("forumThreadListView");

                const viewId = items.id.replace(/_items$/, "");
                const menu = document.getElementById(`${viewId}_sorting`);
                const label = listView.querySelector(".listView__header__button.dropdown .dropdownToggle span");
                if (!menu || !label) {
                    return;
                }

                const update = () => {
                    const active = menu.querySelector(".listView__sorting__button.active");
                    if (active) {
                        label.textContent = active.textContent.trim();
                    }
                };
                update();
                new MutationObserver(update).observe(menu, { attributes: true, subtree: true, attributeFilter: ["class"] });
            });
        });
    }

    function setup() {
        setupThreadListSorting();
        setupPopover(".forumThreadLink", "com.tinsever.forum.thread", "threads");
        setupPopover(".forumPostLink", "com.tinsever.forum.post", "posts");
    }
});
