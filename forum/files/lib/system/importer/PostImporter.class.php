<?php

namespace forum\system\importer;

use forum\data\post\Post;
use forum\data\post\PostEditor;
use wcf\system\importer\AbstractImporter;
use wcf\system\importer\ImportHandler;

/**
 * Imports posts. Messages without `enableHtml` are treated as BBCode and are
 * converted by the post rebuild data worker.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostImporter extends AbstractImporter
{
    use TForumImporter;

    /**
     * @inheritDoc
     */
    protected $className = Post::class;

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $data['threadID'] = $this->getNewID('com.woltlab.wbb.thread', $data['threadID'] ?? null);
        if (!$data['threadID']) {
            return 0;
        }

        $data['userID'] = $this->getNewID('com.woltlab.wcf.user', $data['userID'] ?? null);
        $data['editorID'] = $this->getNewID('com.woltlab.wcf.user', $data['editorID'] ?? null);
        $data['username'] ??= '';
        $data['subject'] = \mb_substr((string)($data['subject'] ?? ''), 0, 255);
        $data['message'] = (string)($data['message'] ?? '');
        $data['time'] ??= TIME_NOW;
        $data['enableHtml'] = empty($data['enableHtml']) ? 0 : 1;
        $data['ipAddress'] = \mb_substr((string)($data['ipAddress'] ?? ''), 0, 39);

        $data = $this->filterColumns('forum1_post', $data);
        unset($data['postID'], $data['pollID'], $data['attachments'], $data['hasEmbeddedObjects']);
        if ($this->canReuseID('forum1_post', 'postID', $oldID)) {
            $data['postID'] = (int)$oldID;
        }

        $post = PostEditor::create($data);
        ImportHandler::getInstance()->saveNewID('com.woltlab.wbb.post', $oldID, $post->postID);

        return $post->postID;
    }
}
