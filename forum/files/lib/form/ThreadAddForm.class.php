<?php

namespace forum\form;

use forum\system\board\BoardBreadcrumbs;
use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardNodeTree;
use forum\data\post\Post;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use forum\system\label\object\ThreadLabelObjectHandler;
use wcf\data\user\User;
use wcf\event\message\MessageSpamChecking;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\event\EventHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\flood\FloodControl;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\CaptchaFormField;
use wcf\system\form\builder\field\label\LabelFormField;
use wcf\system\form\builder\field\language\ContentLanguageFormField;
use wcf\system\form\builder\field\RadioButtonFormField;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\field\tag\TagFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\field\wysiwyg\WysiwygFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\page\PageLocationManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\UserUtil;

/**
 * Shows the form to start a new thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractFormBuilderForm<Thread>
 */
class ThreadAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $objectActionClass = ThreadAction::class;

    /**
     * @inheritDoc
     */
    public $formAction = 'create';

    public int $boardID = 0;

    public ?Board $board = null;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['id'])) {
            $this->boardID = \intval($_REQUEST['id']);
        }

        if ($this->boardID) {
            $this->board = BoardCache::getInstance()->getBoard($this->boardID);
            if ($this->board === null || !$this->board->isBoard()) {
                throw new IllegalLinkException();
            }
            if (!$this->board->canStartThread()) {
                throw new PermissionDeniedException();
            }
        } elseif ($this->getSelectableBoardOptions() === []) {
            throw new PermissionDeniedException();
        }

        $this->enforceFloodControl();
    }

    /**
     * Prevents users from creating threads too quickly.
     */
    protected function enforceFloodControl(): void
    {
        $limit = \intval(WCF::getSession()->getPermission('user.forum.floodControlTime'));
        if (!$limit) {
            return;
        }

        $lastTime = FloodControl::getInstance()->getLastTime('com.tinsever.forum.thread');
        if ($lastTime !== null && $lastTime > TIME_NOW - $limit) {
            throw new NamedUserException(WCF::getLanguage()->getDynamicVariable('forum.thread.error.floodControl', [
                'waitTime' => $lastTime + $limit - TIME_NOW,
            ]));
        }
    }

    /**
     * @return list<array{depth: int, label: string, value: int, isSelectable: bool}>
     */
    protected function getSelectableBoardOptions(): array
    {
        $options = (new BoardNodeTree(0, static fn(Board $board) => $board->isVisible() && $board->canEnter()))
            ->getNestedOptions(static fn(Board $board) => $board->canStartThread());

        foreach ($options as $option) {
            if ($option['isSelectable']) {
                return $options;
            }
        }

        return [];
    }

    /**
     * Returns the board that is used to determine the permissions while building the form.
     */
    protected function getEffectiveBoard(): ?Board
    {
        if ($this->board !== null) {
            return $this->board;
        }

        $boardID = \intval($_POST['boardID'] ?? 0);
        $board = BoardCache::getInstance()->getBoard($boardID);

        return ($board !== null && $board->canStartThread()) ? $board : null;
    }

    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        $board = $this->getEffectiveBoard();
        $isGuest = !WCF::getUser()->userID;

        $information = FormContainer::create('information')
            ->label('forum.thread.information')
            ->appendChildren([
                SelectFormField::create('boardID')
                    ->label('forum.board.board')
                    ->options($this->getSelectableBoardOptions(), true, false)
                    ->available($this->board === null)
                    ->required(),
                TextFormField::create('username')
                    ->label('wcf.user.username')
                    ->available($isGuest)
                    ->maximumLength(255)
                    ->required()
                    ->addValidator(new FormFieldValidator('username', static function (TextFormField $field) {
                        $username = (string)$field->getValue();
                        if (!UserUtil::isValidUsername($username)) {
                            $field->addValidationError(new FormFieldValidationError('invalid', 'wcf.user.username.error.invalid'));
                        } elseif (User::getUserByUsername($username)->userID) {
                            $field->addValidationError(new FormFieldValidationError('notUnique', 'wcf.user.username.error.notUnique'));
                        }
                    })),
                TextFormField::create('topic')
                    ->label('forum.thread.topic')
                    ->maximumLength(255)
                    ->censorship()
                    ->autoFocus()
                    ->required(),
                ContentLanguageFormField::create(),
            ]);

        if ($board !== null) {
            $labelGroups = ThreadLabelObjectHandler::getLabelGroupsForBoard($board->boardID);
            if ($labelGroups !== []) {
                $information->appendChildren(LabelFormField::createFields('com.tinsever.forum.thread', $labelGroups));
            }
        }

        $information->appendChild(
            TagFormField::create()
                ->objectType('com.tinsever.forum.thread')
                ->available(
                    \MODULE_TAGGING
                    && WCF::getSession()->getPermission('user.tag.canViewTag')
                    && ($board === null || $board->getPermission('canSetTags'))
                )
        );

        $canStartPoll = \MODULE_POLL && ($board === null || $board->getPermission('canStartPoll'));
        $canUpload = $board !== null && $board->getPermission('canUploadAttachment');

        $message = WysiwygFormContainer::create('message')
            ->label('forum.post.message')
            ->messageObjectType(Post::OBJECT_TYPE)
            ->supportMentions()
            ->supportQuotes()
            ->supportSmilies(WCF::getSession()->getPermission('user.message.canUseSmilies') ? true : false)
            ->required()
            ->autosaveId('com.tinsever.forum.threadAdd-' . ($board?->boardID ?: 0));
        if ($canUpload) {
            $message->attachmentData(Post::OBJECT_TYPE, $board->boardID);
        }
        if ($canStartPoll) {
            $message->pollObjectType(Post::OBJECT_TYPE);
        }
        $maxLength = \intval(WCF::getSession()->getPermission('user.forum.maxPostLength'));
        if ($maxLength) {
            $message->maximumLength($maxLength);
        }

        $settings = [];
        if ($board === null || $board->getPermission('canPinThread') || $board->getPermission('canStartAnnouncement')) {
            $typeOptions = [0 => 'forum.thread.type.normal'];
            if ($board === null || $board->getPermission('canPinThread')) {
                $typeOptions[1] = 'forum.thread.type.sticky';
            }
            if ($board === null || $board->getPermission('canStartAnnouncement')) {
                $typeOptions[2] = 'forum.thread.type.announcement';
            }
            $settings[] = RadioButtonFormField::create('type')
                ->label('forum.thread.type')
                ->options($typeOptions)
                ->value(0);
        }
        $settings[] = BooleanFormField::create('isClosed')
            ->label('forum.thread.isClosed')
            ->available($board === null || $board->getPermission('canCloseThread'));
        $settings[] = BooleanFormField::create('subscribeThread')
            ->label('forum.thread.subscribe')
            ->available(!$isGuest)
            ->value(\FORUM_THREAD_SUBSCRIBE_DEFAULT ? true : false);
        foreach ($settings as $setting) {
            $message->addSettingsNode($setting);
        }

        $this->form->appendChildren([
            $information,
            $message,
            CaptchaFormField::create()
                ->objectType(\CAPTCHA_TYPE)
                ->available($isGuest && \CAPTCHA_TYPE !== ''),
        ]);
    }

    #[\Override]
    protected function finalizeForm()
    {
        parent::finalizeForm();

        $this->form->getDataHandler()->addProcessor(new CustomFormDataProcessor(
            'forumThread',
            function (IFormDocument $document, array $parameters) {
                $data = $parameters['data'];
                $type = \intval($data['type'] ?? 0);

                $parameters['data'] = [
                    'boardID' => $this->board?->boardID ?? \intval($data['boardID']),
                    'topic' => $data['topic'],
                    'languageID' => $data['languageID'] ?? null,
                    'isClosed' => !empty($data['isClosed']) ? 1 : 0,
                    'isSticky' => $type === 1 ? 1 : 0,
                    'isAnnouncement' => $type === 2 ? 1 : 0,
                    'time' => TIME_NOW,
                    'userID' => WCF::getUser()->userID ?: null,
                    'username' => WCF::getUser()->userID ? WCF::getUser()->username : $data['username'],
                ];
                $parameters['subscribeThread'] = !empty($data['subscribeThread']);
                $parameters['htmlInputProcessor'] = $parameters['message_htmlInputProcessor'] ?? null;
                $parameters['attachmentHandler'] = $parameters['message_attachmentHandler'] ?? null;
                $parameters['pollData'] = $parameters['message_pollData'] ?? null;
                $parameters['labelIDs'] = \array_filter($parameters['labelIDs'] ?? []);
                unset(
                    $parameters['message_htmlInputProcessor'],
                    $parameters['message_attachmentHandler'],
                    $parameters['message_pollData']
                );

                return $parameters;
            }
        ));
    }

    #[\Override]
    public function validate()
    {
        parent::validate();

        $messageField = $this->form->getNodeById('message');
        \assert($messageField instanceof WysiwygFormField);
        $topicField = $this->form->getNodeById('topic');
        \assert($topicField instanceof TextFormField);

        $event = new MessageSpamChecking(
            $messageField->getHtmlInputProcessor(),
            WCF::getUser()->userID ? WCF::getUser() : null,
            UserUtil::getIpAddress(),
            (string)$topicField->getValue(),
        );
        EventHandler::getInstance()->fire($event);
        if ($event->defaultPrevented()) {
            throw new PermissionDeniedException();
        }

        if ($this->board === null) {
            $board = BoardCache::getInstance()->getBoard(\intval($this->form->getNodeById('boardID')->getValue()));
            if ($board === null || !$board->canStartThread()) {
                throw new PermissionDeniedException();
            }
        }
    }

    #[\Override]
    public function saved()
    {
        parent::saved();

        $thread = $this->objectAction->getReturnValues()['returnValues'];
        \assert($thread instanceof Thread);

        FloodControl::getInstance()->registerContent('com.tinsever.forum.thread');
        FloodControl::getInstance()->registerContent('com.tinsever.forum.post');

        if ($thread->isDisabled) {
            HeaderUtil::delayedRedirect(
                $thread->getBoard()->getLink(),
                WCF::getLanguage()->getDynamicVariable('forum.thread.moderation.redirect'),
                30
            );
        } else {
            HeaderUtil::redirect($thread->getLink());
        }

        exit;
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        if ($this->board !== null) {
            BoardBreadcrumbs::addBoard($this->board);
        } else {
            PageLocationManager::getInstance()->addParentLocation('com.tinsever.forum.BoardList');
        }
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'board' => $this->board,
            'action' => $this->board !== null
                ? LinkHandler::getInstance()->getControllerLink(static::class, ['id' => $this->board->boardID])
                : LinkHandler::getInstance()->getControllerLink(static::class),
        ]);
    }

    #[\Override]
    protected function setFormAction()
    {
        $parameters = [];
        if ($this->board !== null) {
            $parameters['id'] = $this->board->boardID;
        }

        $this->form->action(LinkHandler::getInstance()->getControllerLink(static::class, $parameters));
    }
}
