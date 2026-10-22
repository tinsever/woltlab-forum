/**
 * Handles the subscription dropdowns of boards and threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "WoltLabSuite/Core/Ajax/Backend", "WoltLabSuite/Core/Component/Snackbar"], function (require, exports, Backend_1, Snackbar_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;

    const icons = {
        "": ["bookmark", false],
        watching: ["bookmark", true],
        ignoring: ["eye-slash", false],
    };

    async function changeStatus(container, status) {
        const response = await (0, Backend_1.prepareRequest)(container.dataset.endpoint)
            .post({ status })
            .fetchAsJson();

        container.dataset.status = response.status;
        container.querySelectorAll(".dropdownMenu li").forEach((item) => {
            const button = item.querySelector("button");
            item.classList.toggle("active", button.dataset.status === response.status);
        });

        const icon = container.querySelector(".dropdownToggle fa-icon");
        if (icon) {
            const [name, solid] = icons[response.status] || icons[""];
            icon.setIcon(name, solid);
        }

        const label = container.querySelector(".dropdownToggle span");
        const key = response.status ? "label" + response.status.charAt(0).toUpperCase() + response.status.slice(1) : "labelDefault";
        if (label && container.dataset[key]) {
            label.textContent = container.dataset[key];
        }

        (0, Snackbar_1.showDefaultSuccessSnackbar)();
    }

    function setup() {
        document.querySelectorAll(".forumSubscription").forEach((container) => {
            if (container.dataset.forumInitialized) {
                return;
            }
            container.dataset.forumInitialized = "true";

            container.querySelectorAll(".dropdownMenu button[data-status]").forEach((button) => {
                button.addEventListener("click", () => {
                    void changeStatus(container, button.dataset.status);
                });
            });
        });
    }
});
