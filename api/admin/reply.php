<?php
require_once __DIR__ . '/../../config/bootstrap.php'; requireAdmin();
$data=requireJsonRequest();requireCsrf($data);$contactId=(int)($data['contact_id']??0);$key=cleanText($data['conversation_key']??'',80);$body=cleanText($data['body']??'',2000);if(!$contactId||!$key||!$body)jsonResponse(['success'=>false,'message'=>'A reply message is required.'],422);
$adminName=$_SESSION['admin_name']??'DeafConnect Admin';$stmt=$pdo->prepare('INSERT INTO messages (contact_id,conversation_key,sender_type,sender_name,body,is_read) VALUES (?,?,?,?,?,1)');$stmt->execute([$contactId,$key,'admin',$adminName,$body]);jsonResponse(['success'=>true]);
