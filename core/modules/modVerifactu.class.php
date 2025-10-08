<?php
/**
 * modVerifactu.class.php — Descriptor del módulo + instalación BD y despliegue de scripts
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modVerifactu extends DolibarrModules
{
    /** @var DoliDB */
    public $db;

    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        $this->numero = 104900;                 // Un número único y alto
        $this->rights_class = 'verifactu';

        $this->family = 'crm';                   // Familia visible en listado módulos
        $this->module_position = 500;
        $this->name = 'verifactu';
        $this->description = 'Integración AEAT Verifactu (instalador, log y trigger)';
        $this->editor_name = 'Nou Signe';
        $this->editor_url = 'https://www.nousigne.com';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_VERIFACTU';
        $this->special = 0;
        $this->picto = 'generic';

        // Activamos triggers
        $this->module_parts = array(
            'triggers' => 1
        );

        $this->dirs = array();
        $this->rights = array();
        $this->depends = array();
        $this->config_page_url = array();
    }

    /** Instalación del módulo */
    public function init($options = '')
    {
        $this->_load_tables_and_columns();
        $this->_deploy_scripts_and_dirs();

        // Constante para simular OK (1 por defecto)
        $this->insert_const('VERIFACTU_SIMULATE_OK', 1, 'chaine', 0, 'Simular respuestas OK de AEAT');

        return $this->_init();
    }

    /** Desinstalación (no borra datos por seguridad) */
    public function remove($options = '')
    {
        // Si quisieras eliminar tabla/columnas, hazlo aquí. Por seguridad lo omitimos.
        return $this->_remove($options);
    }

    private function _load_tables_and_columns()
    {
        $this->db->begin();

        // 1) Crear tabla llx_verifactu_log si no existe
        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX."verifactu_log (
              rowid INT AUTO_INCREMENT PRIMARY KEY,
              tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              id_envio VARCHAR(64) NOT NULL,
              ref VARCHAR(128) NOT NULL,
              hash_qr TEXT NULL,
              fecha_envio_aeat DATE NULL,
              hora_envio_aeat TIME NULL,
              estado VARCHAR(10) NULL,
              mensaje MEDIUMTEXT NULL,
              INDEX idx_verifactu_ref (ref),
              INDEX idx_verifactu_id_envio (id_envio)
            ) ENGINE=innodb";
        $res = $this->db->query($sql);
        if (!$res) {
            $this->db->rollback();
            dol_syslog(__METHOD__.' error creating table verifactu_log: '.$this->db->lasterror(), LOG_ERR);
            return -1;
        }

        // 2) Añadir columnas a llx_facture si no existen
        $factTable = MAIN_DB_PREFIX.'facture';
        $this->_addColumnIfNotExists($factTable, 'verifactu_id_envio',          "VARCHAR(64) NULL");
        $this->_addColumnIfNotExists($factTable, 'verifactu_fecha_envio_aeat',  "DATE NULL");
        $this->_addColumnIfNotExists($factTable, 'verifactu_hora_envio_aeat',   "TIME NULL");
        $this->_addColumnIfNotExists($factTable, 'verifactu_estado',            "VARCHAR(10) NULL");

        $this->db->commit();
    }

    private function _addColumnIfNotExists($table, $column, $type)
    {
        $sql = "SHOW COLUMNS FROM $table LIKE '".$this->db->escape($column)."'";
        $res = $this->db->query($sql);
        if ($res && $this->db->num_rows($res) == 0) {
            $alter = "ALTER TABLE $table ADD COLUMN $column $type";
            $ok = $this->db->query($alter);
            if (!$ok) {
                dol_syslog(__METHOD__.' error altering table '.$table.' add '.$column.' : '.$this->db->lasterror(), LOG_ERR);
            }
        }
    }

    private function _deploy_scripts_and_dirs()
    {
        $fsRoot = DOL_DOCUMENT_ROOT;                 // .../htdocs
        $customRoot = $fsRoot.'/custom';             // .../htdocs/custom

        // 1) Copiar verifactu.php si no existe
        $src = dol_buildpath('/verifactu/scripts/verifactu.php', 0);
        $dst = $customRoot.'/verifactu.php';
        if (is_readable($src) && !file_exists($dst)) {
            @copy($src, $dst);
        }

        // 2) Crear carpeta de respuestas si no existe
        $dir = $customRoot.'/respuesta_verifactu';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}
