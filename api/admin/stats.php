<?php
require_once __DIR__ . '/../../config/bootstrap.php'; requireAdmin();
$counts=[];
$counts['bookings']=(int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$counts['pending']=(int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE status="pending"')->fetchColumn();
$counts['messages']=(int)$pdo->query('SELECT COUNT(*) FROM messages WHERE sender_type="guest"')->fetchColumn();
$counts['audit']=(int)$pdo->query('SELECT COUNT(*) FROM audit_reports')->fetchColumn();
$stmt=$pdo->query('SELECT id,first_name,last_name,service_type,preferred_date,preferred_time,status,created_at FROM bookings ORDER BY created_at DESC LIMIT 6');
$bookings=[];foreach($stmt as $r){$bookings[]=$r;}
$audit=$pdo->query('SELECT baseline_score,critical_count,serious_count,moderate_count,created_at FROM audit_reports ORDER BY created_at DESC LIMIT 1')->fetch();
jsonResponse(['success'=>true,'counts'=>$counts,'bookings'=>$bookings,'latest_audit'=>$audit]);
