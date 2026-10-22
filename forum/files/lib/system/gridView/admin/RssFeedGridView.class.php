<?php

namespace forum\system\gridView\admin;

use forum\acp\form\RssFeedEditForm;
use forum\data\board\BoardCache;
use forum\data\rss\feed\RssFeed;
use forum\data\rss\feed\RssFeedList;
use forum\system\interaction\admin\RssFeedInteractions;
use wcf\data\DatabaseObject;
use wcf\system\gridView\AbstractGridView;
use wcf\system\gridView\GridViewColumn;
use wcf\system\gridView\GridViewRowLink;
use wcf\system\gridView\renderer\AbstractColumnRenderer;
use wcf\system\gridView\renderer\ObjectIdColumnRenderer;
use wcf\system\gridView\renderer\TimeColumnRenderer;
use wcf\system\interaction\Divider;
use wcf\system\interaction\EditInteraction;
use wcf\system\interaction\ToggleInteraction;
use wcf\system\view\filter\TextFilter;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Grid view for the list of RSS feeds.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractGridView<RssFeed, RssFeedList>
 */
final class RssFeedGridView extends AbstractGridView
{
    public function __construct()
    {
        $this->addColumns([
            GridViewColumn::for('feedID')
                ->label('wcf.global.objectID')
                ->renderer(new ObjectIdColumnRenderer())
                ->sortable(),
            GridViewColumn::for('title')
                ->label('wcf.global.title')
                ->titleColumn()
                ->filter(TextFilter::class)
                ->renderer(
                    new class extends AbstractColumnRenderer {
                        #[\Override]
                        public function render(mixed $value, DatabaseObject $row): string
                        {
                            \assert($row instanceof RssFeed);

                            $html = StringUtil::encodeHTML($row->getTitle());
                            if ($row->lastError) {
                                $html .= \sprintf(
                                    ' <span class="badge red jsTooltip" title="%s">%s</span>',
                                    StringUtil::encodeHTML($row->lastError),
                                    WCF::getLanguage()->get('wcf.global.error.title')
                                );
                            }

                            return $html;
                        }
                    }
                )
                ->sortable(),
            GridViewColumn::for('boardID')
                ->label('forum.acp.rssFeed.boardID')
                ->renderer(
                    new class extends AbstractColumnRenderer {
                        #[\Override]
                        public function render(mixed $value, DatabaseObject $row): string
                        {
                            $board = BoardCache::getInstance()->getBoard((int)$value);

                            return $board === null ? '' : StringUtil::encodeHTML($board->getTitle());
                        }
                    }
                ),
            GridViewColumn::for('cycleTime')
                ->label('forum.acp.rssFeed.cycleTime')
                ->renderer(
                    new class extends AbstractColumnRenderer {
                        #[\Override]
                        public function render(mixed $value, DatabaseObject $row): string
                        {
                            return WCF::getLanguage()->getDynamicVariable(
                                'forum.acp.rssFeed.cycleTime.minutes',
                                ['minutes' => \intdiv((int)$value, 60)]
                            );
                        }
                    }
                )
                ->sortable(),
            GridViewColumn::for('lastRun')
                ->label('forum.acp.rssFeed.lastRun')
                ->renderer(new TimeColumnRenderer())
                ->sortable(),
        ]);

        $provider = new RssFeedInteractions();
        $provider->addInteractions([
            new Divider(),
            new EditInteraction(RssFeedEditForm::class),
        ]);
        $this->setInteractionProvider($provider);

        $this->addQuickInteraction(
            new ToggleInteraction(
                'enable',
                'forum/rss/feeds/%s/enable',
                'forum/rss/feeds/%s/disable'
            )
        );

        $this->addRowLink(new GridViewRowLink(RssFeedEditForm::class));
        $this->setDefaultSortField('title');
    }

    #[\Override]
    public function isAccessible(): bool
    {
        return WCF::getSession()->getPermission('admin.forum.canManageRssFeed');
    }

    #[\Override]
    protected function createObjectList(): RssFeedList
    {
        return new RssFeedList();
    }
}
