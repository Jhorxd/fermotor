<?php
class Comprobante_formapago_model extends Model 
{
    public function __construct() 
    {
        parent::__construct();
        $this->load->database();
    }

    public function getList($idComprobante = 0, $typeFP = 0, $withMain = false) 
    {
        $rows = $this->db->select("comfp.*, fp.FORPAC_Descripcion, mon.MONED_Simbolo")
            ->from('cji_comprobante_formaspago comfp')
            ->join('cji_formapago fp', 'fp.FORPAP_Codigo = comfp.FORPAP_Codigo AND fp.FORPAC_FlagEstado=1' ,'LEFT')
            ->join('cji_moneda mon', 'mon.MONED_Codigo = comfp.MONED_Codigo AND mon.MONED_FlagEstado=1' ,'LEFT')
            ->where([
                'comfp.CPP_Codigo' => $idComprobante,
                'comfp.compro_forPa_flag' => 1
            ]);

        if (!empty($typeFP))
            $rows->where('fp.FORPAP_Codigo', $typeFP);

        if ($withMain)
        {
            $query1 = $rows->get()->result();
            $rowMain = $this->db->select('c.CPP_Codigo, c.MONED_Codigo, c.FORPAP_Codigo, 
                    c.FORPAP_Monto AS monto,
                    c.CPC_total, 
                    fp1.FORPAC_Descripcion, 
                    mon1.MONED_Simbolo')
                    ->from('cji_comprobante c')
                    ->join('cji_formapago fp1', 'fp1.FORPAP_Codigo = c.FORPAP_Codigo AND fp1.FORPAC_FlagEstado=1' ,'LEFT')
                    ->join('cji_moneda mon1', 'mon1.MONED_Codigo = c.MONED_Codigo AND mon1.MONED_FlagEstado=1' ,'LEFT')
                    ->where([
                        'c.CPP_Codigo' => $idComprobante
                    ]);
            if (!empty($typeFP))
                $rowMain->where('fp1.FORPAP_Codigo', $typeFP);

            $query2 = $rowMain->get()->result();
            $arrayMerge = array_merge($query2, $query1);
            return $arrayMerge;
        }

        return $rows->get()->result();
    }

    public function insertar($filter) 
    {
        $this->db->insert("cji_comprobante_formaspago", (array) $filter);
        return $this->db->insert_id();
    }

    public function deleteforComprobante($idComprobante) 
    {
        $this->db->delete("cji_comprobante_formaspago", ['CPP_Codigo' => $idComprobante]);
    }


    public function ventasDiarioCTOTALUNI($fechaInicio, $fechaFin, $compania,$caja_codigo) {
        $this->db->select('c.CPC_Serie, c.CPC_Numero, c.CPP_Codigo,c.CPC_total, f.FORPAC_Descripcion, CASE (c.CPC_FlagEstado) when 2 then "Denegado" when 0 then "Anulado" ELSE "Aprobado" END as Estado', FALSE);
        $this->db->from('cji_comprobante as c');
        $this->db->join('cji_formapago as f', 'c.FORPAP_Codigo = f.FORPAP_Codigo');
        $this->db->where('c.COMPP_Codigo', $compania);
       // $this->db->where('c.CPC_FechaRegistro >=', $fechaInicio);
       // $this->db->where('c.CPC_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
        $this->db->where('c.CPC_FlagEstado !=', 2);
        $this->db->where('c.CPC_FlagEstado !=', 0);

        //SE AGREGO HOY MARTES
        $this->db->where('c.CPC_TipoOperacion !=', 'C');

        $this->db->where('c.CAJA_Codigo', $caja_codigo);

                // Filtra usando CPC_FechaModificacion si no está vacío, o CPC_FechaRegistro si lo está
                $this->db->where("
                CASE 
                    WHEN c.CPC_FechaModificacion IS NOT NULL THEN (c.CPC_FechaModificacion >= '$fechaInicio' AND c.CPC_FechaModificacion < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
                    ELSE (c.CPC_FechaRegistro >= '$fechaInicio' AND c.CPC_FechaRegistro < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
                END
            ", NULL, FALSE);
        
      
        $response = $this->db->get();
        return $response->result();
    }

    public function getListcajacierretablatotal($idComprobantes = [], $typeFP = 0) 
    {
        // Comprobamos que $idComprobantes sea un array y no esté vacío
        if (empty($idComprobantes) || !is_array($idComprobantes)) {
            return []; // Retorna un array vacío si no se pasan IDs válidos
        }
    
        // Consulta para obtener la descripción de la forma de pago y el monto total
        $rows = $this->db->select("fp.FORPAC_Descripcion AS descripcion, SUM(comfp.monto) AS monto_total")
            ->from('cji_comprobante_formaspago comfp')
            ->join('cji_formapago fp', 'fp.FORPAP_Codigo = comfp.FORPAP_Codigo AND fp.FORPAC_FlagEstado=1', 'LEFT')
            ->where_in('comfp.CPP_Codigo', $idComprobantes) // Usar where_in para múltiples IDs
            ->where('comfp.compro_forPa_flag', 1)
            ->group_by('fp.FORPAP_Codigo'); // Agrupar por el código de la forma de pago
    
        if (!empty($typeFP)) {
            $rows->where('fp.FORPAP_Codigo', $typeFP);
        }
    
        return $rows->get()->result();
    }

    public function ventasDiarioN($fechaInicio, $fechaFin, $compania, $numero_combinado) {
        // Retorna un array vacío si no se pasa un número válido
       if (empty($numero_combinado)) {
         return [];
     }
    
       $this->db->select('n.CRED_Serie, n.CRED_Numero, n.CRED_total, f.FORPAC_Descripcion, CASE n.CRED_FlagEstado when 2 then "Denegado" ELSE "Aprobado" END as Estado', FALSE);
       $this->db->from('cji_nota as n');
       $this->db->join('cji_formapago as f', 'n.CRED_FormaPago = f.FORPAP_Codigo');
       $this->db->where('n.COMPP_Codigo', $compania);
       //$this->db->where('n.CRED_FechaRegistro >=', $fechaInicio);
       //$this->db->where('n.CRED_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
       $this->db->where('n.CRED_FlagEstado !=', 2);
       $this->db->where_in('n.CRED_NumeroInicio', $numero_combinado);

       // Excluir documentos tipo F
     
       $response = $this->db->get();
     
       return $response->result();
     }

    public function getListcajacierre($idComprobantes = [], $typeFP = 0) 
    {
        // Comprobamos que $idComprobantes sea un array y no esté vacío
        if (empty($idComprobantes) || !is_array($idComprobantes)) {
            return []; // Retorna un array vacío si no se pasan IDs válidos
        }
    
        // Consulta para las tablas de comprobantes de forma de pago y comprobantes
        $rows = $this->db->select("comfp.*, comfp.idcji_compro_forPa, fp.FORPAC_Descripcion, mon.MONED_Simbolo, c.CPC_Serie, c.CPC_Numero")
            ->from('cji_comprobante_formaspago comfp')
            ->join('cji_comprobante c', 'c.CPP_Codigo = comfp.CPP_Codigo', 'LEFT')
            ->join('cji_formapago fp', 'fp.FORPAP_Codigo = comfp.FORPAP_Codigo AND fp.FORPAC_FlagEstado=1', 'LEFT')
            ->join('cji_moneda mon', 'mon.MONED_Codigo = comfp.MONED_Codigo AND mon.MONED_FlagEstado=1', 'LEFT')
            ->where_in('comfp.CPP_Codigo', $idComprobantes) // Usar where_in para múltiples IDs
            ->where('comfp.compro_forPa_flag', 1);
    
        if (!empty($typeFP)) {
            $rows->where('fp.FORPAP_Codigo', $typeFP);
        }
    
        return $rows->get()->result();
    }

    public function obtenerTotalesingresoefectivo($idComprobantes = [], $fechaInicio, $fechaFin, $compania, $caja_codigo) 
    {
        // Obtener resultados de la primera consulta
        $totalesFormasPago = $this->getListcajacierretablatotalefectivo($idComprobantes);
        
        // Obtener resultados de la segunda consulta
        $totalesVentas = $this->ventasTotalefectivo($fechaInicio, $fechaFin, $compania, $caja_codigo);
    

        //ALVARO 12/11/2024
        //$totalesRestar = $this->ventasrestar($fechaInicio, $fechaFin, $compania, $caja_codigo);
        ///alvaro hoy 2:41 pm
        $listacajamoviincialingre = $this->listacajamoviincialefectivo($fechaInicio, $fechaFin,$caja_codigo);
        /////

        //ALVARO AGREGADO HOY MIERCOLES 4:33
        //$listacajamoviegreso = $this->listacajamoviegreso($fechaInicio, $fechaFin,$caja_codigo);

        ///


        // Inicializar arreglo para almacenar los totales combinados
        $totalesCombinados = [];
    
        // Agregar totales de la primera consulta
        foreach ($totalesFormasPago as $item) {
            $descripcion = $item->descripcion;
            $totalesCombinados[$descripcion] = isset($totalesCombinados[$descripcion]) 
                ? $totalesCombinados[$descripcion] + $item->monto_total 
                : $item->monto_total;
        }
    
        // Agregar totales de la segunda consulta y combinar si ya existe la descripción
        foreach ($totalesVentas as $item) {
            $descripcion = $item->FORPAC_Descripcion;
            if ($descripcion =='MULTIPLE') {
                continue;
            }
            $totalesCombinados[$descripcion] = isset($totalesCombinados[$descripcion]) 
                ? $totalesCombinados[$descripcion] + $item->Total 
                : $item->Total;
        }


            //ALVARO HOY 3:44PM
            foreach ($listacajamoviincialingre as $item) {
                $descripcion = $item->FORPAC_Descripcion;
                $montoSumar = $item->Total;
            
                // Crear una fila con el formato "(EGRESO)Descripción" para mostrar la resta
                $totalesCombinados["(Caja Inicial)$descripcion"] = isset($totalesCombinados["(Caja Inicial)$descripcion"]) 
                    ? $totalesCombinados["(Caja Inicial)$descripcion"] + (+$montoSumar) 
                    : (+$montoSumar);
            }

        // Convertir el resultado a un formato adecuado para devolver
        $resultadoFinal = [];
        foreach ($totalesCombinados as $descripcion => $total) {
            $resultadoFinal[] = (object) [
                'descripcion' => $descripcion,
                'monto_total' => $total,
            ];
        }
    
        return $resultadoFinal;
    }

    public function getListcajacierretablatotalefectivo($idComprobantes = [], $typeFP = 0) 
    {
        // Comprobamos que $idComprobantes sea un array y no esté vacío
        if (empty($idComprobantes) || !is_array($idComprobantes)) {
            return []; // Retorna un array vacío si no se pasan IDs válidos
        }
    
        // Consulta para obtener la descripción de la forma de pago y el monto total
        $rows = $this->db->select("fp.FORPAC_Descripcion AS descripcion, SUM(comfp.monto) AS monto_total")
            ->from('cji_comprobante_formaspago comfp')
            ->join('cji_formapago fp', 'fp.FORPAP_Codigo = comfp.FORPAP_Codigo AND fp.FORPAC_FlagEstado=1', 'LEFT')
            ->where_in('comfp.CPP_Codigo', $idComprobantes) // Usar where_in para múltiples IDs
            ->where('comfp.compro_forPa_flag', 1)
            -> where ('comfp.FORPAP_Codigo',1)
            ->group_by('fp.FORPAP_Codigo'); // Agrupar por el código de la forma de pago
    
        if (!empty($typeFP)) {
            $rows->where('fp.FORPAP_Codigo', $typeFP);
        }
    
        return $rows->get()->result();
    }

    public function ventasTotalefectivo($fechaInicio, $fechaFin, $compania,$caja_codigo) {
        $this->db->select('f.FORPAC_Descripcion, SUM(c.CPC_total) as Total');
        $this->db->from('cji_comprobante as c');
        $this->db->join('cji_formapago as f', 'c.FORPAP_Codigo = f.FORPAP_Codigo', 'INNER');
        $this->db->join('(SELECT CRED_FormaPago, SUM(CRED_total) as nota_total FROM cji_nota WHERE COMPP_Codigo = '.$compania.' AND CRED_Fecha >= "'.$fechaInicio.'" AND CRED_Fecha <= "'.$fechaFin.'" GROUP BY CRED_FormaPago) n', 'c.FORPAP_Codigo = n.CRED_FormaPago', 'LEFT');
        $this->db->where('c.COMPP_Codigo', $compania);
        //$this->db->where('c.CPC_FechaRegistro >=', $fechaInicio);
        //$this->db->where('c.CPC_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
        $this->db->where('c.CPC_FlagEstado !=', 2);
        $this->db->where('c.CPC_FlagEstado !=', 0);

        //Se agrego hoy martes
        $this->db->where('c.CPC_TipoOperacion !=', 'C');
        $this->db->where('c.CAJA_Codigo', $caja_codigo);
        $this->db->where('c.FORPAP_Codigo', 1);
        $this->db->group_by('f.FORPAC_Descripcion');
      
        $this->db->where("
        CASE 
            WHEN c.CPC_FechaModificacion IS NOT NULL THEN (c.CPC_FechaModificacion >= '$fechaInicio' AND c.CPC_FechaModificacion < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
            ELSE (c.CPC_FechaRegistro >= '$fechaInicio' AND c.CPC_FechaRegistro < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
        END
        ", NULL, FALSE);
        $response = $this->db->get();
      
        return $response->result();
    }

    public function listacajamoviincialefectivo($fechaInicio, $fechaFin,$caja_codigo){
        // Seleccionar todas las columnas de la tabla
        $this->db->select('f.FORPAC_Descripcion ,SUM(cm.CAJAMOV_Monto)as Total');
        $this->db->from('cji_cajamovimiento as cm');
        $this->db->join('cji_formapago as f', 'cm.FORPAP_Codigo = f.FORPAP_Codigo', 'INNER');
        $this->db->where('cm.CAJA_Codigo', $caja_codigo);
        $this->db->where('cm.CAJAMOV_FechaRegistro >=', $fechaInicio);
        $this->db->where('cm.CAJAMOV_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
        $this->db->where('cm.CAJAMOV_FlagEstado', 1);
        $this->db->where('cm.CAJAMOV_MovDinero', 1);
        $this->db->where('cm.CAJAMOV_CodigoUsuario', 0);
        $this->db->where('cm.FORPAP_Codigo', 1);
        $this->db->group_by('f.FORPAC_Descripcion');
    
        // Ejecutar la consulta
        $query = $this->db->get();
        
        // Retornar todos los resultados como un array de objetos
        return $query->result();
    }

    public function obtenerTotalesVentasUnificados($idComprobantes = [], $fechaInicio, $fechaFin, $compania, $caja_codigo) 
    {
        // Obtener resultados de la primera consulta
        $totalesFormasPago = $this->getListcajacierretablatotal($idComprobantes);
        
        // Obtener resultados de la segunda consulta
        $totalesVentas = $this->ventasTotal($fechaInicio, $fechaFin, $compania, $caja_codigo);
    

        //ALVARO 12/11/2024
        $totalesRestar = $this->ventasrestar($fechaInicio, $fechaFin, $compania, $caja_codigo);
        ///alvaro hoy 2:41 pm
        $listacajamoviincialingre = $this->listacajamoviincial($fechaInicio, $fechaFin,$caja_codigo);
        /////

        //ALVARO AGREGADO HOY MIERCOLES 4:33
        $listacajamoviegreso = $this->listacajamoviegreso($fechaInicio, $fechaFin,$caja_codigo);

        ///


        // Inicializar arreglo para almacenar los totales combinados
        $totalesCombinados = [];
    
        // Agregar totales de la primera consulta
        foreach ($totalesFormasPago as $item) {
            $descripcion = $item->descripcion;
            $totalesCombinados[$descripcion] = isset($totalesCombinados[$descripcion]) 
                ? $totalesCombinados[$descripcion] + $item->monto_total 
                : $item->monto_total;
        }
    
        // Agregar totales de la segunda consulta y combinar si ya existe la descripción
        foreach ($totalesVentas as $item) {
            $descripcion = $item->FORPAC_Descripcion;
            if ($descripcion =='MULTIPLE') {
                continue;
            }
            $totalesCombinados[$descripcion] = isset($totalesCombinados[$descripcion]) 
                ? $totalesCombinados[$descripcion] + $item->Total 
                : $item->Total;
        }

            //ALVARO HOY 3:44PM
            foreach ($listacajamoviincialingre as $item) {
                $descripcion = $item->FORPAC_Descripcion;
                $montoSumar = $item->Total;
            
                // Crear una fila con el formato "(EGRESO)Descripción" para mostrar la resta
                $totalesCombinados["(Caja Inicial)$descripcion"] = isset($totalesCombinados["(Caja Inicial)$descripcion"]) 
                    ? $totalesCombinados["(Caja Inicial)$descripcion"] + (+$montoSumar) 
                    : (+$montoSumar);
            }

             //ALVARO AGREGADO HOY MIERCOLES 4:33
             foreach ($listacajamoviegreso as $item) {
                $descripcion = $item->FORPAC_Descripcion;
                $montorestaregre = $item->Total;
            
                // Crear una fila con el formato "(EGRESO)Descripción" para mostrar la resta
                $totalesCombinados["(Caja Egreso)$descripcion"] = isset($totalesCombinados["(Caja Egreso)$descripcion"]) 
                    ? $totalesCombinados["(Caja Egreso)$descripcion"] + (-$montorestaregre) 
                    : (-$montorestaregre);
            }

            
            


    
        // Convertir el resultado a un formato adecuado para devolver
        $resultadoFinal = [];
        foreach ($totalesCombinados as $descripcion => $total) {
            $resultadoFinal[] = (object) [
                'descripcion' => $descripcion,
                'monto_total' => $total,
            ];
        }
    
        return $resultadoFinal;
    }

    public function ventasTotal($fechaInicio, $fechaFin, $compania,$caja_codigo) {
        $this->db->select('f.FORPAC_Descripcion, SUM(c.CPC_total) as Total');
        $this->db->from('cji_comprobante as c');
        $this->db->join('cji_formapago as f', 'c.FORPAP_Codigo = f.FORPAP_Codigo', 'INNER');
        $this->db->join('(SELECT CRED_FormaPago, SUM(CRED_total) as nota_total FROM cji_nota WHERE COMPP_Codigo = '.$compania.' AND CRED_Fecha >= "'.$fechaInicio.'" AND CRED_Fecha <= "'.$fechaFin.'" GROUP BY CRED_FormaPago) n', 'c.FORPAP_Codigo = n.CRED_FormaPago', 'LEFT');
        $this->db->where('c.COMPP_Codigo', $compania);
        //$this->db->where('c.CPC_FechaRegistro >=', $fechaInicio);
        //$this->db->where('c.CPC_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
        $this->db->where('c.CPC_FlagEstado !=', 2);
        $this->db->where('c.CPC_FlagEstado !=', 0);

        //Se agrego hoy martes
        $this->db->where('c.CPC_TipoOperacion !=', 'C');
        $this->db->where('c.CAJA_Codigo', $caja_codigo);
      
        $this->db->group_by('f.FORPAC_Descripcion');
      
        $this->db->where("
        CASE 
            WHEN c.CPC_FechaModificacion IS NOT NULL THEN (c.CPC_FechaModificacion >= '$fechaInicio' AND c.CPC_FechaModificacion < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
            ELSE (c.CPC_FechaRegistro >= '$fechaInicio' AND c.CPC_FechaRegistro < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
        END
        ", NULL, FALSE);
        $response = $this->db->get();
      
        return $response->result();
    }

    
    public function ventasrestar($fechaInicio, $fechaFin, $compania,$caja_codigo) {
        $this->db->select('f.FORPAC_Descripcion, SUM(c.CPC_total) as Total');
        $this->db->from('cji_comprobante as c');
        $this->db->join('cji_formapago as f', 'c.FORPAP_Codigo = f.FORPAP_Codigo', 'INNER');
        $this->db->join('(SELECT CRED_FormaPago, SUM(CRED_total) as nota_total FROM cji_nota WHERE COMPP_Codigo = '.$compania.' AND CRED_Fecha >= "'.$fechaInicio.'" AND CRED_Fecha <= "'.$fechaFin.'" GROUP BY CRED_FormaPago) n', 'c.FORPAP_Codigo = n.CRED_FormaPago', 'LEFT');
        $this->db->where('c.COMPP_Codigo', $compania);
        //$this->db->where('c.CPC_FechaRegistro >=', $fechaInicio);
        //$this->db->where('c.CPC_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
        $this->db->where('c.CPC_FlagEstado !=', 2);
        $this->db->where('c.CPC_FlagEstado !=', 0);

        //Se agrego hoy martes
        $this->db->where('c.CPC_TipoOperacion !=', 'V');
        $this->db->where('c.CAJA_Codigo', $caja_codigo);
      
        $this->db->group_by('f.FORPAC_Descripcion');
      
        $this->db->where("
        CASE 
            WHEN c.CPC_FechaModificacion IS NOT NULL THEN (c.CPC_FechaModificacion >= '$fechaInicio' AND c.CPC_FechaModificacion < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
            ELSE (c.CPC_FechaRegistro >= '$fechaInicio' AND c.CPC_FechaRegistro < '" . date('Y-m-d H:i:s', strtotime($fechaFin . ' + 1 minute')) . "')
        END
        ", NULL, FALSE);
        $response = $this->db->get();
      
        return $response->result();
    }

    public function listacajamoviincial($fechaInicio, $fechaFin,$caja_codigo){
        // Seleccionar todas las columnas de la tabla
        $this->db->select('f.FORPAC_Descripcion ,SUM(cm.CAJAMOV_Monto)as Total');
        $this->db->from('cji_cajamovimiento as cm');
        $this->db->join('cji_formapago as f', 'cm.FORPAP_Codigo = f.FORPAP_Codigo', 'INNER');
        $this->db->where('cm.CAJA_Codigo', $caja_codigo);
        $this->db->where('cm.CAJAMOV_FechaRegistro >=', $fechaInicio);
        $this->db->where('cm.CAJAMOV_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
        $this->db->where('cm.CAJAMOV_FlagEstado', 1);
        $this->db->where('cm.CAJAMOV_MovDinero', 1);
        $this->db->where('cm.CAJAMOV_CodigoUsuario', 0);
    
        $this->db->group_by('f.FORPAC_Descripcion');
    
        // Ejecutar la consulta
        $query = $this->db->get();
        
        // Retornar todos los resultados como un array de objetos
        return $query->result();
        }

        public function listacajamoviegreso($fechaInicio, $fechaFin,$caja_codigo){
            // Seleccionar todas las columnas de la tabla
            $this->db->select('f.FORPAC_Descripcion ,SUM(cm.CAJAMOV_Monto)as Total');
            $this->db->from('cji_cajamovimiento as cm');
            $this->db->join('cji_formapago as f', 'cm.FORPAP_Codigo = f.FORPAP_Codigo', 'INNER');
            $this->db->where('cm.CAJA_Codigo', $caja_codigo);
            $this->db->where('cm.CAJAMOV_FechaRegistro >=', $fechaInicio);
            $this->db->where('cm.CAJAMOV_FechaRegistro <', date('Y-m-d H:i:s', strtotime($fechaFin. ' + 1 minute')));
            $this->db->where('cm.CAJAMOV_FlagEstado', 1);
            $this->db->where('cm.CAJAMOV_MovDinero', 2);
            $this->db->where('cm.CAJAMOV_CodigoUsuario', 0);
        
            $this->db->group_by('f.FORPAC_Descripcion');
        
            // Ejecutar la consulta
            $query = $this->db->get();
            
            // Retornar todos los resultados como un array de objetos
            return $query->result();
    }
}