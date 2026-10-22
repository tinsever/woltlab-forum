<?php

namespace forum\data\post;

use forum\system\board\BoardBreadcrumbs;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\edit\history\entry\EditHistoryEntry;
use wcf\system\edit\IHistorySavingObject;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;

/**
 * Post that supports the edit history.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin Post
 * @extends DatabaseObjectDecorator<Post>
 */
class HistorySavingPost extends DatabaseObjectDecorator implements IHistorySavingObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Post::class;

    #[\Override]
    public function getEditReason()
    {
        return $this->editReason ?? '';
    }

    #[\Override]
    public function getMessage()
    {
        return $this->message;
    }

    #[\Override]
    public function getTitle(): string
    {
        return $this->getDecoratedObject()->getTitle();
    }

    #[\Override]
    public function getLink(): string
    {
        return $this->getDecoratedObject()->getLink();
    }

    #[\Override]
    public function getTime()
    {
        return $this->lastEditTime ?: $this->time;
    }

    #[\Override]
    public function getUserID()
    {
        return $this->editorID ?: $this->userID;
    }

    #[\Override]
    public function getUsername()
    {
        return $this->editor ?: $this->username;
    }

    #[\Override]
    public function revertVersion(EditHistoryEntry $edit)
    {
        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->processIntermediate($edit->message);

        (new PostAction([$this->getDecoratedObject()], 'update', [
            'data' => [
                'editorID' => WCF::getUser()->userID,
                'editor' => WCF::getUser()->username,
                'lastEditTime' => TIME_NOW,
                'editCount' => $this->editCount + 1,
                'editReason' => WCF::getLanguage()->getDynamicVariable('wcf.edit.reverted', ['edit' => $edit]),
            ],
            'htmlInputProcessor' => $htmlInputProcessor,
            'isEdit' => true,
        ]))->executeAction();
    }

    #[\Override]
    public function setLocation()
    {
        BoardBreadcrumbs::addThread($this->getDecoratedObject()->getThread());
    }
}
