<?php
/*
 * Gerenciador de Tarefas Colaborativo
 * 
 * Alunos:
 * Jullia de Oliveira Ribeiro Chrispim - RGM: 42316103
 * Gabrielle Camargo dos Santos        - RGM: 43369014
 * Isabella de Oliveira Gonçalves      - RGM: 42977151
 * Vitória Marconcin Marques           - RGM: 43524532
 */

session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: pages/tarefas.php');
    exit;
}

header('Location: pages/login.php');
exit;
