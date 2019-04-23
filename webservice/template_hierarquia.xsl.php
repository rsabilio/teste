<?php
//
// SIMP
// Descricao: Arquivo para transformar o XML de uma lista de entidades hierarquicas em HTML
// Autor: Rubens Takiguti Ribeiro
// Orgao: TecnoLivre - Cooperativa de Tecnologia e Solucoes Livres
// E-mail: rubens@tecnolivre.com.br
// Versao: 1.0.0.11
// Data: 14/02/2008
// Modificado: 04/10/2012
// Copyright (C) 2008  Rubens Takiguti Ribeiro
// License: LICENSE.TXT
//
require_once('../config.php');

$input = util::get_dado('input', 'string', false);
if ($input):
$template_item = <<<XML
      <xsl:element name="strong">
        <xsl:attribute name="style">cursor: pointer; text-decoration: underline;</xsl:attribute>
        <xsl:attribute name="onclick">
          <xsl:text>window.opener.document.getElementById('{$input}').value = '</xsl:text>
          <xsl:value-of select="@valor" />
          <xsl:text>'; window.close(); return false;</xsl:text>
        </xsl:attribute>
        <xsl:value-of select="@valor" />
      </xsl:element>
XML;
else:
$template_item = <<<XML
      <xsl:element name="strong">
        <xsl:value-of select="@valor" />
      </xsl:element>
XML;
endif;

$omit_xml = (stripos($_SERVER['HTTP_ACCEPT'], 'text/xml') !== false) ? 'no' : 'yes';
if ($CFG->agent->engine == 'mshtml') {
    $xml_header = '';
} else {
    $xml_header = "<xsl:output method=\"xml\" version=\"1.0\" encoding=\"{$CFG->charset}\" omit-xml-declaration=\"{$omit_xml}\" standalone=\"no\" />";
}


// Montar XML
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.1"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xml:lang="pt-br">

{$xml_header}
<xsl:output method="html" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"/>

<!-- TEMPLATE GERAL (Estrutura do documento) -->
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml" xml:space="default" dir="ltr">

<head>
  <title>Lista</title>
  <link rel="stylesheet" type="text/css" charset="utf-8" media="screen" href="{$CFG->wwwlayout}{$CFG->pessoal->tema}/index.css.php" />
<script type="text/javascript"><![CDATA[
window.onload = function() {
    var uls = document.getElementById("lista_principal").getElementsByTagName("ul");
    for (var i = 0; i < uls.length; i++) {
        var ul = uls.item(i);
        ul.style.display = "none";
    }
};
]]></script>
</head>

<body>

<h1>Lista de <xsl:value-of select="item/@nome" /></h1>
<p>Selecione um valor:</p>
<ul id="lista_principal">
<xsl:apply-templates select="item/item" />
</ul>

</body>

</html>
</xsl:template>

<!-- TEMPLATE DE UM ITEM -->
<xsl:template match="item">
  <xsl:param name="nivel" select="1" />
  <xsl:variable name="id_li"><xsl:value-of select="generate-id()" /></xsl:variable>
  <xsl:variable name="num_itens"><xsl:value-of select="count(item)" /></xsl:variable>

  <!-- LI -->
  <xsl:element name="li">

    <!-- Elemento selecionavel -->
    <xsl:if test="@valor">
{$template_item}
      <xsl:text> - </xsl:text>
    </xsl:if>

    <!-- SPAN -->
    <xsl:element name="span">

      <!-- Elemento grupo -->
      <xsl:if test="\$num_itens &gt; 0">
        <xsl:attribute name="style">cursor: pointer; color: #000077;</xsl:attribute>
        <xsl:attribute name="onclick">
          <xsl:text><![CDATA[var obj = document.getElementById(']]></xsl:text>
          <xsl:value-of select="\$id_li" />
          <xsl:value-of select="\$nivel" />
          <xsl:text><![CDATA[');obj.style.display = (obj.style.display == 'none') ? 'block' : 'none';]]></xsl:text>
        </xsl:attribute>
      </xsl:if>
      <xsl:value-of select="./@nome" />
      <xsl:if test="\$num_itens &gt; 0">
        <xsl:text> (</xsl:text><xsl:value-of select="\$num_itens" /><xsl:text>)</xsl:text>
      </xsl:if>
    </xsl:element>

  </xsl:element>

  <!-- UL -->
  <xsl:if test="\$num_itens &gt; 0">
    <xsl:element name="ul">
      <xsl:attribute name="style">display: block;</xsl:attribute>
      <xsl:attribute name="id">
        <xsl:value-of select="\$id_li" />
        <xsl:value-of select="\$nivel" />
      </xsl:attribute>
      <xsl:apply-templates select="item">
        <xsl:with-param name="nivel" select="\$nivel + 1" />
      </xsl:apply-templates>
    </xsl:element>
  </xsl:if>
</xsl:template>

</xsl:stylesheet>
XML;

$opcoes_http = array(
    'arquivo' => 'template_hierarquia.xsl',
    'compactacao' => true
);

/// Exibir XSL
http::cabecalho('text/xml; charset=UTF-8', $opcoes_http);
echo $xml;
exit(0);

