#!/usr/local/bin/php -c /usr/local/lib/php.root.ini -q
<?php
/*********************************************************************\
* General script which saves all password creation and changes        *
* Also handles the SpamNotify change in the SpamAssassin Setup screen *
\*********************************************************************/

  include 'config.php'; // sets $ThisServer  and $sql_password

  define('T_DA'  ,   1);
  define('T_FTP' ,   2);
  define('T_MAIL',   4);
  define('T_DTB' ,   8);

  define('T_MAIN',  15);
  define('T_ALL' , 255);

  define('T_DB_DA'  ,   'directadmin');
  define('T_DB_FTP' ,   'ftp');
  define('T_DB_MAIL',   'mail');
  define('T_DB_DTB' ,   'database');


/*
  $f = fopen('custom_script.log', 'a');
  if($f)
  {
    fwrite($f, "\n----".date('Y-m-d H:i:s')."\n_ENV = ");
    fwrite($f, print_r($_ENV, true));
  }
*/
  $ret = 0;

  $Conn = mysqli_connect($SqlServer, $SqlUsername, $SqlPassword, $SqlDatabase);
  if($Conn) {
    $accnt = addslashes($_ENV[user]);
    $user  = addslashes($_ENV[username]);
    $pass  = addslashes($_ENV[passwd]);
    $domain= addslashes($_ENV[domain]);
    $dtb   = addslashes($_ENV[database]);
    $cmd   = addslashes($_ENV[command]);
    $ip    = addslashes($_ENV[caller_ip]);

    switch($_ENV[command]) {
      //---
      case 'KABOEM_CHECK_USER' :

      
        $admin = addslashes($_ENV[creator]);

        //$info = trim(file_get('http://www.kaboemprogrammeurs.nl.nl/soap/CheckUser.php?user='.$user.'&server='.$ThisServer));
        if(strtolower($admin) == 'kaboem') {
          $msg = "User already exist on the system, choose another username";
          //echo $info;
          $ret = 1;
        }

        break;

      //---
      case 'KABOEM_CREATE_USER' :
        if($pass && $user) {
          $msg = "Create $_ENV[usertype] $user - $pass ($domain)";
          AddPass($user, $pass, $domain, $_ENV[usertype] == 'user' ? T_MAIN : T_DA);
        } else  {
          $msg = "No user for $_ENV[usertype] : pass=$pass ($domain)";
        }
        break;

      //---
      // Change Password
      case 'KABOEM_CHANGE_USER_PASSWD' :
        if($pass) {
          $type = 0;
          if($_ENV[options] === 'yes') {
            $msg = "Change password $user - $pass ( ";

            if($_ENV[system]   === 'yes') {$type |= T_DA | T_MAIL; $msg .= 'system ';}
            if($_ENV[ftp]      === 'yes') {$type |= T_FTP        ; $msg .= 'ftp ';}
            if($_ENV[database] === 'yes') {$type |= T_DTB        ; $msg .= 'dtb ';}
            $msg .= ')';
          } else {
            $type = T_MAIN;
            $msg = "Change password $user - $pass (ALL)";
          }
          ChangePass($user, $pass, $domain, $type);
        }
        break;

      //---
      case 'KABOEM_DESTROY_USER':
        $msg = "Delete $user";
        DelPass($user);
        break;

      //---
      case 'KABOEM_DOMAIN_CREATE':
        $msg = "Add domain $domain for $user";
        AddDomain($user, $domain);
        break;

      //---
      case 'KABOEM_DOMAIN_DESTROY':
        $msg = "Delete domain $domain for $user";
        DelDomain($user, $domain);

        // Delete notifier file
        $file = "/usr/local/directadmin/data/users/$user/domains/$domain.spam_notifier";
        @unlink($file);
        break;

      //---
      case 'KABOEM_FTP_CREATE':
				$admin = $user;
				$user  = $accnt.'@'.$domain;
				$msg   = "Create FTP $user - $pass";
        AddPass($user, $pass, $domain, T_FTP);
        break;

      //---
      case 'KABOEM_FTP_MODIFY':
				$admin = $user;
				$user  = $accnt.'@'.$domain;
        if($pass) {
          $msg = "Change FTP passw: $user - $pass";
          ChangePass($user, $pass, $domain, T_FTP);
        }
        else $msg = "Change FTP root: $user";
        break;

      //---
      case 'KABOEM_FTP_DELETE':
				$admin = $user;
				$user  = $accnt.'@'.$domain;
				$msg   = "Delete FTP: $user - $pass";
        DelPass($user, T_FTP);
        break;

      //---
      case 'KABOEM_MAIL_CREATE':
				$admin = $user;
				$user  = $accnt.'@'.$domain;
				$msg   = "Create Mail $user - $pass";
        AddPass($user, $pass, $domain, T_MAIL);
        break;

      //---
      case 'KABOEM_MAIL_CHANGE':
				$admin = $user;
				$user  = $accnt.'@'.$domain;
        if($pass) {
          $msg = "Change Mail passw: $user - $pass";
          ChangePass($user, $pass, $domain, T_MAIL);
        }
        else if($admin) $msg = "Change Mail quota: $user";
        else
        {
          $msg = "Change Mail passw through /CMD_CHANGE_EMAIL_PASSWORD (DA-bug): $user - ???";
          ChangePass($user, '???', $domain, T_MAIL);
        }
        break;

      //---
      case 'KABOEM_MAIL_DELETE':
				$admin = $user;
				$user  = $accnt.'@'.$domain;
				$msg   = "Delete Mail: $user - $pass";
        DelPass($user, T_MAIL);
        break;

      //---
      case 'KABOEM_DTB_CREATE':
				$admin = $user;
				$user  = $accnt;
				$msg   = "Create DTB $dtb: $user - $pass";
        AddDtbPass($user, $pass, $admin, $dtb);
        break;

      //---
      case '/CMD_DB':
        if(($_ENV[action] === 'modifyuser') && ($_ENV[passwd] === $_ENV[passwd2]) && $_ENV[passwd] && $_ENV[user])  {
          ChangePass(addslashes($_ENV[user]), $pass, $domain, T_DTB);
          $msg = "Change DTB $dtb passw: $user - $pass";
        }  else {
          if(($_ENV[action] === 'deleteuser') && ($_ENV[delete] === 'Delete Selected')) {
            $count = 0;
            foreach($_ENV as $var => $val) {
              if(substr($var, 0, 6) == 'select') {
                $val = addslashes($val);
                DelPass($val, T_DTB);
                mysqli_query($Conn, "INSERT INTO `log` SET `admin`='$user', `user`='$val', `cmd`='$cmd /DELETE_DB_USER', `Msg`='Delete DB User: $val', `ip`='$ip'");
                $count++;
              }
            }
            $msg = "Deleted $count DB Users";
          } else {
            $msg = "Dtb: $dtb - Action: $_ENV[action]";
          }
        }
        break;

      //---
      case 'KABOEM_DTB_DELETE':
        $admin = $user;
        $user = $dtb;
        $msg = "Delete DTB $user";
        DelDtbPass($dtb);
        break;

      //---
      case '/CMD_API_SPAMASSASSIN':
      case '/CMD_SPAMASSASSIN':
        if($_ENV[action] == 'save') {
          $SpamNotifier = array();
          if($_ENV[where] == 'userspamfolder') {
            if(!is_array($_ENV[SpamNotifier])) $_ENV[SpamNotifier] = array($_ENV[SpamNotifier]);

            foreach($_ENV[SpamNotifier] as $time) {
              switch($time) {
                case 8:
                case 12:
                case 16:
                  $SpamNotifier[] = $time;
              }
            }
            sort($SpamNotifier);
          }

          $file = "/usr/local/directadmin/data/users/$user/domains/$domain.spam_notifier";
          if($SpamNotifier) {
            $msg  = "SpamNotifier set to ".implode(' & ', $SpamNotifier);
            $msg .= ' : '.$file;
            file_put_contents($file, implode("\n", $SpamNotifier));
            chown($file, 'diradmin');
            chgrp($file, 'diradmin');
            chmod($file, 0644);
          } else {
            $msg = "SpamNotifier turned OFF";
            unlink($file);
          }
        }

      //---
      default:
        $admin = $user;
        $user = $_ENV[name];
        break;
    }

    $msg = addslashes($msg);

    list($x,$y) = explode('/', $cmd, 3);
    switch($y) {
      // Skip logging for there commands:
      case 'CMD_API_MAIL_QUEUE':
      case 'CMD_PLUGINS':
      case 'CMD_TICKET':
      case 'CMD_API_SHOW_USER_USAGE':
      case 'CMD_API_BANDWIDTH_BREAKDOWN':
      case 'CMD_PLUGINS_ADMIN':
      case 'CMD_API_SHOW_USER_CONFIG':
        break;

      default:
        mysqli_query($Conn, "INSERT INTO `log` SET `admin`='$admin', `user`='$user', `cmd`='$cmd', `Msg`='$msg', `ip`='$ip'");
    }
  } else  {
    $ret = 0;
    LogMsg("** FAIL selecting database '$sql_database': ".mysql_error()."\n$line");
  }


  mysqli_close($Conn);
  exit($ret);

//--------------------------------------------------

function AddPass($user, $pass, $domain, $type_mask=T_ALL, $dtb='') {
  global $ThisServer, $Conn;
  if($type_mask & T_DA  ) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user` ='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_DA."'");
  if($type_mask & T_FTP ) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user`='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_FTP."'");
  if($type_mask & T_DTB ) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user`='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_DTB."'");
  if($type_mask & T_MAIL) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user`='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_MAIL."'");
}

function ChangePass($user, $pass, $domain, $type_mask=T_ALL) {
  global $Conn;

  if (mysqli_num_rows(mysqli_query($Conn, "SELECT `server` FROM `users` WHERE `user`='$user' AND `type` & $type_mask")) == 0) {
    global $ThisServer;

    if($type_mask & T_DA  ) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user` ='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_DA."'");
    if($type_mask & T_FTP ) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user`='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_FTP."'");
    if($type_mask & T_DTB ) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user`='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_DTB."'");
    if($type_mask & T_MAIL) mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `user`='$user', `pass`='$pass', `domain`='$domain ', `type`='".T_DB_MAIL."'");
  }

  if(!mysqli_query($Conn, "UPDATE `users` SET `pass`='$pass' WHERE `user`='$user' AND `type` & $type_mask")) {
    LogMsg("FAIL ChangePass($user, $pass, $domain, $type_mask)\n - could not update log: ".mysql_error()."\n\n".print_r($_ENV, true));
  }
  if($type_mask == T_DA)  mysqli_query($Conn, "REPLACE INTO `users` SET `pass`='$pass' WHERE `user`='$user' AND `type`=".T_MAIL);
}

//---
function DelPass($user, $type_mask=T_ALL) {
  global $Conn;

  if(!mysqli_query($Conn, "DELETE FROM `users` WHERE `user`='$user' AND `type` & $type_mask")) {
    LogMsg("FAIL DelPass($user)\n - could not update log: ".mysql_error()."\n\n".print_r($_ENV, true));
  }
}

function AddDomain($user, $domain) {
  global $Conn;

  mysqli_query($Conn, "REPLACE INTO `users` SET `domain`=CONCAT(`domain`,'$domain '), `user`='$user'");
}

function GetDomains($user) {
  global $Conn;
  $result = mysqli_query($Conn, "SELECT `domain` FROM `users` WHERE `user`='$user'");
  if($result)
  {
    $dom = mysqli_fetch_assoc($result);
    return $dom[0];
  }
}

function DelDomain($user, $domain) {
  global $Conn;

  mysqli_query($Conn, "REPLACE INTO `users` SET `domain`=REPLACE(`domain`,'$domain ', ''), `domain` LIKE '%$domain %'");
  mysqli_query($Conn, "DELETE FROM `users` WHERE `domain`=''");
}

function AddDtbPass($user, $pass, $root, $dtb) {
  global $Conn;

  $result = mysqli_query($Conn, "SELECT dtb FROM users WHERE user='$user' AND type=".T_DTB);
  if($result && mysqli_num_rows($result)) {
    mysqli_query($Conn, "REPLACE INTO `users` SET `dtb`=CONCAT(`dtb`,'$dtb ') WHERE `user`='$user' AND `type`=".T_DTB);
  } else {
    global $ThisServer;

    $doms = GetDomains($root);
    mysqli_query($Conn, "REPLACE INTO `users` SET `server`='$ThisServer', `dtb`='$dtb ', `user`='$user', `pass`='$pass', `domain`='$doms', `type`=".T_DTB);
  }
}

function DelDtbPass($dtb) {
  mysqli_query($Conn, "REPLACE INTO `users` SET `dtb`=REPLACE(`dtb`,'$dtb ', '') WHERE `dtb` LIKE '%$dtb %' AND `type`=".T_DTB);
  mysqli_query($Conn, "DELETE FROM users WHERE dtb='' AND type=".T_DTB);
}

//---
function file_get($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $buffer = curl_exec($ch);
  curl_close($ch);
  return $buffer;
}

//---
function LogMsg($Msg) {
  mail('yourEmail@myDomain.com', 'DirectAdmin Log-error', $Msg);
}

?>
