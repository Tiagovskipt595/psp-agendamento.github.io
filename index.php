<?php
/**
 * Ponto de entrada único - Redireciona para public/
 * Este arquivo permite acessar o site diretamente pela raiz
 */

// Redirecionar para a pasta public
header('Location: public/');
exit;
