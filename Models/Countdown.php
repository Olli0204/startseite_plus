<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Models;

use Exception;
use JTL\Model\DataAttribute;
use JTL\Model\DataModel;
use JTL\Model\InputConfig;
use JTL\Plugin\Admin\InputType;

/**
 * @property int    $id
 * @property string $name
 * @property string $until
 * @property string $label
 * @property string $label_en
 * @property string $style
 * @property string $expired_mode
 * @property string $expired_text
 * @property string $expired_text_en
 * @property string $product_page
 * @property int    $active
 * @method   int    getId()
 * @method   string getName()
 * @method   string getUntil()
 * @method   string getLabel()
 * @method   string getStyle()
 * @method   int    getActive()
 */
final class Countdown extends DataModel
{
    public function getTableName(): string
    {
        return 'startseite_plus_countdown';
    }

    /**
     * Endzeitpunkt als Wert für <input type="datetime-local"> (leer bei neuem Datensatz).
     * Hinweis: Felder mit Unterstrich (label_en, expired_mode, …) sind im Template über
     * $item->label_en zu lesen; die magischen Getter getLabelEn() kennt DataModel nicht.
     */
    public function getUntilInput(): string
    {
        $ts = \strtotime((string)($this->until ?? ''));

        return $ts === false || $ts <= 0 ? '' : \date('Y-m-d\TH:i', $ts);
    }

    public function setKeyName($keyName): void
    {
        throw new Exception(__METHOD__ . ': setting of keyname is not supported', self::ERR_DATABASE);
    }

    public function getAttributes(): array
    {
        static $attributes = null;
        if ($attributes !== null) {
            return $attributes;
        }

        $id = DataAttribute::create('id', 'int', null, false, true);
        $id->getInputConfig()->setHidden(true);

        $select = static function (array $allowed): InputConfig {
            $config = new InputConfig();
            $config->setInputType(InputType::SELECT);
            $config->setAllowedValues($allowed);

            return $config;
        };

        $style = DataAttribute::create('style', 'varchar', 'boxes', false);
        $style->setInputConfig($select(['boxes' => 'Kästchen', 'inline' => 'Textzeile']));
        $style->getInputConfig()->setHidden(true);

        $mode = DataAttribute::create('expired_mode', 'varchar', 'hide', false);
        $mode->setInputConfig($select(['hide' => 'Ausblenden', 'text' => 'Hinweistext', 'keep' => 'Ohne Countdown weiter anzeigen']));
        $mode->getInputConfig()->setHidden(true);

        $productPage = DataAttribute::create('product_page', 'varchar', 'none', false);
        $productPage->setInputConfig($select(['none' => 'Nein', 'sale' => 'Nur bei Sonderpreis', 'all' => 'Immer']));

        $hidden = static function (string $name, string $type = 'varchar', mixed $default = ''): DataAttribute {
            $attr = DataAttribute::create($name, $type, $default, false);
            $attr->getInputConfig()->setHidden(true);

            return $attr;
        };

        $attributes = [
            'id'              => $id,
            'name'            => DataAttribute::create('name', 'varchar', '', false),
            'until'           => DataAttribute::create('until', 'datetime', null, false),
            'label'           => $hidden('label'),
            'label_en'        => $hidden('label_en'),
            'style'           => $style,
            'expired_mode'    => $mode,
            'expired_text'    => $hidden('expired_text'),
            'expired_text_en' => $hidden('expired_text_en'),
            'product_page'    => $productPage,
            'active'          => DataAttribute::create('active', 'tinyint', 1, false),
        ];

        return $attributes;
    }
}
