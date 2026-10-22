<?php

namespace forum\data\thread;

use forum\system\label\object\ThreadLabelObjectHandler;
use wcf\data\DatabaseObjectCollection;
use wcf\data\label\Label;
use wcf\data\TCollectionUserProfiles;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Batch-loads related data for threads that were read together.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends DatabaseObjectCollection<Thread>
 */
class ThreadCollection extends DatabaseObjectCollection
{
    use TCollectionUserProfiles;

    /**
     * @var array<int, Label[]>
     */
    private array $labels;

    /**
     * @var array<int, string>
     */
    private array $userStatus;

    /**
     * @return Label[]
     */
    public function getLabels(Thread $thread): array
    {
        if (!isset($this->labels)) {
            $threadIDs = [];
            foreach ($this->getObjects() as $object) {
                if ($object->hasLabels) {
                    $threadIDs[] = $object->threadID;
                }
            }

            $this->labels = $threadIDs === []
                ? []
                : ThreadLabelObjectHandler::getInstance()->getAssignedLabels($threadIDs);
        }

        return $this->labels[$thread->threadID] ?? [];
    }

    public function getUserStatus(Thread $thread): ?string
    {
        if (!isset($this->userStatus)) {
            $this->userStatus = [];

            if (WCF::getUser()->userID) {
                $conditions = new PreparedStatementConditionBuilder();
                $conditions->add('userID = ?', [WCF::getUser()->userID]);
                $conditions->add('threadID IN (?)', [$this->getObjectIDs()]);

                $sql = "SELECT  threadID, status
                        FROM    forum1_thread_user_status
                        " . $conditions;
                $statement = WCF::getDB()->prepare($sql);
                $statement->execute($conditions->getParameters());
                $this->userStatus = $statement->fetchMap('threadID', 'status');
            }
        }

        return $this->userStatus[$thread->threadID] ?? null;
    }
}
