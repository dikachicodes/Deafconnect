<?php
require_once __DIR__ . '/../../config/bootstrap.php'; requireAdmin();
$stmt=$pdo->query('SELECT m.id,m.contact_id,m.conversation_key,m.sender_type,m.sender_name,m.body,m.created_at,c.name contact_name FROM messages m JOIN contacts c ON c.id=m.contact_id WHERE m.conversation_key IS NOT NULL ORDER BY m.created_at DESC LIMIT 200');
$rows=$stmt->fetchAll();$groups=[];foreach($rows as $row){$key=$row['contact_id'].'|'.$row['conversation_key'];if(!isset($groups[$key]))$groups[$key]=['contact_id'=>(int)$row['contact_id'],'conversation_key'=>$row['conversation_key'],'contact_name'=>$row['contact_name'],'latest'=>$row['body'],'latest_time'=>$row['created_at'],'messages'=>[]];$groups[$key]['messages'][]=$row;}
foreach($groups as &$g) usort($g['messages'],fn($a,$b)=>strcmp($a['created_at'],$b['created_at'])); unset($g);
jsonResponse(['success'=>true,'conversations'=>array_values($groups)]);
