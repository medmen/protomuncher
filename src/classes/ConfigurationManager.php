<?php
namespace protomuncher\classes;

use ErrorException;
use Medoo\Medoo;

class ConfigurationManager
{
    private $db, $geraet, $parameters, $helper, $cnf, $success;

    function __construct(Medoo $database, ConfigObject $cnf)
    {
        $this->db = $database;
        $this->geraet = $cnf->getGeraet();
        $this->parameters = array();
        $this->helper = array();
        $this->cnf = $cnf;
        if (false === $this->isValidGeraet()) {
            throw new ErrorException('invalid_geraet');
        }
    }

    function isValidGeraet(): bool
    {
        return (
        $this->db->has("geraet",
            ["geraet_id" => $this->geraet]
        )
        );
    }

    public function populateConf(): void
    {
        // select($table, $join, $columns, $where)
        $params = $this->db->select("parameter",
            "name",
            ["selected[=]" => 1,
                "geraet_id[=]" => $this->geraet
            ]);

        //@Problem: dont create numeric arrays here - do name => value
        $helpers = $this->db->select("helperfields",
            [ //fields
                "name",
                "value"
            ],
            [   // where
                "geraet_id[=]" => $this->geraet
            ]);

        $this->cnf->setParameters($params);
        $this->cnf->setHelpers($helpers);
    }


    public function conf2form(): string
    {
        $cnf = $this->db->select("helperfields", "*", ["geraet_id[=]" => $this->geraet]);

        $helpers = '<section id="helpers">';
        foreach ($cnf as $row) {
            $helpers .= '<span class="help">' . htmlspecialchars($row['help']) . '</span>';
            $helpers .= '<label for="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['label']) . '</label>';
            $helpers .= '<input type="' . htmlspecialchars($row['inputtype']) . '" name="' . htmlspecialchars($row['name']) . '" id="' . htmlspecialchars($row['name']) . '" placeholder="' . htmlspecialchars($row['placeholder']) . '" value="' . htmlspecialchars($row['value']) . '">';
            $helpers .= '<input type="hidden" name="geraet" id="geraet" value="' . $this->geraet . '">';
        }
        $helpers.='</section>';

        $cnf = $this->db->select("parameter", "*", ["geraet_id[=]" => $this->geraet]);
        $sel = '<select multiple name="parameter[]" id="parameter[]">';
        foreach ($cnf as $row) {
            $default = intval($row['default']) ? 'class="default"' : '';
            $selected = intval($row['selected']) ? 'selected' : '';
            $sel .= '<option ' . $default . ' value="' . htmlspecialchars($row['name']) . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
        }
        $sel .= '</select>';

        $new_params = '<span class="help">Wenn sie neue Parameter eingeben wollen, bitte mit Komma (,) trennen</span>';
        $new_params .= '<label for="new_params">Neue Parameter</label>';
        $new_params .= '<input type="text" name="new_params" id="new_params" placeholder="parameter1, parameter2">';

        return ($helpers.$sel.$new_params);
    }

    public function sanitize($item)
    {
        return filter_var(trim($item), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
    }

    public function form2conf(array $configOptionsFromForm): array
    {
        $this->success = false;
        $sql_err = array();

        if (!is_array($configOptionsFromForm)) {
            return array('status' => $this->success);
        }
        unset($configOptionsFromForm['save_conf']); // we dont need the form name

        // sanitize input!
        $new_params = array_map($this->sanitize, explode(',', $configOptionsFromForm['new_params']));
        unset($configOptionsFromForm['new_params']);
        $old_params = array_map($this->sanitize, array_values($configOptionsFromForm['parameter']));
        unset($configOptionsFromForm['parameter']);
        // now $configOptionsFromForm should contain helperfields only, sanitize too
        $helperfields = array_map($this->sanitize, $configOptionsFromForm);
        unset($configOptionsFromForm);
        /**
         * start DB insert/update
         * @todo collect queries for a transaction, so we can roll back if one query fails
         * meedoo supports transactions via "action" command
         */

        // set all parameters to not selected
        $this->db->update('parameter', ['selected' => '0'], ['geraet_id[=]' => $this->geraet]);
        if ($this->db->error()[2] !== 'null') {
            // log error
            $sql_err[] = $this->db->error()[2];
        }

        // add new params if they dont exist
        if (is_array($new_params)) {
            foreach ($new_params as $new_param) {
                $this->db->insert('parameter', ['name' => $new_param, 'selected' => '1', 'geraet_id' => $this->geraet]);
                $err = $this->db->error();
                if ($err[0] !== '00000') {
                    // log error
                    $sql_err[] = $err[2];
                }
            }
        }

        // set transmitted parameters to selected
        foreach($old_params as $old_param) {
            $this->db->update('parameter', ['selected' => '1'], ['name[=]' => $old_param, 'geraet_id[=]' => $this->geraet]);
            if ($this->db->error()[2] !== 'null') {
                // log error
                $sql_err[] = $this->db->error()[2];
            }
        }

        // update helperfields
        foreach ($helperfields as $name => $value) {
            $this->db->update('helperfields', ['value' => $value], ['name[=]' => $name, 'geraet_id[=]' => $this->geraet]);
            if ($this->db->error()[2] !== 'null') {
                // log error
                $sql_err[] = $this->db->error()[2];
            }
        }

        // we only return success if no errors occurred
        if (empty($sql_err)) {
            $sql_err[] = 'Konfiguration erfolgreich gespeichert';
        }
        return array('status' => $this->success, 'message' => $sql_err);

    }
}