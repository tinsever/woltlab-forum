/**
 * Collapses and expands the categories of the board list.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "WoltLabSuite/Core/Ajax/Backend"], function (require, exports, Backend_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;

    function toggle(button) {
        const category = button.closest(".forumBoardList__category, .forumBoardCategory");
        const boards = document.getElementById(button.getAttribute("aria-controls"));
        if (!category || !boards) {
            return;
        }

        const collapsed = !category.classList.contains("forumBoardCategory--collapsed");
        category.classList.toggle("forumBoardCategory--collapsed", collapsed);
        boards.hidden = collapsed;
        button.setAttribute("aria-expanded", collapsed ? "false" : "true");

        void (0, Backend_1.prepareRequest)(`${window.WSC_RPC_API_URL}forum/boards/${category.dataset.boardId}/collapsed`)
            .post({ collapsed })
            .fetchAsJson();
    }

    function setup() {
        document.querySelectorAll(".forumBoardCategory__toggle").forEach((button) => {
            if (button.dataset.forumInitialized) {
                return;
            }
            button.dataset.forumInitialized = "true";
            button.addEventListener("click", () => toggle(button));
        });
    }
});
