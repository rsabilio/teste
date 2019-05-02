<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Exibe os dados de um(a) Marca
// Autor: Ramon
// Orgao: Fagammon
// E-mail: ramon@teste.com.br
// Versao: 1.0.0.0
// Data: 01/05/2019
// Modificado: 01/05/2019
// Copyright (C) 2019  Ramon
// License: LICENSE.TXT
//
require_once('../../config.php');
require_once($CFG->dirroot.'sessao.php');


/// Dados do Quadro
$dados_quadro = new stdClass();
$dados_quadro->campos = array(
    'cod_marca',
    'nome'
);


modulo::exibir('marca', $dados_quadro);