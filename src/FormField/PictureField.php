<?php

namespace Cita\FormField;

use SilverStripe\Dev\Debug;
use Cita\Model\Picture;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\FormAction;

class PictureField extends CompositeField
{
    protected $fields = [];
    protected $fieldList = [];
    protected $picture;
    protected $pictureTitle;
    protected $desktopImage;
    protected $tabletImage;
    protected $phoneImage;
    protected $picDesktopWidth;
    protected $picDesktopHeight;
    protected $picTabletWidth;
    protected $picTabletHeight;
    protected $picPhoneWidth;
    protected $picPhoneHeight;
    protected $manyMode = false;
    protected $performDelete = false;
    protected $sortField = null;
    protected $additionalDBFields = [];
    protected $additionalDBMappedValues = [];

    /** @phpstan-ignore-next-line */
    public function __construct($name, $title = null, $owner = null)
    {
        $this->picture = $owner->{$name}();

        $this->manyMode = $this->picture instanceof ManyManyList || $this->picture instanceof HasManyList;

        if ($this->manyMode) {
            $this->initManyMode($name);
        } else {
            $this->initSingleMode($name, $this->picture);
        }

        $this->fieldList['Title'] = $this->fields['Title'];
        $this->fieldList['UploaderGroup'] =$this->fields['UploaderGroup'];
        $this->fieldList['Caption'] =$this->fields['Caption'];

        if (!empty($this->fields['PictureControl'])) {
            $this->fieldList['PictureControl'] =$this->fields['PictureControl'];
        }

        if (!empty($this->fields['GridField'])) {
            $this->fieldList['GridField'] =$this->fields['GridField'];
        }

        parent::__construct($this->fieldList);

        $this->setName($name);
        $this->setTitle($title ?? self::name_to_label($name));

        $this->addExtraClass('picture-field');

        if ($this->manyMode) {
            $this->addExtraClass('multi-mode');
        }
    }

    public function setAdditionalDBFields($fields)
    {
        if (!empty($fields)) {
            $this->additionalDBFields = $fields;
        }

        return $this;
    }

    public function setDimensions($dimensions)
    {
        foreach ($dimensions as $device => $dimension) {
            $dimension         = (object) $dimension;
            $deviceFieldWidth  = "pic{$device}Width";
            $deviceFieldHeight = "pic{$device}Height";

            $this->{$deviceFieldWidth}  = $dimension->Width;
            $this->{$deviceFieldHeight} = $dimension->Height;

            if (!empty($this->fields[$device])) {
                $this->fields[$device]->setDescription("Width: {$dimension->Width}px, Height: {$dimension->Height}px");
            }
        }

        return $this;
    }

    public function setFolderName($name)
    {
        foreach ($this->fields as $key => $field) {
            if ($field->hasMethod('setFolderName')) {
                $field->setFolderName($name);
            }
        }

        return $this;
    }

    public function hasData()
    {
        return true;
    }

    public function setSubmittedValue($value, $data = null)
    {
        if ($data && isset($data['pictureTitle_' . $this->name]) && isset($data['pictureCaption_' . $this->name])) {
            $this->pictureTitle   = $data['pictureTitle_' . $this->name];
            $this->pictureCaption = $data['pictureCaption_' . $this->name];

            foreach ($this->additionalDBFields as $fieldName) {
                if (isset($data[$fieldName])) {
                    $this->additionalDBMappedValues[$fieldName] = $data[$fieldName];
                }
            }

        } else {
            $this->performDelete = true;
        }

        return $this;
    }

    public function saveInto($data)
    {
        if ($this->name) {
            if ($this->manyMode) {
                $this->saveMany($data);
            } else {
                $this->saveSingle($data);
            }
        }
    }

    private function initManyMode($name, $title = null)
    {
        $this->initSingleMode($name);

        $this->fields['GridField'] = GridField::create(
            'pictureGridfield_' . $name,
            'Uploaded pictures',
            $this->picture
        )->setConfig($this->makeConfig())
            ->addExtraClass('picture-field-gridfield')
        ;
    }

    public function setSortField($fieldName)
    {
        if ($this->sortField) {
            $this->sortField->setSortField($fieldName);
        }

        return $this;
    }

    private function makeConfig()
    {
        $config = GridFieldConfig::create();

        $config->addComponent($sort = new GridFieldSortableHeader());
        $config->addComponent($columns = new GridFieldDataColumns());
        $config->addComponent(new GridFieldEditButton());
        $config->addComponent(new GridFieldDeleteAction());
        $config->addComponent(new GridField_ActionMenu());
        $config->addComponent($pagination = new GridFieldPaginator(null));
        $config->addComponent(new GridFieldDetailForm());
        $config->addComponent($this->sortField = GridFieldOrderableRows::create('Sort'));

        $columns->setDisplayFields([
            'Desktop.CMSThumbnail' => 'Desktop',
            'Tablet.CMSThumbnail'  => 'Tablet',
            'Phone.CMSThumbnail'   => 'Mobile',
            'Text'                 => [
                'title'    => 'Title & caption',
                'callback' => function ($pic) {
                    return '<dl>
                        <dt>Title</dt>
                        <dd>' . ($pic->Title ?? '<em>not set</em>') . '</dd>
                        <dt>Caption</dt>
                        <dd>' . ($pic->Caption ?? '<em>not set</em>') . '</dd>
                    </dl>';
                },
            ],
        ])->setFieldCasting([
            'Text' => 'HTMLFragment->RAW',
        ]);

        $sort->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);

        return $config;
    }

    private function initSingleMode($name, $picture = null)
    {
        $this->fields['Title'] = TextField::create('pictureTitle_' . $name, 'Title', $picture && $picture->exists() ? $picture->Title : null);

        $this->fields['Desktop'] = UploadField::create(
            'desktopImage_' . $name,
            'Desktop'
        )
            ->setAllowedMaxFileNumber(1)
            ->setAllowedExtensions(['png', 'gif', 'jpeg', 'jpg'])
            ->addExtraClass('first-uploader')
        ;

        $this->fields['Tablet'] = UploadField::create(
            'tabletImage_' . $name,
            'Tablet'
        )
            ->setAllowedMaxFileNumber(1)
            ->setAllowedExtensions(['png', 'gif', 'jpeg', 'jpg'])
        ;

        $this->fields['Phone'] = UploadField::create(
            'phoneImage_' . $name,
            'Mobile'
        )
            ->setAllowedMaxFileNumber(1)
            ->setAllowedExtensions(['png', 'gif', 'jpeg', 'jpg'])
            ->addExtraClass('last-uploader')
        ;

        $this->fields['Caption'] = TextField::create('pictureCaption_' . $name, 'Caption', $picture && $picture->exists() ? $picture->Caption : null);

        if ($picture && $picture->exists()) {
            if ($picture->Desktop()->exists()) {
                $this->fields['Desktop']->setValue($picture->Desktop());
            }

            if ($picture->Tablet()->exists()) {
                $this->fields['Tablet']->setValue($picture->Tablet());
            }

            if ($picture->Phone()->exists()) {
                $this->fields['Phone']->setValue($picture->Phone());
            }

            $this->fields['PictureControl'] = FormAction::create(null, 'Remove')->setUseButtonTag('true')->addExtraClass('btn-remove-cita-picture btn btn-danger');
            $this->fields['PictureControl']->setName('RemovePictureButton_' . $name);
        }

        $this->fields['UploaderGroup'] = CompositeField::create([
            $this->fields['Desktop'],
            $this->fields['Tablet'],
            $this->fields['Phone'],
        ])->addExtraClass('picture-field__uploader-group');
    }

    private function saveMany(&$data)
    {
        if ($picID = $this->saveSingle($data, true)) {
            $this->picture->add($picID);
        }
    }

    private function saveSingle(&$data, $return = false)
    {
        $name = $this->name;

        if ($this->performDelete && $data->{$name}()->exists()) {
            $data->{$name}()->delete();
            return;
        }

        $pic = $return ? Picture::create() : ($data->{$name}()->exists() ? $data->{$name}() : Picture::create());

        $desktop = $this->fields['Desktop']->Value();
        $tablet  = $this->fields['Tablet']->Value();
        $phone   = $this->fields['Phone']->Value();

        if (empty($desktop)
            && empty($tablet)
            && empty($phone)
            && empty(trim($this->pictureTitle))
            && empty(trim($this->pictureCaption))
        ) {
            return;
        }

        $desktop = !empty($desktop) && !empty($desktop['Files']) ? $desktop['Files'][0] : null;
        $tablet  = !empty($tablet)  && !empty($tablet['Files']) ? $tablet['Files'][0] : null;
        $phone   = !empty($phone)   && !empty($phone['Files']) ? $phone['Files'][0] : null;

        $pic = $pic->update(array_merge([
            'DesktopWidth'  => $this->picDesktopWidth,
            'DesktopHeight' => $this->picDesktopHeight,
            'TabletWidth'   => $this->picTabletWidth,
            'TabletHeight'  => $this->picTabletHeight,
            'PhoneWidth'    => $this->picPhoneWidth,
            'PhoneHeight'   => $this->picPhoneHeight,
            'Title'         => $this->pictureTitle,
            'Caption'       => $this->pictureCaption,
            'DesktopID'     => $desktop,
            'TabletID'      => $tablet,
            'PhoneID'       => $phone,
        ], $this->additionalDBMappedValues));

        if ($return) {
            return $pic->write();
        }

        $this->setValue($pic->write());
        $data = $data->setCastedField($name, $this->dataValue());
    }
}
