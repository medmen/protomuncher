<?php
return(
    array(
        'limit' => array(
              'conf_limit_files' => '1,9',
              'name' => 'conf_limit_files',
              'typ' => 'text',
              'hilfe' => 'Wenn nur ein Teil der Seiten im PDF verarbeitet werden soll, können sie hier Start- und Endseite getrennt durch Komma (,) angeben'
        ),
        'valid'=> array(
              'conf_valid_entries' => array(
                    'schichtdicke',
                    'distanzfaktor',
                    'schichten',
                    'tr',
                    'te',
                    'fov auslese'
              ),
            'name' => 'conf_valid_entries',
            'typ' => 'select',
            'hilfe' => 'In der folgenden Liste können die anzuzeigenden Parameter geändert werden, auch die Reihenfolge ist einstellbar',
            'hilfe_new' => 'Neue Einträge getrennt durch Komma (,) angeben'
        ),
        'debug' => array(
            'conf_protomuncher_debug' => true,
            'name' => 'conf_protomuncher_debug',
            'typ' => 'checkbox',
            'hilfe' => 'für debug-Ausgabe Häkchen setzen (nur für Entwickler interessant)'
        )
    )
);