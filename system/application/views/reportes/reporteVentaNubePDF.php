<!doctype html>
<html lang="ES">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>REPORTE CONSOLIDADO DE VENTAS</title>
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
    
            <?php
  #************************************************************* REPORTE DE VENTAS POR FORMA DE PAGO DETALLADO *********************************************************************
  // Obtener la fecha seleccionada
  $currentDate = $fecha1;
  $fechaInicio = $currentDate . ' 00:00:00';
  $fechaFin = $currentDate . ' 23:59:59';
   // Inicializa el total general


  // Obtener datos de la sesi¨®n
  $user = $this->session->userdata('user');
  $compania = $this->somevar['compania'];
  $empresa = $this->session->userdata('compania');
  $caja_codigo = $this->session->userdata('caja_codigo');
  ?>

    <table border="0" cellspacing="0" cellspacing="0">
    <tr>
      <td class="bold center">
        <h3 style="margin: 0;">REPORTE DE GANANCIAS DE LOS SOCIOS</h3>
      </td>
    </tr>
  </table>

  <br>
  <br>
  
  <table border="1" cellspacing="0" cellspacing="0" style="width: 50%;">
    <thead>
      <tr style="background-color: #e1e1e1;">
        <th colspan="4">TOTALES POR SOCIO</th>
      </tr>
      <tr>
        <th>Socio</th>
        <th>FORMA PAGO</th>
        <th>TOTAL SOLES</th>
        <th>TOTAL DOLARES</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Inicializamos las variables
      $formasGanciasSocios = [];
      $dineroDevuelto = [];
      $totalesGlobales = ['soles' => 0, 'dolares' => 0];

      foreach ($dataGananciasSocio as $gananciaSocio) {
        $nombreSocio = $gananciaSocio->PERSC_Nombre;
        $descripcion = $gananciaSocio->FORPAC_Descripcion;
        $estado = $gananciaSocio->CPC_FlagEstado;

        // Separar por estado
        if ($estado == 0) { // Dinero devuelto
          if (!isset($dineroDevuelto[$nombreSocio])) {
            $dineroDevuelto[$nombreSocio] = [];
          }
          if (!isset($dineroDevuelto[$nombreSocio][$descripcion])) {
            $dineroDevuelto[$nombreSocio][$descripcion] = ['soles' => 0, 'dolares' => 0];
          }
          if ($gananciaSocio->MONED_Codigo == 1) {
            $dineroDevuelto[$nombreSocio][$descripcion]['soles'] += $gananciaSocio->CPDEC_Total;
          } elseif ($gananciaSocio->MONED_Codigo == 2) {
            $dineroDevuelto[$nombreSocio][$descripcion]['dolares'] += $gananciaSocio->CPDEC_Total;
          }
        } else { // Ganancias
          if (!isset($formasGanciasSocios[$nombreSocio])) {
            $formasGanciasSocios[$nombreSocio] = [];
          }
          if (!isset($formasGanciasSocios[$nombreSocio][$descripcion])) {
            $formasGanciasSocios[$nombreSocio][$descripcion] = ['soles' => 0, 'dolares' => 0];
          }
          if ($gananciaSocio->MONED_Codigo == 1) {
            $formasGanciasSocios[$nombreSocio][$descripcion]['soles'] += $gananciaSocio->CPDEC_Total;
          } elseif ($gananciaSocio->MONED_Codigo == 2) {
            $formasGanciasSocios[$nombreSocio][$descripcion]['dolares'] += $gananciaSocio->CPDEC_Total;
          }
        }
      }

      // Mostrar los datos de las ganancias por socio
      foreach ($formasGanciasSocios as $nombreSocio => $descripciones) {
        $totalSolesSocio = 0;
        $totalDolaresSocio = 0;

        foreach ($descripciones as $descripcion => $ganancias) {
          $totalSolesSocio += $ganancias['soles'];
          $totalDolaresSocio += $ganancias['dolares'];
          $totalesGlobales['soles'] += $ganancias['soles'];
          $totalesGlobales['dolares'] += $ganancias['dolares'];

          echo "<tr>
                <td>{$nombreSocio}</td>
                <td>{$descripcion}</td>
                <td class='center'>" . number_format($ganancias['soles'], 2) . "</td>
                <td class='center'>" . number_format($ganancias['dolares'], 2) . "</td>
              </tr>";
        }

        // Subtotales por socio
        echo "<tr style='font-weight: bold;'>
            <td colspan='2'>Total {$nombreSocio}</td>
            <td class='center'>" . number_format($totalSolesSocio, 2) . "</td>
            <td class='center'>" . number_format($totalDolaresSocio, 2) . "</td>
          </tr>";
      }
      ?>
      <!-- Totales globales -->
      <tr style="font-weight: bold; background-color: #e1e1e1;">
        <td colspan="2">TOTALES</td>
        <td class="center"><?= number_format($totalesGlobales['soles'], 2) ?></td>
        <td class="center"><?= number_format($totalesGlobales['dolares'], 2) ?></td>
      </tr>
    </tbody>
  </table>

  <br><br>

  <table border="1" cellspacing="0" cellspacing="0" style="width: 50%;">
    <thead>
      <tr style="background-color: #e1e1e1;">
        <th colspan="3">TOTALES</th>
      </tr>
      <tr>
        <th>MEDIO DE PAGO</th>
        <th>SOLES</th>
        <th>DOLARES</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Inicializar totales
      $totalSoles = 0;
      $totalDolares = 0;
      $mediosDePago = []; // Arreglo para almacenar los totales por medio de pago

    
      foreach ($data as $row) { 
        $medioPago = $row->FORPAC_Descripcion; 
        $monto = $row->CPC_total;             
        $moneda = $row->MONED_Codigo;       


        if (!isset($mediosDePago[$medioPago])) {
          $mediosDePago[$medioPago] = ['soles' => 0, 'dolares' => 0];
        }


        if ($moneda == 1) {
          $mediosDePago[$medioPago]['soles'] += $monto;
          $totalSoles += $monto;
        } elseif ($moneda == 2) {
          $mediosDePago[$medioPago]['dolares'] += $monto;
          $totalDolares += $monto;
        }
      }

      // Generar filas de la tabla
      foreach ($mediosDePago as $medio => $totales) {
        echo "<tr>";
        echo "<td>{$medio}</td>";
        echo "<td>" . number_format($totales['soles'], 2) . "</td>";
        echo "<td>" . number_format($totales['dolares'], 2) . "</td>";
        echo "</tr>";
      }
      ?>
    </tbody>
    <tfoot>
      <tr>
        <td><strong>TOTALES</strong></td>
        <td><strong><?php echo number_format($totalSoles, 2); ?></strong></td>
        <td><strong><?php echo number_format($totalDolares, 2); ?></strong></td>
      </tr>
    </tfoot>
  </table>

  <br><br>
  
        <table border="1" cellspacing="0" cellpadding="0" style="width: 50%;">
    <thead>
      <tr style="background-color: #e1e1e1;">
        <th colspan="4">DINERO DEVUELTO POR ANULACION DE VENTA</th>
      </tr>
      <tr>
        <th>Socio</th>
        <th>Forma Pago</th>
        <th>Total Soles</th>
        <th>Total Dolares</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($dineroDevuelto as $nombreSocio => $descripciones) { ?>
        <?php foreach ($descripciones as $descripcion => $ganancias) { ?>
          <tr>
            <td><?= $nombreSocio ?></td>
            <td><?= $descripcion ?></td>
            <td class="center"><?= number_format($ganancias['soles'], 2) ?></td>
            <td class="center"><?= number_format($ganancias['dolares'], 2) ?></td>
          </tr>
        <?php } ?>
      <?php } ?>
    </tbody>
  </table>

  <br><br>
  
  
  
  
<h2>Ventas Multiples</h2>
  <table border="1" cellpadding="4" cellspacing="0">
    <thead>
      <tr style="background-color: #f2f2f2;">
        <th width="20%" align="center"><strong>N Comprobante</strong></th>
        <th width="20%" align="center"><strong>N Orden</strong></th>
        <th width="20%" align="center"><strong>Monto (S/)</strong></th>
        <th width="20%" align="center"><strong>Metodo Pago</strong></th>
        <th width="20%" align="center"><strong>Estado</strong></th>
      </tr>
    </thead>
    <tbody>
      <?php

      $dailySalesReceipt = $db->ventas_model->ventasDiarioCFER($fechaInicio, $fechaFin);

      $pruebas = [];
      if (is_array($dailySalesReceipt) || is_object($dailySalesReceipt)) {
        foreach ($dailySalesReceipt as $receipt) {
          $pruebas[] = $receipt->CPP_Codigo;
        }
      }
      $othersFormasP = $db->comprobante_formapago_model->getListcajacierre($pruebas);
      if (count($othersFormasP) > 0) {
        $total_general = 0;
        foreach ($othersFormasP as $others) {
          // Aseg¨²rate de que $totalItems est¨¦ correctamente definido
          $descripcion = utf8_encode($totalItems->descripcion);
          $monto_total = number_format($totalItems->monto_total, 2);
          $total_general += $totalItems->monto_total;
      ?>
          <tr>
            <td align="center"><?php echo strtoupper($others->CPC_Serie . "-" . $others->CPC_Numero); ?></td>
            <td align="center"><?php echo strtoupper($others->idcji_compro_forPa); ?></td>
            <td align="center"><?php echo $others->MONED_Simbolo . number_format($others->monto, 2); ?></td>
            <td align="center"><?php echo strtoupper($others->FORPAC_Descripcion); ?></td>
            <td align="center" style="color: blue;"><strong>Adicional</strong></td>
          </tr>
      <?php
        }
      }
      ?>
    </tbody>
  </table>
  <br><br>

  <h2>Total Ventas Multiples</h2>
  <table border="1" cellpadding="4" cellspacing="0">
    <thead>
      <tr style="background-color: #f2f2f2;">
        <th width="50%" align="center"><strong>Descripcion</strong></th>
        <th width="50%" align="center"><strong>Total (S/)</strong></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $totalesinmulti = $this->comprobante_formapago_model->ventasDiarioCTOTALUNI($fechaInicio, $fechaFin, $compania, $caja_codigo);
      // Verifica si es un array y extrae los CPP_Codigo
      $totaluni = [];
      if (is_array($totalesinmulti) || is_object($totalesinmulti)) {
        foreach ($totalesinmulti as $receiptsinmulti) {
          $totaluni[] = $receiptsinmulti->CPP_Codigo; // Extrae CPP_Codigo y lo agrega al array
        }
      }
      //obtener informacion de total de ventas multiples.
      $totalformamulti = $this->comprobante_formapago_model->getListcajacierretablatotal($totaluni);
      $total_general = 0;
      // Filas para los m¨¦todos de pago adicionales
      foreach ($totalformamulti as $totalItems) {
        $descripcion = utf8_encode($totalItems->descripcion); // Usa la descripci¨®n desde el modelo
        $monto_total = number_format($totalItems->monto_total, 2) . " S/"; // Usa el monto total desde el modelo
        $total_general += $totalItems->monto_total; // Acumular el total
      ?>
        <tr>
          <td align="center"><?= $descripcion ?></td>
          <td align="center"><?= $monto_total ?></td>
        </tr>
      <?php
      }
      ?>
    </tbody>
  </table>
  <br><br>
  
  
  <table border="0" cellspacing="0" cellspacing="0">
    <tr>
      <td class="bold center">
        <h3 style="margin: 0;">REPORTE DE DE VENTAS DEL DIA</h3>
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
  
  <table border="1" cellspacing="0" cellspacing="0" id="tbTableMain">
    <thead>
      <tr>
        <td class="bold center" colspan="7">COMPROBANTES</td>
      </tr>
      <tr style="background-color: #e1e1e1;">
        <td style="width: 7%;" class="center">
          <label>TIPO</label>
        </td>
        <td style="width: 7%;" class="center">
          <label>SERIE</label>
        </td>
        <td style="width: 8%;" class="center">
          <label>NUM DOC</label>
        </td>
        <td style="width: 18%;" class="center">
          <label>DENOMINACION</label>
        </td>
        <td style="width: 7%;" class="center">
          <label>MONEDA</label>
        </td>
        <td style="width: 8%;" class="center">
          <label>TOTAL</label>
        </td>
        <td style="width: 12%;" class="center">
          <label>FORMA PAGO</label>
        </td>
      </tr>
    </thead>
    <tbody style="font-size:7pt !important">
      <?php
      $totalCPPSol = 0;
      $totalCPPDol = 0;
      foreach ($data as $key => $row) {
        switch ($row->CPC_TipoDocumento) {
          case 'F':
            $typeName = 'FACTURA';
            break;
          case 'B':
            $typeName = 'BOLETA';
            break;
          default:
            $typeName = 'COMPROBANTE';
            break;
        }
      ?>
        <tr class="center">
          <td style="border-bottom: none;border-top: none;"><?php echo $typeName ?></td>
          <td style="border-bottom: none;border-top: none;"><?php echo $row->CPC_Serie . '-' . $row->CPC_Numero ?></td>
          <td style="border-bottom: none;border-top: none;"><?php echo $row->numero_documento_cliente ?></td>
          <td style="border-bottom: none;border-top: none;"><?php echo $row->razon_social_cliente ?></td>
          <td style="border-bottom: none;border-top: none;">
            <?php echo '(' . $row->MONED_Simbolo . ') ' . $row->MONED_Descripcion ?>
          </td>
          <td style="border-bottom: none;border-top: none;">
            <?php
            echo $row->CPC_total;
            $totalCPPSol += ($row->MONED_Codigo == 1) ? $row->CPC_total : 0;
            $totalCPPDol += ($row->MONED_Codigo == 2) ? $row->CPC_total : 0;
            ?>
          </td>
          <td style="border-bottom: none;border-top: none;"><?php echo $row->FORPAC_Descripcion ?></td>
        </tr>
      <?php } ?>
    </tbody>
    <tfoot>
      <tr class="bold">
        <td colspan="5"></td>
        <td colspan="2">
          <label>TOTAL (S/.): </label> <?php echo $totalCPPSol ?>
        </td>
      </tr>
      <tr class="bold">
        <td colspan="5"></td>
        <td colspan="2">
          <label>TOTAL (US$): </label> <?php echo $totalCPPDol ?>
        </td>
      </tr>
    </tfoot>
  </table>
  
  <?php
  $formas = [];

  foreach ($data as $key1 => $cpp) {
    $rowFP = $db->Comprobante_formapago_model->getList($cpp->CODCPC, 0, true);

    foreach ($rowFP as $key2 => $rowOneFP) {

      if ($rowOneFP->FORPAP_Codigo == "22") {
        continue; 
      }
      $indice = array_search($rowOneFP->FORPAP_Codigo, array_column($formas, 'Codigo'));
      $montoS = ($rowOneFP->MONED_Codigo == 1) ? $rowOneFP->monto : 0;
      $montoD = ($rowOneFP->MONED_Codigo == 2) ? $rowOneFP->monto : 0;

      if ($indice > 0) {
        $formas[$indice]["MontoS"] += $montoS;
        $formas[$indice]["MontoD"] += $montoD;
      } else {
        $datas = ["Codigo" => $rowOneFP->FORPAP_Codigo, "Nombre" => $rowOneFP->FORPAC_Descripcion, "MontoS" => $montoS, "MontoD" => $montoD];
        array_push($formas, $datas);
      }
    }
  }
  ?>

 <br><br>



  









</body>

</html>