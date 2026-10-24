<?php

namespace forum\system\importer;

use forum\data\thread\Thread;
use forum\data\thread\ThreadEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractImporter;
use wcf\system\importer\ImportHandler;
use wcf\system\label\LabelHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\tagging\TagEngine;
use wcf\system\WCF;

/**
 * Imports threads. The first and last post as well as the counters are restored
 * by the rebuild data workers after the import.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadImporter extends AbstractImporter
{
    use TForumImporter;

    /**
     * @inheritDoc
     */
    protected $className = Thread::class;

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $data['boardID'] = $this->getNewID('com.woltlab.wbb.board', $data['boardID'] ?? null);
        if (!$data['boardID']) {
            return 0;
        }

        $data['userID'] = $this->getNewID('com.woltlab.wcf.user', $data['userID'] ?? null);
        $data['lastPosterID'] = $this->getNewID('com.woltlab.wcf.user', $data['lastPosterID'] ?? null);
        $data['username'] ??= '';
        $data['topic'] = \mb_substr((string)($data['topic'] ?? ''), 0, 255);
        $data['time'] ??= TIME_NOW;
        $data['lastPostTime'] = $data['lastPostTime'] ?? $data['time'];

        if (!empty($data['movedThreadID'])) {
            $data['movedThreadID'] = $this->getNewID('com.woltlab.wbb.thread', $data['movedThreadID']);
            if (!$data['movedThreadID']) {
                return 0;
            }
        } else {
            $data['movedThreadID'] = null;
        }

        if (!empty($additionalData['languageCode'])) {
            $data['languageID'] = LanguageFactory::getInstance()->getLanguageByCode($additionalData['languageCode'])?->languageID;
        } elseif (!empty($data['languageID'])) {
            $data['languageID'] = LanguageFactory::getInstance()->getLanguage((int)$data['languageID'])?->languageID;
        } else {
            $data['languageID'] = null;
        }

        $data = $this->filterColumns('forum1_thread', $data);
        unset($data['threadID'], $data['firstPostID'], $data['lastPostID'], $data['bestAnswerPostID']);
        if ($this->canReuseID('forum1_thread', 'threadID', $oldID)) {
            $data['threadID'] = (int)$oldID;
        }

        $thread = ThreadEditor::create($data);
        ImportHandler::getInstance()->saveNewID('com.woltlab.wbb.thread', $oldID, $thread->threadID);

        if (!empty($additionalData['tags']) && \MODULE_TAGGING) {
            TagEngine::getInstance()->addObjectTags(
                'com.tinsever.forum.thread',
                $thread->threadID,
                $additionalData['tags'],
                $thread->languageID ?: LanguageFactory::getInstance()->getDefaultLanguageID()
            );
        }

        if (!empty($additionalData['labels'])) {
            $this->importLabels($thread, $additionalData['labels']);
        }

        if ($thread->isAnnouncement) {
            $this->importAnnouncementBoards($thread, $additionalData['assignedBoards'] ?? []);
        }

        return $thread->threadID;
    }

    /**
     * @param array<int|string> $oldLabelIDs
     */
    private function importLabels(Thread $thread, array $oldLabelIDs): void
    {
        $labelIDs = [];
        foreach ($oldLabelIDs as $oldLabelID) {
            $labelID = $this->getNewID('com.woltlab.wcf.label', $oldLabelID);
            if ($labelID) {
                $labelIDs[] = $labelID;
            }
        }

        if ($labelIDs === []) {
            return;
        }

        $objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.label.object',
            'com.tinsever.forum.thread'
        );
        LabelHandler::getInstance()->setLabels(\array_unique($labelIDs), $objectTypeID, $thread->threadID, false);

        (new ThreadEditor($thread))->update(['hasLabels' => 1]);
    }

    /**
     * @param array<int|string> $oldBoardIDs
     */
    private function importAnnouncementBoards(Thread $thread, array $oldBoardIDs): void
    {
        $boardIDs = [$thread->boardID];
        foreach ($oldBoardIDs as $oldBoardID) {
            $boardID = $this->getNewID('com.woltlab.wbb.board', $oldBoardID);
            if ($boardID) {
                $boardIDs[] = $boardID;
            }
        }

        $sql = "INSERT IGNORE INTO  forum1_thread_announcement
                                    (boardID, threadID)
                VALUES              (?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        foreach (\array_unique($boardIDs) as $boardID) {
            $statement->execute([$boardID, $thread->threadID]);
        }
    }
}
