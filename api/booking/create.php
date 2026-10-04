<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$data = requireJsonRequest();
requireCsrf($data);
$first = cleanText($data['firstname'] ?? '', 80);
$last = cleanText($data['lastname'] ?? '', 80);
$email = filter_var(trim((string)($data['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$service = cleanText($data['service'] ?? '', 40);
$date = trim((string)($data['date'] ?? ''));
$time = cleanText($data['time'] ?? '', 20);
$notes = cleanText($data['notes'] ?? '', 2000);
$allowedServices = ['interpreter','audiology','counselling','community','relay'];
if (!$first || !$last || !$email || !in_array($service, $allowedServices, true) || !$date || !$time) jsonResponse(['success'=>false,'message'=>'Please complete all required booking fields correctly.'],422);
$dateObj = DateTime::createFromFormat('Y-m-d',$date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $date || $date < date('Y-m-d')) jsonResponse(['success'=>false,'message'=>'Please select a valid future date.'],422);
$stmt = $pdo->prepare('INSERT INTO bookings (first_name,last_name,email,service_type,preferred_date,preferred_time,notes,status) VALUES (?,?,?,?,?,?,?,"pending")');
$stmt->execute([$first,$last,$email,$service,$date,$time,$notes ?: null]);
jsonResponse(['success'=>true,'booking_id'=>(int)$pdo->lastInsertId()]);
