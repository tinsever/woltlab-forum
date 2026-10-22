/**
 * Marks all boards or a single board as read.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "WoltLabSuite/Core/Ajax/Backend", "WoltLabSuite/Core/Component/Snackbar"], function (require, exports, Backend_1, Snackbar_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;

    async function markAsRead(button) {
        const boardId = button.dataset.boardId;
        const url = boardId
            ? `${window.WSC_RPC_API_URL}forum/boards/${boardId}/mark-as-read`
            : `${window.WSC_RPC_API_URL}forum/boards/mark-as-read`;

        await (0, Backend_1.prepareRequest)(url).post().fetchAsJson();

        document.querySelectorAll(".forumBoard--new").forEach((element) => element.classList.remove("forumBoard--new"));
        document.querySelectorAll(".forumBoard__subBoard--new").forEach((element) => element.classList.remove("forumBoard__subBoard--new"));
        document.querySelectorAll(".forumBoard__unreadCount, .newMessageBadge").forEach((element) => element.remove());
        document.querySelectorAll(".forumThreadList__item--new").forEach((element) => {
            element.classList.remove("forumThreadList__item--new");
            element.querySelector(".listView__item__markAsRead")?.remove();
        });

        (0, Snackbar_1.showDefaultSuccessSnackbar)();
    }

    function setup() {
        document.querySelectorAll(".forumMarkAsReadButton").forEach((button) => {
            if (button.dataset.forumInitialized) {
                return;
            }
            button.dataset.forumInitialized = "true";

            let pending = false;
            button.addEventListener("click", () => {
                if (pending) {
                    return;
                }
                pending = true;
                void markAsRead(button).finally(() => {
                    pending = false;
                });
            });
        });
    }
});
