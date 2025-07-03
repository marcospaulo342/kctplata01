<?php
$config = [
  'chance' => floatval($_POST['chance']),
  'estrutura1' => $_POST['estrutura1'],
  'estrutura2' => $_POST['estrutura2']
];

file_put_contents("config.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo json_encode(['sucesso' => true]);
