<?php
//
// AlugarCar - Sistema para Reserva e Alugel de Veículos
// Descricao: Lista de loja
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


/// Dados da Lista
$dados_lista = new stdClass();
$dados_lista->opcoes = array('exibir', 'alterar', 'excluir');
$dados_lista->campos = array('nome');
$dados_lista->ordem  = array('nome' => true);

modulo::listar_entidades('loja', $dados_lista);