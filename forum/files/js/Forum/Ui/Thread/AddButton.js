/**
 * Asks for the target board before a new thread is started from outside of a board.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "WoltLabSuite/Core/Component/Dialog"], function (require, exports, Dialog_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;

    function setup() {
        document.querySelectorAll(".forumThreadAddButton[data-endpoint]").forEach((button) => {
            if (button.dataset.forumInitialized) {
                return;
            }
            button.dataset.forumInitialized = "true";

            button.addEventListener("click", async (event) => {
                event.preventDefault();

                const { ok, result } = await (0, Dialog_1.dialogFactory)().usingFormBuilder().fromEndpoint(button.dataset.endpoint);
                if (ok && result?.redirectUrl) {
                    window.location.href = result.redirectUrl;
                }
            });
        });
    }
});
