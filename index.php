<?php
error_reporting(E_ALL);
shell_exec('title=Mailer');
$pdo = new PDO('mysql:host=localhost;dbname=side', 'root', '', [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

function getEmailDB(){
	global $pdo;
	global $setting;
	$getEmails = $pdo->query(setQuery($setting));
	return $getEmails;
}

function setQuery($setting){
	$default = 'SELECT emails FROM emails';
	$table = 'emails';
	$column = 'email';
	if(isset($setting['COLUMN']) && isset($setting['TABLE'])){
		return 'SELECT '.$setting['COLUMN'].' FROM '.$setting['TABLE'].'';
	}
	return $default;
}

require __DIR__.("/config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$emailList = file($setting['email_liste']);

if($setting['DB'] == true){
	$emailListe = getEmailDB()->fetchAll(PDO::FETCH_ASSOC);
	foreach($emailListe as $email){
		$heleListe[] = $email['emails'];
	}
}
else{
	foreach($emailList as $email){
		$heleListe[] = $email;
	}
}
$counterEmails = 0;
class Request extends Threaded {
	public $modtager;
	public $listenummer;
	
    public function __construct($modtager, $listenummer) {
		$this->modtager = $modtager;
		$this->listenummer = $listenummer;
		
    }
    public function run() {
		global $counterEmails;
		$counterEmails++;
		$listenummer = $this->listenummer;
		require_once __DIR__.'/vendor/phpmailer/phpmailer/src/Exception.php';
		require_once __DIR__.'/vendor/phpmailer/phpmailer/src/PHPMailer.php';
		require_once __DIR__.'/vendor/phpmailer/phpmailer/src/SMTP.php'; 
		require __DIR__.("/config.php");
		$message = file_get_contents(__DIR__.'/letter.txt');
		$mail = new PHPMailer();
		$mail->XMailer = "X-Mailer: PHP/" . phpversion();
		
		$mail->IsSMTP();
		$mail->CharSet = 'UTF-8';
		$mail->SMTPKeepAlive = true;
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = false;
		$mail->SMTPAutoTLS = false;
		$mail->SMTPOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
		$modtager = $this->modtager;
		$_SERVER['SERVER_NAME'] = $setting['SMTP_HOST'];
		$message = str_replace('{email}', $modtager, $message);

		//SMTP settings
		$mail->Host = $setting['SMTP_HOST'];
		$mail->Port = $setting['SMTP_PORT'];
		$mail->Username = $setting['SMTP_USER'];
		$mail->Password = $setting['SMTP_PASSWORD'];
	
		$mail->clearAddresses();
		$mail->SetFrom($setting['FRA_EMAIL'], $setting['FRA_NAVN']);
		$mail->AddAddress($modtager);
		$mail->IsHTML(true);
		if($setting['ATTACHMENT'] != false){
			if(file_exists($setting['ATTACHMENT'])){
				$mail->AddAttachment($setting['ATTACHMENT'], 'Zip Fil');
			}
			else{
				exit("Attachment file does not exist");
			}
		}
		
		$mail->Subject = 'test';
		$mail->Body = utf8_encode($message);
		$mail->AltBody = 'test';
		shell_exec('title=Mailer | Sent emails: '. $counterEmails);
		//Send besked
		if (!$mail->send()){
			echo "\e[0;00;1;31m[\e[0;00;1;32m".$listenummer."\e[0;00;1;31m] \e[0;00;1;33m=> \e[0;00;1;31mSMTP\e[0;00;1;31m[\e[0;00;1;31m".$mail->Host."\e[0;00;1;31m] has not been sent to: $modtager";
		}
		else
		{
			echo "\e[0;00;1;31m[\e[0;00;1;32m".$listenummer."\e[0;00;1;31m] \e[0;00;1;33m=> \e[0;00;1;32mSMTP\e[0;00;1;31m[\e[0;00;1;32m".$mail->Host."\e[0;00;1;31m] \e[0;00;1;32mhas been sent to: $modtager";
		}
		echo "\e[1;37m\n";
		$mail->clearCustomHeaders();
   
    }
}
if($setting['threads'] > 0){
	$pool = new Pool($setting['threads']);
}
else{
	$pool = new Pool(0);
}

$counter = 0;
foreach ($heleListe as $email) {
	$counter++;
    $pool->submit(new Request($email, $counter));
}
$pool->shutdown();
echo "\e[0;00;1;31mSuccessfully sended emails";
