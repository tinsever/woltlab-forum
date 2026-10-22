<?php

namespace forum\acp\form;

use forum\data\board\BoardNodeTree;
use forum\data\rss\feed\RssFeed;
use forum\data\rss\feed\RssFeedAction;
use forum\system\rss\RssFeedReader;
use wcf\data\IStorableObject;
use wcf\data\user\User;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\language\ContentLanguageFormField;
use wcf\system\form\builder\field\MultilineTextFormField;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\UrlFormField;
use wcf\system\form\builder\field\user\UserFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\IFormDocument;

/**
 * Shows the form to add an RSS feed.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractFormBuilderForm<RssFeed>
 */
class RssFeedAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'forum.acp.menu.link.rssFeed.add';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.forum.canManageRssFeed'];

    /**
     * @inheritDoc
     */
    public $objectActionClass = RssFeedAction::class;

    /**
     * @inheritDoc
     */
    public $objectEditLinkController = RssFeedEditForm::class;

    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        $this->form->appendChildren([
            FormContainer::create('general')
                ->appendChildren([
                    UrlFormField::create('url')
                        ->label('forum.acp.rssFeed.url')
                        ->required()
                        ->addValidator(new FormFieldValidator('feed', static function (UrlFormField $field) {
                            if ($field->getValidationErrors() !== [] || $field->getValue() === '') {
                                return;
                            }

                            try {
                                (new RssFeedReader())->read($field->getValue());
                            } catch (\Throwable $e) {
                                $field->addValidationError(new FormFieldValidationError(
                                    'invalid',
                                    'forum.acp.rssFeed.url.error.invalid',
                                    ['message' => $e->getMessage()]
                                ));
                            }
                        })),
                    TextFormField::create('title')
                        ->label('wcf.global.title')
                        ->description('forum.acp.rssFeed.title.description')
                        ->maximumLength(255),
                    SelectFormField::create('boardID')
                        ->label('forum.acp.rssFeed.boardID')
                        ->options((new BoardNodeTree(0, null, -1))->getNestedOptions(), true, false)
                        ->required(),
                    UserFormField::create('userID')
                        ->label('forum.acp.rssFeed.userID')
                        ->description('forum.acp.rssFeed.userID.description')
                        ->nullable(),
                    ContentLanguageFormField::create(),
                    IntegerFormField::create('cycleTime')
                        ->label('forum.acp.rssFeed.cycleTime')
                        ->suffix('wcf.acp.option.suffix.minutes')
                        ->minimum(10)
                        ->maximum(10080)
                        ->value(30)
                        ->required(),
                    BooleanFormField::create('isDisabled')
                        ->label('forum.acp.rssFeed.isDisabled'),
                ]),
            FormContainer::create('import')
                ->label('forum.acp.rssFeed.import.settings')
                ->appendChildren([
                    IntegerFormField::create('maxResults')
                        ->label('forum.acp.rssFeed.maxResults')
                        ->description('forum.acp.rssFeed.maxResults.description')
                        ->minimum(0)
                        ->maximum(100)
                        ->value(0),
                    MultilineTextFormField::create('searchKeywords')
                        ->label('forum.acp.rssFeed.searchKeywords')
                        ->description('forum.acp.rssFeed.searchKeywords.description')
                        ->rows(4),
                    TextFormField::create('threadTags')
                        ->label('forum.acp.rssFeed.threadTags')
                        ->description('forum.acp.rssFeed.threadTags.description')
                        ->available(\MODULE_TAGGING),
                    BooleanFormField::create('useCategoriesAsTags')
                        ->label('forum.acp.rssFeed.useCategoriesAsTags')
                        ->available(\MODULE_TAGGING),
                    BooleanFormField::create('closeThread')
                        ->label('forum.acp.rssFeed.closeThread'),
                    BooleanFormField::create('disableThread')
                        ->label('forum.acp.rssFeed.disableThread'),
                ]),
        ]);
    }

    #[\Override]
    protected function finalizeForm()
    {
        parent::finalizeForm();

        $this->form->getDataHandler()->addProcessor(
            new CustomFormDataProcessor(
                'rssFeed',
                static function (IFormDocument $document, array $parameters) {
                    $data = &$parameters['data'];
                    $data['cycleTime'] = \intval($data['cycleTime'] ?? 30) * 60;

                    $data['username'] = '';
                    if (!empty($data['userID'])) {
                        $data['username'] = (new User($data['userID']))->username;
                    } else {
                        $data['userID'] = null;
                    }

                    if (empty($data['languageID'])) {
                        $data['languageID'] = null;
                    }

                    return $parameters;
                },
                static function (IFormDocument $document, array $data, IStorableObject $object) {
                    \assert($object instanceof RssFeed);
                    $data['cycleTime'] = \intdiv($object->cycleTime, 60);

                    return $data;
                }
            )
        );
    }
}
