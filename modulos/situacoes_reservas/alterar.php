<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Arquivo para alterar dados de Situacoes
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


/// Dados do Formulario
$dados_form = new stdClass();
$dados_form->campos = array(
    'nome'
);


modulo::alterar('situacao_reserva', $dados_form);