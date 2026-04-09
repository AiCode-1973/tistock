<?php
/**
 * TI Stock - Configurações Globais
 *
 * Edite este arquivo conforme o ambiente de instalação.
 * Mantenha-o fora do acesso público em produção.
 */

// ----------------------------------------
// Configurações do Banco de Dados
// ----------------------------------------
define('DB_HOST',    '186.209.113.107');
define('DB_NAME',    'dema5738_tistock');
define('DB_USER',    'dema5738_tistock');
define('DB_PASS',    'Dema@1973');
define('DB_CHARSET', 'utf8mb4');

// ----------------------------------------
// Configurações da Aplicação
// URL base sem barra final. Ex: '/tistock' para http://localhost/tistock
// ----------------------------------------
define('BASE_URL',     '/tistock');
define('APP_NAME',     'TI Stock');
define('APP_SUBTITLE', 'Controle de Estoque – Setor de TI');
define('APP_VERSION',  '1.0.0');

// Fuso horário padrão
date_default_timezone_set('America/Sao_Paulo');
