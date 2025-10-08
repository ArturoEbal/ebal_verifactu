<?php
/**
 * verifactu.php — Script llamado por el trigger BILL_VALIDATE
 * Versión inicial: simula respuesta OK de AEAT, guarda XML y registra en BD.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
    // Si se ejecuta fuera del contexto Dolibarr, intenta autodetectar
    define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../..');
}

/**
 * Simula/realiza envío a AEAT Verifactu y registra resultado.
 *
 * @param DoliDB  $db
 * @param Facture $invoice     Objeto factura validada
 * @param bool    $simulateOk  true: fuerza OK simulado
 * @param Conf    $conf
 * @return void
 */
function verifactu_send($db, $invoice, $simulateOk = true, $conf = null)
{
    $nowDate = date('Y-m-d');
    $nowTime = date('H:i:s');

    $ref   = !empty($invoice->ref) ? $invoice->ref : ('FAC'.$invoice->id);
    $rowid = (int) $invoice->id;

    // === 1) Simular resultado ===
    $idEnvio = 'vf_'.bin2hex(random_bytes(8));
    $estado  = $simulateOk ? 'OK' : 'KO';
    $qr      = 'SIMULATED-QR://AEAT/REF='.$ref.'/ID='.$idEnvio;

    // Generar XML de respuesta (simulada o futura real)
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
         . "<VerifactuResponse>\n"
         . "  <Status>{$estado}</Status>\n"
         . "  <InvoiceRef>{$ref}</InvoiceRef>\n"
         . "  <IdEnvio>{$idEnvio}</IdEnvio>\n"
         . "  <Fecha>{$nowDate}</Fecha>\n"
         . "  <Hora>{$nowTime}</Hora>\n"
         . "  <QR>{$qr}</QR>\n"
         . "</VerifactuResponse>\n";

    // === 2) Guardar XML en carpeta respuesta_verifactu ===
    $destDir = DOL_DOCUMENT_ROOT.'/custom/respuesta_verifactu';
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0775, true);
    }
    $fname = sprintf('Respuesta_%s_%s.xml',
        preg_replace('/[^A-Za-z0-9_-]/', '_', $ref),
        date('Ymd_His')
    );
    $fpath = $destDir.'/'.$fname;
    @file_put_contents($fpath, $xml);

    // Texto mensaje para log
    $msg = 'XML guardado en: '.$fpath;

    // === 3) Registrar en llx_verifactu_log ===
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."verifactu_log (id_envio, ref, hash_qr, fecha_envio_aeat, hora_envio_aeat, estado, mensaje) VALUES ("
         . "'".$db->escape($idEnvio)."',"
         . "'".$db->escape($ref)."',"
         . "'".$db->escape($qr)."',"
         . "'".$db->escape($nowDate)."',"
         . "'".$db->escape($nowTime)."',"
         . "'".$db->escape($estado)."',"
         . "'".$db->escape($msg)."')";
    $db->query($sql); // si falla no interrumpimos

    // === 4) Actualizar columnas verifactu_* en llx_facture ===
    $sql2 = "UPDATE ".MAIN_DB_PREFIX."facture SET "
          . "verifactu_id_envio='".$db->escape($idEnvio)."', "
          . "verifactu_fecha_envio_aeat='".$db->escape($nowDate)."', "
          . "verifactu_hora_envio_aeat='".$db->escape($nowTime)."', "
          . "verifactu_estado='".$db->escape($estado)."' "
          . "WHERE rowid=".$rowid;
    $db->query($sql2);
}
