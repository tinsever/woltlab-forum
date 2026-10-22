<?php

namespace forum\data\rss\feed;

use forum\system\rss\RssFeedImporter;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\TDatabaseObjectToggle;

/**
 * Executes RSS feed-related actions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<RssFeed, RssFeedEditor>
 */
class RssFeedAction extends AbstractDatabaseObjectAction
{
    use TDatabaseObjectToggle;

    /**
     * @inheritDoc
     */
    protected $className = RssFeedEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.forum.canManageRssFeed'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.forum.canManageRssFeed'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.forum.canManageRssFeed'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'delete', 'toggle', 'update', 'import'];

    public function validateImport(): void
    {
        $this->validateUpdate();
    }

    /**
     * Fetches the feeds and imports new entries, regardless of their cycle time.
     *
     * @return array<int, int> number of imported entries by feed id
     */
    public function import(): array
    {
        if ($this->objects === []) {
            $this->readObjects();
        }

        $importer = new RssFeedImporter();
        $result = [];
        foreach ($this->getObjects() as $editor) {
            $result[$editor->feedID] = $importer->import($editor->getDecoratedObject());
        }

        return $result;
    }
}
