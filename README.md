# ebal_verifactu
Módulo instalable que prepara dolibarr para el envío a verifactu.
# Dolibarr Verifactu Module

Módulo para Dolibarr que prepara la integración con AEAT Verifactu:
- Añade campos `verifactu_*` a `llx_facture`.
- Crea `llx_verifactu_log`.
- Despliega `htdocs/custom/verifactu.php` y `htdocs/custom/respuesta_verifactu`.
- Trigger `BILL_VALIDATE` que llama al script.


## Instalación
1. Copiar esta carpeta `verifactu/` dentro de `htdocs/custom/`.
2. En Dolibarr: **Inicio → Configuración → Módulos/Aplicaciones** y activar **Verifactu**.
3. La activación:
- Crea `llx_verifactu_log`.
- Añade columnas `verifactu_*` en `llx_facture` (si no existen).
- Copia `scripts/verifactu.php` a `htdocs/custom/verifactu.php` (si no existe).
- Crea `htdocs/custom/respuesta_verifactu/` si no existe.


## Desinstalación
- El módulo **no** elimina columnas por defecto (para preservar datos). Puedes eliminarlas manualmente si lo deseas.


## Configuración
- Constante opcional: `VERIFACTU_SIMULATE_OK` = `1|0` (por defecto `1`).
