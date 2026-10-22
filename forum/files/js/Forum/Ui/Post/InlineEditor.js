/**
 * Inline editor for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Message/InlineEditor"], function (require, exports, tslib_1, InlineEditor_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.PostInlineEditor = void 0;
    InlineEditor_1 = tslib_1.__importDefault(InlineEditor_1);

    class PostInlineEditor extends InlineEditor_1.default {
        constructor(threadId) {
            super({
                className: "forum\\data\\post\\PostAction",
                containerId: threadId.toString(),
                editorPrefix: "messageEditor",
            });
        }
    }
    exports.PostInlineEditor = PostInlineEditor;
});
