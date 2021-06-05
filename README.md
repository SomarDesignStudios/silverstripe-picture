# CITANZ's SilverStripe Picture object and field
The object that allows the users to upload 3 images for 3 different breakpoints: mobile, tablet and desktop, and also accepts the dimension settings for each individual images.

For more details... read the code yourself?

### Usage
1. Install
  ```
  composer require cita/silverstripe-picture
  ```

2. `/dev/build?flush=all`

3. Sample code:
```
...
use Cita\Model\Picture;
use Cita\FormField\PictureField;
...

private static $has_one = [
    'Picture' => Picture::class,
];

private static $many_many = [
    'Pictures' => Picture::class,
];

...

public function getCMSFields()
{
    $fields = parent::getCMSFields();

    $fields->addFieldToTab(
        'Root.Main',
        PictureField::create('Picture', 'Picture', $this)
            ->setFolderName('ContentPictures')
            ->setDimensions([
                'Desktop' => [
                    'Width' => 320,
                    'Height' => 320,
                ],
                'Tablet' => [
                    'Width' => 240,
                    'Height' => 240,
                ],
                'Phone' => [
                    'Width' => 120,
                    'Height' => 120,
                ],
            ])
    );

    $fields->addFieldToTab(
        'Root.Pictures',
        PictureField::create('Pictures', 'Pictures', $this)
            ->setFolderName('content')
            ->setDimensions([
                'Desktop' => [
                    'Width' => 320,
                    'Height' => 320,
                ],
                'Tablet' => [
                    'Width' => 240,
                    'Height' => 240,
                ],
                'Phone' => [
                    'Width' => 120,
                    'Height' => 120,
                ],
            ])
    );

    return $fields;
}
```
