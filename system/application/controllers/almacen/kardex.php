<?php

class Kardex extends controller
{

  public function __construct(){
    parent::Controller();
    $this->load->model('almacen/kardex_model');
    $this->load->model('almacen/producto_model');
    $this->load->model('almacen/almacenproducto_model');
    $this->load->model('almacen/almacen_model');
    $this->load->model('almacen/guiain_model');
    $this->load->model('almacen/guiasa_model');
    $this->load->model('almacen/unidadmedida_model');
    $this->load->model('maestros/compania_model');
    $this->load->model('compras/proveedor_model');
    $this->load->model('ventas/cliente_model');
    $this->load->model('seguridad/usuario_model');
    $this->load->helper('form', 'url');
    $this->load->library('pagination');
    $this->load->library('form_validation');
    $this->somevar['compania'] = $this->session->userdata('compania');
  }

  public function listar(){
    unset($_SESSION['serieReal']);
    unset($_SESSION['serieRealBD']);
    $this->load->library('layout', 'layout');
    $data['compania'] =$this->somevar['compania'];
    $data['titulo_tabla'] = "KARDEX DE PRODUCTOS";
    $data['form_open'] = form_open($url, array("name" => "frmkardex", "id" => "frmkardex"));
    $data['cboAlmacen'] = form_dropdown("almacen", $this->almacen_model->seleccionar($this->somevar['compania']), $almacen_id, " class='form-control w-porc-90' id='almacen'"); // EN 
    $atributos = array('width' => 700, 'height' => 450, 'scrollbars' => 'yes', 'status' => 'yes', 'resizable' => 'yes', 'screenx' => '0', 'screeny' => '0');
    $data['oculto'] = form_hidden(array('base_url' => base_url()));
    $data['form_close'] = form_close();
    $this->layout->view('almacen/kardex_index', $data);
  }

  function obtener_nombre_numdoc($tipo, $codigo){

    $nombre = '';
    $numdoc = '';
    if ($tipo == 'CLIENTE') {
      $datos_cliente = $this->cliente_model->obtener($codigo);
      if ($datos_cliente) {
        $nombre = $datos_cliente->nombre;
        $numdoc = $datos_cliente->ruc;
      }
    } else {
      $datos_proveedor = $this->proveedor_model->obtener($codigo);
      if ($datos_proveedor) {
        $nombre = $datos_proveedor->nombre;
        $numdoc = $datos_proveedor->ruc;
      }
    }
    return array('numdoc' => $numdoc, 'nombre' => $nombre);
  }

  function VerificarMovimiento($codKardex){
    $filter = new stdClass();
    $filter->KARDC_FlagValida = '1';
    $filter->USUA_Codigo = $_SESSION['user'];
    $update = $this->kardex_model->verificarMovimiento($codKardex, $filter);

    if($update == true){
      $usuario = $this->usuario_model->obtener2($_SESSION['user']);
      $detaUsuario = $usuario[0]->PERSC_Nombre." ".$usuario[0]->PERSC_ApellidoPaterno." ".$usuario[0]->PERSC_ApellidoMaterno;
      echo  $detaUsuario;
    }else{
      echo 'error';
    }
  }

  public function datatable_kardex($value=''){

    $filter = new stdClass();
    $filter->producto     = $this->input->post('producto');
    $filter->almacen      = $this->input->post('almacen');
    $filter->descripcion  = $this->input->post('search_descripcion');
    $filter->fechai       = $this->input->post('fechai');
    $filter->fechaf       = $this->input->post('fechaf');
    $filter->ult_inventario = $this->input->post('ult_inventario');

    $cantidad_salida  = 0;
    $cantidad_entrada = 0;
    $balance          = 0;

    $kardex = $this->kardex_model->consultar_kardex($filter);

    if ($kardex) {
      foreach ($kardex as $key => $value) {
        $fontin       = "<font color='blue'>";
        $anulado      = "<font color='red'>";
        $fontaout     = "<font color='green'>";
        $fontend      = "</fon>";

        //DOCUMENTO INFO
        //$compania     = $value->COMPP_Codigo;
        $fecha        = $value->fecha;
        $almacen      = $value->almacen;
        $tipo_docu    = $value->tipo_docu; 
        $codigoDoc    = $value->codigo_docu; 
        $numDoc       = $value->serie."-".$value->numero; 
        
        //DETALLE INFO
        $productoCod  = $value->codigo; 
        //$nombreProd   = $value->PROD_Descripcion;
        $cantidad     = $value->cantidad;
        //$afectacion   = $value->KARDC_ProdAfectacion;
        //$pu           = $value->KARDC_Costo;
        $precioConIgv = $value->moneda." ".$value->pu_conIgv;
        $subtotal     = $value->moneda." ".$value->subtotal;
        $total        = $value->moneda." ".$value->total;

        //MOVIMIENTO INFO
        $tipo_mov     = $value->tipo_mov;
        $descr_mov    = $value->tipo_des;
        $estado       = $value->estado;
        
        $num_doc        = $numDoc;
        $cantidad_final = $cantidad;
        $precio_conigv  = $precioConIgv;
        $nom_almacen    = $value->nombre_almacen;
        $denominacion   = $value->razon_social_cliente!=NULL ? $value->razon_social_cliente:$value->razon_social_proveedor;  

        if ($tipo_docu=="F" || $tipo_docu=="B" || $tipo_docu=="N") {
          if ($tipo_mov == 1) {
            if ($estado==1) {
              $tipo_mov_d     = $fontaout.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_salida  += $cantidad;
          }else{
            if ($estado==1) {
              $tipo_mov_d     = $fontin.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_entrada += $cantidad;
          }
         
        }else if($tipo_docu=="T"){
          if ($tipo_mov == 1) {
            $denominacion   = "TRASLADO DE ALMACEN";
            $tipo_mov_d     = $fontaout.$descr_mov;
            $cantidad_salida  += $cantidad;
          }else{
            $denominacion   = "TRASLADO DE ALMACEN";
            $tipo_mov_d     = $fontin.$descr_mov;
            $cantidad_entrada += $cantidad;
          }
        }
        else if($tipo_docu=="A"){
          if ($tipo_mov == 1) {
            $cantidad_salida  = 0;
            $cantidad_entrada = 0;
            $balance          = 0;
            $denominacion     = "REEMPLAZO POR AJUSTE";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada  += $cantidad;
          }else{
            $denominacion     = "SUMA POR AJUSTE";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada += $cantidad;
          }
        }else if($tipo_docu=="NC"){
          if ($tipo_mov == 1) {
            if ($estado==1) {
              $tipo_mov_d     = $fontaout.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_salida  += $cantidad;
          }else{
            if ($estado==1) {
              $tipo_mov_d     = $fontin.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_entrada += $cantidad;
          }
        }else if($tipo_docu=="I"){
          if ($tipo_mov == 1) {
            $cantidad_salida  = 0;
            $cantidad_entrada = 0;
            $balance          = 0;
            $denominacion     = "INGRESO DE INVENTARIO";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada  += $cantidad;
          }else{
            $cantidad_salida  = 0;
            $cantidad_entrada = 0;
            $balance          = 0;
            $denominacion     = "INGRESO DE INVENTARIO";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada += $cantidad;
          }
        }
        

        $balance = $cantidad_entrada-$cantidad_salida;
        $pos=0;
        $lista[] = array(
          $pos++ => $fecha,   
          $pos++ => $num_doc, 
          $pos++ => $denominacion, 
          $pos++ => $tipo_mov_d,
          $pos++ => $cantidad_final,
          $pos++ => $balance,
          $pos++ => $precio_conigv,
          $pos++ => $total,   
          $pos++ => $nom_almacen  
        );
      }

    }else{
      $lista = array();
    }
  

    $json = array(
      "entrada"  => $cantidad_entrada,
      "salida"   => $cantidad_salida,
      "data"     => array_reverse($lista)
    );

    echo json_encode($json);
  }

  public function balancear_stock($value='')
  { 
    $filter = new stdClass();
    $filter->producto = $this->input->post('producto');
    $filter->almacen = $this->input->post('almacen');
    $filter->descripcion = $this->input->post('search_descripcion');
    $filter->fechai = $this->input->post('fechai');
    $filter->fechaf = $this->input->post('fechaf');

    $cantidad_salida  = 0;
    $cantidad_entrada = 0;

    $kardex = $this->kardex_model->consultar_kardex($filter);
    if ($kardex) {
      foreach ($kardex as $key => $value) {
        $fontin       = "<font color='blue'>";
        $anulado      = "<font color='red'>";
        $fontaout     = "<font color='green'>";
        $fontend      = "</fon>";

        //DOCUMENTO INFO
        //$compania     = $value->COMPP_Codigo;
        $fecha        = $value->fecha;
        $almacen      = $value->almacen;
        $tipo_docu    = $value->tipo_docu; # T: transferencia 
        $codigoDoc    = $value->codigo_docu; 
        $numDoc       = $value->serie."-".$value->numero; 
              
        //DETALLE INFO
        $productoCod  = $value->codigo; 
        //$nombreProd   = $value->PROD_Descripcion;
        $cantidad     = $value->cantidad;
        //$afectacion   = $value->KARDC_ProdAfectacion;
        //$pu           = $value->KARDC_Costo;
        $precioConIgv = $value->moneda." ".$value->pu_conIgv;
        $subtotal     = $value->moneda." ".$value->subtotal;
        $total        = $value->moneda." ".$value->total;

        //MOVIMIENTO INFO
        $tipo_mov     = $value->tipo_mov;
        $descr_mov    = $value->tipo_des;
        $estado       = $value->estado;
        
        $num_doc        = $numDoc;
        $cantidad_final = $cantidad;
        $precio_conigv  = $precioConIgv;
        $nom_almacen    = $value->nombre_almacen;
        $denominacion   = $value->razon_social_cliente!=NULL ? $value->razon_social_cliente:$value->razon_social_proveedor;  

        if ($tipo_docu=="F" || $tipo_docu=="B" || $tipo_docu=="N") {
          if ($tipo_mov == 1) {
            if ($estado==1) {
              $tipo_mov_d     = $fontaout.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_salida  += $cantidad;
          }else{
            if ($estado==1) {
              $tipo_mov_d     = $fontin.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_entrada += $cantidad;
          }
         
        }else if($tipo_docu=="T"){
          if ($tipo_mov == 1) {
            $denominacion   = "TRASLADO DE ALMACEN";
            $tipo_mov_d     = $fontaout.$descr_mov;
            $cantidad_salida  += $cantidad;
          }else{
            $denominacion   = "TRASLADO DE ALMACEN";
            $tipo_mov_d     = $fontin.$descr_mov;
            $cantidad_entrada += $cantidad;
          }
        }
        else if($tipo_docu=="A"){
          if ($tipo_mov == 1) {
            $cantidad_salida  = 0;
            $cantidad_entrada = 0;
            $balance          = 0;
            $denominacion     = "REEMPLAZO POR AJUSTE";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada  += $cantidad;
          }else{
            $denominacion     = "SUMA POR AJUSTE";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada += $cantidad;
          }
        }else if($tipo_docu=="NC"){
          if ($tipo_mov == 1) {
            if ($estado==1) {
              $tipo_mov_d     = $fontaout.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_salida  += $cantidad;
          }else{
            if ($estado==1) {
              $tipo_mov_d     = $fontin.$descr_mov;
            }else{
              $tipo_mov_d     = $anulado.$descr_mov;
            }
            $cantidad_entrada += $cantidad;
          }
        }else if($tipo_docu=="I"){
          if ($tipo_mov == 1) {
            $cantidad_salida  = 0;
            $cantidad_entrada = 0;
            $balance          = 0;
            $denominacion     = "INGRESO DE INVENTARIO";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada  += $cantidad;
          }else{
            $cantidad_salida  = 0;
            $cantidad_entrada = 0;
            $balance          = 0;
            $denominacion     = "INGRESO DE INVENTARIO";
            $tipo_mov_d       = $fontin.$descr_mov;
            $cantidad_entrada += $cantidad;
          }
        }
        
        $balance = $cantidad_entrada-$cantidad_salida;
  
      }

      $datas = new stdClass();
      $datas->ALMPROD_Stock = $balance;
      $almacen              = $filter->almacen;
      $product              = $filter->producto;
      
      $actualizar = $this->kardex_model->atualizar_sctock($almacen,$product,$datas);
      $exit       = array('resultado' => true,'response' => $actualizar);
      
    }else{
      $exit = array('resultado' => false);
    }
    echo json_encode($exit);
  }

  public function balancear_stock_total($value='')
  { 
    
    $listado = $this->producto_model->productos_sistema();
    $lista = array();
    
    if ( count($listado) > 0 ){
        foreach ($listado as $indice => $valor) {
            
          $filter = new stdClass();
          $filter->producto = $valor->PROD_Codigo;
          $filter->almacen = $this->input->post('almacen');
          $filter->ult_inventario = 0;
          
          $cantidad_salida  = 0;
          $cantidad_entrada = 0;
          $balance          = 0;
          $kardex = $this->kardex_model->consultar_kardex($filter);
          if ($kardex) {
            foreach ($kardex as $key => $value) {
              $fontin       = "<font color='blue'>";
              $anulado      = "<font color='red'>";
              $fontaout     = "<font color='green'>";
              $fontend      = "</fon>";

              //DOCUMENTO INFO
              //$compania     = $value->COMPP_Codigo;
              $fecha        = $value->fecha;
              $almacen      = $value->almacen;
              $tipo_docu    = $value->tipo_docu; # T: transferencia 
              $codigoDoc    = $value->codigo_docu; 
              $numDoc       = $value->serie."-".$value->numero; 
              
              //DETALLE INFO
              $productoCod  = $value->codigo; 
              //$nombreProd   = $value->PROD_Descripcion;
              $cantidad     = $value->cantidad;
              //$afectacion   = $value->KARDC_ProdAfectacion;
              //$pu           = $value->KARDC_Costo;
              $precioConIgv = $value->moneda." ".$value->pu_conIgv;
              $subtotal     = $value->moneda." ".$value->subtotal;
              $total        = $value->moneda." ".$value->total;

              //MOVIMIENTO INFO
              $tipo_mov     = $value->tipo_mov;
              $descr_mov    = $value->tipo_des;
              $estado       = $value->estado;
              
              $num_doc        = $numDoc;
              $cantidad_final = $cantidad;
              $precio_conigv  = $precioConIgv;
              $nom_almacen    = $value->nombre_almacen;
              $denominacion   = $value->razon_social_cliente!=NULL ? $value->razon_social_cliente:$value->razon_social_proveedor;  

              if ($tipo_docu=="F" || $tipo_docu=="B" || $tipo_docu=="N") {
                if ($tipo_mov == 1) {
                  if ($estado==1) {
                    $tipo_mov_d     = $fontaout.$descr_mov;
                  }else{
                    $tipo_mov_d     = $anulado.$descr_mov;
                  }
                  $cantidad_salida  += $cantidad;
                }else{
                  if ($estado==1) {
                    $tipo_mov_d     = $fontin.$descr_mov;
                  }else{
                    $tipo_mov_d     = $anulado.$descr_mov;
                  }
                  $cantidad_entrada += $cantidad;
                }
               
              }else if($tipo_docu=="T"){
                if ($tipo_mov == 1) {
                  $denominacion   = "TRASLADO DE ALMACEN";
                  $tipo_mov_d     = $fontaout.$descr_mov;
                  $cantidad_salida  += $cantidad;
                }else{
                  $denominacion   = "TRASLADO DE ALMACEN";
                  $tipo_mov_d     = $fontin.$descr_mov;
                  $cantidad_entrada += $cantidad;
                }
              }
              else if($tipo_docu=="A"){
                if ($tipo_mov == 1) {
                  $cantidad_salida  = 0;
                  $cantidad_entrada = 0;
                  $balance          = 0;
                  $denominacion     = "REEMPLAZO POR AJUSTE";
                  $tipo_mov_d       = $fontin.$descr_mov;
                  $cantidad_entrada  += $cantidad;
                }else{
                  $denominacion     = "SUMA POR AJUSTE";
                  $tipo_mov_d       = $fontin.$descr_mov;
                  $cantidad_entrada += $cantidad;
                }
              }else if($tipo_docu=="NC"){
                if ($tipo_mov == 1) {
                  if ($estado==1) {
                    $tipo_mov_d     = $fontaout.$descr_mov;
                  }else{
                    $tipo_mov_d     = $anulado.$descr_mov;
                  }
                  $cantidad_salida  += $cantidad;
                }else{
                  if ($estado==1) {
                    $tipo_mov_d     = $fontin.$descr_mov;
                  }else{
                    $tipo_mov_d     = $anulado.$descr_mov;
                  }
                  $cantidad_entrada += $cantidad;
                }
              }else if($tipo_docu=="I"){
                if ($tipo_mov == 1) {
                  $cantidad_salida  = 0;
                  $cantidad_entrada = 0;
                  $balance          = 0;
                  $denominacion     = "INGRESO DE INVENTARIO";
                  $tipo_mov_d       = $fontin.$descr_mov;
                  $cantidad_entrada  += $cantidad;
                }else{
                  $cantidad_salida  = 0;
                  $cantidad_entrada = 0;
                  $balance          = 0;
                  $denominacion     = "INGRESO DE INVENTARIO";
                  $tipo_mov_d       = $fontin.$descr_mov;
                  $cantidad_entrada += $cantidad;
                }
              }
        
              $balance = $cantidad_entrada-$cantidad_salida;
        
            }

            $datas                = new stdClass();
            $datas->ALMPROD_Stock = $balance;
            $almacen              = $filter->almacen;
            $product              = $filter->producto;
            $actualizar           = $this->kardex_model->atualizar_sctock($almacen,$product,$datas);
            $exit                 = array('resultado' => true, 'response' => "true");
            
          }else{
            $exit = array('resultado' => false);
          }
        }
    }

    
    echo json_encode($exit);
  }

  ###########################
  public function ingreso_a_kardex($value='')
  { 
    
    $listado = $this->producto_model->productos_sistema();
    $lista = array();
    
    if ( count($listado) > 0 ){
        foreach ($listado as $indice => $valor) {
            
          $filter = new stdClass();
          $filter->producto = $valor->PROD_Codigo;
          $filter->almacen = $this->input->post('almacen');
          $filter->ult_inventario = 0;
          
          $cantidad_salida  = 0;
          $cantidad_entrada = 0;

          $kardex = $this->kardex_model->para_el_kardex($filter);
          if ($kardex) {
            foreach ($kardex as $key => $value) {
              $fecha        = "";
              $almacen      = $value->almacen;
              $nom_almacen  = $value->nombre_almacen;
              $fecha        = $value->fecha;//mysql_to_human($value->fecha);
              $num_doc      = $value->serie.' - '.$value->numero;
              $precio_conigv = $value->pu_conIgv;
              $fontin       = "<font color='blue'>";
              $fontaout     = "<font color='green'>";
              $fontend      = "</fon>";
              $codprod      = $value->codigo;
              
              $cantidad = $value->cantidad;
              
              $cantidad_final = $cantidad;
              $subtotal = $value->subtotal;
              $total    = $value->total;

              $cantidad = $cantidad;

              if ($value->numero=="" || $value->numero==NULL) {
                $num_doc="INVENTARIO";
              }
              if($value->tipo_oper=='V'){
                $cliente = $value->razon_social_cliente;
                $tipo_mov=$fontaout."SALIDA";
                $cantidad_salida  += $cantidad;
                ############################
                # REGISTRO DE KARDEX
                ############################
                $cKardex = new stdClass();
                $cKardex->fecha  = $value->fecha;
                $cKardex->codigo_documento  = $value->codigo_docu;
                $cKardex->tipo_docu         = $value->tipo_docu;
                $cKardex->producto          = $codprod;
                $cKardex->nombre_producto   = NULL;
                $cKardex->cantidad          = $cantidad;
                $cKardex->serie             = $value->serie;
                $cKardex->numero            = $value->numero;
                $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                $cKardex->afectacion        = 1;
                $cKardex->costo             = NULL;
                $cKardex->precio_con_igv    = NULL;
                $cKardex->subtotal          = $subtotal;
                $cKardex->total             = $total;
                $cKardex->compania          = $this->somevar['compania'];
                $cKardex->tipo_oper         = 1; # 1: SALIDA 2: INGRESO 
                $cKardex->tipo_movimiento   = "SALIDA POR VENTA";
                $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                $cKardex->almacen           = $filter->almacen;
                $cKardex->cliente           = $value->razon_social_cliente;
                $cKardex->proveedor         = $value->razon_social_proveedor;
                $cKardex->usuario           = 1; 
                $cKardex->estado            = 1;
                $this->registrar_kardex($cKardex);

              }elseif ($value->tipo_oper=='C') {
                $cliente = $value->razon_social_proveedor;
                $tipo_mov=$fontin."ENTRADA";
                $cantidad_entrada += $cantidad;
                ############################
                # REGISTRO DE KARDEX
                ############################
                $cKardex = new stdClass();
                $cKardex->fecha  = $value->fecha;
                $cKardex->codigo_documento  = $value->codigo_docu;
                $cKardex->tipo_docu         = $value->tipo_docu;
                $cKardex->producto          = $codprod;
                $cKardex->nombre_producto   = NULL;
                $cKardex->cantidad          = $cantidad;
                $cKardex->serie             = $value->serie;
                $cKardex->numero            = $value->numero;
                $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                $cKardex->afectacion        = 1;
                $cKardex->costo             = NULL;
                $cKardex->precio_con_igv    = NULL;
                $cKardex->subtotal          = $subtotal;
                $cKardex->total             = $total;
                $cKardex->compania          = $this->somevar['compania'];
                $cKardex->tipo_oper         = 2; # 1: SALIDA 2: INGRESO 
                $cKardex->tipo_movimiento   = "ENTRADA POR COMPRA";
                $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                $cKardex->almacen           = $filter->almacen;
                $cKardex->cliente           = $value->razon_social_cliente;
                $cKardex->proveedor         = $value->razon_social_proveedor;
                $cKardex->usuario           = 1; 
                $cKardex->estado            = 1;
                $this->registrar_kardex($cKardex);
              }else{
                if($value->tipo_oper=="I"){
                  $tipo_mov = $fontin."ENTRADA";
                  $cliente  = "ENTRADA POR MOVIMIENTO DE INVENTARIO"; 
                  $cantidad_entrada += $cantidad;
                  ############################
                  # REGISTRO DE KARDEX
                  ############################
                  $cKardex = new stdClass();
                    $cKardex->fecha  = $value->fecha;
                    $cKardex->codigo_documento  = $value->codigo_docu;
                    $cKardex->tipo_docu         = $value->tipo_docu;
                    $cKardex->producto          = $codprod;
                    $cKardex->nombre_producto   = NULL;
                    $cKardex->cantidad          = $cantidad;
                    $cKardex->serie             = $value->serie;
                    $cKardex->numero            = $value->numero;
                    $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                    $cKardex->afectacion        = 1;
                    $cKardex->costo             = NULL;
                    $cKardex->precio_con_igv    = NULL;
                    $cKardex->subtotal          = $subtotal;
                    $cKardex->total             = $total;
                    $cKardex->compania          = $this->somevar['compania'];
                    $cKardex->tipo_oper         = 1; # 1: REEMPLAZO
                    $cKardex->tipo_movimiento   = "INGRESO DE INVENTARIO";
                    $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->almacen           = $filter->almacen;
                    $cKardex->cliente           = $value->razon_social_cliente;
                    $cKardex->proveedor         = $value->razon_social_proveedor;
                    $cKardex->usuario           = 1; 
                    $cKardex->estado            = 1;
                    $this->registrar_kardex($cKardex);
                }
                if($value->tipo_oper=="T"){
                  if($value->almacen == $filter->almacen){
                    $tipo_mov = $fontaout."SALIDA";
                    $cliente  = "TRASLADO DE ALMACEN"; 
                    $cantidad_salida  += $cantidad;
                    ############################
                    # REGISTRO DE KARDEX
                    ############################
                    $cKardex = new stdClass();
                    $cKardex->fecha  = $value->fecha;
                    $cKardex->codigo_documento  = $value->codigo_docu;
                    $cKardex->tipo_docu         = $value->tipo_docu;
                    $cKardex->producto          = $codprod;
                    $cKardex->nombre_producto   = NULL;
                    $cKardex->cantidad          = $cantidad;
                    $cKardex->serie             = $value->serie;
                    $cKardex->numero            = $value->numero;
                    $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                    $cKardex->afectacion        = 1;
                    $cKardex->costo             = NULL;
                    $cKardex->precio_con_igv    = NULL;
                    $cKardex->subtotal          = $subtotal;
                    $cKardex->total             = $total;
                    $cKardex->compania          = $this->somevar['compania'];
                    $cKardex->tipo_oper         = 1; # 1: SALIDA 2: INGRESO 
                    $cKardex->tipo_movimiento   = "SALIDA POR TRASLADO DE ALMACEN";
                    $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->almacen           = $filter->almacen;
                    $cKardex->cliente           = $value->razon_social_cliente;
                    $cKardex->proveedor         = $value->razon_social_proveedor;
                    $cKardex->usuario           = 1; 
                    $cKardex->estado            = 1;
                    $this->registrar_kardex($cKardex);
                  }else{
                    $cliente  = "TRASLADO DE ALMACEN"; 
                    $tipo_mov = $fontin."ENTRADA";
                    $cantidad_entrada += $cantidad;
                    ############################
                    # REGISTRO DE KARDEX
                    ############################
                    $cKardex = new stdClass();
                    $cKardex->fecha  = $value->fecha;
                    $cKardex->codigo_documento  = $value->codigo_docu;
                    $cKardex->tipo_docu         = $value->tipo_docu;
                    $cKardex->producto          = $codprod;
                    $cKardex->nombre_producto   = NULL;
                    $cKardex->cantidad          = $cantidad;
                    $cKardex->serie             = $value->serie;
                    $cKardex->numero            = $value->numero;
                    $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                    $cKardex->afectacion        = 1;
                    $cKardex->costo             = NULL;
                    $cKardex->precio_con_igv    = NULL;
                    $cKardex->subtotal          = $subtotal;
                    $cKardex->total             = $total;
                    $cKardex->compania          = $this->somevar['compania'];
                    $cKardex->tipo_oper         = 2; # 1: SALIDA 2: INGRESO 
                    $cKardex->tipo_movimiento   = "INGRESO POR TRASLADO DE ALMACEN";
                    $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->almacen           = $filter->almacen;
                    $cKardex->cliente           = $value->razon_social_cliente;
                    $cKardex->proveedor         = $value->razon_social_proveedor;
                    $cKardex->usuario           = 1; 
                    $cKardex->estado            = 1;
                    $this->registrar_kardex($cKardex);
                  }
                }

                if ($value->tipo_oper=="A") {
                  $total="";
                  $cliente  = "AJUSTE DE INVENTARIO";
                  if ($value->codigo_docu==1) {
                    $tipo_mov = $fontin."REEMPLAZO";
                    $cantidad_entrada += $cantidad;
                    ############################
                    # REGISTRO DE KARDEX
                    ############################
                    $cKardex = new stdClass();
                    $cKardex->fecha  = $value->fecha;
                    $cKardex->codigo_documento  = $value->codigo_docu;
                    $cKardex->tipo_docu         = $value->tipo_docu;
                    $cKardex->producto          = $codprod;
                    $cKardex->nombre_producto   = NULL;
                    $cKardex->cantidad          = $cantidad;
                    $cKardex->serie             = $value->serie;
                    $cKardex->numero            = $value->numero;
                    $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                    $cKardex->afectacion        = 1;
                    $cKardex->costo             = NULL;
                    $cKardex->precio_con_igv    = NULL;
                    $cKardex->subtotal          = $subtotal;
                    $cKardex->total             = $total;
                    $cKardex->compania          = $this->somevar['compania'];
                    $cKardex->tipo_oper         = 1; # 1: REEMPLAZO
                    $cKardex->tipo_movimiento   = "REEMPLAZO POR AJUSTE";
                    $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->almacen           = $filter->almacen;
                    $cKardex->cliente           = $value->razon_social_cliente;
                    $cKardex->proveedor         = $value->razon_social_proveedor;
                    $cKardex->usuario           = 1; 
                    $cKardex->estado            = 1;
                    $this->registrar_kardex($cKardex);
                  }else{
                    $tipo_mov = $fontin."SUMA";
                    $cantidad_entrada += $cantidad;
                    ############################
                    # REGISTRO DE KARDEX
                    ############################
                    $cKardex = new stdClass();
                    $cKardex->fecha  = $value->fecha;
                    $cKardex->codigo_documento  = $value->codigo_docu;
                    $cKardex->tipo_docu         = $value->tipo_docu;
                    $cKardex->producto          = $codprod;
                    $cKardex->nombre_producto   = NULL;
                    $cKardex->cantidad          = $cantidad;
                    $cKardex->serie             = $value->serie;
                    $cKardex->numero            = $value->numero;
                    $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                    $cKardex->afectacion        = 1;
                    $cKardex->costo             = NULL;
                    $cKardex->precio_con_igv    = NULL;
                    $cKardex->subtotal          = $subtotal;
                    $cKardex->total             = $total;
                    $cKardex->compania          = $this->somevar['compania'];
                    $cKardex->tipo_oper         = 2; # 1: SALIDA 2: INGRESO 
                    $cKardex->tipo_movimiento   = "SUMA POR AJUSTE";
                    $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->almacen           = $filter->almacen;
                    $cKardex->cliente           = $value->razon_social_cliente;
                    $cKardex->proveedor         = $value->razon_social_proveedor;
                    $cKardex->usuario           = 1; 
                    $cKardex->estado            = 1;
                    $this->registrar_kardex($cKardex);
                  }
                }
                if ($value->tipo_oper=="N") {
                  $total="";
                  $cliente  = $value->razon_social_cliente;
                  $num_doc      ="NC ". $value->serie.' - '.$value->numero;
                  $tipo_mov=$fontin."ENTRADA";
                  $cantidad_entrada += $cantidad;
                    ############################
                    # REGISTRO DE KARDEX
                    ############################
                    $cKardex = new stdClass();
                    $cKardex->fecha  = $value->fecha;
                    $cKardex->codigo_documento  = $value->codigo_docu;
                    $cKardex->tipo_docu         = $value->tipo_docu;
                    $cKardex->producto          = $codprod;
                    $cKardex->nombre_producto   = NULL;
                    $cKardex->cantidad          = $cantidad;
                    $cKardex->serie             = $value->serie;
                    $cKardex->numero            = $value->numero;
                    $cKardex->nombre_almacen    = NULL; #opcionales (para futuro desarrollo)
                    $cKardex->afectacion        = 1;
                    $cKardex->costo             = NULL;
                    $cKardex->precio_con_igv    = NULL;
                    $cKardex->subtotal          = $subtotal;
                    $cKardex->total             = $total;
                    $cKardex->compania          = $this->somevar['compania'];
                    $cKardex->tipo_oper         = 2; # 1: SALIDA 2: INGRESO 
                    $cKardex->tipo_movimiento   = "INGRESO POR NOTA DE CREDITO";
                    $cKardex->nombre            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->numdoc            = ""; #opcionales (para futuro desarrollo)
                    $cKardex->almacen           = $filter->almacen;
                    $cKardex->cliente           = $value->razon_social_cliente;
                    $cKardex->proveedor         = $value->razon_social_proveedor;
                    $cKardex->usuario           = 1; 
                    $cKardex->estado            = 1;
                    $this->registrar_kardex($cKardex);
                }
              }

              //$balance = $cantidad_entrada - $cantidad_salida;

                
              
            }



                
            $exit       = array('resultado' => true,'response' => "true");
            
          }else{
            $exit = array('resultado' => false);
          }
        }
    }

    
    echo json_encode($exit);
  }


  public function registrar_kardex($filter)
  {
      $cKardex = new stdClass();
     
      $cKardex->KARD_Fecha            = $filter->fecha;
      $cKardex->KARDC_CodigoDoc       = $filter->codigo_documento;
      $cKardex->DOCUP_Codigo          = $filter->tipo_docu;
      $cKardex->PROD_Codigo           = $filter->producto;
      $cKardex->PROD_Descripcion      = $filter->nombre_producto; #opcionales (para futuro desarrollo)
      $cKardex->KARDC_Cantidad        = $filter->cantidad;
      $cKardex->KARDC_Serie           = $filter->serie;
      $cKardex->KARDC_Numero          = $filter->numero;
      $cKardex->KARDC_AlmacenDesc     = $filter->nombre_almacen; #opcionales (para futuro desarrollo)
      $cKardex->MONED_Codigo          = $filter->moneda;
      $cKardex->KARDC_ProdAfectacion  = $filter->afectacion;
      $cKardex->KARDC_Costo           = $filter->costo;
      $cKardex->KARDC_PrecioConIgv    = $filter->precio_con_igv;
      $cKardex->KARDC_Subtotal        = $filter->subtotal;
      $cKardex->KARDC_Total           = $filter->total;
      $cKardex->COMPP_Codigo          = $filter->compania;
      $cKardex->TIPOMOVP_Codigo       = $filter->tipo_oper;
      $cKardex->LOTP_Codigo           = NULL;
      $cKardex->KARDC_TipoIngreso     = $filter->tipo_movimiento;
      $cKardex->Denominacion          = $filter->nombre; #opcionales (para futuro desarrollo)
      $cKardex->NumDocRuc             = $filter->numdoc; #opcionales (para futuro desarrollo)
      $cKardex->ALMPROD_Codigo        = $filter->almacen;
      $cKardex->CLIP_Codigo           = $filter->cliente;
      $cKardex->PROVP_Codigo          = $filter->proveedor;
      $cKardex->USUA_Codigo           = $filter->usuario; #Nombre o codigo?
      $cKardex->KARDP_FlagEstado      = $filter->estado;
      $this->kardex_model->ingresar_kardex($cKardex);
  }

}

?>