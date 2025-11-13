<?php
session_start();
include 'index.php';

$user_id = $_SESSION['user_id'];
$player_id = $_POST['player_id'];

if ($player_id && $user_id) {
  $stmt = $conn->prepare("UPDATE users SET player_id = ? WHERE id = ?");
  $stmt->bind_param("si", $player_id, $user_id);
  $stmt->execute();
}