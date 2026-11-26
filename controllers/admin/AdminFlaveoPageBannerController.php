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

require_once _PS_MODULE_DIR_ . 'flaveo_pagebanner/classes/FlaveoPageBannerItem.php';

class AdminFlaveoPageBannerController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = FlaveoPageBannerItem::TABLE;
        $this->identifier = 'id_flaveo_pagebanner';
        $this->className = 'FlaveoPageBannerItem';
        $this->lang = true;
        $this->position_identifier = 'id_flaveo_pagebanner';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->allow_export = false;

        parent::__construct();

        $this->addRowAction('edit');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Admin.Actions'),
                'icon' => 'icon-trash',
                'confirm' => $this->trans('Delete selected items?', [], 'Admin.Notifications.Warning'),
            ],
        ];

        $this->fields_list = [
            'id_flaveo_pagebanner' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'message' => [
                'title' => $this->l('Message'),
                'callback' => 'renderMessagePreview',
                'search' => true,
                'filter_key' => 'b!message',
            ],
            'date_start' => [
                'title' => $this->l('Début'),
                'type' => 'datetime',
                'callback' => 'renderDate',
            ],
            'date_end' => [
                'title' => $this->l('Fin'),
                'type' => 'datetime',
                'callback' => 'renderDate',
            ],
            'status' => [
                'title' => $this->l('État'),
                'callback' => 'renderStatus',
                'search' => false,
                'orderby' => false,
                'align' => 'center',
            ],
            'marquee' => [
                'title' => $this->l('Défilant'),
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'callback' => 'renderYesNo',
            ],
            'position' => [
                'title' => $this->l('Position'),
                'position' => 'position',
                'align' => 'center',
                'class' => 'fixed-width-md',
                'filter_key' => 'a!position',
            ],
            'active' => [
                'title' => $this->l('Activé'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ],
        ];

        // Extra columns needed by the callbacks
        $this->_select = 'a.`bg_color`, a.`text_color`, a.`date_start` AS `status`';
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_banner'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Ajouter un bandeau'),
                'icon' => 'process-icon-new',
            ];
        }
        parent::initPageHeaderToolbar();
    }

    /* ------------------------------------------------------------------ */
    /* List callbacks                                                     */
    /* ------------------------------------------------------------------ */

    public function renderMessagePreview($message, $row)
    {
        $text = trim(strip_tags((string) $message));
        if (Tools::strlen($text) > 90) {
            $text = Tools::substr($text, 0, 87) . '…';
        }

        return '<span style="display:inline-block;padding:4px 10px;border-radius:3px;background:'
            . Tools::safeOutput($row['bg_color']) . ';color:' . Tools::safeOutput($row['text_color']) . ';">'
            . Tools::safeOutput($text) . '</span>';
    }

    public function renderDate($value, $row)
    {
        if (empty($value) || strpos($value, '0000') === 0) {
            return '<span class="text-muted">—</span>';
        }

        return Tools::displayDate($value, null, true);
    }

    public function renderStatus($value, $row)
    {
        $status = FlaveoPageBannerItem::computeStatus($row['active'], $row['date_start'], $row['date_end']);
        $map = [
            'running' => ['label-success', $this->l('En cours')],
            'scheduled' => ['label-info', $this->l('Programmé')],
            'expired' => ['label-default', $this->l('Expiré')],
            'disabled' => ['label-warning', $this->l('Désactivé')],
        ];

        return '<span class="label ' . $map[$status][0] . '">' . $map[$status][1] . '</span>';
    }

    /**
     * Simple duplicate link (the core one asks about copying images).
     */
    public function displayDuplicateLink($token, $id, $name = null)
    {
        $href = self::$currentIndex . '&' . $this->identifier . '=' . (int) $id
            . '&duplicate' . $this->table . '&token=' . ($token ?: $this->token);

        return '<a href="' . Tools::safeOutput($href) . '" title="' . $this->l('Dupliquer') . '">'
            . '<i class="icon-copy"></i> ' . $this->l('Dupliquer') . '</a>';
    }

    public function renderYesNo($value, $row)
    {
        return $value
            ? '<i class="icon-check text-success"></i>'
            : '<i class="icon-remove text-muted"></i>';
    }

    /* ------------------------------------------------------------------ */
    /* Form                                                               */
    /* ------------------------------------------------------------------ */

    public function renderForm()
    {
        /** @var FlaveoPageBannerItem $obj */
        $obj = $this->loadObject(true);

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Bandeau'),
                'icon' => 'icon-bullhorn',
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Activé'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => $this->switchValues('active'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Message'),
                    'name' => 'message',
                    'lang' => true,
                    'required' => true,
                    'hint' => $this->l('HTML simple autorisé (<strong>, <em>, <span>…).'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Lien'),
                    'name' => 'link',
                    'lang' => true,
                    'desc' => $this->l('Optionnel. Rend tout le message cliquable.'),
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Afficher à partir du'),
                    'name' => 'date_start',
                    'desc' => $this->l('Laisser vide pour afficher immédiatement.'),
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Afficher jusqu\'au'),
                    'name' => 'date_end',
                    'desc' => $this->l('Laisser vide pour ne pas définir de fin.'),
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Couleur de fond'),
                    'name' => 'bg_color',
                    'required' => true,
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Couleur du texte'),
                    'name' => 'text_color',
                    'required' => true,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Mode marquee (texte défilant)'),
                    'name' => 'marquee',
                    'is_bool' => true,
                    'values' => $this->switchValues('marquee'),
                    'desc' => $this->l('Le message défile en continu. Il se met en pause au survol et reste fixe pour les visiteurs ayant activé « réduire les animations ».'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Vitesse de défilement'),
                    'name' => 'marquee_speed',
                    'suffix' => 'px/s',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('Par défaut : 60. Plus la valeur est haute, plus c\'est rapide.'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Peut être fermé'),
                    'name' => 'closable',
                    'is_bool' => true,
                    'values' => $this->switchValues('closable'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Masquer après fermeture pendant'),
                    'name' => 'cookie_days',
                    'suffix' => $this->l('jours'),
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('0 = jusqu\'à la fermeture du navigateur. Modifier le bandeau le réaffiche à tout le monde.'),
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
            ],
            'buttons' => [
                'save-and-stay' => [
                    'title' => $this->trans('Save and stay', [], 'Admin.Actions'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                ],
            ],
        ];

        // Clean "0000-00-00" / NULL dates for the datepicker
        foreach (['date_start', 'date_end'] as $field) {
            $value = $this->getFieldValue($obj, $field);
            $this->fields_value[$field] = empty($value) || strpos($value, '0000') === 0 ? '' : $value;
        }

        return parent::renderForm();
    }

    protected function switchValues($name)
    {
        return [
            ['id' => $name . '_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
            ['id' => $name . '_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Processing                                                         */
    /* ------------------------------------------------------------------ */

    public function processSave()
    {
        $start = trim((string) Tools::getValue('date_start'));
        $end = trim((string) Tools::getValue('date_end'));

        foreach (['date_start' => $start, 'date_end' => $end] as $field => $value) {
            if ($value !== '' && !Validate::isDate($value)) {
                $this->errors[] = sprintf($this->l('Date invalide pour le champ « %s ».'), $field);
            }
        }
        if ($start !== '' && $end !== '' && strtotime($end) <= strtotime($start)) {
            $this->errors[] = $this->l('La date de fin doit être postérieure à la date de début.');
        }
        foreach (['marquee_speed', 'cookie_days'] as $field) {
            $value = Tools::getValue($field);
            if ($value !== '' && $value !== false && !Validate::isUnsignedInt($value)) {
                $this->errors[] = sprintf($this->l('Le champ « %s » doit être un entier positif.'), $field);
            }
        }

        if (!empty($this->errors)) {
            $this->display = 'edit';
            $this->action = '';

            return false;
        }

        return parent::processSave();
    }

    protected function copyFromPost(&$object, $table)
    {
        parent::copyFromPost($object, $table);

        $object->date_start = trim((string) $object->date_start) === '' ? null : $object->date_start;
        $object->date_end = trim((string) $object->date_end) === '' ? null : $object->date_end;
        $object->marquee_speed = (int) $object->marquee_speed ?: 60;
        $object->cookie_days = (int) $object->cookie_days;
    }

    public function processDuplicate()
    {
        $source = $this->loadObject();
        if (!Validate::isLoadedObject($source)) {
            $this->errors[] = $this->l('Impossible de charger le bandeau.');

            return false;
        }

        $copy = clone $source;
        $copy->id = null;
        $copy->active = false;
        $copy->position = 0;
        if ($copy->add()) {
            $this->redirect_after = self::$currentIndex . '&conf=19&token=' . $this->token;

            return true;
        }

        $this->errors[] = $this->l('Impossible de dupliquer le bandeau.');

        return false;
    }

    public function initProcess()
    {
        if (Tools::getIsset('duplicate' . $this->table)) {
            $this->action = 'duplicate';
            $this->display = 'list';
            if (!$this->access('add')) {
                $this->errors[] = $this->trans('You do not have permission to add this.', [], 'Admin.Notifications.Error');
                $this->action = '';
            }

            return;
        }

        parent::initProcess();
    }

    public function ajaxProcessUpdatePositions()
    {
        $way = (int) Tools::getValue('way');
        $id = (int) Tools::getValue('id');
        $positions = Tools::getValue($this->table);

        if (!is_array($positions)) {
            die('{"hasError" : true, "errors" : "No positions"}');
        }

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);
            if (isset($pos[2]) && (int) $pos[2] === $id) {
                $banner = new FlaveoPageBannerItem($id);
                if (Validate::isLoadedObject($banner) && $banner->updatePosition($way, (int) $position)) {
                    die('ok position ' . (int) $position . ' for banner ' . $id);
                }
                die('{"hasError" : true, "errors" : "Can not update banner ' . $id . ' to position ' . (int) $position . '"}');
            }
        }

        die('{"hasError" : true, "errors" : "Banner not found"}');
    }
}
