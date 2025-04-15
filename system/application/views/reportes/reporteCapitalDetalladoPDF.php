<!doctype html>
<html lang="ES">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>REPORTE CAPITAL SOCIAL DETALLADO</title>
  <?php
  $logoMain = getConvertPDFToBase64('images/img_db/comprobante_orden_recepcion.jpg');
  $numberDocumento = ($cliente->tipo == 1) ? $cliente->ruc : $cliente->dni;
  $fecha = formatDate($row->OCOMC_Fecha);
  $fechaHoy = date('d/m/Y');
  ?>
  <style>
    html {
      font-family: 'helvetica' !important;
      margin: 20px 25px;
    }

    body {
      font-size: 7pt;
      font-family: 'helvetica' !important;
    }

    .center {
      text-align: center;
    }

    .width100 {
      width: 100%;
    }

    .bold,
    label {
      font-weight: 500;
      font-family: 'helvetica' !important;

    }

    .bold {

      background-color: #e1e1e1;
    }

    .right {
      text-align: right;
    }

    table {
      width: 100%;
      font-family: 'helvetica' !important;
    }

    table tr td {
      font-family: 'helvetica' !important;
      padding: 3px 5px;
    }

    p {
      margin: 5px;
    }

    #tbTableMain thead tr td,
    #tbTableMain tfoot tr td {
      padding: 7px;
      font-family: 'helvetica' !important;
    }

    #tbTableMain tbody tr td {
      padding: 5px;
    }

    .page_break {
      page-break-before: always;
    }

    .grid {
      display: grid;
    }
  </style>
  <link rel="stylesheet" href="<?php base_url() ?>css/grid12.css">
</head>

<body>
  <table border="0" cellspacing="0" cellspacing="0">
    <tr>
      <td class="bold center">
        <h3 style="margin: 0;">REPORTE CAPITAL SOCIAL DETALLADO</h3>
      </td>
    </tr>
  </table>

  <table border="0" cellspacing="0" cellspacing="0" style="margin-bottom: 5px;">
    <tr>
      <td style="width: 100%;" colspan="2">
        <label>EMPRESA: </label>
        <?php echo isset($datos_empresa[0]->EMPRC_RazonSocial) ? $datos_empresa[0]->EMPRC_RazonSocial : '' ?>
      </td>
    </tr>
    <tr>
      <td style="width: 100%;" colspan="2">
        <label>RUC: </label> <?php echo isset($datos_empresa[0]->EMPRC_Ruc) ? $datos_empresa[0]->EMPRC_Ruc : '' ?>
      </td>
    </tr>
    <tr>
      <td style="width: 100%;" colspan="2">
        <label>LOCAL: </label>
        <?php echo isset($establecimiento[0]->EESTABC_Descripcion) ? $establecimiento[0]->EESTABC_Descripcion : '-' ?>
      </td>
    </tr>
    <tr>
      <td style="width: 100%;">
        <label>FECHA: </label> <?php echo mysql_to_human($fecha1) . ' - ' . mysql_to_human($fecha2) ?>
      </td>
    </tr>
  </table>

  <table border="1" cellspacing="0" id="tbTableMain">
    <thead>
        <tr style="background-color: #e1e1e1;">
            <td style="width: 7%;" class="center">
                <label>ITEM</label>
            </td>
            <td style="width: 7%;" class="center">
                <label>SOCIO</label>
            </td>
            <td style="width: 7%;" class="center">
                <label>CODIGO</label>
            </td>
            <td style="width: 18%;" class="center">
                <label>PRODUCTO</label>
            </td>
            <td style="width: 8%;" class="center">
                <label>STOCK DISPONIBLE</label>
            </td>
            <td style="width: 7%;" class="center">
                <label>PRECIO ULTIMA COMPRA (P. UNITARIO)</label>
            </td>
            <td style="width: 10%;" class="center">
                <label>STOCK EN SOLES (S/.)</label>
            </td>
            <td style="width: 12%;" class="center">
                <label>Fecha Ultimo Movimiento</label>
            </td>
        </tr>
    </thead>
    <tbody>
        <?php
        $sociosAgrupados = [];
        foreach ($dataproductos as $producto) {
            $nombreSocio = $producto->PERSC_Nombre;
            if (!isset($sociosAgrupados[$nombreSocio])) {
                $sociosAgrupados[$nombreSocio] = [
                    'productos' => [],
                    'totalStockSoles' => 0
                ];
            }
            $sociosAgrupados[$nombreSocio]['productos'][] = $producto;
            $sociosAgrupados[$nombreSocio]['totalStockSoles'] += $producto->capital;
        }

        foreach ($sociosAgrupados as $socio => $data) :
            $item = 1; 
        ?>
            <tr style="background-color: #d9ead3; font-weight: bold;">
                <td colspan="8"><?= htmlspecialchars($socio) ?></td>
            </tr>
            <?php foreach ($data['productos'] as $producto) : ?>
                <tr>
                    <td class="center"><?= $item++ ?></td>
                    <td><?= htmlspecialchars($producto->PERSC_Nombre) ?></td>
                    <td><?= htmlspecialchars($producto->codigoproducto) ?></td>
                    <td><?= htmlspecialchars($producto->PROD_Nombre) ?></td>
                    <td><?= htmlspecialchars($producto->stock) ?></td>
                    <td><?= htmlspecialchars($producto->KARDC_PrecioConIgv) ?></td>
                    <td><?= htmlspecialchars($producto->capital) ?></td>
                    <td><?= htmlspecialchars($producto->fecha_ultimo_movimiento) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="background-color: #ffff99; font-weight: bold;">
              <td colspan="7" style="text-align: right;">TOTAL <?= htmlspecialchars($socio) ?></td>
              <td style="text-align: right;"><?= number_format($data['totalStockSoles'], 2) ?></td>
          </tr>

        <?php endforeach; ?>
    </tbody>
</table>



        

 </body>

</html>