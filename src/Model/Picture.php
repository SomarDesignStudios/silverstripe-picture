<?php

namespace Cita\Model;

use SilverStripe\Assets\Image;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use TractorCow\Fluent\Extension\FluentExtension;

class Picture extends DataObject
{
    private static string $table_name = 'Cita_Picture';

    private static string $singular_name = 'picture';

    private static string $plural_name = 'pictures';

    private static array $db = [
        'Title'         => 'Varchar',
        'Caption'       => 'Varchar',
        'DesktopWidth'  => 'Int',
        'DesktopHeight' => 'Int',
        'TabletWidth'   => 'Int',
        'TabletHeight'  => 'Int',
        'PhoneWidth'    => 'Int',
        'PhoneHeight'   => 'Int',
        'Sort'          => 'Int',
    ];

    private static array $has_one = [
        'Desktop' => Image::class,
        'Tablet'  => Image::class,
        'Phone'   => Image::class,
    ];

    private static array $owns = [
        'Desktop',
        'Tablet',
        'Phone',
    ];

    private static array $summary_fields = [
        'Title'                => 'Title',
        'Desktop.CMSThumbnail' => 'Thumbnail',
        'Caption'              => 'Image Caption',
    ];

    private static array $searchable_fields = [
        'Title',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'DesktopWidth',
            'DesktopHeight',
            'TabletWidth',
            'TabletHeight',
            'PhoneWidth',
            'PhoneHeight',
            'Sort',
        ]);

        $dimensions = $this->getDimensions();

        $descriptions = [];

        if ($dimensions->sm) {
            $descriptions['Phone'] = "Recommended Width: {$dimensions->sm->Retina->Width}px, Height: {$dimensions->sm->Retina->Height}px";
        }

        if ($dimensions->md) {
            $descriptions['Tablet'] = "Recommended Width: {$dimensions->md->Retina->Width}px, Height: {$dimensions->md->Retina->Height}px";
        }

        if ($dimensions->lg) {
            $descriptions['Desktop'] = "Recommended Width: {$dimensions->lg->Default->Width}px, Height: {$dimensions->lg->Default->Height}px";
        } elseif ($dimensions->md) {
            $descriptions['Desktop'] = "Recommended Width: {$dimensions->md->Retina->Width}px, Height: {$dimensions->md->Retina->Height}px";
        } elseif ($dimensions->sm) {
            $descriptions['Desktop'] = "Recommended Width: {$dimensions->sm->Retina->Width}px, Height: {$dimensions->sm->Retina->Height}px";
        }

        foreach ($descriptions as $key => $value) {
            $fields->fieldByName("Root.Main.{$key}")->setDescription($value);
        }

        $fields->addFieldToTab(
            'Root.Main',
            $fields->fieldByName('Root.Main.Caption')
        );

        return $fields;
    }

    public function getCMSValidator(): RequiredFields
    {
        return RequiredFields::create(['Desktop']);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->Desktop()->exists()) {
            return;
        }

        if (empty(trim($this->Title))) {
            $this->Title = $this->Desktop()->Title;
        }
    }

    public function CMSThumbnail()
    {
        return $this->Desktop() instanceof Image && $this->Desktop()->exists() ? $this->Desktop()->CMSThumbnail() : null;
    }

    public function getDimensions()
    {
        $dimensions = [];

        if (!empty($this->PhoneWidth) && !empty($this->PhoneHeight)) {
            $dimensions['sm'] = [
                'Default' => [
                    'Width'  => $this->PhoneWidth,
                    'Height' => $this->PhoneHeight,
                ],
                'Retina' => [
                    'Width'  => $this->PhoneWidth * 2,
                    'Height' => $this->PhoneHeight * 2,
                ],
            ];
        }

        if (!empty($this->TabletWidth) && !empty($this->TabletHeight)) {
            $dimensions['md'] = [
                'Default' => [
                    'Width'  => $this->TabletWidth,
                    'Height' => $this->TabletHeight,
                ],
                'Retina' => [
                    'Width'  => $this->TabletWidth * 2,
                    'Height' => $this->TabletHeight * 2,
                ],
            ];
        }

        if (!empty($this->DesktopWidth) && !empty($this->DesktopHeight)) {
            $dimensions['lg'] = [
                'Default' => [
                    'Width'  => $this->DesktopWidth,
                    'Height' => $this->DesktopHeight,
                ],
            ];
        }

        return ArrayData::create($dimensions);
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        foreach ($this->config()->has_one as $rel => $value) {
            if ($this->{$rel}()->exists() && !$this->{$rel}()->isPublished()) {
                $this->{$rel}()->writeToStage('Live');
            }
        }
    }

    public function forTemplate()
    {
        return $this->renderWith($this->ClassName);
    }
}
