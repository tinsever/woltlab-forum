<?php

namespace forum\acp\form;

use forum\data\board\Board;
use forum\data\board\BoardAction;
use forum\data\board\BoardCache;
use forum\data\board\BoardEditor;
use forum\data\board\BoardNodeTree;
use forum\system\board\BoardPermissionHandler;
use wcf\data\IStorableObject;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\package\PackageCache;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\acl\ACLHandler;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\container\TabMenuFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\acl\AclFormField;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\dependency\ValueFormFieldDependency;
use wcf\system\form\builder\field\IconFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\MultilineTextFormField;
use wcf\system\form\builder\field\RadioButtonFormField;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\TitleFormField;
use wcf\system\form\builder\field\UrlFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\IFormDocument;
use wcf\system\language\I18nHandler;
use wcf\system\style\StyleHandler;
use wcf\system\WCF;

/**
 * Shows the form to create a board.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractFormBuilderForm<Board>
 */
class BoardAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'forum.acp.menu.link.board.add';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.forum.canManageBoard'];

    /**
     * @inheritDoc
     */
    public $objectActionClass = BoardAction::class;

    /**
     * @inheritDoc
     */
    public $objectEditLinkController = BoardEditForm::class;

    /**
     * @inheritDoc
     */
    public $additionalFields = [
        'title' => '',
        'description' => '',
    ];

    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        $isBoard = static fn(string $id) => ValueFormFieldDependency::create($id . 'BoardType')
            ->fieldId('boardType')
            ->values([Board::TYPE_BOARD]);

        $excludedIDs = [];
        if ($this->formObject instanceof Board) {
            $excludedIDs = BoardCache::getInstance()->getAllChildIDs($this->formObject->boardID);
        }

        $styles = [0 => 'forum.acp.board.styleID.default'];
        foreach (StyleHandler::getInstance()->getStyles() as $style) {
            $styles[$style->styleID] = $style->styleName;
        }

        $sortFields = [];
        foreach (Board::SORT_FIELDS as $field) {
            $sortFields[$field] = 'forum.acp.board.sortField.' . $field;
        }

        $this->form->appendChild(
            TabMenuFormContainer::create('tabMenu')
                ->appendChildren([
                    TabFormContainer::create('generalTab')
                        ->label('wcf.global.form.data')
                        ->appendChildren([
                            FormContainer::create('general')
                                ->appendChildren([
                                    RadioButtonFormField::create('boardType')
                                        ->label('forum.acp.board.boardType')
                                        ->options([
                                            Board::TYPE_BOARD => 'forum.acp.board.boardType.board',
                                            Board::TYPE_CATEGORY => 'forum.acp.board.boardType.category',
                                            Board::TYPE_LINK => 'forum.acp.board.boardType.link',
                                        ])
                                        ->value(Board::TYPE_BOARD)
                                        ->required(),
                                    SelectFormField::create('parentID')
                                        ->label('forum.acp.board.parentID')
                                        ->options(
                                            (new BoardNodeTree(0, null, -1, $excludedIDs))->getNestedOptions(static fn() => true),
                                            true,
                                            false
                                        ),
                                    TitleFormField::create()
                                        ->label('wcf.global.title')
                                        ->maximumLength(255)
                                        ->i18n()
                                        ->languageItemPattern('forum.board.board\d+')
                                        ->required(),
                                    MultilineTextFormField::create('description')
                                        ->label('wcf.global.description')
                                        ->rows(5)
                                        ->i18n()
                                        ->languageItemPattern('forum.board.board\d+.description'),
                                    BooleanFormField::create('descriptionUseHtml')
                                        ->label('forum.acp.board.descriptionUseHtml'),
                                    UrlFormField::create('externalURL')
                                        ->label('forum.acp.board.externalURL')
                                        ->required()
                                        ->addDependency(
                                            ValueFormFieldDependency::create('boardTypeLink')
                                                ->fieldId('boardType')
                                                ->values([Board::TYPE_LINK])
                                        ),
                                    IconFormField::create('iconName')
                                        ->label('forum.acp.board.iconName')
                                        ->description('forum.acp.board.iconName.description'),
                                    IntegerFormField::create('position')
                                        ->label('forum.acp.board.position')
                                        ->minimum(0)
                                        ->value(0),
                                ]),
                            FormContainer::create('settings')
                                ->label('forum.acp.board.settings')
                                ->appendChildren([
                                    BooleanFormField::create('isClosed')
                                        ->label('forum.acp.board.isClosed')
                                        ->description('forum.acp.board.isClosed.description')
                                        ->addDependency($isBoard('isClosed')),
                                    BooleanFormField::create('isInvisible')
                                        ->label('forum.acp.board.isInvisible')
                                        ->description('forum.acp.board.isInvisible.description'),
                                    BooleanFormField::create('isPrivate')
                                        ->label('forum.acp.board.isPrivate')
                                        ->description('forum.acp.board.isPrivate.description')
                                        ->addDependency($isBoard('isPrivate')),
                                    BooleanFormField::create('countUserPosts')
                                        ->label('forum.acp.board.countUserPosts')
                                        ->value(true)
                                        ->addDependency($isBoard('countUserPosts')),
                                    BooleanFormField::create('searchable')
                                        ->label('forum.acp.board.searchable')
                                        ->value(true)
                                        ->addDependency($isBoard('searchable')),
                                    BooleanFormField::create('ignorable')
                                        ->label('forum.acp.board.ignorable')
                                        ->value(true)
                                        ->addDependency($isBoard('ignorable')),
                                    BooleanFormField::create('showSubBoards')
                                        ->label('forum.acp.board.showSubBoards')
                                        ->value(true),
                                    BooleanFormField::create('enableMarkingAsDone')
                                        ->label('forum.acp.board.enableMarkingAsDone')
                                        ->description('forum.acp.board.enableMarkingAsDone.description')
                                        ->addDependency($isBoard('enableMarkingAsDone')),
                                    BooleanFormField::create('enableBestAnswer')
                                        ->label('forum.acp.board.enableBestAnswer')
                                        ->description('forum.acp.board.enableBestAnswer.description')
                                        ->addDependency($isBoard('enableBestAnswer')),
                                    SelectFormField::create('styleID')
                                        ->label('forum.acp.board.styleID')
                                        ->options($styles)
                                        ->value(0),
                                    TextFormField::create('metaDescription')
                                        ->label('forum.acp.board.metaDescription')
                                        ->maximumLength(255),
                                ]),
                        ]),
                    TabFormContainer::create('displayTab')
                        ->label('forum.acp.board.display')
                        ->addDependency($isBoard('displayTab'))
                        ->appendChild(
                            FormContainer::create('display')
                                ->appendChildren([
                                    SelectFormField::create('sortField')
                                        ->label('forum.acp.board.sortField')
                                        ->options(['' => 'forum.acp.board.sortField.default'] + $sortFields)
                                        ->value(''),
                                    SelectFormField::create('sortOrder')
                                        ->label('forum.acp.board.sortOrder')
                                        ->options([
                                            '' => 'forum.acp.board.sortOrder.default',
                                            'ASC' => 'wcf.global.sortOrder.ascending',
                                            'DESC' => 'wcf.global.sortOrder.descending',
                                        ])
                                        ->value(''),
                                    SelectFormField::create('postSortOrder')
                                        ->label('forum.acp.board.postSortOrder')
                                        ->options([
                                            '' => 'forum.acp.board.sortOrder.default',
                                            'ASC' => 'wcf.global.sortOrder.ascending',
                                            'DESC' => 'wcf.global.sortOrder.descending',
                                        ])
                                        ->value(''),
                                    IntegerFormField::create('threadsPerPage')
                                        ->label('forum.acp.board.threadsPerPage')
                                        ->description('forum.acp.board.perPage.description')
                                        ->minimum(0)
                                        ->maximum(500)
                                        ->value(0),
                                    IntegerFormField::create('postsPerPage')
                                        ->label('forum.acp.board.postsPerPage')
                                        ->description('forum.acp.board.perPage.description')
                                        ->minimum(0)
                                        ->maximum(500)
                                        ->value(0),
                                    IntegerFormField::create('daysPrune')
                                        ->label('forum.acp.board.daysPrune')
                                        ->description('forum.acp.board.daysPrune.description')
                                        ->suffix('wcf.acp.option.suffix.days')
                                        ->minimum(0)
                                        ->value(0),
                                ])
                        ),
                    TabFormContainer::create('permissionsTab')
                        ->label('wcf.acl.permissions')
                        ->appendChild(
                            FormContainer::create('permissions')
                                ->appendChild(
                                    AclFormField::create('aclPermissions')
                                        ->label('wcf.acl.permissions')
                                        ->objectType('com.tinsever.forum.board')
                                )
                        ),
                ])
        );
    }

    #[\Override]
    protected function finalizeForm()
    {
        parent::finalizeForm();

        $this->form->getDataHandler()->addProcessor(new CustomFormDataProcessor(
            'boardData',
            static function (IFormDocument $document, array $parameters) {
                $parameters['data']['parentID'] = \intval($parameters['data']['parentID'] ?? 0) ?: null;
                $parameters['data']['styleID'] = \intval($parameters['data']['styleID'] ?? 0) ?: null;
                foreach (['externalURL', 'iconName', 'sortField', 'sortOrder', 'postSortOrder', 'metaDescription'] as $column) {
                    $parameters['data'][$column] = (string)($parameters['data'][$column] ?? '');
                }

                return $parameters;
            },
            static function (IFormDocument $document, array $data, IStorableObject $object) {
                $data['parentID'] = $data['parentID'] ?: null;
                $data['styleID'] = $data['styleID'] ?: 0;

                return $data;
            }
        ));
    }

    #[\Override]
    public function saved()
    {
        if ($this->formAction === 'create') {
            $board = $this->objectAction->getReturnValues()['returnValues'];
        } else {
            $board = new Board($this->formObject->boardID);
        }
        \assert($board instanceof Board);

        $updateData = [];
        $packageID = PackageCache::getInstance()->getPackageID('com.tinsever.forum');

        $titleField = $this->form->getFormField('title');
        $titleItem = 'forum.board.board' . $board->boardID;
        if ($titleField->hasPlainValue()) {
            I18nHandler::getInstance()->remove($titleItem);
        } else {
            $updateData['title'] = $titleItem;
            I18nHandler::getInstance()->save($titleField->getPrefixedId(), $titleItem, 'forum.board', $packageID);
        }

        $descriptionField = $this->form->getFormField('description');
        $descriptionItem = 'forum.board.board' . $board->boardID . '.description';
        if ($descriptionField->hasPlainValue()) {
            I18nHandler::getInstance()->remove($descriptionItem);
        } else {
            $updateData['description'] = $descriptionItem;
            I18nHandler::getInstance()->save($descriptionField->getPrefixedId(), $descriptionItem, 'forum.board', $packageID);
        }

        if ($updateData !== []) {
            (new BoardEditor($board))->update($updateData);
        }

        $formData = $this->form->getData();
        if (isset($formData['aclPermissions_aclObjectTypeID'])) {
            ACLHandler::getInstance()->save($board->boardID, $formData['aclPermissions_aclObjectTypeID']);
        }

        BoardEditor::resetCache();

        parent::saved();
    }
}
