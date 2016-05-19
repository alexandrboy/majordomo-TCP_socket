<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'TCP_socket/TCP_socket.class.php');
$TCP_socket_module = new TCP_socket();
$TCP_socket_module->getConfig();
$tmp = SQLSelectOne("SELECT ID FROM Sockets LIMIT 1");
if (!$tmp['ID'])
   exit; // no devices added -- no need to run this cycle
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check = 0;
$ping_cnt = 0;
$execute = true;
$checkEvery = 1;    // poll every 1 seconds
$ping_esc = 4;      // escape polling (for ping only)


while (1)
{
   setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
   if ((time()-$latest_check)>=$checkEvery) 
   {
        $latest_check=time();
        echo "\r\n".date('Y-m-d H:i:s').' Polling devices...\r\n';
        $TCP_socket_module->processCycle();
 
        // Получаем все сокеты из БД
        $sockets = SQLSelect("SELECT * FROM Sockets");
        $total = count($sockets);
        for($i = 0; $i < $total; $i++) 
        {
            $sock = $sockets[$i];
            $IP = $sock['IP'];
            $port = $sock['PORT'];
            // Пинг IP
            $online = ping(processTitle($sock['IP']));
            if($online)
            {
                if($execute)
                {
                    // Пинг сокета
                    $out = "ping";
                    $res = socket_sendto($socket[$i], $out, strlen($out), 0, $IP, $port);
                    if(!($res != 0))
                    {
                        $sock['STATUS'] = '0';
                        // Создаём TCP/IP сокет
                        $socket[$i] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                        if (!($socket[$i] === false)) 
                        {
                            socket_set_option($socket[$i], SOL_SOCKET, SO_RCVTIMEO, array("sec"=>0,"usec"=>10000));  // Ждать новые данные 10ms
                            // Подключаем TCP/IP сокет
                            $result = socket_connect($socket[$i], $IP, $port);
                            if (!($result === false)) 
                            {
                                $sock['STATUS'] = '1';
                            } 
        //                    else 
        //                    {
        //                        echo "\r\nFail socket_connect(): reason: ($result) " . socket_strerror(socket_last_error($socket[$i])) . "\r\n";
        //                    }
                        } 
        //                else 
        //                {
        //                    echo "\r\nFail socket_create(): reason: " . socket_strerror(socket_last_error()) . "\r\n";                 
        //                }
                    }
                    else
                    {
                        $sock['STATUS'] = '1';
                    }
                }
                // Чтение сокета
                $read_buf = '';
                $res = socket_recv($socket[$i], $read_buf, 255, 0);
//                if(!($res != 0))
//                {
//                    echo "\r\nFail to read socket at addres ".$IP."and port ".$port."\r\n";
//                }
                //echo "Bytes receive:".$res."\r\n";
                // Получаем все каналы из БД
                $channels = SQLSelect("SELECT * FROM Channels WHERE DEVICE_ID=($i + 1)");
                $cnt = count($channels);
                for($j = 0; $j < $cnt; $j++) 
                {
                    $chan = $channels[$j];
                    if($chan['LINKED_OBJECT']!='' && $chan['LINKED_PROPERTY']!='') 
                    {
                        if(preg_match('\'^GET\\([0-9]{1,}\\)$\'', $chan['VALUE']))
                        {
                            preg_match('\'[0-9]{1,}\'', $chan['VALUE'], $val);
                            $pos = strpos($read_buf, $chan['TITLE']);
                            if(!($pos === false))
                            {
                                setGlobal($chan['LINKED_OBJECT'].'.'.$chan['LINKED_PROPERTY'], $val[0]);
                            }
                        }
                        if(preg_match('\'^SET\\([0-9]{1,}\\)$\'', $chan['VALUE']))
                        {
                            preg_match('\'[0-9]{1,}\'', $chan['VALUE'], $val);
                            $status_new = getGlobal($chan['LINKED_OBJECT'].'.'.$chan['LINKED_PROPERTY']);
                            $status_old = $chan['STATUS'];
                            if($status_new === $val[0])
                            {
                                if($status_new != $status_old)
                                {
                                    $write_buf = $chan['TITLE'];
                                    // Запись сокета
                                    $res = socket_sendto($socket[$i], $write_buf, strlen($write_buf), 0, $IP, $port);
//                                    if(!($res != 0))
//                                    {
//                                        echo "\r\nFail to write socket at addres ".$IP."and port ".$port."\r\n";
//                                    }
//                                    echo "Bytes send:".$res."\r\n";
                                    usleep(10000);  // Небольшая (10ms) задержка после записи сокета
                                }
                            }
                            $chan['STATUS'] = $status_new;
                        }
                    }
                    SQLUpdate('Channels', $chan);
                }
            }
            else 
            {
                $sock['STATUS'] = '0';
            }
            SQLUpdate('Sockets', $sock);
        }
        $ping_cnt++;
        if($ping_cnt > $ping_esc)
        {
            $ping_cnt = 0;
            $execute = true;
        }
        else 
        {
            $execute = false;
        }
    }
    if (file_exists('./reboot') || IsSet($_GET['onetime']))
    {
        $db->Disconnect();
        exit;
    }
    //sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
