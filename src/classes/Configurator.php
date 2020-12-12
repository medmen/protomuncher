<?php
namespace protomuncher;

use Medoo\Medoo;

class Configurator
{
    private $db, $cnf, $success;

    function __construct(Medoo $databsae)
    {
        $this->db = $databsae;
    }

    public function conf2form($geraet = 1)
    {
        $this->cnf = $this->db->select("helperfields", "*", ["geraet_id[=]" => $geraet]);

        $helpers = '<section id="helpers">';
        foreach ($this->cnf as $row) {
            $helpers.= '<span class="help">' . htmlspecialchars($row['help']) . '</span>';
            $helpers.= '<label for="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['label']) . '</label>';
            $helpers.= '<input type="' . htmlspecialchars($row['inputtype']) . '" name="' . htmlspecialchars($row['name']) . '" id="' . htmlspecialchars($row['name']) . '" placeholder="' . htmlspecialchars($row['placeholder']) . '" value="' . htmlspecialchars($row['value']) . '">';
            $helpers.= '<input type="hidden" name="geraet" id="geraet" value="' . $geraet . '">';
        }
        $helpers.='</section>';

        $this->cnf = $this->db->select("parameter", "*", ["geraet_id[=]" => $geraet]);
        $sel = '<select multiple name="parameter[]" id="parameter[]">';
        foreach ($this->cnf as $row) {
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

    private function sanitize($item)
    {
        return filter_var(trim($item), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
    }

    public function form2conf($array)
    {
        $this->success = false;
        $sql_err = array();

        if (!is_array($array)) {
            return $this->success;
        }
        unset($array['save_conf']); // we dont need the form name

        // sanitize input!
        $new_params = array_map($this->sanitize, explode(',', $array['new_params']));
        unset($array['new_params']);
        $old_params = array_map($this->sanitize, array_values($array['parameter']));
        unset($array['parameter']);
        // now $array should contain helperfields only, sanitize too
        $helperfields = array_map($this->sanitize, $array);
        unset($array);
        /**
         * start DB insert/update
         * @todo collect queries for a transaction, so we can roll back if one query fails
         * meedoo supports transactions via "action" command
         */

        // add new params if they dont exist
        if (is_array($new_params)) {
            foreach ($new_params as $new_param) {
                $this->db->insert('parameter', ['name' => $new_param, 'selected' => '1', 'geraet_id' => $helperfields['geraet']]);
                $err = $this->db->error();
                if($err[0] !== '00000') {
                    // log error
                    $err_count = count($sql_err);
                    $sql_err[$err_count] = $err[2];
                }
            }
        }

        $this->db->update('parameter', ['selected' => '0'], ['geraet_id[=]' => $helperfields['geraet']]);
        $sql_err[] = $this->db->error();
        foreach($old_params as $old_param) {
            $this->db->update('parameter', ['selected' => '1'], ['name[=]' => $old_param, 'geraet_id[=]' => $helperfields['geraet']]);
            $sql_err[] = $this->db->error();
        }

        foreach($helperfields as $name => $value) {
            $this->db->update('helperfields', ['value' => $value], ['name[=]' => $name]);
            $sql_err[] = $this->db->error();
        }

        return ($this->success);
    }
}