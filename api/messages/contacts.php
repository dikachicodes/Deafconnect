<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$stmt = $pdo->query('SELECT id,name,initials,description FROM contacts WHERE is_active=1 ORDER BY sort_order,id');
$contacts=[];
foreach($stmt as $row){
    $unreadStmt=$pdo->prepare('SELECT COUNT(*) FROM messages WHERE contact_id=? AND conversation_key=? AND sender_type="admin" AND is_read=0');
    $unreadStmt->execute([$row['id'],guestKey()]);
    $contacts[]=['id'=>(int)$row['id'],'name'=>$row['name'],'initials'=>$row['initials'],'preview'=>$row['description'],'unread'=>(int)$unreadStmt->fetchColumn()];
}
jsonResponse(['success'=>true,'contacts'=>$contacts]);
