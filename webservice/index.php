<?php
//
// SIMP
// Descricao: Web Service do SIMP (Utilizando NuSOAP)
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.3
// Data: 28/11/2007
// Modificado: 16/02/2011
// License: LICENSE.TXT
// Copyright (C) 2007  Rubens Takiguti Ribeiro
//
require_once('../config.php');
require_once($CFG->dirroot.'webservice/lib/nusoap.php');

// Criar o servidor
$servidor = new nusoap_server();
$servidor->configureWSDL('server.'.$CFG->sistema, 'urn:server.'.$CFG->sistema);

// Registrar metodos
$servidor->register('consultar',

    // Parametros
    array(
        'entidade' => 'xsd:string',
        'codigo'   => 'xsd:integer'
    ),

    // Retorno
    array(
        'nome'   => 'xsd:string',
        'codigo' => 'xsd:integer'
    ),

    'uri:consulta',                  // namespace
    'uri:consulta/consultar',        // SOAPAction
    'rpc',                           // style
    'encoded',                       // use
    'Retorna o nome de uma entidade' // descricao
);


//
//     Retorna o nome de uma entidade
//
function consultar($entidade, $codigo) {
// String $entidade: nome da entidade
// Int $codigo: numero do codigo da entidade a ser consultada
//
    if (simp_autoload($entidade)) {
        $obj = objeto::get_objeto($entidade);
    } else {
        return new soap_fault('Client', '', 'Classe invalida: '.$entidade);
    }
    $obj->consultar($obj->get_chave(), $codigo);
    if (!$obj->existe()) {
        $falha = texto::decodificar($obj->get_entidade().' n&atilde;o encontrado(a)');
        return new soap_fault('Client', '', $falha);
    }
    return array($obj->get_nome(),
                 $obj->get_valor_chave());
}

// Processar o pedido
$servidor->service(isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '');
