<?php
/**
 * Trigger: llama a verifactu.php al validar factura (BILL_VALIDATE)
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceVerifactu extends DolibarrTriggers
{
    public $family = 'verifactu';

    /** @var DoliDB */
    public $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->description = 'Llama a htdocs/custom/verifactu.php para enviar (o simular) Verifactu';
        $this->version = '1.0.0';
    }

    /**
     * @param string   $action   Ex: BILL_VALIDATE
     * @param Facture  $object   Objeto factura
     */
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        if ($action === 'BILL_VALIDATE' && !empty($object) && !empty($object->id)) {
            $script = DOL_DOCUMENT_ROOT.'/custom/verifactu.php';
            if (is_readable($script)) {
                require_once $script;
                if (function_exists('verifactu_send')) {
                    try {
                        $simulateOk = isset($conf->global->VERIFACTU_SIMULATE_OK) ? ((int) $conf->global->VERIFACTU_SIMULATE_OK === 1) : true;
                        verifactu_send($this->db, $object, $simulateOk, $conf);
                    } catch (Exception $e) {
                        dol_syslog(__METHOD__.' Exception: '.$e->getMessage(), LOG_ERR);
                    }
                } else {
                    dol_syslog(__METHOD__.' verifactu_send() no encontrada en verifactu.php', LOG_ERR);
                }
            } else {
                dol_syslog(__METHOD__.' no se encuentra htdocs/custom/verifactu.php', LOG_ERR);
            }
        }
        return 0; // continuar con resto de triggers
    }
}
