<?php
/**
 * Flaveo Page Banner
 *
 * @author    Loris Pinna <https://lorispinna.com>
 * @copyright Loris Pinna
 * @license   Proprietary
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class FlaveoPageBannerItem extends ObjectModel
{
    const TABLE = 'flaveo_pagebanner';

    /** @var bool */
    public $active = true;
    /** @var string|null */
    public $date_start;
    /** @var string|null */
    public $date_end;
    /** @var string */
    public $bg_color = '#000000';
    /** @var string */
    public $text_color = '#ffffff';
    /** @var bool */
    public $marquee = false;
    /** @var int Marquee speed in pixels per second */
    public $marquee_speed = 60;
    /** @var bool */
    public $closable = true;
    /** @var int Days before a closed banner is shown again (0 = browser session) */
    public $cookie_days = 1;
    /** @var string Regenerated on each save so an edited banner is shown again */
    public $cookie_key = '';
    /** @var int */
    public $position = 0;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    /** @var string|array */
    public $message;
    /** @var string|array */
    public $link;

    public static $definition = [
        'table' => self::TABLE,
        'primary' => 'id_flaveo_pagebanner',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_start' => ['type' => self::TYPE_DATE, 'allow_null' => true],
            'date_end' => ['type' => self::TYPE_DATE, 'allow_null' => true],
            'bg_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32, 'required' => true],
            'text_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32, 'required' => true],
            'marquee' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'marquee_speed' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'closable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'cookie_days' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'cookie_key' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            // Lang fields
            'message' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'link' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 512],
        ],
    ];

    public function add($autoDate = true, $nullValues = true)
    {
        $this->normalize();
        if (!$this->position) {
            $this->position = self::getNextPosition();
        }

        return parent::add($autoDate, $nullValues);
    }

    public function update($nullValues = true)
    {
        $this->normalize();

        return parent::update($nullValues);
    }

    public function delete()
    {
        $res = parent::delete();
        if ($res) {
            self::cleanPositions();
        }

        return $res;
    }

    /**
     * Empty dates -> NULL, fresh cookie key so visitors see the edited banner again.
     */
    protected function normalize()
    {
        $this->date_start = empty($this->date_start) || strpos($this->date_start, '0000') === 0 ? null : $this->date_start;
        $this->date_end = empty($this->date_end) || strpos($this->date_end, '0000') === 0 ? null : $this->date_end;
        $this->cookie_key = substr(md5(uniqid('', true)), 0, 12);
        $this->marquee_speed = max(10, (int) $this->marquee_speed);
    }

    /**
     * Banners active right now, ordered by position.
     *
     * @param int $idLang
     *
     * @return array
     */
    public static function getCurrentBanners($idLang)
    {
        $now = pSQL(date('Y-m-d H:i:s'));
        $sql = new DbQuery();
        $sql->select('b.*, bl.message, bl.link')
            ->from(self::TABLE, 'b')
            ->innerJoin(self::TABLE . '_lang', 'bl', 'bl.id_flaveo_pagebanner = b.id_flaveo_pagebanner AND bl.id_lang = ' . (int) $idLang)
            ->where('b.active = 1')
            ->where('(b.date_start IS NULL OR b.date_start <= \'' . $now . '\')')
            ->where('(b.date_end IS NULL OR b.date_end >= \'' . $now . '\')')
            ->where('bl.message IS NOT NULL AND TRIM(bl.message) != \'\'')
            ->orderBy('b.position ASC');

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Status label key for the admin list.
     *
     * @return string running|scheduled|expired|disabled
     */
    public static function computeStatus($active, $dateStart, $dateEnd)
    {
        if (!$active) {
            return 'disabled';
        }
        $now = time();
        if (!empty($dateStart) && strtotime($dateStart) > $now) {
            return 'scheduled';
        }
        if (!empty($dateEnd) && strtotime($dateEnd) < $now) {
            return 'expired';
        }

        return 'running';
    }

    public static function getNextPosition()
    {
        $max = Db::getInstance()->getValue('SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . self::TABLE . '`');

        return $max === null || $max === false ? 0 : (int) $max + 1;
    }

    /**
     * Drag & drop handler used by the admin list.
     */
    public function updatePosition($way, $position)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_flaveo_pagebanner`, `position` FROM `' . _DB_PREFIX_ . self::TABLE . '` ORDER BY `position` ASC'
        );
        if (!$rows) {
            return false;
        }

        $moved = null;
        foreach ($rows as $row) {
            if ((int) $row['id_flaveo_pagebanner'] === (int) $this->id) {
                $moved = $row;
            }
        }
        if ($moved === null) {
            return false;
        }

        $table = '`' . _DB_PREFIX_ . self::TABLE . '`';
        $position = (int) $position;
        $current = (int) $moved['position'];

        return Db::getInstance()->execute(
            'UPDATE ' . $table . ' SET `position` = `position` ' . ($way ? '- 1' : '+ 1') .
            ' WHERE `position` ' . ($way
                ? '> ' . $current . ' AND `position` <= ' . $position
                : '< ' . $current . ' AND `position` >= ' . $position)
        ) && Db::getInstance()->execute(
            'UPDATE ' . $table . ' SET `position` = ' . $position .
            ' WHERE `id_flaveo_pagebanner` = ' . (int) $this->id
        );
    }

    public static function cleanPositions()
    {
        $ids = Db::getInstance()->executeS(
            'SELECT `id_flaveo_pagebanner` FROM `' . _DB_PREFIX_ . self::TABLE . '` ORDER BY `position` ASC'
        );
        if (!$ids) {
            return true;
        }
        foreach ($ids as $i => $row) {
            Db::getInstance()->update(
                self::TABLE,
                ['position' => (int) $i],
                '`id_flaveo_pagebanner` = ' . (int) $row['id_flaveo_pagebanner']
            );
        }

        return true;
    }
}
