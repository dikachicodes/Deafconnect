<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$data=requireJsonRequest();requireCsrf($data);
$contactId=(int)($data['contact_id'] ?? 0);$name=cleanText($data['sender_name'] ?? 'Guest',80);$body=cleanText($data['body'] ?? '',2000);
if($contactId<1||!$body)jsonResponse(['success'=>false,'message'=>'Please enter a message.'],422);
if(!$name)$name='Guest';
$check=$pdo->prepare('SELECT id,name FROM contacts WHERE id=? AND is_active=1');$check->execute([$contactId]);$contact=$check->fetch();if(!$contact)jsonResponse(['success'=>false,'message'=>'Contact not found.'],404);
$key=guestKey();
$pdo->beginTransaction();
try{
 $stmt=$pdo->prepare('INSERT INTO messages (contact_id,conversation_key,sender_type,sender_name,body,is_read) VALUES (?,?,?,?,?,1)');$stmt->execute([$contactId,$key,'guest',$name,$body]);
 $auto='Thanks for your message. A member of our '.$contact['name'].' team will respond here. No phone call is required.';
 $stmt=$pdo->prepare('INSERT INTO messages (contact_id,conversation_key,sender_type,sender_name,body,is_read) VALUES (?,?,?,?,?,0)');$stmt->execute([$contactId,$key,'admin',$contact['name'],$auto]);
 $pdo->commit();
}catch(Throwable $e){$pdo->rollBack();jsonResponse(['success'=>false,'message'=>'Message could not be saved.'],500);}
$stmt=$pdo->prepare('SELECT sender_type,sender_name,body,created_at FROM messages WHERE contact_id=? AND (conversation_key=? OR conversation_key IS NULL) ORDER BY created_at ASC,id ASC');$stmt->execute([$contactId,$key]);$messages=[];foreach($stmt as $row)$messages[]=['sender_type'=>$row['sender_type'],'sender_name'=>$row['sender_name'],'body'=>$row['body'],'display_time'=>date('M j, g:i A',strtotime($row['created_at']))];
jsonResponse(['success'=>true,'messages'=>$messages]);
