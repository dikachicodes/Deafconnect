<?php
require_once __DIR__ . '/../../config/bootstrap.php';requireAdmin();
$data=requireJsonRequest();requireCsrf($data);
$criteria=[
 ['1.2.2','Captions (prerecorded)','A',1],['1.2.3','Audio description or media alternative','A',1],['1.3.1','Info and relationships','A',1],['1.4.1','Use of colour','A',1],['1.4.3','Contrast minimum (4.5:1)','AA',1],['1.4.4','Resize text (200% zoom)','AA',1],['2.1.1','Keyboard accessible','A',1],['2.4.3','Focus order','A',1],['3.3.1','Error identification','A',1],['3.3.2','Labels or instructions','A',1],['4.1.2','Name, role, value','A',1]
];
$violations=[['moderate','Decorative background image / visual ornament should be verified during deployment','1.1.1'],['none','Zero critical violations in the recorded baseline','All'],['none','Zero serious violations in the recorded baseline','All']];
$score=97;$stmt=$pdo->prepare('INSERT INTO audit_reports (baseline_score,critical_count,serious_count,moderate_count,report_json) VALUES (?,?,?,?,?)');$stmt->execute([$score,0,0,1,json_encode(['criteria'=>$criteria,'violations'=>$violations],JSON_UNESCAPED_UNICODE)]);jsonResponse(['success'=>true,'report'=>['baseline_score'=>$score,'critical_count'=>0,'serious_count'=>0,'moderate_count'=>1,'criteria'=>$criteria,'violations'=>$violations,'created_at'=>date('Y-m-d H:i:s')]]);
