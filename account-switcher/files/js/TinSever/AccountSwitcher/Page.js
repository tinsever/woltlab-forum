/**
 * Buttons of the page that lists the linked accounts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "WoltLabSuite/Core/Ajax/Backend", "WoltLabSuite/Core/Component/Confirmation", "WoltLabSuite/Core/Component/Dialog", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Ui/User/Search/Input"], function (require, exports, Backend_1, Confirmation_1, Dialog_1, Notification_1, Input_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;

    /**
     * Suggests usernames while typing, skipping the accounts that are already linked.
     */
    function setupUsernameSuggestions() {
        const observer = new MutationObserver(() => {
            const input = document.getElementById("accountSwitcherLink_username");
            if (input === null || input.dataset.accountSwitcherSuggestions) {
                return;
            }
            input.dataset.accountSwitcherSuggestions = "true";
            observer.disconnect();

            const excludedSearchValues = Array.from(document.querySelectorAll(".accountSwitcherList .username")).map((username) => username.textContent.trim());
            new Input_1(input, { excludedSearchValues });
        });
        observer.observe(document.body, { childList: true, subtree: true });

        return observer;
    }

    async function openLinkDialog(button) {
        const observer = setupUsernameSuggestions();
        let response;
        try {
            response = await (0, Dialog_1.dialogFactory)().usingFormBuilder().fromEndpoint(button.dataset.endpoint);
        }
        finally {
            observer.disconnect();
        }
        const { ok, result } = response;
        if (!ok) {
            return;
        }

        if (result?.redirectUrl) {
            window.location.href = result.redirectUrl;
            return;
        }

        (0, Notification_1.show)(result?.message, () => window.location.reload());
    }

    async function switchTo(button) {
        button.disabled = true;
        try {
            const response = await (0, Backend_1.prepareRequest)(`${window.WSC_RPC_API_URL}account-switcher/accounts/${button.dataset.userId}/switch`)
                .post({ returnUrl: window.location.href.split("#")[0] })
                .fetchAsJson();

            window.location.assign(response.redirectUrl ?? window.location.href.split("#")[0]);
        }
        finally {
            button.disabled = false;
        }
    }

    async function unlink(button) {
        const confirmed = await (0, Confirmation_1.confirmationFactory)().custom(button.dataset.confirmMessage).withoutMessage();
        if (!confirmed) {
            return;
        }

        await (0, Backend_1.prepareRequest)(`${window.WSC_RPC_API_URL}account-switcher/accounts/${button.dataset.userId}/unlink`)
            .post()
            .fetchAsJson();

        (0, Notification_1.show)(undefined, () => window.location.reload());
    }

    function setup(openDialog) {
        const linkButton = document.querySelector(".accountSwitcherLinkButton");
        linkButton?.addEventListener("click", () => void openLinkDialog(linkButton));

        document.querySelectorAll(".accountSwitcherSwitchButton").forEach((button) => {
            button.addEventListener("click", () => void switchTo(button));
        });
        document.querySelectorAll(".accountSwitcherUnlinkButton").forEach((button) => {
            button.addEventListener("click", () => void unlink(button));
        });

        if (openDialog && linkButton) {
            // Drop the parameter, so that a reload does not open the dialog again.
            window.history.replaceState(null, "", window.location.href.replace(/[?&]link=1(?=&|#|$)/, ""));

            void openLinkDialog(linkButton);
        }
    }
});
