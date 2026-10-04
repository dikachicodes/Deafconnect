<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$contactId=(int)($_GET['contact_id'] ?? 0);
if($contactId<1) jsonResponse(['success'=>false,'message'=>'A valid contact is required.'],422);
$check=$pdo->prepare('SELECT id FROM contacts WHERE id=? AND is_active=1');$check->execute([$contactId]);if(!$check->fetch())jsonResponse(['success'=>false,'message'=>'Contact not found.'],404);
$key=guestKey();
$mark=$pdo->prepare('UPDATE messages SET is_read=1 WHERE contact_id=? AND conversation_key=? AND sender_type="admin"');$mark->execute([$contactId,$key]);
$stmt=$pdo->prepare('SELECT sender_type,sender_name,body,created_at FROM messages WHERE contact_id=? AND (conversation_key=? OR conversation_key IS NULL) ORDER BY created_at ASC,id ASC');$stmt->execute([$contactId,$key]);
$messages=[];foreach($stmt as $row){$messages[]=['sender_type'=>$row['sender_type'],'sender_name'=>$row['sender_name'],'body'=>$row['body'],'display_time'=>date('M j, g:i A',strtotime($row['created_at']))];}
jsonResponse(['success'=>true,'messages'=>$messages]);
