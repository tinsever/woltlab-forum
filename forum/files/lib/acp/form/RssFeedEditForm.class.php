<?php

namespace forum\acp\form;

use forum\data\rss\feed\RssFeed;
use wcf\system\exception\IllegalLinkException;

/**
 * Shows the form to edit an RSS feed.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class RssFeedEditForm extends RssFeedAddForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'forum.acp.menu.link.rssFeed.list';

    /**
     * @inheritDoc
     */
    public $formAction = 'edit';

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        $this->formObject = new RssFeed(\intval($_REQUEST['id'] ?? 0));
        if (!$this->formObject->feedID) {
            throw new IllegalLinkException();
        }
    }
}
