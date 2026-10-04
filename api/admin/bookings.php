<?php
require_once __DIR__ . '/../../config/bootstrap.php'; requireAdmin();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $data=requireJsonRequest();requireCsrf($data);$id=(int)($data['id']??0);$status=$data['status']??'';if(!$id||!in_array($status,['pending','confirmed','completed','cancelled'],true))jsonResponse(['success'=>false,'message'=>'Invalid booking update.'],422);$stmt=$pdo->prepare('UPDATE bookings SET status=? WHERE id=?');$stmt->execute([$status,$id]);jsonResponse(['success'=>true]);
}
$stmt=$pdo->query('SELECT id,first_name,last_name,email,service_type,preferred_date,preferred_time,notes,status,created_at FROM bookings ORDER BY created_at DESC');$rows=$stmt->fetchAll();jsonResponse(['success'=>true,'bookings'=>$rows]);
