<?php

namespace forum\form;

use forum\system\board\BoardBreadcrumbs;
use forum\data\post\Post;
use forum\data\post\PostAction;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use forum\system\label\object\ThreadLabelObjectHandler;
use wcf\event\message\MessageSpamChecking;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\event\EventHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\label\LabelFormField;
use wcf\system\form\builder\field\tag\TagFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\wysiwyg\WysiwygFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\UserUtil;

/**
 * Shows the form to edit a post. Editing the first post also allows
 * changing the topic, the labels and the tags of the thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractFormBuilderForm<Post>
 */
class PostEditForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $objectActionClass = PostAction::class;

    /**
     * @inheritDoc
     */
    public $formAction = 'edit';

    public Thread $thread;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        $post = new Post(\intval($_REQUEST['id'] ?? 0));
        if (!$post->postID) {
            throw new IllegalLinkException();
        }
        if (!$post->canEdit()) {
            throw new PermissionDeniedException();
        }

        $this->formObject = $post;
        $this->thread = $post->getThread();
    }

    protected function isFirstPost(): bool
    {
        return $this->thread->firstPostID == $this->formObject->postID;
    }

    protected function canEditThread(): bool
    {
        return $this->isFirstPost() && $this->thread->canEdit();
    }

    protected function canSetTags(): bool
    {
        return $this->canEditThread()
            && \MODULE_TAGGING
            && WCF::getSession()->getPermission('user.tag.canViewTag')
            && $this->thread->getPermission('canSetTags');
    }

    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        $information = FormContainer::create('information')
            ->appendChildren([
                TextFormField::create('topic')
                    ->label('forum.thread.topic')
                    ->maximumLength(255)
                    ->censorship()
                    ->available($this->canEditThread())
                    ->value($this->thread->topic)
                    ->required(),
                TextFormField::create('subject')
                    ->label('wcf.global.subject')
                    ->maximumLength(255)
                    ->censorship()
                    ->available(!$this->isFirstPost()),
            ]);

        if ($this->canEditThread()) {
            $labelGroups = ThreadLabelObjectHandler::getLabelGroupsForBoard($this->thread->boardID);
            if ($labelGroups !== []) {
                $information->appendChildren(LabelFormField::createFields('com.tinsever.forum.thread', $labelGroups));
            }
        }

        $information->appendChild(
            TagFormField::create()
                ->objectType('com.tinsever.forum.thread')
                ->available($this->canSetTags())
        );

        $message = WysiwygFormContainer::create('message')
            ->label('forum.post.message')
            ->messageObjectType(Post::OBJECT_TYPE)
            ->supportMentions()
            ->supportQuotes()
            ->supportSmilies(WCF::getSession()->getPermission('user.message.canUseSmilies') ? true : false)
            ->required()
            ->autosaveId('com.tinsever.forum.postEdit-' . $this->formObject->postID)
            ->lastEditTime($this->formObject->lastEditTime);
        if ($this->thread->getPermission('canUploadAttachment')) {
            $message->attachmentData(Post::OBJECT_TYPE, $this->thread->boardID, $this->formObject->postID);
        }
        if (\MODULE_POLL && $this->thread->getPermission('canStartPoll')) {
            $message->pollObjectType(Post::OBJECT_TYPE);
        }
        $maxLength = \intval(WCF::getSession()->getPermission('user.forum.maxPostLength'));
        if ($maxLength) {
            $message->maximumLength($maxLength);
        }

        $this->form->appendChildren([
            $information,
            $message,
            FormContainer::create('editReasonContainer')
                ->appendChild(
                    TextFormField::create('editReason')
                        ->label('forum.post.editReason')
                        ->maximumLength(255)
                ),
        ]);
    }

    #[\Override]
    protected function setFormObjectData()
    {
        parent::setFormObjectData();

        if (empty($_POST) && $this->canEditThread()) {
            // labels and tags belong to the thread, not to the post
            foreach ($this->form->getNodeById('information')->children() as $node) {
                if ($node instanceof LabelFormField) {
                    $node->value(0);
                    $node->updatedObject([], $this->thread);
                } elseif ($node instanceof TagFormField) {
                    $node->updatedObject(['languageID' => $this->thread->languageID], $this->thread);
                }
            }
        }

        // the edit reason is not prefilled with the previous reason
        $this->form->getNodeById('editReason')->value('');
    }

    #[\Override]
    protected function finalizeForm()
    {
        parent::finalizeForm();

        $this->form->getDataHandler()->addProcessor(new CustomFormDataProcessor(
            'forumPostEdit',
            function (IFormDocument $document, array $parameters) {
                $data = $parameters['data'];
                $post = $this->formObject;

                $parameters['threadData'] = [];
                if ($this->canEditThread()) {
                    $parameters['threadData'] = [
                        'data' => ['topic' => $data['topic']],
                        'labelIDs' => \array_filter($parameters['labelIDs'] ?? []),
                    ];
                    if ($this->canSetTags()) {
                        $parameters['threadData']['tags'] = $parameters['tags'] ?? [];
                    }
                }

                $parameters['data'] = [
                    'subject' => $this->isFirstPost() ? '' : ($data['subject'] ?? ''),
                    'editReason' => $data['editReason'] ?? '',
                ];

                $isGracePeriod = \FORUM_POST_EDIT_GRACE_PERIOD
                    && $post->time > TIME_NOW - \FORUM_POST_EDIT_GRACE_PERIOD
                    && $post->isOwner();
                if (!$isGracePeriod) {
                    $parameters['data']['editorID'] = WCF::getUser()->userID ?: null;
                    $parameters['data']['editor'] = WCF::getUser()->username;
                    $parameters['data']['lastEditTime'] = TIME_NOW;
                    $parameters['data']['editCount'] = $post->editCount + 1;
                }

                $parameters['isEdit'] = true;
                $parameters['htmlInputProcessor'] = $parameters['message_htmlInputProcessor'] ?? null;
                $parameters['attachmentHandler'] = $parameters['message_attachmentHandler'] ?? null;
                if (isset($parameters['message_pollData'])) {
                    $parameters['pollData'] = $parameters['message_pollData'];
                }
                unset(
                    $parameters['message_htmlInputProcessor'],
                    $parameters['message_attachmentHandler'],
                    $parameters['message_pollData'],
                    $parameters['labelIDs'],
                    $parameters['tags']
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

        $event = new MessageSpamChecking(
            $messageField->getHtmlInputProcessor(),
            WCF::getUser()->userID ? WCF::getUser() : null,
            UserUtil::getIpAddress(),
        );
        EventHandler::getInstance()->fire($event);
        if ($event->defaultPrevented()) {
            throw new PermissionDeniedException();
        }
    }

    #[\Override]
    public function saved()
    {
        $threadData = $this->objectAction->getParameters()['threadData'] ?? [];
        if ($threadData !== []) {
            (new ThreadAction([$this->thread], 'update', $threadData))->executeAction();
        }

        // keep the thread counters in sync with the attachments
        (new \forum\data\thread\ThreadEditor(new Thread($this->thread->threadID)))->rebuild();

        parent::saved();

        HeaderUtil::redirect((new Post($this->formObject->postID))->getLink());

        exit;
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        BoardBreadcrumbs::addThread($this->thread);
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'thread' => $this->thread,
            'post' => $this->formObject,
        ]);
    }
}
