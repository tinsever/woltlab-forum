/**
 * User panel menu to switch between linked accounts. Registers itself as a
 * regular user menu provider, therefore it is also part of the mobile menu.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ajax/Backend", "WoltLabSuite/Core/Dom/Change/Listener", "WoltLabSuite/Core/Language", "WoltLabSuite/Core/Ui/User/Menu/Manager", "focus-trap"], function (require, exports, tslib_1, Backend_1, DomChangeListener, Language, Manager_1, focus_trap_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    DomChangeListener = tslib_1.__importStar(DomChangeListener);
    Language = tslib_1.__importStar(Language);

    // Reload the accounts when the menu is opened again after this time.
    const STALE_AFTER = 60 * 1000;

    class AccountSwitcherView {
        element;
        content;
        search;
        searchInput;
        focusTrap;
        options;
        provider;
        lastLoad = 0;
        loading = undefined;

        constructor(provider, options) {
            this.provider = provider;
            this.options = options;
            this.element = this.buildElement();
            this.content = this.element.querySelector(".userMenuContent");
            this.search = this.element.querySelector(".accountSwitcherMenu__search");
            this.searchInput = this.search.querySelector("input");

            this.focusTrap = (0, focus_trap_1.createFocusTrap)(this.element, {
                allowOutsideClick: true,
                escapeDeactivates: () => {
                    this.element.dispatchEvent(new Event("shouldClose"));
                    return false;
                },
                fallbackFocus: this.element,
                initialFocus: () => (this.search.hidden ? this.element : this.searchInput),
            });

            this.searchInput.addEventListener("input", () => this.filter());
            this.searchInput.addEventListener("keydown", (event) => this.searchKeydown(event));
            this.content.addEventListener("keydown", (event) => this.listKeydown(event));
            this.content.addEventListener("click", (event) => {
                const button = event.target.closest(".accountSwitcher__switch");
                if (button && !button.disabled) {
                    void this.switchTo(button);
                }
            });
        }

        getElement() {
            return this.element;
        }

        async open() {
            this.element.hidden = false;

            const isStale = Date.now() - this.lastLoad > STALE_AFTER;
            if (this.lastLoad === 0) {
                this.content.innerHTML = `<span class="userMenuContentStatus"><woltlab-core-loading-indicator size="24" hide-text></woltlab-core-loading-indicator></span>`;
            }

            this.focusTrap.activate();

            if (isStale) {
                await this.load();
                if (!this.search.hidden) {
                    this.searchInput.focus();
                }
            }
        }

        close() {
            this.focusTrap.deactivate();
            this.element.hidden = true;
        }

        async load() {
            if (this.loading === undefined) {
                this.loading = (0, Backend_1.prepareRequest)(`${window.WSC_RPC_API_URL}account-switcher/accounts`)
                    .get()
                    .disableLoadingIndicator()
                    .fetchAsJson()
                    .then((response) => {
                    this.content.innerHTML = response.template;
                    this.lastLoad = Date.now();
                    this.provider.setUnreadCount(response.unreadCount);

                    const hasAccounts = this.getItems().length > 0;
                    this.search.hidden = !this.options.enableSearch || !hasAccounts;
                    this.filter();

                    DomChangeListener.trigger();
                })
                    .finally(() => {
                    this.loading = undefined;
                });
            }

            return this.loading;
        }

        getItems() {
            return Array.from(this.content.querySelectorAll(".accountSwitcher__item"));
        }

        getVisibleButtons() {
            return this.getItems()
                .filter((item) => !item.hidden)
                .map((item) => item.querySelector(".accountSwitcher__switch"))
                .filter((button) => !button.disabled);
        }

        filter() {
            const query = this.search.hidden ? "" : this.searchInput.value.trim().toLocaleLowerCase();
            let visible = 0;
            this.getItems().forEach((item) => {
                const matches = query === "" || item.dataset.username.toLocaleLowerCase().includes(query);
                item.hidden = !matches;
                if (matches) {
                    visible++;
                }
            });

            const noResults = this.content.querySelector(".accountSwitcher__noResults");
            if (noResults) {
                noResults.hidden = visible > 0;
            }
        }

        searchKeydown(event) {
            if (event.key === "ArrowDown") {
                event.preventDefault();
                this.getVisibleButtons()[0]?.focus();
            }
            else if (event.key === "Enter") {
                event.preventDefault();
                const buttons = this.getVisibleButtons();
                if (buttons.length > 0) {
                    void this.switchTo(buttons[0]);
                }
            }
        }

        listKeydown(event) {
            if (event.key !== "ArrowDown" && event.key !== "ArrowUp") {
                return;
            }

            const buttons = this.getVisibleButtons();
            const index = buttons.indexOf(document.activeElement);
            if (index === -1) {
                return;
            }

            event.preventDefault();
            if (event.key === "ArrowDown") {
                buttons[(index + 1) % buttons.length].focus();
            }
            else if (index === 0 && !this.search.hidden) {
                this.searchInput.focus();
            }
            else {
                buttons[(index - 1 + buttons.length) % buttons.length].focus();
            }
        }

        async switchTo(button) {
            if (this.element.classList.contains("accountSwitcherMenu--switching")) {
                return;
            }

            this.element.classList.add("accountSwitcherMenu--switching");
            this.content.querySelectorAll(".accountSwitcher__switch").forEach((otherButton) => {
                otherButton.disabled = true;
            });

            const indicator = document.createElement("woltlab-core-loading-indicator");
            indicator.setAttribute("size", "24");
            indicator.setAttribute("hide-text", "");
            button.querySelector(".accountSwitcher__meta").replaceChildren(indicator);

            try {
                const response = await (0, Backend_1.prepareRequest)(`${window.WSC_RPC_API_URL}account-switcher/accounts/${button.dataset.userId}/switch`)
                    .post({ returnUrl: window.location.href })
                    .fetchAsJson();

                window.location.assign(response.redirectUrl ?? window.location.href.split("#")[0]);
            }
            catch (error) {
                this.element.classList.remove("accountSwitcherMenu--switching");
                this.lastLoad = 0;
                void this.load();

                throw error;
            }
        }

        buildElement() {
            const element = document.createElement("div");
            element.hidden = true;
            element.classList.add("userMenu", "accountSwitcherMenu");
            element.dataset.origin = this.provider.getPanelButton().id;
            element.tabIndex = -1;
            element.innerHTML = `
        <div class="userMenuHeader">
          <div class="userMenuTitle">${Language.get("wcf.accountSwitcher.title")}</div>
          <div class="userMenuButtons">
            <a href="${this.options.linkLink}" class="userMenuButton jsTooltip" title="${Language.get("wcf.accountSwitcher.link")}">
              <fa-icon size="24" name="link"></fa-icon>
            </a>
          </div>
        </div>
        <div class="accountSwitcherMenu__search" hidden>
          <input type="search" class="long" autocomplete="off" spellcheck="false">
        </div>
        <div class="userMenuContent userMenuContentScrollable"></div>
        <div class="userMenuFooter">
          <a href="${this.options.manageLink}" class="userMenuFooterLink">${Language.get("wcf.accountSwitcher.manage")}</a>
        </div>
      `;

            const input = element.querySelector("input");
            input.placeholder = Language.get("wcf.accountSwitcher.search.placeholder");
            input.setAttribute("aria-label", Language.get("wcf.accountSwitcher.search.placeholder"));

            // Prevent clicks inside the menu from closing it.
            element.addEventListener("click", (event) => event.stopPropagation());

            return element;
        }
    }

    class AccountSwitcherProvider {
        button;
        options;
        view = undefined;

        constructor(button, options) {
            this.button = button;
            this.options = options;
        }

        getIdentifier() {
            return "com.tinsever.wcf.accountSwitcher";
        }

        getPanelButton() {
            return this.button;
        }

        getView() {
            if (this.view === undefined) {
                this.view = new AccountSwitcherView(this, this.options);
            }

            return this.view;
        }

        hasUnreadContent() {
            return this.button.querySelector(".badge.badgeUpdate") !== null;
        }

        setUnreadCount(count) {
            const link = this.button.querySelector("a");
            let badge = link.querySelector(".badge.badgeUpdate");
            if (count > 0) {
                if (badge === null) {
                    badge = document.createElement("span");
                    badge.classList.add("badge", "badgeUpdate");
                    link.append(badge);
                }
                badge.textContent = count.toLocaleString();
            }
            else {
                badge?.remove();
            }

            this.button.dataset.count = count.toString();
        }
    }

    function setup(options) {
        const button = document.getElementById("accountSwitcher");
        if (button === null || button.dataset.accountSwitcherInitialized) {
            return;
        }
        button.dataset.accountSwitcherInitialized = "true";

        (0, Manager_1.registerProvider)(new AccountSwitcherProvider(button, options));
    }
});
